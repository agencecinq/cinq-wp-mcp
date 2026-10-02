<?php
/**
 * Page template resolution.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Content
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Content;

use CinqWpMcp\Acf\LocationRules;
use CinqWpMcp\Acf\Zone;
use CinqWpMcp\Config\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Makes sure a post that just received blocks will render them.
 *
 * Some themes attach their blocks to a dedicated page template — without it,
 * ACF hides the field in the editor and the front end prints nothing. Others
 * attach them to every page but one or two templates. The difference is in
 * the zone's location rules, so they decide: a template is assigned only when
 * the rules require one the post does not have yet.
 *
 * `settings.pageTemplate` overrides the reading of the rules: `auto` (the
 * default), `none` to never touch the template, or a template path to always
 * assign when the post has none.
 */
final class TemplateResolver {

	/**
	 * Assigns the template the zone needs, if any.
	 *
	 * @param Zone $zone    Zone that was written.
	 * @param int  $post_id Post ID.
	 * @return string|null The template assigned, or null when nothing changed.
	 */
	public static function ensure( Zone $zone, int $post_id ): ?string {
		$template = self::required( $zone, $post_id );

		if ( null === $template ) {
			return null;
		}

		update_post_meta( $post_id, '_wp_page_template', $template );

		return $template;
	}

	/**
	 * Returns the template a post needs for a zone to render, without assigning it.
	 *
	 * @param Zone $zone    Zone.
	 * @param int  $post_id Post ID.
	 * @return string|null
	 */
	public static function required( Zone $zone, int $post_id ): ?string {
		// The front page renders through front-page.php whatever its template
		// says; forcing one there changes nothing but the editor's display.
		if ( (int) get_option( 'page_on_front' ) === $post_id ) {
			return null;
		}

		$post_type = get_post_type( $post_id );

		if ( ! is_string( $post_type ) ) {
			return null;
		}

		$current = (string) get_post_meta( $post_id, '_wp_page_template', true );
		$setting = (string) Config::get( 'settings.pageTemplate', 'auto' );

		if ( 'none' === $setting ) {
			return null;
		}

		if ( 'auto' !== $setting && '' !== $setting ) {
			return in_array( $current, array( '', 'default' ), true ) ? $setting : null;
		}

		return LocationRules::required_template( $zone->location(), $post_type, $current );
	}
}
