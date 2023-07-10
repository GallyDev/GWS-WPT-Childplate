<?php


/**
 * Gravity Forms PLZ & Ort change order
 */

 add_filter( 'gform_address_display_format', 'address_format' );
 function address_format( $format ) {
     return 'zip_before_city';
 }


//Remove uneccesary

add_action( 'template_redirect', function(){
    ob_start( function( $buffer ){
        $buffer = str_replace( array( 'type="text/javascript"', "type='text/javascript'" ), '', $buffer );
        
        // Also works with other attributes...
        $buffer = str_replace( array( 'type="text/css"', "type='text/css'" ), '', $buffer );
        $buffer = str_replace( array( 'frameborder="0"', "frameborder='0'" ), '', $buffer );
        $buffer = str_replace( array( 'scrolling="no"', "scrolling='no'" ), '', $buffer );
        
        return $buffer;
    });
});

// REMOVE WP EMOJI
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );


//Alternativtext Column in MediaLibary
function wpse_media_extra_column( $cols ) {
    $cols["alt"] = "ALT";
    return $cols;
}

function wpse_media_extra_column_value( $column_name, $id ) {
    if( $column_name == 'alt' )
        echo get_post_meta( $id, '_wp_attachment_image_alt', true);
}
add_filter( 'manage_media_columns', 'wpse_media_extra_column' );
add_action( 'manage_media_custom_column', 'wpse_media_extra_column_value', 10, 2 );

/**
 * Check if Search Engine Indexing is enabled and displayes a massage if it is enabled
 * @return void
 */
function checkSearchEngineIndexing() {
    $blog_public = get_option('blog_public');
    $msg = new WpMessages();
    
    if ($blog_public === '0') {
        $message = 'Die Seite ist nicht Öffentlich und wird nicht von Suchmaschinen indexiert.  
                    <a class="button button-primary" href="'.esc_url(admin_url('options-reading.php')).'">Einstellungen</a>';
        $msg->wpSetMessage('error', $message, false);
        $msg->wpSendMessage();
    }
    
}

add_action('init', 'checkSearchEngineIndexing');

/**
 * Checks and sets Secure headers
 */
function checkAndSetHeaders()
{
    $allHeaders = getallheaders();
    $headersToUse = ['Strict-Transport-Security' => 'Strict-Transport-Security: max-age=31536000',
                    'X-Content-Type-Options' => 'X-Content-Type-Options: nosniff', 
                    'X-Frame-Options' => 'X-Frame-Options: sameorigin', 
                    'Referrer-Policy' => 'Referrer-Policy: origin-when-cross-origin'];
    foreach ($headersToUse as $header => $headerSetUp) {
        if (!isset($allHeaders[$header])) {
            header($headerSetUp);
        }
    }
}
add_action('wp_headers', 'checkAndSetHeaders');