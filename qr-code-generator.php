<?php

/*
* Plugin Name: QR Code Generator
* Plugin URI: https://jamiegrand.co.uk/qr-code-generator
* Description: A plugin to generate QR codes with images.
* Version: 1.0
* Author: jamie Grand
* Author URI: https://jamiegrand.co.uk
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qr_code_form_shortcode() {
    ob_start();
    qr_code_form_plugin_form();
    return ob_get_clean();
}
add_shortcode( 'qr_code_form', 'qr_code_form_shortcode' );

function qr_code_form_plugin_scripts() {
    // Register and enqueue the script that handles the form submission and QR code generation
    wp_register_script( 'qr-code-form-script', plugin_dir_url( __FILE__ ) . 'qr-code-form-script.js', array( 'jquery' ), '1.0', true );
    wp_enqueue_script( 'qr-code-form-script' );

    // Pass the ajaxurl variable to the script to use in the AJAX request
    wp_localize_script( 'qr-code-form-script', 'qr_code_form_ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}
add_action( 'wp_enqueue_scripts', 'qr_code_form_plugin_scripts' );

function qr_code_form_plugin_form() {
    // Check if the user is logged in, and if not, return without displaying the form
    if ( ! is_user_logged_in() ) {
        return;
    }
    ?>
    <form id="qr-code-form">
        <label for="website-address">Website address:</label>
        <input type="text" id="website-address" name="website-address" />
        <?php wp_nonce_field( 'qr_code_form_nonce', 'qr_code_form_nonce_field' ); ?>
        <input type="submit" value="Generate QR Code" />
    </form>
    <div id="qr-code-result"></div>
    <?php
}

function qr_code_form_plugin_submit() {
    error_log('AJAX request:');
    error_log(print_r($_POST, true));
    // Check the nonce to make sure the request is valid
    if ( ! wp_verify_nonce( $_POST['qr_code_form_nonce_field'], 'qr_code_form_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }

    // Print the website address and the result of the validation check
    $website_address = $_POST['website_address'];
    $is_valid = filter_var( $website_address, FILTER_VALIDATE_URL );
    error_log( 'Website address: ' . $website_address );
    error_log( 'Is valid: ' . $is_valid );

    // Validate the website address to make sure it is a valid URL
    if ( ! $is_valid ) {
        wp_send_json_error( 'Invalid website address' );
    }

    // Generate the QR code using the PHP QR Code library
    require_once plugin_dir_path( __FILE__ ) . 'phpqrcode/qrlib.php';
    $temp_dir = plugin_dir_path( __FILE__ ) . 'temp';
    if ( ! file_exists( $temp_dir ) ) {
        // Create the temp directory if it doesn't exist
        mkdir( $temp_dir );
    }
    $filename = $temp_dir . '/' . md5( uniqid() ) . '.png';
    QRcode::png( $website_address, $filename, QR_ECLEVEL_L, 4 );
    $qr_code_image_url = plugins_url( 'temp/' . basename( $filename ), __FILE__ );
    $qr_code_image = file_get_contents( $filename );
    $qr_code_image = 'data:image/png;base64,' . base64_encode( $qr_code_image );
    unlink( $filename );



    // Return the QR code image as the response to the AJAX request
    $result = array(
       'qr_code_image_url' => $qr_code_image_url,
       'qr_code_image' => $qr_code_image
    );
    
    wp_send_json_success( $result );
}
add_action( 'wp_ajax_qr_code_form_submit', 'qr_code_form_plugin_submit' );

function test_qr_code_shortcode() {
    ob_start();

    // Test the QRcode::png function
    require_once plugin_dir_path( __FILE__ ) . 'phpqrcode/qrlib.php';
    $temp_dir = plugin_dir_path( __FILE__ ) . 'temp';
    if ( ! file_exists( $temp_dir ) ) {
        // Create the temp directory if it doesn't exist
        mkdir( $temp_dir );
    }
    $filename = $temp_dir . '/' . md5( uniqid() ) . '.png';
    $website_address = 'https://example.com';
    QRcode::png( $website_address, $filename, QR_ECLEVEL_L, 4 );

    // Check if the QR code image was generated
    if ( file_exists( $filename ) ) {
        // Display the QR code image
        $qr_code_image = file_get_contents( $filename );
        $qr_code_image = 'data:image/png;base64,' . base64_encode( $qr_code_image );
        echo '<img src="' . $qr_code_image . '" alt="QR Code">';
    } else {
        // Display an error message if the QR code image was not generated
        echo '<p>Error: QR code image was not generated</p>';
    }

    return ob_get_clean();
}
add_shortcode( 'test_qr_code', 'test_qr_code_shortcode' );