<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

set_current_user_from_request();
$currentUser = current_user();
$currentUserId = (int) $currentUser['id'];
$allUsers = db_fetch_all('SELECT id, handle, display_name, major FROM Users ORDER BY display_name ASC');

$pendingRequestCount = (int) db_fetch_one(
    'SELECT COUNT(*) AS cnt FROM Project_Join_Requests pjr
     JOIN Projects p ON p.id = pjr.project_id
     WHERE p.owner_user_id = :owner_id AND pjr.status = :status',
    ['owner_id' => $currentUserId, 'status' => 'pending']
)['cnt'];

$activeTab = $activeTab ?? 'feed';
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
        <a class="nav-button <?php echo $activeTab === 'feed' ? 'active' : ''; ?>" href="index.php?tab=feed">Feed</a>
        <a class="nav-button <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" href="index.php?tab=profile">Profile</a>
        <a class="nav-button <?php echo $activeTab === 'requests' ? 'active' : ''; ?>" href="index.php?tab=requests">Requests<?php echo $pendingRequestCount > 0 ? ' (' . h((string) $pendingRequestCount) . ')' : ''; ?></a>
        <form class="nav-switcher" method="get" action="index.php" id="user-switch-form">
            <input type="hidden" name="switch_user" value="1">
            <input type="hidden" name="tab" id="switch-tab" value="<?php echo h($activeTab); ?>">
            <label for="user_id">Switch user</label>
            <select id="user_id" name="user_id">
                <?php foreach ($allUsers as $user): ?>
                    <option value="<?php echo h((string) $user['id']); ?>" <?php echo (int) $user['id'] === $currentUserId ? 'selected' : ''; ?>>
                        <?php echo h($user['display_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </nav>
    <main class="content">
<script>
(() => {
    const switchForm = document.getElementById('user-switch-form');
    const switchUser = document.getElementById('user_id');
    if (!switchForm || !switchUser) {
        return;
    }

    switchUser.addEventListener('change', () => {
        switchForm.submit();
    });
})();
</script>
