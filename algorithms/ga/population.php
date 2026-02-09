<?php

function generatePopulation($subjects, $timeslots) {

    // 6 days × 6 periods = 36 slots
    $chromosome = array_fill(0, 36, null);

    /* ===============================
       1. LABS (3 CONTINUOUS @ P1/P4)
       NO RECURSION
    ================================ */
    foreach ($subjects as $s) {

        if ($s['practical_hours'] == 3) {

            $placed = false;

            // try all days & valid starts
            for ($day = 0; $day < 6 && !$placed; $day++) {
                foreach ([0, 3] as $p) {

                    $start = $day * 6 + $p;

                    if (
                        $chromosome[$start] === null &&
                        $chromosome[$start + 1] === null &&
                        $chromosome[$start + 2] === null
                    ) {
                        $chromosome[$start] =
                        $chromosome[$start + 1] =
                        $chromosome[$start + 2] = $s['subject_id'];

                        $placed = true;
                        break;
                    }
                }
            }

            // if not placed → leave it (fitness will penalize)
        }
    }

    /* ===============================
       2. ANCIENT WISDOM (2 CONTINUOUS)
    ================================ */
    foreach ($subjects as $s) {

        if ($s['subject_name'] === 'Ancient Wisdom') {

            for ($attempt = 0; $attempt < 20; $attempt++) {

                $day = rand(0, 5);
                $start = $day * 6 + rand(0, 4);

                if (
                    $chromosome[$start] === null &&
                    $chromosome[$start + 1] === null
                ) {
                    $chromosome[$start] =
                    $chromosome[$start + 1] = $s['subject_id'];
                    break;
                }
            }
        }
    }

    /* ===============================
   3. SUMMER INTERNSHIP
   2 DAYS × 2 CONTINUOUS HOURS
=============================== */
foreach ($subjects as $s) {

    if ($s['subject_name'] === 'Summer Internship') {

        // Fixed days: Monday (0) and Friday (4)
        $days = [0, 4];

        foreach ($days as $day) {

            $placed = false;

            // try placing continuous 2 periods in that day
            for ($p = 0; $p <= 4; $p++) {

                $start = $day * 6 + $p;

                if (
                    $chromosome[$start] === null &&
                    $chromosome[$start + 1] === null
                ) {
                    $chromosome[$start]     = $s['subject_id'];
                    $chromosome[$start + 1] = $s['subject_id'];
                    $placed = true;
                    break;
                }
            }

            // if cannot place on this day → fail this chromosome
            if (!$placed) {
                return null;
            }
        }
    }
}

    /* ===============================
   4. THEORY SUBJECTS (STRICT)
   BASED ONLY ON lecture_hours
=============================== */
foreach ($subjects as $s) {

    if (
        intval($s['lecture_hours']) > 0 &&
        intval($s['practical_hours']) == 0 &&
        !in_array($s['subject_name'], ['Ancient Wisdom', 'Summer Internship'])
    ) {

        $required = intval($s['lecture_hours']);
        $placed = 0;

        // Loop days repeatedly until required lectures are placed
        while ($placed < $required) {

            $progress = false;

            for ($day = 0; $day < 6 && $placed < $required; $day++) {

                $dayStart = $day * 6;
                $dayEnd   = $dayStart + 5;

                // Check if subject already exists this day
                $exists = false;
                for ($i = $dayStart; $i <= $dayEnd; $i++) {
                    if ($chromosome[$i] === $s['subject_id']) {
                        $exists = true;
                        break;
                    }
                }
                if ($exists) continue;

                // Place in first available slot of the day
                for ($i = $dayStart; $i <= $dayEnd; $i++) {
                    if ($chromosome[$i] === null) {
                        $chromosome[$i] = $s['subject_id'];
                        $placed++;
                        $progress = true;
                        break;
                    }
                }
            }

            // HARD STOP → prevents infinite loop
            if (!$progress) break;
        }
    }
}


    return $chromosome;
}
