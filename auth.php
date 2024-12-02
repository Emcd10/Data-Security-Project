<?php
require 'db.php';
session_start();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        //Takes in new username and password and hashes password
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $group = $_POST['group'];
            //Sends username, hashed password and selected group to DB
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, password, user_group) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password, $group]);
            $message = "Your registration was successful";
        } catch (PDOException $e) {
            $message = "There was an error creating your account: " . $e->getMessage();
        }
    } elseif (isset($_POST['login'])) {
        
        $username = $_POST['username'];
        $password = $_POST['password'];
        //checks username 
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        //Then using password_verufy compares input password to hashed password in DB if correct sends you to main page
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_group'] = $user['user_group'];
            $_SESSION['username'] = $username;
            header("Location: display.php");
            exit;
        } else {
            $message = "Invalid login credentials.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">

        <h1>User Authentication</h1>
        <!-- Displays whatever message is recieved from form submission -->
        <p><?= htmlspecialchars($message) ?></p>

        <!-- Creates the Registration Form -->
        <h2>Register</h2>
        <form method="POST" action="auth.php">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <select name="group">
                <option value="H">Group H (Full Access)</option>
                <option value="R">Group R (Restricted Access)</option>
            </select><br>
            <button type="submit" name="register">Register</button>
        </form>

        <!-- Creates the Login Form -->
        <h2>Login</h2>
        <form method="POST" action="auth.php">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit" name="login">Login</button>
        </form>
    </div>
</body>
</html>
