<?php
/**
 * Smart Podcast Player
 *
 * The WordPress media player for the future—specializing in podcasts using Soundcloud, Libsyn, and any other podcast feed that works in iTunes.
 *
 * @package   SPP
 * @author    Smart Podcast Player <support@smartpodcastplayer.com>
 * @link      http://smartpodcastplayer.com
 * @copyright 2015 SPI Labs, LLC
 * 
 * @wordpress-plugin
 * Plugin Name:       Smart Podcast Player
 * Plugin URI:        http://support.smartpodcastplayer.com
 * Description:       The WordPress media player for the future—specializing in podcasts using Soundcloud, Libsyn, and any other podcast feed that works in iTunes.
 * Version:           1.4.0
 * Author:            Smart Podcast Player
 * Author URI:        http://smartpodcastplayer.com
 * Text Domain:       smart-podcast-player
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define course constants
if ( ! defined( 'SPP_PLUGIN_BASE' ) ) {
	define( 'SPP_PLUGIN_BASE', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'SPP_PLUGIN_URL' ) ) {
	define( 'SPP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'SPP_ASSETS_URL' ) ) {
	define( 'SPP_ASSETS_URL', SPP_PLUGIN_URL . 'assets' . '/' );
}

if ( ! defined( 'SPP_ASSETS_PATH' ) ) {
	define( 'SPP_ASSETS_PATH', SPP_PLUGIN_BASE . 'assets' . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'SPP_INCLUDES_PATH' ) ) {
	define( 'SPP_INCLUDES_PATH', SPP_PLUGIN_BASE . 'includes' . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'SPP_SETTINGS_URL' ) ) {
	define( 'SPP_SETTINGS_URL', admin_url( 'options-general.php?page=spp-player' ) );
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( SPP_PLUGIN_BASE . 'classes/core.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'SPP_Core', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SPP_Core', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'SPP_Core', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() ) {
	require_once( SPP_PLUGIN_BASE . 'classes/admin/core.php' );
	add_action( 'plugins_loaded', array( 'SPP_Admin_Core', 'get_instance' ) );
}
