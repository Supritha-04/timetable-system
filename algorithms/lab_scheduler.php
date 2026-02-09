<?php
include_once __DIR__ . "/constraints.php";
include_once __DIR__ . "/../config/db.php";

/*
 LAB SCHEDULER (COLLEGE-CORRECT)
 - Each lab = 3 continuous hours
 - Only one lab per day per section
 - Labs distributed across days
*/

function scheduleLabs($sections, $timeslots, $branch, &$timetable, &$facultySchedule) {
    global $conn;

    // DO NOT reinitialize here
    // $timetable and $facultySchedule already exist

    $res = $conn->query("
        SELECT * FROM subjects
        WHERE is_lab = 1
          AND branch IN ('$branch', 'COMMON')
    ");

    $labs = [];
    while ($row = $res->fetch_assoc()) {
        $labs[] = $row;
    }

    foreach ($sections as $sectionId) {
        $labDayUsed = [];

        foreach ($labs as $lab) {
            $subjectId = $lab['subject_id'];
            $duration = 3;

            $facRes = $conn->query("
                SELECT faculty_id FROM subject_faculty
                WHERE subject_id = $subjectId
            ");

            $facultyIds = [];
            while ($f = $facRes->fetch_assoc()) {
                $facultyIds[] = $f['faculty_id'];
            }

            for ($i = 0; $i <= count($timeslots) - $duration; $i++) {
                $day = $timeslots[$i]['day'];
                if (isset($labDayUsed[$day])) continue;

                $ok = true;
                for ($k = 0; $k < $duration; $k++) {
                    if (
                        isset($timetable[$sectionId][$i + $k]) ||
                        !areFacultyFree($facultyIds, $i + $k, $facultySchedule) ||
                        $timeslots[$i + $k]['day'] !== $day
                    ) {
                        $ok = false;
                        break;
                    }
                }

                if ($ok) {
                    for ($k = 0; $k < $duration; $k++) {
                        $timetable[$sectionId][$i + $k] = $subjectId;
                        foreach ($facultyIds as $fid) {
                            $facultySchedule[$fid][$i + $k] = true;
                        }
                    }
                    $labDayUsed[$day] = true;
                    break;
                }
            }
        }
    }
}
