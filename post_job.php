<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $job_title = trim($conn->real_escape_string($_POST['job_title']));
    $job_type = trim($conn->real_escape_string($_POST['job_type']));
    $description = trim($conn->real_escape_string($_POST['description']));
    $skills = trim($conn->real_escape_string($_POST['skills']));
    $budget = (float)$_POST['budget'];
    $deadline = trim($conn->real_escape_string($_POST['deadline']));
    $notes = isset($_POST['notes']) ? trim($conn->real_escape_string($_POST['notes'])) : '';
    $client_id = $_SESSION['user_id'];

    if (empty($job_title)) {
        $errors['job_title'] = "Job title is required";
    }

    if (empty($description)) {
        $errors['description'] = "Description is required";
    }

    if (empty($skills)) {
        $errors['skills'] = "Skills are required";
    }

    if ($budget <= 0) {
        $errors['budget'] = "Budget must be positive";
    }

    if (empty($deadline)) {
        $errors['deadline'] = "Deadline is required";
    } elseif (strtotime($deadline) < strtotime('today')) {
        $errors['deadline'] = "Deadline must be in the future";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO jobs 
                              (client_id, job_title, job_type, description, skills, budget, deadline, notes) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssdss", $client_id, $job_title, $job_type, $description, $skills, $budget, $deadline, $notes);
        
        if ($stmt->execute()) {
            $success = "Job posted successfully!";
            header("Location: client_profile.php");
            exit();
        } else {
            $errors['database'] = "Error posting job: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job | FreelanceHub</title>
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
                <li><a href="browse_jobs.php">Browse Jobs</a></li>
                <li><a href="post_job.php" class="active">Post a Job</a></li>
                <li><a href="client_profile.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="post-job-container">
        <section class="post-job-form">
            <h1>Post a New Job</h1>
            
            <?php if (!empty($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($errors['database'])): ?>
                <div class="alert error"><?php echo $errors['database']; ?></div>
            <?php endif; ?>
            
            <form action="post_job.php" method="POST">
                <div class="form-group">
                    <label for="job_title">Job Title*</label>
                    <input type="text" id="job_title" name="job_title" value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>" required>
                    <?php if (isset($errors['job_title'])): ?>
                        <span class="error-text"><?php echo $errors['job_title']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="job_type">Job Type*</label>
                    <select id="job_type" name="job_type" required>
                        <option value="">Select job type</option>
                        <option value="Full-Time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'Full-Time') ? 'selected' : ''; ?>>Full-Time</option>
                        <option value="Part-Time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'Part-Time') ? 'selected' : ''; ?>>Part-Time</option>
                        <option value="Contract" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'Contract') ? 'selected' : ''; ?>>Contract</option>
                        <option value="Internship" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'Internship') ? 'selected' : ''; ?>>Internship</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Job Description*</label>
                    <textarea id="description" name="description" rows="6" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <span class="error-text"><?php echo $errors['description']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="skills">Skills Required*</label>
                    <input type="text" id="skills" name="skills" value="<?php echo htmlspecialchars($_POST['skills'] ?? ''); ?>" required placeholder="e.g. PHP, HTML, CSS, JavaScript (comma separated)">
                    <?php if (isset($errors['skills'])): ?>
                        <span class="error-text"><?php echo $errors['skills']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="budget">Budget (USD)*</label>
                        <div class="input-with-symbol">
                            <span class="symbol">$</span>
                            <input type="number" id="budget" name="budget" min="0" step="0.01" value="<?php echo htmlspecialchars($_POST['budget'] ?? ''); ?>" required>
                        </div>
                        <?php if (isset($errors['budget'])): ?>
                            <span class="error-text"><?php echo $errors['budget']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="deadline">Timeframe/Deadline*</label>
                        <input type="date" id="deadline" name="deadline" value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>" required>
                        <?php if (isset($errors['deadline'])): ?>
                            <span class="error-text"><?php echo $errors['deadline']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Additional Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="4"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Post Job</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>