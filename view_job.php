<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id'])) {
    header("Location: browse_jobs.php");
    exit();
}

$job_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT j.*, u.name as client_name, u.company_name 
                       FROM jobs j 
                       JOIN users u ON j.client_id = u.id 
                       WHERE j.id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    header("Location: browse_jobs.php");
    exit();
}

$stmt = $conn->prepare("SELECT COUNT(*) as application_count FROM applications WHERE job_id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$application_count = $stmt->get_result()->fetch_assoc()['application_count'];

$applications = [];
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $job['client_id']) {
    $stmt = $conn->prepare("SELECT a.*, u.name as freelancer_name, u.email as freelancer_email
                           FROM applications a
                           JOIN users u ON a.freelancer_id = u.id
                           WHERE a.job_id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['job_title']); ?> | FreelanceHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .job-view-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .job-details {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 1.5rem 0;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .meta-item {
            font-size: 0.9rem;
            color: #555;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-badge.open {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-badge.in-progress {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-badge.completed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .job-section {
            margin-bottom: 2rem;
        }
        
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .skill-tag {
            background-color: #e8f4fc;
            color: #3498db;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        /* Job Actions */
        .job-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-edit {
            background-color: #f39c12;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-edit:hover {
            background-color: #e67e22;
        }
        
        .btn-apply {
            background-color: #2ecc71;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-apply:hover {
            background-color: #27ae60;
        }
        
        /* Applications Section */
        .applications-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .applications-container {
            display: grid;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .application-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .application-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .application-status.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .application-status.accepted {
            background-color: #d4edda;
            color: #155724;
        }
        
        .application-status.rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Action Buttons */
        .application-actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .action-form {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-accept, .btn-reject, .btn-message {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-accept {
            background-color: #2ecc71;
            color: white;
            border: none;
        }
        
        .btn-accept:hover {
            background-color: #27ae60;
        }
        
        .btn-reject {
            background-color: #e74c3c;
            color: white;
            border: none;
        }
        
        .btn-reject:hover {
            background-color: #c0392b;
        }
        
        .btn-message {
            background-color: #3498db;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.5rem 1rem;
        }
        
        .btn-message:hover {
            background-color: #2980b9;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .job-meta {
                flex-direction: column;
            }
            
            .job-details, .applications-list {
                padding: 1.5rem;
            }
            
            .action-form {
                flex-direction: column;
            }
            
            .job-actions {
                flex-direction: column;
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
                <li><a href="browse_jobs.php">Browse Jobs</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] == 'client'): ?>
                        <li><a href="post_job.php">Post a Job</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo ($_SESSION['role'] == 'freelancer') ? 'freelancer_profile.php' : 'client_profile.php'; ?>">My Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="signup.php">Signup</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="job-view-container">
        <?php if (!empty($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <section class="job-details">
            <h1><?php echo htmlspecialchars($job['job_title']); ?></h1>
            <div class="job-meta">
                <span class="meta-item">
                    <strong>Posted by:</strong> <?php echo htmlspecialchars($job['client_name']); ?>
                    <?php if (!empty($job['company_name'])): ?>
                        (<?php echo htmlspecialchars($job['company_name']); ?>)
                    <?php endif; ?>
                </span>
                <span class="meta-item">
                    <strong>Job Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?>
                </span>
                <span class="meta-item">
                    <strong>Posted on:</strong> <?php echo date('M j, Y', strtotime($job['posted_date'])); ?>
                </span>
                <span class="meta-item">
                    <strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($job['deadline'])); ?>
                </span>
                <span class="meta-item">
                    <strong>Budget:</strong> $<?php echo number_format($job['budget'], 2); ?>
                </span>
                <span class="meta-item">
                    <strong>Applications:</strong> <?php echo $application_count; ?>
                </span>
                <span class="meta-item status-badge <?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>">
                    <?php echo htmlspecialchars($job['status']); ?>
                </span>
            </div>

            <div class="job-section">
                <h2>Job Description</h2>
                <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
            </div>

            <div class="job-section">
                <h2>Skills Required</h2>
                <div class="skills-list">
                    <?php 
                    $skills = explode(',', $job['skills']);
                    foreach ($skills as $skill): 
                    ?>
                        <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($job['notes'])): ?>
                <div class="job-section">
                    <h2>Additional Notes</h2>
                    <p><?php echo nl2br(htmlspecialchars($job['notes'])); ?></p>
                </div>
            <?php endif; ?>

            <div class="job-actions">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $job['client_id'] && $_SESSION['role'] == 'client'): ?>
                    <a href="edit_job.php?id=<?php echo $job_id; ?>" class="btn-edit">Edit Job</a>
                <?php endif; ?>
                
                <?php if ((!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] == 'freelancer')) && $job['status'] == 'Open'): ?>
                    <a href="apply_job.php?job_id=<?php echo $job_id; ?>" class="btn-apply">Apply Now</a>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!empty($applications)): ?>
            <section class="applications-list">
                <h2>Applications (<?php echo count($applications); ?>)</h2>
                <div class="applications-container">
                    <?php foreach ($applications as $application): ?>
                        <div class="application-card">
                            <div class="application-header">
                                <h3>
                                    <a href="freelancer_profile.php?id=<?php echo $application['freelancer_id']; ?>">
                                        <?php echo htmlspecialchars($application['freelancer_name']); ?>
                                    </a>
                                </h3>
                                <span class="application-status <?php echo $application['status']; ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                            </div>
                            
                            <div class="application-meta">
                                <span><strong>Email:</strong> <?php echo htmlspecialchars($application['freelancer_email']); ?></span>
                                <span><strong>Applied on:</strong> <?php echo date('M j, Y', strtotime($application['submitted_at'])); ?></span>
                            </div>
                            
                            <div class="application-proposal">
                                <h4>Proposal:</h4>
                                <p><?php echo nl2br(htmlspecialchars($application['proposal'])); ?></p>
                            </div>
                            
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $job['client_id']): ?>
                                <div class="application-actions">
                                    <?php if ($application['status'] == 'pending'): ?>
                                        <form action="process_application.php" method="POST" class="action-form">
                                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                                            
                                            <button type="submit" name="action" value="accept" class="btn-accept">
                                                Accept Proposal
                                            </button>
                                            
                                            <button type="submit" name="action" value="reject" class="btn-reject">
                                                Reject
                                            </button>
                                        </form>
                                    <?php elseif ($application['status'] == 'accepted'): ?>
                                        <div class="application-status-message">
                                            <p>✅ You've accepted this proposal</p>
                                            <a href="messaging.php?user_id=<?php echo $application['freelancer_id']; ?>" class="btn-message">
                                                Message Freelancer
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $job['client_id']): ?>
            <div class="no-applications">
                <p>No applications received yet.</p>
            </div>
        <?php endif; ?>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> FreelanceHub. All rights reserved.</p>
    </footer>
</body>
</html>