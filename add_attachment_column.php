<?php
// add_attachment_column.php
// Run this script once to add the 'attachment' column to Content table if not exists

$dbFile = 'database.db';
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if 'attachment' column exists
    $result = $pdo->query("PRAGMA table_info(Content);");
    $hasAttachment = false;
    foreach ($result as $col) {
        if (strtolower($col['name']) === 'attachment') {
            $hasAttachment = true;
            break;
        }
    }
    if (!$hasAttachment) {
        $pdo->exec("ALTER TABLE Content ADD COLUMN attachment TEXT;");
        echo "Column 'attachment' added successfully.\n";
    } else {
        echo "Column 'attachment' already exists.\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

// Show all statuses before
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Statuses before:\n";
    $rows = $pdo->query("SELECT status_id, status_name FROM Status ORDER BY status_name")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo $row['status_id'] . ': ' . $row['status_name'] . "\n";
    }
    // Remove duplicate status values
    $pdo->exec("DELETE FROM Status WHERE rowid NOT IN (SELECT MIN(rowid) FROM Status GROUP BY status_name);");
    echo "Duplicate statuses removed.\n";
    // Show all statuses after
    echo "Statuses after:\n";
    $rows = $pdo->query("SELECT status_id, status_name FROM Status ORDER BY status_name")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo $row['status_id'] . ': ' . $row['status_name'] . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
} 