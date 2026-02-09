<?php
include_once "../config/db.php";

// Fetch timetable with subject names and timeslots
$sql = "
SELECT t.section_id, s.subject_name, ts.day, ts.period_no
FROM timetable t
JOIN subjects s ON t.subject_id = s.subject_id
JOIN timeslots ts ON t.timeslot_id = ts.timeslot_id
WHERE t.section_id = 1
ORDER BY ts.day, ts.period_no
";

$result = $conn->query($sql);

// Build array [day][period]
$timetable = [];
while ($row = $result->fetch_assoc()) {
    $timetable[$row['day']][$row['period_no']] = $row['subject_name'];
}

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
$periods = [1,2,3,4,5,6];
?>

<h2>AIML – 3-2 Timetable</h2>
<table border="1" cellpadding="10">
<tr>
    <th>Day / Period</th>
    <?php foreach ($periods as $p) echo "<th>P$p</th>"; ?>
</tr>

<?php foreach ($days as $day): ?>
<tr>
    <td><b><?= $day ?></b></td>
    <?php foreach ($periods as $p): ?>
        <td><?= $timetable[$day][$p] ?? '-' ?></td>
    <?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
