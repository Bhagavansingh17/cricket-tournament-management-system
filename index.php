<?php
require_once 'includes/header.php'; // This already includes db.php

// Fetch Points Table - It's auto-calculated by the trigger!
$points_sql = "
    SELECT 
        t.TeamName, 
        (t.NoOfWins + t.NoOfLosses + t.NoOfDraws) AS Played, 
        t.NoOfWins, 
        t.NoOfLosses, 
        t.NoOfDraws, 
        t.Points,
        p.PlayerName AS CaptainName
    FROM 
        TEAM t
    LEFT JOIN 
        CAPTAIN c ON t.CaptainID = c.CaptainID
    LEFT JOIN 
        PLAYER p ON c.PlayerID = p.PlayerID
    ORDER BY 
        t.Points DESC
";
$points_result = $conn->query($points_sql);
?>

<div class="column" style="flex-basis: 100%;">
    <div class="card">
        <h2>Points Table [cite: 80, 486]</h2>
        <table>
            <thead>
                <tr>
                    <th>Team</th>
                    <th>Captain</th>
                    <th>Pld</th>
                    <th>Won</th>
                    <th>Lost</th>
                    <th>Draw</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($points_result->num_rows > 0) {
                    while($row = $points_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td><strong>" . htmlspecialchars($row['TeamName']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($row['CaptainName'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($row['Played']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['NoOfWins']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['NoOfLosses']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['NoOfDraws']) . "</td>";
                        echo "<td><strong>" . htmlspecialchars($row['Points']) . "</strong></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No teams found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>