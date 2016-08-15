<?php
include("pgsql.php");

if (isset($_POST["type"]) && isset($_POST["code"])) {
    $type = $_POST["type"];
    $code = $_POST["code"];

    if ($type == "course") {
        $query = "SELECT * FROM course_records
                WHERE  LOWER(subject_area||catalogue_code) LIKE LOWER('%$code%')
                    ORDER BY (subject_area||catalogue_code)";
        $result = pg_query($aims_db_connection, $query);
        $rows = pg_fetch_array($result);

        echo "<h2>" . $rows["subject_area"] . $rows["catalogue_code"] . " - " . $rows["course_name"] . "</h2>";
        echo "<div><table class='table table-striped'>";
        echo "<tbody><tr><td><b>UOC</b></td><td>" . $rows["uoc"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Career</b></td><td>" . $rows["career"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Teaching Rationale</b></td><td>" . $rows["teaching_rationale"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Course Aims</b></td><td>" . $rows["course_aims"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Description</b></td><td>" . $rows["description"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Assumed Knowledge</b></td><td>" . $rows["assumed_knowledge"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Enrolment Requirements</b></td><td>" . $rows["pre_req_conditions"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Class Timetable</b></td><td><a href='http://timetable.unsw.edu.au/2016/" . $rows["subject_area"] . $rows["catalogue_code"] . ".html'>See Class Timetable</a></td></tr></tbody>";
        echo "</table></div>";
    } else if ($type == "stream") {
        $query = "SELECT * FROM stream_records
                WHERE  LOWER(subject_area||strand||stream_type) LIKE LOWER('%$code%')
                    ORDER BY (subject_area||strand||stream_type)";
        $result = pg_query($aims_db_connection, $query);
        $rows = pg_fetch_array($result);

        echo "<h2>" . $rows["subject_area"] . $rows["strand"] . $rows["stream_type"] . " - " . $rows["stream_name"] . "</h2>";
        echo "<div><table class='table table-striped'>";
        echo "<tbody><tr><td><b>UOC</b></td><td>" . $rows["uoc"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Career</b></td><td>" . $rows["career"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Proposal Summary</b></td><td>" . $rows["proposal_summary"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Description</b></td><td>" . $rows["stream_description"] . "</td></tr></tbody>";
        echo "</table></div>";
    } else if ($type == "program") {
        $query = "SELECT * FROM program_records
                WHERE  LOWER(code) LIKE LOWER('%$code%')
                ORDER BY code, program_name";
        $result = pg_query($aims_db_connection, $query);
        $rows = pg_fetch_array($result);

        echo "<h2>" . $rows["code"] . " - " . $rows["program_name"] . "</h2>";
        echo "<div><table class='table table-striped'>";

        echo "<tbody><tr><td><b>UOC</b></td><td>" . $rows["minimum_uoc"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Career</b></td><td>" . $rows["career"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Proposal Summary</b></td><td>" . $rows["proposal_summary"] . "</td></tr></tbody>";
        echo "<tbody><tr><td><b>Description</b></td><td>" . $rows["program_description"] . "</td></tr></tbody>";
    }
}
?>
