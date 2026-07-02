<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_VERSION', '2.8.1' );

if ( ! isset( $content_width ) ) {
	$content_width = 800; // Pixels.
}

if ( ! function_exists( 'hello_elementor_setup' ) ) {
	/**
	 * Set up theme support.
	 *
	 * @return void
	 */
	function hello_elementor_setup() {
		if ( is_admin() ) {
			hello_maybe_update_theme_version_in_db();
		}

		if ( apply_filters( 'hello_elementor_register_menus', true ) ) {
			register_nav_menus( [ 'menu-1' => esc_html__( 'Header', 'hello-elementor' ) ] );
			register_nav_menus( [ 'menu-2' => esc_html__( 'Footer', 'hello-elementor' ) ] );
		}

		if ( apply_filters( 'hello_elementor_post_type_support', true ) ) {
			add_post_type_support( 'page', 'excerpt' );
		}

		if ( apply_filters( 'hello_elementor_add_theme_support', true ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'automatic-feed-links' );
			add_theme_support( 'title-tag' );
			add_theme_support(
				'html5',
				[
					'search-form',
					'comment-form',
					'comment-list',
					'gallery',
					'caption',
					'script',
					'style',
				]
			);
			add_theme_support(
				'custom-logo',
				[
					'height'      => 100,
					'width'       => 350,
					'flex-height' => true,
					'flex-width'  => true,
				]
			);

			/*
			 * Editor Style.
			 */
			add_editor_style( 'classic-editor.css' );

			/*
			 * Gutenberg wide images.
			 */
			add_theme_support( 'align-wide' );

			/*
			 * WooCommerce.
			 */
			if ( apply_filters( 'hello_elementor_add_woocommerce_support', true ) ) {
				// WooCommerce in general.
				add_theme_support( 'woocommerce' );
				// Enabling WooCommerce product gallery features (are off by default since WC 3.0.0).
				// zoom.
				add_theme_support( 'wc-product-gallery-zoom' );
				// lightbox.
				add_theme_support( 'wc-product-gallery-lightbox' );
				// swipe.
				add_theme_support( 'wc-product-gallery-slider' );
			}
		}
	}
}
add_action( 'after_setup_theme', 'hello_elementor_setup' );

function hello_maybe_update_theme_version_in_db() {
	$theme_version_option_name = 'hello_theme_version';
	// The theme version saved in the database.
	$hello_theme_db_version = get_option( $theme_version_option_name );

	// If the 'hello_theme_version' option does not exist in the DB, or the version needs to be updated, do the update.
	if ( ! $hello_theme_db_version || version_compare( $hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<' ) ) {
		update_option( $theme_version_option_name, HELLO_ELEMENTOR_VERSION );
	}
}

if ( ! function_exists( 'hello_elementor_scripts_styles' ) ) {
	/**
	 * Theme Scripts & Styles.
	 *
	 * @return void
	 */
	function hello_elementor_scripts_styles() {
		$min_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( apply_filters( 'hello_elementor_enqueue_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor',
				get_template_directory_uri() . '/style' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( apply_filters( 'hello_elementor_enqueue_theme_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor-theme-style',
				get_template_directory_uri() . '/theme' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_scripts_styles' );

if ( ! function_exists( 'hello_elementor_register_elementor_locations' ) ) {
	/**
	 * Register Elementor Locations.
	 *
	 * @param ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager theme manager.
	 *
	 * @return void
	 */
	function hello_elementor_register_elementor_locations( $elementor_theme_manager ) {
		if ( apply_filters( 'hello_elementor_register_elementor_locations', true ) ) {
			$elementor_theme_manager->register_all_core_location();
		}
	}
}
add_action( 'elementor/theme/register_locations', 'hello_elementor_register_elementor_locations' );

if ( ! function_exists( 'hello_elementor_content_width' ) ) {
	/**
	 * Set default content width.
	 *
	 * @return void
	 */
	function hello_elementor_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'hello_elementor_content_width', 800 );
	}
}
add_action( 'after_setup_theme', 'hello_elementor_content_width', 0 );

if ( is_admin() ) {
	require get_template_directory() . '/includes/admin-functions.php';
}

/**
 * If Elementor is installed and active, we can load the Elementor-specific Settings & Features
*/

// Allow active/inactive via the Experiments
require get_template_directory() . '/includes/elementor-functions.php';

/**
 * Include customizer registration functions
*/
function hello_register_customizer_functions() {
	if ( is_customize_preview() ) {
		require get_template_directory() . '/includes/customizer-functions.php';
	}
}
add_action( 'init', 'hello_register_customizer_functions' );

if ( ! function_exists( 'hello_elementor_check_hide_title' ) ) {
	/**
	 * Check hide title.
	 *
	 * @param bool $val default value.
	 *
	 * @return bool
	 */
	function hello_elementor_check_hide_title( $val ) {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$current_doc = Elementor\Plugin::instance()->documents->get( get_the_ID() );
			if ( $current_doc && 'yes' === $current_doc->get_settings( 'hide_title' ) ) {
				$val = false;
			}
		}
		return $val;
	}
}
add_filter( 'hello_elementor_page_title', 'hello_elementor_check_hide_title' );

if ( ! function_exists( 'hello_elementor_add_description_meta_tag' ) ) {
	/**
	 * Add description meta tag with excerpt text.
	 *
	 * @return void
	 */
	function hello_elementor_add_description_meta_tag() {
		$post = get_queried_object();

		if ( is_singular() && ! empty( $post->post_excerpt ) ) {
			echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $post->post_excerpt ) ) . '">' . "\n";
		}
	}
}
add_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );

/**
 * BC:
 * In v2.7.0 the theme removed the `hello_elementor_body_open()` from `header.php` replacing it with `wp_body_open()`.
 * The following code prevents fatal errors in child themes that still use this function.
 */
if ( ! function_exists( 'hello_elementor_body_open' ) ) {
	function hello_elementor_body_open() {
		wp_body_open();
	}
}

// wp_enqueue_style('style-custom', get_template_directory_uri() . '/style-custom.css');

function encolar_scripts_reservas() {
	wp_enqueue_style('style-custom', get_template_directory_uri() . '/style-custom.css?v=2');
}

add_action('wp_enqueue_scripts', 'encolar_scripts_reservas');


function obtener_ubicacion_juego($producto_id, $nivel = 0) {
    $categorias = wp_get_post_terms($producto_id, 'product_cat');
    $resultado = ($nivel == 2) ? [] : null;

    foreach ($categorias as $categoria) {
        $actual = $categoria;
        $contador_nivel = 0;

        // Subir por la jerarquía para contar el nivel
        while ($actual->parent != 0) {
            $actual = get_term($actual->parent, 'product_cat');
            $contador_nivel++;
        }

        if ($contador_nivel == $nivel) {
            if ($nivel == 2) {
                $resultado[] = ['id' => $categoria->term_id, 'nombre' => $categoria->name];
            } else {
                return ['id' => $categoria->term_id, 'nombre' => $categoria->name];
            }
        }
    }

    return $resultado;
}


function funcion_generar_key_reserva($longitud=10) {
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numCaracteres = strlen($caracteres);
    $codigoAleatorio = '';

    for ($i = 0; $i < $longitud; $i++) {
        $indexAleatorio = rand(0, $numCaracteres - 1);
        $codigoAleatorio .= $caracteres[$indexAleatorio];
    }

    return $codigoAleatorio;
}


//Desactivar "Este usuario ha cambiado de email"
add_filter( 'send_password_change_email', '__return_false' );


//Deshabilitar el botón de "volver a comprar" en thankyou
remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );


//libreria fontawesome
add_action( 'wp_enqueue_scripts', 'enqueue_load_fa' );
function enqueue_load_fa() {
	wp_enqueue_style( 'load-fa', 'https://use.fontawesome.com/releases/v5.3.1/css/all.css' );
}

// list in city games
function list_games_visitasqr($data){
	if(!empty($data) && isset($data['_juego_titulo_']) && isset($data['_juego_imagenes_portada_']) && isset($data['_juego_icono_']) && isset($data['_juego_minimo_jugadores_']) && isset($data['_juego_maximo_jugadores_']) && isset($data['_juego_duracion_']) && isset($data['_juego_direccion_']) && isset($data['_juego_info_link_']) && isset($data['_url_reservas_'])){
		foreach($data as $d){
			$return = '<div class="col col33"><div class="nueva-habitacion"><div class="owl-carousel owl-sliderjuego">';
			foreach($data['_juego_imagenes_portada_'] as $m) $return .= '<div class="item"><img src="'.$m.'" width="800" height="540"></div>';
			$return .= '<div class="descripcion-juego">
							<div class="logo-juego">
								<img src='.$data['_juego_icono_'].' width="300" height="300" />
							</div>
							<p class="caracteristicas">
								<strong class="titulo-seo">'.$data['_juego_titulo_'].'</strong>
								<img src="https://cocoroom.es/wp-content/themes/cocoroom/img/habitaciones/jugadores-blanco.svg" alt="Jugadores" width="16" height="16"/> '.$data['_juego_minimo_jugadores_'].'-'.$data['_juego_maximo_jugadores_'].'
								<img class="reloj" src="https://cocoroom.es/wp-content/themes/cocoroom/img/habitaciones/reloj-blanco.svg" alt="Duracion" width="30" height="16"/> '.$data['_juego_duracion_'].'
								<br>
								'.$data['_juego_direccion_'].'
							</p>
						</div>
						<div class="botones-juego">
							<a href="'.$data['_juego_info_link_'].'" class="boton boton-blanco" >+ info</a>
							<span class="boton boton-ambar" onclick="location.href='.$data['_url_reservas_'].'" title="'.($data['_juego_ciudad_']) ? 'Reservas '.$data['_juego_ciudad_'] : ''.'">Reservar</span>
						</div>
					</div>
				</div>';
			echo $return;
		}
	}
}

//función imprimir partes ficha de producto
function html_games_visitassqr(){
	if(empty($params)) return;
	$r = endpoint_visitasqr($params);
	if($r['r']){
		
	}
	$return = '<section class="header-juego wow fadeIn" data-wow-duration="0.6s" data-wow-delay="0s">
						<div class="container">
							<div>
							<h1 class="titulo-barra"><?php the_title();?></h1>
								<ul>
									<li><p><span>Duración</span>60min</p></li>
									<li><p><span>Dificultad</span>6/10</p></li>
									<li><p><span>Jugadores</span>3-5</p></li>
								</ul>
							</div>
						</div>
						<img src="/wp-content/themes/cocoroom/img/habitaciones/cabecera-enigma.jpg" width="1920" height="800" alt="Ramón y Cajal">
				</section>';
}


/* VACIAR CARRITO OLVIDADO */
function clear_persistent_cart_after_login( $user_login, $user ) {
    $blog_id = get_current_blog_id();
    // persistent carts created in WC 3.1 and below
    if ( metadata_exists( 'user', $user->ID, '_woocommerce_persistent_cart' ) ) {
        delete_user_meta( $user->ID, '_woocommerce_persistent_cart' );
    }

    // persistent carts created in WC 3.2+
    if ( metadata_exists( 'user', $user->ID, '_woocommerce_persistent_cart_' . $blog_id ) ) {
        delete_user_meta( $user->ID, '_woocommerce_persistent_cart_' . $blog_id );
    }
}
add_action('wp_login', 'clear_persistent_cart_after_login', 10, 2);



function agregar_usuario_a_lista_brevo($id_usuario, $id_lista = 3){
	error_log(" ---------------BREVO----------------");

	require_once ABSPATH . 'load-env.php';

	$apiKey = $_ENV['SENDINBLUE_API_KEY'];

	$info_usuario = get_userdata($id_usuario);
	$id_lista = intval($id_lista);

	if ($info_usuario) {
    	$email_usuario = $info_usuario->user_email;
    	$billing_first_name = get_user_meta( $id_usuario, 'billing_first_name', true );
    	$billing_last_name = get_user_meta( $id_usuario, 'billing_last_name', true );
    	$fecha_nacimiento = get_user_meta( $id_usuario, 'fecha_nacimiento', true );
    	$numero_telefono = get_user_meta( $id_usuario, 'billing_phone', true );

		if($email_usuario){
			$response = wp_remote_post('https://api.sendinblue.com/v3/contacts', array(
				'headers' => array(
					'api-key' => $apiKey,
					'Content-Type' => 'application/json',
				),
				'body' => json_encode(array(
					'email' => $email_usuario,
					'listIds' => array($id_lista),
					'attributes' => array(
						"NOMBRE" => $billing_first_name,
						"APELLIDOS" => $billing_last_name,
						"FECHA_NACIMIENTO" => $fecha_nacimiento,
					),
				)),
			));

			// "SMS" => $numero_telefono,
			
			$body = wp_remote_retrieve_body($response);

			if(json_decode($body)->code == "duplicate_parameter"){
				$response = wp_remote_post('https://api.sendinblue.com/v3/contacts/lists/'.$id_lista.'/contacts/add', array(
					'headers' => array(
						'api-key' => $apiKey,
						'Content-Type' => 'application/json',
					),
					'body' => json_encode(array(
						'emails' => array($email_usuario),
					)),
				));
				// error_log("Usuario YA EXISTENTE ha sido agragado a la lista de Brevo con ID ".$id_lista);
			}
			else{
				// error_log("Usuario NUEVO ha sido agragado a la lista de Brevo con ID ".$id_lista);
			}

			/*error_log("EMAIL ".$email_usuario);
			error_log("NOMBRE ".$billing_first_name);
			error_log("APELLIDOS ".$billing_last_name);
			error_log("SMS ".$numero_telefono." (DESACTIVADO)");
			error_log("FECHA_NACIMIENTO ".$fecha_nacimiento);
			error_log("RESPUESTA DE BREVO: ".$body);

			error_log(" ------------------------------- ");
			error_log(" ");*/

		}
	}
}


function borrar_variables_sesion_al_cerrar_sesion() {
    if ( isset( $_SESSION['key'] ) ) {
        unset( $_SESSION['key'] );
    }
    if ( isset( $_SESSION['action'] ) ) {
        unset( $_SESSION['action'] );
    }
	if ( isset( $_SESSION['id'] ) ) {
        unset( $_SESSION['id'] );
    }
}
add_action('wp_logout', 'borrar_variables_sesion_al_cerrar_sesion');




function coco_log($mensaje) {
    $archivo = ABSPATH . 'wp-content/coco.log';
    $mensaje = current_time('mysql') . ' - ' . $mensaje . "\n";
    error_log($mensaje, 3, $archivo);
}









/* CRON DIARIO PARA VACIAR EL CARRITO */

if ( ! wp_next_scheduled( 'cron_vaciar_carritos' ) ) {
    error_log("EL CRON HA SIDO ACTIVADO");
    $timestamp = strtotime('tomorrow midnight'); // Obtener el timestamp de la medianoche del día siguiente
    wp_schedule_event( $timestamp, 'daily', 'cron_vaciar_carritos' );
}
add_action( 'cron_vaciar_carritos', 'vaciar_carritos_dia_anterior' );

// Desprogramar el evento si está programado
// if ( wp_next_scheduled( 'cron_vaciar_carritos' ) ) {
//     wp_clear_scheduled_hook( 'cron_vaciar_carritos' );
//     error_log("EL CRON HA SIDO DESACTIVADO");
// }

function vaciar_carritos_dia_anterior() {
    global $wpdb;

    // Vaciar carritos de usuarios no logueados (carritos en sesión)
    $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_sessions");
    error_log('Carritos no logueados vaciados con éxito.');

    // Vaciar carritos de usuarios logueados
    $meta_key = '_woocommerce_persistent_cart_%';

    // Borrar todos los metadatos relacionados con carritos persistentes de usuarios logueados
    $wpdb->query("DELETE FROM {$wpdb->prefix}usermeta WHERE meta_key LIKE '$meta_key'");
    error_log('Carritos de usuarios logueados vaciados con éxito3.');
}

/* CRON DIARIO PARA VACIAR EL CARRITO */
