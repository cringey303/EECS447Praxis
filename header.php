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
            <form class="nav-switcher" method="get" id="user-switch-form">
                    <input type="hidden" name="switch_user" value="1">
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
    const userSelect = document.getElementById('user_id');

    if (!switchForm || !userSelect) {
        return;
    }

    userSelect.addEventListener('change', () => {
        switchForm.submit();
    });

    // Prefetch likely next pages so navigation feels instant.
    const prefetched = new Set();

    const prefetchUrl = (url) => {
        if (!url || prefetched.has(url)) {
            return;
        }

        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        link.as = 'document';
        document.head.appendChild(link);
        prefetched.add(url);
    };

    const navLinks = Array.from(document.querySelectorAll('.navbar a[href]'));

    const warmLikelyPages = () => {
        for (const navLink of navLinks) {
            prefetchUrl(navLink.getAttribute('href'));
        }

        // Warm a few profile pages commonly reached from feed/request lists.
        const profileLinks = Array.from(document.querySelectorAll('a[href^="profile.php?user_id="]')).slice(0, 8);
        for (const profileLink of profileLinks) {
            prefetchUrl(profileLink.getAttribute('href'));
        }
    };

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(warmLikelyPages, { timeout: 1200 });
    } else {
        window.setTimeout(warmLikelyPages, 350);
    }

    // On intent (hover/focus/touch), prefetch target page immediately.
    document.addEventListener('mouseover', (event) => {
        const anchor = event.target.closest('a[href]');
        if (!anchor) {
            return;
        }

        const href = anchor.getAttribute('href');
        if (href && !href.startsWith('http') && !href.startsWith('#')) {
            prefetchUrl(href);
        }
    });

    document.addEventListener('focusin', (event) => {
        const anchor = event.target.closest('a[href]');
        if (!anchor) {
            return;
        }

        const href = anchor.getAttribute('href');
        if (href && !href.startsWith('http') && !href.startsWith('#')) {
            prefetchUrl(href);
        }
    });
})();
</script>
