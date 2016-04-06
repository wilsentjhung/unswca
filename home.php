<?php
  include("inc/session.php")
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="img/favicon.ico">

    <!-- Include JQuery -->
    <script src="http://code.jquery.com/jquery-1.12.0.min.js"></script>
    <script src="http://code.jquery.com/jquery-migrate-1.2.1.min.js"></script>

    <title>UNSW Course Advisor</title>

    <!-- Bootstrap core CSS -->
    <link href="dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <link href="assets/css/ie10-viewport-bug-workaround.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="css/home_style.css" rel="stylesheet">

    <!-- Connect to the PostgreSQL database -->
    <?php include_once("inc/pgsql.php"); ?>
  </head>

  <body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <a class="navbar-brand" href="#">UNSW Course Advisor</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-right">
            <li><a href="#">Help</a></li>
            <li><a href="inc/logout.php">Log out</a></li>
          </ul>
        </div>
      </div>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <!-- Navigation sidebar -->
        <div class="col-sm-3 col-md-2 sidebar">
          <ul id="nav-sidebar" class="nav nav-sidebar">
            <li id="overview" class="active"><a href="#">Overview</a></li>
            <li id="report"><a href="#">Report</a></li>
            <li id="advisor"><a href="#">Advisor</a></li>
          </ul>
        </div>

        <!-- Overview content -->
        <div id="overview-content" class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
          <?php include("inc/overview.php"); ?>
          <script src="inc/overview.js"></script>
        </div>

        <!-- Report content -->
        <div id="report-content" class="advisor-content col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
          <?php include("inc/report.php"); ?>
          <script src="inc/report.js"></script>
        </div>

        <!-- Advisor content -->
        <div id="advisor-content" class="advisor-content col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
          <input id="searchinput" class="searchinput" type="text" placeholder="Search...">
          <div id="searchres" class="searchres"></div>
          <script src="inc/advisor.js"></script>
        </div>
      </div>
    </div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script>window.jQuery || document.write("<script src='assets/js/vendor/jquery.min.js'><\/script>")</script>
    <script src="dist/js/bootstrap.min.js"></script>
    <!-- Just to make our placeholder images work. Don't actually copy the next line! -->
    <script src="assets/js/vendor/holder.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="assets/js/ie10-viewport-bug-workaround.js"></script>
  </body>
</html>