<?php

if(isset($_POST['img_submit'])){
    $img_name=$_FILES['img_upload']['name'];
    $tmp_img_name=$_FILES['img_upload']['tmp_name'];
    move_uploaded_file($tmp_img_name,$img_name);
}

?>
<form action='resize-img.php' method='POST' enctype='multipart/form-data'>
<input type='file' name='img_upload'><br><br>
Size: <input type='number' step='0.1' name='size'>
<input type='submit' name='img_submit'>
<input type='reset'>
</form>


<br>
<?php
// SOURCE & DESTINATION
if(isset($_POST['img_submit'])){
$source = $_FILES['img_upload']['name'];
$dest = "resized.jpg";

// ORIGINAL DIMENSIONS
$size = getimagesize($_FILES['img_upload']['name']);
$width = $size[0];
$height = $size[1];

// RESIZE TO
$resize = $_POST['size'];
$rwidth = ceil($width * $resize);
$rheight = ceil($height * $resize);

// OPEN ORIGINAL IMAGE
$original = imagecreatefromjpeg($source);

// RESIZE IMAGE
$resized = imagecreatetruecolor($rwidth, $rheight);
imagecopyresampled(
    $resized, $original,
    0,0,0,0,
    $rwidth, $rheight,
    $width, $height
);

// SAVE RESIZED IMAGE
imagejpeg($resized, $dest);
echo "OK";

// CLEAN UP
imagedestroy($original);
imagedestroy($resized);

}
?>