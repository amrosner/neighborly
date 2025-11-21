<?php
// pages/volunteer_profile.php
session_start();

// Include database configuration
require_once '../config/database.php';

// Include locations from config file
$locationsPath = __DIR__ . '/../config/locations.php';
if (file_exists($locationsPath)) {
    $locations = require $locationsPath;
} else {
    // Fallback if file not found
    $locations = ['San Juan', 'Bayamón', 'Carolina', 'Ponce', 'Caguas'];
}

// Authentication check - redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is a volunteer
if ($_SESSION['role'] !== 'volunteer') {
    header("Location: login.php");
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Check if we're in edit mode
$editMode = isset($_GET['edit']) && $_GET['edit'] === 'true';

// Fetch volunteer information
$stmt = $pdo->prepare("
    SELECT u.username, u.email, u.phone, u.location, v.first_name, v.last_name, v.bio
    FROM users u
    JOIN volunteers v ON u.user_id = v.user_id
    WHERE u.user_id = ?
");
$stmt->execute([$userId]);
$volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch volunteer skills
$stmt = $pdo->prepare("
    SELECT s.skill_id, s.skill_name
    FROM user_skills us
    JOIN skills s ON us.skill_id = s.skill_id
    WHERE us.user_id = ?
");
$stmt->execute([$userId]);
$volunteerSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all available skills for the edit form
$stmt = $pdo->prepare("SELECT skill_id, skill_name FROM skills ORDER BY skill_name");
$stmt->execute();
$allSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch active events volunteer is participating in
$stmt = $pdo->prepare("
    SELECT e.event_id, e.title, ea.status
    FROM event_attendees ea
    JOIN events e ON ea.event_id = e.event_id
    WHERE ea.volunteer_id = ? AND e.status = 'active' AND ea.status = 'registered'
    ORDER BY e.start_date
");
$stmt->execute([$userId]);
$activeEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch past events (completed or cancelled)
$stmt = $pdo->prepare("
    SELECT e.event_id, e.title, e.status
    FROM event_attendees ea
    JOIN events e ON ea.event_id = e.event_id
    WHERE ea.volunteer_id = ? AND (e.status = 'completed' OR e.status = 'cancelled')
    ORDER BY e.start_date DESC
");
$stmt->execute([$userId]);
$pastEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Note: $locations array is defined at the top of the file

$pageTitle = $editMode ? "Edit Profile - Neighborly" : "Profile - Neighborly";
$authPage = false;

ob_start();
?>

<?php if (!$editMode): ?>
    <!-- VIEW MODE -->
    <div class="profile-container">
        <div class="profile-greeting">
            <h1>Greetings @<?php echo htmlspecialchars($volunteer['username']); ?> :)</h1>
        </div>

        <div class="profile-section">
            <h2>User information</h2>
            
            <div class="profile-field">
                <strong>1. Name and Last name</strong>
                <p><?php echo !empty($volunteer['first_name']) && !empty($volunteer['last_name']) 
                    ? htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name']) 
                    : 'No name provided'; ?></p>
            </div>
            
            <div class="profile-field">
                <strong>2. Phone Number</strong>
                <p><?php echo !empty($volunteer['phone']) 
                    ? htmlspecialchars($volunteer['phone']) 
                    : 'No phone number provided'; ?></p>
            </div>
            
            <div class="profile-field">
                <strong>3. Skills</strong>
                <p><?php 
                    if (!empty($volunteerSkills)) {
                        $skillNames = array_map(function($skill) {
                            return htmlspecialchars($skill['skill_name']);
                        }, $volunteerSkills);
                        echo implode(', ', $skillNames);
                    } else {
                        echo 'No skills listed';
                    }
                ?></p>
            </div>
        </div>
        
        <hr class="profile-divider">
        
        <div class="profile-section">
            <h2>Active Events you are participating in</h2>
            
            <?php if (empty($activeEvents)): ?>
                <p class="empty-state">No active events</p>
            <?php else: ?>
                <?php foreach ($activeEvents as $index => $event): ?>
                    <div class="event-item">
                        <p><strong><?php echo ($index + 1) . '. ' . htmlspecialchars($event['title']); ?></strong></p>
                        <p><a href="#">Do you want to stop participating?</a></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <hr class="profile-divider">
        
        <div class="profile-section">
            <h2>Events that you have participated in but are no longer active</h2>
            
            <?php if (empty($pastEvents)): ?>
                <p class="empty-state">No past events</p>
            <?php else: ?>
                <?php foreach ($pastEvents as $index => $event): ?>
                    <div class="event-item">
                        <p><strong><?php echo ($index + 1) . '. ' . htmlspecialchars($event['title']); ?></strong></p>
                        <p>No longer Active</p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="profile-actions">
            <a href="volunteer_profile.php?edit=true">
                <button type="button" class="btn">Edit Profile</button>
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- EDIT MODE -->
    <div class="profile-form">
        <h1>Edit profile</h1>

        <form method="post" action="volunteer_profile.php">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($volunteer['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($volunteer['phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="first_name">First name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($volunteer['first_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="last_name">Last</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($volunteer['last_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="old_password">
            </div>

            <div class="form-group">
                <label>New password</label>
                <input type="password" name="new_password">
            </div>

            <div class="form-group">
                <label>Repeat new password</label>
                <input type="password" name="confirm_password">
            </div>

            <div class="form-group">
                <label>Skills</label>
                
                <div class="skills-group">
                    <?php 
                    // Create array of user's skill IDs for easy checking
                    $userSkillIds = array_column($volunteerSkills, 'skill_id');
                    
                    foreach ($allSkills as $skill): 
                        $isChecked = in_array($skill['skill_id'], $userSkillIds) ? 'checked' : '';
                    ?>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="skill_<?php echo $skill['skill_id']; ?>" name="skills[]" value="<?php echo $skill['skill_id']; ?>" <?php echo $isChecked; ?>>
                            <label for="skill_<?php echo $skill['skill_id']; ?>">
                                <?php echo htmlspecialchars($skill['skill_name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <select id="location" name="location">
                    <option value="">Select a location</option>
                    <?php 
                    if (isset($locations) && is_array($locations)) {
                        foreach ($locations as $location): ?>
                            <option value="<?php echo htmlspecialchars($location); ?>" 
                                <?php echo ($volunteer['location'] ?? '') === $location ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location); ?>
                            </option>
                        <?php endforeach;
                    }
                    ?>
                </select>
            </div>

            <div class="form-buttons">
                <a href="volunteer_profile.php">
                    <button type="button" class="btn-cancel">Cancel</button>
                </a>
                <button type="submit" class="btn-save">Save</button>
            </div>

        </form>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include "base.php";
?>