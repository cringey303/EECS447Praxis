DROP TABLE IF EXISTS User_Project;
DROP TABLE IF EXISTS User_Organization;
DROP TABLE IF EXISTS Project_Desired_Majors;
DROP TABLE IF EXISTS Projects;
DROP TABLE IF EXISTS Organizations;
DROP TABLE IF EXISTS Users;

CREATE TABLE Users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    handle VARCHAR(32) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    major VARCHAR(100) NOT NULL,
    bio VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_handle (handle),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Organizations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_organizations_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Projects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    organization_id INT UNSIGNED NOT NULL,
    owner_user_id INT UNSIGNED NOT NULL,
    title VARCHAR(140) NOT NULL,
    summary VARCHAR(500) NOT NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_projects_organization_id (organization_id),
    KEY idx_projects_owner_user_id (owner_user_id),
    KEY idx_projects_status (status),
    CONSTRAINT fk_projects_organization FOREIGN KEY (organization_id) REFERENCES Organizations (id) ON DELETE CASCADE,
    CONSTRAINT fk_projects_owner FOREIGN KEY (owner_user_id) REFERENCES Users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE Project_Desired_Majors (
    project_id INT UNSIGNED NOT NULL,
    major VARCHAR(100) NOT NULL,
    PRIMARY KEY (project_id, major),
    KEY idx_project_desired_majors_major (major),
    CONSTRAINT fk_project_desired_majors_project FOREIGN KEY (project_id) REFERENCES Projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE User_Project (
    user_id INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED NOT NULL,
    role VARCHAR(60) NOT NULL DEFAULT 'Member',
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, project_id),
    KEY idx_user_project_project_id (project_id),
    CONSTRAINT fk_user_project_user FOREIGN KEY (user_id) REFERENCES Users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_project_project FOREIGN KEY (project_id) REFERENCES Projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE User_Organization (
    user_id INT UNSIGNED NOT NULL,
    organization_id INT UNSIGNED NOT NULL,
    role VARCHAR(60) NOT NULL DEFAULT 'Member',
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, organization_id),
    KEY idx_user_organization_organization_id (organization_id),
    CONSTRAINT fk_user_organization_user FOREIGN KEY (user_id) REFERENCES Users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_organization_organization FOREIGN KEY (organization_id) REFERENCES Organizations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO Users (id, handle, display_name, email, major, bio) VALUES
(1, 'luke', 'Luke Coffman', 'luke@praxis.local', 'Computer Science', 'Retro terminal tinkerer.'),
(2, 'lucas', 'Lucas Root', 'lucas@praxis.local', 'Electrical Engineering', 'Likes circuits, screens, and low-friction tools.'),
(3, 'ada', 'Ada Pixel', 'ada@praxis.local', 'Interactive Media', 'Builds playful interfaces with strict constraints.');

INSERT INTO Organizations (id, name, description) VALUES
(1, 'Praxis Lab', 'A small studio for campus projects and demos.'),
(2, 'Signal Club', 'Hardware, software, and polished student prototypes.');

INSERT INTO Projects (id, organization_id, owner_user_id, title, summary, status) VALUES
(1, 1, 1, 'Praxis Feed', 'A minimalist project feed with inline filtering and status toggles.', 'open'),
(2, 1, 2, 'Campus Oscilloscope', 'A student-facing dashboard for visualizing sensor traces.', 'open'),
(3, 2, 3, 'Retro RSVP Board', 'A terminal-style event board for club signups and attendance.', 'closed'),
(4, 2, 2, 'Signal Notes', 'A compact knowledge log for lab members and project notes.', 'open');

INSERT INTO Project_Desired_Majors (project_id, major) VALUES
(1, 'Computer Science'),
(1, 'Interactive Media'),
(2, 'Electrical Engineering'),
(2, 'Computer Science'),
(3, 'Interactive Media'),
(3, 'Art'),
(4, 'Electrical Engineering'),
(4, 'Computer Science');

INSERT INTO User_Project (user_id, project_id, role) VALUES
(1, 1, 'Owner'),
(2, 1, 'Contributor'),
(2, 2, 'Owner'),
(1, 2, 'Contributor'),
(3, 3, 'Owner'),
(2, 3, 'Contributor'),
(2, 4, 'Owner'),
(3, 4, 'Contributor');

INSERT INTO User_Organization (user_id, organization_id, role) VALUES
(1, 1, 'Founder'),
(2, 1, 'Member'),
(2, 2, 'Founder'),
(3, 2, 'Member');
