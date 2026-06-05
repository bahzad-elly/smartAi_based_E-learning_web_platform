<?php
require_once __DIR__ . '/config/db.php';
try {
    // Add shuffle_questions to quizzes if not exists
    $conn->exec("ALTER TABLE quizzes ADD COLUMN shuffle_questions tinyint(1) NOT NULL DEFAULT 0");
    echo "shuffle_questions column: Added OK\n";
} catch(Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "shuffle_questions column: Already exists\n";
    } else {
        echo "shuffle_questions: " . $e->getMessage() . "\n";
    }
}

try {
    // Add quiz_id to certificates if not exists
    $conn->exec("ALTER TABLE certificates ADD COLUMN quiz_id varchar(20) DEFAULT NULL");
    echo "quiz_id column in certificates: Added OK\n";
} catch(Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "quiz_id column: Already exists\n";
    } else {
        echo "quiz_id: " . $e->getMessage() . "\n";
    }
}

// Show tables
$tables = $conn->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in DB: " . implode(', ', $tables) . "\n";
echo "Migration done!\n";
?>
