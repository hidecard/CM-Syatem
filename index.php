<?php
// Database connection
$dbFile = 'database.db';
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

// $pdo->exec("
// CREATE TABLE IF NOT EXISTS Status (
//     status_id INTEGER PRIMARY KEY AUTOINCREMENT,
//     status_name TEXT NOT NULL
// );

// CREATE TABLE IF NOT EXISTS Content (
//     content_id INTEGER PRIMARY KEY AUTOINCREMENT,
//     title TEXT NOT NULL,
//     description TEXT,
//     status_id INTEGER,
//     deadline TEXT,
//     attachment TEXT, -- NEW COLUMN
//     created_at TEXT DEFAULT CURRENT_TIMESTAMP,
//     updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
//     FOREIGN KEY (status_id) REFERENCES Status(status_id)
// );

// INSERT INTO Status (status_name) VALUES ('Draft'), ('Published'), ('Archived');
// ");


// Fetch content with status
$stmt = $pdo->query('SELECT c.content_id, c.title, c.description, s.status_name, c.deadline, c.attachment, c.created_at, c.updated_at FROM Content c JOIN Status s ON c.status_id = s.status_id ORDER BY c.created_at DESC');
$contents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load notification settings
$settingsFile = 'settings.json';
$notification_days = 1;
if (file_exists($settingsFile)) {
    $data = json_decode(file_get_contents($settingsFile), true);
    if (isset($data['notification_days'])) {
        $notification_days = (int)$data['notification_days'];
    }
}

// Find deadlines within notification window (exclude Published)
$today = new DateTime();
$notify_contents = [];
foreach ($contents as $row) {
    if (!empty($row['deadline']) && strtolower($row['status_name']) !== 'published') {
        $deadline = DateTime::createFromFormat('Y-m-d', $row['deadline']);
        if ($deadline) {
            $diff = (int)$today->diff($deadline)->format('%r%a');
            if ($diff >= 0 && $diff <= $notification_days) {
                $notify_contents[] = $row;
            }
        }
    }
}

// --- Search and Pagination ---
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$filtered = $contents;
if ($search !== '') {
    $filtered = array_filter($contents, function($row) use ($search) {
        return stripos($row['title'], $search) !== false || stripos($row['description'], $search) !== false;
    });
}
$total = count($filtered);
$totalPages = max(1, ceil($total / $perPage));
$filtered = array_slice(array_values($filtered), ($page-1)*$perPage, $perPage);
$from = $total ? ($page-1)*$perPage+1 : 0;
$to = min($from+$perPage-1, $total);

// --- Notification read state (JS will use localStorage) ---
$hasNoti = count($notify_contents) > 0 ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Content Hub</title>
    <link rel="icon" type="image/x-icon" href="data:image/x-icon;base64," />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
      body {
      font-family: 'Segoe UI', 'Inter', 'Noto Sans', Arial, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
      margin: 0;
      padding: 0;
    }
    .app-header {
      width: 100vw;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      box-shadow: 0 2px 12px 0 rgba(31,38,135,0.08);
      padding: 0.7rem 0 0.7rem 0;
      margin-bottom: 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 3px solid rgba(255,255,255,0.2);
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
      text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .app-header .header-action {
      margin-right: 2vw;
    }
    .btn {
      border-radius: 10px;
      font-weight: 700;
      font-size: 1.1em;
      padding: 0.6em 1.4em;
      box-shadow: 0 4px 12px 0 rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }
    .btn-primary {
      background: linear-gradient(45deg, #667eea, #764ba2);
      border: none;
      color: #fff;
    }
    .btn-primary:hover, .btn-primary:focus {
      background: linear-gradient(45deg, #5a6fd8, #6a4190);
      color: #fff;
      box-shadow: 0 6px 20px 0 rgba(102, 126, 234, 0.4);
      transform: translateY(-3px) scale(1.05);
    }
    .btn-outline-primary {
      color: #667eea;
      border: 2px solid #667eea;
      background: rgba(255, 255, 255, 0.9);
    }
    .btn-outline-primary:hover {
      background: linear-gradient(45deg, #667eea, #764ba2);
      color: #fff;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 6px 20px 0 rgba(102, 126, 234, 0.4);
    }
    .btn-outline-danger {
      color: #dc3545;
      border: 2px solid #dc3545;
      background: rgba(255, 255, 255, 0.9);
    }
    .btn-outline-danger:hover {
      background: linear-gradient(45deg, #dc3545, #e74c3c);
      color: #fff;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 6px 20px 0 rgba(220, 53, 69, 0.4);
    }
    .btn-outline-info {
      color: #17a2b8;
      border: 2px solid #17a2b8;
      background: rgba(255, 255, 255, 0.9);
    }
    .btn-outline-info:hover {
      background: linear-gradient(45deg, #17a2b8, #20c997);
      color: #fff;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 6px 20px 0 rgba(23, 162, 184, 0.4);
    }
    .main-content {
      max-width: 100%;
      margin: 2.5rem auto 0 auto;
      padding: 0 2vw;
      display: flex;
      flex-direction: column;
      gap: 2.2rem;
    }
    .table-area {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      box-shadow: 0 8px 32px 0 rgba(31,38,135,0.2);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 0.5rem 1.2rem 1.2rem 1.2rem;
    }
    .table-title-row th {
      font-size: 1rem;
      font-weight: 900;
      color: #2c3e50;
      background: linear-gradient(45deg, #667eea, #764ba2) !important;
      color: white !important;
      border-bottom: 2.5px solid rgba(255,255,255,0.3) !important;
      letter-spacing: 0.5px;
    }
    .table thead th {
      font-size: 1.08rem;
      font-weight: 700;
      background: rgba(102, 126, 234, 0.1);
      color: #2c3e50;
      border-bottom: 2px solid rgba(102, 126, 234, 0.2);
    }
    .table {
      border-radius: 8px;
      overflow: hidden;
      background: rgba(255, 255, 255, 0.8);
      margin-bottom: 0;
    }
    .table-striped > tbody > tr:nth-of-type(odd) {
      background-color: rgba(102, 126, 234, 0.05);
    }
    .table-hover tbody tr:hover, .table-hover tbody tr:focus {
      background-color: rgba(102, 126, 234, 0.1);
      cursor: pointer;
      transition: all 0.3s ease;
      outline: 2px solid #667eea;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    }
    .badge-status {
      font-size: 1em;
      padding: 0.5em 1em;
      border-radius: 1em;
      background: linear-gradient(45deg, #6c757d, #5a6268);
      color: #fff;
      font-weight: 600;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .badge-status.published {
      background: linear-gradient(45deg, #28a745, #20c997);
    }
    .badge-status.archived {
      background: linear-gradient(45deg, #dc3545, #e74c3c);
    }
    .action-btns .btn {
      font-size: 1.08em;
      padding: 0.45em 1.1em;
      border-radius: 8px;
      font-weight: 700;
      margin-right: 0.2em;
      box-shadow: 0 4px 12px 0 rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }
    .btn-view {
      background: linear-gradient(45deg, #667eea, #764ba2);
      color: #fff;
      border: none;
    }
    .btn-view:hover, .btn-view:focus {
      background: linear-gradient(45deg, #5a6fd8, #6a4190);
      color: #fff;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 6px 20px 0 rgba(102, 126, 234, 0.4);
    }
    .btn-edit {
      background: linear-gradient(45deg, #28a745, #20c997);
      color: #fff;
      border: none;
    }
    .btn-edit:hover, .btn-edit:focus {
      background: linear-gradient(45deg, #218838, #1e7e34);
      color: #fff;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 6px 20px 0 rgba(40, 167, 69, 0.4);
    }
    .btn-delete {
      background: linear-gradient(45deg, #dc3545, #e74c3c);
      color: #fff;
      border: none;
    }
    .btn-delete:hover, .btn-delete:focus {
      background: linear-gradient(45deg, #c82333, #bd2130);
      color: #fff;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 6px 20px 0 rgba(220, 53, 69, 0.4);
    }
    @media (max-width: 991px) {
      .main-content {
        max-width: 99vw;
        padding: 0 0.5rem;
      }
      .app-header .app-title {
        font-size: 1.1rem;
      }
      }
    </style>
  </head>
  <body>
  <div class="app-header">
    <div class="app-header-left">
       
        <span class="app-title">Content Hub</span>
      </div>
      <div class="header-action d-flex align-items-center gap-2">
        <a class="btn btn-primary d-inline-flex align-items-center" href="new.php">
          <i class="bi bi-plus-circle me-1"></i> New Content
        </a>
        <a href="settings.php" class="btn btn-outline-primary p-2 rounded-circle d-flex align-items-center justify-content-center ms-1 header-icon-btn" title="Settings">
          <i class="bi bi-gear-fill" style="font-size:1.35em;"></i>
        </a>
        <button id="notiBtn" class="btn btn-outline-primary p-2 rounded-circle d-flex align-items-center justify-content-center ms-1 position-relative header-icon-btn" title="Notifications" type="button" data-bs-toggle="modal" data-bs-target="#notiModal">
          <i class="bi bi-bell-fill" style="font-size:1.35em;"></i>
          <span id="notiDot" class="pulse-dot" style="display:none;"></span>
        </button>
      </div>
    </div>
  </div>
  <?php if (count($notify_contents) > 0): ?>
    <div id="deadlineAlert" class="alert alert-warning alert-dismissible fade show mt-3 mx-3" role="alert" style="z-index:1051; position:relative;">
      <strong><i class="bi bi-bell-fill me-1"></i> Deadline Alert!</strong>
      You have content with upcoming deadlines.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      <a href="#" class="alert-link" data-bs-toggle="modal" data-bs-target="#notiModal">View Details</a>
    </div>
  <?php endif; ?>
  <?php if (count($notify_contents) > 0): ?>
    <!-- Noti Modal -->
    <div class="modal fade" id="notiModal" tabindex="-1" aria-labelledby="notiModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-warning-subtle">
            <h5 class="modal-title" id="notiModalLabel"><i class="bi bi-bell-fill me-2 text-warning"></i>Deadline Notifications</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if (count($notify_contents) > 0): ?>
              <ul class="list-group mb-3">
                <?php foreach ($notify_contents as $n): ?>
                  <li class="list-group-item d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                    <div>
                      <b><?= htmlspecialchars($n['title']) ?></b>
                      <span class="badge badge-status ms-2">Status: <?= htmlspecialchars($n['status_name']) ?></span>
              </div>
                    <div class="text-danger fw-bold">
                      <?= htmlspecialchars($n['deadline']) ?>
            </div>
                </li>
                <?php endforeach; ?>
              </ul>
              <button id="markReadBtn" class="btn btn-outline-primary w-100" type="button">Mark all as read</button>
            <?php else: ?>
              <div class="text-center text-secondary py-3">No notifications. All caught up!</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
            </div>
  <?php endif; ?>
  <div class="main-content">
    <div class="d-flex justify-content-end mb-3 gap-3">
      <a href="calendar.php" class="btn btn-outline-primary" style="background: linear-gradient(45deg, #667eea, #764ba2); color: white; border: none; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); transition: all 0.3s ease;">
        <i class="bi bi-calendar3 me-1"></i>Calendar View
      </a>
      <a href="export.php" class="btn btn-outline-success" style="background: linear-gradient(45deg, #28a745, #20c997); color: white; border: none; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3); transition: all 0.3s ease;">
        <i class="bi bi-download me-1"></i>Export CSV
      </a>
      <a href="import.php" class="btn btn-outline-secondary" style="background: linear-gradient(45deg, #17a2b8, #20c997); color: white; border: none; box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3); transition: all 0.3s ease;">
        <i class="bi bi-upload me-1"></i>Import CSV
      </a>
    </div>
    <div class="table-area">
      <form class="mb-3 d-flex gap-2" method="get" action="">
        <input type="text" class="form-control" name="search" placeholder="Search title or description..." value="<?= htmlspecialchars($search) ?>" style="max-width:320px;">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Search</button>
        <?php if ($search !== ''): ?>
          <a href="index.php" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
      </form>
      <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
            <th scope="col" style="min-width: 200px; max-width: 320px;">Content Title</th>
            <th scope="col" style="min-width: 200px; max-width: 320px;">Description</th>
            <th scope="col" style="min-width: 180px;">Actions</th>
                    <th scope="col" style="min-width: 120px;">Status</th>
                    <th scope="col" style="min-width: 120px;">Attachment</th>
                    <th scope="col" style="min-width: 160px;">Date</th>
                  </tr>
                </thead>
                <tbody>
          <?php if (count($filtered) === 0): ?>
                  <tr><td colspan="6" class="text-center text-secondary">No content found.</td></tr>
                <?php else: ?>
            <?php foreach ($filtered as $row): ?>
            <tr>
              <td class="fw-semibold text-dark text-truncate" style="max-width: 320px;">
                <span title="<?= htmlspecialchars($row['title']) ?>">
                  <?= htmlspecialchars($row['title']) ?>
                </span>
              </td>
              <td class="text-truncate" style="max-width: 320px;">
                <span title="<?= htmlspecialchars($row['description']) ?>">
                  <?php
                    $desc = htmlspecialchars($row['description']);
                    if (mb_strlen($desc) > 60) {
                        echo mb_substr($desc, 0, 60) . '... ';
                    } else {
                        echo $desc;
                    }
                  ?>
                </span>
              </td>
              <td>
                <div class="action-btns d-flex flex-wrap flex-md-nowrap gap-2 justify-content-md-end align-items-center">
                  <button type="button" class="btn btn-view d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#viewDetailModal"
                    data-title="<?= htmlspecialchars($row['title']) ?>"
                    data-description="<?= htmlspecialchars($row['description']) ?>"
                    data-status="<?= htmlspecialchars($row['status_name']) ?>"
                    data-deadline="<?= htmlspecialchars($row['deadline']) ?>"
                    data-created="<?= htmlspecialchars($row['created_at']) ?>"
                    data-updated="<?= htmlspecialchars($row['updated_at']) ?>"
                    data-attachment="<?= htmlspecialchars($row['attachment']) ?>">
                    <i class="bi bi-eye me-1"></i>View
                  </button>
                  <a href="edit.php?id=<?= $row['content_id'] ?>" class="btn btn-edit d-inline-flex align-items-center">
                    <i class="bi bi-pencil me-1"></i>Edit
                  </a>
                  <a href="delete.php?id=<?= $row['content_id'] ?>" 
   class="btn btn-delete d-inline-flex align-items-center delete-btn"
   data-delete-url="delete.php?id=<?= $row['content_id'] ?>">
   <i class="bi bi-trash me-1"></i>Delete
</a>
                      </div>
                    </td>
                    <td>
                <span class="badge badge-status w-100">
                  <?= htmlspecialchars($row['status_name']) ?>
                </span>
                    </td>
                    <td>
                    <?php if (!empty($row['attachment'])): ?>
                      <a href="uploads/<?= htmlspecialchars($row['attachment']) ?>" target="_blank" class="btn btn-outline-info btn-sm">View</a>
                    <?php else: ?>
                      <span class="text-secondary">-</span>
                    <?php endif; ?>
                  </td>
                    <td><?= htmlspecialchars($row['deadline']) ?></td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
              </table>
      <!-- Table summary -->
      <div class="d-flex justify-content-between align-items-center mt-2 mb-1 px-1 small text-secondary">
        <span>Showing <?= $from ?>–<?= $to ?> of <?= $total ?> items</span>
      </div>
      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav class="mt-2">
          <ul class="pagination justify-content-center pagination-lg custom-pagination">
            <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
              <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page-1 ?>" tabindex="-1">&laquo;</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item<?= $page >= $totalPages ? ' disabled' : '' ?>">
              <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page+1 ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
            </div>
          </div>
  <!-- Modal for View Detail -->
  <div class="modal fade" id="viewDetailModal" tabindex="-1" aria-labelledby="viewDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewDetailModalLabel">Content Detail</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <dl class="row mb-0">
            <dt class="col-sm-3">Title</dt>
            <dd class="col-sm-9" id="modalDetailTitle"></dd>
            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9" id="modalDetailDescription" style="white-space:pre-wrap;"></dd>
            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9" id="modalDetailStatus"></dd>
            <dt class="col-sm-3">Deadline</dt>
            <dd class="col-sm-9" id="modalDetailDeadline"></dd>
            <dt class="col-sm-3">Attachment</dt>
            <dd class="col-sm-9" id="modalDetailAttachment"></dd>
            <dt class="col-sm-3">Created At</dt>
            <dd class="col-sm-9" id="modalDetailCreated"></dd>
            <dt class="col-sm-3">Updated At</dt>
            <dd class="col-sm-9" id="modalDetailUpdated"></dd>
          </dl>
        </div>
      </div>
    </div>
  </div>
  <!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger-subtle">
        <h5 class="modal-title" id="deleteConfirmModalLabel"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this content?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    var viewDetailModal = document.getElementById('viewDetailModal');
    if (viewDetailModal) {
      viewDetailModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('modalDetailTitle').textContent = button.getAttribute('data-title');
        document.getElementById('modalDetailDescription').textContent = button.getAttribute('data-description');
        document.getElementById('modalDetailStatus').textContent = button.getAttribute('data-status');
        document.getElementById('modalDetailDeadline').textContent = button.getAttribute('data-deadline');
        document.getElementById('modalDetailCreated').textContent = button.getAttribute('data-created');
        document.getElementById('modalDetailUpdated').textContent = button.getAttribute('data-updated');
        var attachment = button.getAttribute('data-attachment');
        var modalAttachment = document.getElementById('modalDetailAttachment');
        if (attachment) {
          modalAttachment.innerHTML = '<a href="uploads/' + encodeURIComponent(attachment) + '" target="_blank">View Attachment</a>';
        } else {
          modalAttachment.textContent = '-';
        }
      });
    }
    // Dismissible deadline alert with localStorage
    var alertBox = document.getElementById('deadlineAlert');
    if (alertBox) {
      if (localStorage.getItem('deadlineAlertDismissed') === '1') {
        alertBox.style.display = 'none';
      }
      var closeBtn = alertBox.querySelector('.btn-close');
      if (closeBtn) {
        closeBtn.addEventListener('click', function () {
          alertBox.style.display = 'none';
          localStorage.setItem('deadlineAlertDismissed', '1');
        });
      }
    }
    // Recurring browser notification for deadlines
    function showDeadlineNotification() {
      var hasNoti = <?= $hasNoti ?>;
      if (!hasNoti) return;
      if (!('Notification' in window)) return;
      var lastNoti = localStorage.getItem('lastDeadlineNoti') || '';
      var today = new Date().toISOString().slice(0,10);
      if (lastNoti === today) return;
      Notification.requestPermission().then(function(permission) {
        if (permission === 'granted') {
          var n = new Notification('Content Hub: Deadline Reminder', {
            body: 'You have content with upcoming deadlines. Click to view.',
            icon: 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/icons/bell-fill.svg',
          });
          n.onclick = function() { window.focus(); document.getElementById('notiBtn').click(); };
          localStorage.setItem('lastDeadlineNoti', today);
        }
      });
    }
    // Initial check
    showDeadlineNotification();
    // Repeat every 30 minutes
    setInterval(showDeadlineNotification, 30 * 60 * 1000);

    var deleteUrl = '';
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        deleteUrl = btn.getAttribute('data-delete-url');
        deleteModal.show();
      });
    });
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
      if (deleteUrl) {
        window.location.href = deleteUrl;
      }
    });

    // Noti dot visibility
    var notiDot = document.getElementById('notiDot');
    if (notiDot) {
      if (<?= $hasNoti ?>) {
        notiDot.style.display = 'block';
      } else {
        notiDot.style.display = 'none';
      }
    }

    // Mark all as read button logic
    var markReadBtn = document.getElementById('markReadBtn');
    if (markReadBtn) {
      markReadBtn.addEventListener('click', function () {
        // Hide noti dot and alert
        if (notiDot) notiDot.style.display = 'none';
        var alertBox = document.getElementById('deadlineAlert');
        if (alertBox) alertBox.style.display = 'none';
        // Set localStorage to hide alert on reload
        localStorage.setItem('deadlineAlertDismissed', '1');
        // Close modal
        var notiModal = bootstrap.Modal.getInstance(document.getElementById('notiModal'));
        if (notiModal) notiModal.hide();
      });
    }

    // If no notifications, show modal with message when bell is clicked
    var notiBtn = document.getElementById('notiBtn');
    var notiModalEl = document.getElementById('notiModal');
    if (notiBtn && notiModalEl && <?= count($notify_contents) ?> === 0) {
      notiBtn.addEventListener('click', function (e) {
        e.preventDefault();
        var modal = new bootstrap.Modal(notiModalEl);
        modal.show();
      });
    }
  });
  </script>
  <style>
    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(255,193,7,0.7); }
      70% { box-shadow: 0 0 0 10px rgba(255,193,7,0); }
      100% { box-shadow: 0 0 0 0 rgba(255,193,7,0); }
    }
    .animate-pulse .bi-bell-fill {
      color: #ffc107;
      animation: pulse 1.2s infinite;
    }
    .pulse-dot {
      position: absolute;
      top: 8px;
      right: 8px;
      width: 14px;
      height: 14px;
      background: #ffc107;
      border-radius: 50%;
      box-shadow: 0 0 8px 2px #ffc10799;
      animation: pulse 1.2s infinite;
      z-index: 2;
      border: 2px solid #fff;
      outline: 2px solid #1976d2;
      outline-offset: 1px;
    }
    .header-icon-btn {
      border-width: 2px !important;
      border-color: #667eea !important;
      background: rgba(255, 255, 255, 0.9) !important;
      color: #667eea !important;
      width: 40px;
      height: 40px;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px 0 rgba(0,0,0,0.1);
    }
    .header-icon-btn:hover, .header-icon-btn:focus {
      background: linear-gradient(45deg, #667eea, #764ba2) !important;
      color: #fff !important;
      box-shadow: 0 4px 16px 0 rgba(102, 126, 234, 0.3);
      outline: none;
    }
    .header-icon-btn:active {
      background: linear-gradient(45deg, #5a6fd8, #6a4190) !important;
      color: #fff !important;
    }
    .header-icon-btn .bi {
      vertical-align: middle;
      display: inline-block;
    }
    /* Custom pagination styles */
    .custom-pagination .page-link {
      border-radius: 1.5rem !important;
      margin: 0 0.15rem;
      font-size: 1.15em;
      min-width: 2.5rem;
      text-align: center;
      color: #667eea;
      border: 1.5px solid rgba(102, 126, 234, 0.2);
      background: rgba(255, 255, 255, 0.9);
      transition: all 0.3s ease;
    }
    .custom-pagination .page-link:focus {
      box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.3);
    }
    .custom-pagination .page-item.active .page-link {
      background: linear-gradient(45deg, #667eea, #764ba2);
      color: #fff;
      border: none;
      font-weight: 700;
      box-shadow: 0 4px 16px 0 rgba(102, 126, 234, 0.3);
    }
    .custom-pagination .page-item.disabled .page-link {
      color: #6c757d;
      background: rgba(255, 255, 255, 0.5);
      border: 1.5px solid rgba(108, 117, 125, 0.2);
    }
    /* Table UI improvements */
    .table-area {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 14px;
      box-shadow: 0 8px 32px 0 rgba(31,38,135,0.2);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 1.2rem 1.2rem 1.2rem 1.2rem;
    }
    .table thead th {
      font-size: 1.08rem;
      font-weight: 700;
      background: rgba(102, 126, 234, 0.1);
      color: #2c3e50;
      border-bottom: 2px solid rgba(102, 126, 234, 0.2);
      white-space: nowrap;
      text-overflow: ellipsis;
      overflow: hidden;
    }
    .table-striped > tbody > tr:nth-of-type(odd) {
      background-color: rgba(102, 126, 234, 0.05);
    }
    .table-hover tbody tr:hover, .table-hover tbody tr:focus {
      background-color: rgba(102, 126, 234, 0.1);
      cursor: pointer;
      transition: all 0.3s ease;
      outline: 2px solid #667eea;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    }
    .badge-status {
      font-size: 1em;
      padding: 0.5em 1em;
      border-radius: 1em;
      background: linear-gradient(45deg, #6c757d, #5a6268);
      color: #fff;
      font-weight: 600;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .badge-status.published {
      background: linear-gradient(45deg, #28a745, #20c997);
    }
    .badge-status.archived {
      background: linear-gradient(45deg, #dc3545, #e74c3c);
    }
    .action-btns {
      min-width: 180px;
      flex-wrap: wrap;
      gap: 0.5rem !important;
    }
    @media (max-width: 767px) {
      .action-btns {
        flex-direction: column !important;
        align-items: stretch !important;
      }
      .table thead th, .table td {
        max-width: 120px !important;
        font-size: 0.98em;
      }
      .table-area {
        padding: 0.5rem 0.2rem 0.5rem 0.2rem;
      }
    }
    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.3);
    }
    .btn-view {
      background: linear-gradient(45deg, #667eea, #764ba2);
      color: #fff;
      border: none;
    }
    .btn-view:hover, .btn-view:focus {
      background: linear-gradient(45deg, #5a6fd8, #6a4190);
      color: #fff;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 6px 20px 0 rgba(102, 126, 234, 0.4);
    }
    .btn-edit {
      background: linear-gradient(45deg, #28a745, #20c997);
      color: #fff;
      border: none;
    }
    .btn-edit:hover, .btn-edit:focus {
      background: linear-gradient(45deg, #218838, #1e7e34);
      color: #fff;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 6px 20px 0 rgba(40, 167, 69, 0.4);
    }
    .btn-delete {
      background: linear-gradient(45deg, #dc3545, #e74c3c);
      color: #fff;
      border: none;
    }
    .btn-delete:hover, .btn-delete:focus {
      background: linear-gradient(45deg, #c82333, #bd2130);
      color: #fff;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 6px 20px 0 rgba(220, 53, 69, 0.4);
    }
    
    /* Enhanced button hover effects for import/export buttons */
    .btn-outline-primary:hover {
      background: linear-gradient(45deg, #5a6fd8, #6a4190) !important;
      transform: translateY(-3px) scale(1.05) !important;
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
    }
    
    .btn-outline-success:hover {
      background: linear-gradient(45deg, #218838, #1e7e34) !important;
      transform: translateY(-3px) scale(1.05) !important;
      box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4) !important;
    }
    
    .btn-outline-secondary:hover {
      background: linear-gradient(45deg, #138496, #117a8b) !important;
      transform: translateY(-3px) scale(1.05) !important;
      box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4) !important;
    }
    
    /* Responsive button adjustments */
    @media (max-width: 768px) {
      .d-flex.justify-content-end {
        flex-direction: column !important;
        align-items: stretch !important;
      }
      .d-flex.justify-content-end .btn {
        margin-bottom: 0.5rem !important;
        text-align: center !important;
      }
    }
  </style>
  </body>
</html>