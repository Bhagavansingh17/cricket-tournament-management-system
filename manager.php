<?php
require_once 'includes/header.php'; // This already includes db.php

// --- SECURITY ---
// If not logged in, or not a manager, kick out
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: login.php");
    exit;
}

// Get the manager's team ID from their session
$myTeamID = $_SESSION['team_id'];
$message = "";
$message_type = "success";

// ---
// HANDLE ALL FORM SUBMISSIONS (CREATE, UPDATE, DELETE, APPOINT)
// ---

// --- HANDLE APPOINT CAPTAIN (NEW FEATURE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appoint_captain_id'])) {
    $player_id_to_appoint = $_POST['appoint_captain_id'];

    // This is a complex transaction, all or nothing
    $conn->begin_transaction();
    try {
        // 1. Get the OLD CaptainID from the TEAM table (to delete it later)
        $sql_get_old_captain = "SELECT CaptainID FROM TEAM WHERE TeamID = ?";
        $stmt_get = $conn->prepare($sql_get_old_captain);
        $stmt_get->bind_param("i", $myTeamID);
        $stmt_get->execute();
        $old_captain_id = $stmt_get->get_result()->fetch_assoc()['CaptainID'];
        $stmt_get->close();

        // 2. Create a NEW row in the CAPTAIN table for the new player
        $sql_insert_captain = "INSERT INTO CAPTAIN (PlayerID) VALUES (?)";
        $stmt_insert = $conn->prepare($sql_insert_captain);
        $stmt_insert->bind_param("i", $player_id_to_appoint);
        $stmt_insert->execute();
        $new_captain_id = $conn->insert_id; // Get the ID of the row we just created
        $stmt_insert->close();

        // 3. UPDATE the TEAM table to link to this new CaptainID
        $sql_update_team = "UPDATE TEAM SET CaptainID = ? WHERE TeamID = ?";
        $stmt_update = $conn->prepare($sql_update_team);
        $stmt_update->bind_param("ii", $new_captain_id, $myTeamID);
        $stmt_update->execute();
        $stmt_update->close();

        // 4. (Cleanup) DELETE the OLD Captain row from the CAPTAIN table
        if ($old_captain_id) {
            $sql_delete_old = "DELETE FROM CAPTAIN WHERE CaptainID = ?";
            $stmt_delete = $conn->prepare($sql_delete_old);
            $stmt_delete->bind_param("i", $old_captain_id);
            $stmt_delete->execute();
            $stmt_delete->close();
        }

        // If all 4 steps worked, commit the changes
        $conn->commit();
        $message = "New captain appointed successfully!";
        $message_type = "success";

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback(); // Undo all changes if one part failed
        $message = "Error appointing captain: " . $exception->getMessage();
        $message_type = "error";
    }
}

// --- HANDLE DELETE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_player_id'])) {
    $player_id_to_delete = $_POST['delete_player_id'];

    // Security Check: Make sure this player belongs to this manager's team
    $sql = "DELETE FROM PLAYER WHERE PlayerID = ? AND TeamID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $player_id_to_delete, $myTeamID);
    
    if ($stmt->execute()) {
        $message = "Player deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting player: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// --- HANDLE CREATE / UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_or_update_player'])) {
    
    // Get all form fields
    $player_id = $_POST['player_id']; // This is hidden, empty for "Add"
    $playerName = $_POST['playerName'];
    $runs = $_POST['runsScored'];
    $sixes = $_POST['noOfSixes'];
    $fours = $_POST['noOfFours'];
    $strikeRate = $_POST['strikeRate'];
    $wickets = $_POST['noOfWickets'];
    $economy = $_POST['economy'];
    $best = $_POST['best'];

    if (empty($player_id)) {
        // --- This is an INSERT (Create) ---
        $sql = "INSERT INTO PLAYER (TeamID, PlayerName, RunsScored, NoOfSixes, NoOfFours, StrikeRate, NoOfWickets, Economy, Best) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isiiidids", $myTeamID, $playerName, $runs, $sixes, $fours, $strikeRate, $wickets, $economy, $best);
        
        if ($stmt->execute()) {
            $message = "New player added successfully!";
            $message_type = "success";
        } else {
            $message = "Error adding player: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();

    } else {
        // --- This is an UPDATE ---
        $sql = "UPDATE PLAYER SET 
                    PlayerName = ?, RunsScored = ?, NoOfSixes = ?, NoOfFours = ?, 
                    StrikeRate = ?, NoOfWickets = ?, Economy = ?, Best = ? 
                WHERE 
                    PlayerID = ? AND TeamID = ?"; // Security check
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiididsii", $playerName, $runs, $sixes, $fours, $strikeRate, $wickets, $economy, $best, $player_id, $myTeamID);
        
        if ($stmt->execute()) {
            $message = "Player updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating player: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// ---
// FETCH ALL DATA FOR THE PAGE
// ---

// Fetch this manager's team name AND current CaptainID
$team_sql = "SELECT TeamName, CaptainID FROM TEAM WHERE TeamID = ?";
$team_stmt = $conn->prepare($team_sql);
$team_stmt->bind_param("i", $myTeamID);
$team_stmt->execute();
$team_result = $team_stmt->get_result();
$team = $team_result->fetch_assoc();
$myTeamName = $team['TeamName'];
$myTeamCaptainID = $team['CaptainID']; // We need this to check who is captain
$team_stmt->close();

// Find the PlayerID of the current captain
$currentCaptainPlayerID = null;
if ($myTeamCaptainID) {
    $captain_sql = "SELECT PlayerID FROM CAPTAIN WHERE CaptainID = ?";
    $captain_stmt = $conn->prepare($captain_sql);
    $captain_stmt->bind_param("i", $myTeamCaptainID);
    $captain_stmt->execute();
    $captain_result = $captain_stmt->get_result();
    if ($captain_result->num_rows > 0) {
        $currentCaptainPlayerID = $captain_result->fetch_assoc()['PlayerID'];
    }
    $captain_stmt->close();
}

// Fetch ALL players for THIS manager's team
$players_sql = "SELECT * FROM PLAYER WHERE TeamID = ? ORDER BY PlayerName";
$players_stmt = $conn->prepare($players_sql);
$players_stmt->bind_param("i", $myTeamID);
$players_stmt->execute();
$players_result = $players_stmt->get_result();
?>

<!-- 
    This page has custom styles for the new buttons.
    We add them here so we don't have to edit style.css
-->
<style>
    .player-actions {
        display: flex;
        flex-direction: column; /* Stack buttons vertically */
        gap: 8px;
    }
    .player-actions .btn-edit,
    .player-actions .btn-delete,
    .player-actions .btn-appoint {
        padding: 6px 10px;
        font-size: 0.85em;
        font-weight: 600;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s ease;
        text-align: center;
        width: 120px; /* Give buttons a uniform width */
    }
    .btn-edit {
        background-color: #2563eb; /* Blue */
        color: white;
    }
    .btn-edit:hover {
        background-color: #3b82f6;
    }
    .btn-delete {
        background-color: #dc2626; /* Red */
        color: white;
    }
    .btn-delete:hover {
        background-color: #ef4444;
    }
    /* NEW BUTTON STYLE */
    .btn-appoint {
        background-color: #16a34a; /* Green */
        color: white;
    }
    .btn-appoint:hover {
        background-color: #22c55e;
    }
    /* Style for the (C) badge */
    .captain-badge {
        color: #facc15; /* Yellow */
        font-weight: 700;
        font-size: 0.9em;
        margin-left: 8px;
    }
    /* --- */
    #form-cancel-btn {
        background-color: #64748b; /* Gray */
        margin-top: 10px;
    }
    #form-cancel-btn:hover {
        background-color: #94a3b8;
    }
    .form-row {
        display: flex;
        gap: 20px;
    }
    .form-row .form-group {
        flex: 1;
    }
    fieldset {
        border: 1px solid #475569;
        border-radius: 6px;
        padding: 10px 15px 15px;
        margin-bottom: 15px;
    }
    legend {
        color: #e2e8f0;
        font-weight: 600;
        padding: 0 5px;
    }
    .table-wrapper {
        overflow-x: auto;
    }
</style>


<!-- Column 1: Add / Edit Player Form -->
<div class="column" style="flex: 1 0 40%;">
    <div class="card">
        <h2 id="form-title">Manager: Add Player</h2>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type == 'error' ? 'message-error' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- This form is now used for BOTH Add and Edit -->
        <form id="player-form" action="manager.php" method="POST">
            <!-- Hidden field to store PlayerID for edits -->
            <input type="hidden" name="player_id" id="form-player-id">

            <div class="form-group">
                <label for="playerName">Player Name:</label>
                <input type="text" id="playerName" name="playerName" required>
            </div>

            <fieldset>
                <legend>Batting Stats</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="runsScored">Runs Scored:</label>
                        <input type="number" id="runsScored" name="runsScored" value="0">
                    </div>
                    <div class="form-group">
                        <label for="strikeRate">Strike Rate:</label>
                        <input type="text" id="strikeRate" name="strikeRate" value="0.00" placeholder="e.g., 150.25">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="noOfFours">No. of Fours:</label>
                        <input type="number" id="noOfFours" name="noOfFours" value="0">
                    </div>
                    <div class="form-group">
                        <label for="noOfSixes">No. of Sixes:</label>
                        <input type="number" id="noOfSixes" name="noOfSixes" value="0">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Bowling Stats</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="noOfWickets">Wickets Taken:</label>
                        <input type="number" id="noOfWickets" name="noOfWickets" value="0">
                    </div>
                    <div class="form-group">
                        <label for="economy">Economy:</label>
                        <input type="text" id="economy" name="economy" value="0.00" placeholder="e.g., 7.50">
                    </div>
                </div>
                <div class="form-group">
                    <label for="best">Best (e.g., "3/25"):</label>
                    <input type="text" id="best" name="best" value="N/A">
                </div>
            </fieldset>
            
            <button type="submit" name="add_or_update_player" id="form-submit-btn" class="btn">Add Player</button>
            <button type="button" id="form-cancel-btn" class="btn" onclick="cancelEdit()" style="display:none;">Cancel Edit</button>
        </form>
    </div>
</div>

<!-- Column 2: Player Roster (Now with all stats and buttons) -->
<div class="column" style="flex: 1 0 58%;">
    <div class="card">
        <h2>Player Roster: <?php echo htmlspecialchars($myTeamName); ?></h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Player Name</th>
                        <th>Runs</th>
                        <th>Wkts</th>
                        <th>S/R</th>
                        <th>Econ</th>
                        <th>Best</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($players_result->num_rows > 0) {
                        while($row = $players_result->fetch_assoc()) {
                            // Prepare data for JS
                            $player_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                            // Check if this player is the current captain
                            $is_captain = ($currentCaptainPlayerID == $row['PlayerID']);
                            
                            echo "<tr>";
                            // Player Name Cell (with (C) badge if captain)
                            echo "<td><strong>" . htmlspecialchars($row['PlayerName']) . "</strong>";
                            if ($is_captain) {
                                echo "<span class='captain-badge'>(C)</span>";
                            }
                            echo "</td>";
                            
                            echo "<td>" . htmlspecialchars($row['RunsScored']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['NoOfWickets']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['StrikeRate']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Economy']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Best']) . "</td>";
                            
                            // --- Action Buttons ---
                            echo "<td class='player-actions'>";
                            
                            // Show "Appoint" button ONLY if they are NOT captain
                            if (!$is_captain) {
                                echo "<form action='manager.php' method='POST' style='margin:0;'>";
                                echo "<input type='hidden' name='appoint_captain_id' value='" . $row['PlayerID'] . "'>";
                                echo "<button type='submit' class='btn-appoint'>Appoint Captain</button>";
                                echo "</form>";
                            }

                            // Edit Button
                            echo "<button class='btn-edit' onclick='editPlayer($player_json)'>Edit</button>";
                            
                            // Delete Button (as a mini-form)
                            echo "<form action='manager.php' method='POST' style='margin:0;'>";
                            echo "<input type='hidden' name='delete_player_id' value='" . $row['PlayerID'] . "'>";
                            echo "<button type='submit' class='btn-delete' onclick='return confirm(\"Are you sure you want to delete this player?\");'>Delete</button>";
                            echo "</form>";
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>No players found for your team</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// This JS handles the "Edit" button click
function editPlayer(player) {
    // Fill the form with the player's data
    document.getElementById('form-player-id').value = player.PlayerID;
    document.getElementById('playerName').value = player.PlayerName;
    document.getElementById('runsScored').value = player.RunsScored;
    document.getElementById('noOfSixes').value = player.NoOfSixes;
    document.getElementById('noOfFours').value = player.NoOfFours;
    document.getElementById('strikeRate').value = player.StrikeRate;
    document.getElementById('noOfWickets').value = player.NoOfWickets;
    document.getElementById('economy').value = player.Economy;
    document.getElementById('best').value = player.Best;

    // Change form state
    document.getElementById('form-title').innerText = 'Manager: Edit Player';
    document.getElementById('form-submit-btn').innerText = 'Save Changes';
    document.getElementById('form-cancel-btn').style.display = 'block';

    // Scroll to top to see the form
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// This JS handles the "Cancel Edit" button click
function cancelEdit() {
    // Clear all form fields
    document.getElementById('player-form').reset();
    document.getElementById('form-player-id').value = '';

    // Reset form state
    document.getElementById('form-title').innerText = 'Manager: Add Player';
    document.getElementById('form-submit-btn').innerText = 'Add Player';
    document.getElementById('form-cancel-btn').style.display = 'none';
}
</script>

<?php
require_once 'includes/footer.php';
?>