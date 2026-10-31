<?php
$uri = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
if ( $uri === '/wp-json/dentora/v1/upit' ) {
	require __DIR__ . '/wp-shim.php';
	$json = json_decode( file_get_contents( 'php://input' ), true );
	$res  = $GLOBALS['__routes']['/dentora/v1/upit']['callback']( new WP_REST_Request( $json ) );
	file_put_contents( __DIR__ . '/last_mails.json', json_encode( $GLOBALS['__mails'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
	http_response_code( $res->status ); header( 'Content-Type: application/json' );
	echo json_encode( $res->data ); return true;
}
$file = __DIR__ . '/../../site/dist/dentora-demo' . $uri;
if ( $uri === '/' ) { $file .= 'index.html'; }
if ( is_file( $file ) ) {
	$types = array( 'html' => 'text/html; charset=utf-8', 'avif' => 'image/avif', 'webp' => 'image/webp', 'png' => 'image/png', 'jpg' => 'image/jpeg' );
	header( 'Content-Type: ' . ( $types[ pathinfo( $file, PATHINFO_EXTENSION ) ] ?? 'application/octet-stream' ) );
	readfile( $file ); return true;
}
http_response_code( 404 ); return true;
