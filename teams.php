<?php
require_once 'includes/header.php'; // This already includes db.php

// Fetch all teams and their managers
$sql = "
    SELECT t.TeamName, m.ManagerName, m.BattingCoach, m.BowlingCoach 
    FROM TEAM t
    LEFT JOIN TEAM_MANAGEMENT m ON t.ManagerID = m.ManagerID
    ORDER BY t.TeamName
";
$result = $conn->query($sql);
?>

<div class="column" style="flex-basis: 100%;">
    <div class="card">
        <h2>All Teams [cite: 77, 483]</h2>
        <table>
            <thead>
                <tr>
                    <th>Team Name</th>
                    <th>Manager</th>
                    <th>Batting Coach</th>
                    <th>Bowling Coach</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td><strong>" . htmlspecialchars($row['TeamName']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($row['ManagerName'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($row['BattingCoach'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($row['BowlingCoach'] ?? 'N/A') . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No teams found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>