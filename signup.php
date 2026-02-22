<?php
session_start();
require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 
    $name = trim($conn->real_escape_string($_POST['name']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $company_name = isset($_POST['company_name']) ? trim($conn->real_escape_string($_POST['company_name'])) : null;


    if (empty($name)) {
        $errors['name'] = "Full name is required";
    }

    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    } else {
   
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['email'] = "Email already exists";
        }
    }

    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters";
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match";
    }

    if (empty($role)) {
        $errors['role'] = "Please select a role";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, company_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $company_name);
        
        if ($stmt->execute()) {
            $success = "Registration successful! You can now <a href='login.php'>login</a>.";
            
         
            $name = $email = $company_name = '';
            $role = '';
        } else {
            $errors['database'] = "Registration failed: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | FreelanceHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="index.php">FreelanceHub</a>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="browse_job.php">Browse Jobs</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="signup.php" class="active">Signup</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="auth-form">
            <h2>Create Your Account</h2>
            
            <?php if (!empty($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($errors['database'])): ?>
                <div class="alert error"><?php echo $errors['database']; ?></div>
            <?php endif; ?>
            
            <form action="signup.php" method="POST">
                <div class="form-group">
                    <label for="name">Full Name*</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <span class="error-text"><?php echo $errors['name']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address*</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-text"><?php echo $errors['email']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password*</label>
                        <input type="password" id="password" name="password" required>
                        <?php if (isset($errors['password'])): ?>
                            <span class="error-text"><?php echo $errors['password']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password*</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <span class="error-text"><?php echo $errors['confirm_password']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>You Are*</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="role" value="freelancer" <?php echo (isset($role) && $role == 'freelancer') ? 'checked' : ''; ?> required>
                            Freelancer
                        </label>
                        <label>
                            <input type="radio" name="role" value="client" <?php echo (isset($role) && $role == 'client') ? 'checked' : ''; ?>>
                            Client
                        </label>
                    </div>
                    <?php if (isset($errors['role'])): ?>
                        <span class="error-text"><?php echo $errors['role']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group" id="company-field" style="<?php echo (isset($role) && $role == 'client') ? '' : 'display: none;'; ?>">
                    <label for="company_name">Company Name (Optional)</label>
                    <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company_name ?? ''); ?>">
                </div>
                
                <button type="submit" class="btn-primary">Create Account</button>
            </form>
            
            <p class="auth-link">Already have an account? <a href="login.php">Login here</a></p>
        </section>
    </main>

    <script>
        document.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('company-field').style.display = 
                    this.value === 'client' ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>