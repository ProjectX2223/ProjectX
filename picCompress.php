<?php
/*
Plugin Name: Image Compression
Plugin URI: http://www.example.com/
Description: Compress images on upload
Version: 1.0
Author: John Doe
Author URI: http://www.example.com/
License: GPL2
*/

add_filter('wp_handle_upload', 'compress_uploaded_image');

function compress_uploaded_image($image) {
    $image_file = $image['file'];
    $info = getimagesize($image_file);
    if ($info['mime'] == 'image/jpeg') {
        $image_resource = imagecreatefromjpeg($image_file);
        imagejpeg($image_resource, $image_file, 75);
    } elseif ($info['mime'] == 'image/png') {
        $image_resource = imagecreatefrompng($image_file);
        imagepng($image_resource, $image_file, 5);
    }
    return $image;
}
?>
