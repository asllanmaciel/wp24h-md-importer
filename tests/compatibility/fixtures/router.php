<?php

declare(strict_types=1);

$path = basename( (string) parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ) );
$fixtures = array(
    'featured.png'  => 'image/png',
    'featured.jpg'  => 'image/jpeg',
    'featured.webp' => 'image/webp',
);

if ( isset( $fixtures[ $path ] ) && is_file( __DIR__ . '/' . $path ) ) {
    header( 'Content-Type: ' . $fixtures[ $path ] );
    readfile( __DIR__ . '/' . $path );
    return;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Not found';
