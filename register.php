<?php
require_once 'includes/header.php'; // This already includes db.php

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = $_POST['username'];
    $password = $_POST['password'];
    $teamName = $_POST['teamName'];
    $managerName = $_POST['managerName'];
    $battingCoach = $_POST['battingCoach'];
    $bowlingCoach = $_POST['bowlingCoach'];

    // --- This is a TRANSACTION ---
    // If any part fails, it all rolls back.
    $conn->begin_transaction();
    try {
        // 1. Create TEAM_MANAGEMENT [cite: 88, 338]
        $sql1 = "INSERT INTO TEAM_MANAGEMENT (ManagerName, BattingCoach, BowlingCoach) VALUES (?, ?, ?)";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("sss", $managerName, $battingCoach, $bowlingCoach);
        $stmt1->execute();
        $managerId = $conn->insert_id; // Get the new ManagerID

        // 2. Create TEAM [cite: 88, 324]
        $sql2 = "INSERT INTO TEAM (TeamName, ManagerID) VALUES (?, ?)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("si", $teamName, $managerId);
        $stmt2->execute();
        $teamId = $conn->insert_id; // Get the new TeamID

        // 3. Create USER (Manager) [cite: 74, 92]
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'manager';
        $sql3 = "INSERT INTO USERS (Username, PasswordHash, Role, TeamID) VALUES (?, ?, ?, ?)";
        $stmt3 = $conn->prepare($sql3);
        $stmt3->bind_param("sssi", $username, $passwordHash, $role, $teamId);
        $stmt3->execute();

        // If all 3 steps worked, commit the changes
        $conn->commit();
        $message = "Registration successful! Your team '$teamName' is registered. You can now log in.";
        $message_type = "success";

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback(); // Undo all changes if one part failed
        $message = "Registration failed. Username or Team Name may already exist. Error: " . $exception->getMessage();
        $message_type = "error";
    }
}
?>

<div class="column" style="flex: 1 0 50%; margin: auto;">
    <div class="card">
        <h2>Register Your Team (Manager) [cite: 92]</h2>
        <p style="color: #9CA3AF; font-size: 0.9em; margin-bottom: 20px;">This creates your Manager account and your Team at the same time.</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type == 'error' ? 'message-error' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <h3 style="color: #DDD; margin-top: 0;">Login Details</h3>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <h3 style="color: #DDD; margin-top: 20px;">Team Details [cite: 88]</h3>
            <div class="form-group">
                <label for="teamName">Team Name:</label>
                <input type="text" id="teamName" name="teamName" required>
            </div>
            <div class="form-group">
                <label for="managerName">Your Name (Manager):</label>
                <input type="text" id="managerName" name="managerName" required>
            </div>
            <div class="form-group">
                <label for="battingCoach">Batting Coach:</label>
                <input type="text" id="battingCoach" name="battingCoach">
            </div>
            <div class="form-group">
                <label for="bowlingCoach">Bowling Coach:</label>
                <input type="text" id="bowlingCoach" name="bowlingCoach">
            </div>

            <button type="submit" class="btn">Register Team</button>
        </form>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>