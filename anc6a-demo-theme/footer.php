	</div>
</div>

<div class="anc-footer">
	<div>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></div>
	<div><?php esc_html_e( 'Demo site — not an official government record.', 'anc6a-demo' ); ?></div>
</div>

<?php if ( is_page( 'agendas' ) && shortcode_exists( 'mwai_chatbot' ) ) : ?>
	<?php echo do_shortcode( '[mwai_chatbot]' ); ?>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
