<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

set_current_user_from_request();
$currentUserId = (int) current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed.');
}

$requestId = (int) (filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT) ?? 0);

// Fetch the join request
$joinRequest = db_fetch_one(
    'SELECT user_id, project_id FROM Project_Join_Requests WHERE id = :id AND status = :status',
    ['id' => $requestId, 'status' => 'pending']
);
if (!$joinRequest) {
    http_response_code(404);
    die('Join request not found.');
}

// Verify current user owns the project
$project = db_fetch_one(
    'SELECT owner_user_id FROM Projects WHERE id = :id',
    ['id' => $joinRequest['project_id']]
);
if (!$project || (int) $project['owner_user_id'] !== $currentUserId) {
    http_response_code(403);
    die('Unauthorized.');
}

// Update request status to approved
db_execute(
    'UPDATE Project_Join_Requests SET status = :status, reviewed_at = CURRENT_TIMESTAMP WHERE id = :id',
    ['id' => $requestId, 'status' => 'approved']
);

header('Location: pending_requests.php');
