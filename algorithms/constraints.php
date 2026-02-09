<?php

// Check if a section is free at a given timeslot
function isSectionFree($sectionId, $timeslotId, $timetable) {
    return !isset($timetable[$sectionId][$timeslotId]);
}

// Check if faculty are free (handles multiple faculty for labs)
function areFacultyFree($facultyIds, $timeslotId, $facultySchedule) {
    foreach ($facultyIds as $fid) {
        if (isset($facultySchedule[$fid][$timeslotId])) {
            return false;
        }
    }
    return true;
}

// Check non-credit subject constraint (last period only)
function isLastPeriod($timeslotId, $timeslots) {
    $slot = $timeslots[$timeslotId];
    return $slot['period_no'] == 6;
}

// Check lab continuous hours (3 periods)
function canPlaceLab($sectionId, $startSlot, $timetable) {
    for ($i = 0; $i < 3; $i++) {
        if (isset($timetable[$sectionId][$startSlot + $i])) {
            return false;
        }
    }
    return true;
}

?>
