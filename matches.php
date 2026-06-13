<?php
require_once 'includes/header.php'; // This already includes db.php

// Fetch all matches and their assigned umpires
$sql = "
    SELECT 
        m.Date, m.Location, m.Result,
        tA.TeamName AS TeamA,
        tB.TeamName AS TeamB,
        tW.TeamName AS Winner,
        -- This joins all umpire names for a match into one string
        GROUP_CONCAT(DISTINCT u.UmpireName SEPARATOR ', ') AS Umpires
    FROM `MATCH` m
    JOIN TEAM tA ON m.TeamA_ID = tA.TeamID
    JOIN TEAM tB ON m.TeamB_ID = tB.TeamID
    LEFT JOIN TEAM tW ON m.WinningTeamID = tW.TeamID
    -- New JOINS to get the umpire names
    LEFT JOIN UMPIRED_BY ub ON m.MatchID = ub.MatchID
    LEFT JOIN UMPIRE u ON ub.UmpireID = u.UmpireID
    GROUP BY m.MatchID -- Group by match to get one row per match
    ORDER BY m.Date DESC
";
$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}
?>

<div class="column" style="flex-basis: 100%;">
    <div class="card">
        <h2>Match Schedule & Results</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Team A</th>
                        <th>Team B</th>
                        <th>Location</th>
                        <th>Umpires</th> <!-- NEW COLUMN -->
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $resultText = $row['Result'];
                            if ($row['Result'] == 'Completed') {
                                $resultText = htmlspecialchars($row['Winner']) . " Won";
                            }
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['Date']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['TeamA']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['TeamB']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Location']) . "</td>";
                            
                            // *** THIS IS THE FIXED LINE (Line 59) ***
                            echo "<td>" . (htmlspecialchars($row['Umpires'] ?? 'N/A')) . "</td>";
                            
                            echo "<td><strong>" . htmlspecialchars($resultText) . "</strong></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No matches found</td></tr>"; // Colspan is now 6
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>