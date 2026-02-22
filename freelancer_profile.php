<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer'){
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$name = $email = $specialization = $location = $education = $skills = $experience_years = $bio = '';
$profileExists = false;
$success = $error = '';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if($user) {
    $name = $user['name'];
    $email = $user['email'];
}
$stmt = $conn->prepare("SELECT * FROM freelancer_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

if($profile) {
    $profileExists = true;
    $specialization = $profile['specialization'];
    $location = $profile['location'];
    $education = $profile['education'];
    $skills = $profile['skills'];
    $experience_years = $profile['experience_years'];
    $bio = $profile['bio'];
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $specialization = $conn->real_escape_string($_POST['specialization']);
    $location = $conn->real_escape_string($_POST['location']);
    $education = $conn->real_escape_string($_POST['education']);
    $skills = $conn->real_escape_string($_POST['skills']);
    $experience_years = (int)$_POST['experience_years'];
    $bio = $conn->real_escape_string($_POST['bio']);

    if($profileExists) {
        $stmt = $conn->prepare("UPDATE freelancer_profiles SET specialization=?, location=?, education=?, skills=?, experience_years=?, bio=? WHERE user_id=?");
        $stmt->bind_param("ssssisi", $specialization, $location, $education, $skills, $experience_years, $bio, $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO freelancer_profiles (user_id, specialization, location, education, skills, experience_years, bio) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssis", $user_id, $specialization, $location, $education, $skills, $experience_years, $bio);
    }
    
    if($stmt->execute()) {
        $success = "Profile saved successfully!";
        $profileExists = true;
    } else {
        $error = "Error saving profile: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | FreelanceHub</title>
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
                <li><a href="freelancer_dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="profile-container">
            <h1>Freelancer Profile</h1>
            <p class="profile-subtitle">Manage your professional profile</p>
            
            <?php if(!empty($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($name); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="specialization">Specialization*</label>
                    <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($specialization); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location*</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="experience_years">Years of Experience*</label>
                        <input type="number" id="experience_years" name="experience_years" min="0" value="<?php echo htmlspecialchars($experience_years); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="education">Education*</label>
                    <input type="text" id="education" name="education" value="<?php echo htmlspecialchars($education); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="skills">Skills* (comma separated)</label>
                    <input type="text" id="skills" name="skills" value="<?php echo htmlspecialchars($skills); ?>" required>
                    <small>Example: Web Design, PHP, MySQL, JavaScript</small>
                </div>
                
                <div class="form-group">
                    <label for="bio">Professional Bio*</label>
                    <textarea id="bio" name="bio" rows="5" required><?php echo htmlspecialchars($bio); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="freelancer_dashboard.php" class="btn-secondary">Back to Dashboard</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>