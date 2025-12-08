<?php
session_start();

require_once '../config/database.php';

$pdo = connect_to_database();

$locationsPath = __DIR__ . '/../config/locations.php';
if (!file_exists($locationsPath)) {
    die("Error: Locations file not found at: " . $locationsPath);
}
$locations = require $locationsPath;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'volunteer') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$errors = [];

if (isset($_GET['stop_event']) && is_numeric($_GET['stop_event'])) {
    $eventId = (int)$_GET['stop_event'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE event_attendees 
            SET status = 'cancelled' 
            WHERE event_id = :event_id AND volunteer_id = :volunteer_id
        ");
        $stmt->execute([
            'event_id' => $eventId,
            'volunteer_id' => $userId
        ]);
        
        header("Location: volunteer_profile.php?stopped=1");
        exit;
    } catch (PDOException $e) {
        $errors[] = "Error removing from event: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $errors = [];
    
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $skills = $_POST['skills'] ?? [];
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!empty($phone)) {
        if (!preg_match('/^\d{3}-\d{3}-\d{4}$/', $phone)) {
            $errors[] = "Phone number must be in format: XXX-XXX-XXXX (e.g., 123-456-7890)";
        }
    }
    
    if (!empty($newPassword)) {
        if (empty($oldPassword)) {
            $errors[] = "Old password is required to set a new password.";
        } else {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $user = $stmt->fetch();
            
            if ($user && hash('sha256', $oldPassword) !== $user['password_hash']) {
                $errors[] = "Old password is incorrect.";
            } elseif (!$user) {
                $errors[] = "User not found.";
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
                UPDATE volunteers 
                SET first_name = :first_name, last_name = :last_name
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'user_id' => $userId
            ]);
            
            $stmt = $pdo->prepare("DELETE FROM user_skills WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            
            if (!empty($skills)) {
                $stmt = $pdo->prepare("INSERT INTO user_skills (user_id, skill_id) VALUES (:user_id, :skill_id)");
                foreach ($skills as $skillId) {
                    $stmt->execute([
                        'user_id' => $userId,
                        'skill_id' => $skillId
                    ]);
                }
            }
            
            header("Location: volunteer_profile.php?success=1");
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Error updating profile: " . $e->getMessage();
        }
    }
}

$editMode = isset($_GET['edit']) && $_GET['edit'] === 'true';

$stmt = $pdo->prepare("
    SELECT u.username, u.email, u.phone, u.location, v.first_name, v.last_name, v.bio
    FROM users u
    JOIN volunteers v ON u.user_id = v.user_id
    WHERE u.user_id = :user_id
");
$stmt->execute(['user_id' => $userId]);
$volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT s.skill_id, s.skill_name
    FROM user_skills us
    JOIN skills s ON us.skill_id = s.skill_id
    WHERE us.user_id = :user_id
");
$stmt->execute(['user_id' => $userId]);
$volunteerSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT skill_id, skill_name FROM skills ORDER BY skill_name");
$stmt->execute();
$allSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT e.event_id, e.title, ea.status
    FROM event_attendees ea
    JOIN events e ON ea.event_id = e.event_id
    WHERE ea.volunteer_id = :volunteer_id 
    AND e.status = 'active' 
    AND ea.status = 'registered'
    ORDER BY e.start_date
");
$stmt->execute(['volunteer_id' => $userId]);
$activeEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT e.event_id, e.title, e.status AS event_status, ea.status AS attendee_status
    FROM event_attendees ea
    JOIN events e ON ea.event_id = e.event_id
    WHERE ea.volunteer_id = :volunteer_id
    AND (
        e.status = 'completed' 
        OR e.status = 'cancelled' 
        OR ea.status = 'cancelled'
    )
    AND NOT (e.status = 'active' AND ea.status = 'registered')
    ORDER BY e.start_date DESC
");
$stmt->execute(['volunteer_id' => $userId]);
$pastEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = $editMode ? "Edit Profile - Neighborly" : "Profile - Neighborly";
$authPage = false;

ob_start();
?>

<?php if (!$editMode): ?>
    <div class="profile-container">
        <div class="profile-greeting">
            <h1>Greetings @<?php echo htmlspecialchars($volunteer['username']); ?> :)</h1>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                Profile updated successfully!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['stopped'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                You have stopped participating in the event.
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
                        <p><a href="volunteer_profile.php?stop_event=<?php echo $event['event_id']; ?>" 
                              onclick="return confirm('Are you sure you want to stop participating in this event?');">
                              Do you want to stop participating?
                           </a></p>
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
                        <p>
                            <?php if ($event['event_status'] === 'completed' && $event['attendee_status'] === 'registered'): ?>
                                Event Completed
                            <?php elseif ($event['event_status'] === 'cancelled'): ?>
                                Event Cancelled
                            <?php elseif ($event['attendee_status'] === 'cancelled'): ?>
                                You stopped participating
                            <?php endif; ?>
                        </p>
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
    <div class="profile-form">
        <h1>Edit profile</h1>

        <form method="post" action="volunteer_profile.php">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($volunteer['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone (Format: XXX-XXX-XXXX)</label>
                <input type="tel" 
                       id="phone" 
                       name="phone" 
                       value="<?php echo htmlspecialchars($volunteer['phone'] ?? ''); ?>"
                       pattern="^\d{3}-\d{3}-\d{4}$"
                       title="Phone number must be in format: XXX-XXX-XXXX (e.g., 123-456-7890)"
                       placeholder="123-456-7890"
                       oninput="formatPhoneNumber(this)">
                <small style="color: #666; font-size: 0.9rem;">Format: XXX-XXX-XXXX (e.g., 123-456-7890)</small>
            </div>

            <div class="form-group">
                <label for="first_name">First name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($volunteer['first_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($volunteer['last_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Password (leave blank to keep current)</label>
                <input type="password" name="old_password">
            </div>

            <div class="form-group">
                <label>New password (leave blank to keep current)</label>
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
                <select id="location" name="location" required>
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
                <button type="submit" name="save_profile" class="btn-save">Save</button>
            </div>

        </form>
    </div>

    <script>
    function formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        
        if (value.length > 0) {
            if (value.length <= 3) {
                input.value = value;
            } else if (value.length <= 6) {
                input.value = value.substr(0, 3) + '-' + value.substr(3);
            } else {
                input.value = value.substr(0, 3) + '-' + value.substr(3, 3) + '-' + value.substr(6, 4);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('phone');
        if (phoneInput && phoneInput.value) {
            if (!/^\d{3}-\d{3}-\d{4}$/.test(phoneInput.value)) {
                formatPhoneNumber(phoneInput);
            }
        }
        
        if (phoneInput) {
            phoneInput.addEventListener('keydown', function(e) {
                if ([46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                    (e.keyCode === 65 && e.ctrlKey === true) ||
                    (e.keyCode === 67 && e.ctrlKey === true) ||
                    (e.keyCode === 86 && e.ctrlKey === true) ||
                    (e.keyCode === 88 && e.ctrlKey === true) ||
                    (e.keyCode >= 35 && e.keyCode <= 39)) {
                    return;
                }
                
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
        }
    });
    </script>

<?php endif; ?>

<?php
$content = ob_get_clean();
include "base.php";
?>