<?php

function crossover($p1, $p2) {

    $point = rand(1, count($p1) - 2);

    return array_merge(
        array_slice($p1, 0, $point),
        array_slice($p2, $point)
    );
}
