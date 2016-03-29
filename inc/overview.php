<?php
  include("pgsql.php");

  $index = 0;
  $subjects = array("");
  $query = "SELECT p.id AS id, p.given_name AS given_name, p.family_name AS family_name,
                   pr.id AS program_id, pr.code AS program_code, pr.title AS program_title,
                   st.id AS stream_id, st.code AS stream_code, st.title AS stream_title, st.career AS stream_career,
                   t.id AS term_id, t.code AS term_code, t.name AS term_name
            FROM   people p, program_enrolments pre, programs pr, stream_enrolments ste, streams st, terms t
            WHERE  p.id = $login_session AND p.id = pre.student_id AND pre.program_id = pr.id AND
                   p.id = ste.student_id AND ste.stream_id = st.id AND ste.term_id = t.id
            GROUP BY t.id, st.id, pr.id, p.id";
  $result = pg_query($sims_db_connection, $query);
  $rows = pg_fetch_array($result);

  echo "<h1 class='page-header'>" . $rows["given_name"] . " " . $rows["family_name"] . " (z" . $rows["id"] . ")</h1>";

  echo "<h2>Basic Information</h2><br>";
  echo "Program: " . $rows["program_code"] . " - " . $rows["program_title"] . "<br><br>";
  echo "Stream: " . $rows["stream_code"] . " - " . $rows["stream_title"] . "<br><br>";
  echo "Career: " . $rows["stream_career"] . "<br><br>";
  echo "Term Start: " . $rows["term_code"] . "<br><br>";

  $streamCode = $rows["stream_code"];

  $index = 0;
  $prerequisites = array("");
  $query = "SELECT title, raw_defn FROM active_rules WHERE code LIKE '%$streamCode%' ORDER BY title";
  $result = pg_query($aims_db_connection, $query);

  echo "<h2>Prerequisites for " . $rows["stream_title"] . " (" . $rows["stream_code"] . ") Stream</h2>";
  echo "<div col-md-6'><table class='table table-striped'>";
  echo "<thead><tr><th>#</th><th>Title</th><th>Prerequisite</th></tr></thead>";
  while ($rows = pg_fetch_array($result)) {
    echo "<tbody><tr>";
    echo "<td>" . ($index+1) . "</td>";
    echo "<td>" . $rows["title"] . "</td>";

    $prereqs = explode(",", $rows["raw_defn"]);
    sort($prereqs);
    echo "<td>";
    foreach ($prereqs as &$prereq) {
      $prereq = str_ireplace("none", "", $prereq);
      $prereq = str_ireplace("nil", "", $prereq);
      $prereq = str_replace(";", " or ", $prereq);
      $prereq = str_replace("{", "", $prereq);
      $prereq = str_replace("}", "", $prereq);
      echo $prereq . "<br>";
      array_push($prerequisites, $prereq);
    }
    echo "</td>";

    echo "</tr></tbody>";
    $index++;
  }
  echo "</table></div>";
?>
