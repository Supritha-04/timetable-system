<?php
include_once __DIR__ . "/constraints.php";
include_once __DIR__ . "/../config/db.php";

/*
 STRICT SUMMER INTERNSHIP SCHEDULER
 - EXACTLY 4 hours/week
 - 2 hrs on first day
 - 2 hrs on last day
*/
function scheduleInternship($sections, $timeslots, &$timetable) {
    global $conn;

    $res = $conn->query("
        SELECT subject_id FROM subjects
        WHERE subject_name = 'Summer Internship'
    ");
    if ($res->num_rows == 0) return;

    $subjectId = $res->fetch_assoc()['subject_id'];

    // Group slots by day
    $slotsByDay = [];
    foreach ($timeslots as $i => $slot) {
        $slotsByDay[$slot['day']][] = $i;
    }

    $days = array_keys($slotsByDay);
    $firstDay = $days[0];
    $lastDay  = $days[count($days) - 1];

    foreach ($sections as $sectionId) {

        $placed = 0;

        // FIRST DAY (2 hours)
        $placed += placeBlock($sectionId, $slotsByDay[$firstDay], $subjectId, $timetable, 2);

        // LAST DAY (2 hours)
        $placed += placeBlock($sectionId, $slotsByDay[$lastDay], $subjectId, $timetable, 2);

        if ($placed < 4) {
            echo "❌ Internship scheduled $placed / 4 hours for section $sectionId<br>";
        }
    }
}

function placeBlock($sectionId, $slotIndexes, $subjectId, &$timetable, $hours) {
    for ($i = 0; $i <= count($slotIndexes) - $hours; $i++) {
        $ok = true;
        for ($k = 0; $k < $hours; $k++) {
            if (!isSectionFree($sectionId, $slotIndexes[$i + $k], $timetable)) {
                $ok = false; break;
            }
        }
        if ($ok) {
            for ($k = 0; $k < $hours; $k++) {
                $timetable[$sectionId][$slotIndexes[$i + $k]] = $subjectId;
            }
            return $hours;
        }
    }
    return 0;
}
