<?php
include_once __DIR__ . "/../config/db.php";
include_once __DIR__ . "/constraints.php";

/*
 ANCIENT WISDOM
 - One day only
 - Last two periods (continuous 2 hrs)
*/
function scheduleAncientWisdom($sections, $timeslots, &$timetable) {
    global $conn;

    $res = $conn->query("
        SELECT subject_id FROM subjects 
        WHERE subject_name = 'Ancient Wisdom'
    ");
    if ($res->num_rows == 0) return;

    $subjectId = $res->fetch_assoc()['subject_id'];

    foreach ($sections as $sectionId) {

        foreach ($timeslots as $i => $slot) {

            // Period 5 & 6 only
            if ($slot['period_no'] != 5) continue;

            // Ensure next period exists
            if (!isset($timeslots[$i + 1])) continue;

            // Same day
            if ($timeslots[$i + 1]['day'] !== $slot['day']) continue;

            // Both slots free
            if (
                isSectionFree($sectionId, $i, $timetable) &&
                isSectionFree($sectionId, $i + 1, $timetable)
            ) {
                $timetable[$sectionId][$i] = $subjectId;
                $timetable[$sectionId][$i + 1] = $subjectId;
                break; // ✅ only ONE day
            }
        }
    }
}
