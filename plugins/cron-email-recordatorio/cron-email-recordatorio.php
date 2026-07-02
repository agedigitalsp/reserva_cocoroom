<?php
/*
* Plugin Name: CRON recordatorio Coco Room
* Plugin URI: https://agenciasp.com/
* Description: Plugin para enviar masivamente un recordatorio de la reserva un día antes.
* Version: 1.0.0
* Author: Agencia Digital SP
* Author URI: https://agenciasp.com/
* License:
*/

if ( ! wp_next_scheduled( 'cron_dia_anterior' ) ) {
    error_log("EL CRON HA SIDO ACTIVADO");
    wp_schedule_event( strtotime('12:00:00'), 'daily', 'cron_dia_anterior' );
}
add_action( 'cron_dia_anterior', 'enviar_email_dia_anterior' );

// Desprogramar el evento si está programado
// if ( wp_next_scheduled( 'cron_dia_anterior' ) ) {
//     wp_clear_scheduled_hook( 'cron_dia_anterior' );
//     error_log("EL CRON HA SIDO DESACTIVADO");
// }

function enviar_email_dia_anterior() {
    global $wpdb;

    $fecha_manana = date('Y-m-d', strtotime('+1 day'));

    $sql = "SELECT * FROM  ".$wpdb->prefix."reservas WHERE fecha_reserva = '".$fecha_manana."' AND estado LIKE 'reservada' ";
    $reservas = $wpdb->get_results( $sql );

    if($reservas){
        $emails = array();
        foreach ($reservas as $reserva) {
            $emails[] = $reserva->email_usuario;
        }

        // Convertir la lista de correos en una cadena separada por comas para el campo BCC
        $emails = implode(',', $emails);
        
        $headers = array(
            'Bcc: ' . $emails
        );
    
        $data = array();

        do_action('send_mails_adpnsy_cllbck', "info@cocoroom.es", 'Recordatorio de tu reserva en Coco Room', 'recordatorio_partida', $data, $headers); 
    }
}


?>