<?php
	include "pgsql.php";

	if (isset($_POST["input"])) {
		$input = $_POST["input"];
		$output = array("");
		$index = 0;

		$query = "SELECT * FROM course_records WHERE LOWER(course_name) LIKE LOWER('%$input%') OR LOWER(subject_area||catalogue_code) LIKE LOWER('%$input%') ORDER BY (subject_area||catalogue_code)";
		$result = pg_query($aims_db_connection, $query);

		echo "<ul id='reslist' class='reslist'>";
		while ($row = pg_fetch_array($result)) {
			$output[$index] = $row["subject_area"] . $row["catalogue_code"] . " - " . $row["course_name"];
			echo "<br><li id='res" . $index . "'><a>" . $row["subject_area"] . $row["catalogue_code"] . " - " . $row["course_name"] . "</a></li>";
			$index++;
		}
		echo "</ul>";
	}
?>

<script>
	$(document).ready(function() {
		$("#reslist").find("li").click(function() {
			$("#reslist").hide();
			var output = <?php echo json_encode($output); ?>;
			var index = this.id.charAt(3);

			if (output[index] != "") {
				var course = output[index].split(" ");
				var courseCode = course[0];
				var subjectArea = courseCode.substring(0, 4);
				var catalogueCode = courseCode.substring(4, 8);

				$.post("inc/info.php", {subjectArea: subjectArea, catalogueCode: catalogueCode}, function(data) {
					$("#reslist").html(data);
				});

				setTimeout(function() {
					$("#reslist").slideDown("slow");
				}, 500);
			}
		});
	});
</script>

<script>
	$(document).ready(function() {
		$("#reslist").keydown(function() {
			var output = <?php echo json_encode($output); ?>;
			var index = this.id.charAt(3);

		});
	});
</script>
