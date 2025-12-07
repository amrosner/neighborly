<?php
// pages/test_login.php
// TEMPORARY FILE FOR TESTING - DELETE BEFORE PRODUCTION

// TO USE: http://localhost:8000/pages/test_login.php

// Handle login logic FIRST (before any HTML output)
if (isset($_GET['type'])) {
    session_start();
    $userType = $_GET['type'];

    switch($userType) {
        case 'volunteer':
            $_SESSION['user_id'] = 1;
            $_SESSION['role'] = 'volunteer';
            header("Location: volunteer_profile.php");
            exit;
            
        case 'organizer':
            $_SESSION['user_id'] = 6;
            $_SESSION['role'] = 'organizer';
            header("Location: organizer_profile.php");
            exit;
            
        case 'admin':
            $_SESSION['user_id'] = 9;
            $_SESSION['role'] = 'admin';
            header("Location: admin_panel.php");
            exit;
            
        default:
            $error = "Invalid user type!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Login - Neighborly</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            color: #856404;
        }
        .error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            color: #721c24;
        }
        .instructions {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button-group {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s;
        }
        .btn-volunteer {
            background: #4A90A4;
            color: white;
        }
        .btn-volunteer:hover {
            background: #205B8C;
        }
        .btn-organizer {
            background: #28a745;
            color: white;
        }
        .btn-organizer:hover {
            background: #218838;
        }
        .btn-admin {
            background: #dc3545;
            color: white;
        }
        .btn-admin:hover {
            background: #c82333;
        }
        .info {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #4A90A4;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Login - Neighborly</h1>
        
        <div class="warning">
            <strong>WARNING:</strong> THIS IS ONLY A TEST!
        </div>

        <?php if (isset($error)): ?>
            <div class="error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="instructions">
            <h2>How to Use:</h2>
            <ol>
                <li>Click one of the buttons below to login as a test user</li>
                <li>You will be redirected to the appropriate dashboard</li>
                <li>Test the features (edit profile, manage events, etc.)</li>
                <li>To test as a different user type, come back here and click another button</li>
            </ol>
        </div>

        <h2>Choose a User Type:</h2>
        
        <div class="button-group">
            <a href="?type=volunteer" class="btn btn-volunteer">
                Login as Volunteer<br>
            </a>
            
            <a href="?type=organizer" class="btn btn-organizer">
                Login as Organizer<br>
            </a>
            
            <a href="?type=admin" class="btn btn-admin">
                Login as Admin<br>
            </a>
        </div>

        <div class="info">
            <h3>What Each User Can Do:</h3>
            <ul>
                <li><strong>Volunteer:</strong> View/edit profile, manage skills, join/leave events</li>
                <li><strong>Organizer:</strong> View/edit organization, create events, end campaigns</li>
                <li><strong>Admin:</strong> Approve/reject events, delete users, manage all data</li>
            </ul>
        </div>

        <div class="info">
            <h3>To Logout:</h3>
            <p>click the "Sign Out" link in the navigation bar</p>
        </div>
    </div>
</body>
</html>