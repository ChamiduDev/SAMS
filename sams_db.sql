-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 22, 2025 at 06:45 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sams_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late') NOT NULL,
  `marked_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `subject_id`, `date`, `status`, `marked_by`, `created_at`) VALUES
(39, 11, 12, '2025-08-21', 'present', 2, '2025-08-21 09:11:44'),
(40, 11, 13, '2025-08-21', 'absent', 2, '2025-08-21 09:11:51'),
(41, 12, 17, '2025-08-22', 'present', 2, '2025-08-22 03:41:02'),
(42, 12, 16, '2025-08-22', 'late', 2, '2025-08-22 03:41:12'),
(43, 12, 14, '2025-08-22', 'absent', 2, '2025-08-22 03:41:19'),
(46, 12, 15, '2025-08-22', 'present', 2, '2025-08-22 03:44:21');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `name`, `code`, `description`, `created_at`) VALUES
(8, 'Graphic Design', 'GD001', 'Boost your creative skills by joining our Graphic Design Course! \r\nLearn Adobe Photoshop and CorelDRAW hands-on and bring your ideas to life.\r\n\r\nCreative Designs & Editing\r\n\r\nLogo & Flyer Designing\r\n\r\nProfessional Tips & Tricks\r\n\r\nPerfect for beginners, hobbyists, and anyone looking to enhance their professional design skills.', '2025-08-21 09:07:36'),
(9, 'Web Development', 'WD001', 'Learn to build modern, responsive, and dynamic websites using HTML, CSS, JavaScript, and frameworks with hands-on projects.', '2025-08-22 01:52:46');

-- --------------------------------------------------------

--
-- Table structure for table `course_subjects`
--

CREATE TABLE `course_subjects` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `duration` int(11) DEFAULT 60 COMMENT 'Duration in minutes',
  `total_marks` int(11) NOT NULL,
  `weight` decimal(5,2) DEFAULT 100.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `title`, `subject_id`, `exam_date`, `duration`, `total_marks`, `weight`, `created_by`, `created_at`) VALUES
(10, 'Photoshop Exam', 13, '2025-08-21', 60, 100, 100.00, 2, '2025-08-21 09:14:00'),
(11, 'CorelDRAW Exam', 12, '2025-08-21', 60, 100, 100.00, 2, '2025-08-21 09:14:41'),
(13, 'Backend & Database Exam', 17, '2025-08-22', 60, 100, 100.00, 2, '2025-08-22 03:50:29'),
(14, 'HTML & CSS Fundamentals Exam', 14, '2025-08-22', 60, 100, 100.00, 2, '2025-08-22 03:51:04'),
(15, 'Frontend Frameworks Exam', 16, '2025-08-23', 60, 100, 100.00, 2, '2025-08-22 03:51:31'),
(16, 'JavaScript Programming exam', 15, '2025-08-23', 60, 100, 100.00, 2, '2025-08-22 03:51:55');

-- --------------------------------------------------------

--
-- Table structure for table `exam_components`
--

CREATE TABLE `exam_components` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `max_marks` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `marks_obtained` decimal(7,2) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_results`
--

INSERT INTO `exam_results` (`id`, `exam_id`, `student_id`, `marks_obtained`, `remarks`, `marked_by`, `created_at`) VALUES
(16, 10, 11, 80.00, 'Superb', 2, '2025-08-22 03:54:11'),
(17, 10, 12, 100.00, 'Superb', 2, '2025-08-22 03:54:11'),
(18, 11, 11, 60.00, 'Work Hard', 2, '2025-08-22 03:54:33'),
(19, 11, 12, 68.00, 'good', 16, '2025-08-22 03:54:33');

-- --------------------------------------------------------

--
-- Table structure for table `fee_payments`
--

CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL,
  `student_fee_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `method` enum('cash','card','bank_transfer','online') DEFAULT 'cash',
  `reference` varchar(150) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_payments`
--

INSERT INTO `fee_payments` (`id`, `student_fee_id`, `amount_paid`, `payment_date`, `method`, `reference`, `created_by`, `created_at`) VALUES
(6, 9, 5000.00, '2025-08-21', 'cash', '', 2, '2025-08-21 11:18:51'),
(7, 10, 5000.00, '2025-08-21', 'cash', '', 2, '2025-08-21 11:20:18'),
(8, 12, 1000.00, '2025-08-22', 'bank_transfer', '', 2, '2025-08-22 04:01:41');

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `type_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `applicable_class` varchar(50) DEFAULT NULL,
  `applicable_year` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_structures`
--

INSERT INTO `fee_structures` (`id`, `title`, `type_id`, `amount`, `applicable_class`, `applicable_year`, `created_at`) VALUES
(3, 'Graphic design Course Monthly payments', 3, 5000.00, NULL, 2025, '2025-08-21 09:23:37'),
(4, 'Exam Fees', 4, 1000.00, NULL, 2025, '2025-08-22 04:00:42');

-- --------------------------------------------------------

--
-- Table structure for table `fee_types`
--

CREATE TABLE `fee_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_types`
--

INSERT INTO `fee_types` (`id`, `name`, `description`, `created_at`) VALUES
(3, 'Monthly Fees', 'Rs.5000 from each Student', '2025-08-21 09:21:08'),
(4, 'Exam Fees', 'Semester Exam fees', '2025-08-22 04:00:07');

-- --------------------------------------------------------

--
-- Table structure for table `grading_scale`
--

CREATE TABLE `grading_scale` (
  `id` int(11) NOT NULL,
  `min_percent` decimal(5,2) NOT NULL,
  `max_percent` decimal(5,2) NOT NULL,
  `grade_label` varchar(10) NOT NULL,
  `grade_point` decimal(3,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grading_scale`
--

INSERT INTO `grading_scale` (`id`, `min_percent`, `max_percent`, `grade_label`, `grade_point`) VALUES
(2, 75.00, 85.00, 'A+', 4.00);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `target_role` varchar(50) DEFAULT 'all',
  `target_user_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`, `target_role`, `target_user_id`, `created_by`, `type`) VALUES
(12, NULL, 'Today Exam', 'The exam is scheduled for 10 o’clock.', 0, '2025-08-22 04:04:03', 'student', NULL, 2, 'exam'),
(13, NULL, 'Exam Fee Reminder', 'Please pay your pending exam fees as soon as possible to avoid any issues with exam registration.', 0, '2025-08-22 04:06:56', 'student', NULL, 2, 'fees'),
(14, NULL, 'Attendance Update', 'Reminder: Please submit your class attendance for this week by Friday.', 0, '2025-08-22 04:31:38', 'teacher', NULL, 2, 'attendance');

-- --------------------------------------------------------

--
-- Table structure for table `notification_reads`
--

CREATE TABLE `notification_reads` (
  `id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_reads`
--

INSERT INTO `notification_reads` (`id`, `notification_id`, `user_id`) VALUES
(2, 1, 2),
(30, 1, 7),
(13, 1, 9),
(1, 2, 2),
(29, 2, 7),
(12, 2, 9),
(8, 3, 2),
(28, 3, 7),
(7, 4, 2),
(27, 4, 7),
(6, 5, 2),
(26, 5, 7),
(3, 6, 2),
(11, 6, 9),
(14, 7, 2),
(22, 8, 2),
(25, 8, 7),
(60, 9, 2),
(24, 9, 7),
(23, 10, 2),
(89, 11, 2),
(91, 11, 14),
(90, 11, 17),
(95, 12, 2),
(96, 12, 18),
(97, 13, 18),
(98, 14, 16);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`, `category`, `created_at`) VALUES
(1, 'dashboard_view', 'View dashboard', 'Dashboard', '2025-08-17 11:31:30'),
(2, 'students_add', 'Add new students', 'Students', '2025-08-17 11:31:30'),
(3, 'students_list', 'View list of students', 'Students', '2025-08-17 11:31:30'),
(4, 'students_edit', 'Edit student information', 'Students', '2025-08-17 11:31:30'),
(5, 'students_delete', 'Delete students', 'Students', '2025-08-17 11:31:30'),
(6, 'students_view', 'View student details', 'Students', '2025-08-17 11:31:30'),
(7, 'courses_add', 'Add new courses', 'Courses', '2025-08-17 11:31:30'),
(8, 'courses_list', 'View list of courses', 'Courses', '2025-08-17 11:31:30'),
(9, 'courses_edit', 'Edit course information', 'Courses', '2025-08-17 11:31:30'),
(10, 'courses_delete', 'Delete courses', 'Courses', '2025-08-17 11:31:30'),
(11, 'subjects_add', 'Add new subjects', 'Subjects', '2025-08-17 11:31:30'),
(12, 'subjects_list', 'View list of subjects', 'Subjects', '2025-08-17 11:31:30'),
(13, 'subjects_edit', 'Edit subject information', 'Subjects', '2025-08-17 11:31:30'),
(14, 'subjects_delete', 'Delete subjects', 'Subjects', '2025-08-17 11:31:30'),
(15, 'exams_add', 'Add new exams', 'Exams', '2025-08-17 11:31:30'),
(16, 'exams_list', 'View list of exams', 'Exams', '2025-08-17 11:31:30'),
(17, 'exams_edit', 'Edit exam information', 'Exams', '2025-08-17 11:31:30'),
(18, 'exams_delete', 'Delete exams', 'Exams', '2025-08-17 11:31:30'),
(19, 'exams_view', 'View exams', 'Exams', '2025-08-17 11:31:30'),
(20, 'grades_mark', 'Mark student grades', 'Grades', '2025-08-17 11:31:30'),
(21, 'grades_list', 'View list of grades', 'Grades', '2025-08-17 11:31:30'),
(22, 'grades_edit', 'Edit grades', 'Grades', '2025-08-17 11:31:30'),
(23, 'grades_delete', 'Delete grades', 'Grades', '2025-08-17 11:31:30'),
(24, 'attendance_mark', 'Mark student attendance', 'Attendance', '2025-08-17 11:31:30'),
(25, 'attendance_list', 'View attendance records', 'Attendance', '2025-08-17 11:31:30'),
(26, 'attendance_delete', 'Delete attendance records', 'Attendance', '2025-08-17 11:31:30'),
(27, 'attendance_report', 'View attendance reports', 'Attendance', '2025-08-17 11:31:30'),
(28, 'fees_assign', 'Assign fees to students', 'Fees', '2025-08-17 11:31:30'),
(29, 'fees_list', 'View list of fees', 'Fees', '2025-08-17 11:31:30'),
(30, 'fees_edit', 'Edit fee information', 'Fees', '2025-08-17 11:31:30'),
(31, 'fees_view', 'View fees', 'Fees', '2025-08-17 11:31:30'),
(32, 'fee_structures_add', 'Add fee structures', 'Fee Structures', '2025-08-17 11:31:30'),
(33, 'fee_structures_list', 'View fee structures', 'Fee Structures', '2025-08-17 11:31:30'),
(34, 'fee_types_add', 'Add fee types', 'Fee Types', '2025-08-17 11:31:30'),
(35, 'fee_types_list', 'View fee types', 'Fee Types', '2025-08-17 11:31:30'),
(36, 'payments_record', 'Record payments', 'Payments', '2025-08-17 11:31:30'),
(37, 'payments_list', 'View list of payments', 'Payments', '2025-08-17 11:31:30'),
(38, 'notifications_create', 'Create notifications', 'Notifications', '2025-08-17 11:31:30'),
(39, 'notifications_list', 'View list of notifications', 'Notifications', '2025-08-17 11:31:30'),
(40, 'notifications_view', 'View notifications', 'Notifications', '2025-08-17 11:31:30'),
(41, 'notifications_delete', 'Delete notifications', 'Notifications', '2025-08-17 11:31:30'),
(42, 'users_add', 'Add new users', 'Users', '2025-08-17 11:31:30'),
(43, 'users_list', 'View list of users', 'Users', '2025-08-17 11:31:30'),
(44, 'users_edit', 'Edit user information', 'Users', '2025-08-17 11:31:30'),
(45, 'users_delete', 'Delete users', 'Users', '2025-08-17 11:31:30'),
(46, 'roles_add', 'Add new roles', 'Roles', '2025-08-17 11:31:30'),
(47, 'roles_list', 'View list of roles', 'Roles', '2025-08-17 11:31:30'),
(48, 'roles_edit', 'Edit roles', 'Roles', '2025-08-17 11:31:30'),
(49, 'roles_delete', 'Delete roles', 'Roles', '2025-08-17 11:31:30'),
(50, 'settings_school_info', 'Manage school information', 'Settings', '2025-08-17 11:31:30'),
(51, 'settings_grading_scale', 'Manage grading scale', 'Settings', '2025-08-17 11:31:30'),
(52, 'grades_view', 'View grades', NULL, '2025-08-19 05:17:12'),
(53, 'attendance_view', 'View attendance', NULL, '2025-08-19 05:17:12'),
(54, 'profile_view', 'View profile', NULL, '2025-08-19 05:17:12');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'admin'),
(4, 'manager'),
(3, 'student'),
(2, 'teacher');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission`) VALUES
(1, 1, 'dashboard_view'),
(2, 1, 'students_add'),
(3, 1, 'students_list'),
(4, 1, 'students_edit'),
(5, 1, 'students_delete'),
(6, 1, 'courses_add'),
(7, 1, 'courses_list'),
(8, 1, 'courses_edit'),
(9, 1, 'courses_delete'),
(10, 1, 'subjects_add'),
(11, 1, 'subjects_list'),
(12, 1, 'subjects_edit'),
(13, 1, 'subjects_delete'),
(14, 1, 'exams_add'),
(15, 1, 'exams_list'),
(16, 1, 'exams_edit'),
(17, 1, 'exams_delete'),
(18, 1, 'grades_mark'),
(19, 1, 'grades_list'),
(20, 1, 'grades_edit'),
(21, 1, 'grades_delete'),
(22, 1, 'attendance_mark'),
(23, 1, 'attendance_list'),
(24, 1, 'attendance_delete'),
(25, 1, 'attendance_report'),
(26, 1, 'fees_assign'),
(27, 1, 'fees_list'),
(28, 1, 'fees_edit'),
(29, 1, 'fees_view'),
(30, 1, 'payments_record'),
(31, 1, 'payments_list'),
(32, 1, 'notifications_create'),
(33, 1, 'notifications_list'),
(34, 1, 'notifications_view'),
(35, 1, 'notifications_delete'),
(36, 1, 'users_add'),
(37, 1, 'users_list'),
(38, 1, 'users_edit'),
(39, 1, 'users_delete'),
(40, 1, 'roles_add'),
(41, 1, 'roles_list'),
(42, 1, 'roles_edit'),
(43, 1, 'roles_delete'),
(44, 1, 'settings_school_info'),
(45, 1, 'settings_grading_scale'),
(64, 1, 'exams_view'),
(65, 1, 'fee_structures_add'),
(66, 1, 'fee_structures_list'),
(67, 1, 'fee_types_add'),
(68, 1, 'fee_types_list'),
(69, 1, 'students_view'),
(524, 3, 'attendance_view'),
(525, 3, 'grades_view'),
(526, 3, 'profile_view'),
(527, 3, 'dashboard_view'),
(528, 3, 'exams_view'),
(529, 3, 'fees_view'),
(530, 3, 'notifications_create'),
(531, 3, 'notifications_delete'),
(532, 3, 'notifications_list'),
(533, 3, 'notifications_view'),
(567, 4, 'attendance_view'),
(568, 4, 'grades_view'),
(569, 4, 'profile_view'),
(570, 4, 'attendance_delete'),
(571, 4, 'attendance_list'),
(572, 4, 'attendance_mark'),
(573, 4, 'attendance_report'),
(574, 4, 'courses_add'),
(575, 4, 'courses_delete'),
(576, 4, 'courses_edit'),
(577, 4, 'courses_list'),
(578, 4, 'dashboard_view'),
(579, 4, 'exams_add'),
(580, 4, 'exams_delete'),
(581, 4, 'exams_edit'),
(582, 4, 'exams_list'),
(583, 4, 'exams_view'),
(584, 4, 'fee_structures_add'),
(585, 4, 'fee_structures_list'),
(586, 4, 'fee_types_add'),
(587, 4, 'fee_types_list'),
(588, 4, 'fees_assign'),
(589, 4, 'fees_edit'),
(590, 4, 'fees_list'),
(591, 4, 'fees_view'),
(592, 4, 'grades_delete'),
(593, 4, 'grades_edit'),
(594, 4, 'grades_list'),
(595, 4, 'grades_mark'),
(596, 4, 'notifications_create'),
(597, 4, 'notifications_delete'),
(598, 4, 'notifications_list'),
(599, 4, 'notifications_view'),
(600, 4, 'payments_list'),
(601, 4, 'payments_record'),
(602, 4, 'settings_grading_scale'),
(603, 4, 'settings_school_info'),
(604, 4, 'students_add'),
(605, 4, 'students_delete'),
(606, 4, 'students_edit'),
(607, 4, 'students_list'),
(608, 4, 'students_view'),
(609, 4, 'subjects_add'),
(610, 4, 'subjects_delete'),
(611, 4, 'subjects_edit'),
(612, 4, 'subjects_list'),
(747, 2, 'attendance_view'),
(748, 2, 'grades_view'),
(749, 2, 'profile_view'),
(750, 2, 'attendance_delete'),
(751, 2, 'attendance_list'),
(752, 2, 'attendance_mark'),
(753, 2, 'attendance_report'),
(754, 2, 'dashboard_view'),
(755, 2, 'exams_add'),
(756, 2, 'exams_delete'),
(757, 2, 'exams_edit'),
(758, 2, 'exams_list'),
(759, 2, 'exams_view'),
(760, 2, 'grades_delete'),
(761, 2, 'grades_edit'),
(762, 2, 'grades_list'),
(763, 2, 'grades_mark'),
(764, 2, 'notifications_list'),
(765, 2, 'notifications_view'),
(766, 2, 'students_add'),
(767, 2, 'students_delete'),
(768, 2, 'students_edit'),
(769, 2, 'students_list');

-- --------------------------------------------------------

--
-- Table structure for table `school_info`
--

CREATE TABLE `school_info` (
  `id` int(11) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `school_address` text DEFAULT NULL,
  `school_email` varchar(255) DEFAULT NULL,
  `school_phone` varchar(50) DEFAULT NULL,
  `school_logo` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_info`
--

INSERT INTO `school_info` (`id`, `school_name`, `school_address`, `school_email`, `school_phone`, `school_logo`, `updated_at`) VALUES
(1, 'Smart Academic Management System', 'school, city', 'samss@edu.com', '0764907961', '../assets/images/school_logo_1755672941.jpg', '2025-08-20 10:34:32');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_id` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('M','F','O') NOT NULL,
  `year` int(11) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_no` varchar(255) DEFAULT NULL,
  `status` enum('active','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `parent_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_id`, `first_name`, `last_name`, `dob`, `gender`, `year`, `address`, `contact_no`, `status`, `created_at`, `parent_user_id`) VALUES
(11, 14, '20250001', 'Chamindu', 'Kavishka', '2002-03-02', 'M', 2025, '123 Home, anuradhapura city', '0764907961', 'active', '2025-08-21 09:10:11', NULL),
(12, 18, '20250002', 'Kavindu', 'Perera', '2005-02-09', 'M', 2025, 'No. 45, Lake Road, Colombo 07', '0715849622', 'active', '2025-08-22 01:58:42', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_courses`
--

CREATE TABLE `student_courses` (
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_courses`
--

INSERT INTO `student_courses` (`student_id`, `course_id`) VALUES
(11, 8),
(12, 8),
(12, 9);

-- --------------------------------------------------------

--
-- Table structure for table `student_course_subjects`
--

CREATE TABLE `student_course_subjects` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_course_subjects`
--

INSERT INTO `student_course_subjects` (`id`, `student_id`, `course_id`, `subject_id`) VALUES
(7, 11, 8, 12),
(8, 11, 8, 13),
(9, 12, 8, 12),
(10, 12, 8, 13),
(13, 12, 9, 14),
(14, 12, 9, 15),
(12, 12, 9, 16),
(11, 12, 9, 17);

-- --------------------------------------------------------

--
-- Table structure for table `student_fees`
--

CREATE TABLE `student_fees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `structure_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `outstanding_amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','partial','paid','overdue','cancelled','void') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_fees`
--

INSERT INTO `student_fees` (`id`, `student_id`, `structure_id`, `total_amount`, `outstanding_amount`, `due_date`, `status`, `created_at`, `notes`) VALUES
(9, 11, 3, 5000.00, 0.00, '2025-08-21', 'paid', '2025-08-21 11:18:38', NULL),
(10, 11, 3, 5000.00, 0.00, '2025-07-09', 'paid', '2025-08-21 11:19:20', NULL),
(12, 12, 4, 1000.00, 0.00, '2025-08-22', 'paid', '2025-08-22 04:01:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_notifications`
--

CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `course_id`, `name`, `code`, `teacher_id`, `created_at`) VALUES
(12, 8, 'CorelDRAW', 'CD001', 13, '2025-08-21 09:08:18'),
(13, 8, 'Photoshop', 'PS001', 13, '2025-08-21 09:08:40'),
(14, 9, 'HTML & CSS Fundamentals', 'HC001', 16, '2025-08-22 01:53:47'),
(15, 9, 'JavaScript Programming', 'JS001', 16, '2025-08-22 01:54:16'),
(16, 9, 'Frontend Frameworks', 'FF001', 16, '2025-08-22 01:54:41'),
(17, 9, 'Backend & Database', 'DB001', 16, '2025-08-22 01:55:06');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `attendance_threshold` decimal(5,2) NOT NULL DEFAULT 75.00,
  `passing_grade` decimal(5,2) NOT NULL DEFAULT 50.00,
  `enable_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `enable_parent_portal` tinyint(1) NOT NULL DEFAULT 1,
  `session_timeout` int(11) NOT NULL DEFAULT 30,
  `default_timezone` varchar(100) NOT NULL DEFAULT 'UTC',
  `date_format` varchar(20) NOT NULL DEFAULT 'Y-m-d',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `attendance_threshold`, `passing_grade`, `enable_notifications`, `enable_parent_portal`, `session_timeout`, `default_timezone`, `date_format`, `created_at`, `updated_at`) VALUES
(1, 75.00, 50.00, 1, 1, 60, 'UTC', 'Y-m-d', '2025-08-20 06:26:11', '2025-08-20 10:34:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role_id`, `created_at`, `updated_at`) VALUES
(2, 'SuperAdmin', 'admin@admin.com', '$2y$10$wXVLqz58BxOSNr7PYLdCrO6e1v4TtydkwmyepbphIeTAgUS8OlUoa', 1, '2025-08-17 12:43:31', '2025-08-22 04:26:08'),
(13, 'maheshika', 'maheshika@gmail.com', '$2y$10$lXRTlfh2HBoW7.1mquuMOOfhME3AE9MUrPLqfLQMnxEJdPNDW/1KC', 2, '2025-08-21 09:04:34', '2025-08-21 09:04:34'),
(14, 'chamindu.kavishka', 'chamindu.kavishka@sams.edu', '$2y$10$BLGyYKQXlAoclLVtk9/I/.lCKN5fQqCbiNXTXOP4C58vdlci11W1q', 3, '2025-08-21 09:10:11', '2025-08-21 09:10:11'),
(16, 'john', 'jhon@gmail.com', '$2y$10$0jPTWhrsMaeYnHhT02uzmO.J.FDmBZuka/1gwl7FPlNl/OFg5u0Yi', 2, '2025-08-22 01:39:26', '2025-08-22 01:39:26'),
(17, 'manager', 'manager@gmail.com', '$2y$10$W4R3lk8cZFTq0jaDruEbluzoM4gWb3h9SfSPAUGjAIM6ulkHTK.0y', 4, '2025-08-22 01:47:05', '2025-08-22 01:47:05'),
(18, 'kavindu.perera', 'kavindu.perera@sams.edu', '$2y$10$G93Jdo1uGXKB4x2XeQLnhecIB6zsvJ8i/F1MoiXD4BldRdtmuLs62', 3, '2025-08-22 01:58:42', '2025-08-22 01:58:42');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `exam_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `attendance_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `fee_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_subject_date` (`student_id`,`subject_id`,`date`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `course_subjects`
--
ALTER TABLE `course_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_course_subject` (`course_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_exam_subject` (`subject_id`);

--
-- Indexes for table `exam_components`
--
ALTER TABLE `exam_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_id` (`exam_id`,`student_id`),
  ADD KEY `marked_by` (`marked_by`),
  ADD KEY `idx_result_student` (`student_id`);

--
-- Indexes for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_fee_payments_student_fee` (`student_fee_id`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `fee_types`
--
ALTER TABLE `fee_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `grading_scale`
--
ALTER TABLE `grading_scale`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_notification_user` (`notification_id`,`user_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_role_permissions_role` (`role_id`),
  ADD KEY `fk_role_permissions_permission` (`permission`);

--
-- Indexes for table `school_info`
--
ALTER TABLE `school_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_parent_user` (`parent_user_id`);

--
-- Indexes for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD PRIMARY KEY (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `student_course_subjects`
--
ALTER TABLE `student_course_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_course_subject` (`student_id`,`course_id`,`subject_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `structure_id` (`structure_id`),
  ADD KEY `idx_student_fees_student` (`student_id`);

--
-- Indexes for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_notification_student` (`notification_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role_id` (`role_id`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_preferences_user_id_unique` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `course_subjects`
--
ALTER TABLE `course_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `exam_components`
--
ALTER TABLE `exam_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fee_types`
--
ALTER TABLE `fee_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `grading_scale`
--
ALTER TABLE `grading_scale`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `notification_reads`
--
ALTER TABLE `notification_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=770;

--
-- AUTO_INCREMENT for table `school_info`
--
ALTER TABLE `school_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `student_course_subjects`
--
ALTER TABLE `student_course_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `student_fees`
--
ALTER TABLE `student_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `student_notifications`
--
ALTER TABLE `student_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_subjects`
--
ALTER TABLE `course_subjects`
  ADD CONSTRAINT `course_subjects_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exam_components`
--
ALTER TABLE `exam_components`
  ADD CONSTRAINT `exam_components_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_results_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD CONSTRAINT `fee_payments_ibfk_1` FOREIGN KEY (`student_fee_id`) REFERENCES `student_fees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD CONSTRAINT `fee_structures_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `fee_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission`) REFERENCES `permissions` (`name`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_parent_user` FOREIGN KEY (`parent_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD CONSTRAINT `student_courses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_course_subjects`
--
ALTER TABLE `student_course_subjects`
  ADD CONSTRAINT `student_course_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_course_subjects_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_course_subjects_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD CONSTRAINT `student_fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_ibfk_2` FOREIGN KEY (`structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD CONSTRAINT `student_notifications_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_notifications_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subjects_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
