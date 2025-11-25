<?php
// pages/admin_panel.php
session_start();

// Include database configuration
require_once '../config/database.php';

// Connect to database
$pdo = connect_to_database();

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
                                <button class="btn-approve">Approve</button>
                                <button class="btn-reject">Reject</button>
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
                                <button class="btn-view">View</button>
                                <button class="btn-delete">Delete</button>
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
                                    <button class="btn-approve">Approve</button>
                                    <button class="btn-reject">Reject</button>
                                <?php endif; ?>
                                <button class="btn-delete">Delete</button>
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
