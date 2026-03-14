<?php
include("auth_check.php");
include("../layout/header.php");
?>

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mt-3">

<h3>Admin Dashboard</h3>

<a href="../auth/logout.php" class="btn btn-danger">
<i class="fa fa-sign-out-alt"></i> Logout
</a>

</div>


<div class="row mt-4">

<!-- Faculty -->
<div class="col-md-4">
<div class="card p-4 text-center shadow">

<i class="fa fa-users fa-2x text-primary"></i>

<h4 class="mt-3">Faculty</h4>

<p>Manage faculty members</p>

<a href="faculty.php" class="btn btn-primary">
Manage Faculty
</a>

</div>
</div>


<!-- Subjects -->
<div class="col-md-4">
<div class="card p-4 text-center shadow">

<i class="fa fa-book fa-2x text-success"></i>

<h4 class="mt-3">Subjects</h4>

<p>Manage subjects</p>

<a href="subjects.php" class="btn btn-success">
Manage Subjects
</a>

</div>
</div>


<!-- Assign Faculty -->
<div class="col-md-4">
<div class="card p-4 text-center shadow">

<i class="fa fa-link fa-2x text-warning"></i>

<h4 class="mt-3">Assign Faculty</h4>

<p>Assign faculty to subjects</p>

<a href="assign_faculty.php" class="btn btn-warning">
Assign Faculty
</a>

</div>
</div>

</div>



<div class="row mt-4">

<!-- Open Elective -->
<div class="col-md-3">
<div class="card p-4 text-center shadow">

<i class="fa fa-bookmark fa-2x text-secondary"></i>

<h4 class="mt-3">Open Elective</h4>

<p>Manage open elective subjects</p>

<a href="open_elective.php" class="btn btn-secondary">
Open Elective
</a>

</div>
</div>


<!-- Generate Timetable -->
<div class="col-md-3">
<div class="card p-4 text-center shadow">

<i class="fa fa-cogs fa-2x text-danger"></i>

<h4 class="mt-3">Generate Timetable</h4>

<p>Generate timetable for all sections</p>

<a href="generate.php" class="btn btn-danger">
Generate Timetable
</a>

</div>
</div>


<!-- View Timetable -->
<div class="col-md-3">
<div class="card p-4 text-center shadow">

<i class="fa fa-table fa-2x text-info"></i>

<h4 class="mt-3">View Timetable</h4>

<p>View generated timetable</p>

<a href="view_timetable.php" class="btn btn-info">
View Timetable
</a>

</div>
</div>


<!-- View Faculty Timetable -->
<div class="col-md-3">
<div class="card p-4 text-center shadow">

<i class="fa fa-chalkboard-teacher fa-2x text-dark"></i>

<h4 class="mt-3">Faculty Timetable</h4>

<p>View faculty-wise timetable</p>

<a href="view_faculty_timetable.php" class="btn btn-dark">
Faculty Timetable
</a>

</div>
</div>

</div>

</div>

<?php include("../layout/footer.php"); ?>