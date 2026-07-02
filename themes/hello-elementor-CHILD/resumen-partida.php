<?php /* Template Name: SP Admin RESUMEN PARTIDA */ 
$contenido = "";

global $wpdb; 

if ( (isset($_GET['id']) && isset($_GET['key']))) {
    if ( isset($_GET['id']) && isset($_GET['key']) ) {
        $id  = sanitize_text_field($_GET['id']); 
        $key = sanitize_text_field($_GET['key']);

        $sql = $wpdb->prepare(
            "SELECT * FROM " .$wpdb->prefix. "reservas WHERE id_reserva = %s AND skey = '%s'",
            $id,
            $key
        );

        $result = $wpdb->get_row($sql);

        if ( $result ) {
            switch ($result->estado) {
                case 'pendiente':
                    $error = true;
                    $contenido = "<h1>¡Oh, vaya!</h1><h2>La reserva todavía no ha sido pagada.</h2>";
                break;
                case 'cancelada':
                    $error = true;
                    $contenido = "<h1>¡Oh, vaya!</h1><h2>La reserva fue cancelada.</h2>";
                break;
                case 'reservada':
                case 'finalizada':
                    $error = true;
                    $contenido = "<h1>¡Oh, vaya!</h1><h2>La partida todavía no ha finalizado.</h2>";
                break;
                case 'activa':
                    $error = true;
                    $contenido = "<h1>¿Qué haces con el móvil?</h1><h2>¡La partida ha comenzado!</h2>";
                break;
                case 'cerrada':

                    $fecha_reservaObj = new DateTime($result->fecha_reserva);
                    $fecha_actual = new DateTime();
                    $diferencia_borrar_pagina = $fecha_actual->diff($fecha_reservaObj);
                    $dias_para_que_se_borre = 15; //CAMBIAR AQUÍ EL NÚMERO DE DÍAS QUE TIENEN QUE PASAR PARA BLOQUEAR EL ACCESO A UNA POSTPARTIDA

                    $mensaje_objetivo = "";
                    $tiempo_partida_equipos = array();

                    $modo_combate = get_post_meta($result->id_juego, 'modo_combate', true);
                    $modo_kids = get_post_meta($result->id_juego, 'modo_kids', true);
                    $ignorar_usuarios = $result->ignorar_usuarios;
                    

                    if($modo_combate!="1"){
                        if($result->objetivo_conseguido)
                            $mensaje_objetivo = get_post_meta($result->id_juego, 'texto_objetivo_conseguido', true);
                        else 
                            $mensaje_objetivo = get_post_meta($result->id_juego, 'texto_objetivo_fallido', true);
                    }
                    else{
                        $mensaje_objetivo = "¡Esperamos que lo hayáis disfrutado!";
                    }

                    if(($diferencia_borrar_pagina->days < $dias_para_que_se_borre) || (is_user_logged_in() && (current_user_can('administrator') || current_user_can('gamemaster') || current_user_can('franquicia')))) { //
                        $jugadores = json_decode($result->jugadores);

                        $fecha_reserva = DateTime::createFromFormat('Y-m-d', $result->fecha_reserva);
                        setlocale(LC_TIME, 'es_ES.UTF-8');
                        $fecha_reserva = strftime("%e de %B de %Y", $fecha_reserva->getTimestamp());
                        $num_equipos = count(explode(",",$result->id_salas));

                        $tiempo_partida = 0;
                        $participantes = "";

                        if($num_equipos >=2){
                            $fecha_hora_empezar_partida = json_decode($result->fecha_hora_empezar_partida, true);
                            $fecha_hora_finalizar_partida = json_decode($result->fecha_hora_finalizar_partida, true);

                            foreach ($fecha_hora_empezar_partida as $equipo => $empezar_partida) {
                                $finalizar_partida = isset($fecha_hora_finalizar_partida[$equipo]) ? $fecha_hora_finalizar_partida[$equipo] : "2016-06-17 02:43:00";

                                $fechaInicio = DateTime::createFromFormat('Y-m-d H:i:s', $empezar_partida);
                                $fechaFin = DateTime::createFromFormat('Y-m-d H:i:s', $finalizar_partida);
                                
                                if( !$fechaFin || $fechaInicio ){
                                    $fechaInicio = DateTime::createFromFormat('Y-m-d H:i:s', "2017-06-17 00:00:00");
                                    $fechaFin = DateTime::createFromFormat('Y-m-d H:i:s', "2017-06-17 02:43:00");
                                }

                                $diferencia = $fechaInicio->diff($fechaFin);

                                $tiempo_partida_equipos[$equipo] = $diferencia;
                            }




                            $equipoMasRapido = null;
                            $equipoMasLento = null;
                            $duracionMasRapido = null;
                            $duracionMasLento = null;
                            
                            foreach ($tiempo_partida_equipos as $equipo => $intervalo) {
                                // Calcula la duración total en minutos del intervalo para cada equipo
                                $duracionTotalMinutos = ($intervalo->s / 60) + $intervalo->i + ($intervalo->h * 60);
                            
                                // Compara si este equipo es más rápido que el equipo más rápido actual
                                if ($duracionMasRapido === null || $duracionTotalMinutos < $duracionMasRapido) {
                                    $duracionMasRapido = $duracionTotalMinutos;
                                    $equipoMasRapido = $equipo;
                                }
                            
                                // Compara si este equipo es más lento que el equipo más lento actual
                                if ($duracionMasLento === null || $duracionTotalMinutos > $duracionMasLento) {
                                    $duracionMasLento = $duracionTotalMinutos;
                                    $equipoMasLento = $equipo;
                                }
                            }
                            

                            


                            
                            $tiempo_partida = intval($duracionMasLento);
                            $menor_tiempo = intval($duracionMasRapido);
                            $equipo_ganador = $equipoMasRapido;

                            $equipos = json_decode($result->jugadores, true);
                            foreach ($equipos as $nombre_equipo => $jugadores) {
                                if($nombre_equipo == $equipo_ganador)
                                    $mensaje_puntuacion = "<!--<span style='margin-left:20px; color:#F5E165;'>¡Habéis ganado!</span>-->";
                                else 
                                    $mensaje_puntuacion = "<!--<span style='margin-left:20px; color:#aaa;'>Habéis perdido :(</span>-->";

                                $nombre_equipo_visible = str_replace("equipo","Equipo ",$nombre_equipo);

                                // Calcular la diferencia en minutos
                                $diferencia_minutos = ($tiempo_partida_equipos[$nombre_equipo]->days * 24 * 60) + ($tiempo_partida_equipos[$nombre_equipo]->h * 60) + $tiempo_partida_equipos[$nombre_equipo]->i;
                                $tiempo_partida_equipo = "<!--<span style='float: right;'>".$diferencia_minutos." minutos</span>-->";
                                
                                $participantes .= "<tr><td><h4>" . $nombre_equipo_visible . " " . $mensaje_puntuacion . " " . $tiempo_partida_equipo . "</h4></td></tr>";
                                

                                foreach ($jugadores as $jugador) {
                                    if(!empty($jugador["nombre"])){
                                        $aceptar_redes_sociales = "";
                                        if(is_user_logged_in() && (current_user_can('administrator') || current_user_can('gamemaster') || current_user_can('franquicia')))
                                            $aceptar_redes_sociales = get_user_meta($jugador["ID"],"aceptar_redes_sociales", true) ? " (Acepta salir en redes)" : " (NO acepta salir en redes)" ;
                                        $participantes .= "<tr><td>" . $jugador["nombre"] . " " . $jugador["apellidos"] . $aceptar_redes_sociales . "</td></tr>";
                                    }
                                }

                            }
                        }
                        else{
                            $fechaInicio = DateTime::createFromFormat('Y-m-d H:i:s', $result->fecha_hora_empezar_partida);
                            $fechaFin = DateTime::createFromFormat('Y-m-d H:i:s', $result->fecha_hora_finalizar_partida);

                            if( !$fechaFin || $fechaInicio ){
                                $fechaInicio = DateTime::createFromFormat('Y-m-d H:i:s', "2017-06-17 00:00:00");
                                $fechaFin = DateTime::createFromFormat('Y-m-d H:i:s', "2017-06-17 02:43:00");
                            }

                            $diferencia = $fechaInicio->diff($fechaFin);
                            $tiempo_partida = ($diferencia->days * 24 * 60) + ($diferencia->h * 60) + $diferencia->i;



                            $jugadores = json_decode($result->jugadores, true);
                            foreach ($jugadores as $jugador) {
                                if(!empty($jugador["nombre"])){
                                    $aceptar_redes_sociales = "";
                                    if(is_user_logged_in() && (current_user_can('administrator') || current_user_can('gamemaster') || current_user_can('franquicia')))
                                        $aceptar_redes_sociales = get_user_meta($jugador["ID"],"aceptar_redes_sociales", true) ? " (Acepta salir en redes)" : " (NO acepta salir en redes)" ;
                                    $participantes .= "<tr><td>" . $jugador["nombre"] . " " . $jugador["apellidos"] . $aceptar_redes_sociales . "</td></tr>";
                                }
                            }
                        }

                        $enlace_resenia_buena = get_term_meta($result->id_local, 'enlace_resenia_buena', true) ? get_term_meta($result->id_local, 'enlace_resenia_buena', true) : "#";
                        $enlace_resenia_mala =  get_term_meta($result->id_local, 'enlace_resenia_mala', true) ? get_term_meta($result->id_local, 'enlace_resenia_mala', true) : "#";


                        if($result->enviar_email_resenia != 1)
                            $enlaces_resenia ='';
                        else
                            $enlaces_resenia ='<div class="rate-box">
                                <p>Valora tu experiencia</p>
                                <div>
                                    
                                    <a target="_blank" title="Reseña positiva" href="'.$enlace_resenia_buena.'" class="btn-rate"><svg xmlns="http://www.w3.org/2000/svg" height="32" viewBox="0 -960 960 960" width="24"><path d="M620-520q25 0 42.5-17.5T680-580q0-25-17.5-42.5T620-640q-25 0-42.5 17.5T560-580q0 25 17.5 42.5T620-520Zm-280 0q25 0 42.5-17.5T400-580q0-25-17.5-42.5T340-640q-25 0-42.5 17.5T280-580q0 25 17.5 42.5T340-520Zm140 260q68 0 123.5-38.5T684-400h-66q-22 37-58.5 58.5T480-320q-43 0-79.5-21.5T342-400h-66q25 63 80.5 101.5T480-260Zm0 180q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Z"/></svg></a>
                                </div>
                            </div>';

                        //<a target="_blank" title="Reseña negativa" href="'.$enlace_resenia_mala.'" class="btn-rate bad"><svg xmlns="http://www.w3.org/2000/svg" height="32" viewBox="0 -960 960 960" width="24"><path d="M620-520q25 0 42.5-17.5T680-580q0-25-17.5-42.5T620-640q-25 0-42.5 17.5T560-580q0 25 17.5 42.5T620-520Zm-280 0q25 0 42.5-17.5T400-580q0-25-17.5-42.5T340-640q-25 0-42.5 17.5T280-580q0 25 17.5 42.5T340-520Zm140 100q-68 0-123.5 38.5T276-280h66q22-37 58.5-58.5T480-360q43 0 79.5 21.5T618-280h66q-25-63-80.5-101.5T480-420Zm0 340q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Z"/></svg></a>

                            
                        $contenido = '
                        <div class="content_pg">    
                            <header class="header_pg">
                                <p>Resumen de la partida</p>
                                <p style="text-align:right">Esta página se borrará en <span class="time_pg">'.($dias_para_que_se_borre - $diferencia_borrar_pagina->days).' día'.(($dias_para_que_se_borre - $diferencia_borrar_pagina->days)==1?'':'s').'</span></p>
                            </header>  
                            <div class="logo_container">
                                <img class="logo-login" src="'.esc_url(home_url('/')).'wp-content/uploads/2023/08/LogoCOCOROOM.png" alt="logo cocoroom">
                            </div>         
                        <div class="sec_1">

                            <h1 class="tittle_principal">¡Gracias por visitarnos!</h1>
                            <p class="msg_1">'. $mensaje_objetivo .'</p>
                        '.$enlaces_resenia.'
                        
                        </div>
                        <hr class="hr_"/>
                    
                        <div class="sec_2">
                            <h2 class="tittle_secon">Tu partida</h2>
                            <h3 class="tittle_3">Juego</h3>
                            <p class="info">'. $result->nombre_juego .'</p>
                        </div>
                        
                        <div class="sec_2_inf">
                            <div>
                                <h3 class="tittle_3">Fecha</h3>
                                <p class="info">'. $fecha_reserva .'</p>
                            </div>
                            <!--<div>
                                <h3 class="tittle_3">Tiempo de juego</h3>
                                <p class="info">'. $tiempo_partida .' minutos</p>
                            </div>-->
                            <div style="text-align:right">
                                <h3 class="tittle_3">Tiempo de juego</h3>
                                <p class="info">'. get_post_meta($result->id_juego, 'duracion_estimada', true) .' minutos</p>
                            </div>
                        </div>
                        ';

                        if($result->fotografia && $result->aceptan_hacerse_foto)
                            $contenido .='
                            <div class="sec_2_img">
                                <h3 class="tittle_3">Foto de los jugadores</h3>
                                <img src="'.home_url()."/".$result->fotografia.'" alt="Foto de los jugadores">
                                <button id="btn_descarga_foto" class="btn_descarga_foto">Descargar foto</button>
                            </div>
                            <script>
                                document.getElementById("btn_descarga_foto").addEventListener("click", function() {
                                    let a = document.createElement("a");
                                    a.href = "'.home_url()."/".$result->fotografia.'";
                                    a.download = "foto-cocoroom-'.$result->fecha_reserva.'-'.$result->hora_reserva.'.jpg";
                                    a.click();
                                });
                            </script>';
                            
                        if($modo_kids != "1" && $ignorar_usuarios != 1)
                            $contenido .='<div class="sec_3">
                                <h2 class="tittle_secon">Participantes</h2>
                                <table>
                                    <tbody>'. $participantes .'</tbody>
                                </table>
                            </div>';

                        $contenido .='
                        <footer class="footer_pg">
                            <a class="link_footer" href="https://cocoroom.es/contacto/" target="_blank" title="Contacto">Contacto</a>
                            <a class="link_footer" href="https://cocoroom.es/promociones/" target="_blank" title="promociones">Promociones</a>
                            <a class="link_footer" href="https://cocoroom.es/aviso-legal/" target="_blank" title="Aviso legal">Aviso legal</a>
                            <a class="link_footer" href="https://cocoroom.es/terminos-condiciones/" target="_blank" title="Terminos y condiciones">Terminos y condiciones</a>
                            <a class="link_footer" href="https://cocoroom.es/cookies/" target="_blank" title="Politicas de cookies">Politicas de cookies</a>
                        </footer>
                    </div>';

                    

                    }
                    else{
                        $error = true;
                        $contenido = "<h1>¡Oh, vaya!</h1><h2>Hace demasiado tiempo de la partida.</h2>";
                    }
                break;
            }
        }
        else {
            $error = true;
            $contenido = "<h1>¡Oh, vaya!</h1><h2>Los datos de la reserva no son correctos.</h2>";
        }
    }
    else{
        $error = true;
        $contenido = "<h1>¡Oh, vaya!</h1><h2>No hay ninguna reserva disponible.</h2>";
    }
}
else{
    $error = true;
    $contenido = "<h1>¡Oh, vaya!</h1><h2>No hay ninguna reserva disponible.</h2>";
}


?>





<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Resumen de la partida - Reservas Coco Room</title>


        <script src='/wp-includes/js/jquery/jquery.min.js?ver=3.6.4' id='jquery-core-js'></script>
        <script src='/wp-includes/js/jquery/jquery-migrate.min.js?ver=3.4.0' id='jquery-migrate-js'></script>
        <link rel="stylesheet" type="text/css" href="/wp-content/plugins/admin_panel_system/app-assets/vendors/sweetalert/sweetalert2.min.css">
        <script src="/wp-content/plugins/admin_panel_system/app-assets/vendors/sweetalert/sweetalert2.all.min.js"></script>


        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&display=swap" rel="stylesheet">
        <style>
            body{
                background: #292929;
                font-family: "Poppins",sans-serif;
                margin: 0;
                padding: 0;
            }

            #contenedor{
                background: #292929;
                margin: auto;
                /* top: 50%;
                left: 50%; */
                /* transform: translate(-50%, -50%); */
                /* position: absolute; */
                /*width: calc(100% - 400px);*/
                padding: 32px;
                /*box-shadow: 0px 0px 20px 0px #0005;*/
            }

            h1{
                color: #fff;
                text-align:center;
                font-size: 60px;
            }

            h2{
                color: #fff;
                text-align:center;
                font-size: 40px;
            }
            
            h4{
                margin: 0!important;
                margin-left: -30px!important;
            }

            #contenedor_inputs{
                background-color: white;
                margin: -32px;
                margin-top: 0px;
            }

            #contenedor_inputs *{
                font-weight: 400!important;
                font-size: 20px!important;
                color: #292929!important;
            }

            #contenedor_inputs table{
                width: 100%;
            }

            #contenedor_inputs table tr td{
                padding: 20px;
            }

            button{
                border-radius: 6px;
                background: #F5E165;
                padding: 10px;
                color: black;
                border: 2px solid;
                font-weight: 600!important;
                margin: auto;
            }

            .tittle_principal{
                font-family: Poppins;
                font-weight: 700;
                font-size: 50px;
                margin-bottom: -20px;
                color: #fff;

            }
            .sec_1 {
                display: flex;
                flex-direction: column;
            }

            p.msg_1 {
                text-align: center;
                margin-bottom: 25px;
                font-size: 24px;
                color: #fff;

            }
            h3.tittle_3 {
                font-size: 15px;
                /* margin-bottom: -25px; */
                margin-top: 25px;
                margin-bottom: 5px;
                line-height: 0px;
                color: #aaaaaa;

            }
            h2.tittle_secon {
                display: flex;
                font-size: 24px;
                color: #fff;
            }

            p.info {
                font-size: 24px;
                color: #fff;
                line-height: 14px;

            }
            button.btn_vlr {
                background: transparent;
                width: 199px;
                height: 40px;
                border-radius: 3px;
                border: 1px solid white;
                padding: 8px 12px 8px 12px;
                font-size: 16px;    
                margin-bottom: 50px;
                color: #fff;
            }
            button.btn_vlr:hover{
                border: 1px solid #F5E165;
                color: #F5E165;
                cursor: pointer;
            }

            header.header_pg {
                display: flex;
                justify-content: space-between;
                color: #F5E165;

            }
            .sec_2_inf {
                display: flex;
                justify-content: space-between;
            }
            .img_equipo {
                display: flex;
                justify-content: center;
                max-width: 1280px;;
                /* width: 636px; */
                height: 40px;
            }
            .content_pg {
                /* width: 636px; */
                max-width: 1280px;
                margin: auto;
            }
            .sec_2_img {
                display: flex;
                flex-direction: column;
            }

            .sec_2_img img {
                border: 1px solid #F5E165;
                width: 100%;
                max-width: 636px;
                margin: 20px;
                align-self: center;
            }

            button.btn_descarga_foto {
                max-width: 636px;
                width: 100%;
                height: 40px;
                border-radius: 3px;
                border: 1px solid;
                padding: 8px 12px 8px 12px;
                font-size: 16px;
                margin-bottom: 50px;
                color: #292929;
            }
            button.btn_descarga_foto:hover{
                border: 1px solid #F5E165;
                color: #F5E165;
                background: transparent;
                cursor: pointer;
            }
            thead{
                font-size: 16px;
                color: #aaaaaa;
            }
            tbody {
                color: white;
                font-size: 16px
            }

            /* Estilo para la tabla */
            table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            }

            /* Estilo para las celdas del encabezado */
            th {
            border-bottom: 1px solid gray;
            text-align: left;
            padding: 8px;
            }

            /* Estilo para las celdas del cuerpo de la tabla */
            td {
            border-bottom: 1px solid gray;
            text-align: left;
            padding: 8px;
            padding-left: 30px;
            }

            footer.footer_pg {
                display: flex;
                justify-content: space-around;
                margin-top: 50px;
                border-top: 1px solid gray;
                padding-top: 15px;
            }

            footer.footer_pg a {
                color: #fff;
                text-decoration: none;
            }
            a.link_footer {
                font-size: 16px;
                width: 20%;
                color: white;
            }
            img.logo-login {
                width: 244px;
            }
            .logo_container {
                display: flex;
                justify-content: center;
            }
            @media screen and (max-width: 768px) {
                #contenedor{
                    width: calc(100% - 100px);
                }
                h1{
                    font-size: 40px;
                }
                h2{
                    font-size: 28px;
                }

                .sec_2_inf div{
                    width:100%;
                }
                .sec_2_inf{
                    flex-direction:column;
                }

                #contenedor_inputs *{
                    font-size: 14px!important;
                }

                .ocultar_movil{
                    display:none;
                }


                footer.footer_pg {
                    flex-direction: column;
                    gap: 24px
                }
                footer.footer_pg a {
                    width: 100%;
                }
                
            }

            /* Iconos de reseñas */
            .rate-box{
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-bottom: 18px;
            }

            .rate-box p{
                text-align: center !important;
                color: white !important;
                font-size: 24px !important;
                margin:0px !important;
            }

            .rate-box div{
                display: flex;
                flex-direction: row;
                align-items: center;
                gap: 20px;
                margin:8px;
            }

            .btn-rate{
                background: #F5E165;
                display: flex;
                width: 48px;
                aspect-ratio: 1;
                justify-content: center;
                align-items: center;
                border-radius: 50%;
                fill:#292929;
                transition:transform 0.2s, background 0.2s;
            }

            .btn-rate.bad{
                background: #981e30;
                fill:white;
            }
            .btn-rate.bad:hover svg{
                fill:#292929!important;
            }

            .btn-rate svg{
                transform: scale(1.2);
                transition:transform 0.2s, fill 0.2s
            }

            .btn-rate:hover{
                transform: scale(1.05);
                background: #fff;
            }
            .btn-rate:hover svg{
                transform: scale(1.4);
            }

            .btn-rate:active{
                transform: scale(0.95);
                background: #F5E165;
            }
            .btn-rate:active svg{
                transform: scale(1);
            }

            .btn-rate.bad:active{
                background: #981e30!important;
            }

            .btn-rate.bad:active svg{
                fill: white!important
            }
            
        </style>
    </head>

	<body>
        <div id="contenedor">
            <?php echo $contenido?>
        </div>
        
        
    </body>
</html>

