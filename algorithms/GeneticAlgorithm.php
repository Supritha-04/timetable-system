<?php

/**
 * GeneticAlgorithm.php
 *
 * Open Elective handling
 * ──────────────────────
 * OE is a single, fixed, college-wide timeslot stored in the
 * `open_elective_slot` table (one row).  The GA reads this slot at
 * startup and NEVER places any subject in it for any section.
 * It is treated as permanently occupied — like the lunch break.
 */
class GeneticAlgorithm {

    private $conn;

    private $sections        = [];
    private $subjects        = [];   // [subject_id => row]
    private $timeslots       = [];   // [idx => row]  (lunch + OE slot excluded)
    private $faculty         = [];   // ["secId_subId" => [faculty_id,...]]
    private $weeklyHours     = [];   // [subject_id => int]
    private $sectionSubjects = [];   // [section_id => [subject_id,...]]

    // All fixed OE timeslot_ids (up to 3) loaded from open_elective_slot table
    private $oeTimeslotIds = [];  // array of int

    // Slot maps
    private $dayPeriodToIdx    = [];
    private $idxToDay          = [];
    private $idxToPeriod       = [];
    private $slotsByDay        = [];   // [dayName => [idx,...]]

    // Contiguous groups (computed from schedulable slots only)
    private $validLabBlocks    = [];
    private $validDoubleBlocks = [];
    private $doubleBlockSet    = [];   // ["a_b" => true]

    private static $DAY_ORDER = [
        'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'
    ];

    // GA parameters
    private $populationSize;
    private $generations;
    private $mutationRate;
    private $crossoverRate;

    // =========================================================================
    //  BOOT
    // =========================================================================

    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadData();
        $this->buildSlotMaps();
        $this->buildSectionSubjectMap();
    }

    private function loadData() {
        // Ensure the OE slot table exists (safe no-op if already present)
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS open_elective_slot (
                id          INT       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                timeslot_id INT       NOT NULL,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Sections
        $r = $this->conn->query("SELECT * FROM sections ORDER BY section_id");
        while ($row = $r->fetch_assoc()) $this->sections[] = $row;

        // Subjects
        $r = $this->conn->query("SELECT * FROM subjects ORDER BY subject_id");
        while ($row = $r->fetch_assoc()) {
            $id   = (int)$row['subject_id'];
            $name = strtolower(trim($row['subject_name']));
            $this->subjects[$id] = $row;

            // Derive weekly hours from subject type so GA works correctly
            // even if lecture_hours is 0 or wrong in the DB.
            if ((int)$row['is_lab'] === 1) {
                $hours = 3;   // lab = one 3-period block
            } elseif ($name === 'summer internship') {
                $hours = 4;   // 2 continuous + 2 continuous on different days
            } elseif ($name === 'ancient wisdom') {
                $hours = 2;   // one 2-period block
            } elseif ($name === 'open elective') {
                $hours = 3;   // fixed slot — GA skips it, but record correctly
            } elseif ((int)$row['lecture_hours'] > 0) {
                $hours = (int)$row['lecture_hours'];
            } else {
                $hours = 4;   // safe default for any theory subject
            }
            $this->weeklyHours[$id] = $hours;
        }

        // Load ALL fixed OE timeslot_ids (up to 3 rows)
        $r = $this->conn->query("SELECT timeslot_id FROM open_elective_slot");
        while ($row = $r->fetch_assoc())
            $this->oeTimeslotIds[] = (int)$row['timeslot_id'];

        // Load ALL timeslots — lunch is not stored in timeslots table,
        // it is just a visual gap between P3 and P4 in the view.
        // Only exclude the fixed OE slots.
        $sql = "SELECT * FROM timeslots WHERE 1=1";
        if (!empty($this->oeTimeslotIds)) {
            $ids  = implode(',', $this->oeTimeslotIds);
            $sql .= " AND timeslot_id NOT IN ($ids)";
        }
        $sql .= " ORDER BY FIELD(day,'Monday','Tuesday','Wednesday',
                                 'Thursday','Friday','Saturday'), period_no";

        $r = $this->conn->query($sql);
        while ($row = $r->fetch_assoc()) $this->timeslots[] = $row;

        // Faculty assignments
        $r = $this->conn->query("SELECT * FROM subject_faculty");
        while ($row = $r->fetch_assoc()) {
            $key = $row['section_id'] . '_' . $row['subject_id'];
            $this->faculty[$key][] = (int)$row['faculty_id'];
        }
    }

    /**
     * Build per-section subject list from subject_faculty.
     * Uses faculty assignments as the authoritative source so every subject
     * assigned to a section (regardless of branch field) is included.
     */
    private function buildSectionSubjectMap() {
        foreach ($this->sections as $sec)
            $this->sectionSubjects[(int)$sec['section_id']] = [];

        foreach ($this->faculty as $key => $_) {
            [$secId, $subId] = explode('_', $key, 2);
            $secId = (int)$secId; $subId = (int)$subId;
            // Skip the "Open Elective" subject — it is a fixed slot, not scheduled by GA
            if (isset($this->subjects[$subId]) &&
                strtolower(trim($this->subjects[$subId]['subject_name'])) === 'open elective')
                continue;
            if (isset($this->sectionSubjects[$secId]) &&
                !in_array($subId, $this->sectionSubjects[$secId], true))
                $this->sectionSubjects[$secId][] = $subId;
        }

        // Fallback: branch matching when no faculty rows exist yet
        foreach ($this->sections as $sec) {
            $sid = (int)$sec['section_id'];
            if (!empty($this->sectionSubjects[$sid])) continue;
            $branch = $this->sectionBranch($sec['section_name']);
            foreach ($this->subjects as $subId => $sub) {
                if (strtolower(trim($sub['subject_name'])) === 'open elective') continue;
                if ($sub['branch'] === $branch || $sub['branch'] === 'COMMON')
                    $this->sectionSubjects[$sid][] = $subId;
            }
        }
    }

    private function buildSlotMaps() {
        foreach ($this->timeslots as $idx => $slot) {
            $d = $slot['day']; $p = (int)$slot['period_no'];
            $this->dayPeriodToIdx[$d][$p] = $idx;
            $this->idxToDay[$idx]         = $d;
            $this->idxToPeriod[$idx]      = $p;
            $this->slotsByDay[$d][]       = $idx;
        }

        // Derive sorted period list and detect the lunch gap by TIME not period_no.
        // In this DB period_nos are 1-6 with no skip, but there is a time gap
        // between P3 (ends 13:00) and P4 (starts 13:40) — that gap is lunch.
        // We find the largest time gap between consecutive periods to split
        // morning and afternoon groups.
        $periodTimeMap = [];  // period_no => start_minutes
        foreach ($this->timeslots as $slot) {
            $p = (int)$slot['period_no'];
            if (!isset($periodTimeMap[$p]) && isset($slot['start_time'])) {
                [$h, $m] = explode(':', $slot['start_time']);
                $periodTimeMap[$p] = (int)$h * 60 + (int)$m;
            }
        }
        ksort($periodTimeMap);
        $periods = array_keys($periodTimeMap);

        // Also build end_time map to measure gaps
        $periodEndMap = [];
        foreach ($this->timeslots as $slot) {
            $p = (int)$slot['period_no'];
            if (!isset($periodEndMap[$p]) && isset($slot['end_time'])) {
                [$h, $m] = explode(':', $slot['end_time']);
                $periodEndMap[$p] = (int)$h * 60 + (int)$m;
            }
        }

        // Find the largest time gap between end of period[i] and start of period[i+1]
        $maxGap = 0; $gapAfter = -1;
        for ($i = 0; $i < count($periods)-1; $i++) {
            $gap = $periodTimeMap[$periods[$i+1]] - $periodEndMap[$periods[$i]];
            if ($gap > $maxGap) { $maxGap = $gap; $gapAfter = $i; }
        }
        $beforeLunch = []; $afterLunch = [];
        if ($gapAfter >= 0 && $maxGap > 10) {  // >10 min gap = lunch
            $beforeLunch = array_slice($periods, 0, $gapAfter + 1);
            $afterLunch  = array_slice($periods, $gapAfter + 1);
        } else {
            $half = (int)ceil(count($periods) / 2);
            $beforeLunch = array_slice($periods, 0, $half);
            $afterLunch  = array_slice($periods, $half);
        }

        // Lab blocks: full morning block OR full afternoon block (3 consecutive)
        foreach (self::$DAY_ORDER as $day) {
            foreach ([$beforeLunch, $afterLunch] as $group) {
                if (count($group) < 3) continue;
                // Use the last 3 of the group (handles groups of 3 or more)
                $triplets = [];
                for ($i = 0; $i <= count($group)-3; $i++)
                    $triplets[] = array_slice($group, $i, 3);
                foreach ($triplets as $triple) {
                    $block = [];
                    foreach ($triple as $p)
                        if (isset($this->dayPeriodToIdx[$day][$p]))
                            $block[] = $this->dayPeriodToIdx[$day][$p];
                    if (count($block) === 3) $this->validLabBlocks[] = $block;
                }
            }
        }

        // Double blocks: any two consecutive period_nos (no cross-lunch pairs)
        $beforeSet = array_flip($beforeLunch);
        $afterSet  = array_flip($afterLunch);
        foreach (self::$DAY_ORDER as $day) {
            for ($i = 0; $i < count($periods)-1; $i++) {
                $p1 = $periods[$i]; $p2 = $periods[$i+1];
                // Skip if they straddle the lunch gap
                if (isset($beforeSet[$p1]) && isset($afterSet[$p2])) continue;
                if (!isset($this->dayPeriodToIdx[$day][$p1],
                            $this->dayPeriodToIdx[$day][$p2])) continue;
                $a = $this->dayPeriodToIdx[$day][$p1];
                $b = $this->dayPeriodToIdx[$day][$p2];
                $this->validDoubleBlocks[] = [$a, $b];
                $this->doubleBlockSet["$a\_$b"] = true;
                $this->doubleBlockSet["$b\_$a"] = true;
            }
        }
    }

    // =========================================================================
    //  PUBLIC ENTRY POINT
    // =========================================================================

    public function generate(
        $populationSize = 80,
        $generations    = 300,
        $mutationRate   = 0.20,
        $crossoverRate  = 0.85
    ) {
        $this->populationSize = $populationSize;
        $this->generations    = $generations;
        $this->mutationRate   = $mutationRate;
        $this->crossoverRate  = $crossoverRate;

        $population     = $this->initializePopulation();
        $bestChromosome = null;
        $bestFitness    = -1.0;

        for ($gen = 0; $gen < $this->generations; $gen++) {
            $fitness = [];
            foreach ($population as $idx => $chr) {
                $f = $this->calculateFitness($chr);
                $fitness[$idx] = $f;
                if ($f > $bestFitness) {
                    $bestFitness    = $f;
                    $bestChromosome = $chr;
                }
            }
            if ($bestFitness >= 1.0) break;

            arsort($fitness);
            $eliteCount = max(1, (int)round($this->populationSize * 0.10));
            $newPop     = [];
            foreach (array_slice(array_keys($fitness), 0, $eliteCount, true) as $k)
                $newPop[] = $population[$k];

            while (count($newPop) < $this->populationSize) {
                $p1 = $this->tournamentSelection($population, $fitness);
                $p2 = $this->tournamentSelection($population, $fitness);

                $child = ((mt_rand()/mt_getrandmax()) < $this->crossoverRate)
                    ? $this->crossover($p1, $p2) : $p1;

                if ((mt_rand()/mt_getrandmax()) < $this->mutationRate)
                    $child = $this->mutate($child);

                // Hard repair — eliminates same-day theory duplicates
                $child = $this->repairChromosome($child);

                $newPop[] = $child;
            }
            $population = $newPop;
        }

        $this->saveTimetable($bestChromosome);
        return true;
    }

    // =========================================================================
    //  CHROMOSOME CREATION
    // =========================================================================

    private function initializePopulation() {
        $pop = [];
        for ($i = 0; $i < $this->populationSize; $i++)
            $pop[] = $this->createChromosome();
        return $pop;
    }

    private function createChromosome() {
        $chromosome = [];

        foreach ($this->sections as $section) {
            $sid   = (int)$section['section_id'];
            $subs  = $this->sectionSubjects[$sid];
            $genes = []; $usedSlots = [];

            // ── 1. Labs (3 continuous: P1-P2-P3 or P4-P5-P6) ────────────────
            foreach ($subs as $subId) {
                if ($this->subjects[$subId]['is_lab'] != 1) continue;
                $block = $this->pickRandomLabBlock($usedSlots);
                if ($block) foreach ($block as $s) {
                    $genes[]     = ['subject_id'=>$subId,'timeslot_idx'=>$s];
                    $usedSlots[] = $s;
                }
            }

            // ── 2. Summer Internship (2+2 continuous, different days) ─────────
            foreach ($subs as $subId) {
                if ($this->subjects[$subId]['subject_name'] !== 'Summer Internship') continue;
                $placed = 0; $usedDays = [];
                for ($a = 0; $a < 200 && $placed < 4; $a++) {
                    $block = $this->pickDoubleBlockPreferLast($usedSlots, $usedDays);
                    if (!$block) break;
                    $day = $this->idxToDay[$block[0]];
                    foreach ($block as $s) {
                        $genes[]     = ['subject_id'=>$subId,'timeslot_idx'=>$s];
                        $usedSlots[] = $s;
                    }
                    $usedDays[] = $day; $placed += 2;
                }
            }

            // ── 3. Ancient Wisdom (2 continuous) ─────────────────────────────
            foreach ($subs as $subId) {
                if ($this->subjects[$subId]['subject_name'] !== 'Ancient Wisdom') continue;
                $block = $this->pickRandomDoubleBlock($usedSlots);
                if ($block) foreach ($block as $s) {
                    $genes[]     = ['subject_id'=>$subId,'timeslot_idx'=>$s];
                    $usedSlots[] = $s;
                }
            }

            // ── 4. Theory subjects (lecture_hours/week, max 1/day, round-robin)
            // PMOB and every other plain theory subject goes here.
            foreach ($subs as $subId) {
                $sub  = $this->subjects[$subId];
                $name = $sub['subject_name'];
                if ($sub['is_lab'] == 1) continue;
                if (in_array($name, ['Summer Internship','Ancient Wisdom'], true)) continue;

                $required  = $this->weeklyHours[$subId];
                $placed    = 0;
                $freeByDay = $this->buildFreeByDay($usedSlots);
                $days      = self::$DAY_ORDER;
                shuffle($days);

                // One slot per day — guarantees no same-day repeat
                foreach ($days as $day) {
                    if ($placed >= $required) break;
                    if (empty($freeByDay[$day])) continue;
                    $s = array_shift($freeByDay[$day]);
                    $genes[]     = ['subject_id'=>$subId,'timeslot_idx'=>$s];
                    $usedSlots[] = $s;
                    $placed++;
                }

                // Safety fallback if not enough distinct days available
                if ($placed < $required) {
                    foreach ($this->freeSlotsShuffled($usedSlots) as $s) {
                        if ($placed >= $required) break;
                        $genes[]     = ['subject_id'=>$subId,'timeslot_idx'=>$s];
                        $usedSlots[] = $s;
                        $placed++;
                    }
                }
            }

            $chromosome[$sid] = $genes;
        }

        // Compact every section so no gaps appear between subjects on any day
        $chromosome = $this->compactChromosome($chromosome);

        return $chromosome;
    }

    // =========================================================================
    //  COMPACT — push all genes to earliest slots so no gaps appear mid-day.
    //
    //  For each day, collect all assigned (non-lab, non-block) theory genes,
    //  sort the FREE slots on that day by period_no ASC, then re-assign
    //  those genes to the first N free slots.  Labs / Ancient Wisdom /
    //  Summer Internship keep their exact slots (continuity must be preserved).
    //
    //  Called at the END of createChromosome and after repairChromosome.
    // =========================================================================

    private function compactChromosome($chromosome) {
        foreach ($this->sections as $sec) {
            $sid   = (int)$sec['section_id'];
            $genes = $chromosome[$sid] ?? [];

            // Separate genes into "fixed" (labs + blocks) and "theory" (moveable)
            $fixedIdx   = [];   // gene indices that must not move
            $theoryIdx  = [];   // gene indices that can be compacted

            foreach ($genes as $gi => $g) {
                $sub  = $this->subjects[(int)$g['subject_id']];
                $name = $sub['subject_name'];
                if ($sub['is_lab'] == 1
                    || $name === 'Ancient Wisdom'
                    || $name === 'Summer Internship') {
                    $fixedIdx[] = $gi;
                } else {
                    $theoryIdx[] = $gi;
                }
            }

            // Slots occupied by fixed genes
            $fixedSlots = [];
            foreach ($fixedIdx as $gi)
                $fixedSlots[] = $genes[$gi]['timeslot_idx'];
            $fixedSet = array_flip($fixedSlots);

            // For each day, get free slots sorted ASC (excluding fixed-occupied)
            $freeSlotsPerDay = [];
            foreach (self::$DAY_ORDER as $day) {
                $slots = $this->slotsByDay[$day] ?? [];
                // Sort by period_no ascending
                usort($slots, fn($a,$b) => $this->idxToPeriod[$a] - $this->idxToPeriod[$b]);
                $free = [];
                foreach ($slots as $idx)
                    if (!isset($fixedSet[$idx])) $free[] = $idx;
                $freeSlotsPerDay[$day] = $free;
            }

            // Group theory genes by day
            $theoryByDay = [];
            foreach ($theoryIdx as $gi) {
                $day = $this->idxToDay[$genes[$gi]['timeslot_idx']];
                $theoryByDay[$day][] = $gi;
            }

            // Re-assign each day's theory genes to earliest free slots on that day
            foreach ($theoryByDay as $day => $indices) {
                $available = $freeSlotsPerDay[$day];
                // Keep only as many slots as we have genes for this day
                $slotsToUse = array_slice($available, 0, count($indices));
                foreach ($indices as $i => $gi) {
                    if (isset($slotsToUse[$i]))
                        $genes[$gi]['timeslot_idx'] = $slotsToUse[$i];
                }
            }

            $chromosome[$sid] = $genes;
        }
        return $chromosome;
    }

    // =========================================================================
    //  REPAIR — runs after every crossover and mutation
    //
    //  Fixes theory subjects that ended up on the same day more than once.
    //  (Crossover can combine genes from both parents landing on the same day.)
    // =========================================================================

    private function repairChromosome($chromosome) {
        foreach ($this->sections as $section) {
            $sid   = (int)$section['section_id'];
            $genes = $chromosome[$sid] ?? [];

            $usedSlots = array_column($genes, 'timeslot_idx');

            // Build: subId => day => [gene_indices]
            $subDayGenes = [];
            foreach ($genes as $gi => $g) {
                $subId = (int)$g['subject_id'];
                $sub   = $this->subjects[$subId];
                if ($sub['is_lab'] == 1) continue;
                if (in_array($sub['subject_name'],
                    ['Summer Internship','Ancient Wisdom'], true)) continue;
                $day = $this->idxToDay[$g['timeslot_idx']];
                $subDayGenes[$subId][$day][] = $gi;
            }

            foreach ($subDayGenes as $subId => $dayMap) {
                foreach ($dayMap as $day => $indices) {
                    if (count($indices) <= 1) continue;

                    // Keep first occurrence, relocate duplicates
                    array_shift($indices);
                    foreach ($indices as $gi) {
                        $subDays  = array_keys($subDayGenes[$subId]);
                        $freeDays = array_diff(self::$DAY_ORDER, $subDays);

                        $newSlot = null;
                        foreach ($freeDays as $freeDay) {
                            foreach (($this->slotsByDay[$freeDay] ?? []) as $s) {
                                if (!in_array($s, $usedSlots, true)) {
                                    $newSlot = $s; break;
                                }
                            }
                            if ($newSlot !== null) break;
                        }

                        if ($newSlot !== null) {
                            $oldSlot   = $genes[$gi]['timeslot_idx'];
                            $usedSlots = array_values(array_diff($usedSlots, [$oldSlot]));
                            $usedSlots[] = $newSlot;
                            $genes[$gi]['timeslot_idx'] = $newSlot;
                            $newDay = $this->idxToDay[$newSlot];
                            unset($subDayGenes[$subId][$day]);
                            $subDayGenes[$subId][$newDay][] = $gi;
                        }
                    }
                }
            }

            $chromosome[$sid] = array_values($genes);
        }
        // Re-compact after repair so gaps from relocation are eliminated
        $chromosome = $this->compactChromosome($chromosome);

        return $chromosome;
    }

    // =========================================================================
    //  FITNESS FUNCTION
    // =========================================================================

    private function calculateFitness($chromosome) {
        $violations = 0;

        foreach ($this->sections as $section) {
            $sid   = (int)$section['section_id'];
            $genes = $chromosome[$sid] ?? [];

            // C1: No timeslot collision within section
            $slotCount = [];
            foreach ($genes as $g)
                $slotCount[$g['timeslot_idx']] = ($slotCount[$g['timeslot_idx']] ?? 0) + 1;
            foreach ($slotCount as $cnt)
                if ($cnt > 1) $violations += ($cnt-1) * 3;

            // Index by subject
            $subSlots = [];
            foreach ($genes as $g) $subSlots[$g['subject_id']][] = $g['timeslot_idx'];

            // C2: Weekly hours exact
            foreach ($this->sectionSubjects[$sid] as $subId) {
                $diff = abs($this->weeklyHours[$subId] - count($subSlots[$subId] ?? []));
                $violations += $diff * 3;
            }

            // C3: Theory max once per day (high penalty)
            $dailySub = [];
            foreach ($genes as $g) {
                $subId = (int)$g['subject_id'];
                $sub   = $this->subjects[$subId];
                if ($sub['is_lab'] == 1) continue;
                if (in_array($sub['subject_name'],
                    ['Summer Internship','Ancient Wisdom'], true)) continue;
                $day = $this->idxToDay[$g['timeslot_idx']];
                $dailySub[$day][$subId] = ($dailySub[$day][$subId] ?? 0) + 1;
                if ($dailySub[$day][$subId] > 1) $violations += 5;
            }

            // C4: Lab = valid 3-slot block
            foreach ($this->sectionSubjects[$sid] as $subId) {
                if ($this->subjects[$subId]['is_lab'] != 1) continue;
                $slots = $subSlots[$subId] ?? []; sort($slots);
                if (count($slots) !== 3) $violations += abs(3-count($slots)) * 3;
                elseif (!$this->isValidLabBlock($slots)) $violations += 4;
            }

            // C5: Summer Internship = 2+2 on different days
            foreach ($this->sectionSubjects[$sid] as $subId) {
                if ($this->subjects[$subId]['subject_name'] !== 'Summer Internship') continue;
                $slots = $subSlots[$subId] ?? [];
                if (count($slots) !== 4) $violations += abs(4-count($slots)) * 3;
                else $violations += $this->violationsSummerInternship($slots) * 2;
            }

            // C6: Ancient Wisdom = 2 continuous
            foreach ($this->sectionSubjects[$sid] as $subId) {
                if ($this->subjects[$subId]['subject_name'] !== 'Ancient Wisdom') continue;
                $slots = $subSlots[$subId] ?? [];
                if (count($slots) !== 2) $violations += abs(2-count($slots)) * 3;
                elseif (!$this->areContinuous($slots[0],$slots[1])) $violations += 2;
            }

            // C7: Soft — penalise empty days
            $daysUsed = [];
            foreach ($genes as $g) $daysUsed[$this->idxToDay[$g['timeslot_idx']]] = true;
            $violations += count(self::$DAY_ORDER) - count($daysUsed);

            // C8: Penalise gaps — a gap is an empty slot between two occupied slots
            // on the same day. Heavy penalty so GA strongly avoids mid-day holes.
            foreach (self::$DAY_ORDER as $day) {
                $daySlots = $this->slotsByDay[$day] ?? [];
                // Sort by period_no ascending
                usort($daySlots, fn($a,$b) => $this->idxToPeriod[$a] - $this->idxToPeriod[$b]);
                $usedSet = [];
                foreach ($genes as $g)
                    if ($this->idxToDay[$g['timeslot_idx']] === $day)
                        $usedSet[$g['timeslot_idx']] = true;
                // Find first and last used slot indices
                $first = -1; $last = -1;
                foreach ($daySlots as $pos => $idx) {
                    if (isset($usedSet[$idx])) {
                        if ($first === -1) $first = $pos;
                        $last = $pos;
                    }
                }
                // Count empty slots between first and last used
                if ($first >= 0 && $last > $first) {
                    for ($pos = $first+1; $pos < $last; $pos++) {
                        if (!isset($usedSet[$daySlots[$pos]]))
                            $violations += 6;  // heavy — gap is a hard structural issue
                    }
                }
            }
        }

        // C8: Faculty double-booking across sections
        $facSlot = [];
        foreach ($this->sections as $sec) {
            $sid   = (int)$sec['section_id'];
            $genes = $chromosome[$sid] ?? [];
            foreach ($genes as $g) {
                $key = $sid.'_'.$g['subject_id'];
                if (!isset($this->faculty[$key])) continue;
                foreach ($this->faculty[$key] as $fid) {
                    $s = $g['timeslot_idx'];
                    if (isset($facSlot[$fid][$s])) $violations += 3;
                    else $facSlot[$fid][$s] = true;
                }
            }
        }

        // C9: Faculty max 4 lectures/day
        $facDay = [];
        foreach ($this->sections as $sec) {
            $sid   = (int)$sec['section_id'];
            $genes = $chromosome[$sid] ?? [];
            foreach ($genes as $g) {
                $key = $sid.'_'.$g['subject_id'];
                if (!isset($this->faculty[$key])) continue;
                $day = $this->idxToDay[$g['timeslot_idx']];
                foreach ($this->faculty[$key] as $fid) {
                    $facDay[$fid][$day] = ($facDay[$fid][$day] ?? 0) + 1;
                    if ($facDay[$fid][$day] > 4) $violations++;
                }
            }
        }

        $upper = count($this->sections) * count($this->timeslots) * 5;
        return max(0.0, 1.0 - ($violations / max(1, $upper)));
    }

    // =========================================================================
    //  SELECTION
    // =========================================================================

    private function tournamentSelection($population, $fitness, $k = 5) {
        $best = null; $bestFit = -1.0;
        for ($i = 0; $i < $k; $i++) {
            $idx = array_rand($population);
            if ($fitness[$idx] > $bestFit) {
                $best    = $population[$idx];
                $bestFit = $fitness[$idx];
            }
        }
        return $best;
    }

    // =========================================================================
    //  CROSSOVER
    // =========================================================================

    private function crossover($p1, $p2) {
        $child = [];
        foreach ($this->sections as $sec) {
            $sid = (int)$sec['section_id'];
            $g1  = $p1[$sid] ?? []; $g2 = $p2[$sid] ?? [];
            $len = min(count($g1), count($g2));
            if ($len < 2) { $child[$sid] = mt_rand(0,1) ? $g1 : $g2; continue; }
            $pt1 = mt_rand(0,$len-1); $pt2 = mt_rand($pt1,$len-1);
            $child[$sid] = array_merge(
                array_slice($g1,0,$pt1),
                array_slice($g2,$pt1,$pt2-$pt1),
                array_slice($g1,$pt2)
            );
        }
        return $child;
    }

    // =========================================================================
    //  MUTATION
    // =========================================================================

    private function mutate($chromosome) {
        foreach ($this->sections as $sec) {
            $sid   = (int)$sec['section_id'];
            $genes = &$chromosome[$sid];
            $n     = count($genes);

            for ($i = 0; $i < $n; $i++) {
                if ((mt_rand()/mt_getrandmax()) > 0.15) continue;
                $subId = (int)$genes[$i]['subject_id'];
                $sub   = $this->subjects[$subId];

                if ($sub['is_lab'] == 1) {
                    $others = $this->slotsUsedByOthers($genes, $subId);
                    $block  = $this->pickRandomLabBlock($others);
                    if ($block) {
                        $genes = array_values(array_filter($genes,
                            fn($g) => (int)$g['subject_id'] !== $subId));
                        foreach ($block as $s)
                            $genes[] = ['subject_id'=>$subId,'timeslot_idx'=>$s];
                        $n = count($genes); $i = -1;
                    }

                } elseif ($sub['subject_name'] === 'Ancient Wisdom') {
                    $others = $this->slotsUsedByOthers($genes, $subId);
                    $block  = $this->pickRandomDoubleBlock($others);
                    if ($block) {
                        $genes = array_values(array_filter($genes,
                            fn($g) => (int)$g['subject_id'] !== $subId));
                        foreach ($block as $s)
                            $genes[] = ['subject_id'=>$subId,'timeslot_idx'=>$s];
                        $n = count($genes); $i = -1;
                    }

                } else {
                    // Theory: prefer swapping with a gene on a different day
                    $currentDay = $this->idxToDay[$genes[$i]['timeslot_idx']];
                    $candidates = [];
                    foreach ($genes as $j => $g) {
                        if ($j === $i) continue;
                        $s = $this->subjects[(int)$g['subject_id']];
                        if ($s['is_lab'] == 1) continue;
                        if (in_array($s['subject_name'],
                            ['Ancient Wisdom','Summer Internship'], true)) continue;
                        if ($this->idxToDay[$g['timeslot_idx']] !== $currentDay)
                            $candidates[] = $j;
                    }
                    if (empty($candidates)) {
                        foreach ($genes as $j => $g) {
                            if ($j === $i) continue;
                            $s = $this->subjects[(int)$g['subject_id']];
                            if ($s['is_lab'] == 1) continue;
                            if (in_array($s['subject_name'],
                                ['Ancient Wisdom','Summer Internship'], true)) continue;
                            $candidates[] = $j;
                        }
                    }
                    if (!empty($candidates)) {
                        $j = $candidates[array_rand($candidates)];
                        [$genes[$i]['timeslot_idx'], $genes[$j]['timeslot_idx']] =
                         [$genes[$j]['timeslot_idx'], $genes[$i]['timeslot_idx']];
                    }
                }
            }
            unset($genes);
        }
        return $chromosome;
    }

    // =========================================================================
    //  SAVE TIMETABLE
    // =========================================================================

    private function saveTimetable($chromosome) {
        $this->conn->query("DELETE FROM timetable_faculty");
        $this->conn->query("DELETE FROM timetable");

        $r      = $this->conn->query("SELECT room_id FROM classrooms LIMIT 1");
        $roomId = ($r && ($row = $r->fetch_assoc())) ? (int)$row['room_id'] : 1;

        // ── 1. Save GA-generated genes for every section ─────────────────────
        foreach ($this->sections as $sec) {
            $sid   = (int)$sec['section_id'];
            $genes = $chromosome[$sid] ?? [];
            foreach ($genes as $gene) {
                $subjectId  = (int)$gene['subject_id'];
                $timeslotId = (int)$this->timeslots[$gene['timeslot_idx']]['timeslot_id'];

                $st = $this->conn->prepare(
                    "INSERT INTO timetable (section_id,subject_id,room_id,timeslot_id)
                     VALUES (?,?,?,?)");
                $st->bind_param("iiii",$sid,$subjectId,$roomId,$timeslotId);
                $st->execute();
                $ttId = (int)$this->conn->insert_id;
                $st->close();

                $key = $sid.'_'.$subjectId;
                if (isset($this->faculty[$key]))
                    foreach ($this->faculty[$key] as $fid) {
                        $st2 = $this->conn->prepare(
                            "INSERT INTO timetable_faculty (timetable_id,faculty_id)
                             VALUES (?,?)");
                        $st2->bind_param("ii",$ttId,$fid);
                        $st2->execute(); $st2->close();
                    }
            }
        }

        // ── 2. Insert all fixed OE slots for every section ──────────────────
        if (!empty($this->oeTimeslotIds)) {

            // Get or create the "Open Elective" subject
            $oeSubjectId = 0;
            foreach ($this->subjects as $subId => $sub) {
                if (strtolower(trim($sub['subject_name'])) === 'open elective') {
                    $oeSubjectId = $subId;
                    break;
                }
            }
            if ($oeSubjectId === 0) {
                $ins = $this->conn->prepare(
                    "INSERT INTO subjects
                        (subject_name, branch, is_lab, lecture_hours)
                     VALUES ('Open Elective', 'COMMON', 0, 0)");
                $ins->execute();
                $oeSubjectId = (int)$this->conn->insert_id;
                $ins->close();
                $this->subjects[$oeSubjectId] = [
                    'subject_id'    => $oeSubjectId,
                    'subject_name'  => 'Open Elective',
                    'branch'        => 'COMMON',
                    'is_lab'        => 0,
                    'lecture_hours' => 0,
                ];
            }

            foreach ($this->sections as $sec) {
                $sid = (int)$sec['section_id'];
                foreach ($this->oeTimeslotIds as $oeTs) {
                    $st = $this->conn->prepare(
                        "INSERT INTO timetable (section_id,subject_id,room_id,timeslot_id)
                         VALUES (?,?,?,?)");
                    $st->bind_param("iiii", $sid, $oeSubjectId, $roomId, $oeTs);
                    $st->execute();
                    $st->close();
                }
            }
        }

        // ── 3. Fill remaining empty slots with filler subjects ────────────────
        // Strategy: after all real subjects + OE are placed, any slot still
        // empty gets MTP / ECA-CCA / SPORTS rotating in period_no DESC order
        // (latest periods first) so free periods are always at the END of each day.
        //
        // Filler subjects are auto-created in the subjects table if missing.
        $fillerNames = ['MTP', 'ECA/CCA', 'SPORTS'];
        $fillerIds   = [];
        foreach ($fillerNames as $fname) {
            $r = $this->conn->query(
                "SELECT subject_id FROM subjects
                 WHERE LOWER(TRIM(subject_name)) = LOWER('$fname') LIMIT 1");
            if ($r && $r->num_rows > 0) {
                $fillerIds[] = (int)$r->fetch_assoc()['subject_id'];
            } else {
                $this->conn->query(
                    "INSERT INTO subjects (subject_name, branch, is_lab, lecture_hours)
                     VALUES ('$fname', 'COMMON', 0, 0)");
                $fillerIds[] = (int)$this->conn->insert_id;
            }
        }

        // Build after-lunch period_nos (for filler restriction)
        // Detect lunch gap by time — same logic as buildSlotMaps
        $allPeriodRows = [];
        $r = $this->conn->query(
            "SELECT DISTINCT period_no, start_time, end_time FROM timeslots ORDER BY period_no");
        while ($row = $r->fetch_assoc()) $allPeriodRows[(int)$row['period_no']] = $row;
        ksort($allPeriodRows);
        $pNos = array_keys($allPeriodRows);
        $maxGap = 0; $gapAfter = -1;
        for ($i = 0; $i < count($pNos)-1; $i++) {
            [$eh,$em] = explode(':', $allPeriodRows[$pNos[$i]]['end_time']);
            [$sh,$sm] = explode(':', $allPeriodRows[$pNos[$i+1]]['start_time']);
            $gap = ((int)$sh*60+(int)$sm) - ((int)$eh*60+(int)$em);
            if ($gap > $maxGap) { $maxGap = $gap; $gapAfter = $i; }
        }
        // After-lunch period_nos — filler is ONLY allowed here
        $afterLunchPeriods = ($gapAfter >= 0 && $maxGap > 10)
            ? array_flip(array_slice($pNos, $gapAfter + 1))   // flip for O(1) lookup
            : array_flip(array_slice($pNos, (int)ceil(count($pNos)/2)));

        foreach ($this->sections as $sec) {
            $sid = (int)$sec['section_id'];

            // Load every timeslot with day+period_no for this section
            $r = $this->conn->query("
                SELECT ts.timeslot_id, ts.day, ts.period_no
                FROM   timeslots ts
                ORDER  BY FIELD(ts.day,'Monday','Tuesday','Wednesday',
                                'Thursday','Friday','Saturday'), ts.period_no
            ");
            $allSlots = [];
            while ($row = $r->fetch_assoc()) $allSlots[] = $row;

            // Which timeslots already have a subject for this section?
            $r = $this->conn->query(
                "SELECT timeslot_id FROM timetable WHERE section_id = $sid");
            $occupied = [];
            while ($row = $r->fetch_assoc()) $occupied[(int)$row['timeslot_id']] = true;

            // Group slots by day
            $byDay = [];
            foreach ($allSlots as $slot)
                $byDay[$slot['day']][] = $slot;  // already sorted period_no ASC

            $fi = 0;
            foreach ($byDay as $day => $daySlots) {
                // Find the last period_no that has a real subject (after lunch only check)
                $lastRealPeriod = 0;
                foreach ($daySlots as $slot) {
                    if (isset($occupied[(int)$slot['timeslot_id']]))
                        $lastRealPeriod = max($lastRealPeriod, (int)$slot['period_no']);
                }

                // Walk periods in DESC order — fill only trailing empty afternoon slots
                // Stop as soon as we hit an occupied slot (no gaps between fillers)
                $slotsDesc = array_reverse($daySlots);
                $fillingMode = true;
                foreach ($slotsDesc as $slot) {
                    $tsId     = (int)$slot['timeslot_id'];
                    $periodNo = (int)$slot['period_no'];

                    // Only fill afternoon periods
                    if (!isset($afterLunchPeriods[$periodNo])) {
                        $fillingMode = false;  // crossed into morning — stop for this day
                        break;
                    }

                    if (isset($occupied[$tsId])) {
                        // Hit a real subject — stop filling for this day
                        $fillingMode = false;
                        break;
                    }

                    if ($fillingMode) {
                        // This is a trailing empty afternoon slot — assign filler
                        $fillerSubjectId = $fillerIds[$fi % count($fillerIds)];
                        $fi++;
                        $st = $this->conn->prepare(
                            "INSERT INTO timetable (section_id,subject_id,room_id,timeslot_id)
                             VALUES (?,?,?,?)");
                        $st->bind_param("iiii", $sid, $fillerSubjectId, $roomId, $tsId);
                        $st->execute();
                        $st->close();
                        $occupied[$tsId] = true;  // mark so subsequent passes skip it
                    }
                }
            }
        }
    }

    // =========================================================================
    //  SLOT HELPERS
    // =========================================================================

    private function buildFreeByDay($usedSlots) {
        // Slots sorted by period_no ASC so theory subjects fill earliest periods first.
        // Free periods naturally fall at the END of the day for filler subjects.
        $used = array_flip($usedSlots); $byDay = [];
        foreach (self::$DAY_ORDER as $day) {
            $free = [];
            foreach (($this->slotsByDay[$day] ?? []) as $idx)
                if (!isset($used[$idx])) $free[] = $idx;
            // Sort by period_no ascending (earliest first)
            usort($free, fn($a, $b) => $this->idxToPeriod[$a] - $this->idxToPeriod[$b]);
            $byDay[$day] = $free;
        }
        return $byDay;
    }

    private function freeSlotsShuffled($usedSlots) {
        $used = array_flip($usedSlots); $free = [];
        foreach (array_keys($this->timeslots) as $idx)
            if (!isset($used[$idx])) $free[] = $idx;
        shuffle($free);
        return $free;
    }

    private function pickRandomLabBlock($used) {
        $usedSet = array_flip($used); $avail = [];
        foreach ($this->validLabBlocks as $b)
            if (!isset($usedSet[$b[0]]) && !isset($usedSet[$b[1]]) && !isset($usedSet[$b[2]]))
                $avail[] = $b;
        return $avail ? $avail[array_rand($avail)] : null;
    }

    private function pickRandomDoubleBlock($used, $excludeDays = []) {
        $usedSet = array_flip($used); $avail = [];
        foreach ($this->validDoubleBlocks as $b) {
            if (isset($usedSet[$b[0]]) || isset($usedSet[$b[1]])) continue;
            if ($excludeDays && in_array($this->idxToDay[$b[0]],$excludeDays,true)) continue;
            $avail[] = $b;
        }
        return $avail ? $avail[array_rand($avail)] : null;
    }

    private function pickDoubleBlockPreferLast($used, $excludeDays = []) {
        // "Prefer last" = blocks that start in the final 2 periods of the day.
        // With periods 1-6, the last 2 are 5 and 6, so threshold = total_periods - 1.
        $totalPeriods = count(array_unique(array_column($this->timeslots, 'period_no')));
        $threshold    = $totalPeriods - 1;  // e.g. 5 for a 6-period day

        $usedSet = array_flip($used); $pref = []; $fall = [];
        foreach ($this->validDoubleBlocks as $b) {
            if (isset($usedSet[$b[0]]) || isset($usedSet[$b[1]])) continue;
            if ($excludeDays && in_array($this->idxToDay[$b[0]], $excludeDays, true)) continue;
            if ($this->idxToPeriod[$b[0]] >= $threshold) $pref[] = $b;
            else $fall[] = $b;
        }
        if ($pref) return $pref[array_rand($pref)];
        if ($fall) return $fall[array_rand($fall)];
        return null;
    }

    private function slotsUsedByOthers($genes, $excludeSubId) {
        $s = [];
        foreach ($genes as $g)
            if ((int)$g['subject_id'] !== (int)$excludeSubId) $s[] = $g['timeslot_idx'];
        return $s;
    }

    // =========================================================================
    //  CONSTRAINT CHECKERS
    // =========================================================================

    private function areContinuous($a, $b) {
        return isset($this->doubleBlockSet["$a\_$b"]);
    }

    private function isValidLabBlock($slots) {
        sort($slots);
        foreach ($this->validLabBlocks as $b) {
            $c = $b; sort($c);
            if ($c === $slots) return true;
        }
        return false;
    }

    private function violationsSummerInternship($slots) {
        $parts = [
            [[$slots[0],$slots[1]],[$slots[2],$slots[3]]],
            [[$slots[0],$slots[2]],[$slots[1],$slots[3]]],
            [[$slots[0],$slots[3]],[$slots[1],$slots[2]]],
        ];
        $best = PHP_INT_MAX;
        foreach ($parts as [$pA,$pB]) {
            $v = 0;
            if (!$this->areContinuous($pA[0],$pA[1])) $v++;
            if (!$this->areContinuous($pB[0],$pB[1])) $v++;
            if ($this->idxToDay[$pA[0]] === $this->idxToDay[$pB[0]]) $v++;
            $best = min($best,$v);
        }
        return $best === PHP_INT_MAX ? 3 : $best;
    }

    private function sectionBranch($name) {
        return stripos($name,'IoT') !== false ? 'IOT' : 'AIML';
    }
}
?>