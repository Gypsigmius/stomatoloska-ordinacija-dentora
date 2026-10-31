<?php
@unlink( sys_get_temp_dir() . '/dentora_transients.json' );
require __DIR__ . '/wp-shim.php';
$_SERVER['REMOTE_ADDR'] = '1.2.3.4';
$cb    = $GLOBALS['__routes']['/dentora/v1/upit']['callback'];
$pass  = 0; $fail = 0;
function check( $name, $cond ) { global $pass, $fail; if ( $cond ) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; } }
$good = array( 'ime_prezime' => 'Ana <b>Horvat</b>', 'telefon' => '091 123 4567', 'email' => 'ana@example.com', 'tretman' => 'aligneri',
	'poruka' => "Prvi red\nDrugi <script>alert(1)</script> red", 'website' => '', 'ts' => ( time() - 30 ) * 1000, 'izvor' => 'modal · #aligneri' );

check( 'route registered as POST', ( $GLOBALS['__routes']['/dentora/v1/upit']['methods'] ?? '' ) === 'POST' );

// valid submission
$r = $cb( new WP_REST_Request( $good ) );
check( 'valid -> 200 ok', $r->status === 200 && $r->data['ok'] === true );
check( 'two emails sent', count( $GLOBALS['__mails'] ) === 2 );
$clinic = $GLOBALS['__mails'][0]; $patient = $GLOBALS['__mails'][1];
check( 'clinic email to info@', $clinic['to'] === 'info@dentora-clinic.hr' );
check( 'clinic subject has treatment + name', $clinic['subject'] === 'Novi upit: Prozirni aligneri – Ana Horvat' );
check( 'clinic Reply-To is patient', in_array( 'Reply-To: "Ana Horvat" <ana@example.com>', $clinic['headers'], true ) );
check( 'patient email to patient', $patient['to'] === 'ana@example.com' );
check( 'patient template A filled, no tags left', strpos( $patient['body'], '{{' ) === false && strpos( $patient['body'], 'Hvala vam, Ana Horvat.' ) !== false );
check( 'treatment label in patient email', strpos( $patient['body'], 'Prozirni aligneri' ) !== false );
check( 'message escaped + line breaks', strpos( $patient['body'], '&lt;script&gt;' ) === false && strpos( $patient['body'], '<script>' ) === false && strpos( $patient['body'], 'Prvi red<br />' ) !== false );
check( 'plain-text part attached', strpos( $patient['alt'], 'Hvala vam, Ana Horvat!' ) !== false && strpos( $patient['alt'], '{{' ) === false );
check( 'html content type', in_array( 'Content-Type: text/html; charset=UTF-8', $patient['headers'], true ) );
check( 'enquiry saved with meta + mail status', ( $GLOBALS['__meta'][100]['_telefon'] ?? '' ) === '091 123 4567' && ( $GLOBALS['__meta'][100]['_mail_pacijent'] ?? '' ) === 'poslano' );
check( 'phpmailer_init hook removed after send', empty( $GLOBALS['__hooks']['phpmailer_init'] ) );

// validation
$GLOBALS['__mails'] = array();
$bad = $good; $bad['email'] = 'nije-email'; $bad['telefon'] = '12'; $bad['ime_prezime'] = 'A';
$r = $cb( new WP_REST_Request( $bad ) );
check( 'invalid -> 422 with 3 field errors', $r->status === 422 && count( $r->data['errors'] ) === 3 );
check( 'invalid -> no email sent', count( $GLOBALS['__mails'] ) === 0 );

// unknown treatment falls back
$x = $good; $x['tretman'] = 'hakiranje'; $r = $cb( new WP_REST_Request( $x ) );
check( 'unknown treatment -> Ostalo', strpos( $GLOBALS['__mails'][0]['subject'], 'Ostalo / nisam siguran' ) !== false );

// honeypot + timing: silent fake success, nothing sent
$GLOBALS['__mails'] = array();
$h = $good; $h['website'] = 'http://spam.example'; $r = $cb( new WP_REST_Request( $h ) );
check( 'honeypot -> fake ok, no mail', $r->data['ok'] === true && count( $GLOBALS['__mails'] ) === 0 );
$f = $good; $f['ts'] = time() * 1000; $r = $cb( new WP_REST_Request( $f ) );
check( 'too fast -> fake ok, no mail', $r->data['ok'] === true && count( $GLOBALS['__mails'] ) === 0 );

$GLOBALS['__options']['dentora_upiti_settings'] = array( 'template' => 'B-pismo-doktorice' ); $_SERVER['REMOTE_ADDR'] = '4.4.4.4'; $cb( new WP_REST_Request( $good ) );
check( 'template B portrait served from plugin', strpos( end( $GLOBALS['__mails'] )['body'], 'https://staging.example/wp-content/plugins/dentora-upiti/assets/dentora-email-dr-bozana-portret-240.jpg' ) !== false );
// template switch
$GLOBALS['__options']['dentora_upiti_settings'] = array( 'template' => 'C-editorial' );
$_SERVER['REMOTE_ADDR'] = '5.6.7.8';
$r = $cb( new WP_REST_Request( $good ) );
check( 'template C used when selected', strpos( end( $GLOBALS['__mails'] )['body'], 'Kako se pripremiti' ) !== false );

// patient email off
$GLOBALS['__mails'] = array(); $GLOBALS['__options']['dentora_upiti_settings'] = array( 'send_patient' => 0 );
$r = $cb( new WP_REST_Request( $good ) );
check( 'patient email off -> only clinic', count( $GLOBALS['__mails'] ) === 1 );

// rate limit: 5 per IP
$GLOBALS['__options'] = array(); $_SERVER['REMOTE_ADDR'] = '9.9.9.9'; $codes = array();
for ( $i = 0; $i < 6; $i++ ) { $codes[] = $cb( new WP_REST_Request( $good ) )->status; }
check( 'rate limit: 6th request -> 429', $codes === array( 200, 200, 200, 200, 200, 429 ) );

// form-encoded body also works
$_SERVER['REMOTE_ADDR'] = '8.8.8.8';
$r = $cb( new WP_REST_Request( null, $good ) );
check( 'form-encoded body accepted', $r->status === 200 );

// mail failure -> 500 with phone fallback, still saved
$GLOBALS['__mail_fail'] = true; $_SERVER['REMOTE_ADDR'] = '7.7.7.7'; $before = count( $GLOBALS['__posts'] );
$r = $cb( new WP_REST_Request( $good ) );
check( 'mail failure -> 500 + phone in message, enquiry saved', $r->status === 500 && strpos( $r->data['message'], '091 948 7770' ) !== false && count( $GLOBALS['__posts'] ) === $before + 1 );

// settings sanitizer
$s = Dentora_Upiti::sanitize_settings( array( 'clinic_email' => 'x', 'template' => '../../etc', 'send_patient' => '1', 'subject_clinic' => '<b>Hi</b>' ) );
check( 'settings sanitized', $s['clinic_email'] === 'info@dentora-clinic.hr' && $s['template'] === 'A-minimal' && $s['subject_clinic'] === 'Hi' );

echo "\n$pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
