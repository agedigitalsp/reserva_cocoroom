<?php

/*
* Plugin Name: SP Modificaciones de Redsys
* Plugin URI: http://www.agenciasp.com
* Description: Plugin para intervenir el proceso de Redsys y cambiar los datos de la entidad bancaria correspodiente
* Version: 1.0
* Author: Agencia SP
* Author URI: http://www.agenciasp.com
* License:
*/


if ( ! defined( 'ABSPATH' ) ) exit;

//Cambia las opciones de redsys.
//coco_log FTP wp-content/cocolog.log
add_action('woocommerce_checkout_order_processed', 'customizar_configuracion_redsys', 99, 1);
function customizar_configuracion_redsys($order_id) {
    if ( ! $order_id ) {
        return;
    }
    
    $order = wc_get_order($order_id);

    if ( ! $order ) {
        return;
    }


    $items = $order->get_items();
    $first_item = reset($items);
    $id_ciudad = wc_get_order_item_meta( $first_item->get_id(), 'datos_reserva', true )["id_ciudad"];

    $payment_method = $order->get_payment_method();
    if ( $payment_method !== 'redsys' ) {
        coco_log("(PRE PAGO) NO REDSYS ".$id_ciudad);
        return;
    }

    //Ciudad Sevilla, sus reservas no tienen pago
    if($id_ciudad == 37) return;

    $redsys_settings = get_option('woocommerce_redsys_settings');
    $redsys_settings['name'] = get_term_meta($id_ciudad, "redsys_nombre_empresa", true);
    $redsys_settings['fuc'] = get_term_meta($id_ciudad, "redsys_numero_empresa", true);
    $redsys_settings['terminal'] = get_term_meta($id_ciudad, "redsys_numero_terminal", true);
    $redsys_settings['clave256'] = get_term_meta($id_ciudad, "redsys_clave256", true);

    update_option('woocommerce_redsys_settings', $redsys_settings);
    coco_log("(PRE PAGO) Se han cambiado los datos del TPV REDSYS de la ciudad ".$id_ciudad);
    coco_log(print_r($redsys_settings,true));
}



// add_action('woocommerce_api_wc_redsys', 'customizar_configuracion_redsys_postpago', 1);
function customizar_configuracion_redsys_postpago() {
    if (class_exists('RedsysAPI')) {
        if (!empty( $_REQUEST ) ) {
            if (!empty( $_POST ) ) {//URL DE RESP. ONLINE

                /** Recoger datos de respuesta **/
                $version      = $_POST["Ds_SignatureVersion"];
                $datos        = $_POST["Ds_MerchantParameters"];
                $firma_remota = $_POST["Ds_Signature"];

                // Se crea Objeto
                $miObj = new RedsysAPI;

                /** Se decodifican los datos enviados y se carga el array de datos **/
                $decodec = $miObj->decodeMerchantParameters($datos);
                $miObj->stringToArray($decodec);

                $merchantData = b64url_decode($miObj->getParameter('Ds_MerchantData'));
                $merchantData = json_decode( $merchantData ); 

                $idCart = $merchantData->idCart;
                $order = wc_get_order($idCart);

                if ($order) {
                    global $wpdb;
    
                    // Obtener todas las reservas asociadas al ID de pedido
                    $sql_reservas = $wpdb->prepare(
                        "SELECT * FROM " . $wpdb->prefix . "reservas WHERE id_order = %d",
                        $idCart
                    );
                    $reservas = $wpdb->get_results($sql_reservas);
        
                    if (!empty($reservas)) {
                        // Selecciona la primera reserva
                        $primera_reserva = $reservas[0];
                        $id_ciudad = $primera_reserva->id_ciudad;
        
                        // Actualizar los ajustes de Redsys
                        $redsys_settings = get_option('woocommerce_redsys_settings');
                        $redsys_settings['name'] = get_term_meta($id_ciudad, "redsys_nombre_empresa", true);
                        $redsys_settings['fuc'] = get_term_meta($id_ciudad, "redsys_numero_empresa", true);
                        $redsys_settings['terminal'] = get_term_meta($id_ciudad, "redsys_numero_terminal", true);
                        $redsys_settings['clave256'] = get_term_meta($id_ciudad, "redsys_clave256", true);
    
                        update_option('woocommerce_redsys_settings', $redsys_settings);
                        coco_log("(POST PAGO) Se han cambiado los datos del TPV REDSYS de la ciudad ".$id_ciudad);
                        coco_log(print_r($redsys_settings,true));

                    }
                }
            }
        }
    }
}



//Va por ciudad y habilita o no según esté configurado por la ciudad
add_filter('woocommerce_available_payment_gateways', 'disable_payment_gateway_by_category_redsys');
function disable_payment_gateway_by_category_redsys($available_gateways) {
    if (!is_admin() && is_checkout()) {
        $items = WC()->cart->get_cart();
        if (!empty($items)) {
            $first_item = reset($items);
            $product_id = $first_item['product_id'];
            $id_ciudad = obtener_ubicacion_juego($product_id)["id"];
            $habilitar_redsys = get_term_meta($id_ciudad, 'habilitar_redsys', true);
            if (empty($habilitar_redsys)) {
                unset($available_gateways['redsys']);
            }
        }
    }
    return $available_gateways;
}






function guardar_datos_redsys($term_id) {
    if (isset($_POST['habilitar_metodo_pago']) && $_POST['habilitar_metodo_pago'] === 'redsys') {
        update_term_meta($term_id, 'habilitar_redsys', 1);
    } else {
        update_term_meta($term_id, 'habilitar_redsys', 0);
    }
    if (isset($_POST['redsys_nombre_empresa'])) {
        update_term_meta($term_id, 'redsys_nombre_empresa', sanitize_text_field($_POST['redsys_nombre_empresa']));
    }
    if (isset($_POST['redsys_numero_empresa'])) {
        update_term_meta($term_id, 'redsys_numero_empresa', sanitize_text_field($_POST['redsys_numero_empresa']));
    }
    if (isset($_POST['redsys_numero_terminal'])) {
        update_term_meta($term_id, 'redsys_numero_terminal', sanitize_text_field($_POST['redsys_numero_terminal']));
    }
    if (isset($_POST['redsys_clave256'])) {
        update_term_meta($term_id, 'redsys_clave256', sanitize_text_field($_POST['redsys_clave256']));
    }
}
add_action('create_product_cat', 'guardar_datos_redsys');
add_action('edited_product_cat', 'guardar_datos_redsys');

function add_datos_redsys($term, $taxonomy) {
    if ('product_cat' !== $taxonomy) {
        return;
    }

    $habilitar_redsys = get_term_meta($term->term_id, 'habilitar_redsys', true);
    $redsys_nombre_empresa = get_term_meta($term->term_id, 'redsys_nombre_empresa', true);
    $redsys_numero_empresa = get_term_meta($term->term_id, 'redsys_numero_empresa', true);
    $redsys_numero_terminal = get_term_meta($term->term_id, 'redsys_numero_terminal', true);
    $redsys_clave256 = get_term_meta($term->term_id, 'redsys_clave256', true);

    if ($term->parent == 0) { 
    ?>
        <tr class="form-field">
            <td colspan=2>
                <hr>
                <h2>Datos de Redsys</h2>
                <hr>
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row"><label for="habilitar_metodo_pago">Habilitar Redsys</label></th>
            <td>
                <input type="radio" name="habilitar_metodo_pago" id="habilitar_redsys" value="redsys" <?php echo $habilitar_redsys == "1" ? "checked" : "" ?>>
            </td>
        </tr>

        <tr class="form-field datos_redsys">
            <th scope="row"><label for="redsys_nombre_empresa">Nombre de empresa</label></th>
            <td>
                <input type="text" name="redsys_nombre_empresa" id="redsys_nombre_empresa" value="<?php echo esc_attr($redsys_nombre_empresa); ?>">
            </td>
        </tr>

        <tr class="form-field datos_redsys">
            <th scope="row"><label for="redsys_numero_empresa">Número de empresa</label></th>
            <td>
                <input type="text" name="redsys_numero_empresa" id="redsys_numero_empresa" value="<?php echo esc_attr($redsys_numero_empresa); ?>">
            </td>
        </tr>

        <tr class="form-field datos_redsys">
            <th scope="row"><label for="redsys_numero_terminal">Número de terminal</label></th>
            <td>
                <input type="text" name="redsys_numero_terminal" id="redsys_numero_terminal" value="<?php echo esc_attr($redsys_numero_terminal); ?>">
            </td>
        </tr>

        <tr class="form-field datos_redsys">
            <th scope="row"><label for="redsys_clave256">Clave SHA256</label></th>
            <td>
                <input type="text" name="redsys_clave256" id="redsys_clave256" value="<?php echo esc_attr($redsys_clave256); ?>">
            </td>
        </tr>

        <hr>

        <script>
            jQuery(document).ready(function($) {
                function evento_habilitar_redsys(){
                    if ($('#habilitar_redsys').prop('checked')) {
                        $(".datos_redsys").show(); 
                    } else { 
                        $(".datos_redsys").hide();
                    }
                }
                $('input[name="habilitar_metodo_pago"]').change(evento_habilitar_redsys);
                evento_habilitar_redsys();
            })
        </script>
    <?php
    }
}
add_action('product_cat_edit_form_fields', 'add_datos_redsys', 10, 2);

