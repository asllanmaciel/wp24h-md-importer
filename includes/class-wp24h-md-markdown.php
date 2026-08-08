<?php
/**
 * Small dependency-free Markdown renderer.
 *
 * @package WP24H_MD_Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WP24H_MD_Markdown {
	public static function to_html( $markdown ) {
		$markdown  = str_replace( array( "\r\n", "\r" ), "\n", (string) $markdown );
		$lines     = explode( "\n", $markdown );
		$html      = array();
		$paragraph = array();
		$list_type = null;
		$in_code   = false;
		$code      = array();
		$code_lang = '';

		$flush_paragraph = static function () use ( &$paragraph, &$html ) {
			if ( ! empty( $paragraph ) ) {
				$text = trim( implode( ' ', array_map( 'trim', $paragraph ) ) );
				if ( '' !== $text ) {
					$html[] = '<p>' . WP24H_MD_Markdown::inline( $text ) . '</p>';
				}
				$paragraph = array();
			}
		};

		$close_list = static function () use ( &$list_type, &$html ) {
			if ( $list_type ) {
				$html[]    = '</' . $list_type . '>';
				$list_type = null;
			}
		};

		foreach ( $lines as $line ) {
			if ( $in_code ) {
				if ( preg_match( '/^```\s*$/', $line ) ) {
					$class     = '' !== $code_lang ? ' class="language-' . esc_attr( $code_lang ) . '"' : '';
					$html[]    = '<pre><code' . $class . '>' . esc_html( implode( "\n", $code ) ) . '</code></pre>';
					$in_code   = false;
					$code      = array();
					$code_lang = '';
				} else {
					$code[] = $line;
				}
				continue;
			}

			if ( preg_match( '/^```\s*([A-Za-z0-9_+.-]*)\s*$/', $line, $matches ) ) {
				$flush_paragraph();
				$close_list();
				$in_code   = true;
				$code_lang = isset( $matches[1] ) ? sanitize_html_class( $matches[1] ) : '';
				continue;
			}
			if ( '' === trim( $line ) ) {
				$flush_paragraph();
				$close_list();
				continue;
			}
			if ( preg_match( '/^(#{1,6})\s+(.+)$/', $line, $matches ) ) {
				$flush_paragraph();
				$close_list();
				$level  = strlen( $matches[1] );
				$html[] = '<h' . $level . '>' . self::inline( trim( $matches[2] ) ) . '</h' . $level . '>';
				continue;
			}
			if ( preg_match( '/^>\s?(.*)$/', $line, $matches ) ) {
				$flush_paragraph();
				$close_list();
				$html[] = '<blockquote><p>' . self::inline( trim( $matches[1] ) ) . '</p></blockquote>';
				continue;
			}
			if ( preg_match( '/^\s*[-*+]\s+(.+)$/', $line, $matches ) ) {
				$flush_paragraph();
				if ( 'ul' !== $list_type ) {
					$close_list();
					$list_type = 'ul';
					$html[]    = '<ul>';
				}
				$html[] = '<li>' . self::inline( trim( $matches[1] ) ) . '</li>';
				continue;
			}
			if ( preg_match( '/^\s*\d+[.)]\s+(.+)$/', $line, $matches ) ) {
				$flush_paragraph();
				if ( 'ol' !== $list_type ) {
					$close_list();
					$list_type = 'ol';
					$html[]    = '<ol>';
				}
				$html[] = '<li>' . self::inline( trim( $matches[1] ) ) . '</li>';
				continue;
			}
			if ( preg_match( '/^\s*(?:---+|___+|\*\*\*+)\s*$/', $line ) ) {
				$flush_paragraph();
				$close_list();
				$html[] = '<hr>';
				continue;
			}
			$paragraph[] = $line;
		}

		if ( $in_code ) {
			$class  = '' !== $code_lang ? ' class="language-' . esc_attr( $code_lang ) . '"' : '';
			$html[] = '<pre><code' . $class . '>' . esc_html( implode( "\n", $code ) ) . '</code></pre>';
		}
		$flush_paragraph();
		$close_list();
		return implode( "\n", $html );
	}

	public static function inline( $text ) {
		$text = esc_html( (string) $text );
		$text = preg_replace_callback( '/!\[([^\]]*)\]\((https?:\/\/[^\s)]+)\)/', static function ( $matches ) {
			return '<img src="' . esc_url( $matches[2] ) . '" alt="' . esc_attr( html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' ) ) . '">';
		}, $text );
		$text = preg_replace_callback( '/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', static function ( $matches ) {
			return '<a href="' . esc_url( $matches[2] ) . '" rel="noopener noreferrer">' . $matches[1] . '</a>';
		}, $text );
		$text = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $text );
		$text = preg_replace( '/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text );
		$text = preg_replace( '/__([^_]+)__/', '<strong>$1</strong>', $text );
		$text = preg_replace( '/~~([^~]+)~~/', '<del>$1</del>', $text );
		$text = preg_replace( '/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $text );
		return (string) $text;
	}
}
