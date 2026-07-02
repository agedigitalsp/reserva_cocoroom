<?php

/*
Plugin Name: Cupones Cocoroom
Plugin URI: http://agenciasp.com/
Description: Plugin a medida
Author: Agencia Digital SP
Author URI: http://agenciasp.com/
*/

if (!class_exists('cocoroom_cupones')){
	class cocoroom_cupones {

		public function __construct(){
			add_filter('woocommerce_coupon_code', [$this, 'validar'], 10, 1);
			add_filter('comprobar_cupon_woocommerce_api', [$this, 'validar_cupon_panel_reservas'], 10, 3);
			add_action('woocommerce_order_status_completed', [$this, 'actualizar'], 10, 1);
		}

		public function actualizar($order_id) {
		    $order = wc_get_order($order_id);
		    $cupones_utilizados = $order->get_coupon_codes();

		    if(!get_post_meta($order_id, "used_cupon", true)){
			    if (!empty($cupones_utilizados)) {
				    foreach ($cupones_utilizados as $cupon_codigo) {
				        $cupon_id = wc_get_coupon_id_by_code($cupon_codigo);
				        if($cupon_id){
				        	$coupon_code = get_post_meta($cupon_id, 'code_real', true);
				        	$category_db = get_post_meta($cupon_id, 'db_real', true);
				        	if($coupon_code && $category_db){
				        		$dts = $this->api('descontar', ["cupon" => $coupon_code, "db" => $category_db]);
					        	if($dts && $dts->r){
					        		update_post_meta($order_id, "used_cupon", true);
					        	}else{
					        		error_log("Error al actualizar el cupon {$coupon_code} de la BD {$category_db}");
					        	}
				        	}
				        }else{
				        	error_log("Error al obtener el cupon {$cupon_codigo}");
				        }
				    }
				}
			}
		}

		public function validar($coupon_code){
			if($_SERVER['QUERY_STRING'] === 'wc-ajax=apply_coupon' && isset($_POST['coupon_code'])){
			    remove_filter('woocommerce_coupon_code', [$this, 'validar'], 10);
			    global $woocommerce;
				$items = $woocommerce->cart->get_cart();
				if (!empty($items)) {
				    $first_item = reset($items);
				    $product_id = $first_item['product_id'];
				    $product_categories = get_the_terms($product_id, 'product_cat');
				    if (!empty($product_categories)) {
				    	foreach ($product_categories as $category) {
				    		$category_id = $category->term_id;
				    		$category_slug = get_term_meta($category_id, '_db_cupones_name_', true);
				        	$category_db = get_term_meta($category_id, '_db_cupones_', true);
				        	if($category_db) break;
				    	}
				        if($category_db){
				        	$dts = $this->api('verificar', ["cupon" => $coupon_code, "db" => $category_db]);
				        	if($dts){
				        		if($dts->r){
				        			$cupon = $coupon_code . "($category_slug)";
				        			$existing_coupon = new WC_Coupon($cupon);
				        			if (!$existing_coupon->get_id()) {
				        				$this->create_cupon(
				        					$coupon_code,
				        					$category_db,
				        					$cupon, 
				        					$dts->cantidad, 
				        					$dts->dts->one_use, 
				        					$dts->dts->type == 'absolute' ? 'fixed_cart' : 'percent',
				        					$dts->dts->expiration_date
				        				);
				        			}
				        			return $cupon;
				        		}
				        	}
				        }
				    }
				}
			}
		    return $coupon_code;
		}

		public function validar_cupon_panel_reservas($valor_inicial, $id_ciudad, $coupon_code) {
			remove_filter('woocommerce_coupon_code', [$this, 'validar'], 10);
			if ($id_ciudad && $coupon_code) {
				$category_slug = get_term_meta($id_ciudad, '_db_cupones_name_', true);
				$category_db = get_term_meta($id_ciudad, '_db_cupones_', true);
				if($category_db){
					$dts = $this->api('verificar', ["cupon" => $coupon_code, "db" => $category_db]);
					if($dts){
						if($dts->r){
							$cupon = $coupon_code . "($category_slug)";
							$existing_coupon = new WC_Coupon($cupon);
							if (!$existing_coupon->get_id()) {
								$this->create_cupon(
									$coupon_code,
									$category_db,
									$cupon, 
									$dts->cantidad, 
									$dts->dts->one_use, 
									$dts->dts->type == 'absolute' ? 'fixed_cart' : 'percent',
									$dts->dts->expiration_date
								);
							}
							return $cupon;
						}
					}
				}
			}
		    return false;
		}

		private function api($type, $data){
			$time      = date(DATE_RFC2822);

	        $send_data = json_encode($data);
	        $hash      = hash('sha256', $send_data);
	        $salt 	   = '$1$rasmusle$';
	        $time      = date(DATE_RFC2822);
	        $code 	   = 'A_E_A_Q#' . $time;
	        $sigh      = str_replace($salt, '', crypt($code . $hash, $salt));
	        $url       = "https://cocoroom.es/api-cupon/{$type}/{$hash}/";

	        $headers = [
	            'signature' => $sigh,
	            'x-time' => $time,
	            'Content-Type' => 'application/json',
	        ];

	        $args = array(
			    'headers' => $headers,
			    'body' => $send_data,
			);

			$response = wp_remote_post($url, $args);

			if (is_wp_error($response)) {
			    error_log("Hubo un error en la solicitud: " . $response->get_error_message());
			    return null;
			} else {
			    // La solicitud fue exitosa, decodificamos la respuesta JSON
			    $response_code = wp_remote_retrieve_response_code($response);
			    $response_body = wp_remote_retrieve_body($response);

			    // Decodificar la respuesta JSON
			    $dts = json_decode($response_body);

			    if ($dts === null) {
			        error_log("Error al decodificar la respuesta: {$response_body}");
			        return null;
			    } else {
			       return $dts;
			    }
			}
		}

		private function create_cupon($code_real, $db_real, $coupon_code, $cantidad, $limit, $type, $expire){
	        $new_coupon = new WC_Coupon();
	        $new_coupon->set_code($coupon_code);
	        $new_coupon->set_discount_type($type);
	        $new_coupon->set_amount($cantidad);
	        $new_coupon->set_individual_use(true);
	        if($limit > 0) $new_coupon->set_usage_limit($limit);
	        $new_coupon->set_date_expires($expire ?? 0);
	        $new_coupon->save();
	        $coupon_id = $new_coupon->get_id();
	        update_post_meta($coupon_id, 'code_real', $code_real);
	        update_post_meta($coupon_id, 'db_real', $db_real);
		}

		private function delete_cupon($existing_coupon, $coupon_code){
			wp_delete_post($existing_coupon->get_id(), true);
		    error_log("cupon: {$coupon_code} eliminado");
		}

	}
	$GLOBAL['cocoroom_cupones'] = new cocoroom_cupones();
}