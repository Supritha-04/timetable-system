<?php
include("auth_check.php");
include("../config/db.php");
include("../layout/header.php");
include("../layout/sidebar.php");

if(isset($_POST['add'])){

$name=$_POST['subject_name'];
$credits=$_POST['credits'];
$branch=$_POST['branch'];
$lecture=$_POST['lecture_hours'];
$practical=$_POST['practical_hours'];
$is_lab=$_POST['is_lab'];

$stmt=$conn->prepare("
INSERT INTO subjects
(subject_name,credits,is_lab,branch,lecture_hours,practical_hours)
VALUES(?,?,?,?,?,?)
");

$stmt->bind_param("siisii",$name,$credits,$is_lab,$branch,$lecture,$practical);
$stmt->execute();
}

if(isset($_GET['delete'])){
$id=$_GET['delete'];
$conn->query("DELETE FROM subjects WHERE subject_id=$id");
}

$subjects=$conn->query("SELECT * FROM subjects");
?>

<div class="container-fluid">

<h3 class="mt-3">Manage Subjects</h3>

<div class="card p-4 mt-3">

<h5>Add Subject</h5>

<form method="POST" class="row g-3">

<div class="col-md-3">
<input type="text" name="subject_name" class="form-control" placeholder="Subject Name" required>
</div>

<div class="col-md-1">
<input type="number" name="credits" class="form-control" placeholder="Credits">
</div>

<div class="col-md-2">
<select name="branch" class="form-control">
<option value="AIML">AIML</option>
<option value="IOT">IOT</option>
<option value="COMMON">COMMON</option>
</select>
</div>

<div class="col-md-2">
<input type="number" name="lecture_hours" class="form-control" placeholder="Lecture Hrs">
</div>

<div class="col-md-2">
<input type="number" name="practical_hours" class="form-control" placeholder="Practical Hrs">
</div>

<div class="col-md-1">
<select name="is_lab" class="form-control">
<option value="0">Theory</option>
<option value="1">Lab</option>
</select>
</div>

<div class="col-md-1">
<button class="btn btn-success w-100" name="add">
<i class="fa fa-plus"></i>
</button>
</div>

</form>

<hr>

<table class="table table-bordered table-striped">

<thead class="table-dark">
<tr>
<th>ID</th>
<th>Name</th>
<th>Branch</th>
<th>Credits</th>
<th>Lecture</th>
<th>Practical</th>
<th>Type</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row=$subjects->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['subject_id']; ?></td>
<td><?php echo $row['subject_name']; ?></td>
<td><?php echo $row['branch']; ?></td>
<td><?php echo $row['credits']; ?></td>
<td><?php echo $row['lecture_hours']; ?></td>
<td><?php echo $row['practical_hours']; ?></td>
<td><?php echo $row['is_lab']?"Lab":"Theory"; ?></td>

<td>
<a href="?delete=<?php echo $row['subject_id']; ?>" 
class="btn btn-danger btn-sm"
onclick="return confirm('Delete Subject?')">
Delete
</a>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>
</div>

<?php include("../layout/footer.php"); ?>