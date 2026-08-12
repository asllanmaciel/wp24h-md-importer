<?php
/**
 * Post importer service.
 *
 * @package WP24H_MD_Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WP24H_MD_Importer {
	public static function import( $raw, $update_existing = true ) {
		$parsed = WP24H_MD_Front_Matter::parse( $raw );
		$meta   = $parsed['meta'];
		$body   = $parsed['body'];

		$title = isset( $meta['title'] ) ? sanitize_text_field( (string) $meta['title'] ) : '';
		if ( '' === $title ) {
			throw new RuntimeException( __( 'The front matter must contain a title field.', 'wp24h-md-importer' ) );
		}

		$slug               = isset( $meta['slug'] ) ? sanitize_title( (string) $meta['slug'] ) : sanitize_title( $title );
		$status             = self::allowed_status( isset( $meta['status'] ) ? $meta['status'] : 'draft' );
		$excerpt            = isset( $meta['excerpt'] ) ? sanitize_textarea_field( (string) $meta['excerpt'] ) : '';
		$date               = self::normalize_date( isset( $meta['date'] ) ? $meta['date'] : '' );
		$content            = WP24H_MD_Markdown::to_html( $body );
		$featured_image     = isset( $meta['featured_image'] ) ? trim( (string) $meta['featured_image'] ) : '';
		$featured_image_alt = isset( $meta['featured_image_alt'] ) ? sanitize_text_field( (string) $meta['featured_image_alt'] ) : '';

		if ( in_array( $status, array( 'publish', 'private' ), true ) && ! current_user_can( 'publish_posts' ) ) {
			$status = 'draft';
		}

		$postarr = array(
			'post_type'    => 'post',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_status'  => $status,
			'post_excerpt' => $excerpt,
			'post_content' => wp_kses_post( $content ),
		);

		if ( $date ) {
			$postarr['post_date']     = $date;
			$postarr['post_date_gmt'] = get_gmt_from_date( $date );
		}

		$existing = null;
		if ( $update_existing && '' !== $slug ) {
			$existing = get_page_by_path( $slug, OBJECT, 'post' );
			if ( $existing instanceof WP_Post ) {
				if ( ! current_user_can( 'edit_post', $existing->ID ) ) {
					throw new RuntimeException( __( 'You are not allowed to update the existing post.', 'wp24h-md-importer' ) );
				}
				$postarr['ID'] = $existing->ID;
			}
		}

		$post_id = wp_insert_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $post_id ) ) {
			throw new RuntimeException( $post_id->get_error_message() );
		}

		self::set_categories( $post_id, isset( $meta['categories'] ) ? $meta['categories'] : array() );
		self::set_tags( $post_id, isset( $meta['tags'] ) ? $meta['tags'] : array() );
		self::set_seo_meta( $post_id, $meta );
		self::set_sources( $post_id, isset( $meta['sources'] ) ? $meta['sources'] : array() );
		$featured_image_id = self::set_featured_image( $post_id, $featured_image, $featured_image_alt );

		update_post_meta( $post_id, '_wp24h_md_imported_at', current_time( 'mysql' ) );
		update_post_meta( $post_id, '_wp24h_md_importer_version', WP24H_MD_IMPORTER_VERSION );

		return array(
			'post_id'           => $post_id,
			'updated'           => (bool) $existing,
			'featured_image_id' => $featured_image_id,
			'edit_url'          => get_edit_post_link( $post_id, '' ),
			'view_url'          => get_permalink( $post_id ),
		);
	}

	private static function allowed_status( $status ) {
		$status  = sanitize_key( (string) $status );
		$allowed = array( 'draft', 'pending', 'publish', 'private' );
		return in_array( $status, $allowed, true ) ? $status : 'draft';
	}

	private static function normalize_date( $date ) {
		$date = trim( (string) $date );
		if ( '' === $date ) {
			return null;
		}
		$timezone = wp_timezone();
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$local = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $timezone );
			if ( false === $local ) {
				return null;
			}
			$now   = new DateTimeImmutable( 'now', $timezone );
			$local = $local->setTime( (int) $now->format( 'H' ), (int) $now->format( 'i' ), (int) $now->format( 's' ) );
			return $local->format( 'Y-m-d H:i:s' );
		}
		try {
			$local = new DateTimeImmutable( $date, $timezone );
			return $local->setTimezone( $timezone )->format( 'Y-m-d H:i:s' );
		} catch ( Exception $exception ) {
			return null;
		}
	}

	private static function normalize_list( $value ) {
		if ( is_array( $value ) ) {
			return array_values( array_filter( array_map( static function ( $item ) {
				return trim( (string) $item );
			}, $value ) ) );
		}
		if ( is_string( $value ) && '' !== $value ) {
			$trimmed = trim( $value );
			if ( strlen( $trimmed ) >= 2 && '[' === $trimmed[0] && ']' === $trimmed[ strlen( $trimmed ) - 1 ] ) {
				$inside = trim( substr( $trimmed, 1, -1 ) );
				if ( '' === $inside ) {
					return array();
				}
				return array_values( array_filter( array_map( static function ( $item ) {
					return trim( $item, " \t\n\r\0\x0B\"'" );
				}, explode( ',', $inside ) ) ) );
			}
			return array( $trimmed );
		}
		return array();
	}

	private static function set_categories( $post_id, $categories ) {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		$term_ids = array();
		foreach ( self::normalize_list( $categories ) as $name ) {
			$name = sanitize_text_field( $name );
			if ( '' === $name ) {
				continue;
			}
			$term = term_exists( $name, 'category' );
			if ( ! $term ) {
				$term = wp_insert_term( $name, 'category' );
			}
			if ( ! is_wp_error( $term ) ) {
				$term_ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
			}
		}
		if ( ! empty( $term_ids ) ) {
			wp_set_post_categories( $post_id, $term_ids, false );
		}
	}

	private static function set_tags( $post_id, $tags ) {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		$tags = array_values( array_filter( array_map( 'sanitize_text_field', self::normalize_list( $tags ) ) ) );
		if ( ! empty( $tags ) ) {
			wp_set_post_tags( $post_id, $tags, false );
		}
	}

	private static function set_seo_meta( $post_id, $meta ) {
		$seo_title   = isset( $meta['seo_title'] ) ? sanitize_text_field( (string) $meta['seo_title'] ) : '';
		$description = isset( $meta['meta_description'] ) ? sanitize_textarea_field( (string) $meta['meta_description'] ) : '';
		if ( '' !== $seo_title ) {
			update_post_meta( $post_id, '_wp24h_md_seo_title', $seo_title );
			if ( defined( 'WPSEO_VERSION' ) ) {
				update_post_meta( $post_id, '_yoast_wpseo_title', $seo_title );
			}
			if ( defined( 'RANK_MATH_VERSION' ) ) {
				update_post_meta( $post_id, 'rank_math_title', $seo_title );
			}
		}
		if ( '' !== $description ) {
			update_post_meta( $post_id, '_wp24h_md_meta_description', $description );
			if ( defined( 'WPSEO_VERSION' ) ) {
				update_post_meta( $post_id, '_yoast_wpseo_metadesc', $description );
			}
			if ( defined( 'RANK_MATH_VERSION' ) ) {
				update_post_meta( $post_id, 'rank_math_description', $description );
			}
		}
	}

	private static function set_sources( $post_id, $sources ) {
		$sources = array_values( array_filter( array_map( static function ( $url ) {
			return esc_url_raw( trim( (string) $url ), array( 'http', 'https' ) );
		}, self::normalize_list( $sources ) ) ) );
		update_post_meta( $post_id, '_wp24h_md_sources', $sources );
	}

	private static function set_featured_image( $post_id, $image_url, $alt_text ) {
		$image_url = trim( (string) $image_url );
		if ( '' === $image_url ) {
			return null;
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			throw new RuntimeException( __( 'You are not allowed to upload the featured image.', 'wp24h-md-importer' ) );
		}

		$image_url = esc_url_raw( $image_url, array( 'http', 'https' ) );
		if ( '' === $image_url || ! wp_http_validate_url( $image_url ) ) {
			throw new RuntimeException( __( 'The featured_image field must contain a valid public HTTP(S) image URL.', 'wp24h-md-importer' ) );
		}

		$attachment_id = self::find_featured_image_by_source( $image_url );
		if ( ! $attachment_id ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$attachment_id = media_sideload_image( $image_url, $post_id, $alt_text, 'id' );
			if ( is_wp_error( $attachment_id ) ) {
				throw new RuntimeException(
					sprintf(
						/* translators: %s: image sideload error message. */
						__( 'Could not import the featured image: %s', 'wp24h-md-importer' ),
						$attachment_id->get_error_message()
					)
				);
			}

			update_post_meta( $attachment_id, '_wp24h_md_source_url', $image_url );
		}

		if ( '' !== $alt_text ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		}

		set_post_thumbnail( $post_id, $attachment_id );

		return (int) $attachment_id;
	}

	private static function find_featured_image_by_source( $image_url ) {
		$attachments = get_posts(
			array(
				'post_type'        => 'attachment',
				'post_status'      => 'inherit',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'meta_key'         => '_wp24h_md_source_url',
				'meta_value'       => $image_url,
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);

		return empty( $attachments ) ? 0 : (int) $attachments[0];
	}
}
