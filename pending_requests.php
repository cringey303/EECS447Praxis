<?php
declare(strict_types=1);

$pageTitle = 'Join Requests';
require_once __DIR__ . '/header.php';

$currentUserId = (int) $currentUser['id'];

// Fetch projects owned by current user with pending requests
$ownedProjectsWithRequests = db_fetch_all(
    <<<SQL
    SELECT
        p.id,
        p.title,
        COUNT(pjr.id) as pending_count
    FROM Projects p
    LEFT JOIN Project_Join_Requests pjr ON pjr.project_id = p.id AND pjr.status = 'pending'
    WHERE p.owner_user_id = :owner_id
    GROUP BY p.id, p.title
    HAVING pending_count > 0
    ORDER BY p.title ASC
    SQL,
    ['owner_id' => $currentUserId]
);

// Fetch all pending requests for projects owned by current user
$pendingRequests = db_fetch_all(
    <<<SQL
    SELECT
        pjr.id as request_id,
        pjr.user_id,
        pjr.project_id,
        pjr.requested_at,
        u.display_name,
        u.handle,
        p.title as project_title
    FROM Project_Join_Requests pjr
    JOIN Users u ON u.id = pjr.user_id
    JOIN Projects p ON p.id = pjr.project_id
    WHERE p.owner_user_id = :owner_id AND pjr.status = 'pending'
    ORDER BY pjr.requested_at DESC
    SQL,
    ['owner_id' => $currentUserId]
);
?>

<section class="panel">
    <p class="section-label">JOIN REQUESTS</p>
    <p>You have <?php echo count($ownedProjectsWithRequests); ?> project(s) with pending join request(s).</p>
</section>

<section class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Project</th>
                <th>Requester</th>
                <th>Requested</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($pendingRequests === []): ?>
                <tr>
                    <td colspan="4" class="empty-state">No pending join requests.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($pendingRequests as $request): ?>
                    <tr>
                        <td><strong><?php echo h($request['project_title']); ?></strong></td>
                        <td>
                            <a href="profile.php?user_id=<?php echo (int) $request['user_id']; ?>">
                                <?php echo h($request['display_name']); ?> (@<?php echo h($request['handle']); ?>)
                            </a>
                        </td>
                        <td><?php echo h((string) $request['requested_at']); ?></td>
                        <td>
                            <div class="action-row">
                                <form class="inline-form" method="post" action="approve_request.php">
                                    <input type="hidden" name="request_id" value="<?php echo h((string) $request['request_id']); ?>">
                                    <button type="submit" class="approve-btn">Approve</button>
                                </form>
                                <form class="inline-form" method="post" action="deny_request.php">
                                    <input type="hidden" name="request_id" value="<?php echo h((string) $request['request_id']); ?>">
                                    <button type="submit" class="deny-btn">Deny</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

</main>
</div>
</body>
</html>
