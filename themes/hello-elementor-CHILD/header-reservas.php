<?php
/**
 * The template for displaying the header
 *
 * This is the template that displays all of the <head> section, opens the <body> tag and adds the site's header.
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$viewport_content = apply_filters( 'hello_elementor_viewport_content', 'width=device-width, initial-scale=1' );
$enable_skip_link = apply_filters( 'hello_elementor_enable_skip_link', true );
$skip_link_url = apply_filters( 'hello_elementor_skip_link_url', '#content' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo('charset'); ?>">
		<meta name="viewport" content="<?php echo esc_attr( $viewport_content ); ?>">
		<!-- <link rel="profile" href="https://gmpg.org/xfn/11"> -->
		<?php wp_head(); ?>

		<!-- <link rel="stylesheet" href="/resources/demos/style.css"> -->
		<!-- <link rel="stylesheet" href="/assets/font-awesome-4.7.0/css/font-awesome.min.css"> -->
		<!-- <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> -->
		<?php ?>	

		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

		<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
		<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
		<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
		<meta http-equiv="pragma" content="no-cache">

	</head>
		<body <?php body_class(); ?>>
		<?php wp_body_open(); ?>
			<?php if ( $enable_skip_link ) { ?>
				<a class="skip-link screen-reader-text" href="<?php echo esc_url( $skip_link_url ); ?>"><?php echo esc_html__( 'Skip to content', 'hello-elementor' ); ?></a>
			<?php } ?>
		<div id="header">
			<div class="container_menu_reservas">
				<div class="logo_header_container">
					<a href="<?php echo esc_url(home_url('/')); ?>"><?php the_custom_logo(); ?></a>
				</div>
				<div class="info_btn_menu_header">
					<div class="menu_home-container">
						<ul id="menu_home" class="menu">

							<?php if (!WC()->cart->is_empty()) { ?>
								<li class="menu-item menu-item-type-post_type menu-item-object-page">
									<a href="<?php echo esc_url(home_url('/')).'finalizar-compra/'; ?>">
										<i class="material-icons">shopping_cart</i>
										<span><?= __("Finalizar reserva","hello-elementor-child"); ?></span>
									</a>
								</li>
							<?php } ?>


							<?php if (!is_user_logged_in()) { ?>

								<li class="menu-item menu-item-type-post_type menu-item-object-page">
									<a href="<?php echo esc_url(home_url('/')).'iniciar-sesion/'; ?>">
										<i class="material-icons">login</i>
										<span><?= __("Iniciar Sesión","hello-elementor-child"); ?></span>
									</a>
								</li>

							<?php }else {?>
								
								<li class="menu-item menu-item-type-post_type menu-item-object-page">
									<a href="<?php echo esc_url(home_url('/')).'cerrar-sesion/'; ?>">
										<i class="material-icons">logout</i>
										<span><?= __("Cerrar Sesión","hello-elementor-child"); ?></span>
									</a>
								</li>
								
								<li class="menu-item menu-item-type-post_type menu-item-object-page">
									<a href="<?php echo esc_url(home_url('/')).'iniciar-sesion/'; ?>">
										<i class="material-icons">person</i>
										<span><?= __("Mi Cuenta","hello-elementor-child"); ?></span>
									</a>
								</li>

							<?php } ?>

						</ul>
					</div>
				</div>
			</div>
		</div>



		<?php
