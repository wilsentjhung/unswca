<?php
    $index = 0;
    $query = "SELECT * FROM advice($login_session)";
    $result = pg_query($sims_db_connection, $query);

    echo "<h2>Suggested Courses</h2>";
    echo "<div><table class='table table-striped'>";
    echo "<thead><tr><th>#</th><th>Course</th><th>Course Title</th></thead>";
    while ($rows = pg_fetch_array($result)) {
        echo "<tbody><tr>";
        echo "<td>" . ($index+1) . "</td>";
        echo "<td>" . $rows["code"] . "</td>";
        echo "<td>" . $rows["title"] . "</td>";
        echo "</tr></tbody>";
        $index++;
    }
    echo "</table></div>";
?>

<script src="inc/planner.js"></script>
