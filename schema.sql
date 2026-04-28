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
    owner_user_id INT UNSIGNED NOT NULL,
    title VARCHAR(140) NOT NULL,
    summary VARCHAR(500) NOT NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_projects_owner_user_id (owner_user_id),
    KEY idx_projects_status (status),
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
(1, 'luke', 'Luke Coffman', 'luke@praxis.local', 'Computer Science', ''),
(2, 'lucas', 'Lucas Root', 'lucas@praxis.local', 'Computer Science', "Hi, I'm Lucas, a Computer Science and Cybersecurity student @ KU."),
(3, 'alice', 'Alice Smith', 'alice@praxis.local', 'Marketing', "Marketing enthusiast with a passion for creative campaigns."),
(4, 'bob', 'Bob Johnson', 'bob@praxis.local', 'Art', 'Contemprary artist. Works with digital media and interactive installations.'),
(5, 'carol', 'Carol Davis', 'carol@praxis.local', 'Cybersecurity Engineering', '"Protect the digital frontier."'),
(6, 'mark', 'Mark Zuckerberg', 'mark@praxis.local', 'Computer Science', 'Co-founder and CEO of Facebook.');

INSERT INTO Organizations (id, name, description) VALUES
(1, 'Microsoft', 'Software, services, and tools for developers and businesses.'),
(2, "Bob's Burgers", 'A local burger joint.'),
(3, 'Praxis', 'A platform for connecting students with real-world projects and organizations.');

INSERT INTO Projects (id, owner_user_id, title, summary, status) VALUES
(1, 6, 'Facebook', 'The Facebook project.', 'open'),
(2, 1, 'Order Dashboard', 'A dashboard for managing orders.', 'open'),
(3, 4, 'New Menu Items', 'Developing new menu items for the restaurant.', 'open'),
(4, 6, 'Security Audit', 'Conducting a security audit of our systems.', 'open'),
(5, 2, 'Praxis Platform', 'Building the Praxis platform for connecting students with projects.', 'open');

INSERT INTO Project_Desired_Majors (project_id, major) VALUES
(1, 'Computer Science'),
(2, 'Computer Science'),
(3, 'Marketing'),
(4, 'Computer Science'),
(4, 'Cybersecurity Engineering'),
(5, 'Computer Science'),
(5, 'Marketing'),
(5, 'Art'),
(5, 'Cybersecurity Engineering');

INSERT INTO User_Project (user_id, project_id, role) VALUES
(6, 1, 'Owner'),
(1, 2, 'Owner'),
(4, 3, 'Owner'),
(6, 4, 'Owner'),
(2, 5, 'Owner'),
(2, 1, 'Member'),
(3, 2, 'Member'),
(5, 4, 'Member'),
(1, 5, 'Member');

INSERT INTO User_Organization (user_id, organization_id, role) VALUES
(1, 1, 'Member'),
(2, 3, 'Owner'),
(3, 2, 'Member'),
(4, 2, 'Owner'),
(5, 1, 'Member'),
(6, 1, 'Owner')
ON DUPLICATE KEY UPDATE role = VALUES(role);
