<?php

define('DAYS', 6);
define('PERIODS', 6);

/* ---------- EMPTY TIMETABLE ---------- */
function emptyTimetable() {
    $tt = [];
    for ($d=0; $d<DAYS; $d++) {
        for ($p=0; $p<PERIODS; $p++) {
            $tt[$d][$p] = null;
        }
    }
    return $tt;
}

/* ---------- FETCH SUBJECTS ---------- */
function getSubjects($conn, $branch) {
    $sql = "
        SELECT *
        FROM subjects
        WHERE 
            branch = '$branch'
            OR branch = 'COMMON'
    ";

    $res = $conn->query($sql);
    $subjects = [];

    while ($row = $res->fetch_assoc()) {
        $subjects[] = $row;
    }
    return $subjects;
}


/* ---------- PLACE LABS (3 CONTINUOUS HOURS) ---------- */
function placeLabs(&$tt, $subjects) {

    foreach ($subjects as $s) {

        if ($s['practical_hours'] < 3) continue;

        $placed = false;

        for ($day = 0; $day < DAYS && !$placed; $day++) {
            for ($p = 0; $p <= PERIODS - 3; $p++) {

                if (
                    $tt[$day][$p] === null &&
                    $tt[$day][$p+1] === null &&
                    $tt[$day][$p+2] === null
                ) {
                    $tt[$day][$p]   = $s['subject_name']." (Lab)";
                    $tt[$day][$p+1] = $s['subject_name']." (Lab)";
                    $tt[$day][$p+2] = $s['subject_name']." (Lab)";
                    $placed = true;
                    break;
                }
            }
        }

        if (!$placed) {
            echo "<b>⚠ Lab not placed:</b> {$s['subject_name']}<br>";
        }
    }
}



/* ---------- INTERNSHIP: 2 DAYS × 2 HOURS ---------- */
function placeInternship(&$tt, $sectionId) {

    $slots = [
        1 => [[0,0],[0,1],[4,0],[4,1]],
        2 => [[0,2],[0,3],[4,2],[4,3]],
        3 => [[1,0],[1,1],[3,0],[3,1]],
        4 => [[1,2],[1,3],[3,2],[3,3]]
    ];

    if (!isset($slots[$sectionId])) return;

    foreach ($slots[$sectionId] as [$d,$p]) {
        if ($tt[$d][$p] === null) {
            $tt[$d][$p] = 'Internship';
        }
    }
}


/* ---------- ANCIENT WISDOM: ONE DAY ONLY ---------- */
function placeAncientWisdom(&$tt) {

    for ($day = 0; $day < DAYS; $day++) {
        for ($p = 0; $p <= PERIODS - 2; $p++) {

            if ($tt[$day][$p] === null && $tt[$day][$p+1] === null) {
                $tt[$day][$p]   = 'Ancient Wisdom';
                $tt[$day][$p+1] = 'Ancient Wisdom';
                return;
            }
        }
    }
}

/* ---------- THEORY (EXACT WEEKLY HOURS) ---------- */
function placeTheory(&$tt, $subjects) {

    foreach ($subjects as $s) {

        if ($s['lecture_hours'] <= 0 || $s['practical_hours'] > 0) continue;

        $remaining = $s['lecture_hours'];
        $usedDays = [];

        for ($day = 0; $day < DAYS && $remaining > 0; $day++) {

            if (in_array($day, $usedDays)) continue;

            for ($p = 0; $p < PERIODS && $remaining > 0; $p++) {

                if ($tt[$day][$p] === null) {
                    $tt[$day][$p] = $s['subject_name'];
                    $remaining--;
                    $usedDays[] = $day;
                    break;
                }
            }
        }

        if ($remaining > 0) {
            echo "<b>⚠ Theory hours missing:</b> {$s['subject_name']} ($remaining)<br>";
        }
    }
}


