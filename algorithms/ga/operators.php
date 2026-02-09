<?php

function generateInitialTimetable($sections, $timeslots, $conn) {

    $timetable = [];

    foreach ($sections as $sectionId => $branch) {

        // initialize empty slots
        $timetable[$sectionId] = array_fill(0, count($timeslots), null);

        // fetch subjects with hours
        $res = $conn->query("
            SELECT subject_id, lecture_hours, practical_hours
            FROM subjects
            WHERE branch IN ('$branch','COMMON')
        ");

        $subjects = [];
        while ($r = $res->fetch_assoc()) {
            $subjects[] = $r;
        }

        // 1️⃣ PLACE LABS FIRST (3 continuous hours)
        foreach ($subjects as $sub) {

            if ($sub['practical_hours'] == 3) {

                $placed = false;

                while (!$placed) {
                    $day = rand(0, 4); // Mon–Fri
                    $start = $day * 7 + rand(0, 4); // ensure 3 slots fit

                    if (
                        $timetable[$sectionId][$start] === null &&
                        $timetable[$sectionId][$start + 1] === null &&
                        $timetable[$sectionId][$start + 2] === null
                    ) {
                        $timetable[$sectionId][$start]     = $sub['subject_id'];
                        $timetable[$sectionId][$start + 1] = $sub['subject_id'];
                        $timetable[$sectionId][$start + 2] = $sub['subject_id'];
                        $placed = true;
                    }
                }
            }
        }

        // 2️⃣ PLACE THEORY SUBJECTS BY WEEKLY HOURS
        foreach ($subjects as $sub) {

            if ($sub['lecture_hours'] > 0) {

                $count = 0;

                while ($count < $sub['lecture_hours']) {

                    $slot = rand(0, count($timeslots) - 1);

                    if ($timetable[$sectionId][$slot] === null) {
                        $timetable[$sectionId][$slot] = $sub['subject_id'];
                        $count++;
                    }
                }
            }
        }
    }

    return $timetable;
}


/* ---------- selection ---------- */
function tournamentSelection($population) {
    $a = $population[array_rand($population)];
    $b = $population[array_rand($population)];
    return fitness($a) > fitness($b) ? $a : $b;
}

/* ---------- crossover ---------- */
function crossover($p1, $p2) {
    $child = [];

    foreach ($p1 as $sectionId => $slots) {
        foreach ($slots as $i => $val) {
            $child[$sectionId][$i] =
                (rand(0,1) === 0)
                ? $p1[$sectionId][$i]
                : $p2[$sectionId][$i];
        }
    }
    return $child;
}

/* ---------- mutation ---------- */
function mutate(&$chromosome, $timeslots, $conn, $rate) {

    if (rand(1,100) > $rate) return;

    $sectionId = array_rand($chromosome);
    $slot = array_rand($chromosome[$sectionId]);

    // DO NOT mutate lab blocks
    $res = $conn->query("
        SELECT subject_id 
        FROM subjects 
        WHERE practical_hours = 3
    ");

    $labSubjects = [];
    while ($r = $res->fetch_assoc()) {
        $labSubjects[] = $r['subject_id'];
    }

    if (in_array($chromosome[$sectionId][$slot], $labSubjects)) {
        return; // protect lab continuity
    }

    // mutate only theory
    $res = $conn->query("
        SELECT subject_id 
        FROM subjects 
        WHERE lecture_hours > 0
    ");

    $theory = [];
    while ($r = $res->fetch_assoc()) {
        $theory[] = $r['subject_id'];
    }

    if (!empty($theory)) {
        $chromosome[$sectionId][$slot] = $theory[array_rand($theory)];
    }
}
