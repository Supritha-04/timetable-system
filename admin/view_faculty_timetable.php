<?php
/**
 * admin/view_faculty_timetable.php
 * Shows a selected faculty member's weekly schedule in the same
 * Day × Period grid as the section timetable.
 * Each cell shows:  Subject Name / Section Name
 * Place at: TIMETABLE-SYSTEM/admin/view_faculty_timetable.php
 */
include("auth_check.php");
include("../config/db.php");
include("../layout/header.php");
include("../layout/sidebar.php");

// Ensure OE slot table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS open_elective_slot (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        timeslot_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_ts (timeslot_id)
    )
");

/* ── All faculty for dropdown ────────────────────────────────────────────── */
$allFaculty = $conn->query("SELECT faculty_id, name FROM faculty ORDER BY name");
$faculty_id = (int)($_GET['faculty_id'] ?? 0);

// Default to first faculty if none selected
if (!$faculty_id) {
    $allFaculty->data_seek(0);
    $first = $allFaculty->fetch_assoc();
    if ($first) $faculty_id = (int)$first['faculty_id'];
    $allFaculty->data_seek(0);
}

/* ── Load this faculty's timetable ──────────────────────────────────────────
   Joins: timetable_faculty → timetable → timeslots + subjects + sections
   One faculty can teach the same subject to multiple sections — each cell
   shows subject + section so clashes are visible.
*/
$timetable  = [];   // day => period_no => HTML string
$rawSubject = [];   // day => period_no => lower subject name (filler detection)

if ($faculty_id) {
    $stmt = $conn->prepare("
        SELECT  ts.day,
                ts.period_no,
                s.subject_name,
                s.is_lab,
                sec.section_name
        FROM    timetable_faculty tf
        JOIN    timetable  t   ON t.timetable_id  = tf.timetable_id
        JOIN    timeslots  ts  ON ts.timeslot_id  = t.timeslot_id
        JOIN    subjects   s   ON s.subject_id    = t.subject_id
        JOIN    sections   sec ON sec.section_id  = t.section_id
        WHERE   tf.faculty_id = ?
        ORDER BY FIELD(ts.day,'Monday','Tuesday','Wednesday',
                              'Thursday','Friday','Saturday'), ts.period_no
    ");
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $d = $row['day'];
        $p = (int)$row['period_no'];

        $subName = $row['subject_name'];
        $secName = $row['section_name'];
        $isLab   = (int)$row['is_lab'];

        // Build cell HTML — subject on top, section below in muted small text
        $cell  = htmlspecialchars($subName);
        if ($isLab)
            $cell .= ' <span class="badge bg-warning text-dark" style="font-size:.65rem;">Lab</span>';
        $cell .= '<br><small class="text-muted">' . htmlspecialchars($secName) . '</small>';

        // If multiple sections share this slot (shouldn't happen but handle gracefully)
        if (isset($timetable[$d][$p])) {
            $timetable[$d][$p]  .= '<hr class="my-1">' . $cell;
        } else {
            $timetable[$d][$p]   = $cell;
        }
        $rawSubject[$d][$p] = strtolower(trim($subName));
    }
    $stmt->close();
}

/* ── OE slots (shown for context — faculty has no class here) ────────────── */
$oeSlots = [];
$r = $conn->query("
    SELECT ts.day, ts.period_no
    FROM   open_elective_slot oe
    JOIN   timeslots ts ON ts.timeslot_id = oe.timeslot_id
    ORDER  BY ts.day, ts.period_no
");
while ($row = $r->fetch_assoc()) $oeSlots[] = $row;

$oeSet = [];
foreach ($oeSlots as $s)
    $oeSet[$s['day'] . '_' . (int)$s['period_no']] = true;

/* ── Period detection (same logic as view_timetable.php) ─────────────────── */
$fillerNames = ['mtp', 'eca/cca', 'sports'];
$days        = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

$periodData = [];
$rp = $conn->query(
    "SELECT DISTINCT period_no, start_time, end_time FROM timeslots ORDER BY period_no");
while ($row = $rp->fetch_assoc()) {
    [$sh,$sm] = explode(':', $row['start_time']);
    [$eh,$em] = explode(':', $row['end_time']);
    $periodData[(int)$row['period_no']] = [
        'start' => (int)$sh*60+(int)$sm,
        'end'   => (int)$eh*60+(int)$em,
        'label' => $row['start_time'] . '–' . $row['end_time'],
    ];
}
ksort($periodData);
$allPeriods = array_keys($periodData);

$maxGap = 0; $gapAfter = -1;
for ($i = 0; $i < count($allPeriods)-1; $i++) {
    $gap = $periodData[$allPeriods[$i+1]]['start'] - $periodData[$allPeriods[$i]]['end'];
    if ($gap > $maxGap) { $maxGap = $gap; $gapAfter = $i; }
}
$beforeLunch = []; $afterLunch = [];
if ($gapAfter >= 0 && $maxGap > 10) {
    $beforeLunch = array_slice($allPeriods, 0, $gapAfter + 1);
    $afterLunch  = array_slice($allPeriods, $gapAfter + 1);
} else {
    $half = (int)ceil(count($allPeriods) / 2);
    $beforeLunch = array_slice($allPeriods, 0, $half);
    $afterLunch  = array_slice($allPeriods, $half);
}

/* ── Selected faculty name for heading ───────────────────────────────────── */
$facultyName = '';
if ($faculty_id) {
    $r = $conn->query(
        "SELECT name FROM faculty WHERE faculty_id = $faculty_id LIMIT 1");
    if ($r && $r->num_rows > 0) $facultyName = $r->fetch_assoc()['name'];
}

/* ── Count classes per week ──────────────────────────────────────────────── */
$totalClasses = 0;
foreach ($timetable as $dayData)
    $totalClasses += count($dayData);
?>

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="container-fluid">

<h3 class="mt-3">Faculty Timetable</h3>

<!-- Faculty selector -->
<form method="GET" class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <label class="fw-medium mb-0">Faculty:</label>
    <select name="faculty_id" class="form-control d-inline" style="width:260px"
            onchange="this.form.submit()">
        <?php
        $allFaculty->data_seek(0);
        while ($fac = $allFaculty->fetch_assoc()):
            $sel = ($fac['faculty_id'] == $faculty_id) ? 'selected' : '';
            echo "<option value='{$fac['faculty_id']}' $sel>"
               . htmlspecialchars($fac['name'])
               . "</option>";
        endwhile;
        ?>
    </select>
    <button class="btn btn-primary">View</button>
</form>

<?php if ($facultyName): ?>
<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
    <h5 class="mb-0 fw-semibold">
        <i class="bi bi-person-badge me-2 text-primary"></i>
        <?= htmlspecialchars($facultyName) ?>
    </h5>
    <span class="badge bg-primary fs-6"><?= $totalClasses ?> classes / week</span>
</div>
<?php endif; ?>

<?php if (!$faculty_id || empty($timetable)): ?>
<div class="alert alert-info">
    <?= $faculty_id
        ? 'No classes assigned to this faculty yet. Generate the timetable first.'
        : 'Please select a faculty member.' ?>
</div>
<?php else: ?>

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
<thead class="table-dark">
<tr>
    <th style="width:100px">Day</th>
    <?php
    $pNum = 1;
    foreach ($beforeLunch as $p):
    ?>
    <th>P<?= $pNum++ ?><br><small style="font-weight:400;font-size:.7rem;">
        <?= $periodData[$p]['label'] ?></small></th>
    <?php endforeach; ?>
    <th style="background:#856404;color:#fff;">Lunch</th>
    <?php foreach ($afterLunch as $p): ?>
    <th>P<?= $pNum++ ?><br><small style="font-weight:400;font-size:.7rem;">
        <?= $periodData[$p]['label'] ?></small></th>
    <?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php foreach ($days as $day): ?>
<tr>
    <td><b><?= $day ?></b></td>

    <?php foreach ($beforeLunch as $p):
        $key      = $day . '_' . $p;
        $isOE     = isset($oeSet[$key]);
        $subName  = $rawSubject[$day][$p] ?? '';
        $isFiller = in_array($subName, $fillerNames, true);
        $cell     = $timetable[$day][$p] ?? '';

        if ($isOE):
    ?>
        <td style="background:#d1e7dd;color:#0f5132;font-style:italic;">
            <small>Open Elective<br>(college-wide)</small>
        </td>
    <?php elseif ($cell): ?>
        <td style="background:#e8f4fd;"><?= $cell ?></td>
    <?php else: ?>
        <td style="color:#ccc;">—</td>
    <?php endif; ?>
    <?php endforeach; ?>

    <td style="background:#ffeeba;"><b>Lunch</b></td>

    <?php foreach ($afterLunch as $p):
        $key      = $day . '_' . $p;
        $isOE     = isset($oeSet[$key]);
        $subName  = $rawSubject[$day][$p] ?? '';
        $isFiller = in_array($subName, $fillerNames, true);
        $cell     = $timetable[$day][$p] ?? '';

        if ($isOE):
    ?>
        <td style="background:#d1e7dd;color:#0f5132;font-style:italic;">
            <small>Open Elective<br>(college-wide)</small>
        </td>
    <?php elseif ($isFiller): ?>
        <td style="background:#f0f0f0;color:#aaa;font-style:italic;">
            <?= htmlspecialchars(ucfirst($subName)) ?>
        </td>
    <?php elseif ($cell): ?>
        <td style="background:#e8f4fd;"><?= $cell ?></td>
    <?php else: ?>
        <td style="color:#ccc;">—</td>
    <?php endif; ?>
    <?php endforeach; ?>

</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Legend -->
<div class="d-flex gap-3 mt-2 flex-wrap">
    <span class="badge px-3 py-2" style="background:#e8f4fd;color:#0c5fa8;border:1px solid #b8d4f0;">
        Class assigned
    </span>
    <span class="badge bg-success px-3 py-2">Open Elective (fixed)</span>
    <span class="badge px-3 py-2"
          style="background:#f0f0f0;color:#888;border:1px solid #ddd;font-style:italic;">
        MTP / ECA-CCA / SPORTS
    </span>
    <span class="badge px-3 py-2"
          style="background:#fff;color:#ccc;border:1px solid #eee;">
        — Free
    </span>
</div>

<?php endif; ?>
</div>

<?php include("../layout/footer.php"); ?>