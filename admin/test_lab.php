<?php
// ================================
// DB CONNECTION
// ================================
include_once "../config/db.php";

// ================================
// ALGORITHMS
// ================================
include_once "../algorithms/constraints.php";
include_once "../algorithms/lab_scheduler.php";
include_once "../algorithms/internship_scheduler.php";
include_once "../algorithms/ancient_wisdom_scheduler.php";
include_once "../algorithms/theory_scheduler.php";

// ================================
// SECTIONS SETUP
// 3 AIML + 1 IOT
// ================================
$sections = [
    1 => 'AIML',   // AIML - A
    2 => 'AIML',   // AIML - B
    3 => 'AIML',   // AIML - C
    4 => 'IOT'     // IOT
];

// ================================
// FETCH TIMESLOTS
// ================================
$res = $conn->query("SELECT * FROM timeslots ORDER BY day, period_no");
$timeslots = [];

while ($row = $res->fetch_assoc()) {
    $timeslots[] = $row;
}

// ================================
// GLOBAL FACULTY SCHEDULE
// (DO NOT RESET INSIDE LOOP)
// ================================
$facultySchedule = [];

// ================================
// START GENERATION
// ================================
echo "<h3>Generating Timetables...</h3>";

foreach ($sections as $sectionId => $branch) {

    echo "<br><b>Section $sectionId ($branch)</b><br>";

    // ----------------------------
    // CLEAR OLD TIMETABLE DATA
    // ----------------------------
    $conn->query("DELETE FROM timetable WHERE section_id = $sectionId");

    // ----------------------------
    // SECTION TIMETABLE ARRAY
    // ----------------------------
    $timetable = [];
    $timetable[$sectionId] = [];

    // ----------------------------
    // 1️⃣ LABS (HARD CONSTRAINT)
    // ----------------------------
    scheduleLabs([$sectionId], $timeslots, $branch, $timetable, $facultySchedule);

    // ----------------------------
    // 2️⃣ INTERNSHIP (4 HOURS TOTAL)
    // ----------------------------
    scheduleInternship([$sectionId], $timeslots, $timetable);

    // ----------------------------
    // 3️⃣ ANCIENT WISDOM (2 HOURS)
    // ----------------------------
    scheduleAncientWisdom([$sectionId], $timeslots, $timetable);

    // ----------------------------
    // 4️⃣ THEORY (EXACT L HOURS)
    // ----------------------------
    scheduleTheorySubjects(
        [$sectionId],
        $timeslots,
        $timetable,
        $facultySchedule,
        $branch
    );

    // ----------------------------
    // SAVE TIMETABLE TO DATABASE
    // ----------------------------
    foreach ($timetable[$sectionId] as $slotIndex => $subjectId) {

    $timeslotId = $timeslots[$slotIndex]['timeslot_id'];
    $roomId = 1;

    // 1️⃣ Insert timetable row
    $stmt = $conn->prepare(
        "INSERT INTO timetable (section_id, subject_id, room_id, timeslot_id)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("iiii", $sectionId, $subjectId, $roomId, $timeslotId);

    if (!$stmt->execute()) {
        die("Timetable insert failed");
    }

    $timetableId = $stmt->insert_id;

    // 🔐 SAFETY CHECK
    if ($timetableId <= 0) {
        continue;
    }

    // 2️⃣ Insert faculty ONLY IF mapping exists
    $facRes = $conn->query("
        SELECT faculty_id 
        FROM subject_faculty 
        WHERE subject_id = $subjectId
    ");

    if ($facRes && $facRes->num_rows > 0) {
        while ($f = $facRes->fetch_assoc()) {

            $facultyId = $f['faculty_id'];

            $stmt2 = $conn->prepare(
                "INSERT INTO timetable_faculty (timetable_id, faculty_id)
                 VALUES (?, ?)"
            );
            $stmt2->bind_param("ii", $timetableId, $facultyId);
            $stmt2->execute();
        }
    }
}


    echo "✅ Timetable saved for section $sectionId<br>";
}
echo "<br><b>🎉 ALL TIMETABLES GENERATED SUCCESSFULLY</b>";
