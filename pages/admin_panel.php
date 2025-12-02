<?php
// pages/admin_panel.php
session_start();

// Include database configuration
require_once '../config/database.php';

// Connect to database
$pdo = connect_to_database();

// Handle admin actions
$successMessage = '';
$errorMessage = '';

// Approve event
if (isset($_POST['approve_event']) && is_numeric($_POST['event_id'])) {
    $eventId = (int)$_POST['event_id'];
    try {
        $stmt = $pdo->prepare("UPDATE events SET is_approved = 1 WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $successMessage = "Event approved successfully.";
    } catch (PDOException $e) {
        $errorMessage = "Error approving event: " . $e->getMessage();
    }
}

// Reject event
if (isset($_POST['reject_event']) && is_numeric($_POST['event_id'])) {
    $eventId = (int)$_POST['event_id'];
    try {
        $stmt = $pdo->prepare("UPDATE events SET is_approved = 0, status = 'cancelled' WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $successMessage = "Event rejected successfully.";
    } catch (PDOException $e) {
        $errorMessage = "Error rejecting event: " . $e->getMessage();
    }
}

// Delete event
if (isset($_POST['delete_event']) && is_numeric($_POST['event_id'])) {
    $eventId = (int)$_POST['event_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $successMessage = "Event deleted successfully.";
    } catch (PDOException $e) {
        $errorMessage = "Error deleting event: " . $e->getMessage();
    }
}

// Delete user
if (isset($_POST['delete_user']) && is_numeric($_POST['user_id'])) {
    $userIdToDelete = (int)$_POST['user_id'];
    
    // Prevent deleting yourself
    if ($userIdToDelete === $_SESSION['user_id']) {
        $errorMessage = "You cannot delete yourself.";
    } else {
        try {
            // Check if user is an admin
            $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
            $stmt->execute([$userIdToDelete]);
            $userToDelete = $stmt->fetch();
            
            if ($userToDelete['role'] === 'admin') {
                $errorMessage = "Cannot delete admin users.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$userIdToDelete]);
                $successMessage = "User deleted successfully.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Error deleting user: " . $e->getMessage();
        }
    }
}

// Authentication check - redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is an admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch pending events (not approved)
$stmt = $pdo->prepare("
    SELECT e.event_id, e.title, e.start_date, e.location, u.username as organizer_name
    FROM events e
    JOIN organizers o ON e.organizer_id = o.user_id
    JOIN users u ON o.user_id = u.user_id
    WHERE e.is_approved = 0
    ORDER BY e.start_date ASC
");
$stmt->execute();
$pendingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all users
$stmt = $pdo->prepare("
    SELECT user_id, username, email, role, location
    FROM users
    ORDER BY user_id ASC
");
$stmt->execute();
$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all events
$stmt = $pdo->prepare("
    SELECT e.event_id, e.title, e.status, e.is_approved, e.start_date, u.username as organizer_name
    FROM events e
    JOIN organizers o ON e.organizer_id = o.user_id
    JOIN users u ON o.user_id = u.user_id
    ORDER BY e.start_date DESC
");
$stmt->execute();
$allEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Admin Panel - Neighborly";
$authPage = false;

ob_start();
?>

<h1>Admin Panel</h1>

<?php if (!empty($successMessage)): ?>
    <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
        <?php echo htmlspecialchars($successMessage); ?>
    </div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
        <?php echo htmlspecialchars($errorMessage); ?>
    </div>
<?php endif; ?>

<div class="admin-section">
    <h2>Pending Event Approvals</h2>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Event ID</th>
                <th>Title</th>
                <th>Organizer</th>
                <th>Date</th>
                <th>Location</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pendingEvents)): ?>
                <tr class="empty-row">
                    <td colspan="6">No pending events to approve</td>
                </tr>
            <?php else: ?>
                <?php foreach ($pendingEvents as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['event_id']); ?></td>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo htmlspecialchars($event['organizer_name']); ?></td>
                        <td><?php echo htmlspecialchars($event['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                        <td>
                            <div class="admin-actions">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                    <button type="submit" name="approve_event" class="btn-approve" 
                                            onclick="return confirm('Approve this event?');">Approve</button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                    <button type="submit" name="reject_event" class="btn-reject"
                                            onclick="return confirm('Reject this event?');">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="admin-section">
    <h2>All Users</h2>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Location</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($allUsers)): ?>
                <tr class="empty-row">
                    <td colspan="6">No users to display</td>
                </tr>
            <?php else: ?>
                <?php foreach ($allUsers as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td><?php echo htmlspecialchars($user['location']); ?></td>
                        <td>
                            <div class="admin-actions">
                                <?php if ($user['role'] !== 'admin'): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit" name="delete_user" class="btn-delete"
                                                onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 0.85rem;">Admin</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="admin-section">
    <h2>All Events</h2>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Event ID</th>
                <th>Title</th>
                <th>Organizer</th>
                <th>Status</th>
                <th>Approved</th>
                <th>Start Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($allEvents)): ?>
                <tr class="empty-row">
                    <td colspan="7">No events to display</td>
                </tr>
            <?php else: ?>
                <?php foreach ($allEvents as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['event_id']); ?></td>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo htmlspecialchars($event['organizer_name']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo htmlspecialchars($event['status']); ?>">
                                <?php echo htmlspecialchars($event['status']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="<?php echo $event['is_approved'] ? 'approved-yes' : 'approved-no'; ?>">
                                <?php echo $event['is_approved'] ? 'Yes' : 'No'; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($event['start_date']); ?></td>
                        <td>
                            <div class="admin-actions">
                                <?php if (!$event['is_approved']): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                        <button type="submit" name="approve_event" class="btn-approve"
                                                onclick="return confirm('Approve this event?');">Approve</button>
                                    </form>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                        <button type="submit" name="reject_event" class="btn-reject"
                                                onclick="return confirm('Reject this event?');">Reject</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                    <button type="submit" name="delete_event" class="btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this event?');">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
include "base.php";
?>
