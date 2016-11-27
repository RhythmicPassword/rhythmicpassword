<?php
$dbh = new_db_conn("host", "username", "password", "dbname");
$app = new App($dbh);
$pageviewController = new PageviewController($dbh);
?>