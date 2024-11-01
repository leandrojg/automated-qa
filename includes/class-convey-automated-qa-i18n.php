<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.conveydigital.com/
 * @since      1.0.0
 *
 * @package    Convey_Automated_Qa
 * @subpackage Convey_Automated_Qa/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Convey_Automated_Qa
 * @subpackage Convey_Automated_Qa/includes
 * @author     Convey Digital <info@conveydigital.com>
 */
class Convey_Automated_Qa_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'convey-automated-qa',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
