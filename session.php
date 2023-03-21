<?php
session_start();

$users_file = 'users.txt';
$uploads_folder = 'uploads';
$thumbs_folder = 'thumbs';
$users = [];

// Create uploads and thumbs folders if they don't exist
if (!file_exists($uploads_folder)) {
    mkdir($uploads_folder);
}

if (!file_exists($thumbs_folder)) {
    mkdir($thumbs_folder);
}

// Read users from file
if (file_exists($users_file)) {
    $users = json_decode(file_get_contents($users_file), true);
} else {
    // Create users file if it doesn't exist
    file_put_contents($users_file, json_encode($users));
}

// Handle registration
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $users[$username] = password_hash($password, PASSWORD_DEFAULT);
    file_put_contents($users_file, json_encode($users));
    $_SESSION['username'] = $username;
    header('Location: index.php');
    exit();
}

// Handle login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    if (isset($users[$username]) && password_verify($password, $users[$username])) {
        $_SESSION['username'] = $username;
        header('Location: index.php');
        exit();
    } else {
        echo "Invalid username or password";
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: session.php');
    exit();
}
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
    <div class="modal modal-signin position-static d-block" tabindex="-1" role="dialog" id="modalSignin">
      <div class="modal-dialog" role="document">
        <div class="modal-content rounded-4">
          <div class="modal-body p-5 pt-0">
            <center>
              <?php if (isset($_SESSION['username'])) { ?>
              <?php } else { ?>
                <h2 class="mt-4 fw-bold">Login or Register</h2>
                <form class="mt-5" method="post" action="session.php">
                  <input class="form-control" type="text" name="username" placeholder="username" required><br>
                  <input class="form-control" type="password" name="password" placeholder="password" required><br>
                  <button class="btn btn-sm btn-primary fw-bold" name="login">Login</button>
                  <button class="btn btn-sm btn-primary fw-bold" name="register">Register</button>
                </form>
              <?php } ?>
            </div>
          </center> 
        </div>
      </div>
    </div>
  </body>
</html>
