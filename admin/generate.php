<?php
session_start();
include("auth_check.php");
include("../config/db.php");
include("../algorithms/GeneticAlgorithm.php");

$conn->query("
    CREATE TABLE IF NOT EXISTS open_elective_slot (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        timeslot_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_ts (timeslot_id)
    )
");

$ga      = new GeneticAlgorithm($conn);
$success = $ga->generate(50, 100, 0.15, 0.8);

// Safety net — re-insert OE rows for every section after GA finishes
if ($success) {
    $oeTimeslotIds = [];
    $r = $conn->query("SELECT timeslot_id FROM open_elective_slot");
    while ($row = $r->fetch_assoc()) $oeTimeslotIds[] = (int)$row['timeslot_id'];

    if (!empty($oeTimeslotIds)) {
        $oeSubjectId = 0;
        $r = $conn->query("SELECT subject_id FROM subjects WHERE LOWER(TRIM(subject_name))='open elective' LIMIT 1");
        if ($r && $r->num_rows > 0) $oeSubjectId = (int)$r->fetch_assoc()['subject_id'];
        if (!$oeSubjectId) {
            $conn->query("INSERT INTO subjects (subject_name,branch,is_lab,lecture_hours) VALUES ('Open Elective','COMMON',0,0)");
            $oeSubjectId = (int)$conn->insert_id;
        }
        $roomId = 1;
        $r = $conn->query("SELECT room_id FROM classrooms LIMIT 1");
        if ($r && ($rrow=$r->fetch_assoc())) $roomId=(int)$rrow['room_id'];

        $r = $conn->query("SELECT section_id FROM sections");
        while ($sec=$r->fetch_assoc()) {
            $sid=(int)$sec['section_id'];
            foreach ($oeTimeslotIds as $oeTs) {
                $chk=$conn->prepare("SELECT COUNT(*) AS cnt FROM timetable WHERE section_id=? AND timeslot_id=?");
                $chk->bind_param("ii",$sid,$oeTs); $chk->execute();
                $exists=(int)$chk->get_result()->fetch_assoc()['cnt']; $chk->close();
                if (!$exists) {
                    $ins=$conn->prepare("INSERT INTO timetable (section_id,subject_id,room_id,timeslot_id) VALUES (?,?,?,?)");
                    $ins->bind_param("iiii",$sid,$oeSubjectId,$roomId,$oeTs);
                    $ins->execute(); $ins->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Generate Timetable</title>
<link rel="stylesheet" href="../assets/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="container mt-5">
<div class="card p-4 text-center" style="max-width:480px;margin:auto;">

    <?php if ($success): ?>
        <div class="mb-3">
            <i class="bi bi-check-circle-fill text-success" style="font-size:3rem;"></i>
        </div>
        <h4 class="text-success mb-4">Timetable Generated Successfully</h4>
        <div class="d-grid gap-2">
            <a href="view_timetable.php" class="btn btn-primary">
                <i class="bi bi-table me-1"></i>View Timetable
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-house-door me-1"></i>Back to Dashboard
            </a>
        </div>
    <?php else: ?>
        <div class="mb-3">
            <i class="bi bi-x-circle-fill text-danger" style="font-size:3rem;"></i>
        </div>
        <h4 class="text-danger mb-4">Failed to Generate Timetable</h4>
        <div class="d-grid gap-2">
            <a href="generate.php" class="btn btn-warning">
                <i class="bi bi-arrow-clockwise me-1"></i>Try Again
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-house-door me-1"></i>Back to Dashboard
            </a>
        </div>
    <?php endif; ?>

</div>
</body>
</html>