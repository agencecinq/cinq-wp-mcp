<?php
/**
 * Test harness bootstrap: stubs, assertions and theme loaders.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

require_once __DIR__ . '/stubs.php';

$GLOBALS['cinq_passed'] = 0;
$GLOBALS['cinq_failed'] = array();

/**
 * Asserts a condition.
 *
 * @param string $label     What is being checked.
 * @param bool   $condition Result.
 * @param string $detail    Extra context shown on failure.
 * @return void
 */
function check( string $label, bool $condition, string $detail = '' ): void {
	if ( $condition ) {
		++$GLOBALS['cinq_passed'];
		echo "  ok    {$label}\n";
		return;
	}

	$GLOBALS['cinq_failed'][] = $label;
	echo "  FAIL  {$label}" . ( '' !== $detail ? " — {$detail}" : '' ) . "\n";
}

/**
 * Prints a section title.
 *
 * @param string $title Title.
 * @return void
 */
function section( string $title ): void {
	echo "\n{$title}\n";
}

/**
 * Loads the starter theme's ACF registration, as on a fresh client site.
 *
 * @return void
 */
function load_starter(): void {
	World::reset();
	World::$post_types     = array( 'page', 'post' );
	World::$page_templates = array(
		'page' => array(
			'page-templates/search-page.php'     => 'Search Page',
			'page-templates/styleguide-page.php' => 'Styleguide',
		),
	);
	World::$menu_locations = array(
		'main'   => 'Main menu',
		'footer' => 'Footer menu',
	);

	acf_add_options_page( array( 'menu_slug' => 'options-theme', 'page_title' => 'Theme' ) );
	acf_add_options_page( array( 'menu_slug' => 'archive-post', 'page_title' => 'Archive Posts' ) );

	foreach ( array( 'ClonesFields', 'BlocksFields', 'PageFields', 'PostFields', 'ThemeFields', 'ArchivePostsFields' ) as $class ) {
		$fqcn = 'WPCinquanteEtUn\\Plugins\\ACF\\IncludeFields\\' . $class;
		( new $fqcn() )->fields();
	}

	World::post( 7, 'page' );
	World::post( 11, 'post' );
}

/**
 * Loads the agencecinq.com theme's ACF registration.
 *
 * @return void
 */
function load_agencecinq(): void {
	World::reset();
	World::$post_types     = array( 'page', 'post', 'case-study' );
	World::$page_templates = array(
		'page' => array( 'page-templates/blocks-page.php' => 'Blocks' ),
	);

	foreach ( array( 'ClonesFields', 'BlocksFields', 'CaseStudyFields' ) as $class ) {
		$fqcn = 'AgenceCinq\\Plugins\\ACF\\IncludeFields\\' . $class;
		( new $fqcn() )->fields();
	}

	// run() is what registers the theme's per-post-type layout filter, the
	// rule the plugin has to honour on write.
	( new AgenceCinq\Plugins\ACF\IncludeFields\BlocksFields() )->run();

	World::post( 7, 'page' );
	World::post( 9, 'case-study' );
}
