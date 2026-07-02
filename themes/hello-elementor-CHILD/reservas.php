<?php 
/* Template Name: SP Admin RESERVAS */ 

if(isset($_GET['action']) && $_GET['action']=="reset"){
    WC()->cart->empty_cart();
    wp_redirect(home_url());
    exit();
}

WC()->cart->empty_cart();



if(isset($_GET['ciudad'])){

    get_header('reservas');
    

    $ciudad = $_GET['ciudad'];
    $term = get_term_by('slug', $ciudad, 'product_cat');

    $idiomas_reservas = get_term_meta($term->term_id, 'idiomas_reservas', true);

    if ($idiomas_reservas) {
        $idiomas_reservas = explode(",", $idiomas_reservas);
        if(count($idiomas_reservas)>1){
            echo '<style>';
            foreach (['en', 'es', 'eu'] as $lang) {
                if (!in_array($lang, $idiomas_reservas)) {
                    echo ".lang-item-$lang { display: none!important; }";
                }
            }
            echo '</style>';
        }
        else {
            echo "<style>
                #floating-button { display: none!important; }
            </style>";
        } 
    } else {
        echo "<style>
            #floating-button { display: none!important; }
        </style>";
    }


    if($term && $term->slug!='sin-especificar'){
        require get_template_directory() . '/boton_idiomas.php';
        require get_template_directory() . '/boton_volver.php';

        //PARA QUE ESTOS ESTILOS TENGAN TRADUCCIÓN
        echo '
        <style>
            label.check_content input.select_time + span.reserva_::before {
                content: "'.__("Reservar","hello-elementor-child").'";
            }
            label.check_content input.select_time:checked + span.reserva_::before {
                content: "'.__("Quitar","hello-elementor-child").'";
            }
        </style>
        ';
        ?>

        <header id="site-header" class="site-header" role="banner">
            <div class="site-branding">
                <h1 class="site-title">
                    <?= __("Reserva en","hello-elementor-child") ." ". $term->name?>
                </h1>
                
                <?php

                $all_ciudades = get_categories(array(
                    'taxonomy'   => 'product_cat',
                    'orderby'    => 'name',
                    'show_count' => 0,
                    'pad_counts' => 0,
                    'hierarchical' => 1,
                    'title_li'   => '',
                    'hide_empty' => 1,
                    'parent'    => 0
                ));
                
                if(count($all_ciudades) > 1){

                    ?>
                    <div id="contenedor_cambiar_ciudad">
                        <span class="numero_bloque"><i class="material-icons">arrow_back_ios</i></span>
                        <?php if (WC()->cart->is_empty()) { ?>
                        <p>
                            <?= __("¿Quieres reservar en otra ciudad?","hello-elementor-child"); ?> <a href='<?php echo esc_url( home_url( '/' ) ); ?>' title="<?php echo esc_attr__( 'Home', 'hello-elementor' ); ?>" rel="home"><?= __("Haz clic aquí.","hello-elementor-child"); ?></a>
                        </p>
                        <?php } else {?>
                        <p>
                            <?= __("¿Quieres reservar en otra ciudad?","hello-elementor-child"); ?> <a href='<?php echo esc_url( home_url( '/' ) ); ?>' onclick="return false;" id='vaciar_carrito' title="<?php echo esc_attr__( 'Home', 'hello-elementor' ); ?>" rel="home"><?= __("Haz clic aquí.","hello-elementor-child"); ?></a>
                        </p>
                        <?php } ?>
                    </div>

                    <?php

                }
                
                ?>
            </div>
        </header>

        <script>
            const ciudad = '<?php echo $ciudad;?>'
            const ciudad_nombre = '<?php echo $term->name;?>'

            <?php 
                if(isset($_GET['juego'])){
                ?>
                    const juego_seleccionado = <?php echo $_GET['juego'];?>;
                <?php 
                }
            ?>
        </script>

        <main id="content" class="site-main page type-page status-publish hentry">
            <div class="page-content">
                <div class="content_selec_user">

                    <div id="bloque_1">
                        <label class="tittle_3" for="num_usuarios"><span class="numero_bloque">1</span> <?= __("Número de participantes","hello-elementor-child"); ?> </label>
                        <div id="contenedor_input_num_usuarios">
                            <select name="num_usuarios" id="num_usuarios" autocomplete="off">
                                <option selected value disabled hidden><?= __("Selecciona el número de participantes","hello-elementor-child"); ?></option>
                            <?php 
                                if(isset($_GET['juego']) && $_GET['juego'] && is_numeric($_GET['juego'])){
                                    $numero_minimo_jugadores_juego_api = get_post_meta($_GET['juego'], 'numero_minimo_jugadores', true);
                                    $numero_maximo_jugadores_juego_api = get_post_meta($_GET['juego'], 'numero_maximo_jugadores', true);

                                    $todos_los_jugadores=false;
                                    if($numero_minimo_jugadores_juego_api && $numero_maximo_jugadores_juego_api)
                                        for($i=$numero_minimo_jugadores_juego_api; $i<=$numero_maximo_jugadores_juego_api; $i++){
                                            echo "<option value='".$i."'>".$i."</option>";
                                        }
                                    else
                                        $todos_los_jugadores=true;
                                }
                                else
                                    $todos_los_jugadores=true;
                                if($todos_los_jugadores){
                                    for($i=2; $i<=18; $i++){
                                        echo "<option value='".$i."'>".$i."</option>";
                                    }
                                
                                    if( $ciudad === "coco-room-sevilla" ) echo "<option value='30'>" . __( "Más de 18", "hello-elementor-child" ) . "</option>";
                                }
                            ?>
                            </select>
                        </div>
                    </div>

                    <div id="bloque_2" style="display:none">
                        <label class="tittle_3" for="fecha_reserva"><span class="numero_bloque">2</span> <?= __("Selecciona una fecha","hello-elementor-child"); ?></label>
                        <div id="fecha_reserva"></div>
                    </div>

                    <div id="bloque_3" style="display:none">
                        <h3 class="tittle_3"><span class="numero_bloque">3</span> <?= __("Escoge la habitación y horario","hello-elementor-child"); ?></h3>

                        <div id="contenedor_informacion">
                            <div id="informacion_texto"><b><?= __("¡IMPORTANTE!","hello-elementor-child"); ?></b>
                                <br><?= __('Vas a realizar una compra de una reserva. Puedes consultar los precios por jugador en el enlace de "Ver precios" de cada juego.',"hello-elementor-child"); ?>
                                <br><?= __("Es de vital importancia ser puntuales en vuestra reserva.","hello-elementor-child"); ?>
                                <br><?= __("Al elegir el juego prestad atención al espacio en el que se realiza la actividad.","hello-elementor-child"); ?>
                            </div>
                        </div>
                        <div id="contenedor_juegos"></div>
                        <div id="contenedor_mensaje_error">
                        </div>
                    </div>

                    <input type="button" style="display:none" id="btn_reservar" value="<?= __("Finalizar Reserva","hello-elementor-child"); ?>">
                    
                </div>
            <div>
        </main>
        <?php

        get_footer();

        return;
    }
}

//MOSTRAR UN SELECT CON LAS CIUDADES DISPONIBLES


$cart_items = WC()->cart->get_cart();
foreach ($cart_items as $cart_item) {
    if ($cart_item['id_ciudad']){

        $slug_ciudad = get_term_by('id', $cart_item['id_ciudad'], 'product_cat')->slug;
        wp_redirect(home_url()."?ciudad=".$slug_ciudad);
        exit();
    }
}


$all_ciudades = get_categories(array(
    'taxonomy'   => 'product_cat',
    'orderby'    => 'name',
    'show_count' => 0,
    'pad_counts' => 0,
    'hierarchical' => 1,
    'title_li'   => '',
    'hide_empty' => 1,
    'parent'    => 0
));



get_header('reservas');
require get_template_directory() . '/boton_idiomas.php';
require get_template_directory() . '/boton_volver.php';

?>

    <header id="site-header" class="site-header" role="banner">
        <div class="site-branding">
            <h1 class="site-title">
                <?php echo get_bloginfo( 'name' ); ?>
            </h1>
        </div>
    </header>

    <main id="content" class="site-main page type-page status-publish hentry">
        <div class="page-content" style="text-align:center">
            <select id="select_ciudades" autocomplete="off">
                <option value=""><?= __("Selecciona tu ciudad","hello-elementor-child"); ?></option>
                <?php
                foreach ($all_ciudades as $ciudad) {
                    if($ciudad->slug!='sin-especificar')
                        echo '<option value="'.$ciudad->slug.'">'.$ciudad->name.'</option>';
                }
                ?>
            </select>
        </div>
    </main>
    <script>

        document.getElementById('select_ciudades').addEventListener('change', function() {
            var select_ciudades = this.value;
            if (select_ciudades) {
                window.location.href = window.location.pathname + '?ciudad=' + select_ciudades;
            }
        });

    </script>

<?php

get_footer();

