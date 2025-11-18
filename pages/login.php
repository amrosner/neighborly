<?php
// pages/login.php
$pageTitle = "Neighborly - Login";
$authPage  = true;

ob_start();
?>
<h1 style="text-align:center;">Log in to Neighborly</h1>
<div class="auth-box">

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

</div>

<p class="helper" style="text-align:center; margin-top: 1rem;">
    Not a registered user?
    <a href="register.php">Click here!</a>
</p>

<?php
$content = ob_get_clean();

include "base.php";