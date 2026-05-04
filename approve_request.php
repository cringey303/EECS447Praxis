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
$returnTo = (string) (filter_input(INPUT_POST, 'return_to', FILTER_UNSAFE_RAW) ?? 'index.php?tab=requests');

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

// Approve the request and add the requester to the project immediately.
$pdo = db();

try {
    $pdo->beginTransaction();

    db_execute(
        'UPDATE Project_Join_Requests SET status = :status, reviewed_at = CURRENT_TIMESTAMP WHERE id = :id',
        ['id' => $requestId, 'status' => 'approved']
    );

    db_execute(
        'INSERT INTO User_Project (user_id, project_id, role) VALUES (:user_id, :project_id, :role) ON DUPLICATE KEY UPDATE role = VALUES(role)',
        [
            'user_id' => $joinRequest['user_id'],
            'project_id' => $joinRequest['project_id'],
            'role' => 'Member',
        ]
    );

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Error approving join request: ' . $exception->getMessage());
    http_response_code(500);
    die('Error approving join request.');
}

header('Location: ' . $returnTo);
