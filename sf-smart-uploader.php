<?php
/*
Plugin Name: SF Smart File Uploader for WooCommerce
Description: Advanced drag & drop uploader with preview, progress bar, and attachments
Version: 1.0
Author: Sufian Fareed
Author URI: https://sufianfareed.com/
*/

if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function(){
    if (is_checkout()) {
        wp_enqueue_style('sf-uploader-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('sf-uploader-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], null, true);

        wp_localize_script('sf-uploader-script', 'sf_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'max_files' => get_option('sf_max_files',3)
        ]);
    }
});

add_action('woocommerce_after_order_notes', function(){

    echo '<div class="sf-box">';
    echo '<h3>Upload Your Files</h3>';
    echo '<div id="sf-dropzone">Drag & Drop OR <button type="button" id="sf-upload-btn">Upload</button></div>';
    echo '<input type="file" id="sf-file-input" multiple style="display:none;">';
    echo '<div id="sf-preview"></div>';
    echo '<input type="hidden" name="sf_files" id="sf_files">';
    echo '</div>';
});

add_action('wp_ajax_sf_upload', 'sf_upload');
add_action('wp_ajax_nopriv_sf_upload', 'sf_upload');

function sf_upload(){
    $upload = wp_handle_upload($_FILES['file'], ['test_form'=>false]);
    wp_send_json_success([
        'url'=>$upload['url'],
        'path'=>$upload['file'],
        'name'=>basename($upload['file'])
    ]);
}

add_action('woocommerce_checkout_create_order', function($order){
    if (!empty($_POST['sf_files'])) {
        $files = json_decode(stripslashes($_POST['sf_files']), true);
        $order->update_meta_data('sf_uploaded_files', $files);
    }
});

add_filter('woocommerce_email_attachments','sf_attach_files',10,3);

function sf_attach_files($attachments, $email_id, $order){

    if(!$order) return $attachments;

    // IMPORTANT: only these emails
    if(!in_array($email_id, [
        'customer_processing_order',
        'customer_completed_order',
        'new_order'
    ])){
        return $attachments;
    }

    $files = $order->get_meta('sf_uploaded_files');

    if(!empty($files)){
        foreach($files as $file){
            if(file_exists($file['path'])){
                $attachments[] = $file['path'];
            }
        }
    }

    return $attachments;
}



add_action('woocommerce_admin_order_data_after_billing_address', function($order){

    $files = $order->get_meta('sf_uploaded_files');

    if(!empty($files)){
        echo '<h3>Uploaded Files</h3>';

        foreach($files as $file){
            echo '<p><a href="'.$file['url'].'" target="_blank">View File</a></p>';
        }
    }

});