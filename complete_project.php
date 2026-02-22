<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    $_SESSION['error'] = "You must be logged in as a freelancer to complete projects";
    header("Location: login.php");
    exit();
}

if(!isset($_POST['application_id']) || !isset($_POST['job_id'])) {
    $_SESSION['error'] = "Invalid request parameters";
    header("Location: freelancer_dashboard.php");
    exit();
}

$application_id = (int)$_POST['application_id'];
$job_id = (int)$_POST['job_id'];
$freelancer_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id FROM applications 
                       WHERE id = ? AND freelancer_id = ? AND job_id = ? 
                       AND (status = 'accepted' OR status = 'in_progress')");
$stmt->bind_param("iii", $application_id, $freelancer_id, $job_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    $_SESSION['error'] = "Project not found or not in a completable state";
    header("Location: freelancer_dashboard.php");
    exit();
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE applications SET status = 'completed' WHERE id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE jobs SET status = 'completed' WHERE id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();

    $conn->commit();
    
    $_SESSION['success'] = "Project marked as completed successfully!";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error completing project: " . $e->getMessage();
}

header("Location: freelancer_dashboard.php");
exit();
?>