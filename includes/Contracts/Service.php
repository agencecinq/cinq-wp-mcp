<?php
/**
 * Service contract.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Contracts
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * A unit of the plugin that hooks itself into WordPress.
 *
 * Mirrors the `AgenceCinq\Service` contract used by the theme so both code
 * bases read the same way.
 */
interface Service {

	/**
	 * Registers the service's hooks.
	 *
	 * @return void
	 */
	public function run(): void;
}
