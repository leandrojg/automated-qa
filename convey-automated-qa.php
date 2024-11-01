<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.conveydigital.com/
 * @since             1.0.0
 * @package           Convey_Automated_Qa
 *
 * @wordpress-plugin
 * Plugin Name:       Automated QA
 * Plugin URI:        https://www.conveydigital.com/
 * Description:       WordPress plugin that will allow us to check certain tasks on our clients' websites to ensure they are functioning optimally.
 * Version:           1.0.0
 * Author:            Convey Digital
 * Author URI:        https://www.conveydigital.com//
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       convey-automated-qa
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CONVEY_AUTOMATED_QA_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-convey-automated-qa-activator.php
 */
function activate_convey_automated_qa() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-convey-automated-qa-activator.php';
	Convey_Automated_Qa_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-convey-automated-qa-deactivator.php
 */
function deactivate_convey_automated_qa() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-convey-automated-qa-deactivator.php';
	Convey_Automated_Qa_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_convey_automated_qa' );
register_deactivation_hook( __FILE__, 'deactivate_convey_automated_qa' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-convey-automated-qa.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_convey_automated_qa() {

	$plugin = new Convey_Automated_Qa();
	$plugin->run();

}
run_convey_automated_qa();
