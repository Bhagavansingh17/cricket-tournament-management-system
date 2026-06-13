<?php
// Start the session on EVERY page
session_start();

// Define BASE_URL
define('BASE_URL', 'http://localhost/ctms');

// We must include the config file here so we can use the BASE_URL
// This __DIR__ makes sure it always finds the file
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTMS Dashboard</title>
    
    <!-- We now use the BASE_URL to create a 100% correct path -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/style.css">
</head>
<body>
    <header>
        <h1>Cricket Tournament Management System (CTMS)</h1>
        <!-- The <p> tag with "Full-Stack DBMS Project" has been removed -->
    </header>
    
    <nav class="main-nav">
        <!-- Guest Links -->
        <a href="<?php echo BASE_URL; ?>/index.php">Home (Points)</a>
        <a href="<?php echo BASE_URL; ?>/teams.php">Teams</a>
        <a href="<?php echo BASE_URL; ?>/matches.php">Matches</a>

        <?php if (isset($_SESSION['user_id'])) : ?>
            <!-- Logged-in Links -->
            <?php if ($_SESSION['role'] == 'admin') : ?>
                <a href="<?php echo BASE_URL; ?>/admin.php">Admin Panel</a>
            <?php endif; ?>
            
            <?php if ($_SESSION['role'] == 'manager') : ?>
                <a href="<?php echo BASE_URL; ?>/manager.php">Manager Dashboard</a>
            <?php endif; ?>
            
            <a href="<?php echo BASE_URL; ?>/logout.php" class="nav-button-logout">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
        <?php else : ?>
            <!-- Logged-out Links -->
            <a href="<?php echo BASE_URL; ?>/login.php" class="nav-button">Login</a>
            <a href="<?php echo BASE_URL; ?>/register.php" class="nav-button">Register Team</a>
        <?php endif; ?>
    </nav>

    <div class="container">