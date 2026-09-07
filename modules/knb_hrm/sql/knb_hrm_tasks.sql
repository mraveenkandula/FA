-- Employee task management - confirmed against TechCloud's live HRM menu
-- (Employee Task Management / Employee Task Management Inquiry) and
-- independently confirmed as an actively-used feature via the Godavari24.apk
-- mobile app (TaskManagementScreen, AddTaskScreen, UpdateTaskScreen,
-- add_employee_task.php/get_employee_tasks.php/update_empl_task.php).

CREATE TABLE IF NOT EXISTS `0_knb_employee_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assigned_to` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'Medium' COMMENT 'Low, Medium, High',
  `status` varchar(20) NOT NULL DEFAULT 'Pending' COMMENT 'Pending, In Progress, Completed, Cancelled',
  `remarks` varchar(255) DEFAULT NULL,
  `created_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `assigned_by` (`assigned_by`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
