<?php
require_once __DIR__ . '/../includes/functions.php';
start_app_session();

if (is_admin()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Flexi Feet</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        body {
            background: var(--logo-navy);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 60px 40px;
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 400px;
            box-shadow: var(--shadow-premium);
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-card img {
            height: 40px;
            margin-bottom: 40px;
            filter: brightness(0) invert(1);
        }
        
        .login-card h2 {
            color: white;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .login-card p {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
            margin-bottom: 40px;
        }
        
        .form-group {
            text-align: left;
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            color: rgba(255,255,255,0.8);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: var(--radius-md);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            background: rgba(255,255,255,0.12);
            border-color: var(--logo-cyan);
            box-shadow: 0 0 0 4px rgba(63, 186, 211, 0.2);
        }
        
        .login-btn {
            width: 100%;
            padding: 16px;
            background: var(--logo-cyan);
            color: var(--logo-navy);
            border: none;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            background: var(--logo-cyan-hover);
            transform: translateY(-2px);
        }
        
        .error-msg {
            background: rgba(255, 59, 48, 0.1);
            color: #ff3b30;
            padding: 12px;
            border-radius: var(--radius-md);
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 59, 48, 0.2);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 30px;
            color: rgba(255,255,255,0.5);
            font-size: 13px;
            text-decoration: none;
            transition: 0.3s;
        }
        
        .back-link:hover {
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="../assets/images/flexi-feet-logo.png" alt="Flexi Feet">
        <h2>Admin Access</h2>
        <p>Enter your credentials to manage appointments</p>
        
        <?php if ($error): ?>
            <div class="error-msg"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">Sign In</button>
        </form>
        
        <a href="../" class="back-link">&larr; Return to Website</a>
    </div>
</body>
</html>
