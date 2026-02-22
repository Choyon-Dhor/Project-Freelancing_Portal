<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About FreelanceHub | Connecting Talent with Opportunity</title>
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
                <li><a href="about.php" class="active">About</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo $_SESSION['role'] == 'freelancer' ? 'freelancer_profile.php' : 'client_profile.php'; ?>">My Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="signup.php">Signup</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="about-container">
        <section class="about-hero">
            <h1>About FreelanceHub</h1>
            <p class="tagline">Bridging the gap between talented freelancers and businesses worldwide</p>
        </section>

        <section class="about-section">
            <div class="about-content">
                <h2>Our Mission</h2>
                <p>FreelanceHub was founded with a simple goal: to create a seamless platform where businesses can find top-tier freelance talent, and skilled professionals can discover rewarding opportunities that match their expertise.</p>
                
                <div class="mission-stats">
                    <div class="stat-item">
                        <span class="stat-number">10,000+</span>
                        <span class="stat-label">Successful Projects</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">5,000+</span>
                        <span class="stat-label">Registered Freelancers</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">2,000+</span>
                        <span class="stat-label">Satisfied Clients</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="about-section alt-bg">
            <div class="about-content">
                <h2>How It Works</h2>
                <div class="work-steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h3>For Clients</h3>
                        <p>Post your project requirements, review freelancer proposals, and hire the perfect match for your needs.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>For Freelancers</h3>
                        <p>Create a profile showcasing your skills, browse available jobs, and submit competitive proposals.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Collaborate</h3>
                        <p>Use our platform to communicate, share files, track progress, and ensure successful project completion.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="about-section">
            <div class="about-content">
                <h2>Why Choose FreelanceHub?</h2>
                <div class="features-grid">
                    <div class="feature">
                        <div class="feature-icon">🔒</div>
                        <h3>Secure Payments</h3>
                        <p>Our escrow system ensures freelancers get paid and clients get quality work.</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">⭐</div>
                        <h3>Quality Talent</h3>
                        <p>Rigorous vetting process to ensure you work with top professionals.</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">🌐</div>
                        <h3>Global Reach</h3>
                        <p>Connect with clients or freelancers from around the world.</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">📈</div>
                        <h3>Growth Tools</h3>
                        <p>Resources and insights to help freelancers and businesses grow.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="about-section alt-bg">
            <div class="about-content">
                <h2>Our Team</h2>
                <p>FreelanceHub was created by a team of passionate developers, designers, and business professionals who understand the challenges of freelancing and remote work. We're committed to continuously improving our platform to better serve our community.</p>
                <a href="contact.php" class="btn-primary">Contact Us</a>
            </div>
        </section>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> FreelanceHub. All rights reserved.</p>
    </footer>
</body>
</html>