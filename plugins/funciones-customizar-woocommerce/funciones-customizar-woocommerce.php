<?php
/*
* Plugin Name: Funciones Customizar Woocommerce
* Plugin URI: https://agenciasp.com/
* Description: Funciones para modificar Wooocommerce
* Version: 1.0.0
* Author: Agencia Digital SP
* Author URI: https://agenciasp.com/
* License:
*/


function enqueue_my_scripts_customizar_woocommerce() {
    //Asignar estilos customizados en cada página de Woocommerce
    if( is_checkout() ){
        wp_enqueue_script( 'custom-cart-remove', plugin_dir_url( __FILE__ ) . 'js/custom-cart-remove.js', array('jquery'), null, true );
        wp_localize_script('custom-cart-remove', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style( 'estilo_checkout', plugin_dir_url( __FILE__ ) . 'css/estilo_checkout.css?v=2' );
    }

    if ( is_cart() ) {
        wp_enqueue_style( 'estilo_carrito', plugin_dir_url( __FILE__ ) . 'css/estilo_carrito.css' );
    }

    if ( is_shop() ) {
        wp_enqueue_style( 'estilo_tienda', plugin_dir_url( __FILE__ ) . 'css/estilo_tienda.css' );
    }

    if ( is_product() ) {
        wp_enqueue_style( 'estilo_single_product', plugin_dir_url( __FILE__ ) . 'css/estilo_single_product.css' );
    }

    if ( is_product_category() ) {
        wp_enqueue_style( 'estilo_product_category', plugin_dir_url( __FILE__ ) . 'css/estilo_product_category.css' );
    }

    if ( is_account_page() ) {
        wp_enqueue_style( 'estilo_mi_cuenta', plugin_dir_url( __FILE__ ) . 'css/estilo_mi_cuenta.css' );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_my_scripts_customizar_woocommerce');



function enqueue_my_scripts_customizar_woocommerce_backend() {
    global $post;
    if ($post && $post->post_type === 'product') {
        wp_enqueue_style( 'ocultar_campos_producto', plugin_dir_url( __FILE__ ) . 'css/estilo_producto_backend.css' );
    }
}
add_action('admin_enqueue_scripts', 'enqueue_my_scripts_customizar_woocommerce_backend');


//Reemplazar la plantilla de thankyou"
add_filter( 'woocommerce_locate_template', 'elementor_woocommerce_locate_template', 20, 3 );
function elementor_woocommerce_locate_template( $template, $template_name, $template_path ) {
	// echo $template_name;

    if ($template_name == 'checkout/thankyou.php') {
		$template_directory = plugin_dir_path( __FILE__ ) .'/templates/thankyou.php';
		if ( file_exists( $template_directory ) ) {
			$template = $template_directory;
		}
    }
    return $template;
}


function custom_remove_woocommerce_menus() {
    // Eliminar menús
    remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_tag&amp;post_type=product'); // Etiquetas
    remove_submenu_page('edit.php?post_type=product', 'product_attributes'); // Atributos
    remove_submenu_page('edit.php?post_type=product', 'product-reviews'); // Valoraciones


    // Desactivar valoraciones
    update_option( 'woocommerce_enable_reviews', 'no' );
}
add_action('admin_menu', 'custom_remove_woocommerce_menus', 999);


function custom_rename_woocommerce_category_labels( $args ) {
    $labels = array(
        'name'              => 'Espacios',
        'singular_name'     => 'Espacio',
        'menu_name'         => 'Espacios',
        'all_items'         => 'Todos los espacios',
        'edit_item'         => 'Editar espacio',
        'view_item'         => 'Ver espacio',
        'update_item'       => 'Actualizar espacio',
        'add_new_item'      => 'Añadir nuevo espacio',
        'new_item_name'     => 'Nombre de nuevo espacio',
        'parent_item'       => 'Espacio padre',
        'parent_item_colon' => 'Espacio padre:',
        'search_items'      => 'Buscar espacios',
        'popular_items'     => 'Espacios populares',
        'not_found'         => 'No se encontraron espacios',
    );

    $args['labels'] = $labels;

    return $args;
}
add_filter( 'woocommerce_taxonomy_args_product_cat', 'custom_rename_woocommerce_category_labels' );








function custom_rename_woocommerce_menus() {
    global $menu;

    // Definir el cambio de nombre
    $rename = array(
        'Productos' => 'Juegos'
    );

    // Cambiar nombres en el menú
    foreach ($menu as $key => $val) {
        $title = $val[0];
        if (isset($rename[$title])) {
            $menu[$key][0] = $rename[$title];
        }
    }

    // Cambiar etiquetas para el tipo de post 'product'
    if ($object = get_post_type_object('product')) {
        $object->labels->name               = 'Juegos';
        $object->labels->singular_name      = 'Juego';
        $object->labels->add_new            = 'Añadir nuevo';
        $object->labels->add_new_item       = 'Añadir nuevo juego';
        $object->labels->edit_item          = 'Editar juego';
        $object->labels->new_item           = 'Nuevo juego';
        $object->labels->view_item          = 'Ver juego';
        $object->labels->view_items         = 'Ver juegos';
        $object->labels->search_items       = 'Buscar juegos';
        $object->labels->not_found          = 'No se encontraron juegos';
        $object->labels->not_found_in_trash = 'No se encontraron juegos en la papelera';
        $object->labels->all_items          = 'Todos los juegos';
        $object->labels->archives           = 'Archivo de juegos';
        $object->labels->attributes         = 'Atributos del juego';
        $object->labels->insert_into_item   = 'Insertar en juego';
    }
    
}
add_action('admin_menu', 'custom_rename_woocommerce_menus');


function custom_change_admin_label() {
    global $menu, $submenu;
    // $menu[70][0] = 'Store';
    $submenu['edit.php?post_type=product'][5][0] = 'Todos los juegos';
}
add_action( 'admin_menu', 'custom_change_admin_label' );






add_filter( 'woocommerce_checkout_cart_item_quantity', 'add_delete_checkout_cart_item', 10, 3 );
function add_delete_checkout_cart_item( $quantity_html, $cart_item, $cart_item_key ){
    $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
    $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

    $quantity_html = sprintf(
        '<a href="javascript:void(0);" class="remove-ajax remove-from-cart" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s"><i class="material-icons">delete</i></div></a>',
        esc_html__( 'Remove this item', 'woocommerce' ),
        esc_attr( $product_id ),
        esc_attr( $cart_item_key ),
        esc_attr( $_product->get_sku() )
    );

    return $quantity_html;
}

add_action('wp_ajax_remove_item_from_cart', 'remove_item_from_cart');
add_action('wp_ajax_nopriv_remove_item_from_cart', 'remove_item_from_cart');

function remove_item_from_cart() {
    $cart_item_key = $_POST['cart_item_key'];
    if ($cart_item_key) {
        WC()->cart->remove_cart_item($cart_item_key);
        echo json_encode(['success' => 'Item removed']);
    } else {
        echo json_encode(['error' => 'No cart item key']);
    }
    wp_die();
}








function set_product_as_virtual_default() {
    global $post;
    if ( 'product' === $post->post_type ) { // && 'auto-draft' === $post->post_status
        update_post_meta( $post->ID, '_virtual', 'yes' );
    }
}
add_action( 'draft_to_publish', 'set_product_as_virtual_default' );



// Eliminar pestañas específicas de los datos del producto
function custom_remove_product_data_tabs( $tabs ) {
    unset( $tabs['inventory'] );         // Deshabilitar Inventario
    unset( $tabs['shipping'] );          // Deshabilitar Envío
    // unset( $tabs['linked_product'] );    // Deshabilitar Productos relacionados
    unset( $tabs['attribute'] );         // Deshabilitar Atributos
    unset( $tabs['advanced'] );          // Deshabilitar Avanzado
    return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'custom_remove_product_data_tabs' );

function set_default_product_type() {
    global $post;
    if ( isset($post) && 'product' === $post->post_type && 'auto-draft' === $post->post_status ) {
        $script = '<script type="text/javascript">
            jQuery(document).ready(function($) {
                if ( $("select#product-type").length ) {
                    $("select#product-type").val("simple").trigger("change");
                }
            });
        </script>';
        wp_add_inline_script( 'jquery', $script );
    }
}
add_action( 'admin_footer', 'set_default_product_type' );






add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

function custom_override_checkout_fields( $fields ) {
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_address_1']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_postcode']);
    unset($fields['billing']['billing_country']);
    unset($fields['billing']['billing_state']);
  
    return $fields;
}












/* CAMPOS COMPLEMENTARIOS A LAS CATEGORIAS (FRANQUICIAS, LOCALES) */

// Añadir campos al formulario de edición de la categoría
function custom_woocommerce_category_fields($taxonomy) {
    // Comprobar si estamos en la taxonomía correcta
    if ('product_cat' !== $taxonomy) {
        return;
    }
    ?>
    <div class="campos_ciudad" style="display: none;">
        <div class="form-field">
            <label for="nombre_sl_franquicia"><?php echo 'Nombre SL Franquicia'; ?></label>
            <input type="text" name="nombre_sl_franquicia" id="nombre_sl_franquicia">
        </div>
    </div>


    <div class="campos_local" style="display: none;">
        <div class="form-field">
            <label for="direccion"><?php echo 'Dirección'; ?></label>
            <input type="text" name="direccion" id="direccion">
        </div>
        <div class="form-field">
            <label for="telefono_contacto"><?php echo 'Teléfono de contacto'; ?></label>
            <input type="text" name="telefono_contacto" id="telefono_contacto">
        </div>
        <div class="form-field">
            <label for="email_contacto"><?php echo 'Email de contacto'; ?></label>
            <input type="email" name="email_contacto" id="email_contacto">
        </div>

        
        <hr>

        
        <div class="form-field">
            <label for="enlace_resenia_buena"><?php echo 'Enlace de Google Maps para recibir la reseña'; ?></label>
            <input type="text" name="enlace_resenia_buena" id="enlace_resenia_buena">
        </div>

        <div class="form-field">
            <label for="enlace_resenia_mala"><?php echo 'Enlace del formulario de contacto para evitar la reseña negativa'; ?></label>
            <input type="text" name="enlace_resenia_mala" id="enlace_resenia_mala">
        </div>
    </div>


    <hr>


    <div class="campos_ciudad" style="display: none;">

        <!-- <div class="form-field">
            <label for="redsys_nombre_empresa"><?php echo 'Nombre de empresa'; ?></label>
            <input type="text" name="redsys_nombre_empresa" id="redsys_nombre_empresa">
        </div>

        <div class="form-field">
            <label for="redsys_numero_empresa"><?php echo 'Número de empresa'; ?></label>
            <input type="text" name="redsys_numero_empresa" id="redsys_numero_empresa">
        </div>

        <div class="form-field">
            <label for="redsys_numero_terminal"><?php echo 'Número de terminal'; ?></label>
            <input type="text" name="redsys_numero_terminal" id="redsys_numero_terminal">
        </div>

        <div class="form-field">
            <label for="redsys_clave256"><?php echo 'Clave SHA256'; ?></label>
            <input type="text" name="redsys_clave256" id="redsys_clave256">
        </div> -->


        <hr>

        <div class="form-field">
            <label for="activar_promociones"><?php echo 'Activar promociones'; ?></label>
            <input type="checkbox" name="activar_promociones" id="activar_promociones">
                Habilitar para activar las promociones en esta ciudad. Es necesario que especificar el ID de la lista de Brevo
        </div>

        <div class="form-field id_lista_brevo">
            <label for="id_lista_brevo"><?php echo 'ID de la lista de Brevo (de esta ciudad)'; ?></label>
            <input type="text" name="id_lista_brevo" id="id_lista_brevo">
        </div>

        
        <hr>


        <div class="form-field">
            <label for="_db_cupones_name_"><?php echo '(CUPONES) Nombre único de ciudad (slug)'; ?></label>
            <input type="text" name="_db_cupones_name_" id="_db_cupones_name_">
        </div>

        <div class="form-field">
            <label for="_db_cupones_"><?php echo '(CUPONES) Base de datos de cupones'; ?></label>
            <input type="text" name="_db_cupones_" id="_db_cupones_" placeholder="cocoroom_cocoroomcake">
        </div>


        <hr>


        <div class="form-field idiomas_reservas">
            <label for="idiomas_reservas"><?php echo 'Idiomas en los que reservar (en esta ciudad). Formato [es,en,eu]'; ?></label>
            <input type="text" name="idiomas_reservas" id="idiomas_reservas" placeholder="es,en,eu" value="es">
        </div>


        <hr>

        
        <div class="form-field">
            <label for="activar_texto_adicional_email_reserva"><?php echo 'Activar texto adicional email reserva'; ?></label>
            <input type="checkbox" name="activar_texto_adicional_email_reserva" id="activar_texto_adicional_email_reserva">
                Habilitar para mostrar el siguiente texto en el email de la reservas.
        </div>

        <div class="form-field term-group">
            <label for="texto_adicional_email_reserva"><?php echo 'Texto adicional email reserva'; ?></label>
            <?php
            wp_editor('', 'texto_adicional_email_reserva', [
                'textarea_name' => 'texto_adicional_email_reserva',
                'media_buttons' => false,
                'textarea_rows' => 5,
                'teeny' => true,
                'quicktags' => false
            ]);
            ?>
            <p class="description">Añade aquí texto adicional que quieres mencionar en los emails de esta franquicia (email de nueva reserva para el cliente)</p>
        </div>

        <hr>

        <div class="form-field">
            <label for="activar_texto_adicional_email_finalizada"><?php echo 'Activar texto adicional email de partida finalizada'; ?></label>
            <input type="checkbox" name="activar_texto_adicional_email_finalizada" id="activar_texto_adicional_email_finalizada">
                Habilitar para mostrar el siguiente texto en el email de "partida finalizada" (similar al campo anterior).
        </div>

        <div class="form-field term-group">
            <label for="texto_adicional_email_finalizada"><?php echo 'Texto adicional email de partida finalizada'; ?></label>
            <?php
            wp_editor('', 'texto_adicional_email_finalizada', [
                'textarea_name' => 'texto_adicional_email_finalizada',
                'media_buttons' => false,
                'textarea_rows' => 5,
                'teeny' => true,
                'quicktags' => false
            ]);
            ?>
            <p class="description">Añade aquí texto adicional que quieres mencionar en los emails de esta franquicia (email de partida finalizada para el cliente)</p>
        </div>


    </div>




    <script>
        jQuery(document).ready(function($) {
            var dropdown = $('select#parent');
            var camposCiudad = $('.campos_ciudad');
            var camposLocal = $('.campos_local');

            function checkParent() {
                var selectedParent = dropdown.find('option:selected').attr('class');

                if (!selectedParent) {
                    camposCiudad.show();
                    camposLocal.show();
                } else {
                    if (selectedParent == "level-0") {
                        camposCiudad.hide();
                        camposLocal.show();
                    } else {
                        camposCiudad.hide();
                        camposLocal.hide();
                    }
                }
            }

            dropdown.change(function() {
                checkParent();
            });

            checkParent();  // Llamamos a la función al cargar la página para establecer los campos adecuados en función de la selección actual.



            $('#activar_promociones').change(function() {
                evento_check_promociones($(this));
            });
            function evento_check_promociones($this){
                if ($this.prop('checked')) {
                    $(".form-field.id_lista_brevo").show(); 
                } else { 
                    $(".form-field.id_lista_brevo").hide();
                }
            }
            evento_check_promociones($('#activar_promociones'));

        });
    </script>

    <?php
}
add_action('product_cat_add_form_fields', 'custom_woocommerce_category_fields');


// Guardar los campos personalizados
function save_custom_woocommerce_category_fields($term_id) {
    if (isset($_POST['nombre_sl_franquicia'])) {
        update_term_meta($term_id, 'nombre_sl_franquicia', sanitize_text_field($_POST['nombre_sl_franquicia']));
    }
    if (isset($_POST['direccion'])) {
        update_term_meta($term_id, 'direccion', sanitize_text_field($_POST['direccion']));
    }
    if (isset($_POST['telefono_contacto'])) {
        update_term_meta($term_id, 'telefono_contacto', sanitize_text_field($_POST['telefono_contacto']));
    }
    if (isset($_POST['email_contacto'])) {
        update_term_meta($term_id, 'email_contacto', sanitize_email($_POST['email_contacto']));
    }

    // if (isset($_POST['redsys_nombre_empresa'])) {
    //     update_term_meta($term_id, 'redsys_nombre_empresa', sanitize_text_field($_POST['redsys_nombre_empresa']));
    // }
    // if (isset($_POST['redsys_numero_empresa'])) {
    //     update_term_meta($term_id, 'redsys_numero_empresa', sanitize_text_field($_POST['redsys_numero_empresa']));
    // }
    // if (isset($_POST['redsys_numero_terminal'])) {
    //     update_term_meta($term_id, 'redsys_numero_terminal', sanitize_text_field($_POST['redsys_numero_terminal']));
    // }
    // if (isset($_POST['redsys_clave256'])) {
    //     update_term_meta($term_id, 'redsys_clave256', sanitize_text_field($_POST['redsys_clave256']));
    // }

    if (isset($_POST['idiomas_reservas'])) {
        update_term_meta($term_id, 'idiomas_reservas', sanitize_text_field($_POST['idiomas_reservas']));
    }

    if (isset($_POST['_db_cupones_name_'])) {
        update_term_meta($term_id, '_db_cupones_name_', sanitize_text_field($_POST['_db_cupones_name_']));
    }
    
    if (isset($_POST['_db_cupones_'])) {
        update_term_meta($term_id, '_db_cupones_', sanitize_text_field($_POST['_db_cupones_']));
    }


    if (isset($_POST['enlace_resenia_buena'])) {
        update_term_meta($term_id, 'enlace_resenia_buena', sanitize_text_field($_POST['enlace_resenia_buena']));
    }
    if (isset($_POST['enlace_resenia_mala'])) {
        update_term_meta($term_id, 'enlace_resenia_mala', sanitize_text_field($_POST['enlace_resenia_mala']));
    }

    if (isset($_POST['activar_promociones'])) {
        if (isset($_POST['id_lista_brevo'])) {
            update_term_meta($term_id, 'id_lista_brevo', sanitize_text_field($_POST['id_lista_brevo']));
        }
        else{
            update_term_meta($term_id, 'id_lista_brevo', '');
        }
    }
    else{
        update_term_meta($term_id, 'id_lista_brevo', '');
    }

    if (isset($_POST['texto_adicional_email_reserva'])) {
        update_term_meta($term_id, 'texto_adicional_email_reserva', wp_kses_post($_POST['texto_adicional_email_reserva']));
    }

    if (isset($_POST['activar_texto_adicional_email_reserva'])) {
        update_term_meta($term_id, 'activar_texto_adicional_email_reserva', 1);
    }
    else{
        update_term_meta($term_id, 'activar_texto_adicional_email_reserva', "");
    }


    if (isset($_POST['texto_adicional_email_finalizada'])) {
        update_term_meta($term_id, 'texto_adicional_email_finalizada', wp_kses_post($_POST['texto_adicional_email_finalizada']));
    }

    if (isset($_POST['activar_texto_adicional_email_finalizada'])) {
        update_term_meta($term_id, 'activar_texto_adicional_email_finalizada', 1);
    }
    else{
        update_term_meta($term_id, 'activar_texto_adicional_email_finalizada', "");
    }
    


}
add_action('create_product_cat', 'save_custom_woocommerce_category_fields');
add_action('edited_product_cat', 'save_custom_woocommerce_category_fields');


// Mostrar los campos personalizados en el formulario de edición de la categoría existente
function edit_custom_woocommerce_category_fields($term, $taxonomy) {
    // Comprobar si estamos en la taxonomía correcta
    if ('product_cat' !== $taxonomy) {
        return;
    }

    echo '<style>
        /* Oculta la descripción */
        .term-description-wrap, .column-description {
            display: none;
        }

        /* Oculta la miniatura (imagen) */
        .term-thumbnail-wrap, .column-thumb {
            display: none;
        }

        /* Oculta el tipo de visualización */
        .term-display-type-wrap {
            display: none;
        }

        /* Oculta el slug */
        .form-field term-slug-wrap {
            display: none;
        }
    </style>';

    $nombre_sl_franquicia = get_term_meta($term->term_id, 'nombre_sl_franquicia', true);
    $direccion = get_term_meta($term->term_id, 'direccion', true);
    $telefono_contacto = get_term_meta($term->term_id, 'telefono_contacto', true);
    $email_contacto = get_term_meta($term->term_id, 'email_contacto', true);


    // $redsys_nombre_empresa = get_term_meta($term->term_id, 'redsys_nombre_empresa', true);
    // $redsys_numero_empresa = get_term_meta($term->term_id, 'redsys_numero_empresa', true);
    // $redsys_numero_terminal = get_term_meta($term->term_id, 'redsys_numero_terminal', true);
    // $redsys_clave256 = get_term_meta($term->term_id, 'redsys_clave256', true);


    $activar_promociones = get_term_meta($term->term_id, 'activar_promociones', true);
    $id_lista_brevo = get_term_meta($term->term_id, 'id_lista_brevo', true);

    $idiomas_reservas = get_term_meta($term->term_id, 'idiomas_reservas', true);


    $_db_cupones_name_ = get_term_meta($term->term_id, '_db_cupones_name_', true);
    $_db_cupones_ = get_term_meta($term->term_id, '_db_cupones_', true);

    

    $enlace_resenia_buena = get_term_meta($term->term_id, 'enlace_resenia_buena', true);
    $enlace_resenia_mala = get_term_meta($term->term_id, 'enlace_resenia_mala', true);
    
    $activar_texto_adicional_email_reserva = get_term_meta($term->term_id, 'activar_texto_adicional_email_reserva', true);
    $texto_adicional_email_reserva = get_term_meta($term->term_id, 'texto_adicional_email_reserva', true);

    $activar_texto_adicional_email_finalizada= get_term_meta($term->term_id, 'activar_texto_adicional_email_finalizada', true);
    $texto_adicional_email_finalizada = get_term_meta($term->term_id, 'texto_adicional_email_finalizada', true);

    $parent_term = get_term($term->parent, $taxonomy);

    if ($term->parent == 0) { 
    ?>
        <tr class="form-field">
            <th scope="row"><label for="nombre_sl_franquicia"><?php echo 'Nombre SL Franquicia'; ?></label></th>
            <td>
                <input type="text" name="nombre_sl_franquicia" id="nombre_sl_franquicia" value="<?php echo esc_attr($nombre_sl_franquicia); ?>">
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="direccion"><?php echo 'Dirección'; ?></label></th>
            <td>
                <input type="text" name="direccion" id="direccion" value="<?php echo esc_attr($direccion); ?>">
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="telefono_contacto"><?php echo 'Teléfono de contacto'; ?></label></th>
            <td>
                <input type="text" name="telefono_contacto" id="telefono_contacto" value="<?php echo esc_attr($telefono_contacto); ?>">
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="email_contacto"><?php echo 'Email de contacto'; ?></label></th>
            <td>
                <input type="email" name="email_contacto" id="email_contacto" value="<?php echo esc_attr($email_contacto); ?>">
            </td>
        </tr>



        <hr>



        <!-- <tr class="form-field">
            <th scope="row"><label for="redsys_nombre_empresa"><?php echo 'Nombre de empresa'; ?></label></th>
            <td>
                <input type="text" name="redsys_nombre_empresa" id="redsys_nombre_empresa" value="<?php //echo esc_attr($redsys_nombre_empresa); ?>">
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row"><label for="redsys_numero_empresa"><?php echo 'Número de empresa'; ?></label></th>
            <td>
                <input type="text" name="redsys_numero_empresa" id="redsys_numero_empresa" value="<?php //echo esc_attr($redsys_numero_empresa); ?>">
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row"><label for="redsys_numero_terminal"><?php echo 'Número de terminal'; ?></label></th>
            <td>
                <input type="text" name="redsys_numero_terminal" id="redsys_numero_terminal" value="<?php //echo esc_attr($redsys_numero_terminal); ?>">
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row"><label for="redsys_clave256"><?php echo 'Clave SHA256'; ?></label></th>
            <td>
                <input type="text" name="redsys_clave256" id="redsys_clave256" value="<?php //echo esc_attr($redsys_clave256); ?>">
            </td>
        </tr>



        <hr> -->



        <tr class="form-field">
            <th scope="row"><label for="activar_promociones"><?php echo 'Activar promociones'; ?></label></label></th>
            <td>
                <input type="checkbox" name="activar_promociones" id="activar_promociones" value="on" <?php echo $id_lista_brevo != '' ? "checked" : "" ?>>
                Habilitar para activar las promociones en esta ciudad. Es necesario que especificar el ID de la lista de Brevo
            </td>
        </tr>

        <tr class="form-field id_lista_brevo">
            <th scope="row"><label for="id_lista_brevo"><?php echo 'ID de la lista de Brevo (de esta ciudad)'; ?></label></th>
            <td>
            <input type="text" name="id_lista_brevo" id="id_lista_brevo" value="<?php echo esc_attr($id_lista_brevo); ?>">
            </td>
        </tr>
        

        <hr>

        
        <tr class="form-field">
            <th scope="row"><label for="_db_cupones_name_"><?php echo '(CUPONES) Nombre único de ciudad (slug)'; ?></label></th>
            <td>
                <input type="text" name="_db_cupones_name_" id="_db_cupones_name_" value="<?php echo esc_attr($_db_cupones_name_); ?>">
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row"><label for="_db_cupones_"><?php echo '(CUPONES) Base de datos de cupones'; ?></label></th>
            <td>
                <input type="text" name="_db_cupones_" id="_db_cupones_" value="<?php echo esc_attr($_db_cupones_); ?>" placeholder="cocoroom_cocoroomcake">
            </td>
        </tr>


        <hr>


        <tr class="form-field">
            <th scope="row"><label for="idiomas_reservas"><?php echo 'Idiomas en los que reservar (en esta ciudad). Formato [es,en,eu]'; ?></label></th>
            <td>
                <input type="text"  name="idiomas_reservas" id="idiomas_reservas" placeholder="es,en,eu" value="<?php echo esc_attr($idiomas_reservas); ?>">
            </td>
        </tr>


        
        <hr>

        

        <tr class="form-field">
            <th scope="row"><label for="activar_texto_adicional_email_reserva"><?php echo 'Activar texto adicional email reserva'; ?></label></label></th>
            <td>
                <input type="checkbox" name="activar_texto_adicional_email_reserva" id="activar_texto_adicional_email_reserva" value="on" <?php echo $activar_texto_adicional_email_reserva != '' ? "checked" : "" ?>>
                Habilitar para mostrar el siguiente texto en el email de la reservas.
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row" valign="top"><label for="texto_adicional_email_reserva"><?php echo 'Texto adicional email reserva'; ?></label></th>
            <td>
                <?php
                wp_editor($texto_adicional_email_reserva, 'texto_adicional_email_reserva', [
                    'textarea_name' => 'texto_adicional_email_reserva',
                    'media_buttons' => false,
                    'textarea_rows' => 5,
                    'teeny' => true,
                    'quicktags' => false
                ]);
                ?>
                <p class="description">Añade aquí texto adicional que quieres mencionar en los emails de esta franquicia (email de nueva reserva para el cliente)</p>
            </td>
        </tr>

        <hr>

        <tr class="form-field">
            <th scope="row"><label for="activar_texto_adicional_email_finalizada"><?php echo 'Activar texto adicional email de partida finalizada'; ?></label></label></th>
            <td>
                <input type="checkbox" name="activar_texto_adicional_email_finalizada" id="activar_texto_adicional_email_finalizada" value="on" <?php echo $activar_texto_adicional_email_finalizada != '' ? "checked" : "" ?>>
                Habilitar para mostrar el siguiente texto en el email de "partida finalizada" (similar al campo anterior).
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row" valign="top"><label for="texto_adicional_email_finalizada"><?php echo 'Texto adicional email de partida finalizada'; ?></label></th>
            <td>
                <?php
                wp_editor($texto_adicional_email_finalizada, 'texto_adicional_email_finalizada', [
                    'textarea_name' => 'texto_adicional_email_finalizada',
                    'media_buttons' => false,
                    'textarea_rows' => 5,
                    'teeny' => true,
                    'quicktags' => false
                ]);
                ?>
                <p class="description">Añade aquí texto adicional que quieres mencionar en los emails de esta franquicia (email de partida finalizada para el cliente)</p>
            </td>
        </tr>


        <script>
            jQuery(document).ready(function($) {

                $('#activar_promociones').change(function() {
                    evento_check_promociones($(this));
                });
                function evento_check_promociones($this){
                    if ($this.prop('checked')) {
                        $(".form-field.id_lista_brevo").show(); 
                    } else { 
                        $(".form-field.id_lista_brevo").hide();
                    }
                }
                evento_check_promociones($('#activar_promociones'));

            })

        </script>

    <?php
    } elseif ($parent_term && $parent_term->parent == 0) { 
    ?>
        <tr class="form-field">
            <th scope="row"><label for="direccion"><?php echo 'Dirección'; ?></label></th>
            <td>
                <input type="text" name="direccion" id="direccion" value="<?php echo esc_attr($direccion); ?>">
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="telefono_contacto"><?php echo 'Teléfono de contacto'; ?></label></th>
            <td>
                <input type="text" name="telefono_contacto" id="telefono_contacto" value="<?php echo esc_attr($telefono_contacto); ?>">
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="email_contacto"><?php echo 'Email de contacto'; ?></label></th>
            <td>
                <input type="email" name="email_contacto" id="email_contacto" value="<?php echo esc_attr($email_contacto); ?>">
            </td>
        </tr>

        <hr>

        <tr class="form-field">
            <th scope="row"><label for="enlace_resenia_buena"><?php echo 'Enlace de Google Maps para recibir la reseña'; ?></label></th>
            <td>
                <input type="text"  name="enlace_resenia_buena" id="enlace_resenia_buena" placeholder="" value="<?php echo esc_attr($enlace_resenia_buena); ?>">
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row"><label for="enlace_resenia_mala"><?php echo 'Enlace del formulario de contacto para evitar la reseña negativa'; ?></label></th>
            <td>
                <input type="text"  name="enlace_resenia_mala" id="enlace_resenia_mala" placeholder="" value="<?php echo esc_attr($enlace_resenia_mala); ?>">
            </td>
        </tr>
    <?php
    } elseif ($parent_term && $parent_term->parent != 0) { 
        // Es una Sala
        // Tus campos para Sala van aquí
    }

}
add_action('product_cat_edit_form_fields', 'edit_custom_woocommerce_category_fields', 10, 2);



function custom_admin_style() {
    global $current_screen;

    if ('edit-tags' === $current_screen->base && 'product_cat' === $current_screen->taxonomy) {
        echo '<style>
            /* Oculta la descripción */
            .term-description-wrap, .column-description {
                display: none;
            }

            /* Oculta la miniatura (imagen) */
            .term-thumbnail-wrap, .column-thumb {
                display: none;
            }

            /* Oculta el tipo de visualización */
            .term-display-type-wrap {
                display: none;
            }

            /* Oculta el slug */
            .form-field term-slug-wrap {
                display: none;
            }

            
        </style>';
    }
}
add_action('admin_head', 'custom_admin_style');





// Términos y Condiciones en la página de finalización de compra
add_action('woocommerce_review_order_before_submit', 'agregar_checkbox_terminos');
function agregar_checkbox_terminos() {
        echo '<p class="form-row terms">';
		woocommerce_form_field('acepto_terminos', array(
			'type' => 'checkbox',
			'class' => array('form-row-wide'),
			'label' => sprintf(__('Acepto los <a title="Términos y condiciones" href="%s" target="_blank">Términos y Condiciones</a>'), esc_url("https://cocoroom.es/terminos-condiciones/")),
			'required' => true,
    ), WC()->checkout->get_value('acepto_terminos'));
    echo '</p>';
}

// Validar el campo de verificación de Términos y Condiciones
add_action('woocommerce_checkout_process', 'validar_checkbox_terminos');

function validar_checkbox_terminos() {
    if (!isset($_POST['acepto_terminos']) || empty($_POST['acepto_terminos'])) {
        wc_add_notice(__('Debes aceptar los Términos y Condiciones para continuar.'), 'error');
    }
}


add_filter( 'woocommerce_checkout_fields' , 'add_custom_field_salas_cocoroom' );
function add_custom_field_salas_cocoroom( $fields ) {
    $fields['billing']['salas_realizadas'] = array(
        'type'          => 'select',
        'label'         => __('¿En cuántas salas habéis estado?', 'woocommerce'),
        'class'         => array('form-row-wide'),
		'options'       => array(
            0     => __('Nos estrenamos con esta!', 'woocommerce'), 
            2     => __('Un par, estamos verdes aun!', 'woocommerce'),
            5     => __('Entre 5 y 15 salas, opositando a escapistas!', 'woocommerce'),
            20     => __('Más de 20, tenemos carné escapista!', 'woocommerce'),
            50     => __('Más de 50, ya tenemos master escapista!', 'woocommerce'),
        ),
        'required' => true,
    );
    
    $fields['billing']['quiero_promociones'] = array(
        'type'          => 'checkbox',
        'label'         => __('Deseo recibir promociones', 'woocommerce'),
        'class'         => array('form-row-wide'),
        'checked'       => 'checked',
        'required' => false,
    );

    $fields['billing']['fecha_nacimiento'] = array(
        'type'          => 'date',
        'label'         => __('Fecha de nacimiento', 'woocommerce'),
        'class'         => array('form-row-wide contenedor_fecha_nacimiento'),
        'value'         => get_user_meta( get_current_user_id(), 'fecha_nacimiento', true ),
        'required' => false,
    );


    if (!is_user_logged_in()) { 
        $fields['billing']['aparecer_redes_sociales'] = array(
			'type'          => 'checkbox',
			'label'         => __('Acepto salir en redes sociales', 'woocommerce'),
			'class'         => array('form-row-wide'),
			'required' => false,
		);

        $fields['billing']['aceptar_monitorizacion'] = array(
			'type'          => 'checkbox',
			'label'         => __('Autorizo la monitorización (no grabación) durante el desarrollo de la actividad', 'woocommerce'),
			'class'         => array('form-row-wide'),
			'required' => true,
		);
	}








    $product_id = 461; // ID del producto a buscar
    $product_in_cart = false;
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];
        if ( $product->get_id() == $product_id ) {
            $product_in_cart = true;
            break; 
        }
    }
    if($product_in_cart){ 
        $fields['billing']['acepto_grandes_winers'] = array(
            'type'          => 'checkbox',
            'label'         => __('Acepto que mis datos sean compartidos con <i>Grandes Vinos</i>, empresa colaboradora del juego <i>Grandes Winers</i>', 'woocommerce'),
            'class'         => array('form-row-wide'),
            'checked'         => 'checked',
            'required' => true,
        );
    }




    $items = WC()->cart->get_cart();
    $id_ciudad = 0;
    if (!empty($items)) {
        $first_item = reset($items);
        $product_id = $first_item['product_id'];
        $id_ciudad = obtener_ubicacion_juego($product_id)["id"];
        $habilitar_redsys = get_term_meta($id_ciudad, 'habilitar_redsys', true);
        if (empty($habilitar_redsys)) {
            unset($available_gateways['redsys']);
        }
    }

    if($id_ciudad == 19){ 
        $fields['billing']['deseo_recibir_factura'] = array(
            'type'          => 'checkbox',
            'label'         => "Deseo recibir factura en formato electrónico (PDF)",
            'class'         => array('form-row-wide'),
            'checked'         => false,
            'required' => false,
        );
    }



    return $fields;
}


// Aparecer en redes sociales si el usuario no está logeado
add_action('woocommerce_review_order_before_submit', 'script_dinamicas_recibir_promociones');
function script_dinamicas_recibir_promociones() {
    ?>
    <script>
        $("#quiero_promociones").prop('checked', true);

        $("#quiero_promociones").off("change").on("change", function(){
            $(".contenedor_fecha_nacimiento").slideToggle(500);
        });
    </script>
    <?php
}



add_action( 'woocommerce_after_checkout_validation', 'quadlayers', 9999, 2);
function quadlayers( $fields, $errors ){
    if( !empty( $errors->get_error_codes() ) ) {

        foreach( $errors->get_error_codes() as $code ) {
            $errors->remove( $code );
        }
        $errors->add( 'validation', 'Por favor, comprueba tus datos personales.' );
    }
}


add_action( 'woocommerce_checkout_create_order', 'save_field_salas_cocoroom' );
function save_field_salas_cocoroom( $order ) {
    if (!empty($_POST['salas_realizadas'])){
        $order->update_meta_data( 'salas_realizadas', sanitize_text_field( $_POST['salas_realizadas'] ) );
    }

    if (!empty($_POST['quiero_promociones']) && !empty($_POST['fecha_nacimiento'])){
        $order->update_meta_data( 'quiero_promociones', "1" );
        $order->update_meta_data( 'fecha_nacimiento', sanitize_text_field( $_POST['fecha_nacimiento'] ) );
        $user_id = $order->get_user_id();
        if ($user_id != 0) {
            update_user_meta($user_id, 'fecha_nacimiento', sanitize_text_field( $_POST['fecha_nacimiento'] ));
        }
    }

    if (!empty($_POST['deseo_recibir_factura'])){
        $order->update_meta_data( 'deseo_recibir_factura', "true");
    }
}

function cambiar_textos_woocommerce( $translated_text, $text, $domain ) {
        if ( 'Lo siento, tu sesión ha caducado.' === $translated_text ) {
            $translated_text = '¡No has hecho ninguna reserva!';
        }
        else if ( 'Volver a la tienda' === $translated_text ) {
            $translated_text = 'Volver a Reservas';
        }
        else if ( 'Detalles de facturación' === $translated_text ) {
            $translated_text = 'Completa tus datos';
        }
        else if ( 'Producto' === $translated_text ) {
            $translated_text = 'Juego';
        }
        else if ( 'Tu pedido' === $translated_text ) {
            $translated_text = 'Tu reserva';
        }
        else if ( 'Detalles del pedido' === $translated_text ) {
            $translated_text = 'Detalles de la reserva';
        }
        else if ( 'Por favor, accede a tu cuenta para ver este pedido.' === $translated_text ) {
            $translated_text = 'Tu reserva ha sido procesada. Por favor, consulta el email de la reserva para ver los pasos de configuración de tu cuenta.';
        }
        else if ( 'Notas del pedido' === $translated_text ) {
            $translated_text = 'Observaciones de la reserva';
        }
        else if ( 'Notas sobre tu pedido, por ejemplo, notas especiales para la entrega.' === $translated_text ) {
            $translated_text = '¿Necesitamos saber algo sobre tu reserva?
Do you want to do the session in English? Let us know!';
        }
         
        


    return $translated_text;
}
add_filter( 'gettext', 'cambiar_textos_woocommerce', 20, 3 );



 
// Cambiar la dirección de correo electrónico del destinatario de un pedido completado

add_filter('woocommerce_email_headers', 'agregar_copia_oculta_a_correos', 10, 3);
function agregar_copia_oculta_a_correos($headers, $email_id, $order) {
    $items = $order->get_items();
    if (!empty($items)) {
        $first_item = reset($items);
        $id_ciudad = wc_get_order_item_meta( $first_item->get_id(), 'datos_reserva', true )["id_ciudad"];

        $args = array(
            'role' => 'franquicia',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'ciudad_usuario',
                    'value' => $id_ciudad,
                    'compare' => '='
                ),
                array(
                    'key' => '_is_dlt_user_',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => 'estado_usuario_',
                    'value' => 1,
                    'compare' => '='
                )
            )
        );

        $user_query = new WP_User_Query($args);

        // Obtener los usuarios con el rol "franquicia" y la misma ID de ciudad
        $franquicias = $user_query->get_results();

        // Agregar las direcciones de correo electrónico de las franquicias como copia oculta
        if (!empty($franquicias)) {
            $bcc_emails = array();
            foreach ($franquicias as $franquicia) {
                $bcc_emails[] = $franquicia->user_email;
            }

            // Agregar las direcciones a los encabezados como copia oculta (BCC)
            if (!empty($bcc_emails)) {
                $headers .= 'Bcc: ' . implode(', ', $bcc_emails) . "\r\n";
            }
        }
    }

    return $headers;
}





function redirigir_pagina_principal() {
    if (is_product_category() || is_product() || is_shop() || is_product_tag() || is_404()) {
        wp_redirect(home_url());
        exit();
    }

    if (is_cart()) {
        if (WC()->cart->is_empty()) 
            wp_redirect(home_url());
        else
            wp_redirect(wc_get_checkout_url());
        exit();
    }
}
add_action('template_redirect', 'redirigir_pagina_principal');

function redirigir_mi_cuenta() {
    if(is_page( "mi-cuenta" ))
        wp_redirect(home_url()."/panel/");
}
add_action('template_redirect', 'redirigir_mi_cuenta');



add_filter( 'woocommerce_order_item_permalink', '__return_false' );




function remove_woocommerce_order_actions() {
    if(get_current_user_id() != 1){
        remove_meta_box( 'woocommerce-order-actions', 'shop_order', 'side' );
    }
}
add_action( 'add_meta_boxes', 'remove_woocommerce_order_actions', 999 );




add_action('woocommerce_before_order_itemmeta', 'custom_display_product_metadata', 10, 3); // MOSTRAR EN ADMINISTRACION
function custom_display_product_metadata($item_id, $item, $product) {
    if ($product) {
        $order = $item->get_order();
        $datos_reserva = wc_get_order_item_meta( $item_id, 'datos_reserva', true );
        if ( is_array( $datos_reserva ) ) {
            $fecha_formateada = DateTime::createFromFormat('Y-m-d', $datos_reserva['fecha_reserva'])->format('d/m/Y');
            $direccion_local = get_term_meta($datos_reserva['id_local'], 'direccion', true);
            
            $detalles_reserva = "<div style='margin-left:24px'><b>Día ". $fecha_formateada . " a las " .$datos_reserva['hora'] ."</b><br> <b>Dirección: ". $direccion_local ."</b></div>";
            echo $detalles_reserva;


        }
    }
}




?>