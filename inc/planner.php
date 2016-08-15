<?php
    // My plan information ------------------------------------------------------------------------
    echo "<h2>My Plan</h2>";
    echo "<div class='plan timetable'></div>";
    /*$i = 0;
    $terms = array("");

    echo "<h2>My Plan</h2>";
    echo "<div><table class='plan table table-striped'>";
    foreach ($courses as $course) {
        $courseParams = explode(" - ", $course);
        $courseCode = $courseParams[0];
        $term = $courseParams[4];

        if (!in_array($term, $terms)) {
            echo "<thead><tr><th>" . $term . "</th></tr></thead>";
            $terms[$i] = $term;
            $i++;
        }

        echo "<tbody><tr>";
        echo "<td>" . $courseCode . "</td>";
        echo "</tr></tbody>";
    }
    echo "</table></div>";*/

    // Progression checks information -------------------------------------------------------------



    // Suggested courses information --------------------------------------------------------------
    $i = 0;
    $query = "SELECT * FROM advice($login_session)";
    $result = pg_query($sims_db_connection, $query);

    echo "<h2>Suggested Courses</h2>";
    echo "<div><table class='table table-striped'>";
    echo "<thead><tr><th>#</th><th>Course</th><th>Course Title</th></thead>";
    while ($rows = pg_fetch_array($result)) {
        echo "<tbody><tr>";
        echo "<td>" . ($i+1) . "</td>";
        echo "<td>" . $rows["code"] . "</td>";
        echo "<td>" . $rows["title"] . "</td>";
        echo "</tr></tbody>";
        $i++;
    }
    echo "</table></div>";
?>

<script>
    var courses = <?php echo json_encode($courses); ?>;
</script>

<script src="inc/planner.js"></script>
