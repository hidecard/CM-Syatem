<?php
// export.php: Export all content as CSV
$dbFile = 'database.db';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=content_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');
// CSV header
fputcsv($output, ['Title', 'Description', 'Status', 'Deadline', 'Attachment', 'Created At', 'Updated At']);

$sql = 'SELECT c.title, c.description, s.status_name, c.deadline, c.attachment, c.created_at, c.updated_at FROM Content c JOIN Status s ON c.status_id = s.status_id ORDER BY c.created_at DESC';
foreach ($pdo->query($sql) as $row) {
    fputcsv($output, [
        $row['title'],
        $row['description'],
        $row['status_name'],
        $row['deadline'],
        $row['attachment'],
        $row['created_at'],
        $row['updated_at'],
    ]);
}
fclose($output);
exit; 