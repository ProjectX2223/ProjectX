<?php
/*
Plugin Name: Image Compressor 3
Plugin URI: https://www.example.com
Description: A plugin that compresses uploaded images.
Version: 1.0
Author: chang
Author URI: https://www.example.com
License: GPLv2 or later
*/

//------------------setting page ----------------------------------------

// Add the settings page to the WordPress admin menu
add_action('admin_menu', 'image_compressor_menu');

function image_compressor_menu() {
    add_options_page(
        'Image Compressor Settings',
        'Image Compressor',
        'manage_options',
        'image-compressor',
        'image_compressor_settings_page'
    );
}


// Define the settings page
function image_compressor_settings_page() {
    ?>
    <div class="wrap">
        <h1>Image Compressor Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('image_compressor_settings_group'); ?>
            <?php do_settings_sections('image_compressor_settings'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}



// Register the plugin settings
add_action('admin_init', 'image_compressor_register_settings');

function image_compressor_register_settings() {
    register_setting(
        'image_compressor_settings_group',
        'image_compressor_ratio',
        'intval'
    );

     register_setting(
        'image_compressor_settings_group',
        'compression_during_upload',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'yes'
        )
    );


    add_settings_section(
        'image_compressor_section',
        'Compression Settings',
        'image_compressor_section_callback',
        'image_compressor_settings'
    );
    add_settings_field(
        'image_compressor_ratio',
        'Compression Ratio',
        'image_compressor_ratio_callback',
        'image_compressor_settings',
        'image_compressor_section'
    );

     add_settings_field(
        'compression_during_upload',
        'Enable compression during upload？',
        'compression_during_upload_callback',
        'image_compressor_settings',
        'image_compressor_section'
    );


}


// Define the settings section
function compression_during_upload_callback() {
    $option = get_option('compression_during_upload');
    ?>
    <label>
        <input type="radio" name="compression_during_upload" value="yes" <?php checked('yes', $option); ?>>
        Yes
    </label>
    <br>
    <label>
        <input type="radio" name="compression_during_upload" value="no" <?php checked('no', $option); ?>>
        No
    </label>
    <?php

   // echo $option ;

    
}


// Define the settings section
function image_compressor_section_callback() {
    echo '<p>Enter the compression ratio to use for uploaded images:</p>';
}

// Define the settings field
function image_compressor_ratio_callback() {
    $value = get_option('image_compressor_ratio', 80);
    echo '<input type="number" min="1" max="100" name="image_compressor_ratio" value="' . esc_attr($value) . '" />';
}



//------------------setting page ----------------------------------------

//------------------compression function definition （upload handling）----------------------

function image_compressor_update_compress_enabled() {
    $should_compress = get_option('compression_during_upload');
    
    if ($should_compress === 'yes') {
        add_filter('wp_handle_upload', 'image_compressor_handle_upload');
    } else {
        remove_filter('wp_handle_upload', 'image_compressor_handle_upload');
    }
}
add_action('init', 'image_compressor_update_compress_enabled');



function image_compressor_handle_upload($fileinfo) {
    $ratio = get_option('image_compressor_ratio', 80);
    if ($fileinfo['type'] == 'image/jpeg') {
        //return：Image resource" or"Image handle
        $image = imagecreatefromjpeg($fileinfo['file']);
        // accept （Image resource" or"Image handle，path，quality）
        // return true/false
        imagejpeg($image, $fileinfo['file'], $ratio);
    } elseif ($fileinfo['type'] == 'image/png') {
        $image = imagecreatefrompng($fileinfo['file']);
        imagepng($image, $fileinfo['file'], round($ratio / 100 * 9));
    }
    return $fileinfo;
}

//------------------compression function definition （upload handling）----------------------

//------------------compression function definition （selection handling）----------------------

function BulkOptimization() {
    add_media_page(
        'Bulk Optimization Page', 
        'Bulk Optimization', // title on menue
        'manage_options', 
        'image-compressor', // slug
        'image_compressor_select_images_page' //callback function
    );
}


add_action('admin_menu', 'BulkOptimization');


// when clciking on page and sumbit，go to click event callback image_compressor_compress_images 
// when wordpress accept form = compress_images_value'，it invokes call back function
add_action('admin_post_compress_images_value', 'image_compressor_compress_images');
//   ｜
//   V

function image_compressor_select_images_page() {
    ?>
    <div class="wrap">
        <h1>Select Images</h1>
        <h2> <?php
            echo " Compression ratio(1-100): ". get_option('image_compressor_ratio', 80);
          ?></h2>
          <h2> <?php         
            echo "If you remian the same compression ratio, compression will not happen";
          ?></h2>



        <!-- here  form = compress_images_value-->
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="compress_images_value">
         <!-- here form = compress_images_value  -->   
            <?php wp_nonce_field('compress_images'); ?>
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                       <th class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        </th> 
                        <th class="manage-column">Image</th>
                        <th class="manage-column">Size Before Compression</th>
                        <th class="manage-column">Possible Size After Compression</th>
                    
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // select all images，and save it to variable = attachments
                    //[0]: The URL of the full-sized image.
                    //[1]: The width of the full-sized image.
                    //[2]: The height of the full-sized image.
                    //[3]: Whether the image is a thumbnail (true/false).
                    $args = array(
                        'post_type' => 'attachment',
                        'post_mime_type' => 'image',
                        'post_status' => 'inherit',
                        'posts_per_page' => -1
                    );

                    $attachments = get_posts($args);
                    foreach ($attachments as $attachment) {
                        $image_src = wp_get_attachment_image_src($attachment->ID, 'full')[0];
                        $ratio = get_option('image_compressor_ratio', 80);
                        ?>

                        <tr>
                            <td><input type="checkbox" name="image_ids[]" value="<?php echo $attachment->ID; ?>"></td>
                            <td>
                                
                                   <img src="<?php echo $image_src; ?>" width="100">
                               
                            </td>

                            <td>
                                <?php
                                $file_size = filesize(get_attached_file($attachment->ID));
                                $formatted_size = size_format($file_size, 2);
                                echo $formatted_size;
                                 ?>
                              
                            </td>
                            <td>
                                <?php
                                $file_size1 = filesize(get_attached_file($attachment->ID))* $ratio/ 100 ;
                                $formatted_size1 = size_format($file_size1, 2);
                                echo $formatted_size1;
                                 ?>
                               
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <?php submit_button('Compress Selected Images'); ?>
        </form>
    </div>
    <?php
}


//------------------compression function definition （submit handling）----------------------

function image_compressor_compress_images() {
    check_admin_referer('compress_images');
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $image_ids = $_POST['image_ids'];
    $total_saved_size = 0;

    foreach ($image_ids as $old_image_id) {
        $old_image_src = wp_get_attachment_image_src($old_image_id, 'full')[0];
        $ratio = get_option('image_compressor_ratio', 80);

        $old_image_size = filesize(get_attached_file($old_image_id));


        // return 内存的id and create a new image to replace old one
        $new_image_resource = image_compressor_compress_image($old_image_src, $ratio);
        if ($new_image_resource) {
            $attachment = get_post($old_image_id);
            $filename = basename($attachment->guid);
            $upload_dir = wp_upload_dir();
            // echo '<div class="notice notice-success"><p>' . $filename . '</p></div>';

            $compressed_file = $upload_dir['path'] . '/' . $filename;
           
            // 上传压缩后的图像并替换原始图像
            $file = array(
                'name' => $filename,
                'tmp_name' => $compressed_file,
            );

            //return：  new id ｜ paramter：pso info，post id （attached to ） 
            $attachment_id = media_handle_sideload($file, $old_image_id);

        
        // calculation
        $new_image_size = filesize(get_attached_file($attachment_id));
        $saved_size = $old_image_size - $new_image_size;
        $total_saved_size += $saved_size;

        //delete old image
            wp_delete_attachment($old_image_id, true);
        }

        // release resource
        imagedestroy($new_image_resource);
    }

  

      $message = sprintf(__('Images compressed successfully! Total saved size: %s'), size_format($total_saved_size));
    ?>
    <div class="notice notice-success" style="text-align: center; margin: 20px auto; padding: 10px; background-color: #eaf2ff;">
        <p><?php echo $message; ?></p>
    </div>

    <div style="text-align: center; margin-bottom: 20px;">
        <a class="button button-primary" href="<?php echo admin_url('upload.php?page=image-compressor'); ?>"><?php _e('Back to select images'); ?></a>
    </div>
    <?php
    exit;
}

// compress image function，该函数返回的是新的图像资源的资源id
function image_compressor_compress_image($image_src, $ratio) {
    $file_type = wp_check_filetype($image_src)['ext'];
    if ($file_type === 'jpg' || $file_type === 'jpeg') {
        $image = imagecreatefromjpeg($image_src);
    } elseif ($file_type === 'png') {
        $image = imagecreatefrompng($image_src);
    } else {
        return false;
    }

    ob_start();
    if ($file_type === 'jpg' || $file_type === 'jpeg') {
        imagejpeg($image, null, $ratio);
    } else {
        imagepng($image, null, round(9 * $ratio / 100));
    }
    $compressed_image_data = ob_get_clean();
    imagedestroy($image);
    return imagecreatefromstring($compressed_image_data);
}



//------------------compression function definition （selection handling）----------------------

