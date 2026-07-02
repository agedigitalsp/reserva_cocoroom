<?php /* Template Name: SP Admin CANCELAR RESERVA */ 

global $wpdb; 
$contenido = "";

if ( (isset($_GET['id']) && isset($_GET['key']))) {
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
            // case 'pendiente':
            //     $contenido = "<h1>¡Oh, vaya!</h1><h2>La reserva todavía no ha sido pagada.</h2>";
            // break;
            case 'cancelada':
                $contenido = "<h1>¡Hecho!</h1><h2>La reserva está cancelada.</h2>";
            break;
            case 'finalizada':
            case 'cerrada':
                $contenido = "<h1>¡Oh, vaya!</h1><h2>La reserva ya ha finalizado.</h2>";
            break;
            case 'activa':
                $contenido = "<h1>¿Qué haces con el móvil?</h1><h2>¡La partida ha comenzado!</h2>";
            break;
            case 'reservada':
            case 'pendiente':
                $nombre_usuario = $result->nombre_usuario;
                $apellidos_usuario = $result->apellidos_usuario;
                $email_usuario = $result->email_usuario;
                $telefono_usuario = $result->telefono_usuario;
                $nombre_juego = get_the_title($result->id_juego);
                $id_juego = $result->id_juego;
                $telefono_contacto_franquicia = get_term_meta($id_ciudad, 'telefono_contacto', true);
                $email_contacto_franquicia = get_term_meta($id_ciudad, 'email_contacto', true);
                $id_local = $result->id_local;
                $fecha_reserva = $result->fecha_reserva;
                $direccion_local = get_term_meta($id_local, 'direccion', true);
                $enlace_local = get_term_meta($id_local, 'enlace_resenia_buena', true);
                $fecha_formateada = DateTime::createFromFormat('Y-m-d', $fecha_reserva)->format('d/m/Y');
                $hora_reserva = date("H:i", strtotime($result->hora_reserva)).".";

                $num_usuarios = (int)$result->num_usuarios;
                $lista_precios_raw = get_post_meta($id_juego, 'lista_precios', true);
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

                $href = home_url("/") . "cancelar-reserva/?id=" . $id . "&key=" . $key . "&confirmar=1";
                $href_retry = home_url("/") . "cancelar-reserva/?id=" . $id . "&key=" . $key;

                $timestamp_reserva = strtotime($result->fecha_reserva . " " . $result->hora_reserva);
                $limite_cancelacion = strtotime('+2 days');

                if ($timestamp_reserva >= $limite_cancelacion) {
                    if(isset($_GET['confirmar'])){

                        $resultado_cancelacion = $wpdb->update(
                            $wpdb->prefix."reservas",
                            ['estado' => "cancelada"],
                            ['id_reserva' => $id, 'skey' => $key]
                        );

                        if($resultado_cancelacion){
                            $id_gamemaster = $result->id_gamemaster;
                            $email_usuario = $result->email_usuario;

                            $telefono_contacto_franquicia = get_term_meta($result->id_ciudad, 'telefono_contacto', true);
                            $email_contacto_franquicia = get_term_meta($result->id_ciudad, 'email_contacto', true);
                            $nombre_sl_franquicia = get_term_meta($result->id_ciudad, 'nombre_sl_franquicia', true);    
                            $enlace_local = get_term_meta($result->id_ciudad, 'enlace_resenia_buena', true);    
                            $direccion_local = get_term_meta($result->id_ciudad, 'direccion', true);    

                            $cupon_descuento = $result->cupon_descuento;
                            $id_ciudad = $result->id_ciudad;
                            $id_local = $result->id_local;
                            $fecha_reserva = $result->fecha_reserva;
                            $fecha_formateada = DateTime::createFromFormat('Y-m-d', $fecha_reserva)->format('d/m/Y');

                            $data = array(
                                'Nombre' => $result->nombre_usuario,
                                'Apellidos' => $result->apellidos_usuario,
                                'Telefono' => $result->telefono_usuario,
                                'Email' => $result->email_usuario,
                
                                "ID_reserva" => $result->id_reserva,
                                "Num_jugadores_reserva" => (int)$result->num_usuarios,
                                "Fecha_reserva" => $fecha_formateada,
                                "Hora_reserva" => $result->hora_reserva,
                                "Nombre_juego" => $result->nombre_juego,
                                "Detalle_jugadores" => $detalle_jugadores,

                                "Cupon_descuento" => $result->cupon_descuento,
                
                                "Email_contacto_franquicia" => $email_contacto_franquicia,
                                "Telefono_contacto_franquicia" => $telefono_contacto_franquicia,
                                
                                "Enlace_local" => $enlace_local,
                                "Direccion_local" => $direccion_local,
                            );

                            do_action('send_mails_adpnsy_cllbck', $email_contacto_franquicia, 'Una reserva de COCOROOM ha sido cancelada', 'reserva_cancelada_franquicia', $data);
                            do_action('send_mails_adpnsy_cllbck', $email_usuario, 'Tu reserva ha sido cancelada', 'reserva_cancelada_cliente', $data);

                            if($id_gamemaster){
                                $gamemaster_ids = explode(",", $id_gamemaster);
                                if (count($gamemaster_ids) > 1) {
                                    $gamemaster1_info = get_userdata($gamemaster_ids[0]);
                                    $gamemaster2_info = get_userdata($gamemaster_ids[1]);

                                    $email_gamemaster1 =  $gamemaster1_info->user_email;
                                    $nombre_gamemaster1 = $gamemaster1_info->display_name;

                                    $email_gamemaster2 =  $gamemaster2_info->user_email;
                                    $nombre_gamemaster2 = $gamemaster2_info->display_name;

                                    do_action('send_mails_adpnsy_cllbck', $email_gamemaster1, 'Una reserva de COCOROOM ha sido cancelada', 'reserva_cancelada_gamemaster', $data);
                                    do_action('send_mails_adpnsy_cllbck', $email_gamemaster2, 'Una reserva de COCOROOM ha sido cancelada', 'reserva_cancelada_gamemaster', $data);
                                } else {
                                    $gamemaster_info = get_userdata($gamemaster_ids[0]);

                                    $email_gamemaster =  $gamemaster_info->user_email;
                                    $nombre_gamemaster = $gamemaster_info->display_name;

                                    do_action('send_mails_adpnsy_cllbck', $email_gamemaster, 'Una reserva de COCOROOM ha sido cancelada', 'reserva_cancelada_gamemaster', $data);
                                }
                            }

                            $contenido = "<h1>¡Hecho!</h1><h2>La reserva ha sido cancelada correctamente.</h2>";
                            $contenido .=  "<div id='info_partida'>";
                            $contenido .=  "¡Esperamos verte pronto!";
                            $contenido .=  "</div>";
                        }else{
                            $contenido = "<h1>¡Oh, vaya!</h1><h2>Ha habido un problema con la cancelación de tu reserva.</h2>";
                            $contenido .=  "<div id='info_partida'>";
                            $contenido .=  "<br>";
                            $contenido .=  "<a href=" .$href_retry. " title='Intentar de nuevo'><b>Vuelve a intentarlo</b></a>";
                            $contenido .=  "<br>";
                            $contenido .=  "</div>";
                        }
                    }
                    else{
                        $contenido = "<h1>¡Cuidado!</h1><h2>¿Seguro que quieres cancelar tu reserva?</h2>";
                        $contenido .=  "<div id='enlace_cancelar_reserva'>";
                        $contenido .=  "<a href=" .$href. " title='Cancelar reserva'><b>Haz clic aquí para cancelar tu reserva definitivamente</b></a>";
                        $contenido .=  "</div>";
                        $contenido .=  "<div id='info_partida'>";
                        $contenido .=  "Dirección: ".$direccion_local;
                        $contenido .=  "<br>Juego: ".$nombre_juego;
                        $contenido .=  "<br>Fecha: ".$fecha_reserva;
                        $contenido .=  "<br>Hora: ".$hora_reserva;
                        $contenido .=  "<br>";
                        $contenido .=  "</div>";
                    }
                }
                else{
                    $contenido = "<h1>¡No puedes cancelar la reserva!</h1><h2>Por favor, contacta con nosotros para cancelaciones de última hora</h2>";
                    $contenido .=  "<div id='info_partida'>";
                    $contenido .=  "<br>Número de teléfono: <a title='Teléfono de contacto' href='tel:" . $telefono_contacto_franquicia. "'>" . $telefono_contacto_franquicia . "</a>";
                    $contenido .=  "<br>Correo electrónico: <a title='Email de contacto' href='mailto:" . $email_contacto_franquicia. "'>" . $email_contacto_franquicia . "</a>";
                }
            break;
        }
    }
    else {
        $contenido = "<h1>¡Oh, vaya!</h1><h2>Los datos de la reserva no son correctos. El enlace utilizado no es válido.</h2>";
    }
}
else{
    $contenido = "<h1>¡Oh, vaya!</h1><h2>No hay ninguna reserva disponible.</h2>";
}

?>





<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelar Reserva - Reservas Coco Room</title>



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
            background: #f5e264;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            position: absolute;
            width: calc(100% - 400px);

            padding: 32px;
            box-shadow: 0px 0px 20px 0px #0005;
        }

        h1{
            color: #292929;
            text-align:center;
            font-size: 60px;
        }

        h2{
            color: #292929;
            text-align:center;
            font-size: 40px;
        }

        #info_partida{
            background-color: white;
            margin: -32px;
            margin-top: 0px;
            padding: 64px 32px;
            text-align: center;
            font-weight: 400!important;
            font-size: 20px!important;
            color: #292929!important;
        }
        #info_partida h2{
            margin:0px!important
        }

        #enlace_cancelar_reserva{
            text-align: center;
            background: #d80a25;
            padding: 32px;
            max-width: 780px;
            margin: auto;
            margin-bottom: 32px;
        }

        #enlace_cancelar_reserva a{
            color: white;
        }

        #logo_cocoroom{
            width:100px;
            position: fixed;
            top: 24px;
            left: 24px;
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
            #info_partida {
                font-size: 14px!important;
            }

            #logo_cocoroom{
                left: 50%;
                transform: translateX(-50%);
            }
        }
        
    </style>
    </head>

	<body>
        <div id="contenedor">
            <?php echo $contenido?>
        </div>

        <img id="logo_cocoroom" src="<?php echo esc_url(home_url('/'))?>wp-content/uploads/2023/08/LogoCOCOROOM.png" alt="Logotipo Coco Room">

    </body>
</html>

