<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

if (!isset($_POST['application_id']) || !isset($_POST['job_id']) || !isset($_POST['action'])) {
    $_SESSION['error'] = "Invalid request parameters";
    header("Location: client_profile.php");
    exit();
}

$application_id = (int)$_POST['application_id'];
$job_id = (int)$_POST['job_id'];
$action = $_POST['action'];
$client_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, status FROM jobs WHERE id = ? AND client_id = ?");
$stmt->bind_param("ii", $job_id, $client_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    $_SESSION['error'] = "Job not found or you don't have permission";
    header("Location: client_profile.php");
    exit();
}

if ($job['status'] !== 'Open') {
    $_SESSION['error'] = "This job is no longer accepting applications";
    header("Location: view_job.php?id=$job_id");
    exit();
}

$stmt = $conn->prepare("SELECT id, freelancer_id FROM applications WHERE id = ? AND job_id = ?");
$stmt->bind_param("ii", $application_id, $job_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();

if (!$application) {
    $_SESSION['error'] = "Application not found for this job";
    header("Location: view_job.php?id=$job_id");
    exit();
}

$conn->begin_transaction();

try {
    $new_status = ($action == 'accept') ? 'accepted' : 'rejected';
    
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $application_id);
    $stmt->execute();
    if ($action == 'accept') {
        $stmt = $conn->prepare("UPDATE jobs SET status = 'In Progress' WHERE id = ?");
        $stmt->bind_param("i", $job_id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE applications SET status = 'rejected' 
                              WHERE job_id = ? AND id != ? AND status = 'pending'");
        $stmt->bind_param("ii", $job_id, $application_id);
        $stmt->execute();

        $table_check = $conn->query("SHOW TABLES LIKE 'projects'");
        if ($table_check->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO projects (job_id, freelancer_id, client_id, start_date, status) 
                                  VALUES (?, ?, ?, NOW(), 'In Progress')");
            $stmt->bind_param("iii", $job_id, $application['freelancer_id'], $client_id);
            $stmt->execute();
        }
    }

    $conn->commit();

    $_SESSION['success'] = "Application has been " . $new_status . " successfully!";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error processing application: " . $e->getMessage();
}

header("Location: view_job.php?id=$job_id");
exit();
?>