<?php
/**
 * Restricted YAML front matter parser.
 *
 * @package WP24H_MD_Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WP24H_MD_Front_Matter {
	public static function parse( $raw ) {
		$raw  = (string) preg_replace( '/^\xEF\xBB\xBF/', '', (string) $raw );
		$meta = array();
		$body = $raw;

		if ( ! preg_match( '/\A---\s*\R(.*?)\R---\s*(?:\R|\z)/s', $raw, $match ) ) {
			return array( 'meta' => $meta, 'body' => trim( $body ) );
		}

		$yaml        = $match[1];
		$body        = substr( $raw, strlen( $match[0] ) );
		$current_key = null;

		foreach ( preg_split( '/\R/', $yaml ) as $line ) {
			if ( '' === trim( $line ) || preg_match( '/^\s*#/', $line ) ) {
				continue;
			}
			if ( preg_match( '/^([A-Za-z0-9_-]+):\s*(.*)$/', $line, $matches ) ) {
				$current_key = sanitize_key( $matches[1] );
				$value       = trim( $matches[2] );
				$meta[ $current_key ] = '' === $value ? array() : self::scalar( $value );
				continue;
			}
			if ( $current_key && preg_match( '/^\s*-\s*(.+)$/', $line, $matches ) ) {
				if ( ! is_array( $meta[ $current_key ] ) ) {
					$meta[ $current_key ] = array( $meta[ $current_key ] );
				}
				$meta[ $current_key ][] = self::scalar( trim( $matches[1] ) );
			}
		}

		return array( 'meta' => $meta, 'body' => trim( $body ) );
	}

	private static function scalar( $value ) {
		$length = strlen( $value );
		if ( $length >= 2 ) {
			$first = $value[0];
			$last  = $value[ $length - 1 ];
			if ( ( '"' === $first && '"' === $last ) || ( "'" === $first && "'" === $last ) ) {
				$value = substr( $value, 1, -1 );
				if ( '"' === $first ) {
					$value = stripcslashes( $value );
				}
			}
		}
		$lower = strtolower( $value );
		if ( 'true' === $lower ) {
			return true;
		}
		if ( 'false' === $lower ) {
			return false;
		}
		if ( 'null' === $lower || '~' === $lower ) {
			return null;
		}
		return $value;
	}
}
