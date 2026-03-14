<?php
include("auth_check.php");
include("../config/db.php");
include("../layout/header.php");
include("../layout/sidebar.php");

$subjects=$conn->query("SELECT * FROM subjects");
$sections=$conn->query("SELECT * FROM sections");
$faculty=$conn->query("SELECT * FROM faculty");
?>

<div class="container-fluid">

<h3 class="mt-3">Assign Faculty</h3>

<div class="card p-4 mt-3">

<form method="POST">

<div class="row mb-3">

<div class="col-md-4">
<select name="subject_id" class="form-control">

<?php while($s=$subjects->fetch_assoc()){ ?>

<option value="<?php echo $s['subject_id']; ?>">
<?php echo $s['subject_name']; ?>
</option>

<?php } ?>

</select>
</div>

<div class="col-md-4">
<select name="section_id" class="form-control">

<?php while($sec=$sections->fetch_assoc()){ ?>

<option value="<?php echo $sec['section_id']; ?>">
<?php echo $sec['section_name']; ?>
</option>

<?php } ?>

</select>
</div>

</div>

<label>Select Faculty</label><br>

<?php while($f=$faculty->fetch_assoc()){ ?>

<div class="form-check">
<input class="form-check-input" type="checkbox"
name="faculty_ids[]" value="<?php echo $f['faculty_id']; ?>">

<label class="form-check-label">
<?php echo $f['name']; ?>
</label>
</div>

<?php } ?>

<br>

<button class="btn btn-primary" name="assign">
Assign Faculty
</button>

</form>

</div>
</div>

<?php include("../layout/footer.php"); ?>