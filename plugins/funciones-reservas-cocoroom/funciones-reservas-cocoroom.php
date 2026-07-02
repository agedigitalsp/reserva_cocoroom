<?php
/*
* Plugin Name: Funciones Reservas Coco Room
* Plugin URI: https://agenciasp.com/
* Description: Funciones para crear la página de reservas de Coco Room
* Version: 1.0.0
* Author: Agencia Digital SP
* Author URI: https://agenciasp.com/
* License:
*/

function enqueue_my_scripts() {
    if(is_home() || is_page(42) || is_page(323) || is_page(327)){
        wp_enqueue_script('scripts_reservas', plugin_dir_url(__FILE__). '/js/scripts_reservas.js', array('jquery'), '2.0', true);
        wp_localize_script('scripts_reservas', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    wp_enqueue_style('material-ui-core', 'https://fonts.googleapis.com/icon?family=Material+Icons');

}
add_action('wp_enqueue_scripts', 'enqueue_my_scripts');

function enqueue_sweetalert2() {
    wp_enqueue_style( 'sweetalert2', plugin_dir_url( __FILE__ ) . 'vendor/sweetalert/sweetalert2.min.css' );
    wp_enqueue_style( 'animatecss', plugin_dir_url( __FILE__ ) . 'vendor/animatecss/animatecss.min.css' );
    wp_enqueue_script( 'sweetalert2', plugin_dir_url( __FILE__ ) . 'vendor/sweetalert/sweetalert2.all.min.js', array( 'jquery' ), '1.5.0', true );
}
add_action('wp_enqueue_scripts', 'enqueue_sweetalert2');




//MOVIDA A RESERVAS.PHP (PLANTILLA DEL TEMA HIJO)
/*function formulario_reservas_cocoroom(){
...
}
add_shortcode('reservas_ciudad', 'formulario_reservas_cocoroom');*/


//MOVIDA A FUNCTIONS.PHP
/*function obtener_ubicacion_juego($producto_id, $nivel = 0) {
...
}*/

//MOVIDA A FUNCTIONS.PHP
/*function funcion_generar_key_reserva($longitud=10) {
...
}*/


add_action('wp_ajax_obtener_reservas_por_ciudad', 'obtener_reservas_por_ciudad'); 
add_action('wp_ajax_nopriv_obtener_reservas_por_ciudad', 'obtener_reservas_por_ciudad'); 
function obtener_reservas_por_ciudad() {

//OBTENER JUEGOS DE LA CIUDAD
    $num_usuarios = $_POST['num_usuarios'];
    $fecha_reserva = $_POST['fecha_reserva'];
    $slug_ciudad = $_POST['ciudad'];
    $id_ciudad = get_term_by('slug', $slug_ciudad, 'product_cat')->term_id;
    $reserva_telefonica = false;

    $dateTime_fecha_reserva = DateTime::createFromFormat('Y-m-d', $fecha_reserva);
    if ($dateTime_fecha_reserva === false) {
        echo json_encode(array("error" => "¡Error! La fecha introducida es incorrecta"));
        wp_die();
    }
    $hoy = new DateTime();
    if ($dateTime_fecha_reserva->format('Y-m-d') < $hoy->format('Y-m-d')) {
        echo json_encode(array("error" => "¡Error! La fecha introducida no es válida"));
        wp_die();
    } elseif ($dateTime_fecha_reserva->format('Y-m-d') === $hoy->format('Y-m-d') || $dateTime_fecha_reserva->format('Y-m-d') === $hoy->modify('+1 day')->format('Y-m-d')) {
        $reserva_telefonica = true;
    }

    $id_juego_seleccionado_api = "No se ha facilitado un juego";
    $num_usuarios_incorrecto_juego_seleccionado_api = false;
    $id_juego_api = $_POST['juego_seleccionado'];
    if (isset($id_juego_api) && $id_juego_api) {
        if (is_numeric($id_juego_api)) {// Comprobar si es un número
            $juego_api = wc_get_product($id_juego_api);
            if ($juego_api) {// Comprobar si es un ID de producto válido en WooCommerce
                $id_ciudad_juego_api = obtener_ubicacion_juego($id_juego_api)["id"];
                if($id_ciudad == $id_ciudad_juego_api){ // Comprobaciones de seguridad para que el juego que se consulta pertenece a 
                    $id_juego_seleccionado_api = (int)$id_juego_api;
                }
                else
                    $id_juego_seleccionado_api = "Se ha intentado alterar insertando un id de un juego al que no corresponde la ciudad";
            }
            else
                $id_juego_seleccionado_api = "Se ha intentado alterar insertando un id que no existe";
        }
        else
            $id_juego_seleccionado_api = "Se ha intentado alterar insertando un id que no es numérica";
    }

    $query = new WP_Query(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $slug_ciudad,
            )
        ),
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'numero_minimo_jugadores',
                'value' => $num_usuarios,
                'compare' => '<=',
                'type' => 'NUMERIC'
            ),
            array(
                'key' => 'numero_maximo_jugadores',
                'value' => $num_usuarios,
                'compare' => '>=',
                'type' => 'NUMERIC'
            )
        )
    ));

    if (!$query->have_posts()){
        if(is_numeric($id_juego_seleccionado_api)){
            $nombre_juego_api = wc_get_product($id_juego_seleccionado_api)->get_title();
            $numero_minimo_jugadores_juego_api = get_post_meta($id_juego_seleccionado_api, 'numero_minimo_jugadores', true);
            $numero_maximo_jugadores_juego_api = get_post_meta($id_juego_seleccionado_api, 'numero_maximo_jugadores', true);

            echo json_encode(array("error" => "¡No hay juegos disponibles!<br>Ten en cuenta que el juego seleccionado (".$nombre_juego_api.") es para un número entre ".$numero_minimo_jugadores_juego_api." y ".$numero_maximo_jugadores_juego_api." jugadores"));
        }
        else{
            echo json_encode(array("error" => "¡No hay juegos disponibles!<br>Revisa los datos introducidos"));
        }
        wp_die();
    }
    else{
        $lista_juegos = array();
        while ($query->have_posts()) : $query->the_post();

            $local = obtener_ubicacion_juego(get_the_ID(),1);
            $salas = obtener_ubicacion_juego(get_the_ID(),2);

            $modo_combate = get_post_meta(get_the_ID(), 'modo_combate', true);
            $lista_precios = preg_replace("/\r?\n/", ",", trim(get_post_meta(get_the_ID(), 'lista_precios', true)));

            $telefono_contacto_local = get_term_meta($local["id"], 'telefono_contacto', true);

            // $horas = preg_replace("/\r?\n/", ",", trim(get_post_meta(get_the_ID(), 'horario_juego', true)));
            $parts = preg_replace("/\r?\n/", ",", trim(get_post_meta(get_the_ID(), 'horario_juego', true)));
            $parts = explode(",", $parts);

            $horas = [];
            $porcentajes = [];

            foreach ($parts as $part) {
                $subparts = explode(' - ', $part);
                
                if (count($subparts) === 2) {
                    $horas[] = $subparts[0];
                    $porcentajes[] = $subparts[0] ." - ". $subparts[1];
                } else {
                    $horas[] = $subparts[0];
                }
            }


            if($local["id"] && $local["nombre"] && $horas && $lista_precios && $telefono_contacto_local && get_post_meta( get_the_ID(), '_regular_price', true)){
                //SI ES UN JUEGO SIMPLE
                if ($salas && (is_null($modo_combate) || !$modo_combate)) {
                    foreach($salas as $sala){
                        $lista_juegos[] = array(
                            'id' => get_the_ID(),
                            'nombre' => get_the_title(),
                            'datos_salas' => array($sala),
                            'id_local' => $local["id"],
                            'nombre_local' => $local["nombre"],
                            'direccion_local' => get_term_meta($local["id"], "direccion", true),
                            'telefono_contacto_local' => $telefono_contacto_local,
                            'horas' => implode(",", $horas),
                            'porcentajes' => implode(",", $porcentajes),
                            'duracion_estimada' => get_post_meta(get_the_ID(), 'duracion_estimada', true),
                            'lista_precios' => $lista_precios,
                            'modo_kids' => get_post_meta(get_the_ID(), 'modo_kids', true) ? true : false,
                            'color' => get_post_meta(get_the_ID(), '_juego_tipo_', true) ? get_post_meta(get_the_ID(), '_juego_tipo_', true) : "Normal"
                        );
                    }
                }
                else{
                    $lista_juegos[] = array(
                        'id' => get_the_ID(),
                        'nombre' => get_the_title(),
                        'datos_salas' => $salas,
                        'id_local' => $local["id"],
                        'nombre_local' => $local["nombre"],
                        'direccion_local' => get_term_meta($local["id"], "direccion", true),
                        'telefono_contacto_local' => $telefono_contacto_local,
                        'horas' => implode(",", $horas),
                        'porcentajes' => implode(",", $porcentajes),
                        'duracion_estimada' => get_post_meta(get_the_ID(), 'duracion_estimada', true),
                        'lista_precios' => $lista_precios,
                        'modo_kids' => get_post_meta(get_the_ID(), 'modo_kids', true) ? true : false,
                        'color' => get_post_meta(get_the_ID(), '_juego_tipo_', true) ? get_post_meta(get_the_ID(), '_juego_tipo_', true) : "Normal"
                    );
                }
            }
            
        endwhile;
        
        if(is_numeric($id_juego_seleccionado_api)){
            // Ordenamos el array con usort
            usort($lista_juegos, function($a, $b) use ($id_juego_seleccionado_api) {
                if ($a['id'] == $id_juego_seleccionado_api) {
                    return -1;
                }
                if ($b['id'] == $id_juego_seleccionado_api) {
                    return 1;
                }
                return 0;
            });

            $existe = false;
            foreach ($lista_juegos as $juego) {
                if ($juego['id'] == $id_juego_seleccionado_api) {
                    $existe = true;
                    break;  // Salimos del bucle si encontramos el juego
                }
            }
            if(!$existe){
                $num_usuarios_incorrecto_juego_seleccionado_api = true; 
            }
        }


        wp_reset_query();

    }
    
    
    usort($lista_juegos, function($a, $b) use($slug_ciudad) {
        $order = ['Ambar' => 1,'Normal' => 2, 'Kids' => 3];

        if($slug_ciudad === "coco-room-zaragoza"){
            $order = ['Normal' => 1, 'Kids' => 2,'Ambar' => 3];
        }
        
        $tipo_a = $a['color'];
        $tipo_b = $b['color'];
    
        $order_a = $order[$tipo_a];
        $order_b = $order[$tipo_b];
    
        if($order_a - $order_b == 0){
            $order_a = $a['nombre'];
            $order_b = $b['nombre'];
            if(strcasecmp($order_a, $order_b) == 0 && isset($a['datos_salas'][0]['nombre']) && isset($b['datos_salas'][0]['nombre'])){
                $order_a = $a['datos_salas'][0]['nombre'];
                $order_b = $b['datos_salas'][0]['nombre'];
                return strcasecmp($order_a, $order_b);
            }
            else return strcasecmp($order_a, $order_b);
        }
        else return $order_a - $order_b;
    });
    

//OBTENER RESERVAS DE LA CIUDAD

    //COMPROBAR SI LOS CAMPOS OBTENIDOS POR POST SON CORRECTOS

    global $wpdb;

    $sql = $wpdb->prepare("SELECT id_salas, id_juego, hora_reserva FROM " . $wpdb->prefix . "reservas WHERE fecha_reserva = %s AND id_ciudad = %s AND estado IN ('pendiente', 'reservada')", $fecha_reserva, $id_ciudad);
    $reservas = $wpdb->get_results($sql, ARRAY_A); // Obtener como array asociativo

    // Obtener los productos del carrito
    $cart_items = WC()->cart->get_cart();
    foreach ($cart_items as $cart_item) {
        $reserva_item = array(
            'id_salas'       => strval($cart_item['id_salas']),
            "id_juego"            => strval($cart_item['product_id']),
            'hora_reserva'           => $cart_item['hora'],
        );

        /*  CARRITO REPETIDO ALERTA  */
        if(strval($cart_item['fecha_reserva'] == $fecha_reserva))
            array_push($reservas, $reserva_item);

    }

//OBTENER CIERRES DE LA CIUDAD

    $sql = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "cierres WHERE id_ciudad = %s", $id_ciudad);
    $cierres = $wpdb->get_results($sql);

    $periodos_cierres = array();

    foreach( $cierres as $cierre ){
        $fecha = json_decode($cierre->fecha);
        $hora = json_decode($cierre->hora);
        $aniadir = false;

        if($fecha->tipo == "fecha"){
            if($fecha->valores == $fecha_reserva)
                $aniadir = true;
        }else
        if($fecha->tipo == "intervalo"){
            $fechas_intervalo = explode(",", $fecha->valores);
            if ($fecha_reserva >= $fechas_intervalo[0] && $fecha_reserva <= $fechas_intervalo[1]) 
                $aniadir = true;
        }
        else
        if($fecha->tipo == "dias_semana"){
            $fecha_reserva_cast = new DateTime($fecha_reserva);
            $fecha_reserva_dia_semana = $fecha_reserva_cast->format('w');
            $dias_semana_cierre = explode(",", $fecha->valores);

            switch($fecha_reserva_dia_semana) {
                case 0:
                    $fecha_reserva_dia_semana = "domingo";
                    break;
                case 1:
                    $fecha_reserva_dia_semana = "lunes";
                    break;
                case 2:
                    $fecha_reserva_dia_semana = "martes";
                    break;
                case 3:
                    $fecha_reserva_dia_semana = "miercoles";
                    break;
                case 4:
                    $fecha_reserva_dia_semana = "jueves";
                    break;
                case 5:
                    $fecha_reserva_dia_semana = "viernes";
                    break;
                case 6:
                    $fecha_reserva_dia_semana = "sabado";
                    break;
                default:
                    $fecha_reserva_dia_semana = "invalido";
                    break;
            }
            if (in_array($fecha_reserva_dia_semana, $dias_semana_cierre)) {
                $aniadir = true;
            }
        }

        $tipo_cierre = json_decode($cierre->tipo_cierre);
        
        if($aniadir){
            if($tipo_cierre->tipo == "juego"){
                $tipo_cierre_valores = json_decode($tipo_cierre->valores);
                $periodos_cierres[] = array(
                    "tipo_cierre" => "juego",
                    "id_juego" => $tipo_cierre_valores->id_juego,
                    "id_salas" => $tipo_cierre_valores->id_salas,
                    "hora" => $hora
                );
            }
            else
            if($tipo_cierre->tipo == "local"){
                $id_local = $tipo_cierre->valores;
                $periodos_cierres[] = array(
                    "tipo_cierre" => "local",
                    "id_local" => $id_local,
                    "hora" => $hora
                );
            }
        }
    }



    if($num_usuarios_incorrecto_juego_seleccionado_api){

        $nombre_juego_api = wc_get_product($id_juego_seleccionado_api)->get_title();
        $numero_minimo_jugadores_juego_api = get_post_meta($id_juego_seleccionado_api, 'numero_minimo_jugadores', true);
        $numero_maximo_jugadores_juego_api = get_post_meta($id_juego_seleccionado_api, 'numero_maximo_jugadores', true);
        $mensaje_error_juego_api="¡No hay juegos disponibles! Ten en cuenta que el juego seleccionado (".$nombre_juego_api.") es para un número entre ".$numero_minimo_jugadores_juego_api." y ".$numero_maximo_jugadores_juego_api." jugadores";
    
        echo json_encode(array(
            "juegos" => $lista_juegos,
            "reservas" => $reservas,
            "cierres" => $periodos_cierres,
            "juego_seleccionado" => $id_juego_seleccionado_api,
            "reserva_telefonica" => $reserva_telefonica,
            "mensaje_error_juego_api" => $mensaje_error_juego_api,
        ));
    }
    else{
        echo json_encode(array(
            "juegos" => $lista_juegos,
            "reservas" => $reservas,
            "cierres" => $periodos_cierres,
            "juego_seleccionado" => $id_juego_seleccionado_api,
            "reserva_telefonica" => $reserva_telefonica,
        ));
    }
    wp_die();
}


//woocommerce_new_order NO FUNCIONABA PORQUE SE EJECUTABA ANTES DE INSERTAR LOS METADATOS
//EN CASO DE SER UNA RESERVA CREADA MANUALMENTE, SE LLAMA AL HOOK woocommerce_checkout_order_processed Y SE EJECUTA EL CÓDIGO DE ABAJO. "CERRAR LA RUEDA"
add_action('woocommerce_checkout_order_processed', 'insertar_reserva_en_tabla_reservas', 10, 1);
function insertar_reserva_en_tabla_reservas($order_id) {
    coco_log("-- Se procede a insertar una reserva en la tabla --");
    $order = wc_get_order($order_id);

    $items = $order->get_items();
    $first_item = reset($items);
    $id_ciudad = wc_get_order_item_meta( $first_item->get_id(), 'datos_reserva', true )["id_ciudad"];

    $customer_firstname = $order->get_billing_first_name();
    $customer_lastname = $order->get_billing_last_name();
    $customer_email = $order->get_billing_email();
    $customer_phone = $order->get_billing_phone();

    $fecha_nacimiento = $order->get_meta('fecha_nacimiento', true);
    $quiero_promociones = $order->get_meta('quiero_promociones', true);
    $aceptar_redes_sociales = $order->get_meta('aceptar_redes_sociales', true);

    $customer_id = $order->get_customer_id();

    if($customer_id == 0){
        if ( !email_exists( $customer_email ) ) {
            $customer_password = wp_generate_password();
            $customer_data = array(
                'user_login' => $customer_email,
                'user_email' => $customer_email,
                'user_pass'  => $customer_password,
                'role'       => 'subscriber', 
            );
            $customer_id = wp_insert_user( $customer_data );
            update_post_meta( $order_id, '_customer_user', $customer_id );
            update_user_meta( $customer_id, 'first_name', $customer_firstname );
            update_user_meta( $customer_id, 'billing_first_name', $customer_firstname);
            update_user_meta( $customer_id, 'last_name', $customer_lastname );
            update_user_meta( $customer_id, 'billing_last_name', $customer_lastname );
            update_user_meta( $customer_id, 'billing_email', $customer_email );
            update_user_meta( $customer_id, 'billing_phone', $customer_phone );
            update_user_meta( $customer_id, 'fecha_nacimiento', $fecha_nacimiento );

            if($aceptar_redes_sociales)
                $aceptar_redes_sociales = 1;
            else
                $aceptar_redes_sociales = 0;
            update_user_meta($customer_id, 'aceptar_redes_sociales', $aceptar_redes_sociales);

            if($quiero_promociones && $fecha_nacimiento){
                update_user_meta( $customer_id, 'quiero_promociones', '1' );
                update_user_meta( $customer_id, 'fecha_nacimiento', $fecha_nacimiento );

                $id_lista_brevo = get_term_meta($id_ciudad, 'id_lista_brevo', true);

                agregar_usuario_a_lista_brevo($customer_id); //CUMPLEAÑOS
                if($id_lista_brevo != ""){
                    agregar_usuario_a_lista_brevo($customer_id, $id_lista_brevo); //CIUDAD
                }
            }
            
            $the_new_user = new WP_User($customer_id);
            $reset_key = get_password_reset_key($the_new_user);

            $enlace_restaurar_password =  home_url()."/recuperar-contrasena/?key=" . $reset_key . "&us=an" . urlencode(base64_encode($the_new_user->user_login));

            //Enviar email bienvenida a familia COCO ROOM
            $id_ciudad = "";
            foreach ($order->get_items() as $item_key => $item) {
                $datos_reserva = $item->get_meta('datos_reserva', true);
                if ( is_array( $datos_reserva ) ) {
                    $id_ciudad = $datos_reserva['id_ciudad'];
                }
                break;
            }
            if($id_ciudad){
                $telefono_contacto_franquicia = get_term_meta($id_ciudad, 'telefono_contacto', true);
                $email_contacto_franquicia = get_term_meta($id_ciudad, 'email_contacto', true);
                $nombre_sl_franquicia = get_term_meta($id_ciudad, 'nombre_sl_franquicia', true);
            }
            $data = array(
                'Nombre' => $customer_firstname,
                'Apellidos' => $customer_lastname,
                'Telefono' => $customer_phone,
                'Email' => $customer_email,

                "Enlace_restaurar_password" => $enlace_restaurar_password,

                "Telefono_contacto_franquicia" => $telefono_contacto_franquicia,
                "Email_contacto_franquicia" => $email_contacto_franquicia,
                "Nombre_sl_franquicia" => $nombre_sl_franquicia,
            );

            coco_log("El usuario se ha registrado tras reservar (".$customer_email.")");

            do_action('send_mails_adpnsy_cllbck', $customer_email, '¡Te has registrado en Coco Room!', 'new-usuario-compra', $data);


        }
        else{
            $customer_id = get_user_by('email', $customer_email)->ID;;
            update_post_meta( $order_id, '_customer_user', $customer_id );
            update_user_meta( $customer_id, 'first_name', $customer_firstname );
            update_user_meta( $customer_id, 'last_name', $customer_lastname );
            update_user_meta( $customer_id, 'billing_email', $customer_email );
            update_user_meta( $customer_id, 'billing_phone', $customer_phone );

            if($quiero_promociones && $fecha_nacimiento){
                update_user_meta( $customer_id, 'quiero_promociones', '1' );
                update_user_meta( $customer_id, 'fecha_nacimiento', $fecha_nacimiento );
    
                $id_lista_brevo = get_term_meta($id_ciudad, 'id_lista_brevo', true);
    
                if($id_lista_brevo != ""){
                    agregar_usuario_a_lista_brevo($customer_id); //CUMPLEAÑOS
                    coco_log("-- Se procede a insertar el usuario ".$customer_email." en la lista de brevo ".$id_lista_brevo." --");
                    agregar_usuario_a_lista_brevo($customer_id, $id_lista_brevo); //CIUDAD
                }
            }
        }
    }
    

    /*
    customer_id==0?
        Si: existe email introducido?
            Si: se le asigna el pedido con la id del usuario y se actualizan sus datos
            No: se registra un usuario nuevo
            
        No: El usuario ya está logueado

    Usuario no registrado hace compra como invitado 
    Usuario registrado hace compra como invitado
    Usuario registrado hace compra con su cuenta
    */

    $cupones_descuento = $order->get_used_coupons();
    $cupon_descuento = "";
    $error = false;

    if( count($cupones_descuento) ){
        $cupon_descuento = implode(", ", $cupones_descuento);
        coco_log("Se ha utilizado el cupón(es) " . $cupon_descuento . " para la reserva ");
    }

    foreach ($order->get_items() as $item_key => $item) {

        $datos_reserva = $item->get_meta('datos_reserva', true);

        global $wpdb;

        $order_id = $order->get_id();
        $id_ciudad = $datos_reserva['id_ciudad'];
        $id_local = $datos_reserva['id_local'];
        $id_salas = $datos_reserva['id_salas'];
        $hora_reserva = $datos_reserva['hora'];
        $fecha_reserva = $datos_reserva['fecha_reserva'];
        $num_usuarios = $datos_reserva['num_usuarios'];
        $id_juego = $item->get_product_id();
    
        $sql_reserva = $wpdb->prepare("SELECT * FROM " .$wpdb->prefix. "reservas WHERE 
            id_juego = ".$id_juego." AND 
            fecha_reserva = '".$fecha_reserva."' AND 
            hora_reserva = '".$hora_reserva."' AND 
            id_ciudad = ".$id_ciudad." AND 
            id_local = ".$id_local." AND 
            id_salas = '".$id_salas."' AND 
            estado NOT LIKE 'cancelada'"
        );

        $reserva_ya_guardada = $wpdb->get_row($sql_reserva);
    
        if(empty($reserva_ya_guardada)){

            $id_juego = $item->get_product_id();
            $nombre_juego = $item->get_name();

            $nota_pedido = "";

            if(isset($datos_reserva['descuento_hora'])) 
                $nota_pedido = "La hora ". $datos_reserva['hora'] . " en " . $nombre_juego . " tiene descuento (" .$datos_reserva['descuento_hora']. ")";

            
            $nota_pedido .= $order->get_customer_note() ? "NOTA: ".$order->get_customer_note()."." : "";

            $nota_pedido = sanitize_text_field($nota_pedido);

            if ( is_array( $datos_reserva ) ) {
                global $wpdb;

                $id_ciudad = $datos_reserva['id_ciudad'];
                $id_local = $datos_reserva['id_local'];
                $id_salas = $datos_reserva['id_salas'];
                $hora_reserva = $datos_reserva['hora'];
                $fecha_reserva = $datos_reserva['fecha_reserva'];
                $num_usuarios = $datos_reserva['num_usuarios'];


                $categoria_ciudad = get_term_by('id', $id_ciudad, 'product_cat');
                $nombre_ciudad = $categoria_ciudad->name;
            
                $skey = funcion_generar_key_reserva();

                /*$wpdb->query("INSERT INTO " .$wpdb->prefix. "reservas (
                        id_order, 
                        fecha_hora_creacion_reserva, 
                        id_ciudad, 
                        id_local, 
                        id_salas, 
                        id_juego, 
                        nombre_juego, 
                        nombre_ciudad,
                        id_usuario,
                        nombre_usuario, 
                        apellidos_usuario, 
                        email_usuario, 
                        telefono_usuario, 
                        num_usuarios,
                        jugadores,
                        fecha_reserva, 
                        hora_reserva,
                        observaciones_reserva,
                        cupon_descuento,
                        skey, 
                        estado
                    ) VALUES (".
                        $order_id.", '".
                        date("Y-m-d H:i:s")."', ".
                        $id_ciudad.", ".
                        $id_local.", '".
                        $id_salas."', ".
                        $id_juego.", '".
                        $item->get_name()."', '".
                        $nombre_ciudad."', ".
                        intval($customer_id). ", '".
                        $customer_firstname . "', '".
                        $customer_lastname ."', '".
                        $customer_email ."', '".
                        $customer_phone ."', '".
                        $num_usuarios."', '".
                        "[]"."', '".
                        $fecha_reserva."', '".
                        $hora_reserva."', '".
                        $nota_pedido."', '".
                        $cupon_descuento."', '".
                        $skey."', 
                        'pendiente'
                    )"
                );*/


                $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO {$wpdb->prefix}reservas (
                            id_order, 
                            fecha_hora_creacion_reserva, 
                            id_ciudad, 
                            id_local, 
                            id_salas, 
                            id_juego, 
                            nombre_juego, 
                            nombre_ciudad,
                            id_usuario,
                            nombre_usuario, 
                            apellidos_usuario, 
                            email_usuario, 
                            telefono_usuario, 
                            num_usuarios,
                            jugadores,
                            fecha_reserva, 
                            hora_reserva,
                            observaciones_reserva,
                            cupon_descuento,
                            skey, 
                            estado
                        ) VALUES ( %d, %s, %d, %d, %s, %d, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )",
                        $order_id,
                        date("Y-m-d H:i:s"),
                        $id_ciudad,
                        $id_local,
                        $id_salas,
                        $id_juego,
                        $item->get_name(),
                        $nombre_ciudad,
                        intval($customer_id),
                        $customer_firstname,
                        $customer_lastname,
                        $customer_email,
                        $customer_phone,
                        $num_usuarios,
                        json_encode([]), // Para representar "[]"
                        $fecha_reserva,
                        $hora_reserva,
                        $nota_pedido,
                        $cupon_descuento,
                        $skey,
                        'pendiente'
                    )
                );



                $id_reserva = $wpdb->insert_id;
                coco_log("Se ha registrado una reserva con ID ".$id_reserva);
                coco_log(print_r($datos_reserva,true));

                $datos_reserva = $item->get_meta('datos_reserva', true);
                $datos_reserva["id_reserva"] = $id_reserva;
                $item->update_meta_data('datos_reserva', $datos_reserva);
                $item->save();
            }
        }
        else{
            coco_log("Se ha intentado hacer un duplicado ".$reserva_ya_guardada->id_reserva);
            if($reserva_ya_guardada->id_usuario == intval($customer_id)){
                coco_log("El usuario ".$customer_email." ha intentado duplicar una reserva suya");
                $id_reserva = $reserva_ya_guardada->id_reserva;
                $datos_reserva = $item->get_meta('datos_reserva', true);
                $datos_reserva["id_reserva"] = $id_reserva;
                $item->update_meta_data('datos_reserva', $datos_reserva);
                $item->save();
                //HABRÍA QUE HACER UN UPDATE DE LA RESERVA PARA QUE TUVIERA EL ID ORDER CORRECTO
            } else{
                coco_log("El usuario ".$customer_email." ha intentado duplicar una reserva que no es suya");
                $error = true;
                break;
            }
        }
    }

    if($error){
        $order->update_status('Pedido cancelado: se ha intentado duplicar una reserva.');
        $response = array(
            'result' => 'failure',
            'messages' => '<ul class="woocommerce-error" role="alert"><li>¡Vaya! Parece que una reserva no está disponible... Por favor, elige otra hora.</li></ul>',
            'refresh' => false,
            'reload' => false,
        );
        echo json_encode($response);
        exit;
    }
}


add_action('woocommerce_order_status_processing', 'cambiar_estado_pedido_completado', 10, 1);
function cambiar_estado_pedido_completado($order_id){
    $order = wc_get_order($order_id);
    $estado_pedido = $order->get_status();
    if ($estado_pedido == 'processing') {
        $order->update_status('completed');
    }
}


add_action('woocommerce_order_status_completed', 'enviar_email_por_cada_reserva', 10, 1);//CAMBIAR A woocommerce_payment_complete Y COMPROBAR PARAMETROS CUANDO SEA EN REAL
function enviar_email_por_cada_reserva($order_id) {
    $order = wc_get_order($order_id);

    $customer_email = $order->get_billing_email();
    coco_log("Se va a intentar enviar los emails correspondientes al pedido ".$order_id);

    foreach ($order->get_items() as $item_key => $item) {
        $datos_reserva = $item->get_meta('datos_reserva', true);

        if ( is_array( $datos_reserva ) ) {
            global $wpdb;

            // $id_ciudad = $datos_reserva['id_ciudad'];
            // $id_local = $datos_reserva['id_local'];
            // $id_salas = $datos_reserva['id_salas'];
            // $hora_reserva = $datos_reserva['hora'];
            // $fecha_reserva = $datos_reserva['fecha_reserva'];
            // $num_usuarios = $datos_reserva['num_usuarios'];
                    
            // $sql = $wpdb->prepare("SELECT id_reserva, skey FROM " .$wpdb->prefix. "reservas WHERE id_order = ".$order_id." AND id_juego = ".$id_juego." AND fecha_reserva = '".$fecha_reserva."' AND hora_reserva = '".$hora_reserva."' AND id_ciudad = ".$id_ciudad." AND id_local = ".$id_local." AND id_salas = '".$id_salas."' AND num_usuarios = ".$num_usuarios);
            
            $sql = $wpdb->prepare("SELECT * FROM " .$wpdb->prefix. "reservas WHERE id_reserva = ".$datos_reserva["id_reserva"]);
            
            $reserva = $wpdb->get_row($sql);

            $id_ciudad = $reserva->id_ciudad;
            $id_local = $reserva->id_local;
            $id_salas = $reserva->id_salas;
            $id_juego = $reserva->id_juego;
            $hora_reserva = $reserva->hora_reserva;
            $fecha_reserva = $reserva->fecha_reserva;
            $num_usuarios = (int)$reserva->num_usuarios;
            $nombre_juego = $reserva->nombre_juego;

            $fecha_formateada = DateTime::createFromFormat('Y-m-d', $fecha_reserva)->format('d/m/Y');

            $categoria_ciudad = get_term_by('id', $id_ciudad, 'product_cat');
            $nombre_ciudad = $categoria_ciudad->name;

            $enlace_unirse = home_url() . "/unirse/?id=" . $reserva->id_reserva . "&key=" . $reserva->skey;
            $enlace_agregar_jugadores = home_url() . "/agregar-jugadores/?id=" . $reserva->id_reserva . "&key=" . $reserva->skey;
    
            $nombre_local = get_term_by('id', $id_local, 'product_cat')->name;

            $telefono_contacto_franquicia = get_term_meta($id_ciudad, 'telefono_contacto', true);
            $email_contacto_franquicia = get_term_meta($id_ciudad, 'email_contacto', true);
            $nombre_sl_franquicia = get_term_meta($id_ciudad, 'nombre_sl_franquicia', true);

            $telefono_contacto_local = get_term_meta($id_local, 'telefono_contacto', true);
            $email_contacto_local = get_term_meta($id_local, 'email_contacto', true);
            $direccion_local = get_term_meta($id_local, 'direccion', true);
            $enlace_local = get_term_meta($id_local, 'enlace_resenia_buena', true);

            $modo_kids = get_post_meta($id_juego, 'modo_kids', true);



            $texto_adicional_email_reserva = "";

            if(get_term_meta($id_ciudad, 'activar_texto_adicional_email_reserva', true))
                $texto_adicional_email_reserva = get_term_meta($id_ciudad, 'texto_adicional_email_reserva', true);


                
            $lista_precios_raw = get_post_meta($id_juego, 'lista_precios', true);
            $lista_precios = preg_replace("/\r?\n/", ",", trim($lista_precios_raw));
            $precios_array = explode(",", $lista_precios);
                
            $detalle_jugadores = "{$num_usuarios} jugadores";
            foreach ($precios_array as $precio_item) {
                $partes = explode(' ', trim($precio_item));
                $jugadores = (int)$partes[0];
        
                if ($jugadores === $num_usuarios) {
                    $detalle_jugadores = trim($precio_item);
                    break;
                }
            }





            $data = array(
                'Nombre' => $reserva->nombre_usuario,
                'Apellidos' => $reserva->apellidos_usuario,
                'Telefono' => $reserva->telefono_usuario,
                'Email' => $reserva->email_usuario,
                "Enlace_agregar_jugadores" => $enlace_agregar_jugadores,
                "Enlace_unirse" => $enlace_unirse,

                "ID_reserva" => $reserva->id_reserva,
                "Num_jugadores_reserva" => $num_usuarios,
                "Fecha_reserva" => $fecha_formateada,
                "Hora_reserva" => $hora_reserva,
                "Nombre_juego" => $nombre_juego,
                "Detalle_jugadores" => $detalle_jugadores,

                'Modo_kids' => $modo_kids,

                "Telefono_contacto_franquicia" => $telefono_contacto_franquicia,
                "Email_contacto_franquicia" => $email_contacto_franquicia,
                "Nombre_sl_franquicia" => $nombre_sl_franquicia,

                "Nombre_local" => $nombre_local,
                "Enlace_local" => $enlace_local,
                "Direccion_local" => $direccion_local,
                "Telefono_contacto_local" => $telefono_contacto_local,
                "Email_contacto_local" => $email_contacto_local,
                "Texto_adicional_email_reserva" => $texto_adicional_email_reserva,
            );

            $email_torrejon = $id_ciudad == 36 ? ",ruben@agenciasp.com" : "";

            do_action('send_mails_adpnsy_cllbck', $customer_email, 'Reserva confirmada: '.$nombre_juego, 'reserva_realizada', $data, array('Bcc: ruben@agenciasp.com'.$email_torrejon));

            coco_log("Email enviado al email ".$customer_email);
            coco_log(print_r($data, true));

        }
    }
}


add_action('wp_ajax_add_to_cart_reservar_horas', 'add_to_cart_reservar_horas');
add_action('wp_ajax_nopriv_add_to_cart_reservar_horas', 'add_to_cart_reservar_horas');

function add_to_cart_reservar_horas() {
    if (!class_exists('WooCommerce')) {
        echo json_encode(array('error' => 'WooCommerce no está activo.'));
        wp_die();
    }

    $ciudad = $_POST['ciudad'];
    $categoria_ciudad = get_term_by('slug', $ciudad, 'product_cat');
    $id_ciudad = $categoria_ciudad->term_id;

    $num_usuarios = $_POST['num_usuarios'];
    $fecha_reserva = $_POST['fecha_reserva'];

    $dateTime_fecha_reserva = DateTime::createFromFormat('Y-m-d', $fecha_reserva);
    if ($dateTime_fecha_reserva === false) {
        echo json_encode(array("error" => "¡Error! La fecha introducida es incorrecta"));
        wp_die();
    }
    $fecha_hoy = new DateTime('now');
    $diferencia = $fecha_hoy->diff($dateTime_fecha_reserva);
    if ($diferencia->invert == 1) {
        echo json_encode(array("error" => "¡Error! La fecha introducida no es válida"));
        wp_die();
    }


    $horas_seleccionadas = json_decode(stripslashes($_POST['horas_seleccionadas']));

    if(!$horas_seleccionadas){
        echo json_encode(array('error' => 'No has seleccionado ninguna hora.'));
        wp_die();
    }

    if ( !WC()->cart->is_empty() ) {
        $cart_items = WC()->cart->get_cart();
        if($id_ciudad != reset($cart_items)['id_ciudad']){
            coco_log("Un usuario ha intentado reservar en otra ciudad con una reserva en el carrito");
            echo json_encode(array('error' => '¡Cuidado! En el carrito tienes una reserva <b>en otra ciudad</b>. Por favor, revisa tu carrito.'));
            wp_die();
        }
    }

    foreach ($horas_seleccionadas as $reserva) {
        $id_juego = $reserva->id_juego;

        global $wpdb;
        $disponible = true;

        if($reserva->id_salas == ""){  //JUEGO NORMAL
            $sql = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "reservas WHERE 
            id_ciudad = %s AND 
            id_juego = %s AND 
            id_local = %s AND 
            fecha_reserva = %s AND 
            hora_reserva = %s", 
            
            $id_ciudad, 
            $id_juego, 
            $reserva->id_local, 
            $fecha_reserva, 
            $reserva->hora);

            $reserva_bd = $wpdb->get_row($sql);

            //COMPROBAR SI EXISTE NINGUNA RESERVA A ESA HORA
            if(!empty($reserva_bd)){
                if($reserva_bd->estado != "cancelada"){
                    coco_log("HORA NO DISPONIBLE (COD 1A)");
                    coco_log(print_r($reserva_bd,true));
                    $disponible = false;
                }
            }

        }
        else{ //JUEGO COMBATE O MODO SIMPLE CON SALAS
            $id_salas = explode(",",str_replace("\"","", $reserva->id_salas));

            if( count($id_salas) === 1){ //JUEGO CON UNA SALA
                //BUSCAR COINCIDENCIAS CON OTRO JUEGO DE UNA SALA
                $sql_A = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "reservas WHERE 
                id_ciudad = %s AND 
                id_juego = %s AND 
                id_local = %s AND 
                id_salas = %s AND 
                fecha_reserva = %s AND 
                hora_reserva = %s", 
                
                $id_ciudad, 
                $id_juego, 
                $reserva->id_local, 
                $reserva->id_salas, 
                $fecha_reserva, 
                $reserva->hora);

                $reserva_bd = $wpdb->get_row($sql_A);

                //COMPROBAR SI EXISTE NINGUNA RESERVA A ESA HORA
                if(!empty($reserva_bd)){
                    if($reserva_bd->estado != "cancelada"){
                        coco_log("HORA NO DISPONIBLE (COD 1B)");
                        coco_log(print_r($reserva_bd,true));
                        $disponible = false;
                    }
                }
                else{
                    //BUSCAR COINCIDENCIAS CON UN JUEGO DE MULTIPLES SALAS CON SALA EN COMÚN

                    $sql_B = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "reservas WHERE 
                    id_ciudad = %s AND 
                    id_local = %s AND 
                    (
                    id_salas LIKE '%,".$reserva->id_salas."' OR 
                    id_salas LIKE '".$reserva->id_salas.",%' OR 
                    id_salas LIKE '%,".$reserva->id_salas.",%'
                    ) AND
                    fecha_reserva = %s AND 
                    hora_reserva = %s", 
                    
                    $id_ciudad, 
                    $reserva->id_local, 
                    $fecha_reserva, 
                    $reserva->hora);

                    $reserva_bd = $wpdb->get_row($sql_B);

                    //COMPROBAR SI EXISTE NINGUNA RESERVA A ESA HORA
                    if(!empty($reserva_bd)){
                        if($reserva_bd->estado != "cancelada"){
                            coco_log("HORA NO DISPONIBLE (COD 1C)");
                            coco_log(print_r($reserva_bd,true));
                            $disponible = false;
                        }
                    }
                }
            }
            //JUEGOS DE VARIAS SALAS
            else{

                //EXACTAMENTE LAS MISMAS SALAS (MISMO JUEGO)
                $sql_B = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "reservas WHERE 
                id_ciudad = %s AND 
                id_juego = %s AND 
                id_local = %s AND 
                id_salas = %s AND 
                fecha_reserva = %s AND 
                hora_reserva = %s", 
                
                $id_ciudad, 
                $id_juego, 
                $reserva->id_local, 
                $reserva->id_salas, 
                $fecha_reserva, 
                $reserva->hora);

                $reserva_bd = $wpdb->get_row($sql_B);

                //COMPROBAR SI EXISTE NINGUNA RESERVA A ESA HORA
                if(!empty($reserva_bd)){
                    if($reserva_bd->estado != "cancelada"){
                        coco_log("HORA NO DISPONIBLE (COD 1D)");
                        coco_log(print_r($reserva_bd,true));
                        $disponible = false;
                    }
                }
                else{
                    //RECORRER CADA SALA

                    foreach ($id_salas as $id_sala){
                        //COMPROBAR CADA UNA DE LAS SALAS INDEPENDIENTES

                        $sql_A = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "reservas WHERE 
                        id_ciudad = %s AND 
                        id_local = %s AND 
                        id_salas = %s AND 
                        fecha_reserva = %s AND 
                        hora_reserva = %s", 
                        
                        $id_ciudad, 
                        $reserva->id_local, 
                        $id_sala, 
                        $fecha_reserva, 
                        $reserva->hora);

                        $reserva_bd = $wpdb->get_row($sql_A);

                        //COMPROBAR SI EXISTE NINGUNA RESERVA A ESA HORA
                        if(!empty($reserva_bd)){
                            if($reserva_bd->estado != "cancelada"){
                                coco_log("HORA NO DISPONIBLE (COD 1E)");
                                coco_log(print_r($reserva_bd,true));
                                $disponible = false;
                            }
                        }
                        else{
                            //COMPROBAR CADA UNA DE LAS SALAS DENTRO DE RESERVAS CON MÁS SALAS

                            $sql_B = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "reservas WHERE 
                            id_ciudad = %s AND 
                            id_local = %s AND 
                            (
                            id_salas LIKE '%,".$id_sala."' OR 
                            id_salas LIKE '".$id_sala.",%' OR 
                            id_salas LIKE '%,".$id_sala.",%'
                            ) AND
                            fecha_reserva = %s AND 
                            hora_reserva = %s", 
                            
                            $id_ciudad, 
                            $reserva->id_local, 
                            $fecha_reserva, 
                            $reserva->hora);

                            $reserva_bd = $wpdb->get_row($sql_B);

                            //COMPROBAR SI EXISTE NINGUNA RESERVA A ESA HORA
                            if(!empty($reserva_bd)){
                                if($reserva_bd->estado != "cancelada"){
                                    coco_log("HORA NO DISPONIBLE (COD 1F)");
                                    coco_log(print_r($reserva_bd,true));
                                    $disponible = false;
                                }
                            }
                        }
                    }
                }
            }
        }

        //COMPROBAR SI LA HORA ES VALIDA
        $horas = preg_replace("/\r?\n/", ",", trim(get_post_meta($id_juego, 'horario_juego', true)));
        $arrayHoras = explode(",", $horas);
        if(!in_array($reserva->hora, $arrayHoras)){
            coco_log("HORA NO DISPONIBLE (COD 2)");
            coco_log($reserva->hora);
            $disponible = false;
        }
        
        // !!! SI TENGO DOS PÁGINAS Y ELIJO DOS VECES EL MISMO, HAY QUE CONTROLAR QUE SOLO SE PUEDA RESERVAR UNA VEZ, Y QUE UNA RESERVA NO CHOQUE CON OTRA SI COMPARTEN SALA !!!
        $cart_items = WC()->cart->get_cart();
        foreach ($cart_items as $cart_item) {
            if($cart_item['fecha_reserva'] == $fecha_reserva
                && $cart_item['hora'] == $reserva->hora
                && $cart_item['id_ciudad'] == $id_ciudad
                && $cart_item['id_local'] == $reserva->id_local
                && $cart_item['id_salas'] == $reserva->id_salas){

                coco_log("Un usuario ha intentado inyectar una hora ya guardada en el carrito");
                


                /*  CARRITO REPETIDO ALERTA  */
                echo json_encode(array('error' => 'Estás seleccionando una hora que ya está en el carrito'));
                wp_die();
                /*  CARRITO REPETIDO OMITIDO  */
                // $disponible = false;
            }
        }
    
        if($disponible ){ 
            $cart_item_data = array(
                'id_ciudad' => $id_ciudad,
                'id_local' => $reserva->id_local,
                'id_salas' => $reserva->id_salas ? $reserva->id_salas : "",
                'hora' => $reserva->hora,
                'fecha_reserva' => $fecha_reserva,
                'num_usuarios' => $num_usuarios,
            );
            
            WC()->cart->add_to_cart($id_juego, 1, 0, array(), $cart_item_data);

            coco_log("Un usuario ha añadido una reserva al carrito");
            coco_log(print_r($cart_item_data,true));
        }
        else{
            coco_log("Un usuario ha intentado inyectar una hora inválida o no disponible");
            coco_log($reserva->hora);



            /*  CARRITO REPETIDO ALERTA  */
            echo json_encode(array('error' => 'Una hora seleccionada no está disponible ('.$reserva->hora.')'));
            wp_die();
            /*  CARRITO REPETIDO OMITIDO  */
            // $disponible = false;
        }
    }

    echo json_encode(array('success' => 'Productos añadidos al carrito.'));
    wp_die();
}


function limpiar_espacios_antes_de_guardar_horas_producto($value, $post_id, $field) {
    $value = preg_replace('/^\s+|\s+$/', '', $value); // Elimina espacios y saltos de línea al inicio y al final del texto completo
    $value = preg_replace('/\s+\n/', "\n", $value);   // Limpia espacios antes de saltos de línea dentro del texto
    return $value;
}

add_filter('acf/update_value/name=horario_juego', 'limpiar_espacios_antes_de_guardar_horas_producto', 10, 3);



// function mostrar_meta_carrito( $cart_item_data, $cart_item ) { 
//         $fecha_formateada = DateTime::createFromFormat('Y-m-d', $cart_item['fecha_reserva'])->format('d/m/Y');
//         $direccion_local = get_term_meta($cart_item['id_local'], 'direccion', true);

//         $detalles_reserva = "Día ". $fecha_formateada . " a las " .$cart_item['hora'] ."<br> Dirección: ". $direccion_local;
//         $cart_item_data[] = array(
//             'name' => '<b>Detalles</b>',
//             'value' =>  $detalles_reserva,
//         );

//     return $cart_item_data;
// }
// add_filter( 'woocommerce_get_item_data', 'mostrar_meta_carrito', 10, 2 );



function mostrar_meta_carrito( $cart_item_data, $cart_item ) { 
    $fecha_formateada = DateTime::createFromFormat('Y-m-d', $cart_item['fecha_reserva'])->format('d/m/Y');
    
    $direccion_local = get_term_meta($cart_item['id_local'], 'direccion', true);
    
    $product_id = $cart_item['product_id'];

    $lista_precios_raw = get_post_meta($product_id, 'lista_precios', true);
    $lista_precios = preg_replace("/\r?\n/", ",", trim($lista_precios_raw));
    $precios_array = explode(",", $lista_precios);

    $num_usuarios = (int)$cart_item['num_usuarios'];

    $detalle_jugadores = "{$num_usuarios} jugadores";
    foreach ($precios_array as $precio_item) {
        $partes = explode(' ', trim($precio_item));
        $jugadores = (int)$partes[0];

        if ($jugadores === $num_usuarios) {
            $detalle_jugadores = trim($precio_item);
            break;
        }
    }

    $detalles_reserva = "<div style='font-size: 15px;'>▷ Día <b>" . esc_html($fecha_formateada) . "</b> a las <b>" . esc_html($cart_item['hora']) . "</b><br>▷ Dirección: <b>" . esc_html($direccion_local) . "</b><br>▷ Participantes: <b>" . esc_html($detalle_jugadores) . "</b></div>";

    $cart_item_data[] = array(
        'name' => '<b>Detalles</b>',
        'value' => $detalles_reserva,
    );

    return $cart_item_data;
}
add_filter( 'woocommerce_get_item_data', 'mostrar_meta_carrito', 10, 2 );




add_action( 'woocommerce_checkout_create_order_line_item', 'guardar_meta_en_pedido', 10, 4 );
function guardar_meta_en_pedido( $item, $cart_item_key, $values, $order ) {

        $meta_data = array();
    if ( isset( $values['id_ciudad'] ) ) {
        $meta_data['id_ciudad'] = $values['id_ciudad'];
    }
    if ( isset( $values['id_local'] ) ) {
        $meta_data['id_local'] = $values['id_local'];
    }
    if ( isset( $values['id_salas'] ) ) {
        $meta_data['id_salas'] = $values['id_salas'];
    }
    

    if ( isset( $values['hora'] ) ) {
        $parts = explode(" - ", $values['hora']);
        if (count($parts) == 2) {
            $hora = $parts[0];
            $porcentaje = $parts[1]; 
            $meta_data['hora'] = $hora;
            $meta_data['descuento_hora'] = $porcentaje;
        }
        else{
            $meta_data['hora'] = $values['hora'];
        }
    }
    if ( isset( $values['fecha_reserva'] ) ) {
        $meta_data['fecha_reserva'] = $values['fecha_reserva'];
    }
    if ( isset( $values['num_usuarios'] ) ) {
        $meta_data['num_usuarios'] = $values['num_usuarios'];
    }
    // Guardar todo en un solo campo de meta_data
    if ( !empty( $meta_data ) ) {
        $item->add_meta_data( 'datos_reserva', $meta_data );
    }
}



add_action('woocommerce_before_order_itemmeta', 'mostrar_datos_reserva_en_pedido', 10, 3);
function mostrar_datos_reserva_en_pedido($item_id, $item, $product) {
    $datos_reserva = wc_get_order_item_meta( $item_id, 'datos_reserva', true );

    if ( is_array( $datos_reserva ) ) {
        echo "<div style='padding: 10px; border: 1px solid #ccc; margin-top: 10px;'>";
        echo "<b>Datos de la reserva original (para administración)</b><br>";
        echo "ID de la ciudad: " . $datos_reserva['id_ciudad'] . "<br>";
        echo "ID del local: " . $datos_reserva['id_local'] . "<br>";
        echo "ID de las salas: " . $datos_reserva['id_salas'] . "<br>";
        echo "Hora: " . $datos_reserva['hora'] . "<br>";
        echo (isset($datos_reserva["descuento_hora"]) && $datos_reserva["descuento_hora"]) ? "descuento_hora: " . $datos_reserva['descuento_hora'] . "<br>" : "";
        echo "Fecha: " . $datos_reserva['fecha_reserva'] . "<br>";
        echo "Número de usuarios: " . $datos_reserva['num_usuarios'];
        if(isset($datos_reserva['id_reserva'])){
            echo "<br>ID RESERVA: " . $datos_reserva['id_reserva'];
        }
        echo "</div>";
    }
}

add_action( 'woocommerce_admin_order_data_after_order_details', 'mostrar_datos_pedido_en_backend' );
function mostrar_datos_pedido_en_backend( $order ) {
    $salas_realizadas = get_post_meta( $order->get_id(), 'salas_realizadas');
    if(count($salas_realizadas) == 0){
        $salas_realizadas_texto = 'Nos estrenamos con esta!';
    }
    else{
        switch($salas_realizadas[0]){
            case 0:{
                $salas_realizadas_texto = 'Nos estrenamos con esta!';
                break;
            }
            case 2:{
                $salas_realizadas_texto = 'Un par, estamos verdes aun!';
                break;
            }
            case 5:{
                $salas_realizadas_texto = 'Entre 5 y 15 salas, opositando a escapistas!';
                break;
            }
            case 20:{
                $salas_realizadas_texto = 'Más de 20, tenemos carné escapista!';
                break;
            }
            case 50:{
                $salas_realizadas_texto = 'Más de 50, ya tenemos master escapista!';
                break;
            }
            default:{
                $salas_realizadas_texto = $salas_realizadas;
                break;
            }
        }
    }

    $_quiere_factura = "";
    $quiere_factura = get_post_meta( $order->get_id(), 'deseo_recibir_factura', true);
    if($quiere_factura === "true"){
        $_quiere_factura = '<p><strong>El cliente solicitó obtener factura</strong></p>';
    }

    echo '<div class="order_data_column">
        <br>
        <hr>
        <h4>Información Extra</h4>
        <p><strong>Salas realizadas</strong> <br>' . esc_html( $salas_realizadas_texto ) . '</p>
        '.$_quiere_factura.'
    </div>';
}

add_shortcode("deseo_recibir_factura_shortcode", "deseo_recibir_factura_callback");
function deseo_recibir_factura_callback($atts){
    $atts = shortcode_atts( [
        'order_id' => '' 
    ], $atts );


    if ( empty( $atts['order_id'] ) ) {
        return '';
    }


    $order = wc_get_order( $atts['order_id'] );
    if ( ! $order ) {
        return '';
    }

    $quiere_factura = get_post_meta( $order->get_id(), 'deseo_recibir_factura', true);
    $_quiere_factura = "";

    if($quiere_factura === "true"){
        $_quiere_factura = '<p><strong>El cliente solicitó obtener factura</strong></p>';
    }

    return $_quiere_factura;
}


function insertar_detalles_juegos_tabla_items( $item_id, $item, $order, $plain_text ) {
    $datos_reserva = wc_get_order_item_meta( $item_id, 'datos_reserva', true );
    if ( is_array( $datos_reserva ) ) {
        $fecha_formateada = DateTime::createFromFormat('Y-m-d', $datos_reserva['fecha_reserva'])->format('d/m/Y');
        $direccion_local = get_term_meta($datos_reserva['id_local'], 'direccion', true);

        // Obtener el ID del producto
        $product_id = $item->get_product_id();

        // Obtener y procesar la lista de precios
        $lista_precios_raw = get_post_meta($product_id, 'lista_precios', true);
        $lista_precios = preg_replace("/\r?\n/", ",", trim($lista_precios_raw));
        $precios_array = explode(",", $lista_precios);

        // Número de usuarios
        $num_usuarios = (int)$datos_reserva['num_usuarios'];

        $detalle_jugadores = "{$num_usuarios} jugadores"; // Valor predeterminado si no hay coincidencia
        foreach ($precios_array as $precio_item) {
            // Obtener el primer número en el item antes del primer espacio
            $partes = explode(' ', trim($precio_item));
            $jugadores = (int)$partes[0];

            // Comprobar si coincide con el número de usuarios deseado
            if ($jugadores === $num_usuarios) {
                $detalle_jugadores = trim($precio_item); // Utilizar el texto completo si coincide
                break;
            }
        }

        $detalles_reserva = "<div style='margin-left:24px'>
        <b>Día ". esc_html($fecha_formateada) . " a las " . esc_html($datos_reserva['hora']) . "</b><br>
        <b>Dirección: " . esc_html($direccion_local) . "</b><br>
        <b>Participantes: " . esc_html($detalle_jugadores) . "</b>
        </div>";
        
        echo $detalles_reserva;
    }
}
add_action('woocommerce_order_item_meta_end', 'insertar_detalles_juegos_tabla_items', 10, 4);







add_action('woocommerce_order_status_changed', 'update_custom_table_on_order_status_change', 10, 3);
function update_custom_table_on_order_status_change($order_id, $old_status, $new_status) {
    global $wpdb;

    $sql = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "reservas WHERE id_order = %d", $order_id);
    $reservas = $wpdb->get_results($sql);

    if ($reservas) {

        $nuevo_estado_reserva = '';
        switch ($new_status) {
            // case 'failed': //PARA QUE PASE A ESTADO PENDIENTE SI FALLA EL TPV
            case 'pending':
                $nuevo_estado_reserva = 'pendiente';
                break;
            case 'on-hold':
                $nuevo_estado_reserva = 'cancelada';
                break;
            case 'cancelled':
            case 'refunded':
                $nuevo_estado_reserva = 'cancelada';
                $wpdb->update(
                    $wpdb->prefix . 'reservas',
                    ['fecha_hora_cancelacion_reserva' => date("Y-m-d H:i:s") ],
                    ['id_order' => $order_id]
                );

                $reserva = $reservas[0];
                // if($reserva->id_usuario == 1){

                    $id_ciudad = $reserva->id_ciudad;

                    $order = wc_get_order($order_id);
                
                    $customer_firstname = $order->get_billing_first_name();
                    $customer_email = $order->get_billing_email();

                    coco_log("Una orden ha sido cancelada o rechazada. Se procede a enviar un email a ".$customer_email);

                    $telefono_contacto_franquicia = get_term_meta($id_ciudad, 'telefono_contacto', true);
                    $email_contacto_franquicia = get_term_meta($id_ciudad, 'email_contacto', true);
                    $nombre_sl_franquicia = get_term_meta($id_ciudad, 'nombre_sl_franquicia', true);

                    $data = array(
                        'Nombre' => $customer_firstname,
                        'Email' => $customer_email,
        
                        "Telefono_contacto_franquicia" => $telefono_contacto_franquicia,
                        "Email_contacto_franquicia" => $email_contacto_franquicia,
                        "Nombre_sl_franquicia" => $nombre_sl_franquicia,
                    );

                    coco_log(print_r($data,true));
        
                    do_action('send_mails_adpnsy_cllbck', $customer_email, 'Reserva cancelada', 'reserva_cancelada', $data);
                // }

                break;
            case 'completed':
                foreach ($reservas as $reserva) {
                    $fecha_hora_reserva = strtotime($reserva->fecha_reserva . ' ' . $reserva->hora_reserva);
                    $now = time();
                    $nuevo_estado_reserva = ($fecha_hora_reserva < $now) ? 'cerrada' : 'reservada';
                }
                break;
            case 'failed': //COMPROBAR LA ULTIMA NOTA. SI EN ESTA NOTA EXISTE EL MENSAJE, PASAR A COMPLETADO. SI NO, CANCELADO.
                $notes = wc_get_order_notes(array('order_id' => $order_id));
                $order = wc_get_order( $order_id );
                coco_log("¡Pedido con estado FAILED!");

                if (!empty($notes)) {
                    $first_note = reset($notes);
                    $note_content = $first_note->content;
        
                    if (strpos($note_content, 'Se ha producido un error validando el pedido, pero la respuesta recibida de Redsys es OK - 0000.') !== false) {
                        coco_log("El pedido ha sido completado de forma manual ".$order_id);
                        $order->update_status( 'completed', 'PEDIDO COMPLETADO DE FORMA MANUAL' );
                        break;
                    }

                }

                coco_log("El pedido ha sido cancelado de forma manual ".$order_id);
        
                $order->update_status( 'refunded', 'PEDIDO CANCELADO DE FORMA MANUAL' );
                break;
        }
        if ($nuevo_estado_reserva) {
            foreach ($reservas as $reserva) {
                $wpdb->update(
                    $wpdb->prefix . 'reservas',
                    ['estado' => $nuevo_estado_reserva],
                    ['id_reserva' => $reserva->id_reserva]
                );
            }
        }
    }
}







add_action('wp_ajax_cargar_reservas_calendario', 'cargar_reservas_calendario_callback');
add_action('wp_ajax_nopriv_cargar_reservas_calendario', 'cargar_reservas_calendario_callback');
function cargar_reservas_calendario_callback() {
    global $wpdb;
    $user_id = get_current_user_id();
    $ciudad_usuario = get_user_meta($user_id, 'ciudad_usuario', true);
    $user_data = get_userdata($user_id);
    $where_1 = "";
    $where_2 = "";
    // if(!in_array('administrator', $user_data->roles)){
        $where_1 = " id_ciudad = $ciudad_usuario AND ";
        $where_2 = "WHERE id_ciudad = $ciudad_usuario";
    // }
    $resultados = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "reservas WHERE $where_1 estado NOT IN ('cancelada')"); 
    $sql_cierres = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."cierres $where_2");
    $one_booking = [];
    $eventos = array();
    
    foreach ($resultados as $r => $resultado) {
        $pass = true;
        $check_bookings = [
            'id_juego' => $resultado->id_juego,
            'fecha_reserva' => $resultado->fecha_reserva,
            'hora_reserva' => $resultado->hora_reserva,
        ];
        // if(!empty($one_booking)){
        //     foreach($one_booking as $k => $v) if($one_booking[$k]['id_juego'] === $check_bookings['id_juego'] && $one_booking[$k]['fecha_reserva'] === $check_bookings['fecha_reserva'] && $one_booking[$k]['hora_reserva'] === $check_bookings['hora_reserva']) $pass = false;
        // }
        if($pass){
            $color_calendario = get_post_meta($resultado->id_juego, "color_juego_calendario", true);
            $one_booking[$r]['id_juego'] = $resultado->id_juego;
            $one_booking[$r]['fecha_reserva'] = $resultado->fecha_reserva;

            $parts = explode(':', $resultado->hora_reserva);
            $parts[0] = sprintf('%02d', $parts[0]);
            $horaFormateada = implode(':', $parts);
            $horaFormateada = str_replace(" ", "", $horaFormateada);

            $one_booking[$r]['hora_reserva'] = $horaFormateada;

            $one_booking[$r]['color_juego'] = get_post_meta($resultado->id_juego, "color_juego_calendario", true);
            $eventos[] = array(
                'classNames' => ['_booking-'.$resultado->id_reserva, 'fc_event_click'],
                'title' => $resultado->nombre_usuario . " " . $resultado->apellidos_usuario,
                'start' => $resultado->fecha_reserva . "T" . $horaFormateada,
                'color' => $color_calendario ? $color_calendario : "#fff",
            );
        }
    }
    if(!empty($sql_cierres)){
        foreach($sql_cierres as $c){
            $tipo_cierre = json_decode($c->tipo_cierre);
            $tipo_cierre->valores = json_decode($tipo_cierre->valores);
            $fecha = json_decode($c->fecha);
            $hora = json_decode($c->hora);
            $cierres_eventos = [];
            $cierres_eventos['classNames'] = ['cierre_cocoroom_calendar'];
            $cierres_eventos['allDay'] = false;
            $title_c_ = [
                'juego' => function($tipo_cierre){
                    $add_title = (isset($tipo_cierre->valores->id_salas)) ? ((strpos($tipo_cierre->valores->id_salas, ',')) ? str_replace(',', ' y ', $tipo_cierre->valores->id_salas) : $tipo_cierre->valores->id_salas) : '';
                    $add_title = ($add_title) ? " ($add_title)" : '';
                    $title = 'Cierre | '.get_the_title($tipo_cierre->valores->id_juego) . $add_title;
                    $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                    return ['title' => $title];
                },
                'local' => function($tipo_cierre){
                    $title = get_term($tipo_cierre->valores, 'product_cat');
                    if($title) return ['title' => 'Cierre | '.$title->name];                    
                },
            ];
            $hora_c_ = [
                'hora' => function($hora){},
                'intervalo' => function($hora){},
                'todo_el_dia' => function($hora){
                    return ['allDay' => true];
                },
            ];
            $fechas_obj_ = [
                'dias_semana' => function($fecha, $hora){
                    $tr = [];
                    $labels = [
                        'domingo' => 0,
                        'lunes' => 1,
                        'martes' => 2,
                        'miercoles' => 3,
                        'jueves' => 4,
                        'viernes' => 5,
                        'sabado' => 6,
                    ];
                    $days_week = [];
                    $values = (strpos($fecha->valores, ',')) ? explode(',', $fecha->valores) : $fecha->valores;
                    if(is_array($values)){
                        foreach($values as $vls) $days_week[] = $labels[$vls];
                    }else{
                        $days_week[] = $labels[$values];
                    }
                    $tr['daysOfWeek'] = $days_week;
                    if($hora->tipo == 'hora') $tr['startTime'] = $hora->valores;
                    return $tr;
                },
                'fecha' => function($fecha, $hora){
                    if($hora->tipo == 'hora'){
                        $time = 'T'.$hora->valores;
                        return ['start' => $fecha->valores.$time, 'end' => $fecha->valores.$time];
                    }else if($hora->tipo == 'intervalo'){
                        $valores = (strpos($hora->valores, ',')) ? explode(',', $hora->valores) : '';
                        if(is_array($valores)){
                            return ['start' => $fecha->valores.'T'.$valores[0], 'end' => $fecha->valores.'T'.$valores[1]];
                        }                    
                    }
                },
                'intervalo' => function($fecha, $hora){
                    $dates = explode(',', $fecha->valores);
                    if($hora->tipo == 'hora'){
                        $time = 'T'.$hora->valores;
                        return ['start' => $dates[0].$time, 'end' => $dates[1].$time];
                    }else if($hora->tipo == 'intervalo'){
                        $valores = (strpos($hora->valores, ',')) ? explode(',', $hora->valores) : '';
                        if(is_array($valores)){
                            return ['start' => $dates[0].'T'.$valores[0], 'end' => $dates[1].'T'.$valores[1]];
                        }
                    }
                },
            ];
            $tc = $title_c_[$tipo_cierre->tipo]($tipo_cierre);
            $cierres_eventos = array_merge($cierres_eventos, ($tc) ? $tc : []);
            $fc = $fechas_obj_[$fecha->tipo]($fecha, $hora);
            $cierres_eventos = array_merge($cierres_eventos, ($fc) ? $fc : []);
            $hc = $hora_c_[$hora->tipo]($hora);
            $cierres_eventos = array_merge($cierres_eventos, ($hc) ? $hc : []);
            $eventos[] = $cierres_eventos;
        }
    }
    echo json_encode($eventos);
    wp_die();
}







add_action('wp_ajax_cargar_reservas_calendario_2', 'cargar_reservas_calendario_2_callback');
add_action('wp_ajax_nopriv_cargar_reservas_calendario_2', 'cargar_reservas_calendario_2_callback');
function cargar_reservas_calendario_2_callback() {
    global $wpdb;

    $id_juego = 0;

    if (!empty($_POST["id_juego"])) {
        $id_juego = sanitize_text_field($_POST["id_juego"]);
        $product = wc_get_product($id_juego);
        if ($product) {
            $id_juego = $product->get_id();
        }
        else{ 
            $id_juego = 0; // volver a asignar cero
        }
    }

    if (!empty($_POST["id_gamemaster"])) {
        $id_gamemaster = sanitize_text_field($_POST["id_gamemaster"]);
        $gamemaster = get_userdata($id_gamemaster);

        if ($gamemaster) {
            $id_gamemaster = $gamemaster->ID;
        }
        else{ 
            $id_gamemaster = 0; // volver a asignar cero
        }
    }


    $user_id = get_current_user_id();
    $ciudad_usuario = get_user_meta($user_id, 'ciudad_usuario', true);
    $user_data = get_userdata($user_id);
    $where_1 = "";
    $where_2 = "";
    $where_3 = "";
    $where_4 = "";

    // if(!in_array('administrator', $user_data->roles)){
        $where_1 = " id_ciudad = $ciudad_usuario AND ";
        $where_2 = "WHERE id_ciudad = $ciudad_usuario";
    // }

    if($id_juego){
        $where_3 = " AND id_juego = ".$id_juego;
    }

    if($id_gamemaster){
        $where_4 = " AND (id_gamemaster LIKE '$id_gamemaster,%' OR id_gamemaster LIKE '%,$id_gamemaster' OR id_gamemaster LIKE '$id_gamemaster' ) ";
    }

    $start_date = sanitize_text_field($_POST["start"]);
    $end_date = sanitize_text_field($_POST["end"]);

    $resultados = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "reservas WHERE $where_1 estado NOT IN ('cancelada') $where_3 $where_4 AND fecha_reserva BETWEEN '$start_date' AND '$end_date'"); 

    if(!$id_juego && !$id_gamemaster){
        $sql_cierres = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."cierres $where_2");
    }
    else{
        $sql_cierres = null;
    }

    $one_booking = [];
    $eventos = array();
    
    foreach ($resultados as $r => $resultado) {
        $pass = true;
        $check_bookings = [
            'id_juego' => $resultado->id_juego,
            'fecha_reserva' => $resultado->fecha_reserva,
            'hora_reserva' => $resultado->hora_reserva,
        ];

        if($pass){
            $color_calendario = get_post_meta($resultado->id_juego, "color_juego_calendario", true);
            $one_booking[$r]['id_juego'] = $resultado->id_juego;
            $one_booking[$r]['fecha_reserva'] = $resultado->fecha_reserva;

            $parts = explode(':', $resultado->hora_reserva);
            $parts[0] = sprintf('%02d', $parts[0]);
            $horaFormateada = implode(':', $parts);
            $horaFormateada = str_replace(" ", "", $horaFormateada);

            $one_booking[$r]['hora_reserva'] = $horaFormateada;

            $one_booking[$r]['color_juego'] = get_post_meta($resultado->id_juego, "color_juego_calendario", true);
            $array_clases = ['_booking-'.$resultado->id_reserva, 'fc_event_click'];
            if($resultado->id_gamemaster){
                $array_clases[] = "underline"; 
            }
            $eventos[] = array(
                'classNames' => $array_clases,
                'title' => $resultado->nombre_usuario . " " . $resultado->apellidos_usuario,
                'start' => $resultado->fecha_reserva . "T" . $horaFormateada,
                'color' => $color_calendario ? $color_calendario : "#fff",
            );
        }
    }
    if(!empty($sql_cierres)){
        foreach($sql_cierres as $c){
            $tipo_cierre = json_decode($c->tipo_cierre);
            $tipo_cierre->valores = json_decode($tipo_cierre->valores);
            $fecha = json_decode($c->fecha);
            $hora = json_decode($c->hora);
            $cierres_eventos = [];
            $cierres_eventos['classNames'] = ['cierre_cocoroom_calendar'];
            $cierres_eventos['allDay'] = false;
            $title_c_ = [
                'juego' => function($tipo_cierre){
                    $add_title = (isset($tipo_cierre->valores->id_salas)) ? ((strpos($tipo_cierre->valores->id_salas, ',')) ? str_replace(',', ' y ', $tipo_cierre->valores->id_salas) : $tipo_cierre->valores->id_salas) : '';
                    $add_title = ($add_title) ? " ($add_title)" : '';
                    $title = 'Cierre | '.get_the_title($tipo_cierre->valores->id_juego) . $add_title;
                    $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                    return ['title' => $title];
                },
                'local' => function($tipo_cierre){
                    $title = get_term($tipo_cierre->valores, 'product_cat');
                    if($title) return ['title' => 'Cierre | '.$title->name];                    
                },
            ];
            $hora_c_ = [
                'hora' => function($hora){},
                'intervalo' => function($hora){},
                'todo_el_dia' => function($hora){
                    return ['allDay' => true];
                },
            ];
            $fechas_obj_ = [
                'dias_semana' => function($fecha, $hora){
                    $tr = [];
                    $labels = [
                        'domingo' => 0,
                        'lunes' => 1,
                        'martes' => 2,
                        'miercoles' => 3,
                        'jueves' => 4,
                        'viernes' => 5,
                        'sabado' => 6,
                    ];
                    $days_week = [];
                    $values = (strpos($fecha->valores, ',')) ? explode(',', $fecha->valores) : $fecha->valores;
                    if(is_array($values)){
                        foreach($values as $vls) $days_week[] = $labels[$vls];
                    }else{
                        $days_week[] = $labels[$values];
                    }
                    $tr['daysOfWeek'] = $days_week;
                    if($hora->tipo == 'hora') $tr['startTime'] = $hora->valores;
                    return $tr;
                },
                'fecha' => function($fecha, $hora){
                    if($hora->tipo == 'hora'){
                        $time = 'T'.$hora->valores;
                        return ['start' => $fecha->valores.$time, 'end' => $fecha->valores.$time];
                    }else if($hora->tipo == 'intervalo'){
                        $valores = (strpos($hora->valores, ',')) ? explode(',', $hora->valores) : '';
                        if(is_array($valores)){
                            return ['start' => $fecha->valores.'T'.$valores[0], 'end' => $fecha->valores.'T'.$valores[1]];
                        }                    
                    }
                },
                'intervalo' => function($fecha, $hora){
                    $dates = explode(',', $fecha->valores);
                    if($hora->tipo == 'hora'){
                        $time = 'T'.$hora->valores;
                        return ['start' => $dates[0].$time, 'end' => $dates[1].$time];
                    }else if($hora->tipo == 'intervalo'){
                        $valores = (strpos($hora->valores, ',')) ? explode(',', $hora->valores) : '';
                        if(is_array($valores)){
                            return ['start' => $dates[0].'T'.$valores[0], 'end' => $dates[1].'T'.$valores[1]];
                        }
                    }
                },
            ];
            $tc = $title_c_[$tipo_cierre->tipo]($tipo_cierre);
            $cierres_eventos = array_merge($cierres_eventos, ($tc) ? $tc : []);
            $fc = $fechas_obj_[$fecha->tipo]($fecha, $hora);
            $cierres_eventos = array_merge($cierres_eventos, ($fc) ? $fc : []);
            $hc = $hora_c_[$hora->tipo]($hora);
            $cierres_eventos = array_merge($cierres_eventos, ($hc) ? $hc : []);
            $eventos[] = $cierres_eventos;
        }
    }
    echo json_encode($eventos);
    wp_die();
}



add_action('wp_ajax_cargar_reservas_calendario_gamemaster', 'cargar_reservas_calendario_gamemaster_callback');
add_action('wp_ajax_nopriv_cargar_reservas_calendario_gamemaster', 'cargar_reservas_calendario_gamemaster_callback');
function cargar_reservas_calendario_gamemaster_callback() {
    global $wpdb;


    $user_id = get_current_user_id();
    $ciudad_usuario = get_user_meta($user_id, 'ciudad_usuario', true);
    $user_data = get_userdata($user_id);
    $where_1 = "";
    $where_2 = "";
    $where_3 = "";

    // if(!in_array('administrator', $user_data->roles)){
        $where_1 = " id_ciudad = $ciudad_usuario AND ";
        $where_2 = "WHERE id_ciudad = $ciudad_usuario";
    // }


    $start_date = sanitize_text_field($_POST["start"]);
    $end_date = sanitize_text_field($_POST["end"]);

    $resultados = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "reservas WHERE $where_1 estado NOT IN ('cancelada') $where_3 AND fecha_reserva BETWEEN '$start_date' AND '$end_date' AND (id_gamemaster LIKE '$user_id,%' OR id_gamemaster LIKE '%,$user_id' OR id_gamemaster LIKE '$user_id' )"); 

    $sql_cierres = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."cierres $where_2");

    $one_booking = [];
    $eventos = array();
    
    foreach ($resultados as $r => $resultado) {
        $pass = true;
        $check_bookings = [
            'fecha_reserva' => $resultado->fecha_reserva,
            'hora_reserva' => $resultado->hora_reserva,
        ];

        if($pass){
            $color_calendario = get_post_meta($resultado->id_juego, "color_juego_calendario", true);
            $one_booking[$r]['id_juego'] = $resultado->id_juego;
            $one_booking[$r]['fecha_reserva'] = $resultado->fecha_reserva;

            $parts = explode(':', $resultado->hora_reserva);
            $parts[0] = sprintf('%02d', $parts[0]);
            $horaFormateada = implode(':', $parts);
            $horaFormateada = str_replace(" ", "", $horaFormateada);

            $one_booking[$r]['hora_reserva'] = $horaFormateada;

            $one_booking[$r]['color_juego'] = get_post_meta($resultado->id_juego, "color_juego_calendario", true);
            $eventos[] = array(
                'classNames' => ['_booking-'.$resultado->id_reserva, 'fc_event_click'],
                'title' => $resultado->nombre_usuario . " " . $resultado->apellidos_usuario,
                'start' => $resultado->fecha_reserva . "T" . $horaFormateada,
                'color' => $color_calendario ? $color_calendario : "#fff",
            );
        }
    }
    if(!empty($sql_cierres)){
        foreach($sql_cierres as $c){
            $tipo_cierre = json_decode($c->tipo_cierre);
            $tipo_cierre->valores = json_decode($tipo_cierre->valores);
            $fecha = json_decode($c->fecha);
            $hora = json_decode($c->hora);
            $cierres_eventos = [];
            $cierres_eventos['classNames'] = ['cierre_cocoroom_calendar'];
            $cierres_eventos['allDay'] = false;
            $title_c_ = [
                'juego' => function($tipo_cierre){
                    $add_title = (isset($tipo_cierre->valores->id_salas)) ? ((strpos($tipo_cierre->valores->id_salas, ',')) ? str_replace(',', ' y ', $tipo_cierre->valores->id_salas) : $tipo_cierre->valores->id_salas) : '';
                    $add_title = ($add_title) ? " ($add_title)" : '';
                    $title = 'Cierre | '.get_the_title($tipo_cierre->valores->id_juego) . $add_title;
                    $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                    return ['title' => $title];
                },
                'local' => function($tipo_cierre){
                    $title = get_term($tipo_cierre->valores, 'product_cat');
                    if($title) return ['title' => 'Cierre | '.$title->name];                    
                },
            ];
            $hora_c_ = [
                'hora' => function($hora){},
                'intervalo' => function($hora){},
                'todo_el_dia' => function($hora){
                    return ['allDay' => true];
                },
            ];
            $fechas_obj_ = [
                'dias_semana' => function($fecha, $hora){
                    $tr = [];
                    $labels = [
                        'domingo' => 0,
                        'lunes' => 1,
                        'martes' => 2,
                        'miercoles' => 3,
                        'jueves' => 4,
                        'viernes' => 5,
                        'sabado' => 6,
                    ];
                    $days_week = [];
                    $values = (strpos($fecha->valores, ',')) ? explode(',', $fecha->valores) : $fecha->valores;
                    if(is_array($values)){
                        foreach($values as $vls) $days_week[] = $labels[$vls];
                    }else{
                        $days_week[] = $labels[$values];
                    }
                    $tr['daysOfWeek'] = $days_week;
                    if($hora->tipo == 'hora') $tr['startTime'] = $hora->valores;
                    return $tr;
                },
                'fecha' => function($fecha, $hora){
                    if($hora->tipo == 'hora'){
                        $time = 'T'.$hora->valores;
                        return ['start' => $fecha->valores.$time, 'end' => $fecha->valores.$time];
                    }else if($hora->tipo == 'intervalo'){
                        $valores = (strpos($hora->valores, ',')) ? explode(',', $hora->valores) : '';
                        if(is_array($valores)){
                            return ['start' => $fecha->valores.'T'.$valores[0], 'end' => $fecha->valores.'T'.$valores[1]];
                        }                    
                    }
                },
                'intervalo' => function($fecha, $hora){
                    $dates = explode(',', $fecha->valores);
                    if($hora->tipo == 'hora'){
                        $time = 'T'.$hora->valores;
                        return ['start' => $dates[0].$time, 'end' => $dates[1].$time];
                    }else if($hora->tipo == 'intervalo'){
                        $valores = (strpos($hora->valores, ',')) ? explode(',', $hora->valores) : '';
                        if(is_array($valores)){
                            return ['start' => $dates[0].'T'.$valores[0], 'end' => $dates[1].'T'.$valores[1]];
                        }
                    }
                },
            ];
            $tc = $title_c_[$tipo_cierre->tipo]($tipo_cierre);
            $cierres_eventos = array_merge($cierres_eventos, ($tc) ? $tc : []);
            $fc = $fechas_obj_[$fecha->tipo]($fecha, $hora);
            $cierres_eventos = array_merge($cierres_eventos, ($fc) ? $fc : []);
            $hc = $hora_c_[$hora->tipo]($hora);
            $cierres_eventos = array_merge($cierres_eventos, ($hc) ? $hc : []);
            $eventos[] = $cierres_eventos;
        }
    }
    echo json_encode($eventos);
    wp_die();
}

add_action('wp_ajax_cargar_reservas_calendario_gamemaster_bilbao', 'cargar_reservas_calendario_gamemaster_bilbao_callback');
add_action('wp_ajax_nopriv_cargar_reservas_calendario_gamemaster_bilbao', 'cargar_reservas_calendario_gamemaster_bilbao_callback');
function cargar_reservas_calendario_gamemaster_bilbao_callback() {
    global $wpdb;


    $user_id = get_current_user_id();
    $ciudad_usuario = get_user_meta($user_id, 'ciudad_usuario', true);
    $user_data = get_userdata($user_id);
    $where_1 = "";
    $where_2 = "";
    $where_3 = "";

        $where_1 = " id_ciudad = $ciudad_usuario AND (id_gamemaster LIKE '$user_id,%' OR id_gamemaster LIKE '%,$user_id' OR id_gamemaster LIKE '$user_id' ) AND ";
        $where_2 = "WHERE id_ciudad = $ciudad_usuario";


    $start_date = sanitize_text_field($_POST["start"]);
    $end_date = sanitize_text_field($_POST["end"]);

    $resultados = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "reservas WHERE $where_1 estado NOT IN ('cancelada') $where_3 AND fecha_reserva BETWEEN '$start_date' AND '$end_date' AND (id_gamemaster LIKE '$user_id,%' OR id_gamemaster LIKE '%,$user_id' OR id_gamemaster LIKE '$user_id' )"); 

    $sql_cierres = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."cierres $where_2");

    $one_booking = [];
    $eventos = array();
    
    foreach ($resultados as $r => $resultado) {
        $pass = true;
        $check_bookings = [
            'fecha_reserva' => $resultado->fecha_reserva,
            'hora_reserva' => $resultado->hora_reserva,
        ];

        if($pass){
            $color_calendario = get_post_meta($resultado->id_juego, "color_juego_calendario", true);
            $one_booking[$r]['id_juego'] = $resultado->id_juego;
            $one_booking[$r]['fecha_reserva'] = $resultado->fecha_reserva;

            $parts = explode(':', $resultado->hora_reserva);
            $parts[0] = sprintf('%02d', $parts[0]);
            $horaFormateada = implode(':', $parts);
            $horaFormateada = str_replace(" ", "", $horaFormateada);

            $one_booking[$r]['hora_reserva'] = $horaFormateada;

            $one_booking[$r]['color_juego'] = get_post_meta($resultado->id_juego, "color_juego_calendario", true);
            $eventos[] = array(
                'classNames' => ['_booking-'.$resultado->id_reserva, 'fc_event_click'],
                'title' => $resultado->nombre_usuario . " " . $resultado->apellidos_usuario,
                'start' => $resultado->fecha_reserva . "T" . $horaFormateada,
                'color' => $color_calendario ? $color_calendario : "#fff",
            );
        }
    }
    if(!empty($sql_cierres)){
        foreach($sql_cierres as $c){
            $tipo_cierre = json_decode($c->tipo_cierre);
            $tipo_cierre->valores = json_decode($tipo_cierre->valores);
            $fecha = json_decode($c->fecha);
            $hora = json_decode($c->hora);
            $cierres_eventos = [];
            $cierres_eventos['classNames'] = ['cierre_cocoroom_calendar'];
            $cierres_eventos['allDay'] = false;
            $title_c_ = [
                'juego' => function($tipo_cierre){
                    $add_title = (isset($tipo_cierre->valores->id_salas)) ? ((strpos($tipo_cierre->valores->id_salas, ',')) ? str_replace(',', ' y ', $tipo_cierre->valores->id_salas) : $tipo_cierre->valores->id_salas) : '';
                    $add_title = ($add_title) ? " ($add_title)" : '';
                    $title = 'Cierre | '.get_the_title($tipo_cierre->valores->id_juego) . $add_title;
                    $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                    return ['title' => $title];
                },
                'local' => function($tipo_cierre){
                    $title = get_term($tipo_cierre->valores, 'product_cat');
                    if($title) return ['title' => 'Cierre | '.$title->name];                    
                },
            ];
            $hora_c_ = [
                'hora' => function($hora){},
                'intervalo' => function($hora){},
                'todo_el_dia' => function($hora){
                    return ['allDay' => true];
                },
            ];
            $fechas_obj_ = [
                'dias_semana' => function($fecha, $hora){
                    $tr = [];
                    $labels = [
                        'domingo' => 0,
                        'lunes' => 1,
                        'martes' => 2,
                        'miercoles' => 3,
                        'jueves' => 4,
                        'viernes' => 5,
                        'sabado' => 6,
                    ];
                    $days_week = [];
                    $values = (strpos($fecha->valores, ',')) ? explode(',', $fecha->valores) : $fecha->valores;
                    if(is_array($values)){
                        foreach($values as $vls) $days_week[] = $labels[$vls];
                    }else{
                        $days_week[] = $labels[$values];
                    }
                    $tr['daysOfWeek'] = $days_week;
                    if($hora->tipo == 'hora') $tr['startTime'] = $hora->valores;
                    return $tr;
                },
                'fecha' => function($fecha, $hora){
                    if($hora->tipo == 'hora'){
                        $time = 'T'.$hora->valores;
                        return ['start' => $fecha->valores.$time, 'end' => $fecha->valores.$time];
                    }else if($hora->tipo == 'intervalo'){
                        $valores = (strpos($hora->valores, ',')) ? explode(',', $hora->valores) : '';
                        if(is_array($valores)){
                            return ['start' => $fecha->valores.'T'.$valores[0], 'end' => $fecha->valores.'T'.$valores[1]];
                        }                    
                    }
                },
                'intervalo' => function($fecha, $hora){
                    $dates = explode(',', $fecha->valores);
                    if($hora->tipo == 'hora'){
                        $time = 'T'.$hora->valores;
                        return ['start' => $dates[0].$time, 'end' => $dates[1].$time];
                    }else if($hora->tipo == 'intervalo'){
                        $valores = (strpos($hora->valores, ',')) ? explode(',', $hora->valores) : '';
                        if(is_array($valores)){
                            return ['start' => $dates[0].'T'.$valores[0], 'end' => $dates[1].'T'.$valores[1]];
                        }
                    }
                },
            ];
            $tc = $title_c_[$tipo_cierre->tipo]($tipo_cierre);
            $cierres_eventos = array_merge($cierres_eventos, ($tc) ? $tc : []);
            $fc = $fechas_obj_[$fecha->tipo]($fecha, $hora);
            $cierres_eventos = array_merge($cierres_eventos, ($fc) ? $fc : []);
            $hc = $hora_c_[$hora->tipo]($hora);
            $cierres_eventos = array_merge($cierres_eventos, ($hc) ? $hc : []);
            $eventos[] = $cierres_eventos;
        }
    }
    echo json_encode($eventos);
    wp_die();
}

add_action('wp_ajax_obtener_informacion_reservas_calendario_descargar', 'obtener_informacion_reservas_calendario_descargar');
add_action('wp_ajax_nopriv_obtener_informacion_reservas_calendario_descargar', 'obtener_informacion_reservas_calendario_descargar');

function obtener_informacion_reservas_calendario_descargar() {
    global $wpdb;

    $ids = explode(',', $_POST['ids']);
    $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
    $table_reservas = $wpdb->prefix . "reservas";

    $query = $wpdb->prepare(
        "SELECT id_reserva, fecha_reserva, hora_reserva, id_salas, nombre_juego, nombre_usuario, telefono_usuario, estado, id_local, observaciones_reserva, observaciones_partida, metodo_pago, importe_fianza, importe_pago, promocion_utilizada, num_usuarios_reales, num_usuarios, cupon_descuento, puntuacion_partida, id_gamemaster, como_nos_han_conocido, aceptan_hacerse_foto, fotografia, enviar_email_resenia, nombre_usuario, apellidos_usuario, email_usuario, telefono_usuario, skey 
         FROM $table_reservas 
         WHERE id_reserva IN ($ids_placeholder)",
        array_merge($ids)
    );

    $resultados = $wpdb->get_results($query, ARRAY_A);

    $reservas = array();

    foreach ($resultados as $reserva) {
        $id_gamemaster1 = "";
        $id_gamemaster2 = "";
        // if ($reserva['estado'] === "cerrada") {
        if($reserva['id_gamemaster']){
            $gamemaster_ids = explode(",", $reserva['id_gamemaster']);
            if (count($gamemaster_ids) > 1) {
                $gamemaster1_info = get_userdata($gamemaster_ids[0]);
                $gamemaster2_info = get_userdata($gamemaster_ids[1]);

                $id_gamemaster1 = $gamemaster1_info ? $gamemaster1_info->user_email : "Usuario eliminado (ID " . $gamemaster_ids[0] . ")";
                $id_gamemaster2 = $gamemaster2_info ? $gamemaster2_info->user_email : "Usuario eliminado (ID " . $gamemaster_ids[1] . ")";
            } else {
                $gamemaster_info = get_userdata($gamemaster_ids[0]);
                $id_gamemaster1 = $gamemaster_info ? $gamemaster_info->user_email : "Usuario eliminado (ID " . $gamemaster_ids[0] . ")";
            }
        }
        // }

        $local_name = get_term_by('id', $reserva['id_local'], 'product_cat')->name;

        $nombres_salas="";

        // if($reserva["id_salas"] != ""){
        //     $id_salas = explode(",", $reserva["id_salas"]);
        //     foreach($id_salas as $id_sala){
        //         $categoria_sala = get_term_by('id', $id_sala, 'product_cat');
        //         $nombres_salas .= $categoria_sala->name. ", ";
        //     }

        //     $nombres_salas = " (".substr($nombres_salas,0,-2).")";
        // }

        $reservas[] = array(
            'id_reserva' => $reserva['id_reserva'],
            'fecha_hora' => str_replace("-", "/", $reserva['fecha_reserva']) . " " . $reserva['hora_reserva'],
            'juego_sala' => $reserva['nombre_juego'] . " " . $nombres_salas,
            'local' => $local_name,
            'usuario' => $reserva['nombre_usuario'] . " (" . $reserva['telefono_usuario'] . ")",
            'observaciones_reserva' => str_replace(";", " - ", $reserva['observaciones_reserva']),
            'estado' => $reserva['estado'],
            'observaciones_partida' => $reserva['estado'] === "cerrada" ? str_replace(";", " - ", $reserva['observaciones_partida']) : "",
            'promocion_utilizada' => $reserva['estado'] === "cerrada" ? $reserva['promocion_utilizada'] : "",
            'nombre_usuario' => $reserva['nombre_usuario'],
            'apellidos_usuario' => $reserva['apellidos_usuario'],
            'email_usuario' => $reserva['email_usuario'],
            'telefono_usuario' => $reserva['telefono_usuario'],
            'cupon_descuento' => $reserva['cupon_descuento'],
            'puntuacion_partida' => $reserva['estado'] === "cerrada" ? $reserva['puntuacion_partida'] : "",
            'gamemaster_1' => $id_gamemaster1,
            'gamemaster_2' => $id_gamemaster2,
            'como_nos_han_conocido' => $reserva['estado'] === "cerrada" ? $reserva['como_nos_han_conocido'] : "",
            'aceptan_hacerse_foto' => $reserva['estado'] === "cerrada" ? $reserva['aceptan_hacerse_foto'] : "",
            'enlace_fotografia' => ($reserva['estado'] === "cerrada" && $reserva['fotografia'] != "") ? home_url() . $reserva['fotografia'] : "",
            'enviar_reseña' => $reserva['estado'] === "cerrada" ? $reserva['enviar_email_resenia'] : "",
            'importe_fianza' => $reserva['estado'] === "cerrada" ? $reserva['importe_fianza'] : "",
            'importe_pago' => $reserva['estado'] === "cerrada" ? $reserva['importe_pago'] : "",
            'num_usuarios' => $reserva['num_usuarios'],
            'num_usuarios_reales' => $reserva['estado'] === "cerrada" ? $reserva['num_usuarios_reales'] : "",
            'metodo_pago' => $reserva['estado'] === "cerrada" ? $reserva['metodo_pago'] : "",
            'skey' => $reserva['skey']
        );
    }

    echo json_encode($reservas);
    wp_die();
}
























add_action('wp_ajax_obtener_datos_segun_tipo', 'obtener_datos_segun_tipo');
add_action('wp_ajax_nopriv_obtener_datos_segun_tipo', 'obtener_datos_segun_tipo');
function obtener_datos_segun_tipo() {
    $tipo_cierre = $_POST['tipo_cierre'];
    $id_usuario = get_current_user_id();  
    $ciudad_usuario = get_user_meta($id_usuario, 'ciudad_usuario', true);  

    if ($tipo_cierre === 'local') {
        $args = array(
            'parent' => $ciudad_usuario,
            'hide_empty' => false
        );
        $categorias_hijas = get_terms('product_cat', $args);
        $categorias_data = array();
        foreach($categorias_hijas as $categoria) {
            $categorias_data[] = array(
                'nombre' => $categoria->name,
                'datos'   => $categoria->term_id
            );
        }
        echo json_encode($categorias_data);

    } elseif ($tipo_cierre === 'juego') {
        $query = new WP_Query(array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'id',
                    'terms' => $ciudad_usuario,
                )
            ),
        ));
    
        if (!$query->have_posts()){
            echo json_encode(array("error" => "¡No hay juegos disponibles!<br>Revisa los datos introducidos"));
        }
        else{
            $lista_juegos = array();
            while ($query->have_posts()) : $query->the_post();
    
                $local = obtener_ubicacion_juego(get_the_ID(),1);
                $salas = obtener_ubicacion_juego(get_the_ID(),2);
    
                $modo_combate = get_post_meta(get_the_ID(), 'modo_combate', true);
                
                $id_salas="";
                
                foreach($salas as $sala){
                    $id_salas .= $sala['id']. ",";
                }

                $id_salas = substr($id_salas,0,-1);

                //SI ES UN JUEGO SIMPLE pero con variedad combate
                if ($salas && (is_null($modo_combate) || !$modo_combate)) {
                    foreach($salas as $sala){
                        $lista_juegos[] = array(
                            'nombre' => get_the_title(), // . " - " . $sala['nombre']
                            'datos' => json_encode(array(
                                'id_juego'   => get_the_ID(),
                                'id_salas' => strval($sala['id'])
                                ),
                            )
                        );
                    }
                }
                else{
                    $lista_juegos[] = array(
                        'nombre' => get_the_title(),
                        'datos' => json_encode(array(
                            'id_juego'   => get_the_ID(),
                            'id_salas' => $id_salas!=false ? $id_salas : ""
                            ),
                        )
                    );
                }
    
                
            endwhile;
    
            wp_reset_query();
    
            echo json_encode($lista_juegos);
        }
    }
    

    wp_die();
}








add_action('wp_ajax_obtener_datos_segun_tipo_cierres_panel', 'obtener_datos_segun_tipo_cierres_panel');
add_action('wp_ajax_nopriv_obtener_datos_segun_tipo_cierres_panel', 'obtener_datos_segun_tipo_cierres_panel');
function obtener_datos_segun_tipo_cierres_panel() {
    $tipo_cierre = $_POST['tipo_cierre'];
    $id_usuario = get_current_user_id();  
    $ciudad_usuario = get_user_meta($id_usuario, 'ciudad_usuario', true);  

    if ($tipo_cierre === 'local') {
        $args = array(
            'parent' => $ciudad_usuario,
            'hide_empty' => false
        );
        $categorias_hijas = get_terms('product_cat', $args);
        $categorias_data = array();
        foreach($categorias_hijas as $categoria) {
            $categorias_data[] = array(
                'nombre' => $categoria->name,
                'datos'   => $categoria->term_id
            );
        }
        echo json_encode($categorias_data);

    } elseif ($tipo_cierre === 'juego') {
        $query = new WP_Query(array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'id',
                    'terms' => $ciudad_usuario,
                )
            ),
        ));
    
        if (!$query->have_posts()){
            echo json_encode(array("error" => "¡No hay juegos disponibles!<br>Revisa los datos introducidos"));
        }
        else{
            $lista_juegos = array();
            while ($query->have_posts()) : $query->the_post();
    
                $local = obtener_ubicacion_juego(get_the_ID(),1);
                $salas = obtener_ubicacion_juego(get_the_ID(),2);
    
                $modo_combate = get_post_meta(get_the_ID(), 'modo_combate', true);
                
                $id_salas="";
                
                foreach($salas as $sala){
                    $id_salas .= $sala['id']. ",";
                }

                $id_salas = substr($id_salas,0,-1);

                //SI ES UN JUEGO SIMPLE pero con variedad combate
                if ($salas && (is_null($modo_combate) || !$modo_combate)) {
                    foreach($salas as $sala){
                        $lista_juegos[] = array(
                            'nombre' => get_the_title(), // . " - " . $sala['nombre']
                            'datos' => json_encode(array(
                                'id_juego'   => get_the_ID(),
                                'id_salas' => strval($sala['id'])
                                ),
                            )
                        );
                    }
                }
                else{
                    $lista_juegos[] = array(
                        'nombre' => get_the_title(),
                        'datos' => json_encode(array(
                            'id_juego'   => get_the_ID(),
                            'id_salas' => $id_salas!=false ? $id_salas : ""
                            ),
                        )
                    );
                }
    
                
            endwhile;
    
            wp_reset_query();
    
            echo json_encode($lista_juegos);
        }
    }
    else
        echo json_encode(array("error" => "Ha habido un error con en el sistema"));

    

    wp_die();
}



add_action('wp_ajax_obtener_juegos_panel_reserva', 'obtener_juegos_panel_reserva');
add_action('wp_ajax_nopriv_obtener_juegos_panel_reserva', 'obtener_juegos_panel_reserva');
function obtener_juegos_panel_reserva() {
    $id_usuario = get_current_user_id();  

    $ciudad_usuario = get_user_meta($id_usuario, 'ciudad_usuario', true);  
    $num_usuarios = $_POST['num_usuarios'];

    if($num_usuarios == 1) $num_usuarios = 2;

    $query = new WP_Query(array(
        'post_type' => 'product',
        'post_status' => array('publish', 'private'),
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'id',
                'terms' => $ciudad_usuario,
            )
        ),
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'numero_minimo_jugadores',
                'value' => $num_usuarios,
                'compare' => '<=',
                'type' => 'NUMERIC'
            ),
            array(
                'key' => 'numero_maximo_jugadores',
                'value' => $num_usuarios,
                'compare' => '>=',
                'type' => 'NUMERIC'
            )
        )
    ));


    
    if (!$query->have_posts()){
        echo json_encode(array("error" => "¡No hay juegos disponibles! Revisa los datos introducidos"));
    }
    else{
        $lista_juegos = array();
        while ($query->have_posts()) : $query->the_post();

            $local = obtener_ubicacion_juego(get_the_ID(),1);
            $salas = obtener_ubicacion_juego(get_the_ID(),2);

            $modo_combate = get_post_meta(get_the_ID(), 'modo_combate', true);
            
            $id_salas="";
            
            foreach($salas as $sala){
                $id_salas .= $sala['id']. ",";
            }

            $id_salas = substr($id_salas,0,-1);

            //SI ES UN JUEGO SIMPLE pero con variedad combate
            if ($salas && (is_null($modo_combate) || !$modo_combate)) {
                foreach($salas as $sala){
                    $lista_juegos[] = array(
                        'nombre' => get_the_title(), // . " - " . $sala['nombre']
                        'datos' => json_encode(array(
                            'id_juego'   => get_the_ID(),
                            'id_salas' => strval($sala['id'])
                            ),
                        )
                    );
                }
            }
            else{
                $lista_juegos[] = array(
                    'nombre' => get_the_title(),
                    'datos' => json_encode(array(
                        'id_juego'   => get_the_ID(),
                        'id_salas' => $id_salas!=false ? $id_salas : ""
                        ),
                    )
                );
            }

            
        endwhile;

        wp_reset_query();

        echo json_encode($lista_juegos);
    }
    
    wp_die();
}


add_action('wp_ajax_obtener_horas_disponibles_juego', 'obtener_horas_disponibles_juego');
add_action('wp_ajax_nopriv_obtener_horas_disponibles_juego', 'obtener_horas_disponibles_juego');
function obtener_horas_disponibles_juego() {
    $lista_horas_disponibles = "";
    $id_usuario = get_current_user_id();  
    $id_ciudad = get_user_meta(get_current_user_id(), 'ciudad_usuario', true);
    $datos_juego = json_decode(stripslashes($_POST["datos_juego"]));  
    $fecha_reserva = $_POST["fecha_reserva"];

    $id_juego = $datos_juego->id_juego;
    $local_juego = obtener_ubicacion_juego($id_juego,1);
    
    $horas = preg_replace("/\r?\n/", ",", trim(get_post_meta($datos_juego->id_juego, 'horario_juego', true)));
    $salas = $datos_juego->id_salas;

    if (!$horas){
        echo "error";
    }
    else{
        $lista_horas = array();
        $modo_combate = get_post_meta($id_juego, 'modo_combate', true);
    
        global $wpdb;
    
        $sql = "
            SELECT * 
            FROM " . $wpdb->prefix . "cierres
            WHERE `id_ciudad` = ".$id_ciudad;
        $cierres = $wpdb->get_results($sql);

        $sql = "
            SELECT *  
            FROM " . $wpdb->prefix . "reservas
            WHERE `id_ciudad` = ".$id_ciudad." 
            AND `fecha_reserva` = '".$fecha_reserva."'
            AND `estado` NOT LIKE 'cancelada' ";
        $reservas = $wpdb->get_results($sql);

        $horas = explode(",", $horas);

        foreach($cierres as $cierre){
            $cierre->tipo_cierre = json_decode($cierre->tipo_cierre);
            $cierre->fecha = json_decode($cierre->fecha);
            $cierre->hora = json_decode($cierre->hora);
            if($cierre->tipo_cierre->tipo == "juego"){
                $cierre->tipo_cierre->valores = json_decode($cierre->tipo_cierre->valores);
            }
        }

        foreach($horas as $hora){

            $hora_tiene_descuento = false;
            
            if(strpos($hora, " - ") !== false){
                $hora_tiene_descuento = true;
                $hora_array = explode(" - ", $hora);
                $hora = $hora_array[0];
                $hora_porcentaje = $hora_array[1];
            }

            $deshabilitado = false;

            foreach($cierres as $cierre){

                if (is_object($cierre->tipo_cierre->valores) && isset($cierre->tipo_cierre->valores->id_salas)) {
                    $salas_cierre = $cierre->tipo_cierre->valores->id_salas;
                } else {
                    $salas_cierre = '';
                }

                $interseccion = array_intersect(
                    array_filter(explode(",", str_replace("\"", "", $salas_cierre)), 'strlen'),
                    array_filter(explode(",", str_replace("\"", "", $salas)), 'strlen')
                );

                // $interseccion = array_intersect(
                //     array_filter(explode(",", str_replace("\"", "", $cierre->tipo_cierre->valores->id_salas)), 'strlen'),
                //     array_filter(explode(",", str_replace("\"", "", $salas)), 'strlen')
                // );

                if(
                    ($cierre->tipo_cierre->tipo=="local" && $cierre->tipo_cierre->valores == $local_juego["id"])
                ||
                    (!empty($interseccion))
                
                    // ($cierre->tipo_cierre->tipo=="juego" && !empty( array_intersect(
                    //     explode(",",str_replace("\"","", $cierre->tipo_cierre->valores->id_salas)),
                    //     explode(",",str_replace("\"","", $salas)))))
                ){
                    if($cierre->fecha->tipo == "fecha"){
                        if($cierre->fecha->valores == $fecha_reserva){
                            $deshabilitado = comparar_hora_cierre_con_hora($cierre->hora, $hora);
                        }
                    }else
                    if($cierre->fecha->tipo == "intervalo"){
                        $fechas_intervalo = explode(",", $cierre->fecha->valores);
                        if ($fecha_reserva >= $fechas_intervalo[0] && $fecha_reserva <= $fechas_intervalo[1]) {
                            $deshabilitado = comparar_hora_cierre_con_hora($cierre->hora, $hora);
                        }
                    }
                    else
                    if($cierre->fecha->tipo == "dias_semana"){
                        $fecha_reserva_cast = new DateTime($fecha_reserva);
                        $fecha_reserva_dia_semana = $fecha_reserva_cast->format('w');
                        $dias_semana_cierre = explode(",", $cierre->fecha->valores);
            
                        switch($fecha_reserva_dia_semana) {
                            case 0:
                                $fecha_reserva_dia_semana = "domingo";
                                break;
                            case 1:
                                $fecha_reserva_dia_semana = "lunes";
                                break;
                            case 2:
                                $fecha_reserva_dia_semana = "martes";
                                break;
                            case 3:
                                $fecha_reserva_dia_semana = "miercoles";
                                break;
                            case 4:
                                $fecha_reserva_dia_semana = "jueves";
                                break;
                            case 5:
                                $fecha_reserva_dia_semana = "viernes";
                                break;
                            case 6:
                                $fecha_reserva_dia_semana = "sabado";
                                break;
                            default:
                                $fecha_reserva_dia_semana = "invalido";
                                break;
                        }
                        if (in_array($fecha_reserva_dia_semana, $dias_semana_cierre)) {
                            $deshabilitado = comparar_hora_cierre_con_hora($cierre->hora, $hora);
                        }
                    }
                }
                if($deshabilitado) break;
            }


            if(!$deshabilitado)
                foreach($reservas as $reserva){
                    if(
                        (!empty(array_intersect(explode(",",str_replace("\"","", $reserva->id_salas)),explode(",",str_replace("\"","", $salas))))
                        &&
                        ($reserva->id_salas != "")
                        &&
                        ($salas != "")
                        )
                        &&
                        strtotime($reserva->hora_reserva) == strtotime($hora)
                    ||
                        ($reserva->id_salas == ""
                        &&
                        $salas == ""
                        &&
                        $reserva->id_juego == $id_juego
                        &&
                        strtotime($reserva->hora_reserva) == strtotime($hora))
                    ){
                        if($reserva->id_reserva != $_POST["id_reserva"]){
                            $deshabilitado = true;    //FECHA HABILITADA SI SE TRATA DE LA MISMA RESERVA
                        }
                    }

                if( !empty( array_intersect( explode(",",str_replace("\"","", $reserva->id_salas)), explode(",",str_replace("\"","", $salas)) ) ) ){

                }
                if( $reserva->id_salas == "" 
                    &&
                    empty(explode(",",str_replace("\"","", $salas)))
                    &&
                    $reserva->id_juego == $id_juego ){
                }
            }

            if(!$deshabilitado){

                if($hora_tiene_descuento){
                    $lista_horas_disponibles.=  $hora." - ".$hora_porcentaje.",";
                }
                else{
                    $lista_horas_disponibles.=  $hora.",";
                }
            }
        }

        if($lista_horas_disponibles)
            echo substr($lista_horas_disponibles,0,-1);
        else
            echo "error";
    }
    wp_die();
}




add_action('wp_ajax_eliminar_usuario_partida', 'eliminar_usuario_partida');
add_action('wp_ajax_nopriv_eliminar_usuario_partida', 'eliminar_usuario_partida');
function eliminar_usuario_partida() {
    if(isset($_POST["id"]) && isset($_POST["key"]) && isset($_POST["email"]) && $_POST["id"]!="" && $_POST["key"]!="" && $_POST["email"]!=""){
        global $wpdb;
    
        $sql = $wpdb->prepare("
            SELECT * 
            FROM " . $wpdb->prefix . "reservas
            WHERE id_reserva = %s AND skey = %s", $_POST["id"], $_POST["key"]);
        $reserva = $wpdb->get_row($sql);

        if($reserva){
            $jugadores = json_decode($reserva->jugadores);

            if ($jugadores !== null) {
                $cambiado = false;

                foreach($jugadores as $index => $jugador){
                    if($jugador->email === $_POST["email"]){
                        unset($jugadores[$index]);
                        $jugadores = array_values($jugadores);  // Reindexa el array
                        $nuevoJson = json_encode($jugadores);

                        $sql = $wpdb->prepare("UPDATE " . $wpdb->prefix . "reservas SET jugadores = %s WHERE id_reserva = %s AND skey = %s", $nuevoJson, $_POST["id"], $_POST["key"]);
                        $cambiado = $wpdb->query($sql);
                        break;
                    }
                }

                if($cambiado)
                    echo json_encode(array("success"=>"El usuario fue eliminado."));
                else
                    echo json_encode(array("error"=>"No se contró ninguna usuario con ese email."));
            } else 
                echo json_encode(array("error"=>"No se pudo decodificar la lista de jugadores."));
        } else 
            echo json_encode(array("error"=>"No se contró ninguna reserva."));
    } else
        echo json_encode(array("error"=>"Parámetros enviados inválidos."));

    wp_die();
}



add_action('wp_ajax_agregar_usuarios_partida', 'agregar_usuarios_partida');
add_action('wp_ajax_nopriv_agregar_usuarios_partida', 'agregar_usuarios_partida');
function agregar_usuarios_partida() {
    if(isset($_POST["id"]) && isset($_POST["key"]) && isset($_POST["emailsString"]) && $_POST["id"]!="" && $_POST["key"]!="" && $_POST["emailsString"]!=""){
        global $wpdb;
    
        $sql = $wpdb->prepare("
            SELECT * 
            FROM " . $wpdb->prefix . "reservas
            WHERE id_reserva = %s AND skey = %s", $_POST["id"], $_POST["key"]);
        $reserva = $wpdb->get_row($sql);

        if ($reserva) {
            $emails = explode(",", strtolower($_POST["emailsString"]));
            $jugadores = json_decode($reserva->jugadores, true); // Asumo que jugadores es un array de objetos en JSON


            if ($emails) {
                $nuevosJugadores = [];
                foreach ($emails as $email) {
                    $jugadorExistente = false;
                    foreach ($jugadores as $jugador) {
                        if ($jugador['email'] === $email) {
                            // Actualiza los datos del jugador si es necesario
                            $jugadorExistente = true;

                            break;
                        }

                    }
                    if (!$jugadorExistente) {

                        if($reserva->email_usuario == $email){
                            $nuevosJugadores[] = [
                                'ID' => $reserva->id_usuario,
                                'nombre' => $reserva->nombre_usuario,
                                'apellidos' => $reserva->apellidos_usuario,
                                'email' => $email,
                                'telefono' => $reserva->telefono_usuario];
                        }
                        else{
                            // Agrega un nuevo jugador si el correo electrónico no existe
                            $nuevosJugadores[] = [
                                'ID' => '' ,
                                'nombre' => '' ,
                                'apellidos' => '' ,
                                'email' => $email,
                                'telefono' => ''];

                            $enlace_unirse = home_url() . "/unirse/?id=" . $reserva->id_reserva . "&key=" . $reserva->skey . "&user=" . base64_encode($email);
                            $nombre_local = get_term_by('id', $reserva->id_local, 'product_cat')->name;

                            $telefono_contacto_franquicia = get_term_meta($reserva->id_ciudad, 'telefono_contacto', true);
                            $email_contacto_franquicia = get_term_meta($reserva->id_ciudad, 'email_contacto', true);
                            $nombre_sl_franquicia = get_term_meta($reserva->id_ciudad, 'nombre_sl_franquicia', true);
                
                            $telefono_contacto_local = get_term_meta($reserva->id_local, 'telefono_contacto', true);
                            $email_contacto_local = get_term_meta($reserva->id_local, 'email_contacto', true);
                            $direccion_local = get_term_meta($reserva->id_local, 'direccion', true);
                            $enlace_local = get_term_meta($reserva->id_local, 'enlace_resenia_buena', true);

                            $fecha_formateada = DateTime::createFromFormat('Y-m-d', $reserva->fecha_reserva)->format('d/m/Y');


                            $num_usuarios = (int)$reserva->num_usuarios;
                            $lista_precios_raw = get_post_meta($reserva->id_juego, 'lista_precios', true);
                            $lista_precios = preg_replace("/\r?\n/", ",", trim($lista_precios_raw));
                            $precios_array = explode(",", $lista_precios);
                                
                            $detalle_jugadores = "{$num_usuarios} jugadores";
                            foreach ($precios_array as $precio_item) {
                                $partes = explode(' ', trim($precio_item));
                                $jugadores__ = (int)$partes[0];
                        
                                if ($jugadores__ === $num_usuarios) {
                                    $detalle_jugadores = trim($precio_item);
                                    break;
                                }
                            }




                            $data = array(

                                "Enlace_unirse" => $enlace_unirse,
                
                                "ID_reserva" => $reserva->id_reserva,
                                "Num_jugadores_reserva" => $reserva->num_usuarios,
                                "Fecha_reserva" => $fecha_formateada,
                                "Hora_reserva" => $reserva->hora_reserva,
                                "Nombre_juego" => $reserva->nombre_juego,
                                "Detalle_jugadores" => $detalle_jugadores,

                                "Telefono_contacto_franquicia" => $telefono_contacto_franquicia,
                                "Email_contacto_franquicia" => $email_contacto_franquicia,
                                "Nombre_sl_franquicia" => $nombre_sl_franquicia,

                                "Nombre_local" => $nombre_local,
                                "Direccion_local" => $direccion_local,
                                "Enlace_local" => $enlace_local,
                                "Telefono_contacto_local" => $telefono_contacto_local,
                                "Email_contacto_local" => $email_contacto_local,

                                "Nombre" => $reserva->nombre_usuario,
                                "Apellidos" => $reserva->apellidos_usuario,

                                "Email_contacto_franquicia" => $email_contacto_franquicia,
                            );

                            coco_log('Se ha invitado a una partida al email ' . $email);
                            coco_log(print_r($data,true));
                            do_action('send_mails_adpnsy_cllbck', $email, 'Tienes una invitación a COCO ROOM: '.$reserva->nombre_juego, 'invitacion_unirse_partida', $data);
                        }
                    }
                }

                // Fusiona la lista original de jugadores con los nuevos jugadores

                $jugadores = array_merge($jugadores, $nuevosJugadores);
                coco_log('Se han modificado los jugadores de la partida ' . $_POST["id"]);
                coco_log(print_r($jugadores,true));

                $string_jugadores = json_encode($jugadores);

                $resultado = $wpdb->update(
                    $wpdb->prefix."reservas",
                    ['jugadores' => $string_jugadores],
                    ['id_reserva' => $_POST["id"], 'skey' => $_POST["key"]]
                );

                if ($resultado !== false){
                    echo json_encode(array("success"=>"Se han agregado usuarios."));
                }
                else{
                    echo json_encode(array("error"=>"No se pudo actualizar la reserva."));
                }
            } else{
                echo json_encode(array("error"=>"No se pudo decodificar la lista de jugadores."));
            }
        } else{
            echo json_encode(array("error"=>"No se encontró ninguna reserva."));
        }
    } else{
        echo json_encode(array("error"=>"Parámetros enviados inválidos."));
    }

    wp_die();
}




add_action('wp_ajax_comprobar_jugadores_nuevos_reserva', 'comprobar_jugadores_nuevos_reserva');
add_action('wp_ajax_nopriv_comprobar_jugadores_nuevos_reserva', 'comprobar_jugadores_nuevos_reserva');
function comprobar_jugadores_nuevos_reserva() {
    if(isset($_POST["id_reserva"]) && isset($_POST["jugadores"]) && $_POST["id_reserva"]!="" && $_POST["jugadores"]!=""){
        global $wpdb;

        $sql = $wpdb->prepare("
            SELECT * 
            FROM " . $wpdb->prefix . "reservas
            WHERE id_reserva = %s", $_POST["id_reserva"]);
        $reserva = $wpdb->get_row($sql);

        if ($reserva) {
            $jugadores_actuales = json_decode($reserva->jugadores, true);
            $jugadores_antes = json_decode(stripslashes($_POST["jugadores"]),true);

            $jugadores_nuevos = array();

            foreach($jugadores_actuales as $actual) {
                $encontrado = false;
                foreach($jugadores_antes as $antes) {
                    if ($actual['ID'] == $antes['ID']) {
                        $encontrado = true;
                        break;
                    }
                    else if ($actual['email']!="" && $antes['email']!="" && $actual['email'] == $antes['email']) {
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {
                    $jugadores_nuevos[] = $actual;
                }
            }
            
            echo json_encode(array("jugadores_nuevos" => $jugadores_nuevos));
        } else 
            echo json_encode(array("error"=>"No se encontró ninguna reserva."));
    } else
        echo json_encode(array("error"=>"Parámetros enviados inválidos."));

    wp_die();
}


add_action('wp_ajax_eliminar_usuario_partida_gamemaster', 'eliminar_usuario_partida_gamemaster');
add_action('wp_ajax_nopriv_eliminar_usuario_partida_gamemaster', 'eliminar_usuario_partida_gamemaster');
function eliminar_usuario_partida_gamemaster() {
    if(isset($_POST["id_reserva"]) && isset($_POST["id_usuario"]) && $_POST["id_reserva"]!="" && $_POST["id_usuario"]!=""){
        global $wpdb;
    
        $sql = $wpdb->prepare("
            SELECT * 
            FROM " . $wpdb->prefix . "reservas
            WHERE id_reserva = %s", $_POST["id_reserva"]);
        $reserva = $wpdb->get_row($sql);

        if($reserva){
            $jugadores = json_decode($reserva->jugadores);

            if ($jugadores !== null) {
                $cambiado = false;

                foreach($jugadores as $index => $jugador){
                    if($jugador->ID == $_POST["id_usuario"]){
                        unset($jugadores[$index]);
                        $jugadores = array_values($jugadores);  // Reindexa el array
                        $nuevoJson = json_encode($jugadores);

                        $sql = $wpdb->prepare("UPDATE " . $wpdb->prefix . "reservas SET jugadores = %s WHERE id_reserva = %s", $nuevoJson, $_POST["id_reserva"]);
                        $cambiado = $wpdb->query($sql);
                        break;
                    }
                }

                if($cambiado)
                    echo json_encode(array("success"=>"El jugador fue eliminado de la partida."));
                else
                    echo json_encode(array("error"=>"No se contró al usuario en la partida."));
            } else 
                echo json_encode(array("error"=>"No se pudo decodificar la lista de jugadores."));
        } else 
            echo json_encode(array("error"=>"No se contró ninguna reserva."));
    } else
        echo json_encode(array("error"=>"Parámetros enviados inválidos."));

    wp_die();
}




function comparar_hora_cierre_con_hora($hora_cierre, $hora_disponible){
    $deshabilitado = false;
    if($hora_cierre->tipo == "hora"){
        $hora_cierre_real = strtotime($hora_cierre->valores);
        $hora_disponible_real = strtotime($hora_disponible);
        if($hora_cierre_real == $hora_disponible_real){
            $deshabilitado = true;
        }
    }
    if($hora_cierre->tipo == "intervalo"){
        $intervalo = explode(",", $hora_cierre->valores);
        $hora_cierre_real1 = strtotime($intervalo[0]);
        $hora_cierre_real2 = strtotime($intervalo[1]);
        $hora_disponible_real = strtotime($hora_disponible);

        if($hora_cierre_real1 <= $hora_disponible_real && $hora_cierre_real2 >= $hora_disponible_real){
            $deshabilitado = true;
        }
    }
    if($hora_cierre->tipo == "todo_el_dia"){
        $deshabilitado = true;
    }

    return $deshabilitado;
}




function crear_tabla_reservas() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();


        
    $sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix . "reservas (
        id_reserva int(9) NOT NULL AUTO_INCREMENT,
        id_order int(9) NOT NULL,
        fecha_hora_creacion_reserva datetime DEFAULT '0000-00-00 00:00:00',
        fecha_hora_cancelacion_reserva datetime DEFAULT '0000-00-00 00:00:00',
        id_ciudad int(9) NOT NULL,
        id_local int(9) NOT NULL,
        id_salas varchar(100),
        id_juego int(9) NOT NULL,
        nombre_juego varchar(100) NOT NULL,
        nombre_ciudad varchar(100) NOT NULL,
        id_usuario int(9) NOT NULL,
        nombre_usuario varchar(100) NOT NULL,
        apellidos_usuario varchar(100) NOT NULL,
        email_usuario varchar(100) NOT NULL,
        telefono_usuario INT NOT NULL,


        num_usuarios int(9) NOT NULL,
        fecha_reserva DATE NOT NULL,
        hora_reserva varchar(9) NOT NULL,


        primera_vez varchar(100) NOT NULL,
        aceptan_redes_sociales int(1) NOT NULL,
        id_gamemaster varchar(10) NOT NULL,
        objetivo_conseguido int(1) NOT NULL,
        enlace_valorar_experiencia text,


        fecha_hora_empezar_partida longtext,
        fecha_hora_finalizar_partida longtext,
        jugadores longtext, 
        fotografia text,

        enviar_email_resenia int(1) NOT NULL,
        puntuacion_partida int(1) NOT NULL,
        quieren_recibir_informacion int(1) NOT NULL,
        como_nos_han_conocido varchar(100) NOT NULL,
        promocion_utilizada varchar(100) NOT NULL,




        skey varchar(10) NOT NULL,

        estado varchar(20) NOT NULL,

        PRIMARY KEY  (id_reserva)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
}
register_activation_hook(__FILE__, 'crear_tabla_reservas');




function crear_tabla_cierres() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
        
    $sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix . "cierres (
        id_cierre int(9) NOT NULL AUTO_INCREMENT,
        tipo_cierre text NOT NULL,
        id_ciudad int(9) NOT NULL,
        fecha text NOT NULL,
        hora text NOT NULL,

        PRIMARY KEY  (id_cierre)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'crear_tabla_cierres');
















// Mostrar el campo en el perfil del usuario
function mostrar_campo_ciudad_en_perfil($user) {


    if (user_can($user, 'franquicia') || user_can($user, 'gamemaster') || user_can($user, 'administrator')) {
        $all_ciudades = get_categories(array(
            'taxonomy'   => 'product_cat',
            'orderby'    => 'name',
            'show_count' => 0,
            'pad_counts' => 0,
            'hierarchical' => 1,
            'title_li'   => '',
            'hide_empty' => 0,
            'parent'    => 0
        ));

        $selected_value = get_user_meta($user->ID, 'ciudad_usuario', true);
        
        if (empty($selected_value)) {
            $selected_value = 15; 
        }



        ?>
        <table class="form-table">
            <tr>
                <th><label for="ciudad_usuario">Ciudad</label></th>
                <td>
                    <select name="ciudad_usuario" id="ciudad_usuario">
                        <?php foreach ($all_ciudades as $ciudad): ?>
                            <option value="<?php echo $ciudad->term_id; ?>" <?php selected($ciudad->term_id, $selected_value); ?>>
                                <?php echo $ciudad->name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <br />
                    <span class="description">Por favor, elige una ciudad.</span>
                </td>
            </tr>
        </table>
        <?php
    }
}
add_action('show_user_profile', 'mostrar_campo_ciudad_en_perfil');
add_action('edit_user_profile', 'mostrar_campo_ciudad_en_perfil');

// Guardar el valor del campo personalizado
function guardar_ciudad_en_perfil($user_id) {
        update_user_meta($user_id, 'ciudad_usuario', $_POST['ciudad_usuario']);
}
add_action('personal_options_update', 'guardar_ciudad_en_perfil');
add_action('edit_user_profile_update', 'guardar_ciudad_en_perfil');






// Mostrar el campo en el perfil del usuario
function mostrar_campos_estados_en_perfil($user) {


    if (user_can($user, 'franquicia') || user_can($user, 'gamemaster') || user_can($user, 'administrator')) {

        $value_borrado = get_user_meta($user->ID, '_is_dlt_user_', true);
        $value_estado = get_user_meta($user->ID, 'estado_usuario_', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="usuario_activo">Usuario activo</label></th>
                <td>
                    <input <?php echo $value_estado == "1" ? "checked" : ""?> type="checkbox" name="usuario_activo" id="usuario_activo">
                    <span class="description">Si este check está <b>activado</b>, el usuario <b>estará activado</b> en el panel de reservas y podrá acceder a su cuenta.</span>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th><label for="usuario_borrado">Eliminar usuario</label></th>
                <td>
                    <input <?php echo $value_borrado ? "checked" : ""?> type="checkbox" name="usuario_borrado" id="usuario_borrado">
                    <span class="description">Si este check está <b>activado</b>, el usuario <b>no se mostrará</b> en el panel de reservas ni podrá acceder a su cuenta, independientemente del campo superior. Su estado será <b>"ELIMINADO"</b></span>
                </td>
            </tr>
        </table>
        <?php
    }
}
add_action('show_user_profile', 'mostrar_campos_estados_en_perfil');
add_action('edit_user_profile', 'mostrar_campos_estados_en_perfil');

function guardar_estados_en_perfil($user_id) {
        if(isset($_POST['usuario_borrado']))
            update_user_meta($user_id, '_is_dlt_user_', '1');
        else 
            delete_user_meta($user_id, '_is_dlt_user_');

        if(isset($_POST['usuario_activo']))
            update_user_meta($user_id, 'estado_usuario_', '1');
        else 
            update_user_meta($user_id, 'estado_usuario_', '0');
}
add_action('personal_options_update', 'guardar_estados_en_perfil');
add_action('edit_user_profile_update', 'guardar_estados_en_perfil');






add_filter( 'pll_custom_flag', 'pll_custom_flag', 10, 2 );
function pll_custom_flag( $flag, $code ) {
    $languages_with_custom_flags = array( 'es', 'gb', 'basque' );
	if ( in_array( $code, $languages_with_custom_flags ) ) {
        $flag['url']    = get_template_directory_uri() . '/flags/'.$code.".svg";
        $flag['width']  = 32;
        $flag['height'] = 22;
        return $flag;
    }
    else{
        return $flag;
    }
}




add_shortcode('sp_pasos_agregar_jugadores', 'sp_pasos_agregar_jugadores_callback');
function sp_pasos_agregar_jugadores_callback() {
    ob_start();
    
    ?>
    <style>
        .pasos_agregar_jugadores{
            margin-top: 50px;
        }

        .pasos_agregar_jugadores .paso_agregar_jugadores{
            margin-bottom: 40px;
        }

        .pasos_agregar_jugadores .site-title{
            margin-bottom: 30px;
        }

        .pasos_agregar_jugadores .imagen_paso{
            text-align: center;
        }

        .pasos_agregar_jugadores .imagen_paso img{
            box-shadow: 0 0 30px 0px #3336;
            max-width: 500px;
            width: 100%;
        }
    </style>

    <div class="pasos_agregar_jugadores">
        <h2 class="site-title">
            ¿Has hecho una reserva?
        </h2>
        <div class="paso_agregar_jugadores">
            <h3 class="tittle_3" for="num_usuarios"><span class="numero_bloque">1</span> Realizas la reserva </h3>
            <p>Si es la primera vez, <b>revisa tu correo electrónico y finaliza el registro</b> pinchando el link en el correo que recibirás.</p>
            <div class="imagen_paso">
                <img  src="<?=home_url()."/wp-content/plugins/funciones-reservas-cocoroom/img/email_bienvenida_cocoroom.png"?>" alt="Email de bienvenida">
            </div>
        </div>
        <div class="paso_agregar_jugadores">
            <h3 class="tittle_3" for="num_usuarios"><span class="numero_bloque">2</span> Inicia sesión en tu cuenta y añade a los participantes </h3>
            <p><b>¡El resto de compañeros deben realizar su registro!</b></p>
            <p>Añade los emails para que los participantes reciban el link o comparte el enlace con el resto de participantes. Los participante rellenarán sus datos de registro y deberán unirse a la partida.</p>
            <!-- <p>Podrás añadirlos de dos formas, elige la que más fácil te resulte:</p> -->
            <!-- <ul> -->
                <!-- <li><p><b></b></p></li> -->
                <!-- <li><p><b>Comparte el enlace.</b></p></li> -->
            </ul>
            <div class="imagen_paso">
                <img  src="<?=home_url()."/wp-content/plugins/funciones-reservas-cocoroom/img/pagina_agregar_jugadores.png"?>" alt="Página de agregar jugadores">
            </div>
        </div>

        <h2 class="site-title">
            ¿Has recibido una invitación?
        </h2>
        <div class="paso_agregar_jugadores">
            <h3 class="tittle_3" for="num_usuarios"><span class="numero_bloque">3</span> Aceptad la invitación iniciando sesión para ser añadidos a la partida. </h3>
            <p>Deberás ver un mensaje como este:</p>
            <div class="imagen_paso">
                <img  src="<?=home_url()."/wp-content/plugins/funciones-reservas-cocoroom/img/enlace_unirse.png"?>" alt="Mensaje de confirmación">
            </div>
        </div>

        <div class="paso_agregar_jugadores">
            <h3 class="tittle_3" for="num_usuarios"><span class="numero_bloque">4</span> ¡Y esto es todo! </h3>
            <p>Solo faltará esperar a que llegue el día de la reserva.</p>
            <p><b>Recordad, ¡es de vital importancia ser puntuales!</b></p>
        </div>
    </div>

    <?php
    return ob_get_clean();

}



add_action('woocommerce_order_details_after_order_table', 'sp_custom_content_after_order_table', 10, 1);
function sp_custom_content_after_order_table($order) {
    echo do_shortcode('[sp_pasos_agregar_jugadores]');
}






add_action('wp_head', 'sp_inyectar_script_gracias_seo');
function sp_inyectar_script_gracias_seo() {
    if (is_order_received_page()) {
        global $wp;
        $order_id = isset($wp->query_vars['order-received']) ? $wp->query_vars['order-received'] : 0;
        if (!$order_id) {
            return;
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $items = $order->get_items();
        if (empty($items)) {
            return;
        }
        $first_item = reset($items);
        $datos_reserva = wc_get_order_item_meta($first_item->get_id(), 'datos_reserva', true);
        if (empty($datos_reserva) || !isset($datos_reserva['id_ciudad'])) {
            return;
        }
        $id_ciudad = $datos_reserva['id_ciudad'];

        $term = get_term_by('id', $id_ciudad, 'product_cat');
        if (!$term || is_wp_error($term)) {
            return;
        }
        $slug_ciudad = $term->slug;

        $email_usuario = $order->get_billing_email();
        
        ?>
        <script>
            window.addEventListener('DOMContentLoaded', function() {
                var currentUrl = window.location.href;

                if (currentUrl.includes("/finalizar-compra/order-received/")) {
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({
                        'event': 'compra-<?php echo esc_js($slug_ciudad); ?>' 
                    });
                    window.dataLayer.push({
                        'event': 'formSubmission' ,
                        'formEmail': '<?php echo esc_js($email_usuario); ?>' 
                    });
                }
            });
        </script>
        <?php
    }
}


?>
