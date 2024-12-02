<?php
require 'db.php';
session_start();

// Sends user back to log in page if they try to access display.php without logging in
if (!isset($_SESSION['user_group'])) {
    header("Location: auth.php");
    exit;
}

$user_group = $_SESSION['user_group'];
$search = '';
$data = [];
$message = ''; 
$integrity_status = []; 

// PHP code for Patient Data submission form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_data']) && $user_group === 'H') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $weight = $_POST['weight'];
    $height = $_POST['height'];
    $health_history = $_POST['health_history'];

    // Concats into one string then hashes (used for tamper detection)
    $data_string = $first_name . $last_name . (string)$gender . (string)$age . (string)$weight . (string)$height . $health_history;
    $hash = hash('sha256', $data_string);

    try {
        // Inserts all the info into the DB
        $stmt = $conn->prepare("INSERT INTO healthcare (first_name, last_name, gender, age, weight, height, health_history, hash) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $gender, $age, $weight, $height, $health_history, $hash]);
        $message = "Patient data was added successfully";
    } catch (PDOException $e) {
        $message = "There was an error adding patient data: " . $e->getMessage();
    }
}

// PHP for the search bar 
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search']) && !empty($_GET['search'])) {
        $search = htmlspecialchars($_GET['search']);
        //Finds role and uses it to change search behavior so R's cant search by patient name
        if ($user_group === 'H') {
            $query = "SELECT * FROM healthcare WHERE first_name LIKE :search OR last_name LIKE :search OR health_history LIKE :search";
        } elseif ($user_group === 'R') {
            $query = "SELECT * FROM healthcare WHERE health_history LIKE :search";
        }
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    } else {
        if ($user_group === 'H') {
            $query = "SELECT * FROM healthcare";
        } elseif ($user_group === 'R') {
            $query = "SELECT * FROM healthcare";
        }
        $stmt = $conn->prepare($query);
    }
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // PHP to check for tampering, does same concat as above and compares it to hash in the DB
    foreach ($data as $row) {
        $data_string = $row['first_name'] . $row['last_name'] . (string)$row['gender'] . (string)$row['age'] . 
                       (string)$row['weight'] . (string)$row['height'] . $row['health_history'];
        $calculated_hash = hash('sha256', $data_string);

        //If same status set to clean if hashes different status is altered
        if ($calculated_hash === $row['hash']) {
            $integrity_status[$row['id']] = "Clean";
        } else {
            $integrity_status[$row['id']] = "Altered";
        }
    }
} catch (PDOException $e) {
    die("There was an error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Data</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <img src="EHH.png" alt="Evan's Healthtastic Hospital Logo" class="logo">
            <h1>Evan's Healthtastic Hospital</h1>
            <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
            <a href="logout.php" class="logout-button">Logout</a>
        </header>

        <!-- Displays the message recieved from the Data Entry form, either success or failure -->
        <?php if ($message): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <!-- Creating the add new data form, only shows up for users in the H group -->
        <?php if ($user_group === 'H'): ?>
            <h2>Add New Data</h2>
            <form method="POST" action="display.php">
                <input type="text" name="first_name" placeholder="First Name" required><br>
                <input type="text" name="last_name" placeholder="Last Name" required><br>
                <select name="gender" required>
                    <option value="1">Male</option>
                    <option value="0">Female</option>
                </select><br>
                <input type="number" name="age" placeholder="Age" required><br>
                <input type="number" name="weight" placeholder="Weight" required><br>
                <input type="number" name="height" placeholder="Height" required><br>
                <textarea name="health_history" placeholder="Health History" required></textarea><br>
                <button type="submit" name="add_data">Add Data</button>
            </form>
        <?php endif; ?>

        <!-- Creates the search form -->
        <form method="GET" action="display.php" class="search-form">
            <input type="text" name="search" placeholder="Search by name or history..." value="<?= htmlspecialchars($search) ?>" class="search-input">
            <button type="submit" class="search-button">Search</button>
        </form>

        <h2>Healthcare Data</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>ID</th>
                     <!-- Makes it so First and last name columns only in table for to H users, done so no blank spaces in table for R users-->
                    <?php if ($user_group === 'H'): ?>
                        <th>First Name</th>
                        <th>Last Name</th>
                    <?php else: ?>
                        <th>First Name</th>
                        <th>Last Name</th>
                    <?php endif; ?>
                    <th>Gender</th>
                    <th>Age</th>
                    <th>Weight</th>
                    <th>Height</th>
                    <th>Health History</th>
                    <th>Integrity Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <?php if ($user_group === 'H'): ?>
                                <td><?= htmlspecialchars($row['first_name']) ?></td>
                                <td><?= htmlspecialchars($row['last_name']) ?></td>
                
                            <?php endif; ?>
                            <td><?= htmlspecialchars($row['gender'] ? 'Male' : 'Female') ?></td>
                            <td><?= htmlspecialchars($row['age']) ?></td>
                            <td><?= htmlspecialchars($row['weight']) ?></td>
                            <td><?= htmlspecialchars($row['height']) ?></td>
                            <td><?= htmlspecialchars($row['health_history']) ?></td>
                            <td><?= htmlspecialchars($integrity_status[$row['id']]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No patient records were found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
