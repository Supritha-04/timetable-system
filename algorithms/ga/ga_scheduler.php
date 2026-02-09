<?php
include_once "fitness.php";
include_once "population.php";
include_once "mutation.php";
include_once "crossover.php";

/* ===============================
   SUBJECT FILTER BY BRANCH
================================ */
function getSubjectsByBranch($conn, $branch) {

    $sql = "
        SELECT *
        FROM subjects
        WHERE branch = '$branch'
           OR branch = 'COMMON'
    ";

    $res = $conn->query($sql);
    $subjects = [];

    while ($row = $res->fetch_assoc()) {
        $subjects[] = $row;
    }

    return $subjects;
}

/* ===============================
   GENETIC ALGORITHM
================================ */
function runGeneticAlgorithm($sections, $timeslots, $conn) {

    $finalTimetable = [];

    foreach ($sections as $sectionId => $branch) {

        // Different random seed per section
        mt_srand($sectionId * 10000);

        $subjects = getSubjectsByBranch($conn, $branch);

        /* ===============================
           INITIAL POPULATION
        ================================ */
        $population = [];
$POP_SIZE = 10;

for ($i = 0; $i < $POP_SIZE; $i++) {

    do {
        $chromosome = generatePopulation($subjects, $timeslots);
    } while ($chromosome === null);

    $population[] = $chromosome;
}


        /* ===============================
           EVOLUTION
        ================================ */
        for ($gen = 0; $gen < $GENERATIONS; $gen++) {

            // Sort by fitness (BEST FIRST)
            usort($population, function ($a, $b) use ($subjects, $timeslots) {
                return fitness($b, $subjects, $timeslots)
                     <=> fitness($a, $subjects, $timeslots);
            });

            // Elitism (keep best 2)
            $newPopulation = [
                $population[0],
                $population[1]
            ];

            while (count($newPopulation) < $POP_SIZE) {

                $p1 = $population[rand(0, 5)];
                $p2 = $population[rand(0, 5)];

                $child = crossover($p1, $p2);

                mutate($child, $subjects, $timeslots);

                $newPopulation[] = $child;
            }

            $population = $newPopulation;
        }

        /* ===============================
           BEST SOLUTION
        ================================ */
        usort($population, function ($a, $b) use ($subjects, $timeslots) {
            return fitness($b, $subjects, $timeslots)
                 <=> fitness($a, $subjects, $timeslots);
        });

        $finalTimetable[$sectionId] = $population[0];
    }

    return $finalTimetable;
}
