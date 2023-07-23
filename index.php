<?php
// Function to generate a new folder for each image upload
function generateFolders() {
    $uploadFolder = 'uploads/';
    $thumbnailFolder = 'thumbnails/';
    
    if (!file_exists($uploadFolder)) {
        mkdir($uploadFolder, 0777, true);
    }

    if (!file_exists($thumbnailFolder)) {
        mkdir($thumbnailFolder, 0777, true);
    }

    return $uploadFolder;
}

// Function to save the uploaded image filenames and unique identifiers to a text file
function saveToDatabase($filename, $uniqueName) {
    $data = $filename . '|' . $uniqueName . '|0' . PHP_EOL; // '0' represents the initial view count
    file_put_contents('uploaded_images.txt', $data, FILE_APPEND);
}

// Function to increment view count for an image
function incrementViewCount($uniqueName) {
    $imageInfo = file('uploaded_images.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $updatedData = '';

    foreach ($imageInfo as $info) {
        list($filename, $name, $viewCount) = explode('|', $info);
        if ($name === $uniqueName) {
            $viewCount++;
        }
        $updatedData .= $filename . '|' . $name . '|' . $viewCount . PHP_EOL;
    }

    file_put_contents('uploaded_images.txt', $updatedData);
}

// Function to get images ordered by view count
function getPopularImages() {
    $imageInfo = file('uploaded_images.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $images = [];

    foreach ($imageInfo as $info) {
        list($filename, $uniqueName, $viewCount) = explode('|', $info);
        $imageUrl = $uniqueName;
        $thumbnailUrl = 'thumbnails/' . $uniqueName;
        $images[] = [
            'name' => $filename,
            'url' => $imageUrl,
            'thumbnail' => $thumbnailUrl,
            'views' => $viewCount
        ];
    }

    // Sort images by view count in descending order
    usort($images, function($a, $b) {
        return $b['views'] - $a['views'];
    });

    return $images;
}

// Function to create a thumbnail image from the original image
function createThumbnail($source, $destination, $maxWidth, $maxHeight) {
    $imageInfo = getimagesize($source);
    $imageType = $imageInfo[2];

    // Load the original image based on its type
    if ($imageType === IMAGETYPE_JPEG) {
        $image = imagecreatefromjpeg($source);
    } elseif ($imageType === IMAGETYPE_PNG) {
        $image = imagecreatefrompng($source);
    } elseif ($imageType === IMAGETYPE_GIF) {
        $image = imagecreatefromgif($source);
    } else {
        return false;
    }

    $width = imagesx($image);
    $height = imagesy($image);

    // Calculate the aspect ratio to maintain
    $aspectRatio = $width / $height;

    // Calculate new dimensions for the thumbnail
    if ($width <= $maxWidth && $height <= $maxHeight) {
        $newWidth = $width;
        $newHeight = $height;
    } elseif ($aspectRatio > 1) { // Landscape image
        $newWidth = $maxWidth;
        $newHeight = $maxWidth / $aspectRatio;
    } else { // Portrait or square image
        $newWidth = $maxHeight * $aspectRatio;
        $newHeight = $maxHeight;
    }

    // Create a new image with the calculated dimensions
    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

    // Copy and resize the original image to the thumbnail
    imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Save the thumbnail to the destination
    imagejpeg($thumbnail, $destination, 80);

    // Free up memory
    imagedestroy($image);
    imagedestroy($thumbnail);

    return true;
}

// Function to handle image uploads
function handleUpload() {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Generate a unique name for the image
        $originalName = $_FILES['file']['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $uniqueName = uniqid() . '_' . $originalName;

        // Save the original image to the 'uploads' folder
        $folder = generateFolders();
        $destination = $folder . $uniqueName;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
            // Save the filename and unique identifier to the database
            saveToDatabase($originalName, $uniqueName);

            // Generate thumbnail and save it to the 'thumbnails' folder
            $thumbnailDestination = 'thumbnails/' . $uniqueName;
            createThumbnail($destination, $thumbnailDestination, 200, 200);

            echo "Image uploaded successfully!";
        } else {
            echo "Failed to upload the image.";
        }
    }
}

// Check if the 'page' parameter is set
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Handle image upload if the 'page' parameter is 'upload'
if ($page === 'upload') {
    handleUpload();
} elseif ($page === 'image' && isset($_GET['id'])) {
    $id = $_GET['id'];
    incrementViewCount($id);
}
?>

<!DOCTYPE html>
<html data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Image Gallery</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">

    <!-- Custom CSS -->
    <style>
        .images {
            display: grid;
            grid-template-columns: repeat(2, 1fr); /* Two columns in mobile view */
            grid-gap: 2px; /* 2px gap between images */
            justify-content: center;
            margin-right: 3px;
            margin-left: 3px;
        }

        .text-stroke {
            -webkit-text-stroke: 1px;
        }

        @media (min-width: 768px) {
            /* For desktop view, change the grid layout */
            .images {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        .imagesA {
            display: block;
            overflow: hidden;
        }

        .imagesImg {
            width: 100%;
            height: auto;
            object-fit: cover;
            height: 200px;
            transition: transform 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand fw-bold bg-body-tertiary">
        <div class="container-fluid">
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && (empty($_GET['page']) || $_GET['page'] == 'home')) ? 'active' : ''; ?>" href="index.php?page=home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($_GET['page'] == 'upload') ? 'active' : ''; ?>" href="index.php?page=upload">Upload</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($_GET['page'] == 'popular') ? 'active' : ''; ?>" href="index.php?page=popular">Popular</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="mt-1">
        <?php
        // Display images if the 'page' parameter is 'home'
        if ($page === 'home') {
            // Assuming the image filenames and unique identifiers are stored in the 'uploaded_images.txt' file
            $imageInfo = file('uploaded_images.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            // Sort the images in reverse order (latest first)
            rsort($imageInfo);

            // Display the images in a grid using Bootstrap grid system
            echo '<div class="images">';
            foreach ($imageInfo as $info) {
                list($filename, $uniqueName, $viewCount) = explode('|', $info);
                $thumbnailUrl = 'thumbnails/' . $uniqueName;
                echo '<a class="imagesA shadow" href="index.php?page=image&id=' . $uniqueName . '"><img src="' . $thumbnailUrl . '" alt="Image" class="rounded shadow img-fluid imagesImg"></a>';
            }
            echo '</div>';
            echo '<br>';
        } elseif ($page === 'upload') {
            // Display the upload form
        ?>
            <div class="container mt-4">
               <h3 class="fw-bold text-center mb-4 mt-2">Upload Image</h3>
               <div class="my-4 text-center">
                   <img id="imagePreview" src="#" alt="Image Preview" class="rounded shadow" style="width: 100%; height: 500px; display: none; object-fit: cover;">
                </div> 
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input type="file" name="file" class="fw-bold form-control" required onchange="previewImage(event)">
                    </div>
                    <button type="submit" class="btn btn-primary fw-bold w-100">Upload</button>
                </form>
            </div>
            <br>
            <script>
                function previewImage(event) {
                    const fileInput = event.target;
                    const imagePreview = document.getElementById("imagePreview");

                    if (fileInput.files && fileInput.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            imagePreview.style.display = "block";
                            imagePreview.src = e.target.result;
                        };
                        reader.readAsDataURL(fileInput.files[0]);
                    }
                }
            </script>
        <?php
        } elseif ($page === 'popular') {
            // Display popular images
            $popularImages = getPopularImages();
            echo '<div class="images">';
            foreach ($popularImages as $image) {
                echo '<a class="imagesA" href="index.php?page=image&id=' . $image['url'] . '"><img src="' . $image['thumbnail'] . '" alt="Image" class="rounded shadow img-fluid imagesImg"></a>';
            }
            echo '</div>';
            echo '<br>';
        } elseif ($page === 'image' && isset($_GET['id'])) {
            // Display full image and view count
            $id = $_GET['id'];
            $imageInfo = file('uploaded_images.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($imageInfo as $info) {
                list($filename, $uniqueName, $viewCount) = explode('|', $info);
                if ($uniqueName === $id) {
                    $imageUrl = 'uploads/' . $uniqueName;
                    echo '<div class="container-fluid mt-4">';
                    echo '<h3 class="text-center">' . $filename . '</h3>';
                    echo '<img src="' . $imageUrl . '" alt="Image" class="rounded shadow my-2 img-fluid">';
                    echo '<p class="fw-bold">View Count: ' . $viewCount . '</p>';
                    echo '</div>';
                    echo '<br>';
                }
            }
        }
        ?>
    </main>
</body>
</html>
