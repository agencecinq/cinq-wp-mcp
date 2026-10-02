<?php
/**
 * Service contract for theme bootstrap.
 *
 * @package WPCinquanteEtUn
 */

namespace WPCinquanteEtUn;

/**
 * Service
 *
 * Theme classes registered in Init must implement this interface.
 */
interface Service {

	/**
	 * Register hooks and run initialization. Called once by Init::run_services().
	 *
	 * @return void
	 */
	public function run(): void;
}
