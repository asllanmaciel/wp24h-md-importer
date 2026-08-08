<?php
/**
 * WordPress admin interface.
 *
 * @package WP24H_MD_Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WP24H_MD_Admin {
	const MAX_FILE_SIZE = 2097152;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_wp24h_md_import', array( $this, 'handle_import' ) );
		add_action( 'admin_post_wp24h_md_save_settings', array( $this, 'handle_settings' ) );
	}

	public function menu() {
		add_management_page(
			__( 'Import Markdown', 'wp24h-md-importer' ),
			__( 'Import Markdown', 'wp24h-md-importer' ),
			'edit_posts',
			'wp24h-md-importer',
			array( $this, 'render' )
		);
	}

	public function render() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'wp24h-md-importer' ) );
		}

		$result_key = 'wp24h_md_result_' . get_current_user_id();
		$result     = get_transient( $result_key );
		if ( $result ) {
			delete_transient( $result_key );
		}

		$api_enabled = '1' === get_option( WP24H_MD_REST_API::OPTION_ENABLED, '0' );
		$endpoint    = rest_url( 'wp24h-md-importer/v1/import' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Import Markdown', 'wp24h-md-importer' ); ?></h1>
			<p><?php echo esc_html__( 'Upload a Markdown file with YAML front matter to create or update a WordPress post.', 'wp24h-md-importer' ); ?></p>

			<?php if ( is_array( $result ) ) : ?>
				<?php if ( ! empty( $result['error'] ) ) : ?>
					<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $result['error'] ); ?></p></div>
				<?php else : ?>
					<div class="notice notice-success is-dismissible"><p>
						<?php echo esc_html( ! empty( $result['updated'] ) ? __( 'Post updated successfully.', 'wp24h-md-importer' ) : __( 'Post created successfully.', 'wp24h-md-importer' ) ); ?>
						<?php if ( ! empty( $result['edit_url'] ) ) : ?>
							<a href="<?php echo esc_url( $result['edit_url'] ); ?>"><?php echo esc_html__( 'Edit post', 'wp24h-md-importer' ); ?></a>
						<?php endif; ?>
					</p></div>
			<?php endif; ?>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="wp24h_md_import">
				<?php wp_nonce_field( 'wp24h_md_import', 'wp24h_md_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="wp24h_md_file"><?php echo esc_html__( 'Markdown file', 'wp24h-md-importer' ); ?></label></th>
						<td>
							<input type="file" id="wp24h_md_file" name="wp24h_md_file" accept=".md,.markdown,text/markdown,text/plain" required>
							<p class="description"><?php echo esc_html__( 'Maximum size: 2 MB.', 'wp24h-md-importer' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Behavior', 'wp24h-md-importer' ); ?></th>
						<td><label><input type="checkbox" name="update_existing" value="1" checked> <?php echo esc_html__( 'Update an existing post when the slug matches', 'wp24h-md-importer' ); ?></label></td>
					</tr>
				</table>

				<?php submit_button( __( 'Import post', 'wp24h-md-importer' ) ); ?>
			</form>

			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<hr>
				<h2><?php echo esc_html__( 'REST API', 'wp24h-md-importer' ); ?></h2>
				<p><?php echo esc_html__( 'Enable an authenticated REST endpoint for automated Markdown imports. It is disabled by default.', 'wp24h-md-importer' ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wp24h_md_save_settings">
					<?php wp_nonce_field( 'wp24h_md_save_settings', 'wp24h_md_settings_nonce' ); ?>
					<label>
						<input type="checkbox" name="api_enabled" value="1" <?php checked( $api_enabled ); ?>>
						<?php echo esc_html__( 'Enable REST API imports', 'wp24h-md-importer' ); ?>
					</label>
					<p class="description">
						<?php echo esc_html__( 'Authentication is handled by WordPress. Application Passwords over HTTPS are recommended for external automation.', 'wp24h-md-importer' ); ?>
					</p>
					<p><code><?php echo esc_html( $endpoint ); ?></code></p>
					<?php submit_button( __( 'Save API settings', 'wp24h-md-importer' ), 'secondary' ); ?>
				</form>
			<?php endif; ?>

			<hr>
			<h2><?php echo esc_html__( 'Expected front matter', 'wp24h-md-importer' ); ?></h2>
			<pre style="max-width:900px;overflow:auto;background:#fff;padding:16px;border:1px solid #ccd0d4;">---
title: "Article title"
slug: "article-slug"
date: "2026-08-09"
status: "draft"
excerpt: "Article summary"
categories:
  - Artificial Intelligence
tags:
  - AI
  - Business
seo_title: "SEO title"
meta_description: "Search engine description"
sources:
  - "https://example.com/source"
---

## Content

Markdown content...</pre>
		</div>
		<?php
	}

	public function handle_import() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to import posts.', 'wp24h-md-importer' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'wp24h_md_import', 'wp24h_md_nonce' );
		$result_key = 'wp24h_md_result_' . get_current_user_id();

		try {
			if ( empty( $_FILES['wp24h_md_file'] ) || ! isset( $_FILES['wp24h_md_file']['tmp_name'] ) ) {
				throw new RuntimeException( __( 'Choose a Markdown file.', 'wp24h-md-importer' ) );
			}

			$file = $_FILES['wp24h_md_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated before use below.
			if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
				throw new RuntimeException( __( 'The file upload failed.', 'wp24h-md-importer' ) );
			}
			if ( (int) $file['size'] > self::MAX_FILE_SIZE ) {
				throw new RuntimeException( __( 'The file exceeds the 2 MB limit.', 'wp24h-md-importer' ) );
			}

			$filename  = sanitize_file_name( wp_unslash( $file['name'] ) );
			$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
			if ( ! in_array( $extension, array( 'md', 'markdown' ), true ) ) {
				throw new RuntimeException( __( 'Invalid format. Upload a .md or .markdown file.', 'wp24h-md-importer' ) );
			}

			$tmp_name = (string) $file['tmp_name'];
			if ( ! is_uploaded_file( $tmp_name ) ) {
				throw new RuntimeException( __( 'Invalid upload.', 'wp24h-md-importer' ) );
			}

			$raw = file_get_contents( $tmp_name ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a verified local upload temporary file.
			if ( false === $raw || '' === trim( $raw ) ) {
				throw new RuntimeException( __( 'The file is empty or could not be read.', 'wp24h-md-importer' ) );
			}

			$update_existing = isset( $_POST['update_existing'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['update_existing'] ) );
			$result          = WP24H_MD_Importer::import( $raw, $update_existing );
			set_transient( $result_key, $result, MINUTE_IN_SECONDS );
		} catch ( RuntimeException $exception ) {
			set_transient( $result_key, array( 'error' => $exception->getMessage() ), MINUTE_IN_SECONDS );
		} catch ( Exception $exception ) {
			set_transient( $result_key, array( 'error' => __( 'An unexpected error occurred while importing the file.', 'wp24h-md-importer' ) ), MINUTE_IN_SECONDS );
		}

		wp_safe_redirect( admin_url( 'tools.php?page=wp24h-md-importer' ) );
		exit;
	}

	public function handle_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'wp24h-md-importer' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'wp24h_md_save_settings', 'wp24h_md_settings_nonce' );
		$enabled = isset( $_POST['api_enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['api_enabled'] ) ) ? '1' : '0';
		update_option( WP24H_MD_REST_API::OPTION_ENABLED, $enabled, false );

		wp_safe_redirect( admin_url( 'tools.php?page=wp24h-md-importer' ) );
		exit;
	}
}
