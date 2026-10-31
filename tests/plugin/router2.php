<?php
$uri = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
if ( $uri === '/' ) {
	require __DIR__ . '/wp-shim.php';
	echo Dentora_Upiti::rewrite_demo_html( file_get_contents( __DIR__ . '/../../site/dist/dentora-demo/index.html' ), '/dentora-demo/' );
	return true;
}
if ( strpos( $uri, '/dentora-demo/' ) === 0 ) {
	$f = __DIR__ . '/../../site/dist' . $uri; if ( is_file( $f ) ) { $t = array( 'avif' => 'image/avif', 'webp' => 'image/webp', 'png' => 'image/png' ); header( 'Content-Type: ' . ( $t[ pathinfo( $f, PATHINFO_EXTENSION ) ] ?? 'text/html' ) ); readfile( $f ); return true; }
}
http_response_code( 404 ); return true;
