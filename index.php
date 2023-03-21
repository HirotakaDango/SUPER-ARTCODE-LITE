<?php
session_start();

$uploads_dir = 'uploads/';
$thumbs_dir = 'thumbs/';
$max_thumb_width = 200;

// Handle file upload
if (isset($_FILES['image'])) {
    $tmp_name = $_FILES['image']['tmp_name'];
    $name = $_FILES['image']['name'];
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $new_name = uniqid() . '.' . $extension;
    move_uploaded_file($tmp_name, $uploads_dir . $new_name);

    // Rename file to sequential number
    $i = 1;
    while (file_exists($uploads_dir . $i . '.' . $extension)) {
        $i++;
    }
    rename($uploads_dir . $new_name, $uploads_dir . $i . '.' . $extension);
    $new_name = $i . '.' . $extension;

    // Generate thumbnail
    if ($extension == 'jpg' || $extension == 'jpeg') {
        $image = imagecreatefromjpeg($uploads_dir . $new_name);
    } elseif ($extension == 'png') {
        $image = imagecreatefrompng($uploads_dir . $new_name);
    } elseif ($extension == 'gif') {
        $image = imagecreatefromgif($uploads_dir . $new_name);
    }
    $thumb_width = min($max_thumb_width, imagesx($image));
    $thumb_height = $thumb_width / imagesx($image) * imagesy($image);
    $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumb_width, $thumb_height, imagesx($image), imagesy($image));
    imagedestroy($image);
    imagepng($thumb, $thumbs_dir . $new_name);
    imagedestroy($thumb);

    header('Location: index.php');
    exit();
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: session.php');
    exit();
}

// List uploaded images, sorted by latest
$images = glob($uploads_dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
usort($images, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Image Upload</title>
  </head>
  <body>
    <?php include('header.php'); ?>
    <div class="images mt-1">
      <?php if (isset($_SESSION['username'])) { ?>
        <?php foreach ($images as $image) { ?>
          <a href="<?php echo $image; ?>"><img src="<?php echo $thumbs_dir . basename($image); ?>" width="200"></a>
        <?php } ?>
        <?php } else { ?>
        <p>You need to <a href="session.php">Login</a> or <a href="session.php">Register</a> to upload images.</p>
      <?php } ?>
    </div>
    <style>
      .image-container {
        margin-bottom: -24px;  
      }
      
      .images {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        grid-gap: 2px;
        justify-content: center;
        margin-right: 3px;
        margin-left: 3px;
      }

      .images a {
        display: block;
        border-radius: 4px;
        overflow: hidden;
        border: 2px solid #ccc;
      }

      .images img {
        width: 100%;
        height: auto;
        object-fit: cover;
        height: 200px;
        transition: transform 0.5s ease-in-out;
      }
    
    </style>
  </body>
</html>
