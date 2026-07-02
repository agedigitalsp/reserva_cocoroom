<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order">

	<?php
	if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?> 



			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
				<?php endif; ?>
			</p>



		<?php else : ?>
			
			<header id="site-header" class="site-header" role="banner">
				<div class="site-branding">
					<h1 class="site-title">
						¡Muchas gracias!
					</h1>
					<div id="contenedor_reserva_confirmada" style="margin-bottom: 48px">
						<span class="numero_bloque"><i class="material-icons">done</i></span>
						<p>
							Tu reserva ha sido confirmada. 
						</p>
					</div>

					<?php if(is_user_logged_in()){ ?>
						<p>
							Accede a <a class="enlace_iniciar_sesion" title="Inciar sesión" href="<?= home_url()?>/mis-reservas/">tus reservas</a> para ver los detalles. 
						</p>
					<?php }else{ ?>
						<p>
							Puedes finalizar tu registro introduciendo una contraseña mediante el enlace que has recibido por email. Después, podrás <a class="enlace_iniciar_sesion" title="Inciar sesión" href="./iniciar-sesion/">iniciar sesión</a> para ver los detalles de tu reserva. 
						</p>
					<?php } ?>
					<p>
						Pronto recibirás un email con las instrucciones para agregar a los jugadores. ¡Esperamos que lo disfrutes!
					</p>

				</div>
			</header>


		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>





	<?php else : ?>

		<header id="site-header" class="site-header" role="banner">
			<div class="site-branding">
				<h1 class="site-title">
					¡Muchas gracias!
				</h1>
				<div id="contenedor_reserva_confirmada">
					<span class="numero_bloque"><i class="material-icons">done</i></span>
					<p>
						Tu reserva ha sido confirmada. Pronto recibirás un email con las instrucciones para agregar a los jugadores.
					</p>
				</div>
			</div>
		</header>

	<?php endif; ?>

</div>
