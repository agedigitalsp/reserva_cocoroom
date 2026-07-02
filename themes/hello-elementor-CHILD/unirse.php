<?php /* Template Name: SP Admin UNIRSE A PARTIDA */ 

global $wpdb; 
$contenido = "";

if (!session_id()) {
    session_start();
}
if ( (isset($_GET['id']) && isset($_GET['key'])) || (isset($_SESSION['id']) && isset($_SESSION['key'])) ) {
    if ( isset($_GET['id']) && isset($_GET['key']) ) {
        $id  = sanitize_text_field($_GET['id']); 
        $key = sanitize_text_field($_GET['key']);
    }
    else{
        if ( isset($_SESSION['id']) && isset($_SESSION['key']) ) {
            $id  = $_SESSION['id']; 
            $key = $_SESSION['key'];            
        }
    }

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
            case 'finalizada':
            case 'cerrada':
                $error = true;
                $contenido = "<h1>¡Oh, vaya!</h1><h2>La reserva ya ha finalizado.</h2>";
            break;
            case 'activa':
                $error = true;
                $contenido = "<h1>¿Qué haces con el móvil?</h1><h2>¡La partida ha comenzado!</h2>";
            break;
            case 'reservada':
                if (is_user_logged_in()) { 
                    //USUARIO REGISTRADO ENTRA AL SISTEMA
                    $current_user = wp_get_current_user();
                    $user_id = $current_user->ID;  

                    $jugadores = json_decode($result->jugadores, true);

                    $direccion_local = get_term_meta($result->id_local, "direccion", true).".";
                    $fecha_reserva = date("d/m/Y", strtotime($result->fecha_reserva)).".";
                    $hora_reserva = date("H:i", strtotime($result->hora_reserva)).".";

                    $jugadores = array_filter($jugadores, function($jugador) use ($user_id, $user_email, &$error, &$contenido, &$direccion_local, &$fecha_reserva, &$hora_reserva) {
                        if ($jugador['ID'] == $user_id) {
                            $error = true;
                            $contenido = "<h1>¡Ya estás dentro de la partida!</h1><h2>Puedes cerrar esta página.</h2>";
                            $contenido .=  "<div id='info_partida'><h2>¡Recuerda!</h2>";
                            $contenido .=  "<br>¡Es de vital importancia ser puntuales!<br>";
                            $contenido .=  "<br>Dirección: ".$direccion_local;
                            $contenido .=  "<br>Fecha: ".$fecha_reserva;
                            $contenido .=  "<br>Hora: ".$hora_reserva;
                            //if(get_current_user_id()==1) $contenido .=  "<br><br>Has accedido ¿No eres tú? <a href='".home_url()."/cerrar-sesion/' title='Cerrar sesión'>Cerrar sesión</a>";
                            $contenido .=  "<br>";
                            $contenido .=  "</div>";
                            return false; // Excluir este jugador del array resultante
                        }

                        if ($jugador['ID'] == $user_id && $jugador['email'] == $user_email) {
                            $error = true;
                            $contenido = "<h1>¡Ya estás dentro de la partida!</h1><h2>Puedes cerrar esta página.</h2>";
                            $contenido .=  "<div id='info_partida'><h2>¡Recuerda!</h2>";
                            $contenido .=  "<br>¡Es de vital importancia ser puntuales!<br>";
                            $contenido .=  "<br>Dirección: ".$direccion_local;
                            $contenido .=  "<br>Fecha: ".$fecha_reserva;
                            $contenido .=  "<br>Hora: ".$hora_reserva;
                            //if(get_current_user_id()==1) $contenido .=  "<br><br>Has accedido ¿No eres tú? <a href='".home_url()."/cerrar-sesion/' title='Cerrar sesión'>Cerrar sesión</a>";
                            $contenido .=  "<br>";
                            $contenido .=  "</div>";
                            return false; // Excluir este jugador del array resultante
                        }
                        
                        if (strtolower($jugador['email']) == strtolower($user_email)) {
                            return false; // Excluir este jugador del array resultante
                        }
                        
                        return true; // Incluir este jugador en el array resultante
                    });
                    

                    if(!$error){

                        $jugadores[] = array(
                            'ID'       => $user_id,
                            'nombre'   => get_user_meta($user_id, 'first_name', true),
                            'apellidos' => get_user_meta($user_id, 'last_name', true),
                            'email'    => strtolower(wp_get_current_user()->user_email),
                            'telefono' => get_user_meta($user_id, 'billing_phone', true),
                        );

                        if (count($jugadores) <= $result->num_usuarios) {

                            $jugadores = array_values($jugadores); // Reindexa el array para asegurar índices secuenciales

                            $string_jugadores = json_encode($jugadores);
            
                            $resultado = $wpdb->update(
                                $wpdb->prefix."reservas",
                                ['jugadores' => $string_jugadores],
                                ['id_reserva' => $id, 'skey' => $key]
                            );

                            $contenido =  "<h1>¡Bienvenido!</h1><h2>Has sido añadido a la partida. Ya puedes cerrar esta página.</h2>";
                            $contenido .=  "<div id='info_partida'><h2>¡Recuerda!</h2>";
                            $contenido .=  "<br>¡Es de vital importancia ser puntuales!<br>";
                            $contenido .=  "<br>Dirección: ".$direccion_local;
                            $contenido .=  "<br>Fecha: ".$fecha_reserva;
                            $contenido .=  "<br>Hora: ".$hora_reserva;
                            // if(!isset($_SESSION['invitado_nuevo'])) $contenido .=  "<br><br>Has accedido ¿No eres tú? <a href='".home_url()."/cerrar-sesion/' title='Cerrar sesión'>Cerrar sesión</a>";
                            $contenido .=  "<br>";
                            $contenido .=  "</div>";
                            unset($_SESSION['id']);
                            unset($_SESSION['key']);
                            unset($_SESSION['action']);

                            if(isset($_SESSION['invitado_nuevo'])){
                                wp_delete_user( $user_id );
                                unset($_SESSION['invitado_nuevo']);
                            }

                            if(isset($_SESSION['invitado_registrado'])){
                                wp_logout();
                                unset($_SESSION['invitado_registrado']);
                            }

                        }
                        else{
                            $error = true;
                            $contenido = "<h1>¡Oh, vaya!</h1><h2>Se ha excedido el número de jugadores.</h2>";
                        }
                    }
                }
                else {
                    if (!session_id()) {
                        session_start();
                    }
                    $_SESSION['id']  = $id;
                    $_SESSION['key'] = $key;
                    $_SESSION['action'] = "unirse";

                    if(isset($_GET["invitado"])){
                        wp_redirect(home_url() . "/invitado/");
                        return;
                    }

                    if(isset($_GET["user"])){
                        $email_descodificado = base64_decode($_GET["user"]);
                        if (filter_var($email_descodificado, FILTER_VALIDATE_EMAIL)) {
                            if(email_exists($email_descodificado)){
                                wp_redirect(home_url() . "/iniciar-sesion/?user=".$_GET["user"]);
                            }
                            else{
                                wp_redirect(home_url() . "/registro/?user=".$_GET["user"]);
                            }
                        }
                    }
                    else{
                        wp_redirect(home_url() . "/acceder/");
                    }

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


?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unirse a la partida - Reservas Coco Room</title>



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
            padding: 32px;
            padding-bottom: 64px;
            text-align: center;
            font-weight: 400!important;
            font-size: 20px!important;
            color: #292929!important;
        }
        #info_partida h2{
            margin:0px!important
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

        <?php //get_footer();?> 

    </body>
</html>

