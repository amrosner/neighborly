<?php
// pages/organizer_profile.php
session_start();

require_once '../config/database.php';

$pdo = connect_to_database();

$locationsPath = __DIR__ . '/../config/locations.php';
if (!file_exists($locationsPath)) {
    die("Error: Locations file not found at: " . $locationsPath);
}

$locations = require $locationsPath;
try {
    $skills_stmt = $pdo->prepare("SELECT skill_id, skill_name FROM skills ORDER BY skill_name");
    $skills_stmt->execute();
    $all_skills = $skills_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Skills fetch error: " . $e->getMessage());
    $all_skills = [];
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'organizer') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $errors = [];
    
    $eventId = (int)$_POST['event_id'];
    $title = trim($_POST['event_title'] ?? '');
    $description = trim($_POST['event_description'] ?? '');
    $location = trim($_POST['event_location'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $slots_available = (int)($_POST['slots_available'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');
    $selected_skills = $_POST['event_skills'] ?? [];
    
    if (empty($title) || empty($description) || empty($location) || empty($start_date) || empty($end_date)) {
        $errors[] = "Please fill in all required fields.";
    } elseif ($slots_available <= 0) {
        $errors[] = "Slots available must be greater than 0.";
    } elseif (!in_array($location, $locations)) {
        $errors[] = "Please select a valid location.";
    } else {
        $start_datetime = strtotime($start_date);
        $end_datetime = strtotime($end_date);
        
        if ($start_datetime === false || $end_datetime === false) {
            $errors[] = "Invalid date format.";
        } elseif ($end_datetime <= $start_datetime) {
            $errors[] = "End date must be after start date.";
        } else {
            try {
                $check_stmt = $pdo->prepare("SELECT event_id FROM events WHERE event_id = :event_id AND organizer_id = :organizer_id");
                $check_stmt->execute(['event_id' => $eventId, 'organizer_id' => $userId]);
                
                if (!$check_stmt->fetch()) {
                    $errors[] = "You don't have permission to edit this event.";
                } else {

                    $stmt = $pdo->prepare("
                        UPDATE events 
                        SET title = :title,
                            description = :description,
                            image_url = :image_url,
                            location = :location,
                            start_date = :start_date,
                            end_date = :end_date,
                            slots_available = :slots_available,
                            updated_at = NOW()
                        WHERE event_id = :event_id AND organizer_id = :organizer_id
                    ");
                    
                    $stmt->execute([
                        'title' => $title,
                        'description' => $description,
                        'image_url' => !empty($image_url) ? $image_url : null,
                        'location' => $location,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'slots_available' => $slots_available,
                        'event_id' => $eventId,
                        'organizer_id' => $userId
                    ]);
                    

                    $delete_skills_stmt = $pdo->prepare("DELETE FROM event_skills WHERE event_id = :event_id");
                    $delete_skills_stmt->execute(['event_id' => $eventId]);
                    

                    if (!empty($selected_skills)) {
                        $skill_stmt = $pdo->prepare("INSERT INTO event_skills (event_id, skill_id) VALUES (:event_id, :skill_id)");
                        foreach ($selected_skills as $skill_id) {
                            $skill_stmt->execute([
                                'event_id' => $eventId,
                                'skill_id' => (int)$skill_id
                            ]);
                        }
                    }
                    
                    header("Location: organizer_profile.php?updated=1");
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Event update error: " . $e->getMessage());
                $errors[] = "Error updating event. Please try again.";
            }
        }
    }
}

if (isset($_GET['end_event']) && is_numeric($_GET['end_event'])) {
    $eventId = (int)$_GET['end_event'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE events 
            SET status = 'cancelled' 
            WHERE event_id = :event_id AND organizer_id = :organizer_id
        ");
        $stmt->execute(['event_id' => $eventId, 'organizer_id' => $userId]);
        
        header("Location: organizer_profile.php?ended=1");
        exit;
    } catch (PDOException $e) {
        $errors[] = "Error ending campaign: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $errors = [];
    
    $email = trim($_POST['email'] ?? '');
    $orgName = trim($_POST['org_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['event_location'] ?? '');
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!empty($newPassword)) {
        if (empty($oldPassword)) {
            $errors[] = "Old password is required to set a new password.";
        } else {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $user = $stmt->fetch();
            
            if (!hash('sha256', $oldPassword) === $user['password_hash']) {
                $errors[] = "Old password is incorrect.";
            }
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = "New passwords do not match.";
        }
        
        if (strlen($newPassword) < 6) {
            $errors[] = "New password must be at least 6 characters.";
        }
    }
    
    if (empty($errors)) {
        try {
            if (!empty($newPassword)) {
                $passwordHash = hash('sha256', $newPassword);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET email = :email, phone = :phone, location = :location, password_hash = :password_hash
                    WHERE user_id = :user_id
                ");
                $stmt->execute([
                    'email' => $email,
                    'phone' => $phone,
                    'location' => $location,
                    'password_hash' => $passwordHash,
                    'user_id' => $userId
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET email = :email, phone = :phone, location = :location
                    WHERE user_id = :user_id
                ");
                $stmt->execute([
                    'email' => $email,
                    'phone' => $phone,
                    'location' => $location,
                    'user_id' => $userId
                ]);
            }
            
            $stmt = $pdo->prepare("
                UPDATE organizers 
                SET org_name = :org_name
                WHERE user_id = :user_id
            ");
            $stmt->execute(['org_name' => $orgName, 'user_id' => $userId]);
            
            header("Location: organizer_profile.php?success=1");
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Error updating profile: " . $e->getMessage();
        }
    }
}

$editMode = isset($_GET['edit']) && $_GET['edit'] === 'true';
$editEventId = isset($_GET['edit_event']) ? (int)$_GET['edit_event'] : null;

$stmt = $pdo->prepare("
    SELECT u.username, u.email, u.phone, u.location, o.org_name, o.org_description
    FROM users u
    JOIN organizers o ON u.user_id = o.user_id
    WHERE u.user_id = :user_id
");
$stmt->execute(['user_id' => $userId]);
$organizer = $stmt->fetch(PDO::FETCH_ASSOC);

$editingEvent = null;
$eventSkills = [];
if ($editEventId) {
    $stmt = $pdo->prepare("
        SELECT event_id, title, description, image_url, location, start_date, end_date, slots_available
        FROM events
        WHERE event_id = :event_id AND organizer_id = :organizer_id
    ");
    $stmt->execute(['event_id' => $editEventId, 'organizer_id' => $userId]);
    $editingEvent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($editingEvent) {
        $skills_stmt = $pdo->prepare("SELECT skill_id FROM event_skills WHERE event_id = :event_id");
        $skills_stmt->execute(['event_id' => $editEventId]);
        $eventSkills = $skills_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

$stmt = $pdo->prepare("
    SELECT e.event_id, e.title, e.start_date, e.location, e.is_approved, e.status
    FROM events e
    WHERE e.organizer_id = :organizer_id AND e.status = 'active'
    ORDER BY e.start_date
");
$stmt->execute(['organizer_id' => $userId]);
$activeEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT e.event_id, e.title, e.start_date, e.location, e.status
    FROM events e
    WHERE e.organizer_id = :organizer_id AND (e.status = 'completed' OR e.status = 'cancelled')
    ORDER BY e.start_date DESC
");
$stmt->execute(['organizer_id' => $userId]);
$pastEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = $editMode ? "Edit Organization - Neighborly" : "Organization - Neighborly";
$authPage = false;

ob_start();
?>

<?php if ($editEventId && $editingEvent): ?>
    <!-- EDIT EVENT MODE -->
    <div class="profile-form">
        <h1>Edit Event: <?php echo htmlspecialchars($editingEvent['title']); ?></h1>

        <?php if (!empty($errors)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                <?php foreach ($errors as $error): ?>
                    <p style="margin: 0.25rem 0;"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="organizer_profile.php">
            <input type="hidden" name="event_id" value="<?php echo $editEventId; ?>">
            
            <div class="form-group">
                <label for="event_title">Event Title *</label>
                <input type="text" id="event_title" name="event_title" required maxlength="255" 
                       value="<?php echo htmlspecialchars($editingEvent['title']); ?>">
            </div>
            
            <div class="form-group">
                <label for="event_description">Description *</label>
                <textarea id="event_description" name="event_description" required rows="5"><?php echo htmlspecialchars($editingEvent['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="event_location">Location *</label>
                <select id="event_location" name="event_location" required>
                    <option value="">Select a location</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo htmlspecialchars($loc); ?>" 
                                <?php echo $editingEvent['location'] === $loc ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="start_date">Start Date & Time *</label>
                <input type="datetime-local" id="start_date" name="start_date" required 
                       value="<?php echo date('Y-m-d\TH:i', strtotime($editingEvent['start_date'])); ?>">
            </div>
            
            <div class="form-group">
                <label for="end_date">End Date & Time *</label>
                <input type="datetime-local" id="end_date" name="end_date" required 
                       value="<?php echo date('Y-m-d\TH:i', strtotime($editingEvent['end_date'])); ?>">
            </div>
            
            <div class="form-group">
                <label for="slots_available">Number of Volunteer Spots *</label>
                <input type="number" id="slots_available" name="slots_available" required min="1" 
                       value="<?php echo $editingEvent['slots_available']; ?>">
            </div>
            
            <div class="form-group">
                <label>Required Skills (optional)</label>
                <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ccc; padding: 0.5rem; border-radius: 4px;">
                    <?php foreach ($all_skills as $skill): ?>
                        <div style="margin-bottom: 0.5rem;">
                            <input type="checkbox" id="skill_<?php echo $skill['skill_id']; ?>" 
                                   name="event_skills[]" value="<?php echo $skill['skill_id']; ?>"
                                   <?php echo in_array($skill['skill_id'], $eventSkills) ? 'checked' : ''; ?>>
                            <label for="skill_<?php echo $skill['skill_id']; ?>" style="font-weight: normal;">
                                <?php echo htmlspecialchars($skill['skill_name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small style="color: #666;">Volunteers must have at least one of these skills to sign up</small>
            </div>
            
            <div class="form-group">
                <label for="image_url">Image URL (optional)</label>
                <input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.jpg" 
                       value="<?php echo htmlspecialchars($editingEvent['image_url'] ?? ''); ?>">
                <small style="color: #666;">Provide a URL to an image for your event</small>
            </div>
            
            <div class="form-buttons">
                <a href="organizer_profile.php">
                    <button type="button" class="btn-cancel">Cancel</button>
                </a>
                <button type="submit" name="update_event" class="btn-save">Update Event</button>
            </div>
        </form>
    </div>

<?php elseif (!$editMode): ?>
    <div class="profile-container">
        <h1>Organization</h1>

        <?php if (isset($_GET['success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                Profile updated successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['updated'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                Event updated successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['ended'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                Campaign ended successfully.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                <?php foreach ($errors as $error): ?>
                    <p style="margin: 0.25rem 0;"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

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
                <strong>4. Location</strong>
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
                        <p>
                            <a href="organizer_profile.php?edit_event=<?php echo $event['event_id']; ?>" 
                               style="margin-right: 1rem;">Edit event</a>
                            |
                            <a href="organizer_profile.php?end_event=<?php echo $event['event_id']; ?>"
                               onclick="return confirm('Are you sure you want to end this campaign?');"
                               style="margin-left: 1rem;">
                              End campaign
                           </a>
                        </p>
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
                <button type="submit" name="save_profile" class="btn-save">Save</button>
            </div>

        </form>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include "base.php";
?>