<?php
  session_start();
  include("pgsql.php");

  if (isset($_POST["submit"]) && isset($_POST["zid"]) && isset($_POST["password"])) {
    $zid = (int)$_POST["zid"];
    $password = $_POST["password"];

    $query = "SELECT * FROM people WHERE id = $zid";
    $result = pg_query($sims_db_connection, $query);
    $rows = pg_fetch_array($result);

    if ($rows != "") {
      $_SESSION["login_user"] = $zid;
      header("Location: home.php");
    }

    pg_close($sims_db_connection);
  }
?>
