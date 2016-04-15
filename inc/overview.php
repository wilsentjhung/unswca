<?php
  include("pgsql.php");

  // Student information --------------------------------------------------------------------------
  $query = "SELECT * FROM people WHERE id = $login_session";
  $result = pg_query($sims_db_connection, $query);
  $rows = pg_fetch_array($result);
  echo "<h1 class='page-header'>" . $rows["given_name"] . " " . $rows["family_name"] . " (z" . $rows["id"] . ")</h1>";

  // Basic information ----------------------------------------------------------------------------
  echo "<h2>Basic Information</h2>";

  // Program(s)
  $index = 0;
  $programs = array("");
  $query = "SELECT p.id, pr.id, pr.code AS pr_code, pr.title AS pr_title, MAX(t.id)
            FROM people p, program_enrolments pre, programs pr, terms t
            WHERE p.id = $login_session AND p.id = pre.student_id AND pre.program_id = pr.id AND pre.term_id = t.id
            GROUP BY p.id, pr.id ORDER BY pr.id";
  $result = pg_query($sims_db_connection, $query);

  echo "<div><table class='table table-striped'>";
  echo "<tbody><tr>";
  echo "<td><b>Program(s)</b></td>";
  while ($rows = pg_fetch_array($result)) {
    $programs[$index] = $rows["pr_code"];
    echo "<td>" . $rows["pr_code"] . " - " . $rows["pr_title"] . "</td>";
    $index++;
  }
  echo "</tbody></tr>";

  // Stream(s)
  $index = 0;
  $career = "";
  $streams = array("");
  $query = "SELECT p.id, st.id, st.code AS st_code, st.title AS st_title, st.career AS st_career, MAX(t.id)
            FROM people p, stream_enrolments ste, streams st, terms t
            WHERE p.id = $login_session AND p.id = ste.student_id AND ste.stream_id = st.id AND ste.term_id = t.id
            GROUP BY p.id, st.id ORDER BY st.id";
  $result = pg_query($sims_db_connection, $query);

  echo "<tbody><tr>";
  echo "<td><b>Stream(s)</b></td>";
  while ($rows = pg_fetch_array($result)) {
    $career = $rows["st_career"];
    $streams[$index] = $rows["st_code"];
    echo "<td>" . $rows["st_code"] . " - " . $rows["st_title"] . "</td>";
    $index++;
  }
  echo "</tbody></tr>";

  // Career
  echo "<tbody><tr>";
  echo "<td><b>Career</b></td><td>" . $career . "</td>";
  echo "</tbody></tr>";

  // Term
  $query = "SELECT p.id, t.code AS t_code, MIN(t.id)
            FROM people p, stream_enrolments ste, terms t
            WHERE p.id = $login_session AND p.id = ste.student_id AND ste.term_id = t.id
            GROUP BY p.id, t.id ORDER BY t.id";
  $result = pg_query($sims_db_connection, $query);
  $rows = pg_fetch_array($result);

  echo "<tbody><tr>";
  echo "<td><b>Start Term</b></td><td>" . $rows["t_code"] . "</td>";
  echo "</tbody></tr>";

  echo "</table></div>";

  // Prerequisites information --------------------------------------------------------------------
  $index1 = 0;
  $prerequisites = array("");

  foreach ($streams as $stream) {
    $index2 = 0;
    $query = "SELECT title, raw_defn, rul_t, min, max FROM active_rules WHERE code LIKE '%$stream%' ORDER BY title";
    $result = pg_query($aims_db_connection, $query);

    echo "<h2>Prerequisites for " . $stream . " Stream</h2>";
    echo "<div><table class='table table-striped'>";
    echo "<thead><tr><th>#</th><th>Title</th><th>Prerequisite</th></tr></thead>";
    while ($rows = pg_fetch_array($result)) {
      echo "<tbody><tr>";
      echo "<td>" . ($index1+1) . "</td>";
      echo "<td>" . $rows["title"] . "</td>";

      $prereqsParams = explode(",", $rows["raw_defn"]);
      sort($prereqsParams);
      echo "<td>";
      foreach ($prereqsParams as $prereq) {
        $prereq = str_ireplace("none", "", $prereq);
        $prereq = str_ireplace("nil", "", $prereq);
        $prereq = str_ireplace(";", " or ", $prereq);
        $prereq = str_ireplace("{", "", $prereq);
        $prereq = str_ireplace("}", "", $prereq);
        echo $prereq . "<br>";
        $prerequisites[$index2] = $prereq . " - " . $rows["title"] . " - " . $rows["rul_t"] . " " . $rows["min"] . " - " . $rows["max"];
        $index2++;
      }
      echo "</td>";

      echo "</tr></tbody>";
      $index1++;
    }
    echo "</table></div>";
  }
?>

<script src="inc/overview.js"></script>
