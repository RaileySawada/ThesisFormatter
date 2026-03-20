CREATE TABLE departments (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  dcode text NOT NULL,
  dname text NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO departments (id, dcode, dname, created_at, updated_at) VALUES
(1, 'DCI', 'Department of Computing and Informatics', '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(2, 'DBA', 'Department of Business and Accountancy', '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(3, 'DTE', 'Department of Teacher Education', '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(4, 'DAS', 'Department of Arts and Sciences', '2023-04-09 10:43:00', '2023-04-09 10:43:00');

CREATE TABLE programs (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pcode text NOT NULL,
  pname text NOT NULL,
  department_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id)
);

INSERT INTO programs (id, pcode, pname, department_id, created_at, updated_at) VALUES
(1, 'IT', 'Information Technology', 1, '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(2, 'CS', 'Computer Science', 1, '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(3, 'ACC', 'Accountancy', 2, '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(4, 'AIS', 'Accounting Information System', 2, '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(5, 'EE', 'Elementary Education', 3, '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(6, 'ECE', 'Early Childhood Education', 3, '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(7, 'BSEd English', 'BSEd English', 3, '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(8, 'BSEd Mathematics', 'BSEd Mathematics', 3, '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(9, 'BSEd Filipino', 'BSEd Filipino', 3, '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(10, 'BSEd Science', 'BSEd Science', 3, '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(11, 'BSEd Social studies', 'BSEd Social studies', 3, '2023-04-09 10:43:00', '2023-04-09 10:43:00'),
(12, 'BS Psychology', 'BS Psychology', 4, '2023-04-09 10:43:00', '2023-04-09 10:43:00');

CREATE TABLE edocs_users (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `role` enum('ADMIN','ADMIN STAFF','FACULTY','STAFF','STUDENT','ACCREDITOR') NOT NULL,
  `department` int(11) DEFAULT NULL,
  `program` int(11) DEFAULT NULL,
  `data_analyst` varchar(255) DEFAULT NULL,
  `research_adviser` varchar(255) DEFAULT NULL,
  `language_editor` varchar(255) DEFAULT NULL,
  `research_facilitator` varchar(255) DEFAULT NULL,
  `dean` varchar(255) DEFAULT NULL,
  `group_number` int(100) DEFAULT NULL,
  `email` text DEFAULT NULL,
  `password` text DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT 'default.png',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `lockout_time` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  FOREIGN KEY (department) REFERENCES departments(id),
  FOREIGN KEY (program) REFERENCES programs(id)
);

INSERT INTO `edocs_users` (`id`, `first_name`, `middle_name`, `last_name`, `id_number`, `role`, `department`, `program`, `data_analyst`, `research_adviser`, `language_editor`, `research_facilitator`, `dean`, `group_number`, `email`, `password`, `profile_pic`, `reset_token`, `reset_token_expires_at`, `login_attempts`, `lockout_time`, `created_at`, `updated_at`) VALUES
(1, 'Admin', '', 'Hehe', '9999-99999', 'ADMIN', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin@example.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'default.png', NULL, NULL, 0, NULL, '2025-10-10 15:18:01', '2026-01-29 21:10:34'),
(2, 'Regina ', 'G', 'Almonte', '0000-00000', 'ADMIN STAFF', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'adminstaff@example.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'default.png', NULL, NULL, 0, NULL, '2025-10-10 15:18:01', '2025-10-10 15:18:01'),
(3, 'Arlou', 'H', 'Fernando', '1111-11111', 'FACULTY', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dcidean@example.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'default.png', NULL, NULL, 0, NULL, '2025-10-10 15:18:01', '2025-10-10 15:18:01'),
(4, 'Railey', 'Solidum', 'Dela Peña', '2022-10361', 'STUDENT', 1, 1, '1111-11111', '1111-11111', '0000-00000', '0000-00000', '1111-11111', NULL, 'rsdela_pena@ccc.edu.ph', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'default.png', NULL, NULL, 0, NULL, '2025-10-13 13:38:22', '2026-01-28 14:13:53'),
(5, 'Renuell', 'Matundan', 'Niquit', '2022-10844', 'STUDENT', 1, 1, '1111-11111', '1111-11111', '0000-00000', '0000-00000', '1111-11111', NULL, 'rmniquit@gmail.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'default.png', NULL, NULL, 0, NULL, '2025-10-15 22:48:02', '2025-10-15 22:48:06'),
(6, 'Director', 'Research', 'Publication', '2222-22222', 'FACULTY', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'drp@example.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'default.png', NULL, NULL, 0, NULL, '2025-10-22 11:20:32', '2025-10-22 11:20:32'),
(7, 'QA', NULL, NULL, '3333-33333', 'FACULTY', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'qa@exmple.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'default.png', NULL, NULL, 0, NULL, '2025-10-22 11:20:32', '2025-10-22 11:20:32'),
(8, 'Accreditor1', '', '', '0011-00000', 'ACCREDITOR', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'accred1@example.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'default.png', NULL, NULL, 0, NULL, '2025-10-22 11:20:32', '2026-01-29 11:50:28'),
(9, 'DCIUploader', NULL, NULL, '0022-00000', 'STAFF', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dciuploader@example.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'default.png', NULL, NULL, 0, NULL, '2025-10-22 11:20:32', '2025-10-22 11:20:32'),
(10, 'DAS', NULL, 'DEAN', '4444-44444', 'FACULTY', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dasdean@example.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'default.png', NULL, NULL, 0, NULL, '2025-10-22 11:20:32', '2026-01-17 23:58:52'),
(11, 'DASUploader', NULL, NULL, '0033-00000', 'STAFF', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dasuploader@example.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'default.png', NULL, NULL, 0, NULL, '2025-10-22 11:20:32', '2025-10-22 11:20:32'),
(12, 'Pwetters', 'Solidum', 'Dela Peña', '2022-11111', 'STUDENT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'rerudelapena@gmail.com', '$2y$10$X33Z7VoPwKfRZxM.zUS0TuPZJ7MFzhZix7b4RjW5L/xPf35TO..Um', 'default.png', NULL, NULL, 0, NULL, '2026-01-30 12:01:38', '2026-01-30 12:01:38');

CREATE TABLE research_roles (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id int(11) NOT NULL,
  role varchar(100) NOT NULL,
  date_added datetime DEFAULT current_timestamp(),
  FOREIGN KEY (user_id) REFERENCES edocs_users(id)
);

INSERT INTO research_roles (id, user_id, role, date_added) VALUES
(1, 3, 'Data Analyst', '2025-12-01 10:21:46'),
(2, 2, 'Language Editor', '2025-12-01 10:22:32'),
(3, 3, 'Research Adviser', '2025-12-01 15:47:17'),
(4, 2, 'Research Adviser', '2025-12-02 10:18:25'),
(5, 3, 'Dean', '2025-12-02 10:18:59'),
(6, 2, 'Research Facilitator', '2025-12-02 12:52:26');

CREATE TABLE rejection_comments (
  rejection_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  form_id int(11) NOT NULL,
  student_id int(11) DEFAULT NULL,
  approver_id int(11) NOT NULL,
  reason varchar(1000) DEFAULT NULL,
  rejected_at timestamp NOT NULL DEFAULT current_timestamp(),
  foreign key (student_id) references edocs_users(id),
  foreign key (approver_id) references edocs_users(id)
);

INSERT INTO rejection_comments (rejection_id, form_id, student_id, approver_id, reason, rejected_at) VALUES
(1, 4, 5, 3, 'Lorem ipsum, dolor sit amet consectetur adipisicing elit.', '2026-01-12 06:31:30'),
(4, 5, 5, 3, 'Wrong document uploaded sdasdadwwqda', '2026-01-14 14:28:31'),
(5, 4, 5, 3, 'Wrong file format', '2026-01-16 03:34:06');

CREATE TABLE sr_forms (
  id bigint(200) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  form_number int(10) UNSIGNED NOT NULL,
  form_title varchar(255) NOT NULL,
  form_file varchar(255) NOT NULL,
  vprepqa bigint(200) NOT NULL DEFAULT 0,
  research_facilitator bigint(200) NOT NULL DEFAULT 0,
  research_adviser bigint(200) NOT NULL DEFAULT 0,
  data_analyst bigint(200) NOT NULL DEFAULT 0,
  language_editor bigint(200) NOT NULL DEFAULT 0,
  dean bigint(200) NOT NULL DEFAULT 0,
  drp bigint(200) NOT NULL DEFAULT 0,
  vpaa bigint(200) NOT NULL DEFAULT 0,
  created_at datetime DEFAULT current_timestamp(),
  updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
);

INSERT INTO sr_forms (id, form_number, form_title, form_file, vprepqa, research_facilitator, research_adviser, data_analyst, language_editor, dean, drp, vpaa, created_at, updated_at) VALUES
(4, 1, "Research Consultant\'s Acceptance Form", 'SR-Form1_Research_Consultant_s_Acceptance_Form.docx', 0, 0, 0, 1, 2, 3, 0, 0, '2025-10-15 21:38:43', '2025-10-30 15:25:03'),
(5, 2, 'Research Topic Approval Form', 'SR-Form2.docx', 0, 0, 1, 3, 2, 1, 0, 0, '2025-10-15 22:21:47', '2026-01-07 13:50:30'),
(6, 3, 'Oral Defense Endorsement Form', 'SR-Form3.docx', 1, 2, 1, 3, 0, 0, 0, 0, '2025-10-15 22:24:43', '2026-01-13 16:07:25'),
(7, 4, "Panelist\'s Acceptance Form", 'SR-Form4.docx', 0, 1, 0, 4, 3, 2, 5, 0, '2025-10-15 22:26:11', '2026-01-07 22:21:45'),
(8, 5, 'Ethics Clearance', 'SR-Form5.docx', 1, 0, 1, 0, 0, 0, 1, 0, '2025-10-15 22:29:20', '2025-10-27 12:58:51'),
(9, 6, 'Thesis Writing Monitoring Form', 'SR-Form6.docx', 0, 1, 0, 0, 0, 0, 0, 0, '2025-10-15 22:30:37', '2025-10-27 12:59:07'),
(10, 7, 'Recommendations Compliance Sheet', 'SR-Form7.docx', 0, 0, 1, 0, 0, 0, 0, 0, '2025-10-15 22:32:12', '2025-10-27 12:59:22'),
(11, 8, 'Hardbound Compliance Form', 'SR-Form8.docx', 1, 0, 0, 0, 0, 1, 0, 1, '2025-10-15 22:32:58', '2025-10-27 12:59:45'),
(12, 9, "Change of Research Consultant\'s Form", 'SR-Form9.docx', 0, 1, 1, 2, 3, 4, 0, 5, '2025-10-15 22:35:31', '2025-11-04 14:40:43'),
(13, 10, 'Change of Student Members Form', 'SR-Form10.docx', 0, 1, 0, 0, 0, 2, 0, 3, '2025-10-15 22:37:31', '2025-11-04 14:40:03'),
(14, 11, 'adaad', 'Form Hierarchy.docx', 2, 1, 0, 0, 0, 0, 0, 0, '2025-10-27 13:15:55', '2025-10-27 13:15:55'),
(15, 12, 'adawfaafawfaf', 'SR-Form12_adawfaafawfaf.docx', 1, 2, 0, 0, 4, 5, 3, 0, '2025-10-27 13:28:02', '2025-10-27 13:28:02'),
(16, 13, 'pwesadwhihi', 'SR-Form13_pwesadwhihi.docx', 1, 0, 0, 0, 0, 3, 0, 2, '2025-10-28 10:32:59', '2025-10-30 14:04:44'),
(17, 14, 'hello', 'SR-Form14_hello.docx', 0, 2, 0, 0, 0, 0, 0, 1, '2025-10-30 14:30:46', '2025-10-30 16:27:02'),
(18, 15, 'awdawawdawdmilihhadsd', 'SR-Form15_awdawawdawdmilihhadsd.docx', 0, 0, 0, 0, 1, 0, 0, 0, '2025-10-30 17:01:15', '2025-10-30 17:01:15');

CREATE TABLE team_members (
  id varchar(20) NOT NULL,
  leader_id varchar(20) NOT NULL,
  name varchar(100) NOT NULL,
  role varchar(100) NOT NULL,
  initials varchar(20) DEFAULT NULL,
  date_added timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO team_members (id, leader_id, name, role, initials, date_added) VALUES
('123', '2022-10844', 'Alden Richards', 'Developer', 'AR', '2025-11-24 19:25:44'),
('2022-12932', '2022-10844', 'Cha Eun Woo', 'Developer', 'CEW', '2025-12-22 07:09:13'),
('2022-18920', '2022-10844', 'Daniel Padilla', 'Developer', 'DP', '2025-12-22 04:20:33'),
('2022-19878', '', 'Alden Richardss', 'Developer', 'AR', '2025-11-24 19:08:59');

CREATE TABLE academic_years (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  acad_year text NOT NULL,
  active tinyint(1) DEFAULT 0,
  created_at datetime DEFAULT current_timestamp()
);


INSERT INTO academic_years (id, acad_year, active, created_at) VALUES
(1, '1st SEM 2025-2026', 0, '2026-01-05 14:18:42'),
(2, '2nd SEM 2025-2026', 1, '2026-01-05 14:18:42');

CREATE TABLE upload_student_files (
  upload_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  form_id int(11) NOT NULL,
  student_id varchar(20) NOT NULL,
  approver_id varchar(20) NOT NULL,
  approver_role varchar(100) NOT NULL,
  order_number int(11) NOT NULL,
  status enum('pending','approved','rejected') DEFAULT 'pending',
  rejection_comments varchar(1000) DEFAULT NULL,
  file_path varchar(255) NOT NULL,
  semester int(11) NOT NULL,
  uploaded_at timestamp NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (semester) REFERENCES academic_years(id)
);

INSERT INTO upload_student_files (upload_id, form_id, student_id, approver_id, approver_role, order_number, status, rejection_comments, file_path, semester, uploaded_at) VALUES
(1, 4, '2022-10844', '1111-11111', 'Data Analyst', 1, 'rejected', NULL, 'Certificate_size_A4.pdf', 1, '2026-01-13 04:06:54'),
(2, 4, '2022-10844', '0000-00000', 'Language Editor', 2, 'pending', NULL, 'Certificate_size_A4.pdf', 1, '2026-01-13 04:06:54'),
(3, 4, '2022-10844', '1111-11111', 'Dean', 3, 'pending', NULL, 'Certificate_size_A4.pdf', 1, '2026-01-13 04:06:54'),
(4, 5, '2022-10844', '1111-11111', 'Dean', 1, 'rejected', NULL, 'lab_1.4_Activity_Niquit_3-IT3.pdf', 1, '2026-01-13 06:02:35'),
(5, 5, '2022-10844', '0000-00000', 'Language Editor', 2, 'pending', NULL, 'lab_1.4_Activity_Niquit_3-IT3.pdf', 1, '2026-01-13 06:02:35'),
(6, 5, '2022-10844', '1111-11111', 'Data Analyst', 3, 'pending', NULL, 'lab_1.4_Activity_Niquit_3-IT3.pdf', 1, '2026-01-13 06:02:35'),
(7, 6, '2022-10844', '0000-00000', 'Research Facilitator', 1, 'pending', NULL, '(1)lab_1.4_Activity_Niquit_3-IT3.pdf', 1, '2026-01-13 06:03:53'),
(8, 6, '2022-10844', '1111-11111', 'Data Analyst', 2, 'pending', NULL, '(1)lab_1.4_Activity_Niquit_3-IT3.pdf', 1, '2026-01-13 06:03:53'),
(9, 4, '2022-10844', '1111-11111', 'Data Analyst', 1, 'rejected', NULL, '(2)lab_1.4_Activity_Niquit_3-IT3.pdf', 1, '2026-01-13 08:26:13'),
(10, 4, '2022-10844', '0000-00000', 'Language Editor', 2, 'pending', NULL, '(2)lab_1.4_Activity_Niquit_3-IT3.pdf', 1, '2026-01-13 08:26:13'),
(11, 4, '2022-10844', '1111-11111', 'Dean', 3, 'pending', NULL, '(2)lab_1.4_Activity_Niquit_3-IT3.pdf', 1, '2026-01-13 08:26:13'),
(12, 10, '2022-10844', '1111-11111', 'Research Adviser', 1, 'pending', NULL, '(1)Certificate_size_A4.pdf', 1, '2026-01-14 14:26:52');

CREATE TABLE qa_level (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  level int(11) NOT NULL,
  active boolean NOT NULL
);

INSERT INTO qa_level (id, level, active) VALUES
(1, 1, 0),
(2, 2, 1);

CREATE TABLE qa_area (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  area_number int(11) NOT NULL,
  area_name text NOT NULL,
  created_at datetime DEFAULT current_timestamp()
);

INSERT INTO qa_area (id, area_number, area_name, created_at) VALUES
(1, 1, 'Governance and Administration', '2026-01-16 15:19:47'),
(2, 2, 'Faculty', '2026-01-16 15:19:47'),
(3, 3, 'Curriculum & Instruction', '2026-01-16 15:19:47'),
(4, 4, 'Student Development Services', '2026-01-16 15:19:47'),
(5, 5, 'Entrepreneurship and Employability', '2026-01-16 15:19:47'),
(6, 6, 'Community Extension Services', '2026-01-16 15:19:47'),
(7, 7, 'Research', '2026-01-16 15:19:47'),
(8, 8, 'Library', '2026-01-16 15:19:47'),
(9, 9, 'Laboratories', '2026-01-16 15:19:47'),
(10, 10, 'Physical Plant', '2026-01-16 15:19:47');

CREATE TABLE qa_links (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  department int(11) NOT NULL,
  program int(11) NOT NULL,
  level int(11) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department) REFERENCES departments(id),
  FOREIGN KEY (program) REFERENCES programs(id),
  FOREIGN KEY (level) REFERENCES qa_level(id)
);

INSERT INTO qa_links (id, department, program, level) VALUES
(1, 3, 5, 1),
(2, 3, 6, 2),
(3, 3, 7, 1),
(4, 3, 8, 1),
(5, 3, 9, 2),
(6, 3, 10, 2),
(7, 3, 11, 2),
(8, 2, 3, 1),
(9, 2, 4, 2),
(10, 1, 1, 1),
(11, 1, 2, 1),
(12, 4, 12, 2);

CREATE TABLE qa_roles (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id int(11) NOT NULL,
  user_role enum('VPREPQA','QA','FACULTY','UPLOADER','ACCREDITOR') NOT NULL,
  department int(11) DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (user_id) REFERENCES edocs_users(id),
  FOREIGN KEY (department) REFERENCES departments(id)
);

INSERT INTO qa_roles (id, user_id, user_role, department, created_at) VALUES
(1, 2, 'VPREPQA', NULL, '2026-01-05 14:18:42'),
(2, 7, 'QA', NULL, '2026-01-05 14:18:42'),
(3, 8, 'ACCREDITOR', NULL, '2026-01-05 14:18:42'),
(4, 3, 'FACULTY', 1, '2026-01-05 14:18:42'),
(5, 9, 'UPLOADER', 1, '2026-01-05 14:18:42'),
(6, 10, 'FACULTY', 4, '2026-01-07 10:52:21'),
(7, 11, 'UPLOADER', 4, '2026-01-07 10:53:07'),
(8, 6, 'FACULTY', NULL, '2026-01-15 09:14:08');

CREATE TABLE qa_items (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  department int(11) NOT NULL,
  program int(11) NOT NULL,
  qa_level int(11) NOT NULL,
  qa_area int(11) NOT NULL,
  item text DEFAULT NULL,
  item_type enum('Compliance','Self-survey') NOT NULL,
  note text NOT NULL,
  dean_status enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  qa_status enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  vprepqa_status enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  item_status enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  status enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  requested_by text NOT NULL,
  qa_uploader int(11) DEFAULT NULL,
  reason text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (department) REFERENCES departments(id),
  FOREIGN KEY (program) REFERENCES programs(id),
  FOREIGN KEY (qa_level) REFERENCES qa_level(id),
  FOREIGN KEY (qa_area) REFERENCES qa_area(id),
  FOREIGN KEY (qa_uploader) REFERENCES edocs_users(id)
);

INSERT INTO qa_items (id, department, program, qa_level, qa_area, item, item_type, note, dean_status, qa_status, vprepqa_status, item_status, status, requested_by, qa_uploader, reason, created_at) VALUES
(1, 1, 1, 1, 1, 'Area 1_BSIT.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 12:16:05'),
(2, 1, 1, 1, 2, 'Area 2_BSIT.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:19:38'),
(3, 1, 1, 1, 3, 'Area 3_BSIT.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:19:38'),
(4, 1, 1, 1, 4, 'Area 4_BSIT.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:19:38'),
(5, 1, 1, 1, 5, 'Area 5_BSIT.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:19:38'),
(6, 1, 1, 1, 6, 'Area 6_BSIT.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:19:38'),
(7, 1, 1, 1, 7, 'Area 7_BSIT.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:19:38'),
(8, 1, 1, 1, 8, 'Area 8_BSIT.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:19:38'),
(9, 1, 1, 1, 9, 'Area 9_BSIT.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:19:38'),
(10, 1, 1, 1, 10, 'Area 10_BSIT.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:19:38'),
(11, 1, 2, 1, 1, 'Area 1_BSCS.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:35:23'),
(12, 1, 2, 1, 2, 'Area 2_BSCS.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:35:23'),
(13, 1, 2, 1, 3, 'Area 3_BSCS.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:35:23'),
(14, 1, 2, 1, 4, 'Area 4_BSCS.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:35:23'),
(15, 1, 2, 1, 5, 'Area 5_BSCS.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:35:23'),
(16, 1, 2, 1, 6, 'Area 6_BSCS.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:35:23'),
(17, 1, 2, 1, 7, 'Area 7_BSCS.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:35:23'),
(18, 1, 2, 1, 8, 'Area 8_BSCS.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:35:23'),
(19, 1, 2, 1, 9, 'Area 9_BSCS.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:35:23'),
(20, 1, 2, 1, 10, 'Area 10_BSCS.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-18 15:35:23'),
(21, 3, 5, 1, 1, 'Area 1_BEED.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(22, 3, 5, 1, 2, 'Area 2_BEED.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(23, 3, 5, 1, 3, 'Area 3_BEED.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(24, 3, 5, 1, 4, 'Area 4_BEED.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(25, 3, 5, 1, 5, 'Area 5_BEED.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(26, 3, 5, 1, 6, 'Area 6_BEED.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(27, 3, 5, 1, 7, 'Area 7_BEED.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(28, 3, 5, 1, 8, 'Area 8_BEED.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(29, 3, 5, 1, 9, 'Area 9_BEED.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(30, 3, 5, 1, 10, 'Area 10_BEED.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(31, 3, 6, 2, 1, 'Area 1_BECE.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(32, 3, 6, 2, 2, 'Area 2_BECE.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(33, 3, 6, 2, 3, 'Area 3_BECE.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(34, 3, 6, 2, 4, 'Area 4_BECE.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(35, 3, 6, 2, 5, 'Area 5_BECE.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(36, 3, 6, 2, 6, 'Area 6_BECE.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(37, 3, 6, 2, 7, 'Area 7_BECE.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(38, 3, 6, 2, 8, 'Area 8_BECE.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(39, 3, 6, 2, 9, 'Area 9_BECE.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(40, 3, 6, 2, 10, 'Area 10_BECE.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(41, 3, 7, 1, 1, 'Area 1_BSEE.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(42, 3, 7, 1, 2, 'Area 2_BSEE.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(43, 3, 7, 1, 3, 'Area 3_BSEE.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(44, 3, 7, 1, 4, 'Area 4_BSEE.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(45, 3, 7, 1, 5, 'Area 5_BSEE.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(46, 3, 7, 1, 6, 'Area 6_BSEE.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(47, 3, 7, 1, 7, 'Area 7_BSEE.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(48, 3, 7, 1, 8, 'Area 8_BSEE.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(49, 3, 7, 1, 9, 'Area 9_BSEE.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(50, 3, 7, 1, 10, 'Area 10_BSEE.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(51, 3, 8, 1, 1, 'Area 1_BSEM.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(52, 3, 8, 1, 2, 'Area 2_BSEM.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(53, 3, 8, 1, 3, 'Area 3_BSEM.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(54, 3, 8, 1, 4, 'Area 4_BSEM.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(55, 3, 8, 1, 5, 'Area 5_BSEM.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(56, 3, 8, 1, 6, 'Area 6_BSEM.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(57, 3, 8, 1, 7, 'Area 7_BSEM.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(58, 3, 8, 1, 8, 'Area 8_BSEM.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(59, 3, 8, 1, 9, 'Area 9_BSEM.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(60, 3, 8, 1, 10, 'Area 10_BSEM.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(61, 3, 9, 2, 1, 'Area 1_BSEd Filipino.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, 'aiohdadhaw ajkbdkabwd bjaksnd, and lakndland', '2025-12-19 00:31:54'),
(62, 3, 9, 2, 2, 'Area 2_BSEF.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(63, 3, 9, 2, 3, 'Area 3_BSEF.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(64, 3, 9, 2, 4, 'Area 4_BSEF.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(65, 3, 9, 2, 5, 'Area 5_BSEF.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(66, 3, 9, 2, 6, 'Area 6_BSEF.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(67, 3, 9, 2, 7, 'Area 7_BSEF.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(68, 3, 9, 2, 8, 'Area 8_BSEF.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(69, 3, 9, 2, 9, 'Area 9_BSEF.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(70, 3, 9, 2, 10, 'Area 10_BSEF.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(71, 3, 10, 2, 1, 'Area 1_BSES.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(72, 3, 10, 2, 2, 'Area 2_BSES.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(73, 3, 10, 2, 3, 'Area 3_BSES.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(74, 3, 10, 2, 4, 'Area 4_BSES.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(75, 3, 10, 2, 5, 'Area 5_BSES.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(76, 3, 10, 2, 6, 'Area 6_BSES.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(77, 3, 10, 2, 7, 'Area 7_BSES.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(78, 3, 10, 2, 8, 'Area 8_BSES.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(79, 3, 10, 2, 9, 'Area 9_BSES.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(80, 3, 10, 2, 10, 'Area 10_BSES.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(81, 3, 11, 2, 1, 'Area 1_BSESS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(82, 3, 11, 2, 2, 'Area 2_BSESS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(83, 3, 11, 2, 3, 'Area 3_BSESS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(84, 3, 11, 2, 4, 'Area 4_BSESS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(85, 3, 11, 2, 5, 'Area 5_BSESS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(86, 3, 11, 2, 6, 'Area 6_BSESS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(87, 3, 11, 2, 7, 'Area 7_BSESS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(88, 3, 11, 2, 8, 'Area 8_BSESS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(89, 3, 11, 2, 9, 'Area 9_BSESS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(90, 3, 11, 2, 10, 'Area 10_BSESS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(91, 2, 3, 1, 1, 'Area 1_BSA.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(92, 2, 3, 1, 2, 'Area 2_BSA.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(93, 2, 3, 1, 3, 'Area 3_BSA.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(94, 2, 3, 1, 4, 'Area 4_BSA.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(95, 2, 3, 1, 5, 'Area 5_BSA.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(96, 2, 3, 1, 6, 'Area 6_BSA.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(97, 2, 3, 1, 7, 'Area 7_BSA.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(98, 2, 3, 1, 8, 'Area 8_BSA.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(99, 2, 3, 1, 9, 'Area 9_BSA.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(100, 2, 3, 1, 10, 'Area 10_BSA.pdf', 'Compliance', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(101, 2, 4, 2, 1, 'Area 1_BSAIS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(102, 2, 4, 2, 2, 'Area 2_BSAIS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(103, 2, 4, 2, 3, 'Area 3_BSAIS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(104, 2, 4, 2, 4, 'Area 4_BSAIS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(105, 2, 4, 2, 5, 'Area 5_BSAIS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(106, 2, 4, 2, 6, 'Area 6_BSAIS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(107, 2, 4, 2, 7, 'Area 7_BSAIS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(108, 2, 4, 2, 8, 'Area 8_BSAIS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(109, 2, 4, 2, 9, 'Area 9_BSAIS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(110, 2, 4, 2, 10, 'Area 10_BSAIS.pdf', 'Compliance', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-19 00:31:54'),
(111, 1, 1, 1, 1, 'Area 1_BSIT.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(112, 1, 1, 1, 2, 'Area 2_BSIT.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(113, 1, 1, 1, 3, 'Area 3_BSIT.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(114, 1, 1, 1, 4, 'Area 4_BSIT.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(115, 1, 1, 1, 5, 'Area 5_BSIT.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(116, 1, 1, 1, 6, 'Area 6_BSIT.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(117, 1, 1, 1, 7, 'Area 7_BSIT.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(118, 1, 1, 1, 8, 'Area 8_BSIT.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(119, 1, 1, 1, 9, 'Area 9_BSIT.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(120, 1, 1, 1, 10, 'Area 10_BSIT.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(121, 1, 2, 1, 1, 'Area 1_BSCS.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(122, 1, 2, 1, 2, 'Area 2_BSCS.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(123, 1, 2, 1, 3, 'Area 3_BSCS.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(124, 1, 2, 1, 4, 'Area 4_BSCS.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(125, 1, 2, 1, 5, 'Area 5_BSCS.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(126, 1, 2, 1, 6, 'Area 6_BSCS.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(127, 1, 2, 1, 7, 'Area 7_BSCS.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(128, 1, 2, 1, 8, 'Area 8_BSCS.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(129, 1, 2, 1, 9, 'Area 9_BSCS.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(130, 1, 2, 1, 10, 'Area 10_BSCS.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(131, 3, 5, 1, 1, 'Area 1_BEED.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(132, 3, 5, 1, 2, 'Area 2_BEED.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(133, 3, 5, 1, 3, 'Area 3_BEED.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(134, 3, 5, 1, 4, 'Area 4_BEED.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(135, 3, 5, 1, 5, 'Area 5_BEED.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(136, 3, 5, 1, 6, 'Area 6_BEED.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(137, 3, 5, 1, 7, 'Area 7_BEED.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(138, 3, 5, 1, 8, 'Area 8_BEED.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(139, 3, 5, 1, 9, 'Area 9_BEED.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(140, 3, 5, 1, 10, 'Area 10_BEED.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(141, 3, 7, 1, 1, 'Area 1_BSEE.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(142, 3, 7, 1, 2, 'Area 2_BSEE.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(143, 3, 7, 1, 3, 'Area 3_BSEE.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(144, 3, 7, 1, 4, 'Area 4_BSEE.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(145, 3, 7, 1, 5, 'Area 5_BSEE.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(146, 3, 7, 1, 6, 'Area 6_BSEE.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(147, 3, 7, 1, 7, 'Area 7_BSEE.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(148, 3, 7, 1, 8, 'Area 8_BSEE.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(149, 3, 7, 1, 9, 'Area 9_BSEE.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(150, 3, 7, 1, 10, 'Area 10_BSEE.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(151, 3, 8, 1, 1, 'Area 1_BSEM.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(152, 3, 8, 1, 2, 'Area 2_BSEM.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(153, 3, 8, 1, 3, 'Area 3_BSEM.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(154, 3, 8, 1, 4, 'Area 4_BSEM.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(155, 3, 8, 1, 5, 'Area 5_BSEM.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(156, 3, 8, 1, 6, 'Area 6_BSEM.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(157, 3, 8, 1, 7, 'Area 7_BSEM.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(158, 3, 8, 1, 8, 'Area 8_BSEM.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(159, 3, 8, 1, 9, 'Area 9_BSEM.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(160, 3, 8, 1, 10, 'Area 10_BSEM.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(161, 3, 9, 2, 1, 'Area 1_BSEF.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(162, 3, 9, 2, 2, 'Area 2_BSEF.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(163, 3, 9, 2, 3, 'Area 3_BSEF.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(164, 3, 9, 2, 4, 'Area 4_BSEF.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(165, 3, 9, 2, 5, 'Area 5_BSEF.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(166, 3, 9, 2, 6, 'Area 6_BSEF.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(167, 3, 9, 2, 7, 'Area 7_BSEF.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(168, 3, 9, 2, 8, 'Area 8_BSEF.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(169, 3, 9, 2, 9, 'Area 9_BSEF.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(170, 3, 9, 2, 10, 'Area 10_BSEF.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(171, 3, 10, 2, 1, 'Area 1_BSES.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(172, 3, 10, 2, 2, 'Area 2_BSES.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(173, 3, 10, 2, 3, 'Area 3_BSES.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(174, 3, 10, 2, 4, 'Area 4_BSES.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(175, 3, 10, 2, 5, 'Area 5_BSES.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(176, 3, 10, 2, 6, 'Area 6_BSES.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(177, 3, 10, 2, 7, 'Area 7_BSES.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(178, 3, 10, 2, 8, 'Area 8_BSES.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(179, 3, 10, 2, 9, 'Area 9_BSES.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(180, 3, 10, 2, 10, 'Area 10_BSES.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:26'),
(181, 3, 6, 2, 1, 'Area 1_BECE.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(182, 3, 6, 2, 2, 'Area 2_BECE.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(183, 3, 6, 2, 3, 'Area 3_BECE.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(184, 3, 6, 2, 4, 'Area 4_BECE.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(185, 3, 6, 2, 5, 'Area 5_BECE.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(186, 3, 6, 2, 6, 'Area 6_BECE.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(187, 3, 6, 2, 7, 'Area 7_BECE.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(188, 3, 6, 2, 8, 'Area 8_BECE.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(189, 3, 6, 2, 9, 'Area 9_BECE.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(190, 3, 6, 2, 10, 'Area 10_BECE.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(191, 3, 11, 2, 1, 'Area 1_BSESS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(192, 3, 11, 2, 2, 'Area 2_BSESS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(193, 3, 11, 2, 3, 'Area 3_BSESS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(194, 3, 11, 2, 4, 'Area 4_BSESS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(195, 3, 11, 2, 5, 'Area 5_BSESS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(196, 3, 11, 2, 6, 'Area 6_BSESS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(197, 3, 11, 2, 7, 'Area 7_BSESS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(198, 3, 11, 2, 8, 'Area 8_BSESS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(199, 3, 11, 2, 9, 'Area 9_BSESS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(200, 3, 11, 2, 10, 'Area 10_BSESS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(201, 2, 3, 1, 1, 'Area 1_BSA.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(202, 2, 3, 1, 2, 'Area 2_BSA.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(203, 2, 3, 1, 3, 'Area 3_BSA.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(204, 2, 3, 1, 4, 'Area 4_BSA.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(205, 2, 3, 1, 5, 'Area 5_BSA.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(206, 2, 3, 1, 6, 'Area 6_BSA.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(207, 2, 3, 1, 7, 'Area 7_BSA.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(208, 2, 3, 1, 8, 'Area 8_BSA.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(209, 2, 3, 1, 9, 'Area 9_BSA.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(210, 2, 3, 1, 10, 'Area 10_BSA.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(211, 2, 4, 2, 1, 'Area 1_BSAIS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(212, 2, 4, 2, 2, 'Area 2_BSAIS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(213, 2, 4, 2, 3, 'Area 3_BSAIS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(214, 2, 4, 2, 4, 'Area 4_BSAIS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(215, 2, 4, 2, 5, 'Area 5_BSAIS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(216, 2, 4, 2, 6, 'Area 6_BSAIS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(217, 2, 4, 2, 7, 'Area 7_BSAIS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(218, 2, 4, 2, 8, 'Area 8_BSAIS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(219, 2, 4, 2, 9, 'Area 9_BSAIS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(220, 2, 4, 2, 10, 'Area 10_BSAIS.pdf', 'Self-survey', 'Hello, World!', 'Pending', 'Pending', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, '', '2025-12-20 01:57:45'),
(221, 4, 12, 2, 1, 'Self-survey_Area-1_DAS_Psychology.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '1234-12345', 2, 'Maliii', '2025-12-20 01:57:45'),
(222, 4, 12, 2, 2, 'Self-survey_Area-2_DAS_Psychology.pdf', 'Self-survey', 'Hello, World!', 'Approved', 'Approved', 'Pending', 'Pending', 'Accepted', '1234-12345', 2, 'awdaw', '2025-12-20 01:57:45'),
(223, 4, 12, 2, 1, 'Compliance_Area-1_DAS_Psychology.pdf', 'Compliance', 'Hihi hi', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '3333-33333', 2, '', '2026-01-07 05:09:16'),
(224, 4, 12, 2, 2, 'Compliance_Area-2_DAS_Psychology.pdf', 'Compliance', 'Test123', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '3333-33333', 2, 'wadwa', '2026-01-15 01:50:40'),
(225, 4, 12, 2, 3, 'Compliance_Area-3_DAS_Psychology.pdf', 'Compliance', 'Test', 'Approved', 'Approved', 'Approved', 'Approved', 'Accepted', '3333-33333', 2, 'adaw', '2026-01-15 02:39:23');

CREATE TABLE extension_roles (
  id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (user_id) REFERENCES edocs_users(id)
);

CREATE TABLE planning_roles (
  id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  FOREIGN KEY (user_id) REFERENCES edocs_users(id)
);

CREATE TABLE signatures (
  id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  signature_file text NOT NULL,
  created_at timestamp not null default current_timestamp(),
  FOREIGN KEY (user_id) REFERENCES edocs_users(id)
)