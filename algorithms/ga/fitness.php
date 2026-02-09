<?php

function fitness($chromosome, $subjects, $timeslots) {

    $score = 1000; // start high

    /* ===============================
       0. COUNT SUBJECT OCCURRENCES
    ================================ */
    $subjectCount = [];

    foreach ($subjects as $s) {
        $subjectCount[$s['subject_id']] = 0;
    }

    foreach ($chromosome as $sid) {
        if ($sid !== null) {
            $subjectCount[$sid]++;
        }
    }

    /* ===============================
       1. EXACT HOURS CHECK
       lecture_hours + practical_hours
    ================================ */
    foreach ($subjects as $s) {

        $expected =
            intval($s['lecture_hours']) +
            intval($s['practical_hours']);

        if ($subjectCount[$s['subject_id']] != $expected) {
            $score -= abs(
                $subjectCount[$s['subject_id']] - $expected
            ) * 200;
        }
    }

    /* ===============================
       2. SAME SUBJECT TWICE A DAY
    ================================ */
    for ($day = 0; $day < 6; $day++) {

        $seen = [];

        for ($p = 0; $p < 6; $p++) {

            $index = $day * 6 + $p;
            $sid = $chromosome[$index];

            if ($sid !== null) {
                if (isset($seen[$sid])) {
                    $score -= 100;
                }
                $seen[$sid] = true;
            }
        }
    }

    /* ===============================
       3. LAB CONTINUITY CHECK (3 HRS)
    ================================ */
    foreach ($subjects as $s) {

        if (intval($s['practical_hours']) == 3) {

            $positions = [];

            foreach ($chromosome as $i => $sid) {
                if ($sid == $s['subject_id']) {
                    $positions[] = $i;
                }
            }

            if (count($positions) != 3) {
                $score -= 300;
            } else {
                sort($positions);

                // Must be continuous AND same day
                if (
                    $positions[1] != $positions[0] + 1 ||
                    $positions[2] != $positions[1] + 1 ||
                    intdiv($positions[0], 6) != intdiv($positions[2], 6)
                ) {
                    $score -= 300;
                }
            }
        }
    }

    /* ===============================
       4. THEORY STRICT CHECK
       lecture_hours ONLY
    ================================ */
    foreach ($subjects as $s) {

        if (
            intval($s['lecture_hours']) > 0 &&
            intval($s['practical_hours']) == 0
        ) {

            $theoryCount = 0;

            foreach ($chromosome as $sid) {
                if ($sid == $s['subject_id']) {
                    $theoryCount++;
                }
            }

            if ($theoryCount != intval($s['lecture_hours'])) {
                $score -= abs(
                    $theoryCount - intval($s['lecture_hours'])
                ) * 150;
            }
        }
    }

    return $score;
}
