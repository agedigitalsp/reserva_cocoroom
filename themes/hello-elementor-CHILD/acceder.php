<?php 
/* Template Name: SP Admin ACCEDER */ 

// if (isset($_SESSION['id']) && isset($_SESSION['key']) && isset($_SESSION['action'])) {  //SI EXISTEN LAS VARIABLES DE SESIÓN DE LA PARTIDA SE REDIRIGE A LA PAGINA DE UNIRSE
//     wp_redirect(home_url() . "/".$_SESSION['action']."/?id=" . $_SESSION['id'] . "&key=" . $_SESSION['key']);
//     exit;
// }



if (is_user_logged_in()) {
    // Obtener los datos del usuario actual
    $user_data = wp_get_current_user();
    $user_roles = $user_data->roles;  // Array de roles del usuario

    if (in_array('subscriber', $user_roles)) {
        // Comprobar si las páginas actuales no son 'mis-reservas' ni 'perfil'
        if (!is_page("mis-reservas") && !is_page("perfil")) {
            wp_redirect(home_url("/mis-reservas/"));
            exit;
        }
    } else {
        // Redireccionar a la página del panel si no es suscriptor
        wp_redirect(home_url("/panel/"));
        exit;
    }
}


?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceder - Reservas Coco Room</title>


    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

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
            margin: 0;
            padding: 24px 0px;
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: space-evenly;
            gap: 24px;
            min-height: calc(100vh - 48px);
            min-width: calc(100vw);
        }

        .contenedor_icono{
            width: 264px;
            height: 264px;
            color: #292929;
            border-radius: 50%;
            background-color: #F5E165;
            display:flex;
            text-align:center;
            flex-direction: column;
            flex-wrap: nowrap;
            justify-content: center;
            transition: transform 0.2s, background-color 0.2s;
        }

        i{
            font-size: 96px!important;
        }

        a{
            background:none!important;
            text-decoration:none!important;
        }

        #logo_cocoroom{
            width:100px;
            position: fixed;
            top: 24px;
            left: 24px;
        }

        
        @media screen and (min-width: 769px) {
            .contenedor_icono:hover{
                transform: scale(1.1);
                background-color: #FFF;
            }

            #contenedor{
                flex-direction: row;
            }

        }

        @media screen and (max-width: 768px) {
            #contenedor{
                flex-direction: column;
            } 
        }

        #contenedor{
            flex-direction: column;
        }
        
    </style>
    </head>

	<body>
        <div id="contenedor">
            <a href="<?php echo esc_url(home_url('/'))?>registro/">
                <div class="contenedor_icono">
                    <i class="material-icons">person_add</i>
                    ¿Nuevo en COCO ROOM?
                </div>
            </a>
            <a href="<?php echo esc_url(home_url('/'))?>iniciar-sesion/">
                <div class="contenedor_icono">
                    <i class="material-icons">key</i>
                    Ya he estado
                </div>
            </a>
        </div>
        <img id="logo_cocoroom" src="<?php echo esc_url(home_url('/'))?>wp-content/uploads/2023/08/LogoCOCOROOM.png" alt="Logotipo Coco Room">
    </body>
</html>

