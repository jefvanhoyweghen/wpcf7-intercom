<?php
/**
 * Plugin Name: WPCF7 Intercom Integration
 * Plugin URI: https://www.outsite.co
 * Description: Custom Intercom integration for WPCF7 forms.
 * Version: 1.0
 * Author: Stijn Beauprez
 * Author URI: http://outsite.co/
 **/


define( 'WPCF7_INTERCOM_VERSION', '0.1.0' );
define( 'WPCF7_INTERCOM_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPCF7_INTERCOM_PLUGIN_NAME', trim( dirname( WPCF7_INTERCOM_BASENAME ), '/' ) );
define( 'WPCF7_INTERCOM_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
define( 'WPCF7_INTERCOM_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );

require_once (WPCF7_INTERCOM_PLUGIN_DIR . '/lib/intercom.php');