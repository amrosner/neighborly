<?php
// pages/organizer_profile.php
session_start();

// Include database configuration
require_once '../config/database.php';

// Connect to database
$pdo = connect_to_database();

// Include locations from config file
$locationsPath = __DIR__ . '/../config/locations.php';
if (!file_exists($locationsPath)) {
    die("Error: Locations file not found at: " . $locationsPath);
}
$locations = require $locationsPath;

// Authentication check - redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is an organizer
if ($_SESSION['role'] !== 'organizer') {
    header("Location: login.php");
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Check if we're in edit mode
$editMode = isset($_GET['edit']) && $_GET['edit'] === 'true';

// Fetch organizer information
$stmt = $pdo->prepare("
    SELECT u.username, u.email, u.phone, u.location, o.org_name, o.org_description
    FROM users u
    JOIN organizers o ON u.user_id = o.user_id
    WHERE u.user_id = ?
");
$stmt->execute([$userId]);
$organizer = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch active events organized by this organizer
$stmt = $pdo->prepare("
    SELECT e.event_id, e.title, e.start_date, e.location, e.is_approved, e.status
    FROM events e
    WHERE e.organizer_id = ? AND e.status = 'active'
    ORDER BY e.start_date
");
$stmt->execute([$userId]);
$activeEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch past events (completed or cancelled)
$stmt = $pdo->prepare("
    SELECT e.event_id, e.title, e.start_date, e.location, e.status
    FROM events e
    WHERE e.organizer_id = ? AND (e.status = 'completed' OR e.status = 'cancelled')
    ORDER BY e.start_date DESC
");
$stmt->execute([$userId]);
$pastEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = $editMode ? "Edit Organization - Neighborly" : "Organization - Neighborly";
$authPage = false;

ob_start();
?>

<?php if (!$editMode): ?>
    <!-- VIEW MODE -->
    <div class="profile-container">
        <h1>Organization</h1>

        <div class="profile-section">
            <h2>Organization information</h2>
            
            <div class="profile-field">
                <strong>1. Organization name</strong>
                <p><?php echo !empty($organizer['org_name']) 
                    ? htmlspecialchars($organizer['org_name']) 
                    : 'No organization name provided'; ?></p>
            </div>
            
            <div class="profile-field">
                <strong>2. Phone Number</strong>
                <p><?php echo !empty($organizer['phone']) 
                    ? htmlspecialchars($organizer['phone']) 
                    : 'No phone number provided'; ?></p>
            </div>
            
            <div class="profile-field">
                <strong>3. Email</strong>
                <p><?php echo !empty($organizer['email']) 
                    ? htmlspecialchars($organizer['email']) 
                    : 'No email provided'; ?></p>
            </div>

            <div class="profile-field">
                <strong>3. Location</strong>
                <p><?php echo !empty($organizer['location']) 
                    ? htmlspecialchars($organizer['location']) 
                    : 'No location provided'; ?></p>
            </div>
        </div>
        
        <hr class="profile-divider">
        
        <div class="profile-section">
            <h2>Active Events</h2>
            
            <?php if (empty($activeEvents)): ?>
                <p class="empty-state">No active events</p>
            <?php else: ?>
                <?php foreach ($activeEvents as $index => $event): ?>
                    <div class="event-item">
                        <p><strong><?php echo ($index + 1) . '. ' . htmlspecialchars($event['title']); ?></strong></p>
                        <p><a href="#">End campaign.</a></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <hr class="profile-divider">
        
        <div class="profile-section">
            <h2>No longer active</h2>
            
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
            <a href="organizer_profile.php?edit=true">
                <button type="button" class="btn">Edit Organization</button>
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- EDIT MODE -->
    <div class="profile-form">
        <h1>Edit Organization</h1>

        <form method="post" action="organizer_profile.php">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($organizer['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="org_name">Organization name</label>
                <input type="text" id="org_name" name="org_name" value="<?php echo htmlspecialchars($organizer['org_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($organizer['phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="first_name">First name</label>
                <input type="text" id="first_name" name="first_name">
            </div>

            <div class="form-group">
                <label for="last_name">Last</label>
                <input type="text" id="last_name" name="last_name">
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
                <label for="location">Location</label>
                <select id="location" name="location">
                    <option value="">Select a location</option>
                    <?php 
                    if (isset($locations) && is_array($locations)) {
                        foreach ($locations as $location): ?>
                            <option value="<?php echo htmlspecialchars($location); ?>" 
                                <?php echo ($organizer['location'] ?? '') === $location ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location); ?>
                            </option>
                        <?php endforeach;
                    }
                    ?>
                </select>
            </div>

            <div class="form-buttons">
                <a href="organizer_profile.php">
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