<?php
// calendar.php: Calendar view of content deadlines
$dbFile = 'database.db';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch all content with deadline and status
$stmt = $pdo->query('SELECT c.content_id, c.title, c.deadline, s.status_name, c.description, c.attachment FROM Content c JOIN Status s ON c.status_id = s.status_id WHERE c.deadline IS NOT NULL AND c.deadline != ""');
$events = [];
foreach ($stmt as $row) {
    $color = '#6c757d'; // Default: Draft (gray)
    if (strtolower($row['status_name']) === 'published') $color = '#28a745';
    if (strtolower($row['status_name']) === 'archived') $color = '#dc3545';
    $events[] = [
        'id' => $row['content_id'],
        'title' => $row['title'],
        'start' => $row['deadline'],
        'status' => $row['status_name'],
        'description' => $row['description'],
        'attachment' => $row['attachment'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => '#fff',
        'deadline' => $row['deadline'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calendar View - Content Hub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
  <style>
    body { 
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', 'Inter', 'Noto Sans', Arial, sans-serif;
    }
    .fc-toolbar-title { 
      font-size: 1.8rem; 
      font-weight: 800; 
      color: #2c3e50;
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .fc-event {
      font-size: 0.95em;
      border-radius: 12px !important;
      box-shadow: 0 4px 12px 0 rgba(0,0,0,0.15);
      font-weight: 600;
      padding: 4px 8px;
      transition: all 0.3s ease;
      border: none !important;
    }
    .fc-event:hover {
      box-shadow: 0 8px 25px 0 rgba(0,0,0,0.25);
      transform: translateY(-3px) scale(1.05);
      filter: brightness(1.1);
      z-index: 10;
    }
    .fc-daygrid-event { white-space: normal; }
    .fc-daygrid-dot-event .fc-event-title { font-weight: 600; }
    .fc-event-title { color: inherit; }
    .fc-day-today { 
      background: linear-gradient(45deg, #fff3cd, #ffeaa7) !important;
      border-radius: 8px;
    }
    .fc-button-primary { 
      background: linear-gradient(45deg, #667eea, #764ba2);
      border: none;
      border-radius: 8px;
      font-weight: 600;
      padding: 8px 16px;
      transition: all 0.3s ease;
    }
    .fc-button-primary:hover, .fc-button-primary:focus { 
      background: linear-gradient(45deg, #5a6fd8, #6a4190);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    .fc-button-active {
      background: linear-gradient(45deg, #4a5fd0, #5a3180) !important;
    }
    .calendar-container { 
      width: 100%; 
      margin: 0 auto; 
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px; 
      box-shadow: 0 8px 32px 0 rgba(31,38,135,0.2);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 1rem 1vw;
    }
    .calendar-legend { 
      display: flex; 
      gap: 2rem; 
      margin-bottom: 1.5rem; 
      justify-content: center;
      flex-wrap: wrap;
    }
    .legend-item { 
      display: flex; 
      align-items: center; 
      gap: 0.8rem; 
      font-size: 1.1em;
      font-weight: 600;
      padding: 8px 16px;
      background: rgba(255, 255, 255, 0.8);
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }
    .legend-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    .legend-dot { 
      width: 20px; 
      height: 20px; 
      border-radius: 50%; 
      display: inline-block;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .legend-draft { background: linear-gradient(45deg, #6c757d, #5a6268); }
    .legend-published { background: linear-gradient(45deg, #28a745, #20c997); }
    .legend-archived { background: linear-gradient(45deg, #dc3545, #e74c3c); }
    .app-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-bottom: 3px solid rgba(255,255,255,0.2);
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .modal-content {
      border-radius: 16px;
      border: none;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    .modal-header {
      background: linear-gradient(45deg, #667eea, #764ba2);
      color: white;
      border-radius: 16px 16px 0 0;
    }
    .modal-title {
      font-weight: 700;
    }
    .btn-close {
      filter: invert(1);
    }
    .btn-secondary {
      background: linear-gradient(45deg, #6c757d, #5a6268);
      border: none;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    .btn-secondary:hover {
      background: linear-gradient(45deg, #5a6268, #495057);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
    }
  </style>
</head>
<body>
  <div class="app-header d-flex align-items-center justify-content-between px-4 py-3 mb-4">
    <span class="app-title text-white fw-bold" style="font-size:1.8rem; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">📅 Content Hub - Calendar View</span>
    <a href="index.php" class="btn btn-outline-light" style="border-radius: 8px; font-weight: 600; transition: all 0.3s ease;"><i class="bi bi-arrow-left me-2"></i>Back to List</a>
  </div>
  <div class="calendar-container">
    <div class="calendar-legend">
      <span class="legend-item"><span class="legend-dot legend-draft"></span> Draft</span>
      <span class="legend-item"><span class="legend-dot legend-published"></span> Published</span>
      <span class="legend-item"><span class="legend-dot legend-archived"></span> Archived</span>
    </div>
    <div id="calendar"></div>
  </div>
  <!-- Modal for Event Detail -->
  <div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="eventDetailModalLabel">📋 Content Detail</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <dl class="row mb-0">
            <dt class="col-sm-4 fw-bold text-primary">Title</dt>
            <dd class="col-sm-8" id="modalDetailTitle"></dd>
            <dt class="col-sm-4 fw-bold text-primary">Status</dt>
            <dd class="col-sm-8" id="modalDetailStatus"></dd>
            <dt class="col-sm-4 fw-bold text-primary">Deadline</dt>
            <dd class="col-sm-8" id="modalDetailDeadline"></dd>
            <dt class="col-sm-4 fw-bold text-primary">Description</dt>
            <dd class="col-sm-8" id="modalDetailDescription" style="white-space:pre-wrap;"></dd>
            <dt class="col-sm-4 fw-bold text-primary">Attachment</dt>
            <dd class="col-sm-8" id="modalDetailAttachment"></dd>
          </dl>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var calendarEl = document.getElementById('calendar');
      var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: <?php echo json_encode($events); ?>,
        eventClick: function(info) {
          info.jsEvent.preventDefault();
          // Show modal with event details
          var event = info.event.extendedProps;
          document.getElementById('modalDetailTitle').textContent = info.event.title;
          document.getElementById('modalDetailStatus').textContent = event.status;
          document.getElementById('modalDetailDeadline').textContent = event.deadline;
          document.getElementById('modalDetailDescription').textContent = event.description || '-';
          var att = event.attachment;
          var attEl = document.getElementById('modalDetailAttachment');
          if (att) {
            attEl.innerHTML = '<a href="uploads/' + encodeURIComponent(att) + '" target="_blank" class="btn btn-outline-primary btn-sm">📎 View Attachment</a>';
          } else {
            attEl.textContent = '-';
          }
          var modal = new bootstrap.Modal(document.getElementById('eventDetailModal'));
          modal.show();
        },
        height: 700,
        nowIndicator: true,
        selectable: false,
        eventDisplay: 'block',
        dayMaxEventRows: 3,
      });
      calendar.render();
    });
  </script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</body>
</html> 