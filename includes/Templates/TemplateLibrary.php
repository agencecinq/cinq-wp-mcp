<?php
/**
 * Page template library.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Templates
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Templates;

use CinqWpMcp\Acf\ZoneRegistry;
use CinqWpMcp\Config\Config;

defined( 'ABSPATH' ) || exit;

/**
 * The page templates a theme declares in `mcp.json`, as ordered block stacks.
 *
 * A template is a design (a Figma frame, a mockup) expressed in the theme's
 * blocks, each section carrying how faithful the mapping is. This matters: an
 * AI client that knows "the booking section has no block yet" says so instead
 * of quietly approximating it and calling the page done.
 *
 * Templates name layouts, so a theme release can invalidate them. `describe()`
 * re-checks every mapping against the live ACF registration, and the settings
 * screen flags the stale ones.
 *
 * Mapping status:
 * - `exact`   the theme block is the section as designed.
 * - `approx`  the nearest existing block; expect visual differences.
 * - `theme`   rendered by the theme outside the block stack (header, footer,
 *             a hero that comes from the post's own fields, the post body).
 * - `missing` nothing in the theme covers it; a new layout would be needed.
 */
final class TemplateLibrary {

	/**
	 * What each status means, for clients.
	 */
	public const LEGEND = array(
		'exact'   => 'The theme block is the section as designed.',
		'approx'  => 'Nearest existing block; expect visual differences from the design.',
		'theme'   => 'Rendered by the theme outside the block stack; set it through "fields" or "content".',
		'missing' => 'No block covers this section. It needs a new layout in the theme: say so rather than approximating it.',
	);

	/**
	 * Returns every template, normalized, keyed by name.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all(): array {
		$templates = array();

		foreach ( (array) Config::get( 'templates', array() ) as $template ) {
			if ( ! is_array( $template ) || '' === (string) ( $template['name'] ?? '' ) ) {
				continue;
			}

			$normalized                       = self::normalize( $template );
			$templates[ $normalized['name'] ] = $normalized;
		}

		return $templates;
	}

	/**
	 * Returns the template names.
	 *
	 * @return array<int, string>
	 */
	public static function names(): array {
		return array_keys( self::all() );
	}

	/**
	 * Returns a template annotated with whether each mapped block exists.
	 *
	 * @param string $name Template name.
	 * @return array<string, mixed>|null
	 */
	public static function describe( string $name ): ?array {
		$template = self::all()[ $name ] ?? null;

		if ( null === $template ) {
			return null;
		}

		$zone      = '' !== $template['zone'] ? ZoneRegistry::get( $template['zone'] ) : ZoneRegistry::primary_for_post_type( $template['postType'] );
		$available = null === $zone ? array() : $zone->layout_names();

		$template['zone'] = null === $zone ? null : $zone->id;

		foreach ( $template['sections'] as $index => $section ) {
			$template['sections'][ $index ]['available'] = null === $section['block']
				? null
				: in_array( $section['block'], $available, true );
		}

		$template['blocks'] = self::blocks_for( $template );
		$template['gaps']   = array_values(
			array_map(
				static fn( array $section ): string => $section['label'],
				array_filter( $template['sections'], static fn( array $section ): bool => 'missing' === $section['status'] )
			)
		);
		$template['stale']  = array_values(
			array_unique(
				array_map(
					static fn( array $section ): string => (string) $section['block'],
					array_filter( $template['sections'], static fn( array $section ): bool => false === $section['available'] )
				)
			)
		);

		return $template;
	}

	/**
	 * Returns the ordered layouts a template is built from.
	 *
	 * Sections rendered by the theme, and sections with no block yet, are left
	 * out — they are not something a client can add to the stack.
	 *
	 * @param array<string, mixed> $template Normalized template.
	 * @return array<int, string>
	 */
	public static function blocks_for( array $template ): array {
		$blocks = array();

		foreach ( $template['sections'] as $section ) {
			if ( null !== $section['block'] && in_array( $section['status'], array( 'exact', 'approx' ), true ) ) {
				$blocks[] = $section['block'];
			}
		}

		return $blocks;
	}

	/**
	 * Returns every section, across templates, that has no block.
	 *
	 * @return array<string, array<int, string>> Template names keyed by section label.
	 */
	public static function gaps(): array {
		$gaps = array();

		foreach ( self::all() as $template ) {
			foreach ( $template['sections'] as $section ) {
				if ( 'missing' === $section['status'] ) {
					$gaps[ $section['label'] ][] = $template['name'];
				}
			}
		}

		return $gaps;
	}

	/**
	 * Fills a template's optional keys.
	 *
	 * @param array<string, mixed> $template Template from the config.
	 * @return array<string, mixed>
	 */
	private static function normalize( array $template ): array {
		$sections = array();

		foreach ( (array) ( $template['sections'] ?? array() ) as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			$block = isset( $section['block'] ) && '' !== (string) $section['block'] ? (string) $section['block'] : null;

			$sections[] = array(
				'label'  => (string) ( $section['label'] ?? $block ?? '' ),
				'block'  => $block,
				'status' => (string) ( $section['status'] ?? ( null === $block ? 'missing' : 'exact' ) ),
				'note'   => (string) ( $section['note'] ?? '' ),
			);
		}

		return array(
			'name'        => (string) $template['name'],
			'title'       => (string) ( $template['title'] ?? $template['name'] ),
			'description' => (string) ( $template['description'] ?? '' ),
			'postType'    => (string) ( $template['postType'] ?? 'page' ),
			'zone'        => (string) ( $template['zone'] ?? '' ),
			'source'      => (string) ( $template['source'] ?? '' ),
			'sections'    => $sections,
		);
	}
}
