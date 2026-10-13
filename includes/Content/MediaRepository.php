<?php
/**
 * Media library access.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Content
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Content;

use CinqWpMcp\Abilities\Registrar;
use WP_Error;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves, searches and imports media library attachments.
 */
final class MediaRepository {

	/**
	 * Resolves an attachment reference to an attachment ID.
	 *
	 * Accepts an ID that already exists, a URL that is already in the library,
	 * or — when importing is allowed — a remote URL to sideload.
	 *
	 * @param mixed $value  Attachment ID or URL.
	 * @param bool  $import Whether an unknown remote URL may be imported.
	 * @return int|WP_Error
	 */
	public static function resolve( $value, bool $import = true ) {
		if ( is_array( $value ) && isset( $value['id'] ) ) {
			$value = $value['id'];
		}

		if ( is_numeric( $value ) ) {
			$attachment_id = (int) $value;

			if ( 'attachment' !== get_post_type( $attachment_id ) ) {
				return new WP_Error(
					'cinq_wp_mcp_unknown_attachment',
					sprintf( 'Attachment %d does not exist. Use %s to find one.', $attachment_id, Registrar::tool( 'search-media' ) )
				);
			}

			return $attachment_id;
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return new WP_Error( 'cinq_wp_mcp_invalid_attachment', 'Expected an attachment ID or a URL.' );
		}

		$url      = trim( $value );
		$existing = attachment_url_to_postid( $url );

		if ( $existing > 0 ) {
			return $existing;
		}

		if ( ! $import ) {
			return new WP_Error(
				'cinq_wp_mcp_attachment_not_imported',
				sprintf( 'No attachment matches "%s" and importing is disabled.', $url )
			);
		}

		return self::import( $url );
	}

	/**
	 * Imports a remote file into the media library.
	 *
	 * @param string $url         Remote file URL.
	 * @param string $description Optional title/alt text.
	 * @param int    $parent_id   Optional post to attach the file to.
	 * @return int|WP_Error Attachment ID.
	 */
	public static function import( string $url, string $description = '', int $parent_id = 0 ) {
		if ( ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'cinq_wp_mcp_invalid_url', sprintf( '"%s" is not a valid URL.', $url ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$temp_file = download_url( $url );

		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		$path      = (string) wp_parse_url( $url, PHP_URL_PATH );
		$file_name = basename( $path );

		if ( '' === $file_name ) {
			$file_name = 'import-' . wp_generate_password( 8, false );
		}

		// URLs without an extension (CDNs, design tools): name the file after its real type.
		$mime = wp_get_image_mime( $temp_file );

		if ( ! wp_check_filetype( $file_name )['ext'] && $mime ) {
			$file_name .= '.' . wp_get_default_extension_for_mime_type( $mime );
		}

		$file = array(
			'name'     => sanitize_file_name( $file_name ),
			'tmp_name' => $temp_file,
		);

		$attachment_id = media_handle_sideload( $file, $parent_id, '' !== $description ? $description : null );

		if ( is_wp_error( $attachment_id ) ) {
			if ( file_exists( $temp_file ) ) {
				wp_delete_file( $temp_file );
			}

			return $attachment_id;
		}

		if ( '' !== $description ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $description ) );
		}

		return (int) $attachment_id;
	}

	/**
	 * Searches the media library.
	 *
	 * @param string $search    Free-text search across title, caption and file name.
	 * @param string $mime_type Optional MIME type prefix, e.g. `image`.
	 * @param int    $limit     Maximum number of results.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search( string $search = '', string $mime_type = '', int $limit = 20 ): array {
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => max( 1, min( 100, $limit ) ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		if ( '' !== $mime_type ) {
			$args['post_mime_type'] = $mime_type;
		}

		$query   = new WP_Query( $args );
		$results = array();

		foreach ( $query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$results[] = self::describe( $post->ID );
			}
		}

		return $results;
	}

	/**
	 * Returns a compact description of an attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string, mixed>
	 */
	public static function describe( int $attachment_id ): array {
		$metadata = wp_get_attachment_metadata( $attachment_id );

		return array(
			'id'        => $attachment_id,
			'title'     => get_the_title( $attachment_id ),
			'url'       => wp_get_attachment_url( $attachment_id ),
			'mime_type' => get_post_mime_type( $attachment_id ),
			'alt'       => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'width'     => isset( $metadata['width'] ) ? (int) $metadata['width'] : null,
			'height'    => isset( $metadata['height'] ) ? (int) $metadata['height'] : null,
		);
	}
}
