<?php
session_start();
require_once 'config.php';


$jobs_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $jobs_per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where = [];
$params = [];
$types = '';

if(!empty($search)) {
    $where[] = "(job_title LIKE ? OR skills LIKE ? OR description LIKE ?)";
    $search_term = "%$search%";
    array_push($params, $search_term, $search_term, $search_term);
    $types .= 'sss';
}

if(!empty($status_filter)) {
    $where[] = "status = ?";
    $params[] = ucfirst($status_filter);
    $types .= 's';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$count_query = "SELECT COUNT(*) as total FROM jobs $where_clause";
$stmt = $conn->prepare($count_query);
if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_jobs = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_jobs / $jobs_per_page);

$query = "SELECT jobs.*, users.name AS client_name 
          FROM jobs 
          JOIN users ON jobs.client_id = users.id 
          $where_clause
          ORDER BY posted_date DESC 
          LIMIT ?, ?";

array_push($params, $offset, $jobs_per_page);
$types .= 'ii';

$stmt = $conn->prepare($query);
if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$status_counts = [
    'all' => 0,
    'open' => 0,
    'in-progress' => 0,
    'completed' => 0
];

$count_query = "SELECT status, COUNT(*) as count FROM jobs GROUP BY status";
$count_result = $conn->query($count_query);
while($row = $count_result->fetch_assoc()) {
    $status_key = strtolower(str_replace(' ', '-', $row['status']));
    if (array_key_exists($status_key, $status_counts)) {
        $status_counts[$status_key] = $row['count'];
    }
}
$status_counts['all'] = array_sum(array_slice($status_counts, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Jobs | FreelanceHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .job-card {
            position: relative;
            border-left: 4px solid;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .job-card.open {
            border-left-color: #2ecc71;
        }
        
        .job-card.in-progress {
            border-left-color: #f39c12;
        }
        
        .job-card.completed {
            border-left-color: #e74c3c;
        }
        
        .job-status {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-open {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-in-progress {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .job-meta {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .meta-label {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 0.25rem;
        }
        
        .meta-value {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .search-section {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            padding: 3rem 1rem;
            border-radius: 0 0 20px 20px;
            margin-bottom: 2rem;
        }
        
        .search-section h1 {
            color: white;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .search-form {
            display: flex;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .search-form input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: none;
            border-radius: 5px 0 0 5px;
            font-size: 1rem;
        }
        
        .search-form button {
            padding: 0 1.5rem;
            background-color: #f39c12;
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .search-form button:hover {
            background-color: #e67e22;
        }
        
        .filter-section {
            max-width: 1200px;
            margin: 0 auto 2rem;
            padding: 0 1rem;
        }
        
        .filter-section h2 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        
        .filter-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.5rem 1rem;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: #2c3e50;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .filter-count {
            background-color: #f8f9fa;
            color: #2c3e50;
            padding: 0.15rem 0.5rem;
            border-radius: 10px;
            font-size: 0.75rem;
        }
        
        .filter-btn:hover .filter-count, .filter-btn.active .filter-count {
            background-color: rgba(255,255,255,0.2);
            color: white;
        }
        
        .job-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-apply {
            background-color: #3498db;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-apply:hover {
            background-color: #2980b9;
        }
        
        .btn-secondary {
            background-color: white;
            color: #3498db;
            border: 1px solid #3498db;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background-color: #f8f9fa;
        }
        
        .no-jobs {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .no-jobs p {
            margin-bottom: 1.5rem;
            color: #555;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }
        
        .page-link {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            background-color: white;
            color: #3498db;
            border: 1px solid #ddd;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background-color: #f8f9fa;
            border-color: #3498db;
        }
        
        .page-link.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        @media (max-width: 768px) {
            .job-meta {
                grid-template-columns: 1fr;
            }
            
            .search-section {
                padding: 2rem 1rem;
            }
            
            .search-section h1 {
                font-size: 1.8rem;
            }
            
            .job-actions {
                flex-direction: column;
            }
            
            .job-actions a {
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
                <li><a href="browse_job.php" class="active">Browse Jobs</a></li>
                <li><a href="about.php">About</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] == 'client'): ?>
                        <li><a href="post_job.php">Post a Job</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $_SESSION['role'] == 'freelancer' ? 'freelancer_profile.php' : 'client_profile.php'; ?>">My Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="signup.php">Signup</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="browse-jobs-container">
        <section class="search-section">
            <h1>Find Your Next Opportunity</h1>
            <form class="search-form" method="GET" action="browse_job.php">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search for jobs by keyword, skill, or location...">
                <button type="submit">Search</button>
                <?php if(!empty($status_filter)): ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <?php endif; ?>
            </form>
        </section>

        <section class="filter-section">
            <h2>Filter by Status</h2>
            <div class="filter-options">
                <a href="browse_job.php?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>" 
                   class="filter-btn <?php echo empty($status_filter) ? 'active' : ''; ?>">
                    All Jobs
                    <span class="filter-count"><?php echo isset($status_counts['all']) ? $status_counts['all'] : 0; ?></span>
                </a>
                <a href="browse_job.php?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>status=open" 
                   class="filter-btn <?php echo $status_filter == 'open' ? 'active' : ''; ?>">
                    Open
                    <span class="filter-count"><?php echo isset($status_counts['open']) ? $status_counts['open'] : 0; ?></span>
                </a>
                <a href="browse_job.php?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>status=in-progress" 
                   class="filter-btn <?php echo $status_filter == 'in-progress' ? 'active' : ''; ?>">
                    In Progress
                    <span class="filter-count"><?php echo isset($status_counts['in-progress']) ? $status_counts['in-progress'] : 0; ?></span>
                </a>
                <a href="browse_job.php?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>status=completed" 
                   class="filter-btn <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                    Completed
                    <span class="filter-count"><?php echo isset($status_counts['completed']) ? $status_counts['completed'] : 0; ?></span>
                </a>
            </div>
        </section>

        <section class="jobs-list">
            <?php if($result->num_rows > 0): ?>
                <?php while($job = $result->fetch_assoc()): ?>
                    <article class="job-card <?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>">
                        <span class="job-status status-<?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>">
                            <?php echo htmlspecialchars($job['status']); ?>
                        </span>
                        
                        <div class="job-header">
                            <h2><?php echo htmlspecialchars($job['job_title']); ?></h2>
                            <span class="job-type"><?php echo htmlspecialchars($job['job_type']); ?></span>
                        </div>
                        <div class="job-details">
                            <p class="job-description"><?php 
                                $description = htmlspecialchars($job['description']);
                                echo strlen($description) > 150 ? substr($description, 0, 150) . '...' : $description; 
                            ?></p>
                            <div class="job-meta">
                                <div class="meta-item">
                                    <span class="meta-label">Skills:</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($job['skills']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Budget:</span>
                                    <span class="meta-value">$<?php echo number_format($job['budget'], 2); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Deadline:</span>
                                    <span class="meta-value"><?php echo date('M j, Y', strtotime($job['deadline'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Posted by:</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($job['client_name']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="job-actions">
                            <a href="view_job.php?id=<?php echo $job['id']; ?>" class="btn-apply">View Details</a>
                            <?php if((!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] == 'freelancer')) && $job['status'] == 'Open'): ?>
                                <a href="apply_job.php?job_id=<?php echo $job['id']; ?>" class="btn-secondary">Apply Now</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-jobs">
                    <p>No jobs found matching your criteria.</p>
                    <a href="browse_job.php" class="btn-secondary">Clear Filters</a>
                </div>
            <?php endif; ?>
        </section>

        <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($current_page > 1): ?>
                    <a href="browse_job.php?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" 
                       class="page-link">← Previous</a>
                <?php endif; ?>

                <?php 
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                for($i = $start_page; $i <= $end_page; $i++): 
                ?>
                    <a href="browse_job.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" 
                       class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if($current_page < $total_pages): ?>
                    <a href="browse_job.php?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" 
                       class="page-link">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> FreelanceHub. All rights reserved.</p>
    </footer>
</body>
</html>