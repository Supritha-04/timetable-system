<?php
include("auth_check.php");
include("../config/db.php");
include("../layout/header.php");
include("../layout/sidebar.php");

if(isset($_POST['add'])){
    $name=$_POST['name'];
    $email=$_POST['email'];
    $max=$_POST['max'];

    $stmt=$conn->prepare("INSERT INTO faculty(name,email,max_lectures_per_day) VALUES(?,?,?)");
    $stmt->bind_param("ssi",$name,$email,$max);
    $stmt->execute();
}
?>

<div class="container-fluid">

<h3 class="mt-3">Manage Faculty</h3>

<div class="card p-4 mt-3">

<form method="POST" class="row g-3">

<div class="col-md-4">
<input type="text" name="name" class="form-control" placeholder="Faculty Name" required>
</div>

<div class="col-md-4">
<input type="email" name="email" class="form-control" placeholder="Email" required>
</div>

<div class="col-md-2">
<input type="number" name="max" class="form-control" placeholder="Max/Day" required>
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100" name="add">
<i class="fa fa-plus"></i> Add
</button>
</div>

</form>

<hr>

<table class="table table-bordered table-hover">

<thead class="table-dark">
<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Max Lectures/Day</th>
</tr>
</thead>

<tbody>

<?php
$res=$conn->query("SELECT * FROM faculty");
while($row=$res->fetch_assoc()){
?>

<tr>
<td><?php echo $row['faculty_id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['max_lectures_per_day']; ?></td>
</tr>

<?php } ?>

</tbody>
</table>

</div>
</div>

<?php include("../layout/footer.php"); ?>