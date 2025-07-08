<?php
// import.php: Import content from CSV
$dbFile = 'database.db';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$statuses = $pdo->query('SELECT status_id, status_name FROM Status')->fetchAll(PDO::FETCH_KEY_PAIR);

$imported = 0;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvfile'])) {
    if ($_FILES['csvfile']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csvfile']['tmp_name'];
        $handle = fopen($file, 'r');
        if ($handle) {
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                // Map status name to status_id
                $status_id = array_search($data['Status'], $statuses);
                if (!$status_id) continue;
                $stmt = $pdo->prepare('INSERT INTO Content (title, description, status_id, deadline, attachment) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([
                    $data['Title'] ?? '',
                    $data['Description'] ?? '',
                    $status_id,
                    $data['Deadline'] ?? '',
                    $data['Attachment'] ?? null,
                ]);
                $imported++;
            }
            fclose($handle);
        } else {
            $error = 'Failed to open uploaded file.';
        }
    } else {
        $error = 'File upload error.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Content</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4">Import Content from CSV</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
    <?php if ($imported): ?>
        <div class="alert alert-success">Imported <?= $imported ?> records successfully.</div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="mb-4">
        <div class="mb-3">
            <label for="csvfile" class="form-label">CSV File</label>
            <input type="file" class="form-control" id="csvfile" name="csvfile" accept=".csv" required>
        </div>
        <button type="submit" class="btn btn-primary">Import</button>
        <a href="index.php" class="btn btn-secondary ms-2">Back</a>
    </form>
    <div class="alert alert-info">
        <b>CSV Columns:</b> Title, Description, Status, Deadline, Attachment<br>
        Status must match one of: <?= implode(', ', array_values($statuses)) ?>
    </div>
</div>
</body>
</html> 