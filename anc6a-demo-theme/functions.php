<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function anc6a_demo_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
}
add_action( 'after_setup_theme', 'anc6a_demo_setup' );

function anc6a_demo_enqueue_assets() {
	wp_enqueue_style( 'anc6a-demo-fonts', 'https://fonts.googleapis.com/css2?family=Lora:wght@400;600&family=Lato:ital,wght@0,400;0,700;1,400&display=swap', array(), null );
	wp_enqueue_style( 'anc6a-demo-style', get_stylesheet_uri(), array( 'anc6a-demo-fonts' ), wp_get_theme()->get( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'anc6a_demo_enqueue_assets' );
