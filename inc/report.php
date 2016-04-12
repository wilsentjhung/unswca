<?php
  include("pgsql.php");

  // Courses information --------------------------------------------------------------------------
  $index = 0;
  $subjects = array("");
  $query = "SELECT tr.code AS tr_code, tr.title AS tr_title, tr.mark AS tr_mark, tr.grade AS tr_grade, tr.term AS tr_term,
                   s.uoc AS s_uoc, t.id
            FROM people p, transcript tr, subjects s, terms t
            WHERE p.id = $login_session AND p.id = tr.student_id AND tr.code LIKE s.code AND tr.term LIKE t.code
            ORDER BY t.id, tr.code";
  $result = pg_query($sims_db_connection, $query);

  echo "<h2>Courses</h2>";
  echo "<div><table class='table table-striped'>";
  echo "<thead><tr><th>#</th><th>Course</th><th>Course Title</th><th>Mark</th><th>Grade</th><th>UOC</th><th>Term</th></tr></thead>";
  while ($rows = pg_fetch_array($result)) {
    $subjects[$index] = $rows["tr_code"] . " - " . $rows["tr_mark"] . " - " . $rows["tr_grade"] . " - " . $rows["s_uoc"] . " - " . $rows["tr_term"];
    /*if ($rows["tr_mark"] == "" && $rows["tr_grade"] == "") {
      echo "<tbody><tr class='active'>";
    } else if ($rows["tr_mark"] < 50 && $rows["tr_grade"] != 'PC') {
      echo "<tbody><tr class='warning'>";
    } else {
      echo "<tbody><tr class='success'>";
    }*/
    echo "<tbody><tr>";
    echo "<td>" . ($index+1) . "</td>";
    echo "<td>" . $rows["tr_code"] . "</td>";
    echo "<td>" . $rows["tr_title"] . "</td>";
    echo "<td>" . $rows["tr_mark"] . "</td>";
    echo "<td>" . $rows["tr_grade"] . "</td>";
    echo "<td>" . $rows["s_uoc"] . "</td>";
    echo "<td>" . $rows["tr_term"] . "</td>";
    echo "</tr></tbody>";
    $index++;
  }
  echo "</table></div>";

  // Academic information -------------------------------------------------------------------------
  $wam = 0;
  $numerator = 0;
  $denominator = 0;

  echo "<h2>Academic Information</h2>";
  foreach ($subjects as $subject) {
    $subjectParams = explode(" - ", $subject);
    $numerator += $subjectParams[1] * $subjectParams[3];
    $denominator += $subjectParams[3];
  }

  // Core requirements information ----------------------------------------------------------------
  $index = 0;
  $done = false;

  echo "<h2>Core Requirements</h2>";
  echo "<div><table class='table table-striped'>";
  echo "<thead><tr><th>#</th><th>Course</th><th>Title</th><th>Min</th></tr></thead>";
  foreach ($prerequisites as $prerequisite) {
    $prereqParams = explode(" - ", $prerequisite);
    $prereqCode = $prereqParams[0];
    $prereqTitle = $prereqParams[1];
    $prereqMin = $prereqParams[3];

    foreach ($subjects as $subject) {
      $subjectParams = explode(" - ", $subject);
      $subjectCode = $subjectParams[0];

      if (stripos($prereqCode, $subjectCode) === false) {
        $done = false;
      } else {
        $done = true;
        break;
      }
    }

    if (!$done) {
      if (stripos($prereqTitle, "core") !== false) {
        echo "<tbody><tr>";
        echo "<td>" . ($index+1) . "</td>";
        echo "<td>" . $prereqCode . "</td>";
        echo "<td>" . $prereqTitle . "</td>";
        echo "<td>" . $prereqMin . "</td>";
        echo "</tr></tbody>";
        $index++;
      }
    }
  }
  echo "</table></div>";
?>

<script src="inc/report.js"></script>
