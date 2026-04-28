<?php
declare(strict_types=1);

$pageTitle = 'Project Feed';
require_once __DIR__ . '/header.php';

$currentUserId = (int) $currentUser['id'];
$searchText = trim((string) (filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW) ?? ''));
$majorFilter = trim((string) (filter_input(INPUT_GET, 'major', FILTER_UNSAFE_RAW) ?? ''));
$availableMajors = db_fetch_all('SELECT DISTINCT major FROM Project_Desired_Majors ORDER BY major ASC');

$projects = db_fetch_all(
    <<<SQL
    SELECT
        p.id,
        p.title,
        p.summary,
        p.status,
        p.created_at,
        p.owner_user_id,
        u.display_name AS owner_name,
        o.name AS organization_name,
        GROUP_CONCAT(DISTINCT pdm.major ORDER BY pdm.major SEPARATOR ', ') AS desired_majors
    FROM Projects p
    JOIN Users u ON u.id = p.owner_user_id
    JOIN Organizations o ON o.id = p.organization_id
    LEFT JOIN Project_Desired_Majors pdm ON pdm.project_id = p.id
        WHERE (:major_filter = '' OR EXISTS (
        SELECT 1
        FROM Project_Desired_Majors filter_major
        WHERE filter_major.project_id = p.id
                    AND filter_major.major = :major_lookup
    ))
      AND (
                :search_text = ''
                OR p.title LIKE :search_like_1
                OR p.summary LIKE :search_like_2
                OR u.display_name LIKE :search_like_3
                OR o.name LIKE :search_like_4
    )
    GROUP BY p.id, p.title, p.summary, p.status, p.created_at, p.owner_user_id, u.display_name, o.name
    ORDER BY p.created_at DESC, p.id DESC
    SQL,
    [
                'major_filter' => $majorFilter,
                'major_lookup' => $majorFilter,
                'search_text' => $searchText,
                'search_like_1' => '%' . $searchText . '%',
                'search_like_2' => '%' . $searchText . '%',
                'search_like_3' => '%' . $searchText . '%',
                'search_like_4' => '%' . $searchText . '%',
    ]
);
?>

<section class="panel">
    <p class="section-label">FILTERS</p>
    <form class="search-form" method="get" action="index.php">
        <div class="field wide">
            <label for="q">Search text</label>
            <input id="q" name="q" type="text" value="<?php echo h($searchText); ?>" placeholder="Project title, summary, owner, or organization">
        </div>
        <div class="field">
            <label for="major">Desired major</label>
            <select id="major" name="major">
                <option value="">All majors</option>
                <?php foreach ($availableMajors as $majorRow): ?>
                    <option value="<?php echo h($majorRow['major']); ?>" <?php echo $majorFilter === $majorRow['major'] ? 'selected' : ''; ?>>
                        <?php echo h($majorRow['major']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="actions">
            <button type="submit">Filter</button>
            <a class="button" href="index.php">Reset</a>
        </div>
    </form>
</section>

<section class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Project</th>
                <th>Owner</th>
                <th>Organization</th>
                <th>Desired Majors</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($projects === []): ?>
                <tr>
                    <td colspan="6" class="empty-state">No projects matched the current filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td>
                            <strong><?php echo h($project['title']); ?></strong><br>
                            <span class="muted"><?php echo h($project['summary']); ?></span>
                        </td>
                        <td><?php echo h($project['owner_name']); ?></td>
                        <td><?php echo h($project['organization_name']); ?></td>
                        <td><?php echo h($project['desired_majors'] ?? ''); ?></td>
                        <td><span class="status <?php echo h($project['status']); ?>"><?php echo h($project['status']); ?></span></td>
                        <td>
                            <?php if ((int) $project['owner_user_id'] === $currentUserId): ?>
                                <form class="inline-form" method="post" action="update_status.php">
                                    <input type="hidden" name="project_id" value="<?php echo h((string) $project['id']); ?>">
                                    <input type="hidden" name="return_to" value="index.php">
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
</section>

</main>
</div>
</body>
</html>
