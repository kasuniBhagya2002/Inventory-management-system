<?php
// ආරක්ෂක පියවර 1: Session Cookie ආරක්ෂිත කිරීම
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true, // HTTPS අනිවාර්යයි
    'httponly' => true, // JavaScript මගින් Cookie කියවීම වළක්වයි
    'samesite' => 'Strict' // CSRF ආරක්ෂාව සඳහා
]);

session_start();

// ආරක්ෂක පියවර: CSRF Token එක සැකසීම (Login form එක ආරක්ෂා කිරීමට)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ආරක්ෂක පියවර 2: Clickjacking සහ XSS ආරක්ෂාව
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block"); // පැරණි බ්‍රව්සර් සඳහා
header("Referrer-Policy: strict-origin-when-cross-origin");

include("connection.php");

// Check if user is already logged in
if (isset($_SESSION['ID'])) {
    header("Location: select_action.php");
    exit();
}

$error_message = "";
$error_type = "";

// ආරක්ෂක පියවර: Brute Force Attack වැළැක්වීම (Login උත්සාහයන් ගණනය කිරීම)
// විනාඩි 10ක් ඇතුළත 5 වතාවක් වැරදුවොත් තාවකාලිකව අවහිර කරයි.
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 5) {
    $lockout_time = 10 * 60; // විනාඩි 10
    if (time() - $_SESSION['last_attempt_time'] < $lockout_time) {
        $error_message = "වැරදි උත්සාහයන් වැඩි බැවින් Login වීම තාවකාලිකව නවතා ඇත. මිනිත්තු 10 කින් උත්සාහ කරන්න.";
        $error_type = "blocked";
    } else {
        // කාලය ඉකුත් වී ඇත්නම් නැවත උත්සාහ කිරීමට ඉඩ දෙන්න
        $_SESSION['login_attempts'] = 0;
    }
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error_type)) {

    // ආරක්ෂක පියවර: CSRF Token එක පරීක්ෂා කිරීම
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF Token. Please refresh the page.");
    }

    $username = trim($_POST["Username"]);
    $password = $_POST["Password"];

    // SQL Query (ඔබේ Logic එක වෙනස් නොකර තබා ඇත)
    $query = "SELECT * FROM user WHERE Username = ? AND Password = ?";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $userData = $result->fetch_assoc();

            // Login සාර්ථකයි - වැරදි උත්සාහයන් Reset කරන්න
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_attempt_time']);

            // Session Fixation වැළැක්වීම
            session_regenerate_id(true);

            $_SESSION['user_id'] = $userData['ID'];
            $_SESSION['Username'] = $userData['Username'];
            $_SESSION['Name'] = $userData['Name'];
            $_SESSION['role'] = trim($userData['Role']);
            $_SESSION['Province_id'] = $userData['Province_id'];
            $_SESSION['district_id'] = $userData['district_id'];
            $_SESSION['dsid'] = $userData['dsid'];
            $_SESSION['ID'] = $userData['ID'];
            $_SESSION['Position'] = $userData['Position'];
            $_SESSION['Photo'] = $userData['Photo'];
            $_SESSION['email'] = $userData['email'];
            $_SESSION['section_id'] = $userData['section_id'];

            // Redirect logic
            $redirectPage = "select_action.php";
            if ($userData['role'] === "leave_subject") {
                $redirectPage = "leaves_admin_view.php";
            }
            if ($userData['role'] === "developer") {
                $redirectPage = "developer.php";
            }

            echo '<!DOCTYPE html>
            <html>
            <head>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            </head>
            <body>
            <script>
            Swal.fire({
                icon: "success",
                title: "Welcome back!",
                text: "You have been successfully logged in.",
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = "'.$redirectPage.'";
            });
            </script>
            </body>
            </html>';
            exit();
        } else {
            // Login අසාර්ථකයි - වැරදි උත්සාහයන් ගණනය කිරීම
            if (!isset($_SESSION['login_attempts'])) {
                $_SESSION['login_attempts'] = 0;
            }
            $_SESSION['login_attempts'] += 1;
            $_SESSION['last_attempt_time'] = time();

            $error_message = "ඇතුළත් කරන ලද Username හෝ Password වැරදියි.";
            $error_type = "credentials";
        }
        $stmt->close();
    } else {
        error_log("Database Prepare Error: " . $conn->error);
        $error_message = "තාක්ෂණික දෝෂයක්. කරුණාකර නැවත උත්සාහ කරන්න.";
        $error_type = "database";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Login</title>
    <link href="css/style.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* (ඔබේ CSS කේතය නොවෙනස්ව මෙහි ඇතැයි සිතන්න - ඉඩ ඉතිරි කිරීමට මෙතන නැවත type නොකරමි) */
        :root { --primary-color: #007bff; --secondary-color: #6c757d; --success-color: #28a745; --danger-color: #dc3545; --warning-color: #ffc107; --info-color: #17a2b8; --light-color: #f8f9fa; --dark-color: #343a40; --border-color: #dee2e6; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .login-container { width: 100%; max-width: 420px; margin: 0 auto; }
        .login-card { background: white; border-radius: 16px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .login-header { background: white; padding: 40px 40px 20px 40px; text-align: center; border-bottom: 1px solid var(--border-color); }
        .login-title { color: var(--dark-color); font-size: 28px; font-weight: 700; margin-bottom: 0; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .login-body { padding: 30px 40px 40px 40px; }
        .form-group { margin-bottom: 24px; position: relative; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark-color); font-size: 14px; text-transform: uppercase; }
        .form-control { width: 100%; padding: 14px 16px; border: 2px solid var(--border-color); border-radius: 10px; font-size: 16px; transition: all 0.3s ease; }
        .form-control:focus { outline: none; border-color: var(--primary-color); }
        .password-container { position: relative; }
        .toggle-password { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--secondary-color); cursor: pointer; }
        .login-button { width: 100%; padding: 14px 20px; background: linear-gradient(135deg, var(--primary-color), #0056b3); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 25px; }
        .error-message { background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; padding: 16px; border-radius: 10px; margin-bottom: 20px; }
        .btn-loading { position: relative; color: transparent !important; }
        .btn-loading::after { content: ''; position: absolute; width: 20px; height: 20px; top: 50%; left: 50%; margin-left: -10px; margin-top: -10px; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 50%; border-top-color: white; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .remember-forgot { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .remember-me { display: flex; align-items: center; gap: 8px; }
        .remember-text { font-size: 14px; color: var(--dark-color); cursor: pointer; }
        .forgot-password { color: var(--primary-color); text-decoration: none; font-size: 14px; }
        .checkbox { width: 18px; height: 18px; border: 2px solid var(--border-color); border-radius: 4px; cursor: pointer; position: relative; }
        .checkbox.checked { background: var(--primary-color); border-color: var(--primary-color); }
        .checkbox.checked::after { content: '✓'; position: absolute; color: white; font-size: 12px; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .signup-link { text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color); }
        .signup-link a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">Login</h1>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="loginForm" autocomplete="off">
                    
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="form-group">
                        <label class="form-label" for="Username">Username</label>
                        <input class="form-control" id="Username" name="Username" type="text" 
                               placeholder="Enter your username"
                               value="<?php echo isset($_POST['Username']) ? htmlspecialchars($_POST['Username']) : ''; ?>" 
                               required autocomplete="off" />
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="inputPassword">Password</label>
                        <div class="password-container">
                            <input class="form-control" id="inputPassword" name="Password" type="password" 
                                   placeholder="Enter your password" required autocomplete="off" />
                            <button type="button" class="toggle-password" id="togglePassword" title="Show/Hide Password">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="remember-forgot">
                        <div class="remember-me">
                            <div class="checkbox" id="rememberCheckbox"></div>
                            <span class="remember-text">Remember me</span>
                        </div>
                        <a href="forgot_password.php" class="forgot-password">
                            Forgot password?
                        </a>
                    </div>
                    
                    <button class="login-button" type="submit" id="loginButton" <?php echo ($error_type === 'blocked') ? 'disabled' : ''; ?>>
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                    
                    <div class="signup-link">
                        <span class="signup-text">Need an account? </span>
                        <a href="register.php">Sign up!</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password toggle functionality
        const passwordField = document.getElementById('inputPassword');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword.addEventListener('click', function () {
            const type = passwordField.type === 'password' ? 'text' : 'password';
            passwordField.type = type;
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Remember me functionality
        const rememberCheckbox = document.getElementById('rememberCheckbox');
        if(rememberCheckbox){
            rememberCheckbox.addEventListener('click', function() {
                this.classList.toggle('checked');
            });
        }

        // Auto-hide error
        document.addEventListener("DOMContentLoaded", function() {
            const errorAlert = document.querySelector('.error-message');
            if (errorAlert) {
                // Brute force block එකක් නම් error එක hide කරන්න එපා
                <?php if ($error_type !== 'blocked'): ?>
                setTimeout(() => {
                    errorAlert.style.opacity = '0';
                    setTimeout(() => { errorAlert.style.display = 'none'; }, 300);
                }, 5000);
                <?php endif; ?>
            }
        });

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('Username').value.trim();
            const password = document.getElementById('inputPassword').value;
            const loginButton = document.getElementById('loginButton');

            if (!username || !password) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please fill in all required fields.',
                    confirmButtonColor: '#007bff'
                });
                return false;
            }

            loginButton.classList.add('btn-loading');
        });
    </script>
</body>
</html>