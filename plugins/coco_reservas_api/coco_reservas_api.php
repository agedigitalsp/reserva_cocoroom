<?php
/**
 * Plugin Name: Coco Disponibilidad API
 * Description: Genera una API para obtener las Reservas por id_ciudad
 * Author: Rubén Biota Maza
 * Version: 1.1.0
 */

if (!defined('ABSPATH')) exit;

class Coco_Disponibilidad_API_Handler {

    public function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'route_requests']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function activate() {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^coco-api/disponibilidad/juego/hora/?$', 'index.php?coco_api_disp_hora_trigger=1', 'top');
        
        add_rewrite_rule('^coco-api/disponibilidad/juego/?$', 'index.php?coco_api_disp_juego_trigger=1', 'top');

        add_rewrite_rule('^coco-api/disponibilidad/?$', 'index.php?coco_api_disponibilidad_trigger=1', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'coco_api_disponibilidad_trigger';
        $vars[] = 'coco_api_disp_juego_trigger';
        $vars[] = 'coco_api_disp_hora_trigger';
        return $vars;
    }

    /**
     * Enrutador: Solo decide a qué método independiente llamar.
     */
    public function route_requests() {
        if (get_query_var('coco_api_disp_hora_trigger') == 1) {
            $this->handle_disponibilidad_hora();
        } elseif (get_query_var('coco_api_disp_juego_trigger') == 1) {
            $this->handle_disponibilidad_juego();
        } elseif (get_query_var('coco_api_disponibilidad_trigger') == 1) {
            $this->handle_disponibilidad_sistema();
        }
    }

    /**
     * Endpoint 1: /coco-api/disponibilidad
     */
    private function handle_disponibilidad_sistema() {
        $id_ciudad     = isset($_GET['id_ciudad']) ? absint($_GET['id_ciudad']) : 0;
        $num_usuarios  = isset($_GET['num_usuarios']) ? absint($_GET['num_usuarios']) : 0;
        $fecha_reserva = isset($_GET['fecha_reserva']) ? sanitize_text_field($_GET['fecha_reserva']) : "";
        
        // Validaciones
        if (!$id_ciudad) $this->output_error('Falta el parámetro id_ciudad');
        if (!$num_usuarios) $this->output_error('Falta el parámetro num_usuarios');
        if (!$fecha_reserva) $this->output_error('Falta el parámetro fecha_reserva');

        $results = [];
        if (function_exists("calcular_disponibilidad_sistema")) {
            $results = calcular_disponibilidad_sistema($id_ciudad, $num_usuarios, $fecha_reserva);
        }

        $this->output_response($results);
    }

    /**
     * Endpoint 2: /coco-api/disponibilidad/juego
     */
    private function handle_disponibilidad_juego() {
        $id_ciudad     = isset($_GET['id_ciudad']) ? absint($_GET['id_ciudad']) : 0;
        $num_usuarios  = isset($_GET['num_usuarios']) ? absint($_GET['num_usuarios']) : 0;
        $fecha_reserva = isset($_GET['fecha_reserva']) ? sanitize_text_field($_GET['fecha_reserva']) : "";
        $id_juego      = isset($_GET['id_juego']) ? absint($_GET['id_juego']) : 0;
        
        // Validaciones
        if (!$id_ciudad) $this->output_error('Falta el parámetro id_ciudad');
        if (!$num_usuarios) $this->output_error('Falta el parámetro num_usuarios');
        if (!$fecha_reserva) $this->output_error('Falta el parámetro fecha_reserva');
        if (!$id_juego) $this->output_error('Falta el parámetro id_juego');

        $results = [];
        if (function_exists("calcular_disponibilidad_juego")) {
            $results = calcular_disponibilidad_juego($id_ciudad, $num_usuarios, $fecha_reserva, $id_juego);
        }

        $this->output_response($results);
    }

    /**
     * Endpoint 3: /coco-api/disponibilidad/juego/hora
     */
    private function handle_disponibilidad_hora() {
        $id_ciudad     = isset($_GET['id_ciudad']) ? absint($_GET['id_ciudad']) : 0;
        $num_usuarios  = isset($_GET['num_usuarios']) ? absint($_GET['num_usuarios']) : 0;
        $fecha_reserva = isset($_GET['fecha_reserva']) ? sanitize_text_field($_GET['fecha_reserva']) : "";
        $id_juego      = isset($_GET['id_juego']) ? absint($_GET['id_juego']) : 0;
        $hora_reserva  = isset($_GET['hora_reserva']) ? sanitize_text_field($_GET['hora_reserva']) : "";
        
        // Validaciones
        if (!$id_ciudad) $this->output_error('Falta el parámetro id_ciudad');
        if (!$num_usuarios) $this->output_error('Falta el parámetro num_usuarios');
        if (!$fecha_reserva) $this->output_error('Falta el parámetro fecha_reserva');
        if (!$id_juego) $this->output_error('Falta el parámetro id_juego');
        if (!$hora_reserva) $this->output_error('Falta el parámetro hora_reserva');

        $results = [];
        if (function_exists("calcular_disponibilidad_juego_hora")) {
            $results = calcular_disponibilidad_juego_hora($id_ciudad, $num_usuarios, $fecha_reserva, $id_juego, $hora_reserva);
        }

        $this->output_response($results);
    }

    /**
     * Helper para emitir errores de validación y detener la ejecución
     */
    private function output_error($message, $code = 400) {
        $this->output_response([
            'status' => false,
            'message' => $message
        ], $code);
    }

    /**
     * Helper centralizado para enviar la respuesta JSON
     */
    private function output_response($data, $code = 200) {
        status_header($code);
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        
        if (empty($data) && $code === 200) {
            $data = []; 
        }

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

new Coco_Disponibilidad_API_Handler();