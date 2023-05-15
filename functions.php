<?php
/**
 * Theme functions and definitions.
 * For additional information on potential customization options,
 * read the developers' documentation:
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'GWS_CHILDPLATE', '1.0.0' );

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function gws_childplate_scripts_styles() {

	wp_enqueue_style(
		'gws_childplate-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		GWS_CHILDPLATE
	);

	wp_enqueue_script('functionjs', get_stylesheet_directory_uri().'/assets/js/functions.js', array('jquery'), false, true);

	
}
add_action( 'wp_enqueue_scripts', 'gws_childplate_scripts_styles', 20 );


/** 
 * mainmods
 */
require_once('mods/mainmods.php'); 

/**
 * Check Plugins
 */
require_once('mods/checkPlugins.php');
$checkPlugin = new checkPlguins();
$checkPlugin->isInstalled();