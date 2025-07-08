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
?> 