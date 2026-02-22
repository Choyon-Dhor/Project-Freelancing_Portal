<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: client_profile.php");
    exit();
}

$job_id = (int)$_GET['id'];
$client_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND client_id = ?");
$stmt->bind_param("ii", $job_id, $client_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    header("Location: client_profile.php");
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
    $status = trim($conn->real_escape_string($_POST['status']));

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
        $stmt = $conn->prepare("UPDATE jobs SET 
                              job_title = ?, 
                              job_type = ?, 
                              description = ?, 
                              skills = ?, 
                              budget = ?, 
                              deadline = ?, 
                              notes = ?,
                              status = ?
                              WHERE id = ? AND client_id = ?");
        $stmt->bind_param("ssssdsssii", $job_title, $job_type, $description, $skills, $budget, $deadline, $notes, $status, $job_id, $client_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Job updated successfully!";
            header("Location: view_job.php?id=$job_id");
            exit();
        } else {
            $errors['database'] = "Error updating job: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job | FreelanceHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .edit-job-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .edit-job-form {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .edit-job-form h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-save {
            background-color: #3498db;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-cancel {
            background-color: #e74c3c;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
        }
        
        .error-text {
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            display: block;
        }
        
        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }
            
            .btn-save, .btn-cancel {
                width: 100%;
                text-align: center;
            }
        }
    </style>
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

    <main class="edit-job-container">
        <section class="edit-job-form">
            <h1>Edit Job</h1>
            
            <?php if (isset($errors['database'])): ?>
                <div class="alert error"><?php echo $errors['database']; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="job_title">Job Title*</label>
                    <input type="text" id="job_title" name="job_title" 
                           value="<?php echo htmlspecialchars($job['job_title']); ?>" required>
                    <?php if (isset($errors['job_title'])): ?>
                        <span class="error-text"><?php echo $errors['job_title']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="job_type">Job Type*</label>
                    <select id="job_type" name="job_type" required>
                        <option value="Full-Time" <?php echo $job['job_type'] == 'Full-Time' ? 'selected' : ''; ?>>Full-Time</option>
                        <option value="Part-Time" <?php echo $job['job_type'] == 'Part-Time' ? 'selected' : ''; ?>>Part-Time</option>
                        <option value="Contract" <?php echo $job['job_type'] == 'Contract' ? 'selected' : ''; ?>>Contract</option>
                        <option value="Internship" <?php echo $job['job_type'] == 'Internship' ? 'selected' : ''; ?>>Internship</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status*</label>
                    <select id="status" name="status" required>
                        <option value="Open" <?php echo $job['status'] == 'Open' ? 'selected' : ''; ?>>Open</option>
                        <option value="In Progress" <?php echo $job['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?php echo $job['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Job Description*</label>
                    <textarea id="description" name="description" required><?php 
                        echo htmlspecialchars($job['description']); 
                    ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <span class="error-text"><?php echo $errors['description']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="skills">Skills Required* (comma separated)</label>
                    <input type="text" id="skills" name="skills" 
                           value="<?php echo htmlspecialchars($job['skills']); ?>" required>
                    <?php if (isset($errors['skills'])): ?>
                        <span class="error-text"><?php echo $errors['skills']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="budget">Budget (USD)*</label>
                    <input type="number" id="budget" name="budget" min="0" step="0.01"
                           value="<?php echo htmlspecialchars($job['budget']); ?>" required>
                    <?php if (isset($errors['budget'])): ?>
                        <span class="error-text"><?php echo $errors['budget']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="deadline">Deadline*</label>
                    <input type="date" id="deadline" name="deadline" 
                           value="<?php echo htmlspecialchars($job['deadline']); ?>" required>
                    <?php if (isset($errors['deadline'])): ?>
                        <span class="error-text"><?php echo $errors['deadline']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="notes">Additional Notes (Optional)</label>
                    <textarea id="notes" name="notes"><?php 
                        echo htmlspecialchars($job['notes']); 
                    ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-save">Save Changes</button>
                    <a href="view_job.php?id=<?php echo $job_id; ?>" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </section>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> FreelanceHub. All rights reserved.</p>
    </footer>
</body>
</html>