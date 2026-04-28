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
        <header class="masthead">
            <div>
                <p class="eyebrow">Praxis</p>
                <h1><?php echo h($pageTitle); ?></h1>
            </div>
            <div class="session-card">
                <p class="session-label">SESSION USER</p>
                <p class="session-name"><?php echo h($currentUser['display_name']); ?></p>
                <p class="session-meta">@<?php echo h($currentUser['handle']); ?> • <?php echo h($currentUser['major']); ?></p>
                <form class="user-switcher" method="get">
                    <label for="user_id">Switch user</label>
                    <select id="user_id" name="user_id" onchange="this.form.submit()">
                        <?php foreach ($allUsers as $user): ?>
                            <option value="<?php echo h((string) $user['id']); ?>" <?php echo (int) $user['id'] === (int) $currentUser['id'] ? 'selected' : ''; ?>>
                                <?php echo h($user['display_name']); ?> (@<?php echo h($user['handle']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button type="submit">Set user</button></noscript>
                </form>
            </div>
        </header>
        <nav class="topbar">
            <a href="index.php">Feed</a>
            <a href="profile.php">Profile</a>
        </nav>
        <main class="content">
