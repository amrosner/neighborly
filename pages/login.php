<?php
// pages/login.php
$pageTitle = "Neighborly - Login";

ob_start();
?>

<div class="auth-box">
    <h1>Log in to Neighborly</h1>
    <p class="helper">
        Enter your username and password to access your volunteer or organizer dashboard.
    </p>

    <form method="post" action="login.php">
        <div class="form-group">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                required
                maxlength="25"
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                maxlength="25"
            >
        </div>

        <button type="submit" class="btn btn-full">Log in</button>
    </form>

    <p class="helper" style="margin-top: 1rem;">
        Don’t have an account yet?
        <a href="register.php">Register here</a>.
    </p>
</div>

<?php
$content = ob_get_clean();

include "base.php";