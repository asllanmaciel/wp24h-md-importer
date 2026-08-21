<?php

declare(strict_types=1);

$wp_root = getenv( 'WP_COMPAT_WP_ROOT' ) ?: '';
if ( '' === $wp_root || ! is_file( $wp_root . '/wp-load.php' ) ) {
	throw new RuntimeException( 'WordPress bootstrap is unavailable. Set WP_COMPAT_WP_ROOT.' );
}

if ( ! defined( 'ABSPATH' ) ) {
	require_once $wp_root . '/wp-load.php';
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( ! is_plugin_active( 'wp24h-md-importer/wp24h-md-importer.php' ) ) {
	throw new RuntimeException( 'WP24H MD Importer must be active before compatibility checks run.' );
}

$fixture_dir = getenv( 'WP_COMPAT_FIXTURE_DIR' ) ?: __DIR__ . '/fixtures';
$checks      = array();
$warnings    = array();
$failed      = null;

add_filter(
	'http_request_host_is_external',
	static function ( $is_external, $host ) {
		return 'fixtures' === $host ? true : $is_external;
	},
	10,
	2
);

set_error_handler(
	static function ( $severity, $message, $file, $line ) use ( &$warnings ) {
		if ( error_reporting() & $severity ) {
			$warnings[] = array(
				'severity' => $severity,
				'message'  => $message,
				'file'     => $file,
				'line'     => $line,
			);
		}
		return false;
	}
);

$assert = static function ( $condition, $name ) use ( &$checks ) {
	$checks[] = $name;
	if ( ! $condition ) {
		throw new RuntimeException( 'Check failed: ' . $name );
	}
};

try {
	$admin_id = (int) get_current_user_id();
	if ( $admin_id < 1 ) {
		throw new RuntimeException( 'A WordPress administrator must be the current compatibility runner user.' );
	}

	$basic = file_get_contents( $fixture_dir . '/basic.md' );
	$first = WP24H_MD_Importer::import( $basic );
	$assert( is_int( $first['post_id'] ) && $first['post_id'] > 0, 'basic import creates a post' );
	$assert( 'compatibilidade-basica' === get_post_field( 'post_name', $first['post_id'] ), 'basic import keeps slug' );

	$second = WP24H_MD_Importer::import( $basic );
	$assert( true === $second['updated'], 'reimport reports update' );
	$assert( $first['post_id'] === $second['post_id'], 'reimport keeps original post id' );

	$complete = file_get_contents( $fixture_dir . '/complete.md' );
	$complete_result = WP24H_MD_Importer::import( $complete );
	$complete_id     = $complete_result['post_id'];
	$assert( has_category( 'Compatibilidade', $complete_id ), 'complete import creates category' );
	$assert( has_term( 'wordpress', 'post_tag', $complete_id ), 'complete import creates tag' );
	$assert( 'SEO de compatibilidade' === get_post_meta( $complete_id, '_wp24h_md_seo_title', true ), 'complete import stores seo title' );
	$assert( 'Metadados de compatibilidade do importador.' === get_post_meta( $complete_id, '_wp24h_md_meta_description', true ), 'complete import stores meta description' );
	$assert( has_post_thumbnail( $complete_id ), 'png fixture creates featured image' );
	$png_attachment = (int) get_post_thumbnail_id( $complete_id );

	$reimport_complete = WP24H_MD_Importer::import( $complete );
	$assert( $png_attachment === (int) get_post_thumbnail_id( $reimport_complete['post_id'] ), 'png fixture reuses featured attachment' );

	foreach ( array( 'featured.jpg', 'featured.webp' ) as $asset ) {
		$variant = str_replace( 'featured.png', $asset, $complete );
		$variant = str_replace( 'compatibilidade-completa', 'compatibilidade-' . pathinfo( $asset, PATHINFO_FILENAME ), $variant );
		$variant_result = WP24H_MD_Importer::import( $variant );
		$assert( has_post_thumbnail( $variant_result['post_id'] ), $asset . ' fixture creates featured image' );
	}

	$before_invalid = count( get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'fields' => 'ids' ) ) );
	try {
		WP24H_MD_Importer::import( str_replace( 'featured.png', 'missing.png', $complete ) );
		throw new RuntimeException( 'invalid media URL was accepted' );
	} catch ( RuntimeException $exception ) {
		$assert( 'invalid media URL was accepted' !== $exception->getMessage(), 'invalid image URL is rejected' );
	}
	$after_invalid = count( get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'fields' => 'ids' ) ) );
	$assert( $before_invalid === $after_invalid, 'invalid image URL creates no attachment' );

	delete_option( WP24H_MD_REST_API::OPTION_ENABLED );
	$api = new WP24H_MD_REST_API();
	$api->register_routes();
	$routes = rest_get_server()->get_routes();
	$assert( ! isset( $routes['/wp24h-md-importer/v1/import'] ), 'rest route is disabled by default' );

	update_option( WP24H_MD_REST_API::OPTION_ENABLED, '1' );
	$api->register_routes();
	$routes = rest_get_server()->get_routes();
	$assert( isset( $routes['/wp24h-md-importer/v1/import'] ), 'rest route registers after explicit enablement' );

	$subscriber_id = (int) wp_insert_user(
		array(
			'user_login' => 'compat-subscriber',
			'user_pass'  => wp_generate_password(),
			'user_email' => 'compat-subscriber@example.test',
			'role'       => 'subscriber',
		)
	);
	wp_set_current_user( $subscriber_id );
	try {
		WP24H_MD_Importer::import( $complete );
		throw new RuntimeException( 'subscriber media import was accepted' );
	} catch ( RuntimeException $exception ) {
		$assert( 'subscriber media import was accepted' !== $exception->getMessage(), 'user without upload_files cannot import featured image' );
	}
	wp_set_current_user( $admin_id );

	$assert( empty( $warnings ), 'compatibility suite produces no PHP warnings' );
} catch ( Throwable $exception ) {
	$failed = $exception->getMessage();
} finally {
	restore_error_handler();
}

$report = array(
	'wordpress_version' => get_bloginfo( 'version' ),
	'php_version'       => PHP_VERSION,
	'plugin_version'    => WP24H_MD_IMPORTER_VERSION,
	'status'            => null === $failed ? 'PASS' : 'FAIL',
	'checks'            => $checks,
	'warnings'          => $warnings,
	'error'             => $failed,
);

$encoded_report = wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
$report_path    = getenv( 'WP_COMPAT_REPORT' ) ?: '';
if ( '' !== $report_path ) {
	if ( false === file_put_contents( $report_path, $encoded_report ) ) {
		fwrite( STDERR, "Unable to write compatibility report: {$report_path}" . PHP_EOL );
		exit( 1 );
	}
}

echo $encoded_report;

if ( null !== $failed ) {
	exit( 1 );
}
