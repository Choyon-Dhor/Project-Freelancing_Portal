<?php
session_start();
require_once 'config.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: login.php");
    exit();
}

$freelancer_id = $_SESSION['user_id'];

$current_projects = [];
$stmt = $conn->prepare("SELECT a.*, j.job_title, j.description, j.budget, j.deadline, j.status as job_status,
                        u.name as client_name, u.company_name, u.id as client_id
                        FROM applications a
                        JOIN jobs j ON a.job_id = j.id
                        JOIN users u ON j.client_id = u.id
                        WHERE a.freelancer_id = ? 
                        AND (a.status = 'accepted' OR a.status = 'in_progress')
                        ORDER BY a.submitted_at DESC");
$stmt->bind_param("i", $freelancer_id);
$stmt->execute();
$current_projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$completed_projects = [];
$stmt = $conn->prepare("SELECT a.*, j.job_title, j.description, j.budget, 
                        u.name as client_name, u.company_name, u.id as client_id
                        FROM applications a
                        JOIN jobs j ON a.job_id = j.id
                        JOIN users u ON j.client_id = u.id
                        WHERE a.freelancer_id = ? AND a.status = 'completed'
                        ORDER BY a.submitted_at DESC");
$stmt->bind_param("i", $freelancer_id);
$stmt->execute();
$completed_projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_earnings = array_reduce($completed_projects, function($carry, $project) {
    return $carry + ($project['bid_amount'] ?? 0);
}, 0);
$total_projects = count($completed_projects) + count($current_projects);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelancer Dashboard | FreelanceHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1rem;
            color: #7f8c8d;
        }
        
        .projects-section {
            margin-bottom: 3rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .projects-grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .project-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
        }
        
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .project-title {
            font-size: 1.25rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .project-client {
            color: #7f8c8d;
            margin-bottom: 0.5rem;
        }
        
        .project-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .meta-item {
            font-size: 0.9rem;
        }
        
        .meta-label {
            color: #7f8c8d;
        }
        
        .project-actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-secondary {
            background-color: white;
            color: #3498db;
            border: 1px solid #3498db;
        }
        
        .btn-secondary:hover {
            background-color: #f8f9fa;
        }
        
        .btn-complete {
            background-color: #2ecc71;
            color: white;
        }
        
        .btn-complete:hover {
            background-color: #27ae60;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-badge.current {
            background-color: #e8f4fc;
            color: #3498db;
        }
        
        .status-badge.completed {
            background-color: #d4edda;
            color: #155724;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state p {
            color: #7f8c8d;
            margin-bottom: 1.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .project-header {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .project-actions {
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
                <li><a href="browse_job.php">Browse Jobs</a></li>
                <li><a href="freelancer_dashboard.php" class="active">Dashboard</a></li>
                <li><a href="freelancer_profile.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="dashboard-header">
            <h1>Freelancer Dashboard</h1>
            <a href="freelancer_profile.php" class="btn-primary">View Profile</a>
        </div>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($current_projects); ?></div>
                <div class="stat-label">Active Projects</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($completed_projects); ?></div>
                <div class="stat-label">Completed Projects</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?php echo number_format($total_earnings, 2); ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_projects; ?></div>
                <div class="stat-label">Total Projects</div>
            </div>
        </div>
        
        <section class="projects-section">
            <div class="section-header">
                <h2>Current Projects</h2>
            </div>
            
            <?php if (!empty($current_projects)): ?>
                <div class="projects-grid">
                    <?php foreach ($current_projects as $project): ?>
                        <div class="project-card">
                            <div class="project-header">
                                <div>
                                    <h3 class="project-title"><?php echo htmlspecialchars($project['job_title']); ?></h3>
                                    <p class="project-client">
                                        Client: <?php echo htmlspecialchars($project['client_name']); ?>
                                        <?php if (!empty($project['company_name'])): ?>
                                            (<?php echo htmlspecialchars($project['company_name']); ?>)
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <span class="status-badge <?php echo $project['job_status'] === 'in_progress' ? 'current' : 'accepted'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $project['job_status'])); ?>
                                </span>
                            </div>
                            
                            <p><?php echo nl2br(htmlspecialchars(substr($project['description'], 0, 200) . '...')); ?></p>
                            
                            <div class="project-meta">
                                <div class="meta-item">
                                    <span class="meta-label">Bid Amount:</span>
                                    <span>$<?php echo number_format($project['bid_amount'] ?? 0, 2); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Deadline:</span>
                                    <span><?php echo date('M j, Y', strtotime($project['deadline'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Status:</span>
                                    <span><?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="project-actions">
                                <a href="messaging.php?user_id=<?php echo $project['client_id']; ?>" class="btn-primary">Message Client</a>
                                <form action="complete_project.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="application_id" value="<?php echo $project['id']; ?>">
                                    <input type="hidden" name="job_id" value="<?php echo $project['job_id']; ?>">
                                    <button type="submit" class="btn-complete" onclick="return confirm('Are you sure you want to mark this project as complete?')">
                                        Mark as Completed
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>You don't have any active projects at the moment.</p>
                    <a href="browse_job.php" class="btn-primary">Browse Jobs</a>
                </div>
            <?php endif; ?>
        </section>
        
        <section class="projects-section">
            <div class="section-header">
                <h2>Completed Projects</h2>
            </div>
            
            <?php if (!empty($completed_projects)): ?>
                <div class="projects-grid">
                    <?php foreach ($completed_projects as $project): ?>
                        <div class="project-card">
                            <div class="project-header">
                                <div>
                                    <h3 class="project-title"><?php echo htmlspecialchars($project['job_title']); ?></h3>
                                    <p class="project-client">
                                        Client: <?php echo htmlspecialchars($project['client_name']); ?>
                                        <?php if (!empty($project['company_name'])): ?>
                                            (<?php echo htmlspecialchars($project['company_name']); ?>)
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <span class="status-badge completed">Completed</span>
                            </div>
                            
                            <p><?php echo nl2br(htmlspecialchars(substr($project['description'], 0, 200) . '...')); ?></p>
                            
                            <div class="project-meta">
                                <div class="meta-item">
                                    <span class="meta-label">Earned:</span>
                                    <span>$<?php echo number_format($project['bid_amount'] ?? 0, 2); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Completed:</span>
                                    <span><?php echo date('M j, Y', strtotime($project['submitted_at'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="project-actions">
                                <a href="view_job.php?id=<?php echo $project['job_id']; ?>" class="btn-secondary">View Job</a>
                                <a href="messaging.php?user_id=<?php echo $project['client_id']; ?>" class="btn-primary">Message Client</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>Your completed projects will appear here.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> FreelanceHub. All rights reserved.</p>
    </footer>
</body>
</html>