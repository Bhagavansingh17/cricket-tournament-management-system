<?php
session_start(); // Test sessions
require_once 'config/db.php'; // Test DB connection

echo "<style>body { font-family: monospace; background: #0f172a; color: #cbd5e1; padding: 20px; line-height: 1.6;}</style>";
echo "<h1>Admin Login Debug Test</h1>";

// Test 1: DB Connection
if ($conn && $conn->ping()) {
    echo "<p style='color: #22c55e; font-size: 1.2em;'>SUCCESS: Database connection is working.</p>";
} else {
    echo "<p style='color: #ef4444; font-size: 1.2em;'>ERROR: Database connection FAILED. Check config/db.php.</p>";
    exit;
}

// Test 2: Find Admin User
$sql = "SELECT * FROM USERS WHERE Username = 'admin'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<p style='color: #ef4444; font-size: 1.2em;'>CRITICAL ERROR: Admin user not found.</p>";
    echo "<p>This means you are using the WRONG database.sql file.</p>";
    echo "<p>SOLUTION: Drop your `ctms_db` in phpMyAdmin and re-import the correct database.sql file I sent you (the one with the `USERS` table).</p>";
    exit;
}

$user = $result->fetch_assoc();
echo "<p style='color: #22c55e; font-size: 1.2em;'>SUCCESS: Admin user was found in the `USERS` table.</p>";

// Test 3: Check Password Hash
$hash_from_db = $user['PasswordHash'];
$correct_hash = '$2y$10$E.qJ4P8J2J5.8m9/C4FjA.Ld1QJk6n.Xp4/E.R.5c.Z.I.oXy8m.';
echo "<hr><p>Hash from your DB:<br>$hash_from_db</p>";
echo "<p>Correct hash:<br>$correct_hash</p>";

if ($hash_from_db != $correct_hash) {
    echo "<p style='color: #ef4444; font-size: 1.2em;'>CRITICAL ERROR: The password hash in your database is WRONG.</p>";
    echo "<p>This proves you are using an old or modified database.sql file.</p>";
    echo "<p>SOLUTION: Drop your `ctms_db` and re-import the correct database.sql file I sent.</p>";
    exit;
}

echo "<p style='color: #22c55e; font-size: 1.2em;'>SUCCESS: Password hash in DB is correct.</p>";

// Test 4: Verify the password
if (password_verify('admin123', $hash_from_db)) {
    echo "<p style='color: #22c55e; font-size: 1.2em;'>SUCCESS: PHP's `password_verify()` function is working correctly.</p>";
} else {
    echo "<p style='color: #ef4444; font-size: 1.2em;'>CRITICAL ERROR: `password_verify()` failed. This is very strange.</p>";
    exit;
}

// Test 5: Check Sessions
$_SESSION['debug_test'] = 'sessions are working';
if (isset($_SESSION['debug_test']) && $_SESSION['debug_test'] == 'sessions are working') {
    echo "<p style='color: #22c55e; font-size: 1.2em;'>SUCCESS: PHP Sessions are working.</p>";
} else {
    echo "<p style='color: #ef4444; font-size: 1.2em;'>CRITICAL ERROR: PHP Sessions are NOT working on your XAMPP.</p>";
    echo "<p>This is the problem. Your login is working, but it can't *save* your login state.</p>";
}

echo "<hr><p style='font-size: 1.2em;'>FINAL DIAGNOSIS: Please read the messages above. If you see any 'CRITICAL ERROR', that is your problem.</p>";

$conn->close();
?>