<?php
	$aims_db_connection = pg_connect("host=localhost dbname=aims user=mikesimarta password=test");
	if (!$aims_db_connection) {
		echo "Error connecting to the AIMS PostgreSQL database!\n";
		exit;
	}

	$sims_db_connection = pg_connect("host=localhost dbname=sims user=mikesimarta password=test");
	if (!$sims_db_connection) {
		echo "Error connecting to the SIMS PostgreSQL database!\n";
		exit;
	}
?>
