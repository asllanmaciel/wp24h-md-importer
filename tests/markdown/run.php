<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( (string) $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'sanitize_html_class' ) ) {
	function sanitize_html_class( $class ) {
		return (string) preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $class );
	}
}

require dirname( __DIR__, 2 ) . '/includes/class-wp24h-md-markdown.php';

$checks = array();
$assert = static function ( $condition, $name ) use ( &$checks ) {
	$checks[] = $name;
	if ( ! $condition ) {
		throw new RuntimeException( 'Check failed: ' . $name );
	}
};

$table = WP24H_MD_Markdown::to_html(
	"| Name | Status |\n|---|---|\n| Ana | **Active** |\n| <script>alert(1)</script> | Inactive |"
);
$assert( false !== strpos( $table, '<table>' ), 'valid table renders semantic table markup' );
$assert( false !== strpos( $table, '<th>Name</th>' ), 'header cells render as th elements' );
$assert( false !== strpos( $table, '<td><strong>Active</strong></td>' ), 'inline Markdown renders inside table cells' );
$assert( false === strpos( $table, '<script>' ), 'raw HTML is not emitted from table cells' );
$assert( false !== strpos( $table, '&lt;script&gt;' ), 'raw HTML in table cells remains escaped' );

$pipe_prose = WP24H_MD_Markdown::to_html( 'Use A | B in prose.' );
$assert( '<p>Use A | B in prose.</p>' === $pipe_prose, 'ordinary pipe prose remains a paragraph' );

$fenced = WP24H_MD_Markdown::to_html(
	"```text\n| Name | Status |\n|---|---|\n```"
);
$assert( false === strpos( $fenced, '<table>' ), 'table-like fenced code is not parsed as a table' );
$assert( false !== strpos( $fenced, '<pre><code class="language-text">| Name | Status |' ), 'fenced code preserves pipe content' );

$list = WP24H_MD_Markdown::to_html( "- alpha | beta\n- gamma" );
$assert( false !== strpos( $list, '<li>alpha | beta</li>' ), 'pipe characters remain valid inside list items' );

$invalid_separator = WP24H_MD_Markdown::to_html(
	"| Name | Status |\n|--|---|\n| Ana | Active |"
);
$assert( false === strpos( $invalid_separator, '<table>' ), 'separator cells require at least three hyphens' );

echo 'Markdown checks passed: ' . count( $checks ) . PHP_EOL;
