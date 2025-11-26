<?php
// pages/base.php

$pageTitle = $pageTitle ?? "Neighborly";
$content   = $content ?? "";
$authPage  = $authPage ?? false;

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="/static/css/style.css">
</head>

<body>


    <!--TOP BAR -->
    <header class="<?php echo $authPage ? 'topbar-auth' : 'topbar'; ?>">
        <div class="topbar-inner">
            <div class="topbar-logo">Neighborly</div>

            <?php if (!$authPage && $isLoggedIn): ?>
                <nav class="topbar-nav">
                    <a href="/pages/timeline.php">Timeline</a>
                    
                    <?php if ($userRole === 'volunteer'): ?>
                        <a href="/pages/volunteer_profile.php">Profile</a>
                    <?php elseif ($userRole === 'organizer'): ?>
                        <a href="/pages/organizer_profile.php">Organization</a>
                    <?php elseif ($userRole === 'admin'): ?>
                        <a href="/pages/admin_panel.php">Admin Panel</a>
                    <?php endif; ?>
                    
                    <a href="/pages/logout.php">Sign Out</a>
                </nav>
            <?php endif; ?>
        </div>
    </header>


    <main class="page">
        <?php echo $content; ?>
    </main>
    <script src="/static/js/main.js"></script>

</body>
</html>
