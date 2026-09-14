<?php
/**
 * Archive template for hb_decision. Uses the active theme's own
 * header/footer via get_header()/get_footer(), so it inherits the
 * site's normal look without the plugin needing to know anything
 * about the theme.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="hb-archive-main">
	<h1 class="hb-archive-title"><?php esc_html_e( 'Public Docket', 'hearback-cabinet' ); ?></h1>
	<?php echo hb_render_cabinet(); // phpcs:ignore -- built entirely from escaped parts. ?>
</main>
<?php
get_footer();
