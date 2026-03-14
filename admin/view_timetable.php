<?php
include("auth_check.php");
include("../config/db.php");
include("../layout/header.php");
include("../layout/sidebar.php");

$conn->query("
    CREATE TABLE IF NOT EXISTS open_elective_slot (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        timeslot_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_ts (timeslot_id)
    )
");

$sections   = $conn->query("SELECT * FROM sections ORDER BY section_name");
$section_id = (int)($_GET['section_id'] ?? 1);

// Section name for display/download
$secName = '';
$rs = $conn->prepare("SELECT section_name FROM sections WHERE section_id=?");
$rs->bind_param("i",$section_id); $rs->execute();
$rs->bind_result($secName); $rs->fetch(); $rs->close();

// Load timetable — LEFT JOIN so OE rows (no faculty) are never dropped
$stmt = $conn->prepare("
    SELECT  ts.day, ts.period_no, s.subject_name,
            GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR ', ') AS faculty
    FROM    timetable t
    JOIN    timeslots ts           ON t.timeslot_id  = ts.timeslot_id
    JOIN    subjects  s            ON t.subject_id   = s.subject_id
    LEFT JOIN timetable_faculty tf ON t.timetable_id = tf.timetable_id
    LEFT JOIN faculty           f  ON tf.faculty_id  = f.faculty_id
    WHERE   t.section_id = ?
    GROUP BY ts.day, ts.period_no, s.subject_name
    ORDER BY FIELD(ts.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
             ts.period_no
");
$stmt->bind_param("i",$section_id);
$stmt->execute();
$result = $stmt->get_result();

$timetable  = [];
$rawSubject = [];
$rawFaculty = [];
while ($row = $result->fetch_assoc()) {
    $d = $row['day']; $p = (int)$row['period_no'];
    $cell = $row['subject_name'];
    if ($row['faculty'])
        $cell .= '<br><small class="text-muted">'.$row['faculty'].'</small>';
    $timetable[$d][$p]  = $cell;
    $rawSubject[$d][$p] = strtolower(trim($row['subject_name']));
    $rawFaculty[$d][$p] = $row['faculty'] ?? '';
}

// OE slots — force into grid
$oeSlots = [];
$r = $conn->query("
    SELECT ts.day, ts.period_no
    FROM   open_elective_slot oe
    JOIN   timeslots ts ON ts.timeslot_id = oe.timeslot_id
    ORDER  BY ts.day, ts.period_no
");
while ($row = $r->fetch_assoc()) {
    $oeSlots[] = $row;
    $d = $row['day']; $p = (int)$row['period_no'];
    $timetable[$d][$p]  = '<span style="color:#0f5132;font-weight:600;"><i class="bi bi-journal-bookmark me-1"></i>Open Elective</span>';
    $rawSubject[$d][$p] = 'open elective';
    $rawFaculty[$d][$p] = '';
}
$oeSet = [];
foreach ($oeSlots as $s)
    $oeSet[$s['day'].'_'.(int)$s['period_no']] = true;

$fillerNames = ['mtp','eca/cca','sports'];
$days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

// Detect lunch gap by time
$periodData = [];
$rp = $conn->query("SELECT DISTINCT period_no,start_time,end_time FROM timeslots ORDER BY period_no");
while ($row = $rp->fetch_assoc()) {
    [$sh,$sm] = explode(':',$row['start_time']);
    [$eh,$em] = explode(':',$row['end_time']);
    $periodData[(int)$row['period_no']] = [
        'start'=>(int)$sh*60+(int)$sm,
        'end'  =>(int)$eh*60+(int)$em,
        'label'=>$row['start_time'].'–'.$row['end_time'],
    ];
}
ksort($periodData);
$allPeriods = array_keys($periodData);
$maxGap=0; $gapAfter=-1;
for ($i=0;$i<count($allPeriods)-1;$i++){
    $gap=$periodData[$allPeriods[$i+1]]['start']-$periodData[$allPeriods[$i]]['end'];
    if($gap>$maxGap){$maxGap=$gap;$gapAfter=$i;}
}
if($gapAfter>=0&&$maxGap>10){
    $beforeLunch=array_slice($allPeriods,0,$gapAfter+1);
    $afterLunch =array_slice($allPeriods,$gapAfter+1);
} else {
    $half=(int)ceil(count($allPeriods)/2);
    $beforeLunch=array_slice($allPeriods,0,$half);
    $afterLunch =array_slice($allPeriods,$half);
}
$allTeachingPeriods = array_merge($beforeLunch, $afterLunch);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<!-- Libraries for export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

<style>
    .btn-dashboard { background:#6c757d;color:#fff;border:none; }
    .btn-dashboard:hover { background:#5a6268;color:#fff; }
    .filler-cell { background:#f0f0f0!important;color:#888;font-style:italic; }
    .oe-cell     { background:#d1e7dd!important; }
    .lunch-cell  { background:#ffeeba!important;font-weight:bold; }
    @media print {
        .no-print { display:none!important; }
        .table { font-size:11px; }
    }
</style>

<div class="container-fluid mt-3">

<!-- ── Top bar ────────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 no-print">
    <h3 class="mb-0">View Timetable</h3>
    <a href="dashboard.php" class="btn btn-dashboard">
        <i class="bi bi-house-door me-1"></i>Back to Dashboard
    </a>
</div>

<!-- ── Controls row ───────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center flex-wrap gap-2 mb-3 no-print">

    <!-- Section selector -->
    <form method="GET" class="d-flex align-items-center gap-2">
        <select name="section_id" class="form-control" style="width:200px"
                onchange="this.form.submit()">
            <?php
            $sections->data_seek(0);
            while ($sec=$sections->fetch_assoc()){
                $sel=($sec['section_id']==$section_id)?'selected':'';
                echo "<option value='{$sec['section_id']}' $sel>{$sec['section_name']}</option>";
            }
            ?>
        </select>
    </form>

    <!-- Export buttons -->
    <button class="btn btn-success" onclick="downloadExcel()">
        <i class="bi bi-file-earmark-excel me-1"></i>Download Excel
    </button>
    <button class="btn btn-danger" onclick="downloadPDF()">
        <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
    </button>
    <button class="btn btn-secondary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Print
    </button>
</div>

<?php if (!empty($oeSlots)): ?>
<div class="alert alert-success py-2 d-inline-flex align-items-center gap-2 mb-3">
    <i class="bi bi-check-circle-fill"></i>
    <span>Fixed OE slots:
    <?php foreach ($oeSlots as $i=>$s): ?>
        <strong><?=$s['day']?> P<?=$s['period_no']?></strong><?=$i<count($oeSlots)-1?', ':''?>
    <?php endforeach; ?>
    </span>
</div>
<?php endif; ?>

<!-- ── Timetable ─────────────────────────────────────────────────────────── -->
<div class="table-responsive">
<table class="table table-bordered text-center" id="timetableTable">
<thead class="table-dark">
<tr>
    <th>Day / Period</th>
    <?php foreach ($beforeLunch as $p): ?>
    <th>P<?=$p?><br><small style="font-weight:normal;font-size:.7rem;"><?=$periodData[$p]['label']?></small></th>
    <?php endforeach; ?>
    <th class="lunch-cell">Lunch</th>
    <?php foreach ($afterLunch as $p): ?>
    <th>P<?=$p?><br><small style="font-weight:normal;font-size:.7rem;"><?=$periodData[$p]['label']?></small></th>
    <?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php foreach ($days as $day): ?>
<tr>
    <td><b><?=$day?></b></td>
    <?php foreach ($beforeLunch as $p):
        $key=$day.'_'.$p;
        $isOE=isset($oeSet[$key]);
        $sub=$rawSubject[$day][$p]??'';
        $isFiller=in_array($sub,$fillerNames,true);
        $cell=$timetable[$day][$p]??'-';
        $cls=$isOE?'oe-cell':($isFiller?'filler-cell':'');
    ?><td class="<?=$cls?>"><?=$cell?></td>
    <?php endforeach; ?>
    <td class="lunch-cell">Lunch</td>
    <?php foreach ($afterLunch as $p):
        $key=$day.'_'.$p;
        $isOE=isset($oeSet[$key]);
        $sub=$rawSubject[$day][$p]??'';
        $isFiller=in_array($sub,$fillerNames,true);
        $cell=$timetable[$day][$p]??'-';
        $cls=$isOE?'oe-cell':($isFiller?'filler-cell':'');
    ?><td class="<?=$cls?>"><?=$cell?></td>
    <?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Legend -->
<div class="d-flex gap-3 flex-wrap mb-4 no-print">
    <span class="badge bg-success px-3 py-2">Open Elective (fixed)</span>
    <span class="badge px-3 py-2" style="background:#f0f0f0;color:#555;border:1px solid #ccc;">MTP / ECA-CCA / SPORTS (filler)</span>
    <span class="badge px-3 py-2" style="background:#ffeeba;color:#555;border:1px solid #ccc;">☕ Lunch</span>
</div>

</div><!-- /container -->

<?php include("../layout/footer.php"); ?>

<!-- ── Export Scripts ─────────────────────────────────────────────────────── -->
<script>
// ── Plain-text data passed from PHP for export ────────────────────────────
const SECTION_NAME = <?= json_encode($secName) ?>;
const DAYS   = <?= json_encode($days) ?>;
const BEFORE = <?= json_encode($beforeLunch) ?>;
const AFTER  = <?= json_encode($afterLunch) ?>;
const RAW    = <?= json_encode(array_map(fn($d) => array_map(fn($p) => [
    'subject' => $rawSubject[$d][$p] ?? '',
    'faculty' => $rawFaculty[$d][$p] ?? '',
], array_combine($allTeachingPeriods, $allTeachingPeriods)), $days)) ?>;

// Rebuild clean data: day => period => {subject, faculty}
const DATA = {};
<?php foreach ($days as $day): ?>
DATA[<?=json_encode($day)?>] = {};
<?php foreach ($allTeachingPeriods as $p): 
    $sub = $rawSubject[$day][$p] ?? '';
    $fac = $rawFaculty[$day][$p] ?? '';
    // OE override
    if (isset($oeSet[$day.'_'.$p])) { $sub = 'Open Elective'; $fac = ''; }
?>
DATA[<?=json_encode($day)?>][<?=$p?>] = {
    subject: <?=json_encode(ucwords($sub))?>,
    faculty: <?=json_encode($fac)?>
};
<?php endforeach; ?>
<?php endforeach; ?>

// ── Excel Download ────────────────────────────────────────────────────────
function downloadExcel() {
    const wb = XLSX.utils.book_new();

    // Header row
    const header = ['Day'];
    BEFORE.forEach(p => header.push('P'+p));
    header.push('Lunch');
    AFTER.forEach(p  => header.push('P'+p));

    const rows = [header];
    DAYS.forEach(day => {
        const row = [day];
        BEFORE.forEach(p => {
            const d = DATA[day]?.[p];
            row.push(d?.subject ? (d.subject + (d.faculty ? '\n'+d.faculty : '')) : '-');
        });
        row.push('LUNCH');
        AFTER.forEach(p => {
            const d = DATA[day]?.[p];
            row.push(d?.subject ? (d.subject + (d.faculty ? '\n'+d.faculty : '')) : '-');
        });
        rows.push(row);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);

    // Column widths
    ws['!cols'] = [{ wch: 12 }];
    header.slice(1).forEach(() => ws['!cols'].push({ wch: 22 }));

    // Row heights for wrap
    ws['!rows'] = rows.map(() => ({ hpt: 40 }));

    XLSX.utils.book_append_sheet(wb, ws, SECTION_NAME || 'Timetable');
    XLSX.writeFile(wb, 'Timetable_' + (SECTION_NAME||'Section') + '.xlsx');
}

// ── PDF Download ──────────────────────────────────────────────────────────
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    doc.setFontSize(14);
    doc.setFont('helvetica','bold');
    doc.text('Timetable — ' + SECTION_NAME, 14, 14);
    doc.setFontSize(9);
    doc.setFont('helvetica','normal');
    doc.text('Generated on ' + new Date().toLocaleDateString(), 14, 20);

    // Build table data
    const head = [['Day', ...BEFORE.map(p=>'P'+p), 'Lunch', ...AFTER.map(p=>'P'+p)]];
    const body = DAYS.map(day => {
        const row = [day];
        BEFORE.forEach(p => {
            const d = DATA[day]?.[p];
            row.push(d?.subject || '-');
        });
        row.push('LUNCH');
        AFTER.forEach(p => {
            const d = DATA[day]?.[p];
            row.push(d?.subject || '-');
        });
        return row;
    });

    doc.autoTable({
        startY: 24,
        head: head,
        body: body,
        theme: 'grid',
        headStyles: {
            fillColor: [30,60,114],
            textColor: 255,
            fontStyle: 'bold',
            fontSize: 8,
            halign: 'center',
        },
        bodyStyles: { fontSize: 7.5, halign: 'center', valign: 'middle', cellPadding: 2 },
        columnStyles: { 0: { fontStyle:'bold', halign:'left', cellWidth: 22 } },
        didParseCell: function(data) {
            if (data.section === 'body') {
                const txt = (data.cell.raw||'').toLowerCase();
                if (txt === 'open elective')
                    data.cell.styles.fillColor = [209,231,221];
                else if (['mtp','eca/cca','sports','lunch'].includes(txt))
                    data.cell.styles.fillColor = [240,240,240];
            }
        },
        margin: { left:10, right:10 },
        tableWidth: 'auto',
    });

    doc.save('Timetable_' + (SECTION_NAME||'Section') + '.pdf');
}
</script>