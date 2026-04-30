<?php
declare(strict_types=1);

$isPartial = (int) (filter_input(INPUT_GET, 'partial', FILTER_VALIDATE_INT) ?? 0) === 1;

if ($isPartial) {
    require_once __DIR__ . '/db.php';
    set_current_user_from_request();
    $currentUser = current_user();
} else {
    $pageTitle = 'Project Feed';
    require_once __DIR__ . '/header.php';
}

$currentUserId = (int) $currentUser['id'];
$searchText = trim((string) (filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW) ?? ''));
$majorFilter = trim((string) (filter_input(INPUT_GET, 'major', FILTER_UNSAFE_RAW) ?? ''));

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
        echo '<td><a href="profile.php?user_id=' . (int) $project['owner_user_id'] . '">' . h($project['owner_name']) . '</a></td>';
        echo '<td>' . h($project['desired_majors'] ?? '') . '</td>';
        echo '<td><span class="status ' . h($project['status']) . '">' . h($project['status']) . '</span></td>';
        echo '<td>';

        if ((int) $project['owner_user_id'] === $currentUserId) {
            echo '<form class="inline-form" method="post" action="update_status.php">';
            echo '<input type="hidden" name="project_id" value="' . h((string) $project['id']) . '">';
            echo '<input type="hidden" name="return_to" value="index.php">';
            echo '<button type="submit">' . ($project['status'] === 'open' ? 'Close' : 'Reopen') . '</button>';
            echo '</form>';
        } elseif ((int) $project['is_member'] === 1) {
            echo '<span class="success">Member</span>';
        } elseif ($project['join_request_status'] === 'pending') {
            echo '<span class="pending">Request pending</span>';
        } elseif ($project['join_request_status'] === 'approved') {
            echo '<form class="inline-form" method="post" action="accept_approved_request.php">';
            echo '<input type="hidden" name="project_id" value="' . h((string) $project['id']) . '">';
            echo '<input type="hidden" name="return_to" value="index.php">';
            echo '<button type="submit">Accept Approval</button>';
            echo '</form>';
        } elseif ($project['join_request_status'] === 'denied') {
            echo '<span class="error">Request denied</span>';
        } else {
            echo '<form class="inline-form" method="post" action="request_join.php">';
            echo '<input type="hidden" name="project_id" value="' . h((string) $project['id']) . '">';
            echo '<input type="hidden" name="return_to" value="index.php">';
            echo '<button type="submit">Request</button>';
            echo '</form>';
        }

        echo '</td>';
        echo '</tr>';
    }
}

if ($isPartial) {
    render_project_rows($projects, $currentUserId);
    exit;
}
?>

<section class="panel">
    <p class="section-label">FILTERS</p>
    <form class="search-form" method="get" action="index.php" id="project-search-form">
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
            <button type="submit">Filter</button>
            <a class="button" href="index.php">Reset</a>
        </div>
    </form>
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
                const nextUrl = `index.php?${urlParams.toString()}`.replace(/\?$/, '');
                window.history.replaceState({}, '', nextUrl);
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

</main>
</div>
</body>
</html>
