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
		$markdown   = str_replace( array( "\r\n", "\r" ), "\n", (string) $markdown );
		$lines      = explode( "\n", $markdown );
		$html       = array();
		$paragraph  = array();
		$list_type  = null;
		$in_code    = false;
		$code       = array();
		$code_lang  = '';
		$line_count = count( $lines );

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

		for ( $line_index = 0; $line_index < $line_count; $line_index++ ) {
			$line = $lines[ $line_index ];

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
			if ( $line_index + 1 < $line_count && self::is_table_row( $line ) && self::is_table_separator( $lines[ $line_index + 1 ] ) && count( self::split_table_row( $line ) ) === count( self::split_table_row( $lines[ $line_index + 1 ] ) ) ) {
				$flush_paragraph();
				$close_list();

				$html[] = '<table>';
				$html[] = '<thead>';
				$html[] = '<tr>';
				foreach ( self::split_table_row( $line ) as $cell ) {
					$html[] = '<th>' . self::inline( $cell ) . '</th>';
				}
				$html[] = '</tr>';
				$html[] = '</thead>';
				$html[] = '<tbody>';

				$line_index += 2;
				while ( $line_index < $line_count && self::is_table_row( $lines[ $line_index ] ) ) {
					$html[] = '<tr>';
					foreach ( self::split_table_row( $lines[ $line_index ] ) as $cell ) {
						$html[] = '<td>' . self::inline( $cell ) . '</td>';
					}
					$html[] = '</tr>';
					$line_index++;
				}

				$html[] = '</tbody>';
				$html[] = '</table>';
				$line_index--;
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

	private static function is_table_row( $line ) {
		$line = (string) $line;
		if ( false === strpos( $line, '|' ) ) {
			return false;
		}
		if ( preg_match( '/^\s*(?:#{1,6}\s+|>\s?|[-*+]\s+|\d+[.)]\s+|```)/', $line ) ) {
			return false;
		}
		return count( self::split_table_row( $line ) ) >= 2;
	}

	private static function is_table_separator( $line ) {
		$cells = self::split_table_row( $line );
		if ( count( $cells ) < 2 ) {
			return false;
		}

		foreach ( $cells as $cell ) {
			if ( ! preg_match( '/^-{3,}$/', trim( $cell ) ) ) {
				return false;
			}
		}
		return true;
	}

	private static function split_table_row( $line ) {
		$row = trim( (string) $line );
		if ( '' !== $row && '|' === $row[0] ) {
			$row = substr( $row, 1 );
		}
		if ( '' !== $row && '|' === substr( $row, -1 ) ) {
			$row = substr( $row, 0, -1 );
		}
		return array_map( 'trim', explode( '|', $row ) );
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
