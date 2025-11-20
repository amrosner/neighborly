<?php
// pages/admin_panel.php
session_start();

// DO LATER: Add style later.

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
            <tr class="empty-row">
                <td colspan="6">No pending events to approve</td>
            </tr>
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
            <tr class="empty-row">
                <td colspan="6">No users to display</td>
            </tr>
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
            <tr class="empty-row">
                <td colspan="7">No events to display</td>
            </tr>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
include "base.php";
?>