<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

set_current_user_from_request();
$currentUser = current_user();
$currentUserId = (int) $currentUser['id'];
$allUsers = db_fetch_all('SELECT id, handle, display_name, major FROM Users ORDER BY display_name ASC');

// Check if current user has owned projects with pending requests
$hasPendingRequests = (int) db_fetch_one(
    'SELECT COUNT(*) as cnt FROM Project_Join_Requests pjr 
     JOIN Projects p ON p.id = pjr.project_id 
     WHERE p.owner_user_id = :owner_id AND pjr.status = :status',
    ['owner_id' => $currentUserId, 'status' => 'pending']
)['cnt'] > 0;

$pageTitle = $pageTitle ?? 'Praxis';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($pageTitle); ?> | Praxis</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-shell">
        <nav class="navbar" aria-label="Primary">
            <a class="nav-button <?php echo $pageTitle === 'Project Feed' ? 'active' : ''; ?>" href="index.php">Feed</a>
                <a class="nav-button <?php echo $pageTitle === 'User Profile' ? 'active' : ''; ?>" href="profile.php">Profile</a>
                <?php if ($hasPendingRequests): ?>
                    <a class="nav-button <?php echo $pageTitle === 'Join Requests' ? 'active' : ''; ?>" href="pending_requests.php">Join Requests</a>
                <?php endif; ?>
            <form class="nav-switcher" method="get">
                <label for="user_id">Switch user</label>
                <select id="user_id" name="user_id">
                    <?php foreach ($allUsers as $user): ?>
                            <option value="<?php echo h((string) $user['id']); ?>" <?php echo (int) $user['id'] === $currentUserId ? 'selected' : ''; ?>>
                            <?php echo h($user['display_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Switch User</button>
            </form>
        </nav>
        <main class="content">
