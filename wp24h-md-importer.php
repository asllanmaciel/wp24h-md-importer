<?php
/**
 * Plugin Name:       WP24H MD Importer
 * Plugin URI:        https://github.com/asllanmaciel/wp24h-md-importer
 * Description:       Import Markdown files with YAML front matter into WordPress posts with taxonomy, SEO metadata, featured images, sources, and optional authenticated REST automation.
 * Version:           1.2.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Asllan Maciel
 * Author URI:        https://asllanmaciel.com.br/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp24h-md-importer
 * Domain Path:       /languages
 *
 * @package WP24H_MD_Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP24H_MD_IMPORTER_VERSION', '1.2.0' );
define( 'WP24H_MD_IMPORTER_PATH', plugin_dir_path( __FILE__ ) );

require_once WP24H_MD_IMPORTER_PATH . 'includes/class-wp24h-md-front-matter.php';
require_once WP24H_MD_IMPORTER_PATH . 'includes/class-wp24h-md-markdown.php';
require_once WP24H_MD_IMPORTER_PATH . 'includes/class-wp24h-md-importer.php';
require_once WP24H_MD_IMPORTER_PATH . 'includes/class-wp24h-md-rest-api.php';
require_once WP24H_MD_IMPORTER_PATH . 'includes/class-wp24h-md-admin.php';

/**
 * Boots plugin integrations.
 *
 * @return void
 */
function wp24h_md_importer_init() {
	new WP24H_MD_REST_API();

	if ( is_admin() ) {
		new WP24H_MD_Admin();
	}
}
add_action( 'plugins_loaded', 'wp24h_md_importer_init' );
