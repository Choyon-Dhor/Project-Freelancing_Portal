<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreelanceHub - Find & Offer Freelance Services</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="index.php">FreelanceHub</a>
            </div>
            <ul class="nav-links">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="browse_job.php">Browse Jobs</a></li>
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

    <main>
        <section class="hero">
            <h1>Welcome to FreelanceHub!</h1>
            <p class="tagline">Connect with top talent or find your next freelance opportunity</p>
            <div class="cta-buttons">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="signup.php" class="btn-primary">Join Now</a>
                <?php endif; ?>
                <a href="browse_job.php" class="btn-secondary">Browse Jobs</a>
            </div>
        </section>
    </main>
</body>
</html>