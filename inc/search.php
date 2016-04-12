<?php
	include("pgsql.php");

	if (isset($_POST["input"]) && isset($_POST["type"])) {
		$input = $_POST["input"];
		$type = $_POST["type"];
		$output = array("");
		$index = 0;

		echo "<ul id='reslist' class='reslist'>";
		if ($type == "course") {
			$query = "SELECT * FROM course_records
								WHERE  LOWER(subject_area||catalogue_code) LIKE LOWER('%$input%') OR LOWER(course_name) LIKE LOWER('%$input%')
								ORDER BY (subject_area||catalogue_code)";
			$result = pg_query($aims_db_connection, $query);

			while ($rows = pg_fetch_array($result)) {
				$output[$index] = $rows["subject_area"] . $rows["catalogue_code"] . " - " . $rows["course_name"];
				echo "<br><li id='res" . $index . "'><a>" . $rows["subject_area"] . $rows["catalogue_code"] . " - " . $rows["course_name"] . "</a></li>";
				$index++;
			}
		} else if ($type == "stream") {
			$query = "SELECT * FROM stream_records
								WHERE LOWER(subject_area||strand||stream_type) LIKE LOWER('%$input%') OR LOWER(stream_name) LIKE LOWER('%$input%')
								ORDER BY (subject_area||strand||stream_type)";
			$result = pg_query($aims_db_connection, $query);

			while ($rows = pg_fetch_array($result)) {
				$output[$index] = $rows["subject_area"] . $rows["strand"] . $rows["stream_type"] . " - " . $rows["stream_name"];
				echo "<br><li id='res" . $index . "'><a>" . $rows["subject_area"] . $rows["strand"] . $rows["stream_type"] . " - " . $rows["stream_name"] . "</a></li>";
				$index++;
			}
		} else if ($type == "program") {
			$query = "SELECT * FROM program_records
								WHERE LOWER(code) LIKE LOWER('%$input%') OR LOWER(program_name) LIKE LOWER('%$input%')
								ORDER BY code, program_name";
			$result = pg_query($aims_db_connection, $query);

			while ($rows = pg_fetch_array($result)) {
				$output[$index] = $rows["code"] . " - " . $rows["program_name"];
				echo "<br><li id='res" . $index . "'><a>" . $rows["code"] . " - " . $rows["program_name"] . "</a></li>";
				$index++;
			}
		}
		echo "</ul>";
	}
?>

<script>
	$(document).ready(function() {
		$("#reslist").find("li").click(function() {
			$("#searchinput").val("");
			$("#reslist").hide();
			var type = <?php echo json_encode($type); ?>;
			var output = <?php echo json_encode($output); ?>;
			var index = this.id.charAt(3);

			if (output[index] != "") {
				var outputParams = output[index].split(" - ");
				var code = outputParams[0];
				var name = outputParams[1];

				$.post("inc/info.php", {type: type, code: code}, function(data) {
					$("#info").html(data);
				});
			}
		});
	});
</script>

<script>
	$(document).ready(function() {
		$("#searchinput").keydown(function(e) {
			var output = <?php echo json_encode($output); ?>;
			var index = 0;

			if (e.keyCode == 13) {

			} else if (e.keyCode == 38) {

			} else if (e.keyCode == 40) {

			}
		});
	});
</script>
