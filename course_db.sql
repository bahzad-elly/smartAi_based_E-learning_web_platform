-- Smart AI-Based E-Learning Web Platform - Professional Database Schema Design
-- Target Database: `course_db`
-- Platform: MySQL / MariaDB (InnoDB Engine)

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Table structure for table `users` (Students)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL, -- Extended length for secure bcrypt hashes
  `image` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. Table structure for table `instructors` (Tutors)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `instructors`;
CREATE TABLE `instructors` (
  `id` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `profession` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `image` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 3. Table structure for table `admins` (Platform Admins)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'admin', -- 'superadmin', 'admin', 'moderator'
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Table structure for table `categories` (Course Categories)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL UNIQUE,
  `slug` varchar(50) NOT NULL UNIQUE,
  `icon` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 5. Table structure for table `playlists` (Playlists)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `playlists`;
CREATE TABLE `playlists` (
  `id` varchar(20) NOT NULL,
  `tutor_id` varchar(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `thumb` varchar(100) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'deactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_playlists_tutors` (`tutor_id`),
  CONSTRAINT `fk_playlists_tutors` FOREIGN KEY (`tutor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 6. Table structure for table `courses` (Courses)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `id` varchar(20) NOT NULL,
  `playlist_id` varchar(20) NOT NULL,
  `category_id` varchar(20) DEFAULT NULL,
  `level` varchar(20) NOT NULL DEFAULT 'beginner', -- 'beginner', 'intermediate', 'advanced'
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,      -- Supporting paid courses
  `status` varchar(20) NOT NULL DEFAULT 'draft',    -- 'draft', 'pending_approval', 'published'
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_courses_playlists` (`playlist_id`),
  KEY `fk_courses_categories` (`category_id`),
  CONSTRAINT `fk_courses_playlists` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_courses_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 7. Table structure for table `lessons` (Lesson Contents)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `lessons`;
CREATE TABLE `lessons` (
  `id` varchar(20) NOT NULL,
  `tutor_id` varchar(20) NOT NULL,
  `playlist_id` varchar(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `video` varchar(100) NOT NULL,
  `thumb` varchar(100) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'deactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_lessons_tutors` (`tutor_id`),
  KEY `fk_lessons_playlists` (`playlist_id`),
  CONSTRAINT `fk_lessons_tutors` FOREIGN KEY (`tutor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lessons_playlists` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 8. Table structure for table `comments` (Lesson Comments)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` varchar(20) NOT NULL,
  `lesson_id` varchar(20) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `tutor_id` varchar(20) NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_comments_lessons` (`lesson_id`),
  KEY `fk_comments_users` (`user_id`),
  KEY `fk_comments_tutors` (`tutor_id`),
  CONSTRAINT `fk_comments_lessons` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_tutors` FOREIGN KEY (`tutor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 9. Table structure for table `replies` (Comment Replies)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `replies`;
CREATE TABLE `replies` (
  `id` varchar(20) NOT NULL,
  `comment_id` varchar(20) NOT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `tutor_id` varchar(20) DEFAULT NULL,
  `reply` varchar(1000) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_replies_comments` (`comment_id`),
  KEY `fk_replies_users` (`user_id`),
  KEY `fk_replies_tutors` (`tutor_id`),
  CONSTRAINT `fk_replies_comments` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_replies_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_replies_tutors` FOREIGN KEY (`tutor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 10. Table structure for table `likes` (Lesson Likes)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `likes`;
CREATE TABLE `likes` (
  `user_id` varchar(20) NOT NULL,
  `tutor_id` varchar(20) NOT NULL,
  `lesson_id` varchar(20) NOT NULL,
  PRIMARY KEY (`user_id`, `lesson_id`), -- Composite PK prevents duplicate likes
  KEY `fk_likes_users` (`user_id`),
  KEY `fk_likes_tutors` (`tutor_id`),
  KEY `fk_likes_lessons` (`lesson_id`),
  CONSTRAINT `fk_likes_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_likes_tutors` FOREIGN KEY (`tutor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_likes_lessons` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 11. Table structure for table `bookmarks` (Saved Playlists)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `bookmarks`;
CREATE TABLE `bookmarks` (
  `user_id` varchar(20) NOT NULL,
  `playlist_id` varchar(20) NOT NULL,
  PRIMARY KEY (`user_id`, `playlist_id`),
  KEY `fk_bookmarks_users` (`user_id`),
  KEY `fk_bookmarks_playlists` (`playlist_id`),
  CONSTRAINT `fk_bookmarks_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookmarks_playlists` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 12. Table structure for table `quizzes` (Assessments)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `quizzes`;
CREATE TABLE `quizzes` (
  `id` varchar(20) NOT NULL,
  `course_id` varchar(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `time_limit` int(11) NOT NULL DEFAULT 0, -- Time limit in minutes
  `passing_score` int(11) NOT NULL DEFAULT 50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_quizzes_courses` (`course_id`),
  CONSTRAINT `fk_quizzes_courses` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 13. Table structure for table `questions` (Quiz Questions)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `questions`;
CREATE TABLE `questions` (
  `id` varchar(20) NOT NULL,
  `quiz_id` varchar(20) NOT NULL,
  `question_text` text NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'multiple_choice', -- 'multiple_choice', 'true_false'
  PRIMARY KEY (`id`),
  KEY `fk_questions_quizzes` (`quiz_id`),
  CONSTRAINT `fk_questions_quizzes` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 14. Table structure for table `answers` (Quiz Question Answers)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `answers`;
CREATE TABLE `answers` (
  `id` varchar(20) NOT NULL,
  `question_id` varchar(20) NOT NULL,
  `answer_text` text NOT NULL,
  `is_correct` boolean NOT NULL DEFAULT false,
  PRIMARY KEY (`id`),
  KEY `fk_answers_questions` (`question_id`),
  CONSTRAINT `fk_answers_questions` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 15. Table structure for table `exam_results` (Exam History)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `exam_results`;
CREATE TABLE `exam_results` (
  `id` varchar(20) NOT NULL,
  `quiz_id` varchar(20) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `score` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `status` varchar(20) NOT NULL, -- 'pass', 'fail'
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_results_quizzes` (`quiz_id`),
  KEY `fk_results_users` (`user_id`),
  CONSTRAINT `fk_results_quizzes` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_results_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 16. Table structure for table `certificates` (Student Credentials)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `certificates`;
CREATE TABLE `certificates` (
  `id` varchar(20) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `course_id` varchar(20) NOT NULL,
  `certificate_code` varchar(50) NOT NULL UNIQUE,
  `qr_hash` varchar(100) NOT NULL UNIQUE,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_certs_users` (`user_id`),
  KEY `fk_certs_courses` (`course_id`),
  CONSTRAINT `fk_certs_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_certs_courses` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 17. Table structure for table `notifications` (Real-time Alerts)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` varchar(20) NOT NULL,
  `user_id` varchar(20) DEFAULT NULL,       -- Target student (null for everyone)
  `tutor_id` varchar(20) DEFAULT NULL,      -- Target tutor
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'unread', -- 'unread', 'read'
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_notif_users` (`user_id`),
  KEY `fk_notif_tutors` (`tutor_id`),
  CONSTRAINT `fk_notif_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_tutors` FOREIGN KEY (`tutor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 18. Table structure for table `chats` (Chat Sessions)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `chats`;
CREATE TABLE `chats` (
  `id` varchar(20) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `tutor_id` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_tutor_chat` (`user_id`, `tutor_id`),
  CONSTRAINT `fk_chats_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chats_tutors` FOREIGN KEY (`tutor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 19. Table structure for table `messages` (Chat Messages)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` varchar(20) NOT NULL,
  `chat_id` varchar(20) NOT NULL,
  `sender_type` enum('user','tutor') NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_messages_chats` (`chat_id`),
  CONSTRAINT `fk_messages_chats` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 20. Table structure for table `reports` (Platform Analytics)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id` varchar(20) NOT NULL,
  `metric_name` varchar(50) NOT NULL, -- 'revenue', 'enrollments', 'registrations'
  `metric_value` decimal(10,2) NOT NULL,
  `reported_at` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 21. Table structure for table `logs` (Security & Audit Trail)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) DEFAULT NULL,
  `tutor_id` varchar(20) DEFAULT NULL,
  `action` varchar(100) NOT NULL, -- 'login', 'failed_login', 'delete_content', 'reset_password'
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_tutor` (`tutor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 22. Table structure for table `user_progress` (Lesson Watch Tracking)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `user_progress`;
CREATE TABLE `user_progress` (
  `user_id` varchar(20) NOT NULL,
  `lesson_id` varchar(20) NOT NULL,
  `is_completed` boolean NOT NULL DEFAULT false,
  `last_watch_position` int(11) NOT NULL DEFAULT 0, -- Watch timeline in seconds
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`, `lesson_id`),
  KEY `fk_progress_users` (`user_id`),
  KEY `fk_progress_lessons` (`lesson_id`),
  CONSTRAINT `fk_progress_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_lessons` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 23. Table structure for table `enrollments` (Course Student Registrations)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE `enrollments` (
  `user_id` varchar(20) NOT NULL,
  `course_id` varchar(20) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`, `course_id`),
  KEY `fk_enroll_users` (`user_id`),
  KEY `fk_enroll_courses` (`course_id`),
  CONSTRAINT `fk_enroll_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enroll_courses` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 24. Table structure for table `course_reviews` (Student Reviews & Feedback)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `course_reviews`;
CREATE TABLE `course_reviews` (
  `id` varchar(20) NOT NULL,
  `course_id` varchar(20) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_course_review` (`user_id`, `course_id`),
  KEY `fk_reviews_courses` (`course_id`),
  KEY `fk_reviews_users` (`user_id`),
  CONSTRAINT `fk_reviews_courses` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
