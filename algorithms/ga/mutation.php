<?php

function mutate(&$chromosome, $subjects, $timeslots) {

    // Very light mutation
    if (rand(1, 100) > 10) return;

    $i = rand(0, 35);
    $j = rand(0, 35);

    if ($chromosome[$i] === null && $chromosome[$j] === null) return;

    // Swap only theory (never break labs)
    $chromosome[$i] = $chromosome[$j];
}
