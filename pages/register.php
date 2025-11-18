<?php
// pages/register.php
$pageTitle = "Neighborly - Register";
$authPage  = true;

ob_start();
?>

<h1 style="text-align:center;">Create your Neighborly account</h1>

<div class="auth-box">
    <form method="post" action="register.php">
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

        <div class="form-group">
            <label for="password_confirm">Confirm password</label>
            <input
                type="password"
                id="password_confirm"
                name="password_confirm"
                required
                maxlength="25"
            >
        </div>

        <!-- Organization checkbox -->
        <div class="form-group checkbox-row">
            <input
                type="checkbox"
                id="is_organization"
                name="is_organization"
                value="1"
            >
            <label for="is_organization">Are you an organization?</label>
        </div>

        <!-- Extra fields that apply to organizations (hidden until checked) -->
        <div id="org-fields" class="org-extra">
            <div class="form-group">
                <label for="org_name">Organization name</label>
                <input
                    type="text"
                    id="org_name"
                    name="org_name"
                >
            </div>

            <div class="form-group">
                <label for="org_email">Organization email</label>
                <input
                    type="email"
                    id="org_email"
                    name="org_email"
                >
            </div>

            <div class="form-group">
                <label for="org_phone">Organization phone</label>
                <input
                    type="tel"
                    id="org_phone"
                    name="org_phone"
                >
            </div>
        </div>

        <button type="submit" class="btn btn-full">Register</button>
    </form>
</div>

<p class="helper" style="text-align:center; margin-top: 1.5rem;">
    Already have an account?
    <a href="login.php">Log in here</a>.
</p>

<?php
$content = ob_get_clean();
include "base.php";