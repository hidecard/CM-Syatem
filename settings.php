

<?php
// Settings file
$settingsFile = 'settings.json';
$notification_days = 1;
$github_message = '';

// Load current setting
if (file_exists($settingsFile)) {
    $data = json_decode(file_get_contents($settingsFile), true);
    if (isset($data['notification_days'])) {
        $notification_days = (int)$data['notification_days'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_days = max(1, min(30, (int)($_POST['notification_days'] ?? 1)));
    file_put_contents($settingsFile, json_encode(['notification_days' => $notification_days]));
    $saved = true;

    // GitHub Backup
    if (isset($_POST['github_backup'])) {
        $output = [];
        $return_var = 0;
        exec('git add . && git commit -m "Backup from UI" && git push origin main 2>&1', $output, $return_var);
        if ($return_var === 0) {
            $github_message = "Backup to GitHub successful!";
        } else {
            $github_message = "Backup failed: " . implode('<br>', $output);
        }
    }
    // GitHub Restore
    if (isset($_POST['github_restore'])) {
        $output = [];
        $return_var = 0;
        exec('git pull origin main 2>&1', $output, $return_var);
        if ($return_var === 0) {
            $github_message = "Restore from GitHub successful!";
        } else {
            $github_message = "Restore failed: " . implode('<br>', $output);
        }
    }
}
?> <?php
// Settings file
$settingsFile = 'settings.json';
$notification_days = 1;

// Load current setting
if (file_exists($settingsFile)) {
    $data = json_decode(file_get_contents($settingsFile), true);
    if (isset($data['notification_days'])) {
        $notification_days = (int)$data['notification_days'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_days = max(1, min(30, (int)($_POST['notification_days'] ?? 1)));
    file_put_contents($settingsFile, json_encode(['notification_days' => $notification_days]));
    $saved = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Segoe UI', 'Inter', 'Noto Sans', Arial, sans-serif;
            background: #f6f8fb;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        .app-header {
            width: 100vw;
            background: linear-gradient(90deg, #1976d2 60%, #42a5f5 100%);
            box-shadow: 0 2px 12px 0 rgba(31,38,135,0.08);
            padding: 0.7rem 0 0.7rem 0;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #1976d2;
            z-index: 10;
            position: sticky;
            top: 0;
        }
        .app-header-left {
            display: flex;
            align-items: center;
            gap: 1.1rem;
            margin-left: 2vw;
        }
        .app-header .app-icon {
            width: 36px;
            height: 36px;
        }
        .app-header .app-title {
            font-size: 1.7rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 8px rgba(31,38,135,0.10);
        }
        .main-content {
            max-width: 95vw;
            margin: 2.5rem auto 0 auto;
            padding: 0 2vw;
            display: flex;
            flex-direction: column;
            gap: 2.2rem;
            align-items: center;
        }
        .form-area {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 16px 0 rgba(31,38,135,0.10);
            border: 2px solid #d0d7e3;
            padding: 2.2rem 2.2rem 1.2rem 2.2rem;
            max-width: 95%;
            width: 100%;
            margin: 0 auto;
        }
        .form-title {
            font-size: 1.25rem;
            font-weight: 900;
            color: #1976d2;
            margin-bottom: 1.5rem;
            letter-spacing: 0.5px;
            text-align: center;
        }
        .form-actions {
            display: flex;
            gap: 1.2rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        .btn {
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.1em;
            padding: 0.6em 1.4em;
            box-shadow: 0 2px 8px 0 rgba(31,38,135,0.04);
            transition: background 0.15s, color 0.15s, box-shadow 0.15s, transform 0.1s;
        }
        .btn-save {
            background: #1976d2;
            color: #fff;
            border: none;
        }
        .btn-save:hover, .btn-save:focus {
            background: #1565c0;
            color: #fff;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 16px 0 rgba(31,38,135,0.13);
        }
        .alert-success {
            font-size: 1.1em;
            font-weight: 600;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="app-header">
        <div class="app-header-left">
            <span class="app-icon">
                <!-- ... existing SVG ... -->
            </span>
            <span class="app-title">Content Hub</span>
        </div>
    </div>
    <div class="main-content">
        <div class="form-area">
            <div class="form-title">Settings</div>
            <?php if (!empty($saved)): ?>
                <div class="alert alert-success mb-3">Settings saved!</div>
            <?php endif; ?>
            <?php if (!empty($github_message)): ?>
                <div class="alert alert-info mb-3"><?= $github_message ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="mb-3">
                    <label for="notification_days" class="form-label">Notify me before deadline (days)</label>
                    <input type="number" min="1" max="30" class="form-control" id="notification_days" name="notification_days" value="<?= htmlspecialchars($notification_days) ?>" required>
                </div>
                <div class="form-actions">
                    <button class="btn btn-save px-4 fw-bold" type="submit">
                        <i class="bi bi-save me-1"></i> Save
                    </button>
                </div>
            </form>
            <form method="post" style="margin-top:20px;">
                <button class="btn btn-warning w-100" name="github_backup" type="submit">
                    <i class="bi bi-cloud-upload me-2"></i> Backup to GitHub
                </button>
            </form>
            <form method="post" style="margin-top:10px;">
                <button class="btn btn-info w-100" name="github_restore" type="submit">
                    <i class="bi bi-cloud-download me-2"></i> Restore from GitHub
                </button>
            </form>
            <a href="backup.php" class="btn btn-warning w-100 mt-3 d-flex align-items-center justify-content-center" style="font-size:1.1em;">
                <i class="bi bi-download me-2"></i> Full Backup
            </a>
            <a href="index.php" class="btn btn-primary w-100 mt-3 d-flex align-items-center justify-content-center" style="font-size:1.1em;">
                <i class="bi bi-arrow-left-circle me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 