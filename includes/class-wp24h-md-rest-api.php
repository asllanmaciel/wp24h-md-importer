<?php
/**
 * REST API integration.
 *
 * @package WP24H_MD_Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WP24H_MD_REST_API {
	const OPTION_ENABLED = 'wp24h_md_api_enabled';
	const MAX_BODY_SIZE  = 2097152;

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers REST routes when the API has been explicitly enabled.
	 *
	 * @return void
	 */
	public function register_routes() {
		if ( '1' !== get_option( self::OPTION_ENABLED, '0' ) ) {
			return;
		}

		register_rest_route(
			'wp24h-md-importer/v1',
			'/import',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'markdown'        => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => null,
					),
					'update_existing' => array(
						'default'           => true,
						'type'              => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
				),
			)
		);
	}

	/**
	 * Checks whether the authenticated REST user can import posts.
	 *
	 * @return bool
	 */
	public function permissions_check() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Imports Markdown received through the REST API.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import( WP_REST_Request $request ) {
		$markdown = (string) $request->get_param( 'markdown' );

		if ( '' === trim( $markdown ) ) {
			return new WP_Error(
				'wp24h_md_empty_markdown',
				__( 'The Markdown content cannot be empty.', 'wp24h-md-importer' ),
				array( 'status' => 400 )
			);
		}

		if ( strlen( $markdown ) > self::MAX_BODY_SIZE ) {
			return new WP_Error(
				'wp24h_md_payload_too_large',
				__( 'The Markdown content exceeds the 2 MB limit.', 'wp24h-md-importer' ),
				array( 'status' => 413 )
			);
		}

		try {
			$result = WP24H_MD_Importer::import(
				$markdown,
				(bool) $request->get_param( 'update_existing' )
			);

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $result,
				),
				200
			);
		} catch ( RuntimeException $exception ) {
			return new WP_Error(
				'wp24h_md_import_error',
				$exception->getMessage(),
				array( 'status' => 400 )
			);
		} catch ( Exception $exception ) {
			return new WP_Error(
				'wp24h_md_unexpected_error',
				__( 'An unexpected error occurred while importing Markdown.', 'wp24h-md-importer' ),
				array( 'status' => 500 )
			);
		}
	}
}
