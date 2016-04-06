<?php
  include("pgsql.php");

  $index = 0;
  $subjects = array("");
  $query = "SELECT p.id AS id, p.given_name AS given_name, p.family_name AS family_name,
                   st.id AS stream_id, st.code AS stream_code,
                   ce.student_id, ce.course_id, ce.mark AS mark, ce.grade AS grade,
                   s.id AS subject_id, s.code AS subject_code, s.title AS subject_title,
                   t.id AS term_id, t.code AS term_code, t.name AS term_name, t.ending AS term_ending
            FROM   people p, stream_enrolments ste, streams st,
                   course_enrolments ce, courses c, subjects s, terms t
            WHERE  p.id = $login_session AND p.id = ste.student_id AND ste.stream_id = st.id AND
                   p.id = ce.student_id AND ce.course_id = c.id AND
                   c.subject_id = s.id AND c.term_id = t.id
            GROUP BY s.id, t.id, ce.course_id, ce.student_id, st.id, p.id
            ORDER BY term_ending, subject_code, subject_title";
  $result = pg_query($sims_db_connection, $query);

  echo "<h2>Courses</h2>";
  echo "<div col-md-6'><table class='table table-striped'>";
  echo "<thead><tr><th>#</th><th>Course</th><th>Course Title</th><th>Mark</th><th>Grade</th><th>Term</th></tr></thead>";
  while ($rows = pg_fetch_array($result)) {
    $subject = $rows["subject_code"] . " - " . $rows["term_code"];
    if (!in_array($subject, $subjects)) {
      $subjects[$index] = $subject;
      echo "<tbody><tr>";
      echo "<td>" . ($index+1) . "</td>";
      echo "<td>" . $rows["subject_code"] . "</td>";
      echo "<td>" . $rows["subject_title"] . "</td>";
      echo "<td>" . $rows["mark"] . "</td>";
      echo "<td>" . $rows["grade"] . "</td>";
      echo "<td>" . $rows["term_code"] . "</td>";
      echo "</tr></tbody>";
      $index++;
    }
  }
  echo "</table></div>";

  /*echo "<h2>Remaining Prerequisites</h2>";
  $done = false;
  foreach ($prerequisites as &$prerequisite) {
    foreach ($subjects as &$subject) {
      if (strpos($prerequisite, $subject) === false) {
        $done = false;
      } else {
        $done = true;
        break;
      }
    }

    if (!$done) {
      echo "<p>" . $prerequisite . "</p>";
    }
  }*/
?>
