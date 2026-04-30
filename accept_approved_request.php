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

// Verify the request is approved
$joinRequest = db_fetch_one(
    'SELECT id FROM Project_Join_Requests WHERE user_id = :user_id AND project_id = :project_id AND status = :status',
    ['user_id' => $currentUserId, 'project_id' => $projectId, 'status' => 'approved']
);
if (!$joinRequest) {
    http_response_code(404);
    die('Approved join request not found.');
}

// Add user to project
db_execute(
    'INSERT INTO User_Project (user_id, project_id, role) VALUES (:user_id, :project_id, :role)',
    ['user_id' => $currentUserId, 'project_id' => $projectId, 'role' => 'Member']
);

header('Location: ' . $returnTo);
