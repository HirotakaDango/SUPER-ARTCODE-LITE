<?php
ob_start();
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: session.php');
    exit();
}

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
    $thumb_height = round($thumb_width / imagesx($image) * imagesy($image));
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
  </head>
  <body>
    <nav class="navbar bg-body-tertiary fixed-top">
      <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">Super ArtCODE Lite</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
          <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
          </div>
          <div class="offcanvas-body">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
              <li>
                <form method="post" action="session.php"><button class="btn btn-sm btn-danger fw-bold mb-2" name="logout">Logout</button></form>
              </li>
              <li>
                <img class="border border-2 rounded object-fit-cover" id="file-ip-1-preview" style="height: 350px; width: 100%; margin-bottom: 15px;">
                <form method="post" action="index.php" enctype="multipart/form-data">
                  <input class="form-control" type="file" name="image" id="file-ip-1" accept="image/*" onchange="showPreview(event);">
                  <button class="w-100 btn btn-sm btn-primary fw-bold">Upload</button>
                </form>      
              </li>
            </ul>
          </div>
        </div>
      </div>
    </nav>
    <br><br>
    <div class="images mt-2">
      <?php if (isset($_SESSION['username'])) { ?>
        <?php foreach ($images as $image) { ?>
          <a href="<?php echo $image; ?>"><img class="lazy-load" data-src="<?php echo $thumbs_dir . basename($image); ?>" width="200"></a>
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
    <script>
        document.addEventListener("DOMContentLoaded", function() {
          let lazyloadImages;
          if("IntersectionObserver" in window) {
            lazyloadImages = document.querySelectorAll(".lazy-load");
            let imageObserver = new IntersectionObserver(function(entries, observer) {
              entries.forEach(function(entry) {
                if(entry.isIntersecting) {
                  let image = entry.target;
                  image.src = image.dataset.src;
                  image.classList.remove("lazy-load");
                  imageObserver.unobserve(image);
                }
              });
            });
            lazyloadImages.forEach(function(image) {
              imageObserver.observe(image);
            });
          } else {
            let lazyloadThrottleTimeout;
            lazyloadImages = document.querySelectorAll(".lazy-load");

            function lazyload() {
              if(lazyloadThrottleTimeout) {
                clearTimeout(lazyloadThrottleTimeout);
              }
              lazyloadThrottleTimeout = setTimeout(function() {
                let scrollTop = window.pageYOffset;
                lazyloadImages.forEach(function(img) {
                  if(img.offsetTop < (window.innerHeight + scrollTop)) {
                    img.src = img.dataset.src;
                    img.classList.remove('lazy-load');
                  }
                });
                if(lazyloadImages.length == 0) {
                  document.removeEventListener("scroll", lazyload);
                  window.removeEventListener("resize", lazyload);
                  window.removeEventListener("orientationChange", lazyload);
                }
              }, 20);
            }
            document.addEventListener("scroll", lazyload);
            window.addEventListener("resize", lazyload);
            window.addEventListener("orientationChange", lazyload);
          }
        })
    </script>
    <script>
      function showPreview(event){
        if(event.target.files.length > 0){
          var src = URL.createObjectURL(event.target.files[0]);
          var preview = document.getElementById("file-ip-1-preview");
          preview.src = src;
          preview.style.display = "block";
        }
      }
    </script> 
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js" integrity="sha384-mQ93GR66B00ZXjt0YO5KlohRA5SY2XofN4zfuZxLkoj1gXtW8ANNCe9d5Y3eG5eD" crossorigin="anonymous"></script>
  </body>
</html>