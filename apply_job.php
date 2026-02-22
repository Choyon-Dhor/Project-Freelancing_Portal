<?php
session_start();
require_once 'config.php';

// Check if job ID is provided
if (!isset($_GET['job_id'])) {
    header("Location: browse_jobs.php");
    exit();
}

$job_id = (int)$_GET['job_id'];

// Redirect if not logged in as freelancer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    $_SESSION['redirect_url'] = "apply_job.php?job_id=" . $job_id;
    header("Location: login.php");
    exit();
}

$freelancer_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Check if columns exist in the database
$columns_exist = true;
$result = $conn->query("SHOW COLUMNS FROM applications LIKE 'bid_amount'");
if ($result->num_rows == 0) {
    $columns_exist = false;
}

// Fetch job details
$stmt = $conn->prepare("SELECT job_title FROM jobs WHERE id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    header("Location: browse_jobs.php");
    exit();
}

// Check for existing application
$stmt = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND freelancer_id = ?");
$stmt->bind_param("ii", $job_id, $freelancer_id);
$stmt->execute();
$existing_application = $stmt->get_result()->fetch_assoc();

if ($existing_application) {
    $errors['application'] = "You have already applied for this job.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($errors)) {
    $proposal = trim($conn->real_escape_string($_POST['proposal']));
    
    if ($columns_exist) {
        $bid_amount = (float)$_POST['bid_amount'];
        $estimated_time = trim($conn->real_escape_string($_POST['estimated_time']));
    }

    // Validate inputs
    if (empty($proposal)) {
        $errors['proposal'] = "Proposal is required";
    }

    if ($columns_exist) {
        if ($bid_amount <= 0) {
            $errors['bid_amount'] = "Bid amount must be positive";
        }
        if (empty($estimated_time)) {
            $errors['estimated_time'] = "Estimated time is required";
        }
    }

    if (empty($errors)) {
        if ($columns_exist) {
            $stmt = $conn->prepare("INSERT INTO applications (job_id, freelancer_id, proposal, bid_amount, estimated_time) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisds", $job_id, $freelancer_id, $proposal, $bid_amount, $estimated_time);
        } else {
            $stmt = $conn->prepare("INSERT INTO applications (job_id, freelancer_id, proposal) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $job_id, $freelancer_id, $proposal);
        }
        
        if ($stmt->execute()) {
            $success = "Your application has been submitted successfully!";
        } else {
            $errors['database'] = "Error submitting application: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?php echo htmlspecialchars($job['job_title']); ?> | FreelanceHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .apply-job-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .apply-job-form {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .apply-job-form h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
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
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            gap: 1.5rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .input-with-symbol {
            position: relative;
        }
        
        .input-with-symbol .symbol {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .input-with-symbol input {
            padding-left: 2rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-secondary {
            background-color: white;
            color: #3498db;
            border: 1px solid #3498db;
            padding: 1rem 2rem;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-secondary:hover {
            background-color: #f8f9fa;
        }
        
        .error-text {
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            display: block;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-primary, .btn-secondary {
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
                <li><a href="freelancer_profile.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="apply-job-container">
        <section class="apply-job-form">
            <h1>Apply for: <?php echo htmlspecialchars($job['job_title']); ?></h1>
            
            <?php if (!empty($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
                <div class="form-actions">
                    <a href="browse_jobs.php" class="btn-primary">Browse More Jobs</a>
                    <a href="freelancer_profile.php" class="btn-secondary">View My Profile</a>
                </div>
            <?php else: ?>
                <?php if (isset($errors['application'])): ?>
                    <div class="alert error"><?php echo $errors['application']; ?></div>
                    <div class="form-actions">
                        <a href="browse_jobs.php" class="btn-primary">Browse More Jobs</a>
                        <a href="freelancer_profile.php" class="btn-secondary">View My Profile</a>
                    </div>
                <?php else: ?>
                    <?php if (isset($errors['database'])): ?>
                        <div class="alert error"><?php echo $errors['database']; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="proposal">Your Proposal*</label>
                            <textarea id="proposal" name="proposal" rows="8" required><?php echo htmlspecialchars($_POST['proposal'] ?? ''); ?></textarea>
                            <?php if (isset($errors['proposal'])): ?>
                                <span class="error-text"><?php echo $errors['proposal']; ?></span>
                            <?php endif; ?>
                            <small>Explain why you're the best fit for this job and how you would approach it.</small>
                        </div>
                        
                        <?php if ($columns_exist): ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bid_amount">Your Bid Amount (USD)*</label>
                                    <div class="input-with-symbol">
                                        <span class="symbol">$</span>
                                        <input type="number" id="bid_amount" name="bid_amount" min="0" step="0.01" 
                                               value="<?php echo htmlspecialchars($_POST['bid_amount'] ?? ''); ?>" required>
                                    </div>
                                    <?php if (isset($errors['bid_amount'])): ?>
                                        <span class="error-text"><?php echo $errors['bid_amount']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label for="estimated_time">Estimated Time*</label>
                                    <input type="text" id="estimated_time" name="estimated_time" 
                                           value="<?php echo htmlspecialchars($_POST['estimated_time'] ?? ''); ?>" 
                                           placeholder="e.g., 2 weeks, 1 month" required>
                                    <?php if (isset($errors['estimated_time'])): ?>
                                        <span class="error-text"><?php echo $errors['estimated_time']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Submit Application</button>
                            <a href="view_job.php?id=<?php echo $job_id; ?>" class="btn-secondary">Back to Job</a>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> FreelanceHub. All rights reserved.</p>
    </footer>
</body>
</html>