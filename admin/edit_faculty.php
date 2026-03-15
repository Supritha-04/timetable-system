<?php
include("auth_check.php");
include("../config/db.php");
include("../layout/header.php");
include("../layout/sidebar.php");

$id = $_GET['id'];

$res = $conn->query("SELECT * FROM faculty WHERE faculty_id=$id");
$row = $res->fetch_assoc();

if(isset($_POST['update'])){

$name = $_POST['name'];
$email = $_POST['email'];
$max = $_POST['max'];

$stmt = $conn->prepare("
UPDATE faculty
SET name=?, email=?, max_lectures_per_day=?
WHERE faculty_id=?
");

$stmt->bind_param("ssii",$name,$email,$max,$id);
$stmt->execute();

echo "<script>
alert('Faculty Updated Successfully');
window.location='manage_faculty.php';
</script>";
}
?>

<div class="container-fluid">

<h3 class="mt-3">Edit Faculty</h3>

<div class="card p-4 mt-3">

<form method="POST" class="row g-3">

<div class="col-md-4">
<label>Faculty Name</label>
<input type="text" name="name" class="form-control"
value="<?php echo $row['name']; ?>" required>
</div>

<div class="col-md-4">
<label>Email</label>
<input type="email" name="email" class="form-control"
value="<?php echo $row['email']; ?>" required>
</div>

<div class="col-md-2">
<label>Max Lectures / Day</label>
<input type="number" name="max" class="form-control"
value="<?php echo $row['max_lectures_per_day']; ?>" required>
</div>

<div class="col-md-2 d-flex align-items-end">

<button class="btn btn-success w-100" name="update">
<i class="fa fa-save"></i> Update
</button>

</div>

</form>

</div>
</div>

<?php include("../layout/footer.php"); ?>