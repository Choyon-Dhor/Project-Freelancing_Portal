<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client'){
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, email, company_name, location, bio FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

// Fixed query - removed 'category' column which doesn't exist in the database
$stmt = $conn->prepare("SELECT id, job_title, job_type, posted_date, status FROM jobs WHERE client_id = ? ORDER BY posted_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$jobs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Profile | FreelanceHub</title>
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
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="client-profile-container">
        <section class="profile-header">
            <h1>Client Profile</h1>
            <a href="edit_client_profile.php" class="btn-edit">Edit Profile</a>
        </section>

        <section class="profile-info">
            <div class="profile-card">
                <div class="profile-details">
                    <div class="detail-group">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($client['name']); ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Company:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($client['company_name'] ?? 'Not specified'); ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($client['email']); ?></span>
                    </div>
                    <div class="detail-group">
                        <span class="detail-label">Location:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($client['location'] ?? 'Not specified'); ?></span>
                    </div>
                </div>
                <div class="profile-bio">
                    <h3>About</h3>
                    <p><?php echo htmlspecialchars($client['bio'] ?? 'No bio information available'); ?></p>
                </div>
            </div>
        </section>

        <section class="posted-jobs">
            <h2>Posted Jobs</h2>
            <?php if($jobs->num_rows > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Job Type</th>
                                <th>Posted Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($job = $jobs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($job['job_title']); ?></td>
                                    <td><?php echo htmlspecialchars($job['job_type']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($job['posted_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($job['status']); ?></td>
                                    <td>
                                        <a href="view_job.php?id=<?php echo $job['id']; ?>" class="btn-small">View</a>
                                        <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="btn-small">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-jobs">You haven't posted any jobs yet. <a href="post_job.php">Post your first job</a></p>
            <?php endif; ?>
        </section>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> FreelanceHub. All rights reserved.</p>
    </footer>
</body>
</html>