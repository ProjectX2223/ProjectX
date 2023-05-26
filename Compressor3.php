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

 use Intervention\Image\Image;


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

// Compress the uploaded image
add_filter('wp_handle_upload', 'image_compressor_handle_upload');

function image_compressor_handle_upload($fileinfo) {
    $ratio = get_option('image_compressor_ratio', 80);
    if ($fileinfo['type'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($fileinfo['file']);
        imagejpeg($image, $fileinfo['file'], $ratio);
    } elseif ($fileinfo['type'] == 'image/png') {
        $image = imagecreatefrompng($fileinfo['file']);
        imagepng($image, $fileinfo['file'], round($ratio / 100 * 9));
    }
    return $fileinfo;
}




add_action('admin_menu', 'image_compressor_add_menu');
function image_compressor_add_menu() {
    add_submenu_page(
        'options-general.php',
        'Select Images',
        'Select Images',
        'manage_options',
        'image_compressor_select_images',
        'image_compressor_select_images_page'
    );
}




// when clciking on page and sumbit，go to click event callback image_compressor_compress_images 
add_action('admin_post_compress_images', 'image_compressor_compress_images');


function image_compressor_select_images_page() {
    ?>
    <div class="wrap">
        <h1>Select Images</h1>
        <!-- here action。might change  -->
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <!--  bug fixed -->
            <input type="hidden" name="action" value="compress_images">
            <?php wp_nonce_field('compress_images'); ?>
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <th class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </th>
                        <th class="manage-column">Image</th>
                        <!-- <th class="manage-column">Compressed</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $args = array(
                        'post_type' => 'attachment',
                        'post_mime_type' => 'image',
                        'post_status' => 'inherit',
                        'posts_per_page' => -1
                    );
                    $attachments = get_posts($args);
                    foreach ($attachments as $attachment) {
                        $image_src = wp_get_attachment_image_src($attachment->ID, 'full')[0];
                        $compressed_image_src = get_post_meta($attachment->ID, 'compressed_image_src', true);
                        ?>
                        <!-- <tr>
                            <td><input type="checkbox" name="image_ids[]" value="<?php echo $attachment->ID; ?>"></td>
                            <td><img src="<?php echo $image_src; ?>" width="100"></td>
                            <td>
                                <?php if ($compressed_image_src) { ?>
                                    <img src="<?php echo $compressed_image_src; ?>" width="100">
                                <?php } else { ?>
                                    Not Compressed Yet
                                <?php } ?>
                            </td>
                        </tr> -->

                        <tr>
                            <td><input type="checkbox" name="image_ids[]" value="<?php echo $attachment->ID; ?>"></td>
                            <!-- <td><img src="<?php echo $image_src; ?>" width="100"></td> -->
                            <td>
                                <?php if ($compressed_image_src) { ?>
                                    <img src="<?php echo $compressed_image_src; ?>" width="100">
                                <?php } else { ?>
                                   <img src="<?php echo $image_src; ?>" width="100">
                                <?php } ?>
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





//from post form nouce 
function image_compressor_compress_images() {
    check_admin_referer('compress_images');
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $image_ids = $_POST['image_ids'];
    $total_saved_size = 0;

    foreach ($image_ids as $image_id) {
        //guid=url
        $image_src = wp_get_attachment_image_src($image_id, 'full')[0];
        //image_id的附件的自定义字段= compressed_image_src，压缩文件作为mata数据存起来
        $compressed_image_src = get_post_meta($image_id, 'compressed_image_src', true);
        //测试
        // $original_size = filesize(get_attached_file($image_id));
           $original_file = get_attached_file($image_id);
           $original_size = filesize($original_file);

       // $original_size = filesize(get_attached_file($image_src));
        //没有这个字段compressed_image_src的话，运行下面
        if (!$compressed_image_src) {
            $ratio = get_option('image_compressor_ratio', 80);
            //user defined function：image_compressor_compress_image，该函数返回的是新的图像资源的资源id
            $image_compressed = image_compressor_compress_image($image_src, $ratio);
            if ($image_compressed) {
                $attachment = get_post($image_id);
                $filename = basename($attachment->guid);
                $upload_dir = wp_upload_dir();
                $compressed_file = $upload_dir['path'] . '/' . $filename;

                // Save the compressed image to a file
                if (wp_check_filetype($compressed_file)['ext'] === 'jpg') {
                    // 新的附件资源，保存的路径名，压缩率
                    imagejpeg($image_compressed, $compressed_file, $ratio);
                } else {
                    imagepng($image_compressed, $compressed_file, round(9 * $ratio / 100));
                }

                // Replace the original attachment with the compressed image
                $file_type = wp_check_filetype($compressed_file)['ext'];
                //路径名
                $file = array(
                    'name' => $filename,
                    'tmp_name' => $compressed_file,
                );
               // 将本地文件上传到WordPress的媒体库，并创建图片附件，返回附件id
                $attachment_id = media_handle_sideload($file, $image_id);
                //case1 ：原图image 在压缩后成功被删除后的话，新的被压缩的文件就可以返回上传，返回一个新attachment_id = 正确对象true
                //case2 ：原图image 在压缩后没有被删除后的话，新的被压缩的文件返回上传，但是imageid存在了，所以返回一个attachment_id = 错误对象false
//错误的话=true，！后=false， 对的话=false，！后true，所以这里说的是attachment-id=case 1
                if (!is_wp_error($attachment_id)) {
                    $compressed_url = wp_get_attachment_url($attachment_id);
                    // 不存在 找不到文件
                    //$total_saved_size += filesize($compressed_file) - filesize(get_attached_file($image_id));
                    //$total_saved_size += abs(filesize($compressed_file) - $original_size);
                    $total_saved_size += abs(filesize(get_attached_file($attachment_id) )- $original_size);
                    update_post_meta($image_id, 'compressed_image_src', $compressed_url);

//替代
// 将原始附件的 URL 替换为压缩后附件的 URL
    $image_src = $compressed_url;
    // 获取附件的元数据
$attachment_metadata = wp_get_attachment_metadata($image_id);

// 更新元数据中的文件路径
$compressed_image_path = str_replace(wp_get_upload_dir()['basedir'], '', $compressed_url);
$attachment_metadata['file'] = $compressed_url;
$attachment_metadata['sizes']['full']['file'] = $compressed_url;

// 更新附件的元数据
wp_update_attachment_metadata($image_id, $attachment_metadata);


  
                    
                }
            }//
        }//
    }


    $message = sprintf(__('Images compressed successfully! Total saved size: %s'), size_format($total_saved_size));
    echo '<div class="notice notice-success"><p>' . $message . '</p></div>';

//bug fixed

    ?>

     <a class="button" href="<?php echo admin_url('options-general.php?page=image_compressor_select_images'); ?>"><?php _e('Back to select images'); ?></a>
    <?php
    exit;
}


//compress image function，该函数返回的是新的图像资源的资源id
function image_compressor_compress_image($image_src, $ratio) {

    // Get the file type and create the corresponding image resource
    $file_type = wp_check_filetype($image_src)['ext'];
    if ($file_type === 'jpg' || $file_type === 'jpeg') {
        $image = imagecreatefromjpeg($image_src);
    } elseif ($file_type === 'png') {
        $image = imagecreatefrompng($image_src);
    } else {
        return false;
    }

    // Compress the image
    ob_start();
    if ($file_type === 'jpg' || $file_type === 'jpeg') {
        imagejpeg($image, null, $ratio);
    } else {
        imagepng($image, null, round(9 * $ratio / 100));
    }
    $compressed_image_data = ob_get_clean();

    // release the image resource and return the compressed image resource
    imagedestroy($image);
    return imagecreatefromstring($compressed_image_data);
}



