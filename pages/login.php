<?php
$pageTitle = "Login";

ob_start();
?>
<h1>Login Page</h1>
<p>This is where the login form will go.</p>
<?php
$content = ob_get_clean();

include "base.php";