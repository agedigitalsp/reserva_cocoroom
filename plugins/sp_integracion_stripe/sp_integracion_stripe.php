<?php


/*
* Plugin Name: SP Integración de Stripe
* Plugin URI: http://www.agenciasp.com
* Description: Plugin para intervenir el proceso de Stripe.
* Version: 2.0
* Author: Agencia SP
* Author URI: http://www.agenciasp.com
* License:
*/


 
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists('stripe_sp')){
    class stripe_sp {
        public function __construct(){
            add_filter('woocommerce_available_payment_gateways', [$this, 'disable_payment_gateway_by_category_stripe']);
            add_action('create_product_cat', [$this, 'guardar_datos_stripe']);
            add_action('edited_product_cat', [$this, 'guardar_datos_stripe']);
            add_action('product_cat_edit_form_fields', [$this, 'add_datos_stripe'], 10, 2);
            add_action('wp_head', [$this, 'eliminar_borde_negro_formulario_pago_stripe']);
            add_filter('option_woocommerce_stripe_settings', [$this, 'modificar_configuracion_stripe']);
            add_filter('wc_stripe_upe_params', [$this, 'modificar_configuracion_stripe_js']);


        }

        public function modificar_configuracion_stripe_js($settings) {
            $cart_items = WC()->cart->get_cart();
            if($cart_items){
                $product = array_values($cart_items)[0];
                $id_ciudad = $product["id_ciudad"];
                $stripe_public = get_term_meta($id_ciudad, "stripe_public", true);
                $stripe_private = get_term_meta($id_ciudad, "stripe_private", true);
                $stripe_webhook = get_term_meta($id_ciudad, "stripe_webhook", true);
                if($stripe_public && $stripe_private && $stripe_webhook){
                    $settings["key"] = $stripe_public;
                }
            }
            
            return $settings;
        }

        public function modificar_configuracion_stripe($settings) {
            global $wp_query;
            if(isset( $wp_query ) && is_checkout()){
                $cart_items = WC()->cart->get_cart();
                if($cart_items){
                    $product = array_values($cart_items)[0];
                    $id_ciudad = $product["id_ciudad"];
                    $stripe_public = get_term_meta($id_ciudad, "stripe_public", true);
                    $stripe_private = get_term_meta($id_ciudad, "stripe_private", true);
                    $stripe_webhook = get_term_meta($id_ciudad, "stripe_webhook", true);
                    if($stripe_public && $stripe_private && $stripe_webhook){
                        $settings["test_publishable_key"] = $stripe_public;
                        $settings["test_secret_key"] = $stripe_private;
                        $settings["test_webhook_secret"] = $stripe_webhook;
                        $settings["publishable_key"] = $stripe_public;
                        $settings["secret_key"] = $stripe_private;
                        $settings["webhook_secret"] = $stripe_webhook;
                    }
                }
            }

            return $settings;
        }

        public function disable_payment_gateway_by_category_stripe($available_gateways) {
            if(get_current_user_id()==1) return $available_gateways;
            if (!is_admin() && is_checkout()) {
                $items = WC()->cart->get_cart();
                
                if (!empty($items)) {
                    $first_item = reset($items);
                    $product_id = $first_item['product_id'];
                    $id_ciudad = obtener_ubicacion_juego($product_id)["id"];
                    $habilitar_stripe = get_term_meta($id_ciudad, 'habilitar_stripe', true);

                    if (empty($habilitar_stripe)) {
                        unset($available_gateways['stripe']);
                    }
                }
            }
            return $available_gateways;
        }

        public function guardar_datos_stripe($term_id) {
            if (isset($_POST['habilitar_metodo_pago']) && $_POST['habilitar_metodo_pago'] === 'stripe') {
                update_term_meta($term_id, 'habilitar_stripe', 1);
            } else {
                update_term_meta($term_id, 'habilitar_stripe', 0);
            }
            if (isset($_POST['stripe_public'])) {
                update_term_meta($term_id, 'stripe_public', sanitize_text_field($_POST['stripe_public']));
            }
            if (isset($_POST['stripe_private'])) {
                update_term_meta($term_id, 'stripe_private', sanitize_text_field($_POST['stripe_private']));
            }
            if (isset($_POST['stripe_webhook'])) {
                update_term_meta($term_id, 'stripe_webhook', sanitize_text_field($_POST['stripe_webhook']));
            }
        }

        public function add_datos_stripe($term, $taxonomy) {
            if ('product_cat' !== $taxonomy) {
                return;
            }

            $habilitar_stripe = get_term_meta($term->term_id, 'habilitar_stripe', true);
            $stripe_public = get_term_meta($term->term_id, 'stripe_public', true);
            $stripe_private = get_term_meta($term->term_id, 'stripe_private', true);
            $stripe_webhook = get_term_meta($term->term_id, 'stripe_webhook', true);

            if ($term->parent == 0) { 
            ?>
                <tr class="form-field">
                    <td colspan=2>
                        <hr>
                        <h2>Datos de Stripe</h2>
                        <hr>
                    </td>
                </tr>

                <tr class="form-field">
                    <th scope="row"><label for="habilitar_metodo_pago">Habilitar Stripe</label></th>
                    <td>
                        <input type="radio" name="habilitar_metodo_pago" id="habilitar_stripe" value="stripe" <?php echo $habilitar_stripe == "1" ? "checked" : "" ?>>
                    </td>
                </tr>

                <tr class="form-field datos_stripe">
                    <th scope="row"><label for="stripe_public">ID Stripe public</label></th>
                    <td>
                        <?php
                            if($term->term_id == 19){
                                echo '<input type="text" name="stripe_public" id="stripe_public" disabled value="(Zaragoza es la cuenta principal)">';
                            } else{
                                echo '<input type="text" name="stripe_public" id="stripe_public" value="' . esc_attr($stripe_public) . '">';
                            }
                        ?>
                    </td>
                </tr>

                <tr class="form-field datos_stripe">
                    <th scope="row"><label for="stripe_private">ID Stripe private</label></th>
                    <td>
                        <?php
                            if($term->term_id == 19){
                                echo '<input type="text" name="stripe_private" id="stripe_private" disabled value="(Zaragoza es la cuenta principal)">';
                            } else{
                                echo '<input type="text" name="stripe_private" id="stripe_private" value="' . esc_attr($stripe_private) . '">';
                            }
                        ?>
                    </td>
                </tr>


                <tr class="form-field datos_stripe">
                    <th scope="row"><label for="stripe_webhook">ID Stripe webhook</label></th>
                    <td>
                        <?php
                            if($term->term_id == 19){
                                echo '<input type="text" name="stripe_webhook" id="stripe_webhook" disabled value="(Zaragoza es la cuenta principal)">';
                            } else{
                                echo '<input type="text" name="stripe_webhook" id="stripe_webhook" value="' . esc_attr($stripe_webhook) . '">';
                            }
                        ?>
                    </td>
                </tr>

                <hr>

                <script>
                    jQuery(document).ready(function($) {
                        function evento_habilitar_stripe(){
                            if ($('#habilitar_stripe').prop('checked')) {
                                $(".datos_stripe").show(); 
                            } else { 
                                $(".datos_stripe").hide();
                            }
                        }
                        $('input[name="habilitar_metodo_pago"]').change(evento_habilitar_stripe);
                        evento_habilitar_stripe();
                    })
                </script>
            <?php
            }
        }

        public function eliminar_borde_negro_formulario_pago_stripe() {
            if (is_checkout()) {
                echo '<style>
                    #wc-stripe-upe-form {
                        border: none!important;
                    }
                </style>';
            }
        }
    }
    $stripe_sp = new stripe_sp();
}
