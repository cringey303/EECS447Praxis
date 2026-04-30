<?php
declare(strict_types=1);

$pageTitle = 'User Profile';
require_once __DIR__ . '/header.php';

$currentUserId = (int) $currentUser['id'];

// Check if viewing a specific user's profile
$viewingUserId = (int) (filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT) ?? $currentUserId);
$isOwnProfile = $viewingUserId === $currentUserId;

// Fetch the user being viewed
$viewedUser = db_fetch_one('SELECT * FROM Users WHERE id = :user_id', ['user_id' => $viewingUserId]);
if (!$viewedUser) {
    http_response_code(404);
    die('User not found.');
}

$organizations = db_fetch_all(
    <<<SQL
    SELECT
        o.id,
        o.name,
        o.description,
        uo.role,
        uo.joined_at
    FROM User_Organization uo
    JOIN Organizations o ON o.id = uo.organization_id
    WHERE uo.user_id = :user_id
    ORDER BY o.name ASC
    SQL,
    ['user_id' => $viewingUserId]
);

$joinedProjects = db_fetch_all(
    <<<SQL
    SELECT
        p.id,
        p.title,
        p.summary,
        p.status,
        p.owner_user_id,
        up.role,
        up.joined_at,
        owner.display_name AS owner_name
    FROM User_Project up
    JOIN Projects p ON p.id = up.project_id
    JOIN Users owner ON owner.id = p.owner_user_id
    WHERE up.user_id = :user_id
    ORDER BY p.created_at DESC, p.id DESC
    SQL,
    ['user_id' => $viewingUserId]
);
?>

<section class="panel">
    <p class="section-label"><?php echo $isOwnProfile ? 'YOUR PROFILE' : 'USER PROFILE'; ?></p>
    <h2><?php echo h($viewedUser['display_name']); ?></h2>
    <p class="muted">@<?php echo h($viewedUser['handle']); ?> • <?php echo h($viewedUser['email']); ?></p>
    <p><?php echo h($viewedUser['bio']); ?></p>
</section>

<section class="stack">
    <div class="table-wrap">
        <h2 class="section-title">Organizations</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($organizations === []): ?>
                    <tr>
                        <td colspan="4" class="empty-state">This session user is not linked to any organizations.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($organizations as $organization): ?>
                        <tr>
                            <td><?php echo h($organization['name']); ?></td>
                            <td><?php echo h($organization['role']); ?></td>
                            <td><?php echo h((string) $organization['joined_at']); ?></td>
                            <td><?php echo h($organization['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-wrap">
        <h2 class="section-title">Joined Projects</h2>
        <table>
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Owner</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($joinedProjects === []): ?>
                    <tr>
                        <td colspan="5" class="empty-state">This session user has not joined any projects.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($joinedProjects as $project): ?>
                        <tr>
                            <td>
                                <strong><?php echo h($project['title']); ?></strong><br>
                                <span class="muted"><?php echo h($project['summary']); ?></span>
                            </td>
                                <td><a href="profile.php?user_id=<?php echo (int) $project['owner_user_id']; ?>"><?php echo h($project['owner_name']); ?></a></td>
                            <td><?php echo h($project['role']); ?></td>
                            <td><span class="status <?php echo h($project['status']); ?>"><?php echo h($project['status']); ?></span></td>
                            <td>
                                <?php if ((int) $project['owner_user_id'] === $currentUserId): ?>
                                    <form class="inline-form" method="post" action="update_status.php">
                                        <input type="hidden" name="project_id" value="<?php echo h((string) $project['id']); ?>">
                                        <input type="hidden" name="return_to" value="profile.php">
                                        <button type="submit"><?php echo $project['status'] === 'open' ? 'Close' : 'Reopen'; ?></button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">Read only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

</main>
</div>
</body>
</html>
