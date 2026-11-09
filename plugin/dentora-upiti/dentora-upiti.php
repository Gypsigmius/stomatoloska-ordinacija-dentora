<?php
/**
 * Plugin Name:       Dentora upiti
 * Description:       Prima upite s Dentora obrazaca (popup, kviz, stranice tretmana), šalje ih klinici i pacijentu šalje e-mail potvrdu. Slanje ide preko wp_mail(), pa koristi postojeći SMTP plugin.
 * Version:           1.1.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Dentora Clinic
 * Text Domain:       dentora-upiti
 * License:           GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DENTORA_UPITI_VERSION', '1.1.1' );
define( 'DENTORA_UPITI_DIR', plugin_dir_path( __FILE__ ) );

final class Dentora_Upiti {

	const OPTION     = 'dentora_upiti_settings';
	const POST_TYPE  = 'dentora_upit';
	const REST_NS    = 'dentora/v1';
	const RATE_LIMIT = 5;               // max submissions per IP …
	const RATE_WIN   = 10 * MINUTE_IN_SECONDS; // … in this window
	const MIN_FILL   = 3;               // seconds: faster than this = bot

	/** Treatment keys sent by the site => label shown in emails. */
	public static function treatments() {
		return apply_filters(
			'dentora_upiti_treatments',
			array(
				'aligneri'         => 'Prozirni aligneri',
				'ljuskice'         => 'Keramičke/kompozitne ljuskice',
				'izbjeljivanje'    => 'Izbjeljivanje zubi',
				'restaurativna'    => 'Restaurativna stomatologija i endodoncija',
				'oralna-kirurgija' => 'Oralna kirurgija i implantologija',
				'protetika'        => 'Protetika',
				'parodontologija'  => 'Parodontologija',
				'djecja'           => 'Dječja i preventivna stomatologija',
				'ortodoncija'      => 'Ortodoncija',
				'ostalo'           => 'Ostalo / nisam siguran',
			)
		);
	}

	public static function templates() {
		return array(
			'A-minimal'         => 'A · Minimal',
			'B-pismo-doktorice' => 'B · Pismo dr. Božane',
			'C-editorial'       => 'C · Editorial (savjeti prije posjeta)',
			'original'          => 'Prva verzija (s fotografijom)',
		);
	}

	public static function defaults() {
		return array(
			'clinic_email'    => 'info@dentora-clinic.hr',
			'template'        => 'A-minimal',
			'send_patient'    => 1,
			'subject_clinic'  => 'Novi upit: {{tretman}} – {{ime_prezime}}',
			'subject_patient' => 'Zaprimili smo vaš upit – Dentora Clinic',
			'allowed_origins' => '',
			'home_demo'       => 1,
			'demo_folder'     => 'dentora-demo',
		);
	}

	public static function settings() {
		return wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'admin_column_value' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_filter( 'rest_pre_serve_request', array( __CLASS__, 'cors' ), 10, 4 );
		add_action( 'template_redirect', array( __CLASS__, 'serve_home_demo' ), 0 );
	}

	/* ------------------------------------------------------------------ storage */

	/** Every enquiry is also saved in WP admin, so nothing is lost if an email fails. */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => 'Upiti',
					'singular_name' => 'Upit',
					'menu_name'     => 'Upiti',
					'all_items'     => 'Svi upiti',
					'edit_item'     => 'Upit',
					'search_items'  => 'Pretraži upite',
					'not_found'     => 'Još nema upita.',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-email-alt',
				'menu_position'   => 26,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
				'show_in_rest'    => false,
			)
		);
	}

	/* --------------------------------------------------------------------- REST */

	public static function register_routes() {
		register_rest_route(
			self::REST_NS,
			'/upit',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle' ),
				'permission_callback' => '__return_true', // public form; protected by honeypot, timing and rate limit
			)
		);
	}

	private static function error( $message, $status = 400 ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => $message ), $status );
	}

	public static function handle( WP_REST_Request $request ) {
		$p = $request->get_json_params();
		if ( empty( $p ) ) {
			$p = $request->get_body_params();
		}
		$p = is_array( $p ) ? $p : array();

		// 1. Honeypot: real people never fill this hidden field. Pretend success to bots.
		if ( ! empty( $p['website'] ) ) {
			return new WP_REST_Response( array( 'ok' => true, 'message' => 'Hvala!' ), 200 );
		}

		// 2. Timing: the form was opened at `ts` (ms). Submitted too fast = bot.
		$ts = isset( $p['ts'] ) ? (int) ( $p['ts'] / 1000 ) : 0;
		if ( $ts && ( time() - $ts ) < self::MIN_FILL ) {
			return new WP_REST_Response( array( 'ok' => true, 'message' => 'Hvala!' ), 200 );
		}

		// 3. Rate limit per IP.
		$ip_key = 'dentora_rl_' . md5( self::client_ip() );
		$count  = (int) get_transient( $ip_key );
		if ( $count >= self::RATE_LIMIT ) {
			return self::error( 'Poslali ste previše upita u kratkom vremenu. Molimo nazovite nas na 091 948 7770.', 429 );
		}

		// 4. Validate.
		$data = array(
			'ime_prezime' => sanitize_text_field( $p['ime_prezime'] ?? '' ),
			'telefon'     => sanitize_text_field( $p['telefon'] ?? '' ),
			'email'       => sanitize_email( $p['email'] ?? '' ),
			'tretman_key' => sanitize_key( $p['tretman'] ?? 'ostalo' ),
			'poruka'      => sanitize_textarea_field( $p['poruka'] ?? '' ),
			'izvor'       => sanitize_text_field( $p['izvor'] ?? '' ),
		);
		$treatments = self::treatments();
		if ( ! isset( $treatments[ $data['tretman_key'] ] ) ) {
			$data['tretman_key'] = 'ostalo';
		}
		$data['tretman'] = $treatments[ $data['tretman_key'] ];

		$errors = array();
		$name_len = function_exists( 'mb_strlen' ) ? mb_strlen( $data['ime_prezime'] ) : strlen( $data['ime_prezime'] );
		if ( $name_len < 2 || $name_len > 100 ) {
			$errors['ime_prezime'] = 'Upišite ime i prezime.';
		}
		$digits = preg_replace( '/\D+/', '', $data['telefon'] );
		if ( strlen( $digits ) < 6 || strlen( $digits ) > 15 || ! preg_match( '/^[0-9+\s\/().-]+$/', $data['telefon'] ) ) {
			$errors['telefon'] = 'Upišite ispravan broj telefona.';
		}
		if ( ! is_email( $data['email'] ) ) {
			$errors['email'] = 'Upišite ispravnu e-mail adresu.';
		}
		$msg_len = function_exists( 'mb_strlen' ) ? mb_strlen( $data['poruka'] ) : strlen( $data['poruka'] );
		if ( $msg_len > 2000 ) {
			$errors['poruka'] = 'Poruka je preduga (najviše 2000 znakova).';
		}
		if ( $errors ) {
			return new WP_REST_Response( array( 'ok' => false, 'message' => 'Provjerite označena polja.', 'errors' => $errors ), 422 );
		}

		set_transient( $ip_key, $count + 1, self::RATE_WIN );

		// 5. Save in WP admin first.
		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => $data['ime_prezime'] . ' – ' . $data['tretman'],
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			foreach ( array( 'ime_prezime', 'telefon', 'email', 'tretman', 'poruka', 'izvor' ) as $k ) {
				update_post_meta( $post_id, '_' . $k, $data[ $k ] );
			}
		}

		// 6. Send emails through wp_mail() (the SMTP plugin takes over from there).
		$s          = self::settings();
		$clinic_ok  = self::send_clinic( $data, $s );
		$patient_ok = $s['send_patient'] ? self::send_patient( $data, $s ) : true;

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_mail_klinika', $clinic_ok ? 'poslano' : 'GREŠKA' );
			update_post_meta( $post_id, '_mail_pacijent', $s['send_patient'] ? ( $patient_ok ? 'poslano' : 'GREŠKA' ) : 'isključeno' );
		}

		do_action( 'dentora_upiti_received', $data, $post_id, $clinic_ok, $patient_ok );

		if ( ! $clinic_ok ) {
			// Saved in admin, but the clinic was not notified by email: tell the visitor to call.
			return self::error( 'Upit je spremljen, ali slanje e-maila trenutno ne radi. Molimo nazovite nas na 091 948 7770.', 500 );
		}

		return new WP_REST_Response(
			array(
				'ok'      => true,
				'message' => 'Hvala, ' . $data['ime_prezime'] . '! Zaprimili smo vaš upit i javit ćemo vam se uskoro.' . ( $s['send_patient'] && $patient_ok ? ' Potvrdu smo poslali na ' . $data['email'] . '.' : '' ),
			),
			200
		);
	}

	/* -------------------------------------------------------------------- mails */

	private static function fill( $text, array $data, $html = true ) {
		$map = array();
		foreach ( array( 'ime_prezime', 'telefon', 'email', 'tretman', 'poruka' ) as $k ) {
			$v = (string) $data[ $k ];
			if ( $html ) {
				$v = esc_html( $v );
				if ( 'poruka' === $k ) {
					$v = '' === $v ? '—' : nl2br( $v );
				}
			}
			$map[ '{{' . $k . '}}' ] = $v;
		}
		return strtr( $text, $map );
	}

	private static function send_clinic( array $data, array $s ) {
		$rows = '';
		foreach ( array(
			'Ime i prezime' => $data['ime_prezime'],
			'Telefon'       => $data['telefon'],
			'E-mail'        => $data['email'],
			'Tretman'       => $data['tretman'],
			'Poruka'        => $data['poruka'],
			'Obrazac'       => $data['izvor'],
		) as $label => $value ) {
			$v     = 'Poruka' === $label ? nl2br( esc_html( $value ) ) : esc_html( $value );
			$rows .= '<tr><td style="padding:8px 16px 8px 0;color:#6f655b;font:14px Arial,sans-serif;vertical-align:top;white-space:nowrap;">' . esc_html( $label ) . '</td><td style="padding:8px 0;color:#2b2520;font:15px Arial,sans-serif;">' . $v . '</td></tr>';
		}
		$tel  = preg_replace( '/[^0-9+]/', '', $data['telefon'] );
		$body = '<div style="font:15px Arial,sans-serif;color:#2b2520;">'
			. '<p style="font:22px Georgia,serif;margin:0 0 16px;">Novi upit s web stranice</p>'
			. '<table cellpadding="0" cellspacing="0" border="0">' . $rows . '</table>'
			. '<p style="margin:20px 0 0;"><a href="tel:' . esc_attr( $tel ) . '" style="color:#7f5e33;font-weight:bold;">Nazovi ' . esc_html( $data['telefon'] ) . '</a> · Odgovori na ovaj e-mail za odgovor pacijentu.</p>'
			. '</div>';
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'Reply-To: ' . self::header_name( $data['ime_prezime'] ) . ' <' . $data['email'] . '>',
		);
		$subject = self::fill( $s['subject_clinic'], $data, false );
		return (bool) wp_mail( $s['clinic_email'], $subject, $body, $headers );
	}

	private static function send_patient( array $data, array $s ) {
		$file = DENTORA_UPITI_DIR . 'templates/' . sanitize_file_name( $s['template'] ) . '.html';
		if ( ! file_exists( $file ) ) {
			$file = DENTORA_UPITI_DIR . 'templates/A-minimal.html';
		}
		$html = self::fill( (string) file_get_contents( $file ), $data, true );
		// Email photos ship with the plugin, so nothing has to be uploaded by hand.
		$html = str_replace( 'https://dentora-clinic.hr/wp-content/uploads/email/', plugins_url( 'assets/', __FILE__ ), $html );
		$text = self::fill( (string) file_get_contents( DENTORA_UPITI_DIR . 'templates/tekst.txt' ), $data, false );

		// Add the plain-text part to the same email (better inbox placement).
		$alt = static function ( $phpmailer ) use ( $text ) {
			$phpmailer->AltBody = $text;
		};
		add_action( 'phpmailer_init', $alt );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'Reply-To: Dentora Clinic <' . $s['clinic_email'] . '>',
		);
		$ok = (bool) wp_mail( $data['email'], self::fill( $s['subject_patient'], $data, false ), $html, $headers );
		remove_action( 'phpmailer_init', $alt );
		return $ok;
	}

	private static function header_name( $name ) {
		return '"' . str_replace( array( '"', "\r", "\n" ), '', $name ) . '"';
	}

	private static function client_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}

	/** Optional: allow the form on another domain (e.g. a static demo) to post here. */
	public static function cors( $served, $result, $request, $server ) {
		if ( 0 !== strpos( $request->get_route(), '/' . self::REST_NS . '/' ) ) {
			return $served;
		}
		$origin  = get_http_origin();
		$allowed = array_filter( array_map( 'trim', explode( "\n", (string) self::settings()['allowed_origins'] ) ) );
		if ( $origin && in_array( untrailingslashit( $origin ), array_map( 'untrailingslashit', $allowed ), true ) ) {
			header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
			header( 'Access-Control-Allow-Methods: POST, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Content-Type' );
			header( 'Vary: Origin' );
		}
		return $served;
	}

	/* -------------------------------------------------------- homepage demo */

	public static function demo_file() {
		return trailingslashit( ABSPATH . self::settings()['demo_folder'] ) . 'index.html';
	}

	/** Point the page's relative img/ paths at the demo folder, so it can be served at "/". */
	public static function rewrite_demo_html( $html, $base ) {
		return preg_replace( '#(["\'(])img/#', '$1' . $base . 'img/', $html );
	}

	/** Serve the static Dentora page on the WordPress front page (until the real theme is ready). */
	public static function serve_home_demo() {
		if ( is_admin() || ! is_front_page() || is_paged() || is_feed() || is_preview() ) {
			return;
		}
		$s = self::settings();
		if ( empty( $s['home_demo'] ) ) {
			return;
		}
		$file = self::demo_file();
		if ( ! is_readable( $file ) ) {
			return;
		}
		$base = trailingslashit( (string) wp_parse_url( home_url( '/' . $s['demo_folder'] . '/' ), PHP_URL_PATH ) );
		$html = self::rewrite_demo_html( (string) file_get_contents( $file ), $base );
		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- static page shipped by the site owner
		exit;
	}

	/* -------------------------------------------------------------------- admin */

	public static function admin_menu() {
		add_submenu_page( 'edit.php?post_type=' . self::POST_TYPE, 'Postavke upita', 'Postavke', 'manage_options', 'dentora-upiti', array( __CLASS__, 'settings_page' ) );
	}

	public static function register_settings() {
		register_setting(
			'dentora_upiti',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::defaults(),
			)
		);
	}

	public static function sanitize_settings( $in ) {
		$d   = self::defaults();
		$in  = is_array( $in ) ? $in : array();
		$out = array(
			'clinic_email'    => is_email( $in['clinic_email'] ?? '' ) ? sanitize_email( $in['clinic_email'] ) : $d['clinic_email'],
			'template'        => isset( self::templates()[ $in['template'] ?? '' ] ) ? $in['template'] : $d['template'],
			'send_patient'    => empty( $in['send_patient'] ) ? 0 : 1,
			'subject_clinic'  => sanitize_text_field( $in['subject_clinic'] ?? $d['subject_clinic'] ),
			'subject_patient' => sanitize_text_field( $in['subject_patient'] ?? $d['subject_patient'] ),
			'home_demo'       => empty( $in['home_demo'] ) ? 0 : 1,
			'demo_folder'     => trim( sanitize_file_name( $in['demo_folder'] ?? $d['demo_folder'] ) ) ?: $d['demo_folder'],
			'allowed_origins' => implode( "\n", array_filter( array_map( 'esc_url_raw', array_map( 'trim', explode( "\n", (string) ( $in['allowed_origins'] ?? '' ) ) ) ) ) ),
		);
		return $out;
	}

	public static function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s   = self::settings();
		$api = rest_url( self::REST_NS . '/upit' );
		?>
		<div class="wrap">
			<h1>Dentora upiti · Postavke</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'dentora_upiti' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="du-clinic">E-mail klinike</label></th>
						<td><input type="email" id="du-clinic" class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[clinic_email]" value="<?php echo esc_attr( $s['clinic_email'] ); ?>" />
						<p class="description">Na ovu adresu stižu svi upiti.</p></td>
					</tr>
					<tr>
						<th scope="row">Potvrda pacijentu</th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[send_patient]" value="1" <?php checked( $s['send_patient'], 1 ); ?> /> Pošalji pacijentu e-mail potvrdu</label></td>
					</tr>
					<tr>
						<th scope="row"><label for="du-tpl">Izgled potvrde</label></th>
						<td><select id="du-tpl" name="<?php echo esc_attr( self::OPTION ); ?>[template]">
							<?php foreach ( self::templates() as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['template'], $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select></td>
					</tr>
					<tr>
						<th scope="row"><label for="du-sc">Naslov (klinika)</label></th>
						<td><input type="text" id="du-sc" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[subject_clinic]" value="<?php echo esc_attr( $s['subject_clinic'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="du-sp">Naslov (pacijent)</label></th>
						<td><input type="text" id="du-sp" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[subject_patient]" value="<?php echo esc_attr( $s['subject_patient'] ); ?>" />
						<p class="description">Oznake: {{ime_prezime}}, {{tretman}}, {{telefon}}, {{email}}</p></td>
					</tr>
					<tr>
						<th scope="row">Naslovnica</th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[home_demo]" value="1" <?php checked( $s['home_demo'], 1 ); ?> /> Prikaži Dentora stranicu na naslovnici</label>
						<p class="description">Naslovnica (<code><?php echo esc_html( home_url( '/' ) ); ?></code>) prikazuje stranicu iz mape
						<input type="text" class="small-text" style="width:140px" name="<?php echo esc_attr( self::OPTION ); ?>[demo_folder]" value="<?php echo esc_attr( $s['demo_folder'] ); ?>" /> u korijenu WordPressa.
						<?php echo file_exists( self::demo_file() ) ? '<strong style="color:#2e7d32">Mapa pronađena.</strong>' : '<strong style="color:#b3261e">Mapa nije pronađena.</strong>'; ?>
						Isključite kad aktivirate pravu temu.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="du-or">Dopuštene domene</label></th>
						<td><textarea id="du-or" class="large-text" rows="3" name="<?php echo esc_attr( self::OPTION ); ?>[allowed_origins]"><?php echo esc_textarea( $s['allowed_origins'] ); ?></textarea>
						<p class="description">Samo ako je stranica s obrascem na <strong>drugoj domeni</strong> od WordPressa (npr. <code>https://staging.dentora-clinic.hr</code>). Jedna po retku. Za istu domenu ostavite prazno.</p></td>
					</tr>
				</table>
				<?php submit_button( 'Spremi postavke' ); ?>
			</form>
			<p><strong>Adresa za obrazac:</strong> <code><?php echo esc_html( $api ); ?></code></p>
		</div>
		<?php
	}

	public static function admin_columns( $cols ) {
		return array(
			'cb'       => $cols['cb'] ?? '',
			'title'    => 'Upit',
			'telefon'  => 'Telefon',
			'email'    => 'E-mail',
			'mail'     => 'E-mail status',
			'date'     => 'Datum',
		);
	}

	public static function admin_column_value( $col, $post_id ) {
		if ( 'telefon' === $col ) {
			echo esc_html( get_post_meta( $post_id, '_telefon', true ) );
		} elseif ( 'email' === $col ) {
			echo esc_html( get_post_meta( $post_id, '_email', true ) );
		} elseif ( 'mail' === $col ) {
			echo esc_html( 'Klinika: ' . get_post_meta( $post_id, '_mail_klinika', true ) . ' · Pacijent: ' . get_post_meta( $post_id, '_mail_pacijent', true ) );
		}
	}

	public static function meta_box() {
		add_meta_box(
			'dentora_upit_detalji',
			'Detalji upita',
			static function ( $post ) {
				echo '<table class="widefat striped"><tbody>';
				foreach ( array( '_ime_prezime' => 'Ime i prezime', '_telefon' => 'Telefon', '_email' => 'E-mail', '_tretman' => 'Tretman', '_poruka' => 'Poruka', '_izvor' => 'Obrazac', '_mail_klinika' => 'E-mail klinici', '_mail_pacijent' => 'Potvrda pacijentu' ) as $k => $label ) {
					echo '<tr><th style="width:180px">' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( (string) get_post_meta( $post->ID, $k, true ) ) ) . '</td></tr>';
				}
				echo '</tbody></table>';
			},
			self::POST_TYPE,
			'normal',
			'high'
		);
	}
}

Dentora_Upiti::init();
