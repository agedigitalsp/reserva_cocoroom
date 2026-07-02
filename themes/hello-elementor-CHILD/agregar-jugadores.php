<?php /* Template Name: SP Admin AGREGAR JUGADORES A PARTIDA */ 
$contenido = "";

global $wpdb; 
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
                    //USUARIO ENTRA AL SISTEMA
                    $current_user = wp_get_current_user();
                    $user_email = $current_user->user_email;
                    $user_id = $current_user->ID;  

                    if($result->id_usuario == $user_id){
                        $jugadores = json_decode($result->jugadores);
                        
                        $inputs = array();

                        for ($i=0; $i<$result->num_usuarios; $i++) {

                            $input = "<td class='ocultar_movil'><label>Email del usuario '".($i+1)."'</label></td><td><input type='email' ";

                            if($jugadores && isset($jugadores[$i])){
                                
                                if(isset($jugadores[$i]->email) && $jugadores[$i]->email != ""){
                                    $input .= " value='".$jugadores[$i]->email."' disabled >";
                                    if(isset($jugadores[$i]->ID) && $jugadores[$i]->ID != ""){
                                        $input .= " (añadido)</td>";
                                    }
                                    else{
                                        $input .= " (pendiente)</td>";
                                    }

                                    $input .= " <td><button class='btn_borrar_usuario' data-email='".$jugadores[$i]->email."'><i class='material-icons'>delete</i></button></td>";
                                }
                                else{
                                    $input .= " placeholder='Escribe un email' class='email_usuario' ></td>";
                                }
    
                            }
                            else{
                                $input .= " placeholder='Escribe un email' class='email_usuario'></td>";
                            }

                            $input = "<tr>".$input."</tr>";
                            $inputs[] = $input;
                        }

                        $contenido = "<h2>Escribe el correo electrónico de tus compañeros.</h2>";
                        $contenido .= "<h3>Si quieres participar, añade tu email (<span style='text-decoration:underline'>" . $user_email . "</span>).</h3>";

                        $contenido .= "<div id='contenedor_inputs'><table>".implode("", $inputs)."</table>";

                        if(count($jugadores) == ($result->num_usuarios)){
                            $contenido .= "<br><button id='btn_agregar_usuarios' disabled='disabled'>¡Todo listo!</button></div>";
                            $contenido .= "<br><button id='btn_compartir' disabled='disabled'><i class='material-icons'>send_to_mobile</i>Compartir</button></div>";
                        }
                        else{
                            $contenido .= "<br><button id='btn_agregar_usuarios'>Añadir usuarios</button></div>";
                            $contenido .= "<br><button id='btn_compartir'><i class='material-icons'>send_to_mobile</i>Compartir</button></div>";
                        }
                        
                    }
                    else{
                        $error = true;
                        $contenido = "<h1>¡Oh, vaya!</h1><h2>Has iniciado sesión con un usuario que no corresponde a esta reserva.</h2>";
                    }
                }
                else {
                    if (!session_id()) {
                        session_start();
                    }
                    $_SESSION['id']  = $id;
                    $_SESSION['key'] = $key;
                    $_SESSION['action'] = "agregar-jugadores";

                    wp_redirect(home_url() . "/acceder/");
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

if(!$error)
    $estilo_head = '
    body{
        min-height: calc(100vh - 64px);
        background: #292929;
        font-family: "Poppins",sans-serif;
        margin: 32px 0px;
        padding: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    #contenedor{
        width: calc(100% - 400px);
        background: #f5e264;
        padding: 32px;
        box-shadow: 0px 0px 20px 0px #0005;
    }

    @media screen and (max-width: 1400px) {
        #contenedor{
            width: calc(100% - 100px);
            margin: 88px 0px;
        }
    }
        
    @media screen and (max-width: 768px) {
        body{
            margin: 88px 0px;
        }
    }';
else
    $estilo_head = '
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
    ';

?>








<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Agregar jugadores - Reservas Coco Room</title>


        <script src='<?php echo esc_url( home_url( '/' ) )?>/wp-includes/js/jquery/jquery.min.js?ver=3.6.4' id='jquery-core-js'></script>
        <script src='<?php echo esc_url( home_url( '/' ) )?>/wp-includes/js/jquery/jquery-migrate.min.js?ver=3.4.0' id='jquery-migrate-js'></script>
        <link rel="stylesheet" type="text/css" href="<?php echo esc_url( home_url( '/' ) )?>/wp-content/plugins/admin_panel_system/app-assets/vendors/sweetalert/sweetalert2.min.css">
        <script src="<?php echo esc_url( home_url( '/' ) )?>/wp-content/plugins/admin_panel_system/app-assets/vendors/sweetalert/sweetalert2.all.min.js"></script>
        <link rel="stylesheet" type="text/css" href="<?php echo esc_url( home_url( '/' ) )?>/wp-content/plugins/funciones-reservas-cocoroom/vendor/animatecss/animatecss.min.css">

        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&display=swap" rel="stylesheet">
        <style>

            <?php echo $estilo_head; ?>

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

            h3{
                color: #292929;
                text-align:center;
                font-size: 28px;
            }

            #contenedor_inputs{
                background-color: white;
                margin: -32px;
                padding: 32px;
                margin-top: 64px;
                padding-bottom: 64px;
                margin-bottom: -64px;
                display: flex;
                flex-direction: column;
                align-items: center;
                color: #292929;
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
                border-radius: 38px;
                background: #F5E165;
                padding: 8px 16px;
                color: #292929;
                border: 2px solid;
                font-family: 'Poppins';
                margin: auto;
                transition: background 0.5s
            }

            button:hover{
                background: #fff;
                cursor:pointer;
            }



            button#btn_compartir{
                position: fixed;
                right: 32px;
                bottom: 32px;
                z-index: 1000;
                box-shadow: 0px 5px 10px #0002;
                color: #333;
                border-radius: 43px;
                width: 196px;
                height: 60px;
                display: flex;
                gap: 12px;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: transform 0.3s;
                font-size: 20px;
            }

            button#btn_compartir:hover {
                transform: scale(1.1); /* Escala el botÃ³n al pasar el cursor por encima */
                color: #F5E165;
                background-color: #333;
            }

            button.btn_borrar_usuario{
                display: flex;
                border-radius: 50%;
                padding: 18px;
                width: 32px;
                height: 32px;
                align-items: center;
                justify-content: center;
            }

            button:disabled{
                background: #fff;
                cursor:not-allowed;
            }

            button:disabled:hover{
                transform: none!important;
                background-color: #fff!important;
                cursor: not-allowed!important;
                color: #333!important;
            }
            
            input{
                border-radius: 38px;
                padding: 8px 14px;
                border: 2px solid;
            }

            input:disabled{
                background-color: #dedede!important;
                color: #333!important;
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
                h3{
                    font-size: 24px;
                }

                #contenedor_inputs *{
                    font-size: 14px!important;
                }

                .ocultar_movil{
                    display:none;
                }

                button#btn_compartir{
                    right: 8px;
                    bottom: 8px;
                    width: 164px;
                    height: 52px;
                    font-size: 16px;
                }

                button#btn_compartir i{
                    font-size: 24px;
                }
            }

            @media screen and (max-width: 600px) {
                h1{
                    font-size: 28px;
                }
                h2{
                    font-size: 20px;
                }
                h3{
                    font-size: 16px;
                }
            }



            @media screen and (max-width: 1400px) {
                #logo_cocoroom{
                    left: 50%;
                    transform: translateX(-50%);
                    background: #292929;
                    padding: 24px 100% 12px 100%;
                    top: 0px;
                }
            }

            
            .swal2-container.swal2-center>.swal2-popup {
                border-radius: 0;
            }

            .swal2-styled.swal2-confirm {
                color: #fff!important;
                border: 2px solid #292929!important;
                transition: color 0.2s, background-color 0.2s;
            }

            .swal2-styled.swal2-confirm:hover {
                color: #292929!important;
                background-color: #fff!important;
                background-image: none!important;
            }

            .swal2-html-container {
                font-size: 16px!important;
                color:#292929!important;
                text-align: center;
            }

            .swal2-html-container ul {
                margin-left: 22px;
                text-align: left;
            }

            .swal2-icon {
                border-color: #f2e177!important;
                color: #292929!important;
                background: #f2e177!important;
            }

            .swal2-x-mark *{
                background-color: #292929!important;
            }

            .swal2-styled.swal2-default-outline:focus {
                box-shadow: none!important
            }

            .swal2-styled.swal2-cancel {
                border: 2px solid #292929!important;
                color: #292929!important;
                background-color: #fff!important;
            }

            .swal2-styled.swal2-cancel:hover {
                color: #fff!important;
                background-color: #292929!important;
            }
            
        </style>
        <script>
            <?php
                if(isset($_GET['id']) && isset($_GET['key'])){
                    echo "const id='".$_GET['id']."';";
                    echo "const key='".$_GET['key']."';";
                }
            ?>
        </script>
    </head>

	<body>
        <div id="contenedor">
            <?php echo $contenido?>
        </div>

        <img id="logo_cocoroom" src="<?php echo esc_url(home_url('/'))?>wp-content/uploads/2023/08/LogoCOCOROOM.png" alt="Logotipo Coco Room">
        
        <script type="text/javascript"> var AjaxUrl = "<?php echo admin_url('admin-ajax.php')?>";</script>
        <script>
            jQuery(document).ready(function($) {
                $('body').on('click', '.btn_borrar_usuario', function(){
                    Swal.fire({
                        title: '¿Seguro que quieres eliminar a este usuario de la partida?',
                        width: 600,
                        showCancelButton: true,
                        confirmButtonColor: '#292929',
                        confirmButtonText: 'Sí, quiero borrarlo',
                        cancelButtonText: 'No, cancelar',
                        showClass: {
                            popup: 'animated fadeInDown faster',
                        },
                        hideClass: {
                            popup: 'animated fadeOutUp faster',
                        },
                    })
                    .then((result) => {
                        if(result.isConfirmed){
                            let email = $(this).data("email");
                            $.ajax({
                                type: 'POST',
                                url: AjaxUrl,
                                data: {
                                    action: 'eliminar_usuario_partida',
                                    id: id,
                                    key: key,
                                    email: email,
                                },
                                beforeSend: function () {
                                    bloquear_boton_enviar();
                                },
                                success: function(response) {
                                    desbloquear_boton_enviar()
                                    let parsedResponse = JSON.parse(response)
                                    if(!parsedResponse.error){
                                        location.reload();
                                    }
                                    else{
                                        Swal.fire({
                                            title: "ERROR: " + parsedResponse.error,
                                            width: 600,
                                            showCancelButton: false,
                                            confirmButtonText: 'Cerrar',
                                            confirmButtonColor: '#292929',
                                            showClass: {
                                                popup: 'animated fadeInDown faster',
                                            },
                                            hideClass: {
                                                popup: 'animated fadeOutUp faster',
                                            },
                                        })
                                        .then((result) => {
                                        })
                                    }
                                },
                                error:function(error){
                                    Swal.fire({
                                        title: 'Sistema no disponible actualmente.',
                                        width: 600,
                                        showCancelButton: false,
                                        confirmButtonText: 'Cerrar',
                                        confirmButtonColor: '#292929',
                                        showClass: {
                                            popup: 'animated fadeInDown faster',
                                        },
                                        hideClass: {
                                            popup: 'animated fadeOutUp faster',
                                        },
                                    })
                                    .then((result) => {
                                    })
                                }
                            })
                        }
                    })
                })




                $('body').on('click', '#btn_agregar_usuarios', function(){
                    let emails = [];
                    let error = false;
                    let hasValidEmail = false;

                    $('input.email_usuario:not(:disabled)').each(function() {
                        if($(this).val() !== "") {
                            if(isEmail($(this).val())) {
                                emails.push($(this).val());
                                hasValidEmail = true;
                            } else {
                                error = true;
                            }
                        }
                    });

                    function tieneDuplicados(array) {
                        var emailsMin = array.map(email => email.toLowerCase());
                        var emailsUnicos = new Set(emailsMin);
                        return emailsUnicos.size !== emailsMin.length;
                    }

                    if (emails.length > 0 && !error && hasValidEmail && !tieneDuplicados(emails)) {
                        var emailsString = emails.join(',');

                        Swal.fire({
                            title: 'Se va a enviar un email a cada usuario para ser añadido a la partida. ¿Estás seguro?',
                            width: 600,
                            showCancelButton: true,
                            confirmButtonText: 'Sí, enviar correos',
                            confirmButtonColor: '#292929',
                            cancelButtonText: 'No, cancelar',
                            showClass: {
                                popup: 'animated fadeInDown faster',
                            },
                            hideClass: {
                                popup: 'animated fadeOutUp faster',
                            },
                        })
                        .then((result) => {
                            if(result.isConfirmed){
                                
                                $.ajax({
                                    type: 'POST',
                                    url: AjaxUrl,
                                    data: {
                                        action: 'agregar_usuarios_partida',
                                        id: id,
                                        key: key,
                                        emailsString: emailsString,
                                    },
                                    beforeSend: function () {
                                        bloquear_boton_enviar();
                                    },
                                    success: function(response) {
                                        let parsedResponse = JSON.parse(response)
                                        if(!parsedResponse.error){
                                            location.reload();
                                        }
                                        else{
                                            desbloquear_boton_enviar();
                                            Swal.fire({
                                                title: "ERROR:" + parsedResponse.error,
                                                width: 600,
                                                showCancelButton: false,
                                                confirmButtonText: 'Cerrar',
                                                confirmButtonColor: '#292929',
                                                showClass: {
                                                    popup: 'animated fadeInDown faster',
                                                },
                                                hideClass: {
                                                    popup: 'animated fadeOutUp faster',
                                                },
                                            })
                                            .then((result) => {
                                            })
                                        }
                                    },
                                    error:function(error){
                                        desbloquear_boton_enviar();
                                        Swal.fire({
                                            title: 'Sistema no disponible actualmente.',
                                            width: 600,
                                            showCancelButton: false,
                                            confirmButtonText: 'Cerrar',
                                            confirmButtonColor: '#292929',
                                            showClass: {
                                                popup: 'animated fadeInDown faster',
                                            },
                                            hideClass: {
                                                popup: 'animated fadeOutUp faster',
                                            },
                                        })
                                        .then((result) => {
                                        })
                                    }
                                })
                            }
                        })
                    }
                    else{
                        Swal.fire({
                            title: "Por favor, introduce los correos y revisa los datos introducidos.",
                            width: 600,
                            showCancelButton: false,
                            confirmButtonText: 'Cerrar',
                            confirmButtonColor: '#292929',
                            showClass: {
                                popup: 'animated fadeInDown faster',
                            },
                            hideClass: {
                                popup: 'animated fadeOutUp faster',
                            },
                        })
                        .then((result) => {
                        })
                    }

                })

                function isEmail(email) {
                    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                    return regex.test(email);
                };

                function bloquear_boton_enviar(){
                    console.log("a");
                    $('.btn_borrar_usuario').prop("disabled", true);
                    $('#btn_agregar_usuarios').prop("disabled", true);
                    $('#btn_agregar_usuarios').text("Un momento...");
                }

                function desbloquear_boton_enviar(){
                    $('.btn_borrar_usuario').prop("disabled", false);
                    $('#btn_agregar_usuarios').prop("disabled", false);
                    $('#btn_agregar_usuarios').text("Añadir usuarios");
                }

                $('body').on('click', '#btn_compartir', function(){
                    let enlace_unirse = "<?php echo home_url() . "/unirse/?id=" . $id . "&key=" . $key?>";

                    navigator.clipboard.writeText("¡Has sido invitado a una partida de COCO ROOM! Haz clic sobre este enlace para unirte: " + enlace_unirse);

                    Swal.fire({
                        title: "¡Comparte el enlace!",
                        html: `Has copiado el enlace para que el resto de jugadores se unan a la partida
                        <ul>
                            <li>Abre tu aplicación (Whatsapp, Instagram...)</li>
                            <li>Selecciona el contacto que quieres que se una</li>
                            <li>¡Pega el enlace!</li>
                        </ul>
                        `,
                        width: 600,
                        showCancelButton: false,
                        confirmButtonText: '¡Entendido!',
                        confirmButtonColor: '#292929',
                        showClass: {
                            popup: 'animated fadeInDown faster',
                        },
                        hideClass: {
                            popup: 'animated fadeOutUp faster',
                        },
                    })
                    .then((result) => {
                    })
                })
            })

        </script>
    </body>
</html>

