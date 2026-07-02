<?php
/**
 * Plugin Name: SP - Woo Order Notes Filter & Silent Complete
 * Description: Filtra pedidos por estado y por notas (en orden) y permite pasarlos a "Completado" sin enviar emails.
 * Version: 1.0.0
 * Author: Agencia SP
 */

if (!defined('ABSPATH')) exit;

class SP_WOO_Order_Notes_Filter_Silent_Complete {

  const MENU_SLUG = 'sp-woo-order-notes-filter';
  const TRANSIENT_KEY_PREFIX = 'sp_woo_notes_filter_ids_';
  const TRANSIENT_TTL = 60 * 60; // 1h

  private static $instance = null;

  /** @var bool */
  private $block_emails = false;

  public static function instance() {
    if (!self::$instance) self::$instance = new self();
    return self::$instance;
  }

  private function __construct() {
    add_action('admin_menu', [$this, 'sp_register_admin_page']);
    add_action('admin_init', [$this, 'sp_handle_post_actions']);

    // Bloqueo de emails (se activa solo durante la operación)
    add_filter('woocommerce_email_enabled', [$this, 'sp_filter_woocommerce_email_enabled'], 9999, 2);
    add_filter('pre_wp_mail', [$this, 'sp_filter_pre_wp_mail'], 9999, 2);
  }

  public function sp_register_admin_page() {
    if (!class_exists('WooCommerce')) return;

    add_submenu_page(
      'woocommerce',
      'SP - Filtro de pedidos (notas)',
      'SP - Filtro pedidos',
      'manage_woocommerce',
      self::MENU_SLUG,
      [$this, 'sp_render_admin_page']
    );
  }

  public function sp_handle_post_actions() {
    if (!is_admin()) return;
    if (!current_user_can('manage_woocommerce')) return;

    $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    if ($page !== self::MENU_SLUG) return;

    // Acción: aplicar filtro
    if (isset($_POST['sp_action']) && $_POST['sp_action'] === 'filter') {
      check_admin_referer('sp_woo_notes_filter', 'sp_nonce');

      $status = isset($_POST['sp_status']) ? sanitize_text_field(wp_unslash($_POST['sp_status'])) : 'any';
      $note_lines_raw = isset($_POST['sp_note_lines']) ? wp_unslash($_POST['sp_note_lines']) : '';

      $note_lines = $this->sp_parse_note_lines($note_lines_raw);

      $matched_ids = $this->sp_find_matching_orders($status, $note_lines);

      $user_id = get_current_user_id();
      set_transient($this->sp_transient_key($user_id), $matched_ids, self::TRANSIENT_TTL);

      // Para evitar reenvíos al refrescar, redirigimos con flag
      wp_safe_redirect(add_query_arg(['page' => self::MENU_SLUG, 'sp_loaded' => 1], admin_url('admin.php')));
      exit;
    }

    // Acción: descargar CSV de los últimos resultados
    if (isset($_POST['sp_action']) && $_POST['sp_action'] === 'download_csv') {
      check_admin_referer('sp_woo_notes_csv', 'sp_csv_nonce');

      $user_id = get_current_user_id();
      $matched_ids = get_transient($this->sp_transient_key($user_id));
      if (!is_array($matched_ids)) $matched_ids = [];

      $items = $this->sp_build_table_items($matched_ids);

      nocache_headers();
      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename=sp-pedidos-filtrados-' . date('Ymd-His') . '.csv');
      header('Pragma: no-cache');
      header('Expires: 0');

      $out = fopen('php://output', 'w');

      // Cabeceras
      fputcsv($out, ['order_id', 'status', 'status_label', 'email']);

      foreach ($items as $row) {
        $status = (string) ($row['status'] ?? '');
        $label  = function_exists('wc_get_order_status_name') ? wc_get_order_status_name($status) : $status;

        fputcsv($out, [
          (int) ($row['order_id'] ?? 0),
          $status,
          $label,
          (string) ($row['email'] ?? ''),
        ]);
      }

      fclose($out);
      exit;
    }

    // Acción: bulk "pasar a completado"
    if (isset($_POST['action']) && $_POST['action'] === 'sp_mark_completed') {
      check_admin_referer('sp_woo_notes_bulk', 'sp_bulk_nonce');

      $order_ids = isset($_POST['order_id']) ? (array) $_POST['order_id'] : [];
      $order_ids = array_values(array_unique(array_map('absint', $order_ids)));
      $order_ids = array_filter($order_ids);

      if (!empty($order_ids)) {
        $result = $this->sp_bulk_mark_completed_silent($order_ids);

        $redirect = add_query_arg([
          'page' => self::MENU_SLUG,
          'sp_loaded' => 1,
          'sp_done' => 1,
          'sp_ok' => (int) $result['ok'],
          'sp_skip' => (int) $result['skip'],
          'sp_fail' => (int) $result['fail'],
        ], admin_url('admin.php'));

        wp_safe_redirect($redirect);
        exit;
      } else {
        $redirect = add_query_arg([
          'page' => self::MENU_SLUG,
          'sp_loaded' => 1,
          'sp_done' => 1,
          'sp_ok' => 0,
          'sp_skip' => 0,
          'sp_fail' => 0,
        ], admin_url('admin.php'));

        wp_safe_redirect($redirect);
        exit;
      }
    }
  }

  public function sp_render_admin_page() {
    if (!current_user_can('manage_woocommerce')) {
      wp_die('No tienes permisos.');
    }
    if (!class_exists('WooCommerce')) {
      echo '<div class="notice notice-error"><p>WooCommerce no está activo.</p></div>';
      return;
    }

    // Aviso post-acción
    if (isset($_GET['sp_done'])) {
      $ok = isset($_GET['sp_ok']) ? absint($_GET['sp_ok']) : 0;
      $skip = isset($_GET['sp_skip']) ? absint($_GET['sp_skip']) : 0;
      $fail = isset($_GET['sp_fail']) ? absint($_GET['sp_fail']) : 0;

      echo '<div class="notice notice-success"><p>';
      echo 'Operación terminada. ';
      echo 'Completados: <strong>' . esc_html($ok) . '</strong> — ';
      echo 'Omitidos: <strong>' . esc_html($skip) . '</strong> — ';
      echo 'Fallidos: <strong>' . esc_html($fail) . '</strong>.';
      echo '</p></div>';
    }

    $statuses = wc_get_order_statuses(); // wc-pending => Pendiente de pago, etc.

    // Tabla (si hay ids guardados)
    $user_id = get_current_user_id();
    $matched_ids = get_transient($this->sp_transient_key($user_id));
    if (!is_array($matched_ids)) $matched_ids = [];

    $items = $this->sp_build_table_items($matched_ids);

    $table = new SP_WOO_Order_Notes_Filter_Table($items);
    $table->prepare_items();

    echo '<div class="wrap">';
    echo '<h1>SP - Filtro de pedidos por notas (y completar sin emails)</h1>';

    echo '<p style="max-width: 900px;">';
    echo 'Este panel permite filtrar pedidos por <strong>estado actual</strong> y por <strong>notas del pedido en orden</strong> (un extracto por línea). ';
    echo 'Luego puedes marcar en lote y pasarlos a <strong>Completado</strong> sin enviar ningún email al cliente (se bloquea el envío durante la operación).';
    echo '</p>';

    // FORM FILTRO
    echo '<hr />';
    echo '<h2>Filtro</h2>';
    echo '<form method="post" action="">';
    wp_nonce_field('sp_woo_notes_filter', 'sp_nonce');

    echo '<table class="form-table" role="presentation">';
    echo '<tr>';
    echo '<th scope="row"><label for="sp_status">Estado del pedido</label></th>';
    echo '<td>';
    echo '<select name="sp_status" id="sp_status">';
    echo '<option value="any">Cualquiera</option>';
    foreach ($statuses as $key => $label) {
      $val = str_replace('wc-', '', $key);
      echo '<option value="' . esc_attr($val) . '">' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">Filtra por estado actual (por ejemplo: Pendiente de pago o Cancelado).</p>';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th scope="row"><label for="sp_note_lines">Extractos de notas (en orden)</label></th>';
    echo '<td>';
    echo '<textarea name="sp_note_lines" id="sp_note_lines" rows="6" style="width: 100%; max-width: 900px;" placeholder=""></textarea>';
    echo '<p class="description">';
    echo 'Cada línea es una coincidencia parcial (no hace falta el texto exacto). Se valida que aparezcan <strong>en ese orden</strong> en las notas del pedido.';
    echo '</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';

    echo '<input type="hidden" name="sp_action" value="filter" />';
    submit_button('Filtrar', 'primary', 'submit', false);
    echo '</form>';

    // FORM TABLA + BULK
    echo '<hr />';
    echo '<h2>Resultados</h2>';
    echo '<p>Pedidos encontrados: <strong>' . esc_html(count($matched_ids)) . '</strong></p>';

    // Botón descargar CSV
    echo '<form method="post" action="" style="margin: 10px 0 20px;">';
    wp_nonce_field('sp_woo_notes_csv', 'sp_csv_nonce');
    echo '<input type="hidden" name="sp_action" value="download_csv" />';
    submit_button('Descargar resultados en CSV', 'secondary', 'submit', false);
    echo '</form>';

    echo '<form method="post" action="">';
    wp_nonce_field('sp_woo_notes_bulk', 'sp_bulk_nonce');

    $table->display();

    echo '</form>';

    echo '<hr />';
    echo '<p><strong>Nota:</strong> si al volver a “Completado” el stock hubiera sido restaurado por la cancelación automática, WooCommerce puede volver a reducirlo (normalmente es lo correcto si el pedido realmente estaba completado/shipped antes).</p>';

    echo '</div>';
  }

  private function sp_transient_key($user_id) {
    return self::TRANSIENT_KEY_PREFIX . (int) $user_id;
  }

  private function sp_parse_note_lines($raw) {
    $raw = (string) $raw;

    // Si el usuario pega "\n" literal, lo convertimos a salto real
    $raw = str_replace(["\\r\\n", "\\n", "\\r"], "\n", $raw);

    // Normalizamos saltos reales
    $raw = str_replace(["\r\n", "\r"], "\n", $raw);

    $lines = explode("\n", $raw);

    $out = [];
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '') continue;

      // OJO: sanitize_text_field puede recortar cosas raras; mantenemos limpio pero sin “romper” el texto
      $out[] = wp_strip_all_tags($line, true);
    }

    return $out;
  }

  private function sp_norm($s) {
    $s = (string) $s;
    $s = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $s);
    $s = str_replace(["–", "—"], "-", $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    $s = trim(mb_strtolower($s, 'UTF-8'));
    return $s;
  }

  private function sp_contains($haystack, $needle) {
    $h = $this->sp_norm($haystack);
    $n = $this->sp_norm($needle);
    if ($n === '') return false;
    return (mb_strpos($h, $n, 0, 'UTF-8') !== false);
  }

  /**
   * Encuentra pedidos que:
   * - Estado actual coincide (si no es 'any')
   * - Contienen todas las líneas (extractos) en orden en las notas del pedido.
   *
   * Devuelve array de order_ids (int).
   */
  private function sp_find_matching_orders($status, array $note_lines) {
    global $wpdb;

    $status = $status ? sanitize_text_field($status) : 'any';

    // Si no hay líneas, devolvemos por estado usando wc_get_orders (ojo: puede ser muchísimos)
    if (empty($note_lines)) {
      $args = [
        'limit' => 500, // límite de seguridad
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'ids',
      ];
      if ($status !== 'any') $args['status'] = $status;

      $ids = wc_get_orders($args);
      return array_map('absint', (array) $ids);
    }

    $comments = $wpdb->comments;

    // Candidatos por la primera línea (SQL LIKE) para no revisar todo el universo
    $first = $note_lines[0];
    $like = '%' . $wpdb->esc_like($first) . '%';

    $candidate_ids = $wpdb->get_col(
      $wpdb->prepare(
        "SELECT DISTINCT comment_post_ID
         FROM {$comments}
         WHERE comment_type = 'order_note'
           AND comment_content LIKE %s",
        $like
      )
    );

    $candidate_ids = array_values(array_unique(array_map('absint', (array) $candidate_ids)));
    if (empty($candidate_ids)) return [];

    $matched = [];

    // Revisamos cada candidato: estado + orden de notas
    foreach ($candidate_ids as $order_id) {
      $order = wc_get_order($order_id);
      if (!$order) continue;

      if ($status !== 'any' && $order->get_status() !== $status) {
        continue;
      }

      // Notas del pedido ordenadas ASC por fecha
      $notes = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT comment_ID, comment_content, comment_date_gmt
          FROM {$comments}
          WHERE comment_post_ID = %d
            AND comment_type = 'order_note'
          ORDER BY comment_date_gmt ASC, comment_ID ASC",
          $order_id
        )
      );

      if (empty($notes)) continue;

      $needle_index = 0;
      foreach ($notes as $n) {
        if ($this->sp_contains($n->comment_content ?? '', $note_lines[$needle_index])) {
          $needle_index++;
          if ($needle_index >= count($note_lines)) {
            $matched[] = $order_id;
            break;
          }
        }
      }
    }

    // límite de seguridad
    $matched = array_slice($matched, 0, 2000);

    return array_values(array_unique(array_map('absint', $matched)));
  }

  private function sp_build_table_items(array $order_ids) {
    $items = [];
    foreach ($order_ids as $order_id) {
      $order = wc_get_order($order_id);
      if (!$order) continue;

      $items[] = [
        'order_id' => $order_id,
        'status'   => $order->get_status(),
        'email'    => $order->get_billing_email(),
      ];
    }
    return $items;
  }

  private function sp_bulk_mark_completed_silent(array $order_ids) {
    $ok = 0; $skip = 0; $fail = 0;

    // Activar bloqueo de emails (Woo + wp_mail) DURANTE la operación
    $this->block_emails = true;

    foreach ($order_ids as $order_id) {
      try {
        $order = wc_get_order($order_id);
        if (!$order) { $fail++; continue; }

        if ($order->get_status() === 'completed') {
          $skip++;
          continue;
        }

        // Nota interna para auditoría
        $order->add_order_note(
          'Restaurado automáticamente a "Completado" por herramienta SP (sin emails al cliente).',
          false // false => nota interna (no al cliente)
        );

        // Cambiar estado
        $order->update_status('completed', '', true);

        $ok++;
      } catch (Throwable $e) {
        $fail++;
      }
    }

    $this->block_emails = false;

    return ['ok' => $ok, 'skip' => $skip, 'fail' => $fail];
  }

  /**
   * Bloquea los emails de WooCommerce mientras $this->block_emails sea true
   */
  public function sp_filter_woocommerce_email_enabled($enabled, $email) {
    if ($this->block_emails) return false;
    return $enabled;
  }

  /**
   * Bloquea CUALQUIER wp_mail (por si algún plugin manda correo fuera del sistema de emails de Woo).
   * Devuelve true para simular éxito y evitar reintentos/errores.
   */
  public function sp_filter_pre_wp_mail($pre, $atts) {
    if ($this->block_emails) return true;
    return $pre;
  }
}

/**
 * Tabla (WP_List_Table)
 */
// ====== Admin-only: WP_List_Table safe load + table class ======
if (is_admin()) {

  if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
  }

  if (!class_exists('SP_WOO_Order_Notes_Filter_Table')) {
    class SP_WOO_Order_Notes_Filter_Table extends WP_List_Table {

      private $items_data = [];

      public function __construct(array $items) {
        parent::__construct([
          'singular' => 'pedido',
          'plural'   => 'pedidos',
          'ajax'     => false,
        ]);
        $this->items_data = $items;
      }

      public function get_columns() {
        return [
          'cb'       => '<input type="checkbox" />',
          'order_id' => 'ID Pedido',
          'status'   => 'Estado',
          'email'    => 'Email',
        ];
      }

      protected function column_cb($item) {
        return sprintf(
          '<input type="checkbox" name="order_id[]" value="%d" />',
          (int) $item['order_id']
        );
      }

      protected function column_order_id($item) {
        $order_id = (int) $item['order_id'];
        $link = admin_url('post.php?post=' . $order_id . '&action=edit');
        return '<a href="' . esc_url($link) . '">#' . esc_html($order_id) . '</a>';
      }

      protected function column_status($item) {
        $status = (string) $item['status'];
        $label = function_exists('wc_get_order_status_name') ? wc_get_order_status_name($status) : $status;
        return esc_html($label);
      }

      protected function column_email($item) {
        return esc_html((string) $item['email']);
      }

      public function get_bulk_actions() {
        return [
          'sp_mark_completed' => 'Pasar a completado (sin emails)',
        ];
      }

      public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = [];

        $this->_column_headers = [$columns, $hidden, $sortable];

        $per_page = 50;
        $current_page = $this->get_pagenum();
        $total_items = count($this->items_data);

        $this->items = array_slice($this->items_data, ($current_page - 1) * $per_page, $per_page);

        $this->set_pagination_args([
          'total_items' => $total_items,
          'per_page'    => $per_page,
          'total_pages' => ceil($total_items / $per_page),
        ]);
      }
    }
  }
}


SP_WOO_Order_Notes_Filter_Silent_Complete::instance();
