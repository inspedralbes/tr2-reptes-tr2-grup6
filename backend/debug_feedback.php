<?php
require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';

$db = Database::getInstance();

$assignmentId = 1;

echo "Checking Assignment ID: $assignmentId\n";

// Check Assignment
$stmt = $db->prepare("SELECT * FROM workshop_assignments WHERE id = ?");
$stmt->execute([$assignmentId]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if ($assignment) {
    echo "Assignment Found:\n";
    print_r($assignment);
} else {
    echo "Assignment NOT FOUND.\n";
}

// Check Existing Feedback
$stmt = $db->prepare("SELECT * FROM workshop_feedback WHERE assignment_id = ?");
$stmt->execute([$assignmentId]);
$feedback = $stmt->fetch(PDO::FETCH_ASSOC);

if ($feedback) {
    echo "Feedback Already Exists for this Assignment:\n";
    print_r($feedback);
} else {
    echo "No existing feedback for this assignment.\n";
}
