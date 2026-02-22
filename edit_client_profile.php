<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client'){
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$name = $email = $company_name = $location = $bio = '';
$errors = [];
$success = '';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email, company_name, location, bio FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

if($client) {
    $name = $client['name'];
    $email = $client['email'];
    $company_name = $client['company_name'];
    $location = $client['location'];
    $bio = $client['bio'];
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($conn->real_escape_string($_POST['name']));
    $company_name = trim($conn->real_escape_string($_POST['company_name']));
    $location = trim($conn->real_escape_string($_POST['location']));
    $bio = trim($conn->real_escape_string($_POST['bio']));


    if(empty($name)) {
        $errors['name'] = "Name is required";
    }

    if(strlen($bio) > 500) {
        $errors['bio'] = "Bio must be less than 500 characters";
    }

    if(empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, company_name = ?, location = ?, bio = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $company_name, $location, $bio, $user_id);
        
        if($stmt->execute()) {
            $success = "Profile updated successfully!";

            $_SESSION['name'] = $name;
        } else {
            $errors['database'] = "Error updating profile: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | FreelanceHub</title>
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
                <li><a href="post_job.php">Post a Job</a></li>
                <li><a href="client_profile.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="edit-profile-container">
        <section class="edit-profile-form">
            <h1>Edit Your Profile</h1>
            
            <?php if(!empty($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($errors['database'])): ?>
                <div class="alert error"><?php echo $errors['database']; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name*</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    <?php if(isset($errors['name'])): ?>
                        <span class="error-text"><?php echo $errors['name']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
                    <small>Contact support to change your email</small>
                </div>
                
                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company_name); ?>">
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>">
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="5"><?php echo htmlspecialchars($bio); ?></textarea>
                    <?php if(isset($errors['bio'])): ?>
                        <span class="error-text"><?php echo $errors['bio']; ?></span>
                    <?php endif; ?>
                    <small>Max 500 characters (<?php echo 500 - strlen($bio); ?> remaining)</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="client_profile.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </section>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> FreelanceHub. All rights reserved.</p>
    </footer>
</body>
</html>