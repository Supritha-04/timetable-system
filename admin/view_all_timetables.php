<?php
include_once "../config/db.php";

// Section names
$sections = [
    1 => "AIML - A",
    2 => "AIML - B",
    3 => "AIML - C",
    4 => "IoT"
];

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$periods = [1, 2, 3, 4, 5, 6];

// Fetch timetable + subject + faculty
$sql = "
SELECT 
    t.section_id,
    ts.day,
    ts.period_no,
    s.subject_name,
    GROUP_CONCAT(f.name SEPARATOR ', ') AS faculty_names
FROM timetable t
JOIN timeslots ts ON ts.timeslot_id = t.timeslot_id
JOIN subjects s ON s.subject_id = t.subject_id
LEFT JOIN timetable_faculty tf ON tf.timetable_id = t.timetable_id
LEFT JOIN faculty f ON f.faculty_id = tf.faculty_id
GROUP BY t.section_id, ts.day, ts.period_no, s.subject_name
ORDER BY t.section_id, ts.day, ts.period_no
";


$result = $conn->query($sql);

// Build timetable array
$timetable = [];

while ($row = $result->fetch_assoc()) {
    $timetable[$row['section_id']]
             [$row['day']]
             [$row['period_no']] = [
                 'subject' => $row['subject_name'],
                 'faculty' => $row['faculty_names']
             ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Timetables with Faculty</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 40px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background-color: #f2f2f2;
        }
        .faculty {
            font-size: 12px;
            color: #555;
        }
        h2 {
            margin-top: 40px;
        }
    </style>
</head>
<body>

<h1>Department Timetables (With Faculty)</h1>

<?php foreach ($sections as $sectionId => $sectionName): ?>

    <h2><?= $sectionName ?></h2>

    <table>
        <tr>
            <th>Day / Period</th>
            <?php foreach ($periods as $p): ?>
                <th>P<?= $p ?></th>
            <?php endforeach; ?>
        </tr>

        <?php foreach ($days as $day): ?>
            <tr>
                <td><b><?= $day ?></b></td>

                <?php foreach ($periods as $p): ?>
                    <td>
                        <?php if (isset($timetable[$sectionId][$day][$p])): ?>
                            <b><?= $timetable[$sectionId][$day][$p]['subject'] ?></b><br>
                            <span class="faculty">
                                (<?= $timetable[$sectionId][$day][$p]['faculty'] ?>)
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>

<?php endforeach; ?>

</body>
</html>
