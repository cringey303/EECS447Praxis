<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

set_current_user_from_request();
$currentUserId = (int) current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed.');
}

$projectId = (int) (filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT) ?? 0);
$returnTo = (string) (filter_input(INPUT_POST, 'return_to', FILTER_UNSAFE_RAW) ?? 'index.php');

// Validate project exists
$project = db_fetch_one('SELECT id FROM Projects WHERE id = :id', ['id' => $projectId]);
if (!$project) {
    http_response_code(404);
    die('Project not found.');
}

// Check if already a member
$isAlreadyMember = db_fetch_one(
    'SELECT 1 FROM User_Project WHERE user_id = :user_id AND project_id = :project_id',
    ['user_id' => $currentUserId, 'project_id' => $projectId]
);
if ($isAlreadyMember) {
    header('Location: ' . $returnTo);
    exit;
}

// Create or update join request
try {
    db_execute(
        <<<SQL
        INSERT INTO Project_Join_Requests (user_id, project_id, status)
        VALUES (:user_id, :project_id, 'pending')
        ON DUPLICATE KEY UPDATE status = 'pending', requested_at = CURRENT_TIMESTAMP
        SQL,
        ['user_id' => $currentUserId, 'project_id' => $projectId]
    );
} catch (Exception $e) {
    error_log('Error creating join request: ' . $e->getMessage());
    http_response_code(500);
    die('Error creating join request.');
}

header('Location: ' . $returnTo);
