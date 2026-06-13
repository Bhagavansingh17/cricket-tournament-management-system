<?php
require_once 'includes/header.php'; // This already includes db.php

// --- SECURITY ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";
$message_type = "success";

// ---
// HANDLE ALL FORM SUBMISSIONS
// ---

// --- 1. HANDLE ADD/UPDATE UMPIRE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_or_update_umpire'])) {
    $umpire_id = $_POST['umpire_id']; // Hidden field
    $umpireName = $_POST['umpireName'];
    $noOfMatches = $_POST['noOfMatches'];

    if (empty($umpire_id)) {
        // --- INSERT ---
        $sql = "INSERT INTO UMPIRE (UmpireName, NoOfMatches) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $umpireName, $noOfMatches);
        if ($stmt->execute()) {
            $message = "Umpire '$umpireName' added successfully!";
            $message_type = "success";
        } else {
            $message = "Error adding umpire: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        // --- UPDATE ---
        $sql = "UPDATE UMPIRE SET UmpireName = ?, NoOfMatches = ? WHERE UmpireID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $umpireName, $noOfMatches, $umpire_id);
        if ($stmt->execute()) {
            $message = "Umpire '$umpireName' updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating umpire: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// --- 2. HANDLE DELETE UMPIRE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_umpire_id'])) {
    $umpire_id = $_POST['delete_umpire_id'];
    
    $sql = "DELETE FROM UMPIRE WHERE UmpireID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $umpire_id);
    
    try {
        if ($stmt->execute()) {
            $message = "Umpire deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Error deleting umpire: " . $stmt->error;
            $message_type = "error";
        }
    } catch (mysqli_sql_exception $e) {
        // This catches the Foreign Key constraint violation
        if ($e->getCode() == 1451) {
            $message = "Cannot delete umpire: They are assigned to one or more matches. You must update those matches first.";
        } else {
            $message = "Error deleting umpire: " . $e->getMessage();
        }
        $message_type = "error";
    }
    $stmt->close();
}


// --- 3. Handle Match Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_match'])) {
    $matchId = $_POST['matchId'];
    $result = $_POST['result']; // 'Completed' or 'Draw'
    $winningTeamId = $_POST['winningTeamId'] ?? NULL; // NULL if draw

    if ($result == 'Draw') {
        $winningTeamId = NULL;
    }

    $sql = "UPDATE `MATCH` SET Result = ?, WinningTeamID = ? WHERE MatchID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $result, $winningTeamId, $matchId);
    
    if ($stmt->execute()) {
        $message = "Match ID $matchId updated! The trigger has updated the points table.";
        $message_type = "success";
    } else {
        $message = "Error: " . $stmt->error;
        $message_type = "error";
    }
}

// --- 4. Handle Schedule Match ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_match'])) {
    $teamA_ID = $_POST['teamA_ID'];
    $teamB_ID = $_POST['teamB_ID'];
    $location = $_POST['location'];
    $date = $_POST['date'];
    $umpire_ids = $_POST['umpires'] ?? []; // This will be an array

    // Validation
    if ($teamA_ID == $teamB_ID) {
        $message = "A team cannot play against itself. Please select two different teams.";
        $message_type = "error";
    } elseif (empty($umpire_ids)) {
        $message = "You must select at least one umpire.";
        $message_type = "error";
    } else {
        // Start a transaction (all or nothing)
        $conn->begin_transaction();
        try {
            // 1. Insert into MATCH
            $sql1 = "INSERT INTO `MATCH` (TeamA_ID, TeamB_ID, Location, Date, Result) VALUES (?, ?, ?, ?, 'Scheduled')";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param("iiss", $teamA_ID, $teamB_ID, $location, $date);
            $stmt1->execute();
            
            // Get the new MatchID we just created
            $newMatchID = $conn->insert_id;

            // 2. Insert into UMPIRED_BY (for each umpire)
            $sql2 = "INSERT INTO `UMPIRED_BY` (MatchID, UmpireID) VALUES (?, ?)";
            $stmt2 = $conn->prepare($sql2);
            
            foreach ($umpire_ids as $umpire_id) {
                $stmt2->bind_param("ii", $newMatchID, $umpire_id);
                $stmt2->execute();
            }

            // If all good, commit
            $conn->commit();
            $message = "New match scheduled with umpires assigned!";
            $message_type = "success";

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback(); // Undo everything if one part fails
            $message = "Error scheduling match: " . $exception->getMessage();
            $message_type = "error";
        }
    }
}

// ---
// FETCH ALL DATA FOR THE PAGE
// ---

// Fetch Scheduled Matches for the "Update" form
$matches_sql = "
    SELECT m.MatchID, tA.TeamName AS TeamA, tB.TeamName AS TeamB, m.TeamA_ID, m.TeamB_ID
    FROM `MATCH` m
    JOIN TEAM tA ON m.TeamA_ID = tA.TeamID
    JOIN TEAM tB ON m.TeamB_ID = tB.TeamID
    WHERE m.Result = 'Scheduled'
";
$matches_result = $conn->query($matches_sql);

// Fetch ALL Teams for the "Schedule" form
$teams_sql = "SELECT TeamID, TeamName FROM TEAM ORDER BY TeamName";
$teams_result = $conn->query($teams_sql);
$teams_result_b = $conn->query($teams_sql); // for the second dropdown

// Fetch ALL Umpires for the "Schedule" form's multi-select
$umpires_list_sql = "SELECT UmpireID, UmpireName FROM UMPIRE ORDER BY UmpireName";
$umpires_list_result = $conn->query($umpires_list_sql);

// Fetch ALL Umpires for the "Manage Umpires" table
$umpires_table_sql = "SELECT UmpireID, UmpireName, NoOfMatches FROM UMPIRE ORDER BY UmpireName";
$umpires_table_result = $conn->query($umpires_table_sql);
?>

<!-- 
    This page has custom styles for the new buttons.
    We add them here so we don't have to edit style.css
-->
<style>
    .player-actions { /* Re-using this style from manager.php */
        display: flex;
        flex-direction: row; /* Side-by-side */
        gap: 8px;
    }
    .player-actions .btn-edit,
    .player-actions .btn-delete {
        padding: 6px 10px;
        font-size: 0.85em;
        font-weight: 600;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s ease;
        text-align: center;
        width: 80px;
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
    #umpire-cancel-btn {
        background-color: #64748b; /* Gray */
        margin-top: 10px;
    }
    #umpire-cancel-btn:hover {
        background-color: #94a3b8;
    }
    .table-wrapper {
        overflow-x: auto;
    }
</style>


<!-- Show message at the top if it exists -->
<?php if ($message): ?>
    <div class="column" style="flex-basis: 100%;">
        <div class="message <?php echo $message_type == 'error' ? 'message-error' : ''; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    </div>
<?php endif; ?>


<!-- Column 1: Schedule a New Match -->
<div class="column">
    <div class="card">
        <h2>Admin: Schedule a New Match</h2>
        <p style="color: #94a3b8; font-size: 0.9em; margin-bottom: 20px;">
            Add a new match to the tournament schedule.
        </p>

        <form action="admin.php" method="POST">
            <div class="form-group">
                <label for="teamA_ID">Select Team A:</label>
                <select id="teamA_ID" name="teamA_ID" required>
                    <option value="">-- Select Team A --</option>
                    <?php
                    if ($teams_result->num_rows > 0) {
                        while($row = $teams_result->fetch_assoc()) {
                            echo "<option value='" . $row['TeamID'] . "'>" . htmlspecialchars($row['TeamName']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="teamB_ID">Select Team B:</label>
                <select id="teamB_ID" name="teamB_ID" required>
                    <option value="">-- Select Team B --</option>
                    <?php
                    if ($teams_result_b->num_rows > 0) {
                        while($row = $teams_result_b->fetch_assoc()) {
                            echo "<option value='" . $row['TeamID'] . "'>" . htmlspecialchars($row['TeamName']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="location">Location:</label>
                <input type="text" id="location" name="location" required>
            </div>
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" required style="color-scheme: dark;">
            </div>
            
            <div class="form-group">
                <label for="umpires">Assign Umpires (Hold Ctrl/Cmd to select multiple):</label>
                <select id="umpires" name="umpires[]" multiple required size="5">
                    <?php
                    if ($umpires_list_result->num_rows > 0) {
                        while($row = $umpires_list_result->fetch_assoc()) {
                            echo "<option value='" . $row['UmpireID'] . "'>" . htmlspecialchars($row['UmpireName']) . "</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No umpires found</option>";
                    }
                    ?>
                </select>
            </div>

            <button type="submit" name="schedule_match" class="btn">Schedule Match</button>
        </form>
    </div>
</div>

<!-- Column 2: Update Match Result -->
<div class="column">
    <div class="card">
        <h2>Admin: Update Match Result</h2>
        <p style="color: #94a3b8; font-size: 0.9em; margin-bottom: 20px;">
            This fires the database trigger and updates the Points Table.
        </p>

        <form action="admin.php" method="POST" onchange="toggleWinnerSelect(this)">
            <div class="form-group">
                <label for="matchId">Select Scheduled Match:</label>
                <select id="matchId" name="matchId" class="match-select" required>
                    <option value="">-- Select a Match --</option>
                    <?php
                    if ($matches_result->num_rows > 0) {
                        // Reset pointer in case it was used
                        $matches_result->data_seek(0); 
                        while($row = $matches_result->fetch_assoc()) {
                            echo "<option value='" . $row['MatchID'] . "' data-teama-id='" . $row['TeamA_ID'] . "' data-teama-name='" . htmlspecialchars($row['TeamA']) . "' data-teamb-id='" . $row['TeamB_ID'] . "' data-teamb-name='" . htmlspecialchars($row['TeamB']) . "'>";
                            echo htmlspecialchars($row['TeamA'] . " vs " . $row['TeamB']);
                            echo "</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No scheduled matches</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="result">Set Result:</label>
                <select id="result" name="result" required>
                    <option value="Completed">Completed</option>
                    <option value="Draw">Draw</option>
                </select>
            </div>
            <div class="form-group" id="winner-group">
                <label for="winningTeamId">Select Winner:</label>
                <select id="winningTeamId" name="winningTeamId" required>
                    <!-- This will be populated by JS -->
                </select>
            </div>
            <button type="submit" name="update_match" class="btn">Update Result</button>
        </form>
    </div>
</div>


<!-- NEW ROW: Umpire Management -->

<!-- Column 1: Add/Edit Umpire Form -->
<div class="column">
    <div class="card">
        <h2 id="umpire-form-title">Admin: Add Umpire</h2>
        <form id="umpire-form" action="admin.php" method="POST">
            <input type="hidden" name="umpire_id" id="form-umpire-id">
            <div class="form-group">
                <label for="umpireName">Umpire Name:</label>
                <input type="text" id="umpireName" name="umpireName" required>
            </div>
            <div class="form-group">
                <label for="noOfMatches">No. of Matches (Legacy):</label>
                <input type="number" id="noOfMatches" name="noOfMatches" value="0">
            </div>
            <button type="submit" name="add_or_update_umpire" id="umpire-submit-btn" class="btn">Add Umpire</button>
            <button type="button" id="umpire-cancel-btn" class="btn" onclick="cancelEditUmpire()" style="display:none; background-color: #64748b; margin-top: 10px;">Cancel Edit</button>
        </form>
    </div>
</div>

<!-- Column 2: Manage Umpires List -->
<div class="column">
    <div class="card">
        <h2>Admin: Manage Umpires</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Umpire Name</th>
                        <th>Matches</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($umpires_table_result->num_rows > 0) {
                        while($row = $umpires_table_result->fetch_assoc()) {
                            $umpire_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['UmpireName']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['NoOfMatches']) . "</td>";
                            echo "<td class='player-actions'>"; // re-using style
                            
                            // Edit Button
                            echo "<button class='btn-edit' onclick='editUmpire($umpire_json)'>Edit</button>";
                            
                            // Delete Button (as a mini-form)
                            echo "<form action='admin.php' method='POST' style='margin:0;' onsubmit='return confirm(\"Are you sure you want to delete this umpire? This will fail if they are assigned to a match.\");'>";
                            echo "<input type='hidden' name='delete_umpire_id' value='" . $row['UmpireID'] . "'>";
                            echo "<button type='submit' class='btn-delete'>Delete</button>";
                            echo "</form>";
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No umpires found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script>
// This JS is for the "Update Match" form
function toggleWinnerSelect(form) {
    const resultSelect = form.querySelector('#result');
    const winnerGroup = form.querySelector('#winner-group');
    const winnerSelect = form.querySelector('#winningTeamId');
    
    // Find the correct match select dropdown
    const matchSelect = form.querySelector('.match-select');
    if (!matchSelect) return;
    
    const selectedMatch = matchSelect.options[matchSelect.selectedIndex];

    if (resultSelect.value === 'Draw') {
        winnerGroup.style.display = 'none';
        winnerSelect.required = false;
    } else {
        winnerGroup.style.display = 'block';
        winnerSelect.required = true;
        
        // Populate winner dropdown based on selected match
        if (selectedMatch && selectedMatch.value) {
            const teamA_ID = selectedMatch.dataset.teamaId;
            const teamA_Name = selectedMatch.dataset.teamaName;
            const teamB_ID = selectedMatch.dataset.teambId;
            const teamB_Name = selectedMatch.dataset.teambName;
            
            winnerSelect.innerHTML = `
                <option value="${teamA_ID}">${teamA_Name}</option>
                <option value="${teamB_ID}">${teamB_Name}</option>
            `;
        } else {
             winnerSelect.innerHTML = '<option value="">-- Select match first --</option>';
        }
    }
}

// --- NEW JAVASCRIPT FOR UMPIRE CRUD ---

function editUmpire(umpire) {
    // Fill the form with the umpire's data
    document.getElementById('form-umpire-id').value = umpire.UmpireID;
    document.getElementById('umpireName').value = umpire.UmpireName;
    document.getElementById('noOfMatches').value = umpire.NoOfMatches;

    // Change form state
    document.getElementById('umpire-form-title').innerText = 'Admin: Edit Umpire';
    document.getElementById('umpire-submit-btn').innerText = 'Save Changes';
    document.getElementById('umpire-cancel-btn').style.display = 'block';

    // Scroll to top to see the form
    document.getElementById('umpire-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function cancelEditUmpire() {
    // Clear all form fields
    document.getElementById('umpire-form').reset();
    document.getElementById('form-umpire-id').value = '';

    // Reset form state
    document.getElementById('umpire-form-title').innerText = 'Admin: Add Umpire';
    document.getElementById('umpire-submit-btn').innerText = 'Add Umpire';
    document.getElementById('umpire-cancel-btn').style.display = 'none';
}

// --- END NEW JAVASCRIPT ---


// Run on load
document.addEventListener('DOMContentLoaded', function() {
    // Find all forms that have this function
    const updateForms = document.querySelectorAll('form[onchange="toggleWinnerSelect(this)"]');
    updateForms.forEach(form => {
        toggleWinnerSelect(form);
        
        // Add listener to the match select dropdown as well
        const matchSelect = form.querySelector('.match-select');
        if (matchSelect) {
            matchSelect.addEventListener('change', function() {
                toggleWinnerSelect(form);
            });
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>