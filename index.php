<?php
declare(strict_types=1);

$partialMode = (string) (filter_input(INPUT_GET, 'partial', FILTER_UNSAFE_RAW) ?? '');
$isPartial = $partialMode !== '';

$activeTab = (string) (filter_input(INPUT_GET, 'tab', FILTER_UNSAFE_RAW) ?? 'feed');
$allowedTabs = ['feed', 'profile', 'requests'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'feed';
}

if ($isPartial) {
    require_once __DIR__ . '/db.php';
    set_current_user_from_request();
    $currentUser = current_user();
} else {
    $pageTitle = 'Praxis';
    require_once __DIR__ . '/header.php';
}

$currentUserId = (int) $currentUser['id'];
$searchText = trim((string) (filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW) ?? ''));
$majorFilter = trim((string) (filter_input(INPUT_GET, 'major', FILTER_UNSAFE_RAW) ?? ''));
$viewingUserId = (int) (filter_input(INPUT_GET, 'view_user_id', FILTER_VALIDATE_INT) ?? $currentUserId);
if ($viewingUserId <= 0) {
    $viewingUserId = $currentUserId;
}

if (!$isPartial) {
    $availableMajors = db_fetch_all('SELECT DISTINCT major FROM Project_Desired_Majors ORDER BY major ASC');
}

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
        GROUP_CONCAT(DISTINCT pdm.major ORDER BY pdm.major SEPARATOR ', ') AS desired_majors,
        CASE WHEN EXISTS (
            SELECT 1 FROM User_Project
            WHERE user_id = :current_user_id_member AND project_id = p.id
        ) THEN 1 ELSE 0 END AS is_member,
        COALESCE((
            SELECT status FROM Project_Join_Requests
            WHERE user_id = :current_user_id_request AND project_id = p.id
            LIMIT 1
        ), NULL) AS join_request_status
    FROM Projects p
    JOIN Users u ON u.id = p.owner_user_id
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
                OR EXISTS (
                    SELECT 1 FROM Project_Desired_Majors pdm_search
                    WHERE pdm_search.project_id = p.id
                    AND pdm_search.major LIKE :search_like_majors
                )
    )
    GROUP BY p.id, p.title, p.summary, p.status, p.created_at, p.owner_user_id, u.display_name
    ORDER BY p.created_at DESC, p.id DESC
    SQL,
    [
                'current_user_id_member' => $currentUserId,
                'current_user_id_request' => $currentUserId,
                'major_filter' => $majorFilter,
                'major_lookup' => $majorFilter,
                'search_text' => $searchText,
                'search_like_1' => '%' . $searchText . '%',
                'search_like_2' => '%' . $searchText . '%',
                'search_like_3' => '%' . $searchText . '%',
                'search_like_majors' => '%' . $searchText . '%',
    ]
);

function render_project_rows(array $projects, int $currentUserId): void
{
    if ($projects === []) {
        echo '<tr><td colspan="5" class="empty-state">No projects matched the current filters.</td></tr>';
        return;
    }

    foreach ($projects as $project) {
        echo '<tr>';
        echo '<td><strong>' . h($project['title']) . '</strong><br><span class="muted">' . h($project['summary']) . '</span></td>';
        echo '<td><a href="index.php?tab=profile&view_user_id=' . (int) $project['owner_user_id'] . '">' . h($project['owner_name']) . '</a></td>';
        echo '<td>' . h($project['desired_majors'] ?? '') . '</td>';
        echo '<td><span class="status ' . h($project['status']) . '">' . h($project['status']) . '</span></td>';
        echo '<td>';

        if ((int) $project['owner_user_id'] === $currentUserId) {
            echo '<form class="inline-form" method="post" action="update_status.php">';
            echo '<input type="hidden" name="project_id" value="' . h((string) $project['id']) . '">';
            echo '<input type="hidden" name="return_to" value="index.php?tab=feed">';
            echo '<button type="submit">' . ($project['status'] === 'open' ? 'Close' : 'Reopen') . '</button>';
            echo '</form>';
        } elseif ((int) $project['is_member'] === 1) {
            echo '<span class="success">Member</span>';
        } elseif ($project['join_request_status'] === 'pending') {
            echo '<span class="pending">Request pending</span>';
        } elseif ($project['join_request_status'] === 'denied') {
            echo '<span class="error">Request denied</span>';
        } elseif ($project['status'] === 'closed') {
            echo '<span class="muted">Closed</span>';
        } else {
            echo '<form class="inline-form" method="post" action="request_join.php">';
            echo '<input type="hidden" name="project_id" value="' . h((string) $project['id']) . '">';
            echo '<input type="hidden" name="return_to" value="index.php?tab=feed">';
            echo '<button type="submit">Request</button>';
            echo '</form>';
        }

        echo '</td>';
        echo '</tr>';
    }
}

function render_profile_panel(int $viewingUserId, int $currentUserId): void
{
    $viewedUser = db_fetch_one('SELECT id, handle, display_name, email, bio FROM Users WHERE id = :user_id', ['user_id' => $viewingUserId]);
    if ($viewedUser === null) {
        echo '<section class="panel"><p class="empty-state">User not found.</p></section>';
        return;
    }

    $isOwnProfile = (int) $viewedUser['id'] === $currentUserId;

    $organizations = db_fetch_all(
        <<<SQL
        SELECT o.name, o.description, uo.role, uo.joined_at
        FROM User_Organization uo
        JOIN Organizations o ON o.id = uo.organization_id
        WHERE uo.user_id = :user_id
        ORDER BY o.name ASC
        SQL,
        ['user_id' => $viewingUserId]
    );

    $joinedProjects = db_fetch_all(
        <<<SQL
        SELECT p.id, p.title, p.summary, p.status, p.owner_user_id, up.role, owner.display_name AS owner_name
        FROM User_Project up
        JOIN Projects p ON p.id = up.project_id
        JOIN Users owner ON owner.id = p.owner_user_id
        WHERE up.user_id = :user_id
        ORDER BY p.created_at DESC, p.id DESC
        SQL,
        ['user_id' => $viewingUserId]
    );

    echo '<section class="panel">';
    echo '<p class="section-label">' . ($isOwnProfile ? 'YOUR PROFILE' : 'USER PROFILE') . '</p>';
    echo '<h2>' . h($viewedUser['display_name']) . '</h2>';
    echo '<p class="muted">@' . h($viewedUser['handle']) . ' • ' . h($viewedUser['email']) . '</p>';
    echo '<p>' . h($viewedUser['bio']) . '</p>';
    echo '</section>';

    echo '<section class="stack">';
    echo '<div class="table-wrap"><h2 class="section-title">Organizations</h2><table><thead><tr><th>Name</th><th>Role</th><th>Joined</th><th>Description</th></tr></thead><tbody>';
    if ($organizations === []) {
        echo '<tr><td colspan="4" class="empty-state">This user is not linked to any organizations.</td></tr>';
    } else {
        foreach ($organizations as $organization) {
            echo '<tr><td>' . h($organization['name']) . '</td><td>' . h($organization['role']) . '</td><td>' . h((string) $organization['joined_at']) . '</td><td>' . h($organization['description']) . '</td></tr>';
        }
    }
    echo '</tbody></table></div>';

    echo '<div class="table-wrap"><h2 class="section-title">Joined Projects</h2><table><thead><tr><th>Project</th><th>Owner</th><th>Role</th><th>Status</th><th>Action</th></tr></thead><tbody>';
    if ($joinedProjects === []) {
        echo '<tr><td colspan="5" class="empty-state">This user has not joined any projects.</td></tr>';
    } else {
        foreach ($joinedProjects as $project) {
            echo '<tr>';
            echo '<td><strong>' . h($project['title']) . '</strong><br><span class="muted">' . h($project['summary']) . '</span></td>';
            echo '<td><a href="index.php?tab=profile&view_user_id=' . (int) $project['owner_user_id'] . '">' . h($project['owner_name']) . '</a></td>';
            echo '<td>' . h($project['role']) . '</td>';
            echo '<td><span class="status ' . h($project['status']) . '">' . h($project['status']) . '</span></td>';
            echo '<td>';
            if ((int) $project['owner_user_id'] === $currentUserId) {
                echo '<form class="inline-form" method="post" action="update_status.php">';
                echo '<input type="hidden" name="project_id" value="' . h((string) $project['id']) . '">';
                echo '<input type="hidden" name="return_to" value="index.php?tab=profile&view_user_id=' . (int) $viewingUserId . '">';
                echo '<button type="submit">' . ($project['status'] === 'open' ? 'Close' : 'Reopen') . '</button>';
                echo '</form>';
            } else {
                echo '<span class="muted">Read only</span>';
            }
            echo '</td></tr>';
        }
    }
    echo '</tbody></table></div></section>';
}

function render_request_rows(int $currentUserId): int
{
    $pendingRequests = db_fetch_all(
        <<<SQL
        SELECT pjr.id AS request_id, pjr.user_id, pjr.project_id, pjr.requested_at, u.display_name, u.handle, p.title AS project_title
        FROM Project_Join_Requests pjr
        JOIN Users u ON u.id = pjr.user_id
        JOIN Projects p ON p.id = pjr.project_id
        WHERE p.owner_user_id = :owner_id AND pjr.status = 'pending'
        ORDER BY pjr.requested_at DESC
        SQL,
        ['owner_id' => $currentUserId]
    );

    if ($pendingRequests === []) {
        echo '<tr><td colspan="4" class="empty-state">No pending join requests.</td></tr>';
        return 0;
    }

    foreach ($pendingRequests as $request) {
        echo '<tr>';
        echo '<td><strong>' . h($request['project_title']) . '</strong></td>';
        echo '<td><a href="index.php?tab=profile&view_user_id=' . (int) $request['user_id'] . '">' . h($request['display_name']) . ' (@' . h($request['handle']) . ')</a></td>';
        echo '<td>' . h((string) $request['requested_at']) . '</td>';
        echo '<td><div class="action-row">';
        echo '<form class="inline-form" method="post" action="approve_request.php"><input type="hidden" name="request_id" value="' . h((string) $request['request_id']) . '"><input type="hidden" name="return_to" value="index.php?tab=requests"><button type="submit" class="approve-btn">Approve</button></form>';
        echo '<form class="inline-form" method="post" action="deny_request.php"><input type="hidden" name="request_id" value="' . h((string) $request['request_id']) . '"><input type="hidden" name="return_to" value="index.php?tab=requests"><button type="submit" class="deny-btn">Deny</button></form>';
        echo '</div></td></tr>';
    }

    return count(array_unique(array_map(static fn(array $row): int => (int) $row['project_id'], $pendingRequests)));
}

if ($isPartial && $partialMode === '1') {
    render_project_rows($projects, $currentUserId);
    exit;
}
?>

<section class="app-section<?php echo $activeTab === 'feed' ? '' : ' is-hidden'; ?>" data-tab-panel="feed">
    <section class="panel">
        <p class="section-label">FILTERS</p>
        <form class="search-form" method="get" action="index.php" id="project-search-form">
            <input type="hidden" name="tab" value="feed">
            <div class="field wide">
                <label for="q">Search text</label>
                <input id="q" name="q" type="text" value="<?php echo h($searchText); ?>" placeholder="Project title, description, or owner">
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
                <a class="button" href="index.php?tab=feed">Reset</a>
            </div>
        </form>
    </section>

    <section class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Owner</th>
                    <th>Desired Majors</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="projects-table-body">
                <?php render_project_rows($projects, $currentUserId); ?>
            </tbody>
        </table>
    </section>
</section>

<section class="app-section<?php echo $activeTab === 'profile' ? '' : ' is-hidden'; ?>" data-tab-panel="profile">
    <?php render_profile_panel($viewingUserId, $currentUserId); ?>
</section>

<section class="app-section<?php echo $activeTab === 'requests' ? '' : ' is-hidden'; ?>" data-tab-panel="requests">
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
                <?php render_request_rows($currentUserId); ?>
            </tbody>
        </table>
    </section>
</section>

<script>
(() => {
    const form = document.getElementById('project-search-form');
    const searchInput = document.getElementById('q');
    const majorSelect = document.getElementById('major');
    const tableBody = document.getElementById('projects-table-body');

    if (!form || !searchInput || !majorSelect || !tableBody) {
        return;
    }

    let inputTimer = null;
    let requestController = null;

    const loadRows = () => {
        if (requestController) {
            requestController.abort();
        }

        requestController = new AbortController();
        const params = new URLSearchParams(new FormData(form));
        params.set('partial', '1');

        fetch(`index.php?${params.toString()}`, {
            method: 'GET',
            headers: { 'X-Requested-With': 'fetch' },
            signal: requestController.signal,
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Failed to load projects.');
                }

                return response.text();
            })
            .then((html) => {
                tableBody.innerHTML = html;

                const urlParams = new URLSearchParams(new FormData(form));
                urlParams.set('tab', 'feed');
                window.history.replaceState({}, '', `index.php?${urlParams.toString()}`);
            })
            .catch((error) => {
                if (error.name !== 'AbortError') {
                    console.error(error);
                }
            });
    };

    searchInput.addEventListener('input', () => {
        if (inputTimer !== null) {
            window.clearTimeout(inputTimer);
        }

        inputTimer = window.setTimeout(() => {
            loadRows();
        }, 220);
    });

    majorSelect.addEventListener('change', () => {
        loadRows();
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadRows();
    });
})();
</script>

</main>
</div>
</body>
</html>
