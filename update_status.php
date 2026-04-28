<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('POST only.');
}

$projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
if ($projectId === null || $projectId === false) {
    http_response_code(400);
    exit('Missing project_id.');
}

$currentUserId = current_user_id();
$project = db_fetch_one(
    'SELECT id, owner_user_id, status FROM Projects WHERE id = :id',
    ['id' => $projectId]
);

if ($project === null) {
    http_response_code(404);
    exit('Project not found.');
}

if ((int) $project['owner_user_id'] !== $currentUserId) {
    http_response_code(403);
    exit('Only the owner can toggle project status.');
}

$newStatus = $project['status'] === 'open' ? 'closed' : 'open';
db_execute(
    'UPDATE Projects SET status = :status WHERE id = :id',
    [
        'status' => $newStatus,
        'id' => $projectId,
    ]
);

$returnTo = 'index.php';
$incomingReturnTo = filter_input(INPUT_POST, 'return_to', FILTER_UNSAFE_RAW);
if (in_array($incomingReturnTo, ['index.php', 'profile.php'], true)) {
    $returnTo = $incomingReturnTo;
}

redirect_to($returnTo);
