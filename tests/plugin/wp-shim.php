<?php
// Minimal WordPress stand-in to exercise the plugin outside WordPress (no network to install real WP here).
define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
$GLOBALS['__hooks'] = array(); $GLOBALS['__routes'] = array(); $GLOBALS['__transients'] = array();
$GLOBALS['__posts'] = array(); $GLOBALS['__meta'] = array(); $GLOBALS['__mails'] = array(); $GLOBALS['__options'] = array();
$GLOBALS['__mail_fail'] = getenv( 'MAIL_FAIL' ) === '1';
$tf = sys_get_temp_dir() . '/dentora_transients.json';
if ( is_file( $tf ) ) { $GLOBALS['__transients'] = json_decode( file_get_contents( $tf ), true ) ?: array(); }
register_shutdown_function( function () use ( $tf ) { file_put_contents( $tf, json_encode( $GLOBALS['__transients'] ) ); } );

function add_action( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['__hooks'][ $h ][] = $cb; }
function add_filter( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['__hooks'][ $h ][] = $cb; }
function remove_action( $h, $cb ) { $GLOBALS['__hooks'][ $h ] = array_values( array_filter( $GLOBALS['__hooks'][ $h ] ?? array(), fn( $x ) => $x !== $cb ) ); }
function do_action( $h, ...$args ) { foreach ( $GLOBALS['__hooks'][ $h ] ?? array() as $cb ) { $cb( ...$args ); } }
function apply_filters( $h, $v ) { return $v; }
function plugin_dir_path( $f ) { return dirname( $f ) . '/'; }
function plugins_url( $p, $f ) { return 'https://staging.example/wp-content/plugins/dentora-upiti/' . $p; }
function register_rest_route( $ns, $route, $args ) { $GLOBALS['__routes'][ '/' . $ns . $route ] = $args; }
function register_post_type() {}
function __return_true() { return true; }
function get_option( $k, $d = false ) { return $GLOBALS['__options'][ $k ] ?? $d; }
function wp_parse_args( $a, $d ) { return array_merge( $d, $a ); }
function sanitize_text_field( $s ) { $s = strip_tags( (string) $s ); $s = preg_replace( '/[\r\n\t ]+/', ' ', $s ); return trim( $s ); }
function sanitize_textarea_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_email( $s ) { return preg_replace( '/[^a-z0-9+_.@-]/i', '', (string) $s ); }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
function sanitize_file_name( $s ) { return preg_replace( '/[^A-Za-z0-9_.-]/', '', $s ); }
function is_email( $s ) { return (bool) filter_var( $s, FILTER_VALIDATE_EMAIL ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return esc_html( $s ); }
function esc_url_raw( $s ) { return filter_var( $s, FILTER_SANITIZE_URL ); }
function wp_unslash( $s ) { return $s; }
function get_transient( $k ) { $t = $GLOBALS['__transients'][ $k ] ?? null; return ( $t && $t[1] > time() ) ? $t[0] : false; }
function set_transient( $k, $v, $e ) { $GLOBALS['__transients'][ $k ] = array( $v, time() + $e ); return true; }
function is_wp_error( $x ) { return false; }
function wp_insert_post( $a ) { $id = count( $GLOBALS['__posts'] ) + 100; $GLOBALS['__posts'][ $id ] = $a; return $id; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['__meta'][ $id ][ $k ] = $v; }
function wp_mail( $to, $subject, $body, $headers ) {
	$m = new stdClass(); $m->AltBody = '';
	do_action( 'phpmailer_init', $m );
	$GLOBALS['__mails'][] = compact( 'to', 'subject', 'body', 'headers' ) + array( 'alt' => $m->AltBody );
	return ! $GLOBALS['__mail_fail'];
}
class WP_REST_Request {
	private $json, $body;
	function __construct( $json = null, $body = array() ) { $this->json = $json; $this->body = $body; }
	function get_json_params() { return $this->json; }
	function get_body_params() { return $this->body; }
	function get_route() { return '/dentora/v1/upit'; }
}
class WP_REST_Response {
	public $data, $status;
	function __construct( $d, $s = 200 ) { $this->data = $d; $this->status = $s; }
}
require __DIR__ . '/../../plugin/dentora-upiti/dentora-upiti.php';
do_action( 'init' ); do_action( 'rest_api_init' );
