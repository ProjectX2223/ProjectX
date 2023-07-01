<?php
/*
Plugin Name: Image Compressor
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
        'Compression Level',
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
    echo '<p>Enter the compression level to use for uploaded images:</p>';
}



function image_compressor_ratio_callback() {
    $value = get_option('image_compressor_ratio', 80);
    ?>
    <input type="radio" name="image_compressor_ratio" value="80" <?php checked($value, 80); ?> /> Mild compression
</br>
    <input type="radio" name="image_compressor_ratio" value="85" <?php checked($value, 85); ?> /> Moderate compression
</br>
    <input type="radio" name="image_compressor_ratio" value="65" <?php checked($value, 65); ?> /> Intense compression
    <?php
    echo '<div class="notice notice-success"><p>' .'new Size: '. $value . '</p></div>';
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
        // imagepng($image, $fileinfo['file'], round($ratio / 100 * 9));
     if($ratio==80)
         imagepng($image, $fileinfo['file'], 4);
        elseif($ratio==85)
         imagepng($image, $fileinfo['file'], 6);
        else
         imagepng($image, $fileinfo['file'], 3);
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
        
        <h2> <?php
        $ratio = get_option('image_compressor_ratio', 80);
    if ($ratio == 85) {
        echo " Compression Level :  ". "Moderate Compression";
    } elseif ($ratio == 80) {
        echo " Compression Level :  ". "Mild Compression";
    } else {
        echo " Compression Level :  ". "Intense Compression";
    }
            // echo " Compression Level(1-100): ". get_option('image_compressor_ratio', 80);
          ?></h2>
          <h3> <?php         
            echo "Currently, only JPEG, JPG, and PNG image types are allowed for compression.";
          ?></h3>

           <h3> <?php         
           
            echo "We plan to support more image types in the future. Stay tuned for updates.";
          ?></h3>



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
                        <th class="manage-column">Current Size</th>
                        <th class="manage-column">Already Compressed</th>
                    
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
                         $value = get_post_meta($attachment->ID, 'compressed', true); 
    if ($value === 'Yes') {
         echo '<span class="dashicons dashicons-yes"></span>';
    } else {
       echo '<span class="dashicons dashicons-no"></span>';
    }


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
    $ratio = get_option('image_compressor_ratio', 80);
    $image_data = wp_get_attachment_metadata($old_image_id);
    $image_file = get_attached_file($old_image_id);
    $image_type = wp_check_filetype($image_file)['ext'];
    //mark
    if ($image_type === 'jpg' || $image_type === 'jpeg'||$image_type === 'png')
    add_post_meta($old_image_id, 'compressed', 'Yes');
    else
    add_post_meta($old_image_id, 'compressed', 'No');

    $old_image_size = filesize($image_file);
    echo '<div class="notice notice-success"><p>' .'Old Size: '. size_format($old_image_size) . '</p></div>';

    if ($image_type === 'jpg' || $image_type === 'jpeg') {
        $image_resource = imagecreatefromjpeg($image_file);
        imagejpeg($image_resource, $image_file,  $ratio);

    } elseif ($image_type === 'png') {
        $image_resource = imagecreatefrompng($image_file);
        // imagepng($image_resource, $image_file, round($ratio / 100 * 9));
         if($ratio==80)
         imagepng($image_resource, $image_file, 4);
        elseif($ratio==85)
         imagepng($image_resource, $image_file, 6);
        else
         imagepng($image_resource, $image_file, 3);

     
    }
    
   clearstatcache();
    if ($image_data) {
        //here original size of a image 
        $image_data['filesize'] = filesize($image_file);
        wp_update_attachment_metadata($old_image_id, $image_data);
    }
    

    $new_image_size = filesize($image_file);
    $saved_size = abs($old_image_size - $new_image_size);
    $total_saved_size += $saved_size;
    echo '<div class="notice notice-success"><p>' .'new Size: '. size_format($image_data['filesize']) . '</p></div>';

    

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




//------------------compression function definition （selection handling）----------------------

