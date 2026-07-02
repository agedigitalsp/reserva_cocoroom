<?php
/**
 * Plugin Name: Coco FAQs API
 * Description: Genera una API para obtener las FAQs de la tabla personalizada por id_ciudad (formatos JSON/XML)
 * Author: Rubén Biota Maza
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

class Coco_FAQ_API_Handler {

    public function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'serve_api_response']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function activate() {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }

    public function add_rewrite_rules() {
        // Endpoint: /coco-api/faqs
        add_rewrite_rule('^coco-api/faqs/?$', 'index.php?coco_api_faqs_trigger=1', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'coco_api_faqs_trigger';
        $vars[] = 'id_ciudad';
        $vars[] = 'formato';
        return $vars;
    }

    public function serve_api_response() {
        if (get_query_var('coco_api_faqs_trigger') != 1) return;

        global $wpdb;
        $table_faqs = $wpdb->prefix . 'faqs';

        // Parámetros de entrada
        $id_ciudad = isset($_GET['id_ciudad']) ? absint($_GET['id_ciudad']) : 0;
        $formato   = isset($_GET['formato']) ? sanitize_text_field(strtolower($_GET['formato'])) : 'json';

        // Validación básica
        if (empty($id_ciudad)) {
            $this->output_response([
                'status' => 'error',
                'message' => 'Falta el parámetro id_ciudad'
            ], $formato, 400);
        }

        // Consulta a la base de datos
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pregunta, respuesta FROM $table_faqs WHERE id_ciudad = %d ORDER BY id ASC",
                $id_ciudad
            ),
            ARRAY_A
        );

        if (empty($results)) {
            $this->output_response([], $formato, 200);
        }

        $this->output_response($results, $formato, 200);
    }

    /**
     * Envía la respuesta en el formato solicitado
     */
    private function output_response($data, $format, $code = 200) {
        status_header($code);
        nocache_headers();

        if ($format === 'xml') {
            header('Content-Type: application/xml; charset=utf-8');
            echo $this->render_xml($data);
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Generador simple de XML
     */
    private function render_xml($data) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><faqs/>');
        
        if (isset($data['status']) && $data['status'] === 'error') {
            $xml->addChild('error', $data['message']);
        } else {
            foreach ($data as $row) {
                $item = $xml->addChild('faq');
                foreach ($row as $key => $value) {
                    // Manejo de CDATA para las respuestas que pueden tener HTML
                    $node = $item->addChild($key);
                    $dom = dom_import_simplexml($node);
                    $owner = $dom->ownerDocument;
                    $dom->appendChild($owner->createCDATASection($value));
                }
            }
        }
        return $xml->asXML();
    }
}

new Coco_FAQ_API_Handler();