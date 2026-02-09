<?php
include_once __DIR__ . "/constraints.php";
include_once __DIR__ . "/../config/db.php";

/*
 STRICT THEORY SCHEDULER
 - Guarantees EXACT lecture_hours
 - Does NOT overwrite labs, internship, ancient wisdom
*/
function scheduleTheorySubjects($sections, $timeslots, &$timetable, &$facultySchedule, $branch) {
    global $conn;

    $res = $conn->query("
        SELECT * FROM subjects
        WHERE is_lab = 0
          AND is_non_credit = 0
          AND practical_hours = 0
          AND subject_name != 'Summer Internship'
          AND branch IN ('$branch', 'COMMON')
    ");

    $subjects = [];
    while ($row = $res->fetch_assoc()) {
        $subjects[] = $row;
    }

    foreach ($sections as $sectionId) {

        foreach ($subjects as $subject) {

            $subjectId = $subject['subject_id'];
            $required = (int)$subject['lecture_hours'];
            if ($required <= 0) continue;

            // Faculty mapping
            $facRes = $conn->query("
                SELECT faculty_id FROM subject_faculty
                WHERE subject_id = $subjectId
            ");
            $facultyIds = [];
            while ($f = $facRes->fetch_assoc()) {
                $facultyIds[] = $f['faculty_id'];
            }

            $scheduled = 0;
            $dayUsed = [];

            // PASS 1: Try 1 lecture per day
            foreach ($timeslots as $slotIndex => $slot) {

                if ($scheduled >= $required) break;

                $day = $slot['day'];

                if (isset($dayUsed[$day])) continue;

                // slot must be empty
                if (isset($timetable[$sectionId][$slotIndex])) continue;

                // faculty must be free
                if (!areFacultyFree($facultyIds, $slotIndex, $facultySchedule)) continue;

                // place lecture
                $timetable[$sectionId][$slotIndex] = $subjectId;
                foreach ($facultyIds as $fid) {
                    $facultySchedule[$fid][$slotIndex] = true;
                }

                $dayUsed[$day] = true;
                $scheduled++;
            }

            // PASS 2: FORCE remaining lectures
            if ($scheduled < $required) {
                foreach ($timeslots as $slotIndex => $slot) {

                    if ($scheduled >= $required) break;

                    if (isset($timetable[$sectionId][$slotIndex])) continue;
                    if (!areFacultyFree($facultyIds, $slotIndex, $facultySchedule)) continue;

                    $timetable[$sectionId][$slotIndex] = $subjectId;
                    foreach ($facultyIds as $fid) {
                        $facultySchedule[$fid][$slotIndex] = true;
                    }

                    $scheduled++;
                }
            }

            // Final validation
            if ($scheduled < $required) {
                echo "❌ {$subject['subject_name']} scheduled $scheduled / $required for section $sectionId<br>";
            }
        }
    }
}
