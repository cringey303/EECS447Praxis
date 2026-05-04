# EECS447Praxis
A Praxis-inspired project for EECS447 final project. By Luke Coffman and Lucas Root.

## What The Demo Shows

Praxis is a student project feed where users can browse projects, filter them by desired major, switch session users, and toggle project status when they own a project.

## Code Requirement Mapping

- 3+ tables: `Users`, `Projects`, `Organizations`, `Project_Desired_Majors`, `User_Project`, and `User_Organization` in `schema.sql`.
- 5+ dynamic queries: feed filtering, project feed loading, organization loading, joined projects loading, status lookup, and status update.
- 2+ JOIN queries: the main feed query in `index.php` and the organization/project queries in `profile.php` use JOINs.
- Session usage: `db.php` starts and maintains the session-backed current user flow.
- Database update: `update_status.php` changes a project's status from open to closed or back again.
- No third-party libraries: the app uses plain PHP, MySQL, HTML, and CSS only.

## Demo Checklist

1. Open `index.php` and show the Feed and Profile navbar buttons.
2. Use the Switch User control to show the session-backed current user changing.
3. Filter the feed by a major and by text to show the dynamic query behavior.
4. Open `profile.php` to show the current user's organizations and joined projects.
5. Toggle a project status as the project owner to show the database update requirement.
6. Reset the feed filters to return to the unfiltered project list.

## Setup

1. Import `schema.sql` into a local MySQL database named `praxis`.
2. Set the `PRAXIS_DB_*` environment variables if your local MySQL login differs from the defaults in `db.php`.
3. Run `php -S 127.0.0.1:8000` from the project root and open the app in your browser.
