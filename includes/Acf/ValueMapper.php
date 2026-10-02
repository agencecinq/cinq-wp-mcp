<?php
/**
 * Maps MCP input onto ACF values.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Acf
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Acf;

use CinqWpMcp\Abilities\Registrar;
use CinqWpMcp\Content\MediaRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Validates and normalizes the values an MCP client sends for ACF fields.
 *
 * Validation is collected rather than thrown: an AI client is far better served
 * by a single response listing every problem with its path
 * (`blocks[2].content.heading`) than by a stream of one-error-at-a-time
 * failures.
 */
final class ValueMapper {

	/**
	 * Collected validation errors.
	 *
	 * @var array<int, array{path: string, message: string}>
	 */
	private array $errors = array();

	/**
	 * Whether missing media should be imported from a URL.
	 *
	 * @var bool
	 */
	private bool $import_media;

	/**
	 * Post type the blocks are being written to, when it is known.
	 *
	 * @var string
	 */
	private string $post_type = '';

	/**
	 * Zone the blocks are being written to, when it is known.
	 *
	 * @var Zone|null
	 */
	private ?Zone $zone = null;

	/**
	 * Block layouts allowed in this scope, keyed by name.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $layouts = array();

	/**
	 * Name of the tool that lists block types, for error messages.
	 *
	 * @var string
	 */
	private string $list_tool = 'list-block-types';

	/**
	 * Constructor.
	 *
	 * @param bool $import_media Whether URLs may be sideloaded into the media library.
	 */
	public function __construct( bool $import_media = true ) {
		$this->import_media = $import_media;

		if ( class_exists( Registrar::class ) ) {
			$this->list_tool = Registrar::tool( 'list-block-types' );
		}
	}

	/**
	 * Restricts the accepted blocks to those the theme allows in a zone.
	 *
	 * Without this the mapper would accept every layout of the zone, which is
	 * right for a schema dump and wrong for a write: a layout the theme reserves
	 * for another content type saves, then vanishes the next time the post is
	 * saved from the editor, which cannot render its row.
	 *
	 * @param Zone   $zone      Zone being written to.
	 * @param string $post_type Post type of the post, or '' for an options page.
	 * @param int    $post_id   Post being written to, or 0.
	 * @return self
	 */
	public function scope_to_zone( Zone $zone, string $post_type = '', int $post_id = 0 ): self {
		$this->zone      = $zone;
		$this->post_type = $post_type;
		$this->layouts   = '' === $post_type
			? $zone->layouts()
			: ZoneRegistry::layouts_for_post_type( $zone, $post_type, $post_id );

		return $this;
	}

	/**
	 * Returns the errors collected so far.
	 *
	 * @return array<int, array{path: string, message: string}>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Returns whether any error was collected.
	 *
	 * @return bool
	 */
	public function has_errors(): bool {
		return ! empty( $this->errors );
	}

	/**
	 * Maps a list of block rows onto ACF flexible content rows.
	 *
	 * Without `$layouts`, the rows are checked against the zone the mapper is
	 * scoped to. A flexible field nested inside a block passes its own.
	 *
	 * @param array<int, mixed>                        $rows    Blocks as sent by the client.
	 * @param string                                   $path    Base path used in error messages.
	 * @param array<string, array<string, mixed>>|null $layouts Layouts keyed by name, or null for the scope's.
	 * @return array<int, array<string, mixed>>
	 */
	public function map_blocks( array $rows, string $path = 'blocks', ?array $layouts = null ): array {
		$scoped  = null === $layouts;
		$layouts = $layouts ?? $this->layouts;
		$mapped  = array();

		foreach ( array_values( $rows ) as $index => $row ) {
			$row_path = sprintf( '%s[%d]', $path, $index );

			if ( ! is_array( $row ) ) {
				$this->error( $row_path, 'Each block must be an object.' );
				continue;
			}

			$layout_name = (string) ( $row['acf_fc_layout'] ?? $row['type'] ?? '' );

			if ( '' === $layout_name ) {
				$this->error( $row_path, sprintf( 'Missing "acf_fc_layout". Call %s for the available block types.', $this->list_tool ) );
				continue;
			}

			if ( ! isset( $layouts[ $layout_name ] ) ) {
				$this->error( $row_path, $scoped ? $this->unavailable_block_message( $layout_name, $layouts ) : sprintf( 'Unknown block type "%s". Available: %s.', $layout_name, implode( ', ', array_keys( $layouts ) ) ) );
				continue;
			}

			unset( $row['acf_fc_layout'], $row['type'] );

			$mapped[] = array( 'acf_fc_layout' => $layout_name ) + $this->map_fields(
				$layouts[ $layout_name ]['sub_fields'] ?? array(),
				$row,
				$row_path
			);
		}

		return $mapped;
	}

	/**
	 * Explains why a block type cannot be used here.
	 *
	 * A layout the theme reserves for another content type deserves a different
	 * answer than a name that does not exist at all: the client should switch
	 * content type or drop the block, not go hunting for a typo.
	 *
	 * @param string                              $layout_name Requested layout.
	 * @param array<string, array<string, mixed>> $layouts     Layouts allowed here.
	 * @return string
	 */
	private function unavailable_block_message( string $layout_name, array $layouts ): string {
		$available = implode( ', ', array_keys( $layouts ) );

		if ( null !== $this->zone && '' !== $this->post_type && null !== $this->zone->layout( $layout_name ) ) {
			$reserved_for = ZoneRegistry::layout_post_types( $this->zone, $layout_name );

			return sprintf(
				'The block type "%s" is not available on "%s" content: the theme keeps it for %s. Available here: %s.',
				$layout_name,
				$this->post_type,
				empty( $reserved_for ) ? 'another content type' : implode( ', ', $reserved_for ),
				$available
			);
		}

		return sprintf(
			'Unknown block type "%s". Available: %s.',
			$layout_name,
			$available
		);
	}

	/**
	 * Maps an associative array of input onto a list of resolved ACF fields.
	 *
	 * A partial mapping only checks what was sent: required fields are not
	 * demanded and defaults are not applied, because the values are merged
	 * into what is already stored — an `update` operation, an options page.
	 * Groups inherit it; repeater rows and blocks, which replace what was
	 * there, do not.
	 *
	 * @param array<int, array<string, mixed>> $fields  Resolved ACF fields.
	 * @param array<string, mixed>             $input   Values sent by the client.
	 * @param string                           $path    Base path used in error messages.
	 * @param bool                             $partial Whether the values merge into stored ones.
	 * @return array<string, mixed> Values keyed by field name.
	 */
	public function map_fields( array $fields, array $input, string $path = '', bool $partial = false ): array {
		$by_name = array();

		foreach ( $fields as $field ) {
			$name = (string) ( $field['name'] ?? '' );

			if ( '' !== $name ) {
				$by_name[ $name ] = $field;
			}
		}

		$accepted = implode( ', ', array_keys( $by_name ) );
		$accepted = '' === $accepted ? 'none' : $accepted;

		foreach ( array_keys( $input ) as $key ) {
			if ( ! isset( $by_name[ (string) $key ] ) ) {
				$this->error(
					$this->join( $path, (string) $key ),
					sprintf( 'Unknown field. Accepted here: %s.', $accepted )
				);
			}
		}

		$values = array();

		foreach ( $by_name as $key => $field ) {
			// PHP casts numeric array keys back to integers, and the theme's
			// media clone names its two images `0` and `1`.
			$name = (string) $key;

			if ( ! array_key_exists( $name, $input ) ) {
				if ( $partial || empty( $field['required'] ) ) {
					continue;
				}

				// The editor pre-fills a required field with its default; a
				// client that leaves it out gets the same.
				if ( SchemaBuilder::has_default( $field ) ) {
					$values[ $name ] = $this->map_field( $field, $field['default_value'], $this->join( $path, $name ) );
					continue;
				}

				$this->error( $this->join( $path, $name ), 'Required field is missing.' );
				continue;
			}

			$values[ $name ] = $this->map_field( $field, $input[ $name ], $this->join( $path, $name ), $partial );
		}

		return $values;
	}

	/**
	 * Maps a single value onto its ACF representation.
	 *
	 * @param array<string, mixed> $field   Resolved ACF field.
	 * @param mixed                $value   Value sent by the client.
	 * @param string               $path    Path used in error messages.
	 * @param bool                 $partial Whether a group value merges into the stored one.
	 * @return mixed
	 */
	public function map_field( array $field, $value, string $path, bool $partial = false ) {
		$type = (string) ( $field['type'] ?? 'text' );

		if ( null === $value ) {
			return '';
		}

		switch ( $type ) {
			case 'group':
				if ( ! is_array( $value ) ) {
					$this->error( $path, 'Expected an object.' );
					return array();
				}

				return $this->map_fields( $field['sub_fields'] ?? array(), $value, $path, $partial );

			case 'repeater':
				return $this->map_repeater( $field, $value, $path );

			case 'flexible_content':
				if ( ! is_array( $value ) ) {
					$this->error( $path, 'Expected an array of blocks.' );
					return array();
				}

				return $this->map_blocks( $value, $path, Zone::index_layouts( $field ) );

			case 'true_false':
				return $this->to_bool( $value ) ? 1 : 0;

			case 'number':
			case 'range':
				return $this->map_number( $field, $value, $path );

			case 'select':
			case 'radio':
			case 'button_group':
			case 'checkbox':
				return $this->map_choice( $field, $value, $path );

			case 'link':
				return $this->map_link( $value, $path );

			case 'image':
			case 'file':
				return $this->map_attachment( $value, $path );

			case 'gallery':
				return $this->map_gallery( $value, $path );

			case 'post_object':
				return empty( $field['multiple'] )
					? $this->map_post_reference( $field, $value, $path )
					: $this->map_reference_list( $field, $value, $path );

			case 'relationship':
				return $this->map_reference_list( $field, $value, $path );

			case 'taxonomy':
				return $this->map_taxonomy( $field, $value, $path );

			case 'wysiwyg':
				return is_string( $value ) ? wp_kses_post( $value ) : $this->expect_string( $value, $path );

			default:
				return $this->expect_string( $value, $path );
		}
	}

	/**
	 * Maps a repeater value.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @param mixed                $value Value sent by the client.
	 * @param string               $path  Path used in error messages.
	 * @return array<int, array<string, mixed>>
	 */
	private function map_repeater( array $field, $value, string $path ): array {
		if ( ! is_array( $value ) ) {
			$this->error( $path, 'Expected an array of rows.' );
			return array();
		}

		$max = (int) ( $field['max'] ?? 0 );

		if ( $max > 0 && count( $value ) > $max ) {
			$this->error( $path, sprintf( 'Too many rows: %d sent, %d allowed.', count( $value ), $max ) );
		}

		$rows = array();

		foreach ( array_values( $value ) as $index => $row ) {
			$row_path = sprintf( '%s[%d]', $path, $index );

			if ( ! is_array( $row ) ) {
				$this->error( $row_path, 'Expected an object.' );
				continue;
			}

			$rows[] = $this->map_fields( $field['sub_fields'] ?? array(), $row, $row_path );
		}

		return $rows;
	}

	/**
	 * Maps a numeric value.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @param mixed                $value Value sent by the client.
	 * @param string               $path  Path used in error messages.
	 * @return int|float|string
	 */
	private function map_number( array $field, $value, string $path ) {
		if ( '' === $value ) {
			return '';
		}

		if ( ! is_numeric( $value ) ) {
			$this->error( $path, 'Expected a number.' );
			return '';
		}

		$number = 0 + $value;

		if ( isset( $field['min'] ) && '' !== $field['min'] && $number < (float) $field['min'] ) {
			$this->error( $path, sprintf( 'Value %s is below the minimum of %s.', $number, $field['min'] ) );
		}

		if ( isset( $field['max'] ) && '' !== $field['max'] && $number > (float) $field['max'] ) {
			$this->error( $path, sprintf( 'Value %s is above the maximum of %s.', $number, $field['max'] ) );
		}

		return $number;
	}

	/**
	 * Maps a value against an ACF choices list.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @param mixed                $value Value sent by the client.
	 * @param string               $path  Path used in error messages.
	 * @return mixed
	 */
	private function map_choice( array $field, $value, string $path ) {
		$choices  = array_map( 'strval', array_keys( (array) ( $field['choices'] ?? array() ) ) );
		$multiple = 'checkbox' === ( $field['type'] ?? '' ) || ! empty( $field['multiple'] );

		$check = function ( $candidate, string $candidate_path ) use ( $choices ) {
			$candidate = is_scalar( $candidate ) ? (string) $candidate : '';

			if ( '' === $candidate ) {
				return '';
			}

			if ( ! empty( $choices ) && ! in_array( $candidate, $choices, true ) ) {
				$this->error(
					$candidate_path,
					sprintf( 'Value "%s" is not allowed. Allowed values: %s.', $candidate, implode( ', ', $choices ) )
				);

				return '';
			}

			return $candidate;
		};

		if ( ! $multiple ) {
			if ( is_array( $value ) ) {
				$this->error( $path, 'Expected a single value.' );
				return '';
			}

			return $check( $value, $path );
		}

		$values = is_array( $value ) ? $value : array( $value );
		$mapped = array();

		foreach ( array_values( $values ) as $index => $item ) {
			$checked = $check( $item, sprintf( '%s[%d]', $path, $index ) );

			if ( '' !== $checked ) {
				$mapped[] = $checked;
			}
		}

		return $mapped;
	}

	/**
	 * Maps an ACF link value.
	 *
	 * @param mixed  $value Value sent by the client.
	 * @param string $path  Path used in error messages.
	 * @return array<string, string>|string
	 */
	private function map_link( $value, string $path ) {
		if ( is_string( $value ) ) {
			$value = array( 'url' => $value );
		}

		if ( ! is_array( $value ) ) {
			$this->error( $path, 'Expected a link object with a "url" property.' );
			return '';
		}

		$url = trim( (string) ( $value['url'] ?? '' ) );

		if ( '' === $url ) {
			return '';
		}

		$target = (string) ( $value['target'] ?? '' );

		if ( ! in_array( $target, array( '', '_blank' ), true ) ) {
			$this->error( $this->join( $path, 'target' ), 'Expected "" or "_blank".' );
			$target = '';
		}

		return array(
			'title'  => sanitize_text_field( (string) ( $value['title'] ?? '' ) ),
			'url'    => esc_url_raw( $url ),
			'target' => $target,
		);
	}

	/**
	 * Resolves an attachment reference to an attachment ID.
	 *
	 * @param mixed  $value Attachment ID or URL.
	 * @param string $path  Path used in error messages.
	 * @return int|string
	 */
	private function map_attachment( $value, string $path ) {
		if ( '' === $value || null === $value ) {
			return '';
		}

		$attachment_id = MediaRepository::resolve( $value, $this->import_media );

		if ( is_wp_error( $attachment_id ) ) {
			$this->error( $path, $attachment_id->get_error_message() );
			return '';
		}

		return $attachment_id;
	}

	/**
	 * Maps a gallery value.
	 *
	 * @param mixed  $value Value sent by the client.
	 * @param string $path  Path used in error messages.
	 * @return array<int, int>
	 */
	private function map_gallery( $value, string $path ): array {
		if ( ! is_array( $value ) ) {
			$this->error( $path, 'Expected an array of attachments.' );
			return array();
		}

		$ids = array();

		foreach ( array_values( $value ) as $index => $item ) {
			$resolved = $this->map_attachment( $item, sprintf( '%s[%d]', $path, $index ) );

			if ( is_int( $resolved ) ) {
				$ids[] = $resolved;
			}
		}

		return $ids;
	}

	/**
	 * Resolves a single post reference.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @param mixed                $value Post ID or slug.
	 * @param string               $path  Path used in error messages.
	 * @return int|string
	 */
	private function map_post_reference( array $field, $value, string $path ) {
		if ( '' === $value || null === $value ) {
			return '';
		}

		$post_types = array_filter( (array) ( $field['post_type'] ?? array() ) );
		$post_id    = 0;

		if ( is_numeric( $value ) ) {
			$post_id = (int) $value;
		} else {
			$found = get_page_by_path( (string) $value, OBJECT, empty( $post_types ) ? 'any' : $post_types );

			if ( $found instanceof \WP_Post ) {
				$post_id = $found->ID;
			}
		}

		$post = $post_id > 0 ? get_post( $post_id ) : null;

		if ( ! $post instanceof \WP_Post ) {
			$this->error( $path, sprintf( 'No post found for "%s".', is_scalar( $value ) ? (string) $value : gettype( $value ) ) );
			return '';
		}

		if ( ! empty( $post_types ) && ! in_array( $post->post_type, $post_types, true ) ) {
			$this->error(
				$path,
				sprintf( 'Post %d is a "%s"; expected one of: %s.', $post->ID, $post->post_type, implode( ', ', $post_types ) )
			);
			return '';
		}

		return $post->ID;
	}

	/**
	 * Resolves a list of post references.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @param mixed                $value Value sent by the client.
	 * @param string               $path  Path used in error messages.
	 * @return array<int, int>
	 */
	private function map_reference_list( array $field, $value, string $path ): array {
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		$ids = array();

		foreach ( array_values( $value ) as $index => $item ) {
			$resolved = $this->map_post_reference( $field, $item, sprintf( '%s[%d]', $path, $index ) );

			if ( is_int( $resolved ) ) {
				$ids[] = $resolved;
			}
		}

		return $ids;
	}

	/**
	 * Resolves taxonomy term references.
	 *
	 * @param array<string, mixed> $field Resolved ACF field.
	 * @param mixed                $value Value sent by the client.
	 * @param string               $path  Path used in error messages.
	 * @return array<int, int>|int|string
	 */
	private function map_taxonomy( array $field, $value, string $path ) {
		$taxonomy = (string) ( $field['taxonomy'] ?? '' );
		$multiple = in_array( $field['field_type'] ?? '', array( 'checkbox', 'multi_select' ), true );

		$resolve = function ( $candidate, string $candidate_path ) use ( $taxonomy ) {
			if ( '' === $candidate || null === $candidate ) {
				return 0;
			}

			$term = is_numeric( $candidate )
				? get_term( (int) $candidate, $taxonomy )
				: get_term_by( 'slug', (string) $candidate, $taxonomy );

			if ( ! $term instanceof \WP_Term ) {
				$this->error(
					$candidate_path,
					sprintf( 'No term "%s" in the "%s" taxonomy.', is_scalar( $candidate ) ? (string) $candidate : '?', $taxonomy )
				);

				return 0;
			}

			return $term->term_id;
		};

		if ( ! $multiple && ! is_array( $value ) ) {
			$term_id = $resolve( $value, $path );

			return $term_id > 0 ? $term_id : '';
		}

		$values = is_array( $value ) ? $value : array( $value );
		$ids    = array();

		foreach ( array_values( $values ) as $index => $item ) {
			$term_id = $resolve( $item, sprintf( '%s[%d]', $path, $index ) );

			if ( $term_id > 0 ) {
				$ids[] = $term_id;
			}
		}

		return $ids;
	}

	/**
	 * Casts a loosely-typed boolean.
	 *
	 * @param mixed $value Value sent by the client.
	 * @return bool
	 */
	private function to_bool( $value ): bool {
		if ( is_string( $value ) ) {
			return in_array( strtolower( $value ), array( '1', 'true', 'yes', 'on' ), true );
		}

		return (bool) $value;
	}

	/**
	 * Coerces a scalar to a sanitized string.
	 *
	 * @param mixed  $value Value sent by the client.
	 * @param string $path  Path used in error messages.
	 * @return string
	 */
	private function expect_string( $value, string $path ): string {
		if ( is_array( $value ) || is_object( $value ) ) {
			$this->error( $path, 'Expected a string.' );
			return '';
		}

		if ( is_bool( $value ) ) {
			return $value ? '1' : '';
		}

		return (string) $value;
	}

	/**
	 * Records a validation error.
	 *
	 * @param string $path    Path of the offending value.
	 * @param string $message Human-readable explanation.
	 * @return void
	 */
	private function error( string $path, string $message ): void {
		$this->errors[] = array(
			'path'    => $path,
			'message' => $message,
		);
	}

	/**
	 * Joins two path segments.
	 *
	 * @param string $base    Base path.
	 * @param string $segment Segment to append.
	 * @return string
	 */
	private function join( string $base, string $segment ): string {
		return '' === $base ? $segment : $base . '.' . $segment;
	}
}
