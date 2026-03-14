<?php
require_once '../config/db.php';
require_once 'auth_check.php';

$conn->query("
    CREATE TABLE IF NOT EXISTS open_elective_slot (
        id          INT       NOT NULL AUTO_INCREMENT PRIMARY KEY,
        timeslot_id INT       NOT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_ts (timeslot_id)
    )
");

$message = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'save') {
        $slotA = (int)($_POST['slot_a'] ?? 0);
        $slotB = (int)($_POST['slot_b'] ?? 0);
        $slotC = (int)($_POST['slot_c'] ?? 0);
        $errors = [];
        if (!$slotA || !$slotB || !$slotC)
            $errors[] = 'Please select all 3 slots.';

        if ($slotA && $slotB && $slotA !== $slotB) {
            $r = $conn->query("
                SELECT t1.day AS dayA, t1.period_no AS pA, t1.end_time AS endA,
                       t2.day AS dayB, t2.period_no AS pB, t2.start_time AS startB
                FROM timeslots t1, timeslots t2
                WHERE t1.timeslot_id=$slotA AND t2.timeslot_id=$slotB
            ");
            if ($r && ($row=$r->fetch_assoc())) {
                if ($row['dayA']!==$row['dayB'])
                    $errors[] = 'Slot A and B must be on the same day.';
                elseif (abs((int)$row['pA']-(int)$row['pB'])!==1)
                    $errors[] = 'Slot A and B must be consecutive periods.';
                else {
                    [$eh,$em]=explode(':',$row['endA']);
                    [$sh,$sm]=explode(':',$row['startB']);
                    $gap=((int)$sh*60+(int)$sm)-((int)$eh*60+(int)$em);
                    if ($gap>10) $errors[]='Slot A and B cannot straddle the lunch break.';
                }
            }
        }

        if ($slotA && $slotC) {
            $r=$conn->query("SELECT t1.day AS dayA,t2.day AS dayC FROM timeslots t1,timeslots t2 WHERE t1.timeslot_id=$slotA AND t2.timeslot_id=$slotC");
            if ($r&&($row=$r->fetch_assoc()))
                if ($row['dayA']===$row['dayC']) $errors[]='Slot C must be on a different day from A and B.';
        }

        if ($errors) { $message=implode(' ',$errors); $msgType='danger'; }
        else {
            $conn->query("DELETE FROM open_elective_slot");
            $st=$conn->prepare("INSERT IGNORE INTO open_elective_slot (timeslot_id) VALUES (?)");
            foreach ([$slotA,$slotB,$slotC] as $ts) { $st->bind_param("i",$ts); $st->execute(); }
            $st->close();
            $message='OE slots saved. Re-generate the timetable to apply.';
        }
    }

    if ($_POST['action']==='clear') {
        $conn->query("DELETE FROM open_elective_slot");
        $message='OE slots cleared.'; $msgType='warning';
    }
}

// Load current OE slots
$currentSlots = [];
$r=$conn->query("SELECT oe.id,ts.timeslot_id,ts.day,ts.period_no,ts.start_time,ts.end_time FROM open_elective_slot oe JOIN timeslots ts ON ts.timeslot_id=oe.timeslot_id ORDER BY ts.day,ts.period_no");
while ($row=$r->fetch_assoc()) $currentSlots[]=$row;

// Timeslots for dropdowns
$timeslotsByDay=[];
$r=$conn->query("SELECT * FROM timeslots ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),period_no");
while ($row=$r->fetch_assoc()) $timeslotsByDay[$row['day']][]=$row;

$sel=['a'=>0,'b'=>0,'c'=>0];
if (count($currentSlots)===3) { $sel['a']=$currentSlots[0]['timeslot_id']; $sel['b']=$currentSlots[1]['timeslot_id']; $sel['c']=$currentSlots[2]['timeslot_id']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Open Elective Slots</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>body{background:#f4f6f9;} .card{border:none;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.08);}</style>
</head>
<body>
<?php include '../layout/header.php'; ?>

<div class="container py-4" style="max-width:700px">

    <!-- Back to Dashboard -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-calendar-event me-2 text-primary"></i>Open Elective Slots</h3>
            <p class="text-muted small mb-0">Set 3 fixed weekly slots: 2 continuous on one day + 1 on a different day.</p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="bi bi-house-door me-1"></i>Back to Dashboard
        </a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?=$msgType?> alert-dismissible fade show">
        <?=htmlspecialchars($message)?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Current slots -->
    <div class="card p-4 mb-4">
        <h5 class="fw-semibold mb-3">Current OE Slots</h5>
        <?php if (count($currentSlots)===3): ?>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php foreach ($currentSlots as $i=>$s): ?>
                <span class="badge bg-success fs-6 px-3 py-2">
                    <?=['A','B','C'][$i]?>: <?=$s['day']?> P<?=$s['period_no']?> (<?=$s['start_time']?>–<?=$s['end_time']?>)
                </span>
                <?php endforeach; ?>
            </div>
            <form method="POST" class="d-inline" onsubmit="return confirm('Clear all OE slots?')">
                <input type="hidden" name="action" value="clear">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Clear All Slots</button>
            </form>
        <?php elseif (count($currentSlots)>0): ?>
            <div class="alert alert-warning">Only <?=count($currentSlots)?> slot(s) set — all 3 required. Re-save below.</div>
        <?php else: ?>
            <p class="text-muted mb-0"><i class="bi bi-exclamation-triangle text-warning me-1"></i>No OE slots set yet.</p>
        <?php endif; ?>
    </div>

    <!-- Set slots form -->
    <div class="card p-4">
        <h5 class="fw-semibold mb-1"><?=count($currentSlots)===3?'Change':'Set'?> OE Slots</h5>
        <p class="text-muted small mb-4">A+B must be consecutive on the same day. C must be on a different day.</p>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <?php
            function slotDropdown($name,$selected,$timeslotsByDay,$label,$hint){
                echo "<div class='mb-3'><label class='form-label fw-medium'>$label <small class='text-muted fw-normal'>$hint</small></label>";
                echo "<select name='$name' class='form-select' required><option value=''>— select —</option>";
                foreach ($timeslotsByDay as $day=>$slots){
                    echo "<optgroup label='$day'>";
                    foreach ($slots as $ts){
                        $sel=($ts['timeslot_id']==$selected)?'selected':'';
                        echo "<option value='{$ts['timeslot_id']}' $sel>$day — P{$ts['period_no']} ({$ts['start_time']}–{$ts['end_time']})</option>";
                    }
                    echo "</optgroup>";
                }
                echo "</select></div>";
            }
            slotDropdown('slot_a',$sel['a'],$timeslotsByDay,'Slot A','(first continuous period)');
            slotDropdown('slot_b',$sel['b'],$timeslotsByDay,'Slot B','(next period, same day as A)');
            slotDropdown('slot_c',$sel['c'],$timeslotsByDay,'Slot C','(single period, different day)');
            ?>
            <button class="btn btn-primary w-100 btn-lg"><i class="bi bi-check-lg me-1"></i>Save OE Slots</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>