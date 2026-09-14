<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function anc6a_demo_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
}
add_action( 'after_setup_theme', 'anc6a_demo_setup' );
