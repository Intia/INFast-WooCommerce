<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://intia.fr
 * @since             1.0.0
 * @package           Infast_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       INFast WooCommerce
 * Plugin URI:        https://intia.fr
 * Description:       Logiciel de facturation pour les entrepreneurs et petites entreprises
 * Version:           1.0.0
 * Author:            INTIA
 * Author URI:        https://intia.fr
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       infast-woocommerce
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
define( 'INFAST_WOOCOMMERCE_VERSION', '1.0.0' );

/**
 * URL used for API calls
 */
define( 'INFAST_API_URL', 'https://app.demo.intia.fr/' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-infast-woocommerce-activator.php
 */
function activate_infast_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-infast-woocommerce-activator.php';
	Infast_Woocommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-infast-woocommerce-deactivator.php
 */
function deactivate_infast_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-infast-woocommerce-deactivator.php';
	Infast_Woocommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_infast_woocommerce' );
register_deactivation_hook( __FILE__, 'deactivate_infast_woocommerce' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-infast-woocommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_infast_woocommerce() {

	$plugin = new Infast_Woocommerce();
	$plugin->run();

}
run_infast_woocommerce();
