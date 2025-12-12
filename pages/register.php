<?php
// pages/register.php
session_start();
require_once '../config/database.php';

$pageTitle = "Neighborly - Register";
$authPage  = true;
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $is_organization = isset($_POST['is_organization']) ? 1 : 0;
    
    $org_name = trim($_POST['org_name'] ?? '');
    $org_email = trim($_POST['org_email'] ?? '');
    $org_phone = trim($_POST['org_phone'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } elseif ($password !== $password_confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($is_organization && empty($org_name)) {
        $error = "Organization name is required for organizations.";
    } elseif ($is_organization && empty($org_email)) {
        $error = "Organization email is required for organizations.";
    } elseif ($is_organization && !empty($org_phone) && !preg_match('/^\d{3}-\d{3}-\d{4}$/', $org_phone)) {
        $error = "Organization phone must be in the format: 123-456-7890";
    } else {
        try {
            $pdo = connect_to_database();
            
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            
            if ($stmt->fetch()) {
                $error = "Username already exists. Please choose another.";
            } else {
                $hashed_password = hash('sha256', $password);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password_hash, email, phone, location, role) 
                    VALUES (:username, :password_hash, :email, :phone, NULL, :role)
                ");
                
                $role = $is_organization ? 'organizer' : 'volunteer';
                
                $email = $is_organization ? $org_email : NULL;
                $phone = $is_organization ? $org_phone : NULL;

                $stmt->execute([
                    'username' => $username,
                    'password_hash' => $hashed_password,
                    'email' => $email,
                    'phone' => $phone,
                    'role' => $role
                ]);
                
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
                $stmt->execute(['username' => $username]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $user_id = $result['user_id'];
                    
                    if ($is_organization) {
                        $stmt = $pdo->prepare("
                            INSERT INTO organizers (user_id, org_name, org_description) 
                            VALUES (:user_id, :org_name, NULL)
                        ");
                        
                        $stmt->execute([
                            'user_id' => $user_id,
                            'org_name' => $org_name
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO volunteers (user_id, first_name, last_name, bio) 
                            VALUES (:user_id, NULL, NULL, NULL)
                        ");
                        
                        $stmt->execute([
                            'user_id' => $user_id
                        ]);
                    }
                    
                    $success = "Account created successfully! Redirecting to login...";
                    
                    header("refresh:2;url=login.php");
                } else {
                    $error = "Error retrieving user information. Please contact support.";
                }
            }
        } catch (PDOException $e) {
            $error = "Registration failed. Please try again.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

ob_start();
?>
<h1 style="text-align:center;">Create your Neighborly account</h1>
<div class="auth-box">
    <?php if (!empty($error)): ?>
        <div class="error-message" style="background-color: #fee; border: 1px solid #fcc; padding: 10px; margin-bottom: 15px; border-radius: 4px; color: #c33;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="success-message" style="background-color: #efe; border: 1px solid #cfc; padding: 10px; margin-bottom: 15px; border-radius: 4px; color: #3c3;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="register.php">
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
        
        <div class="form-group checkbox-row">
            <input
                type="checkbox"
                id="is_organization"
                name="is_organization"
                value="1"
                <?php echo isset($_POST['is_organization']) ? 'checked' : ''; ?>
            >
            <label for="is_organization">Are you an organization?</label>
        </div>
        
        <div id="org-fields" class="org-extra" style="<?php echo isset($_POST['is_organization']) ? '' : 'display:none;'; ?>">
            <div class="form-group">
                <label for="org_name">Organization name</label>
                <input
                    type="text"
                    id="org_name"
                    name="org_name"
                    value="<?php echo htmlspecialchars($_POST['org_name'] ?? ''); ?>"
                >
            </div>
            <div class="form-group">
                <label for="org_email">Organization email</label>
                <input
                    type="email"
                    id="org_email"
                    name="org_email"
                    value="<?php echo htmlspecialchars($_POST['org_email'] ?? ''); ?>"
                >
            </div>
            <div class="form-group">
                <label for="org_phone">Organization phone</label>
                <input
                    type="tel"
                    id="org_phone"
                    name="org_phone"
                    pattern="\d{3}-\d{3}-\d{4}"
                    title="Phone number must be in the format: 123-456-7890"
                    placeholder="123-456-7890"
                    value="<?php echo htmlspecialchars($_POST['org_phone'] ?? ''); ?>"
                >
                <small style="display: block; color: #666; margin-top: 5px;">Format: 123-456-7890</small>
            </div>
        </div>
        <button type="submit" class="btn btn-full">Register</button>
    </form>
</div>
<p class="helper" style="text-align:center; margin-top: 1.5rem;">
    Already have an account?
    <a href="login.php">Log in here</a>.
</p>

<script>
document.getElementById('is_organization').addEventListener('change', function() {
    document.getElementById('org-fields').style.display = this.checked ? 'block' : 'none';
});

document.getElementById('org_phone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    if (value.length > 3 && value.length <= 6) {
        value = value.replace(/(\d{3})(\d+)/, '$1-$2');
    } else if (value.length > 6) {
        value = value.replace(/(\d{3})(\d{3})(\d+)/, '$1-$2-$3');
    }
    
    if (value.length > 12) {
        value = value.substring(0, 12);
    }
    
    e.target.value = value;
});
</script>

<?php
$content = ob_get_clean();
include "base.php";
?>