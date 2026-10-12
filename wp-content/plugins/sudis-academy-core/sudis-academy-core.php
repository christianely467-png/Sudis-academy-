<?php
/**
 * Plugin Name:       SUDIS Academy Core
 * Description:       Certificados PDF automáticos para LearnDash con número único, QR y verificación pública; campos de profesión y licencia.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            SUDIS Institute
 * License:           GPL-2.0-or-later
 * Text Domain:       sudis-academy
 */

defined( 'ABSPATH' ) || exit;

define( 'SUDIS_ACADEMY_VERSION', '0.1.0' );
define( 'SUDIS_ACADEMY_FILE', __FILE__ );
define( 'SUDIS_ACADEMY_DIR', plugin_dir_path( __FILE__ ) );
define( 'SUDIS_ACADEMY_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( SUDIS_ACADEMY_DIR . 'vendor/autoload.php' ) ) {
	require_once SUDIS_ACADEMY_DIR . 'vendor/autoload.php';
}

foreach ( array(
	'class-settings',
	'class-installer',
	'class-repository',
	'class-code-generator',
	'class-profile',
	'class-course-meta',
	'class-renderer',
	'class-mailer',
	'class-issuer',
	'class-frontend',
	'class-admin',
	'class-cli',
	'class-plugin',
) as $sudis_file ) {
	require_once SUDIS_ACADEMY_DIR . 'includes/' . $sudis_file . '.php';
}

register_activation_hook( __FILE__, array( 'Sudis\\Academy\\Installer', 'activate' ) );
add_action( 'plugins_loaded', array( 'Sudis\\Academy\\Plugin', 'boot' ) );
