<?php
/**
 * ACF location rule interpretation.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Acf
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Acf;

defined( 'ABSPATH' ) || exit;

/**
 * Reads a field group's location rules without asking ACF to resolve them.
 *
 * ACF needs a real post to decide a `page_template` or `page_type` rule, and
 * the questions asked here come before any post exists: "can a page ever hold
 * these blocks?", "which template must a new page get for them to render?".
 * So the rules are read as data.
 *
 * ACF semantics: rule groups are OR'd together, rules inside one group are
 * AND'd. A group with no rules applies everywhere.
 */
final class LocationRules {

	/**
	 * Returns the post types a location can apply to.
	 *
	 * @param array<int, mixed>  $location    The group's `location` entry.
	 * @param array<int, string> $registrable Post types this plugin manages.
	 * @return array<int, string> In the order of `$registrable`.
	 */
	public static function post_types( array $location, array $registrable ): array {
		if ( empty( $location ) ) {
			return $registrable;
		}

		$matched = array();

		foreach ( $location as $rule_group ) {
			$rule_group = (array) $rule_group;

			// A group scoped to something other than content never matches a post.
			if ( self::targets_non_post( $rule_group ) ) {
				continue;
			}

			$allowed = $registrable;

			foreach ( $rule_group as $rule ) {
				$allowed = array_intersect( $allowed, self::rule_post_types( (array) $rule, $registrable ) );
			}

			$matched = array_merge( $matched, $allowed );
		}

		return array_values( array_filter( $registrable, static fn( string $type ): bool => in_array( $type, $matched, true ) ) );
	}

	/**
	 * Returns the options pages a location applies to.
	 *
	 * @param array<int, mixed> $location The group's `location` entry.
	 * @return array<int, string> Options page menu slugs.
	 */
	public static function options_pages( array $location ): array {
		$pages = array();

		foreach ( $location as $rule_group ) {
			foreach ( (array) $rule_group as $rule ) {
				$rule = (array) $rule;

				if ( 'options_page' === ( $rule['param'] ?? '' ) && '==' === ( $rule['operator'] ?? '==' ) ) {
					$pages[] = (string) ( $rule['value'] ?? '' );
				}
			}
		}

		return array_values( array_unique( array_filter( $pages ) ) );
	}

	/**
	 * Returns the taxonomies a location applies to.
	 *
	 * A taxonomy rule set to "all" yields every candidate.
	 *
	 * @param array<int, mixed>  $location   The group's `location` entry.
	 * @param array<int, string> $candidates Taxonomies this plugin manages.
	 * @return array<int, string> In the order of `$candidates`.
	 */
	public static function taxonomies( array $location, array $candidates ): array {
		$matched = array();

		foreach ( $location as $rule_group ) {
			foreach ( (array) $rule_group as $rule ) {
				$rule = (array) $rule;

				if ( 'taxonomy' === ( $rule['param'] ?? '' ) && '==' === ( $rule['operator'] ?? '==' ) ) {
					$value   = (string) ( $rule['value'] ?? '' );
					$matched = array_merge( $matched, 'all' === $value ? $candidates : array( $value ) );
				}
			}
		}

		return array_values( array_intersect( $candidates, $matched ) );
	}

	/**
	 * Decides which page template a post needs for a location to apply.
	 *
	 * Returns null when the current template already satisfies a rule group,
	 * or when no rule group requires a specific template. A theme that attaches
	 * its blocks to every page but one template (`page_template != styleguide`)
	 * needs nothing assigned; one that attaches them to a "Blocks" template
	 * (`page_template == page-templates/blocks-page.php`) needs that template.
	 *
	 * A rule group whose other conditions cannot be checked here — "front page",
	 * "child of", a category — neither satisfies the post nor proposes a
	 * template: it is about some other post.
	 *
	 * @param array<int, mixed> $location  The group's `location` entry.
	 * @param string            $post_type Post type of the post.
	 * @param string            $current   Current template, '' or 'default' for none.
	 * @param bool              $is_front  Whether the post is the front page.
	 * @return string|null Template to assign, or null to leave the post alone.
	 */
	public static function required_template( array $location, string $post_type, string $current, bool $is_front = false ): ?string {
		if ( empty( $location ) ) {
			return null;
		}

		$current   = '' === $current ? 'default' : $current;
		$candidate = null;

		foreach ( $location as $rule_group ) {
			$templates = self::template_constraints( (array) $rule_group, $post_type, $is_front );

			if ( null === $templates ) {
				continue;
			}

			if ( empty( $templates['required'] ) ) {
				if ( ! in_array( $current, $templates['excluded'], true ) ) {
					return null;
				}

				continue;
			}

			if ( in_array( $current, $templates['required'], true ) ) {
				return null;
			}

			$candidate ??= $templates['required'][0];
		}

		return $candidate;
	}

	/**
	 * Reads the template constraints of a rule group that applies to a post.
	 *
	 * @param array<int, mixed> $rule_group Rules AND'd together.
	 * @param string            $post_type  Post type of the post.
	 * @param bool              $is_front   Whether the post is the front page.
	 * @return array{required: array<int, string>, excluded: array<int, string>}|null
	 *         Null when the group does not apply to the post, or cannot be decided.
	 */
	private static function template_constraints( array $rule_group, string $post_type, bool $is_front ): ?array {
		$required = array();
		$excluded = array();

		foreach ( $rule_group as $rule ) {
			$rule     = (array) $rule;
			$param    = (string) ( $rule['param'] ?? '' );
			$value    = (string) ( $rule['value'] ?? '' );
			$equality = '==' === ( $rule['operator'] ?? '==' );

			switch ( $param ) {
				case 'post_type':
					if ( ( $value === $post_type ) !== $equality ) {
						return null;
					}
					break;

				case 'page_template':
				case 'post_template':
					if ( $equality ) {
						$required[] = $value;
					} else {
						$excluded[] = $value;
					}
					break;

				case 'page_type':
					if ( 'front_page' !== $value || $is_front !== $equality ) {
						return null;
					}
					break;

				default:
					return null;
			}
		}

		return array(
			'required' => $required,
			'excluded' => $excluded,
		);
	}

	/**
	 * Returns the post types a single location rule can apply to.
	 *
	 * A rule that says nothing about post types does not narrow its group, so
	 * the full list is returned rather than an empty one: the caller intersects.
	 *
	 * @param array<string, mixed> $rule        One location rule.
	 * @param array<int, string>   $registrable Post types this plugin manages.
	 * @return array<int, string>
	 */
	private static function rule_post_types( array $rule, array $registrable ): array {
		$param = (string) ( $rule['param'] ?? '' );
		$value = (string) ( $rule['value'] ?? '' );

		// Only equality narrows. Treating a negative rule as a constraint would
		// hide a post type that does hold blocks, and refusing a legitimate
		// write is worse than allowing one the editor will reject.
		if ( '==' !== (string) ( $rule['operator'] ?? '==' ) ) {
			return $registrable;
		}

		if ( 'post_type' === $param ) {
			return in_array( $value, $registrable, true ) ? array( $value ) : array();
		}

		if ( 'page_template' === $param ) {
			return self::post_types_with_template( $value, $registrable );
		}

		// front_page, top_level, parent, child: only hierarchical types.
		if ( in_array( $param, array( 'page', 'page_type', 'page_parent' ), true ) ) {
			return self::hierarchical_post_types( $registrable );
		}

		return $registrable;
	}

	/**
	 * Returns whether a rule group targets something that is not a post.
	 *
	 * @param array<int, mixed> $rule_group Rules AND'd together.
	 * @return bool
	 */
	private static function targets_non_post( array $rule_group ): bool {
		foreach ( $rule_group as $rule ) {
			$rule  = (array) $rule;
			$param = (string) ( $rule['param'] ?? '' );

			if ( '==' === ( $rule['operator'] ?? '==' )
				&& in_array( $param, array( 'options_page', 'taxonomy', 'user_form', 'user_role', 'nav_menu', 'nav_menu_item', 'widget', 'block', 'comment', 'attachment' ), true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the post types a page template is available to.
	 *
	 * A template declares its own post types through the `Template Post Type`
	 * header, and defaults to `page` without it.
	 *
	 * @param string             $template    Template file, relative to the theme.
	 * @param array<int, string> $registrable Post types this plugin manages.
	 * @return array<int, string>
	 */
	private static function post_types_with_template( string $template, array $registrable ): array {
		if ( 'default' === $template ) {
			return self::hierarchical_post_types( $registrable );
		}

		if ( '' === $template ) {
			return array();
		}

		$theme      = wp_get_theme();
		$post_types = array();

		foreach ( $registrable as $post_type ) {
			$templates = $theme->get_page_templates( null, $post_type );

			if ( is_array( $templates ) && array_key_exists( $template, $templates ) ) {
				$post_types[] = $post_type;
			}
		}

		return $post_types;
	}

	/**
	 * Returns the hierarchical post types among those this plugin manages.
	 *
	 * @param array<int, string> $registrable Post types this plugin manages.
	 * @return array<int, string>
	 */
	private static function hierarchical_post_types( array $registrable ): array {
		return array_values( array_filter( $registrable, 'is_post_type_hierarchical' ) );
	}
}
