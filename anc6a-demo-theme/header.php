<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="anc-header">
	<div class="anc-header__inner">
		<img class="anc-header__seal" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/anc-seal.jpg' ); ?>" alt="<?php esc_attr_e( 'Advisory Neighborhood Commission 6A seal', 'anc6a-demo' ); ?>">
		<div>
			<div class="anc-header__name"><?php bloginfo( 'name' ); ?></div>
			<div class="anc-header__sub"><?php bloginfo( 'description' ); ?></div>
		</div>
		<div class="anc-header__meeting"><?php esc_html_e( 'Public meetings', 'anc6a-demo' ); ?><br><strong><?php esc_html_e( '2nd Thursday, 7:00 pm', 'anc6a-demo' ); ?></strong></div>
	</div>
</header>

<div class="anc-accent-bar"></div>

<nav class="anc-nav">
	<div class="anc-nav__inner">
		<a href="#"><?php esc_html_e( 'Commissioners', 'anc6a-demo' ); ?></a>
		<a href="#"><?php esc_html_e( 'Committees', 'anc6a-demo' ); ?></a>
		<a href="#"><?php esc_html_e( 'Agendas', 'anc6a-demo' ); ?></a>
		<a href="#"><?php esc_html_e( 'Minutes / Reports', 'anc6a-demo' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/docket/' ) ); ?>" class="<?php echo is_post_type_archive( 'hb_decision' ) || is_singular( 'hb_decision' ) ? 'is-active' : ''; ?>"><?php esc_html_e( 'Docket', 'anc6a-demo' ); ?></a>
		<a href="#"><?php esc_html_e( 'Community Calendar', 'anc6a-demo' ); ?></a>
		<a href="#"><?php esc_html_e( 'Contact Us', 'anc6a-demo' ); ?></a>
	</div>
</nav>

<div class="anc-content">
	<div class="anc-card">
