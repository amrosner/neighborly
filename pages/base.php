<?php
// pages/base.php
$pageTitle = $pageTitle ?? "Neighborly";
$content   = $content ?? "";
$authPage  = $authPage ?? false;
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

            <?php if (!$authPage): ?>
                <nav class="topbar-nav">
                    <!-- Navigation to be added -->
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