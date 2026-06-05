-- ============================================================
-- Smart AI E-Learning – Migration: Quiz & Certificate Tables
-- Run this in phpMyAdmin or MySQL to add missing columns
-- ============================================================

-- Add shuffle_questions column to quizzes table
ALTER TABLE `quizzes`
    ADD COLUMN IF NOT EXISTS `shuffle_questions` tinyint(1) NOT NULL DEFAULT 0;

-- Add quiz_id to certificates table (links cert to quiz instead of generic course)
ALTER TABLE `certificates`
    ADD COLUMN IF NOT EXISTS `quiz_id` varchar(20) DEFAULT NULL AFTER `course_id`,
    ADD KEY IF NOT EXISTS `fk_certs_quizzes` (`quiz_id`);

-- Add foreign key if not exists (MySQL 8+)
-- ALTER TABLE `certificates`
--     ADD CONSTRAINT `fk_certs_quizzes` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

-- Verify structure
DESCRIBE `quizzes`;
DESCRIBE `certificates`;
