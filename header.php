<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

set_current_user_from_request();
$currentUser = current_user();
$allUsers = db_fetch_all('SELECT id, handle, display_name, major FROM Users ORDER BY display_name ASC');
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
            <a class="nav-button <?php echo $pageTitle === 'Session Profile' ? 'active' : ''; ?>" href="profile.php">Profile</a>
            <form class="nav-switcher" method="get">
                <label for="user_id">Switch user</label>
                <select id="user_id" name="user_id">
                    <?php foreach ($allUsers as $user): ?>
                        <option value="<?php echo h((string) $user['id']); ?>" <?php echo (int) $user['id'] === (int) $currentUser['id'] ? 'selected' : ''; ?>>
                            <?php echo h($user['display_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Switch User</button>
            </form>
        </nav>
        <main class="content">
