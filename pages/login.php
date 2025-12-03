<?php
// pages/login.php
session_start();
require_once '../config/database.php';

$pageTitle = "Neighborly - Login";
$authPage  = true;
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            $pdo = connect_to_database();
            
            $stmt = $pdo->prepare("SELECT USER_ID, USERNAME, PASSWORD_HASH FROM USERS WHERE USERNAME = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();
            // echo "We survived fetch";
            
            if ($user && hash('sha256', $password) === $user['PASSWORD_HASH']) {
                // echo "We made it to verification";
                header("Location: register.php"); // OJO: WE NEED TO CHANGE THIS TO TIMELINE OR PROFILE WHEN IT IS DONE. 
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again later.";
            error_log("Login error: " . $e->getMessage());
        }
    } else {
        $error = "Please enter both username and password.";
    }
}

ob_start();
?>
<h1 style="text-align:center;">Log in to Neighborly</h1>
<div class="auth-box">
    <?php if (!empty($error)): ?>
        <div class="error-message" style="background-color: #fee; border: 1px solid #fcc; padding: 10px; margin-bottom: 15px; border-radius: 4px; color: #c33;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="login.php">
        <div class="form-group">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                required
                maxlength="25"
                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
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
?>