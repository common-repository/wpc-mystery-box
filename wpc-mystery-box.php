<?php
/*
Plugin Name: WPC Mystery Box for WooCommerce
Plugin URI: https://wpclever.net/
Description: WPC Mystery Box allows you to sell boxes that contain randomly products.
Version: 1.1.1
Author: WPClever
Author URI: https://wpclever.net
Text Domain: wpc-mystery-box
Domain Path: /languages/
Requires Plugins: woocommerce
Requires at least: 4.0
Tested up to: 6.6
WC requires at least: 3.0
WC tested up to: 9.1
*/

defined( 'ABSPATH' ) || exit;

! defined( 'WPCMB_VERSION' ) && define( 'WPCMB_VERSION', '1.1.1' );
! defined( 'WPCMB_LITE' ) && define( 'WPCMB_LITE', __FILE__ );
! defined( 'WPCMB_FILE' ) && define( 'WPCMB_FILE', __FILE__ );
! defined( 'WPCMB_URI' ) && define( 'WPCMB_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WPCMB_DIR' ) && define( 'WPCMB_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'WPCMB_SUPPORT' ) && define( 'WPCMB_SUPPORT', 'https://wpclever.net/support?utm_source=support&utm_medium=wpcmb&utm_campaign=wporg' );
! defined( 'WPCMB_REVIEWS' ) && define( 'WPCMB_REVIEWS', 'https://wordpress.org/support/plugin/wpc-mystery-box/reviews/?filter=5' );
! defined( 'WPCMB_CHANGELOG' ) && define( 'WPCMB_CHANGELOG', 'https://wordpress.org/plugins/wpc-mystery-box/#developers' );
! defined( 'WPCMB_DISCUSSION' ) && define( 'WPCMB_DISCUSSION', 'https://wordpress.org/support/plugin/wpc-mystery-box' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WPCMB_URI );

include 'includes/dashboard/wpc-dashboard.php';
include 'includes/kit/wpc-kit.php';
include 'includes/hpos.php';

if ( ! function_exists( 'wpcmb_init' ) ) {
	add_action( 'plugins_loaded', 'wpcmb_init', 11 );

	function wpcmb_init() {
		// load text-domain
		load_plugin_textdomain( 'wpc-mystery-box', false, basename( __DIR__ ) . '/languages/' );

		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
			add_action( 'admin_notices', 'wpcmb_notice_wc' );

			return null;
		}

		include_once 'includes/class-helper.php';
		include_once 'includes/class-product.php';
		include_once 'includes/class-wpcmb.php';
	}
}

if ( ! function_exists( 'wpcmb_notice_wc' ) ) {
	function wpcmb_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Mystery Box</strong> requires WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}
