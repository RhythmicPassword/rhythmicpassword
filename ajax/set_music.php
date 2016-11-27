<?php
include("../includes/connect.php");
include("../includes/pageview_functions.php");
include("../includes/config.php");
$pageview = $pageviewController->insert_pageview();
$viewer_id = $pageview[1];
$pref = (int) $_REQUEST['pref'];

if ($pref >= 0) {
	$q = "UPDATE pv_viewers SET music_pref=".$pref." WHERE viewer_id='".$viewer_id."';";
	$r = $app->do_query($q);
}

echo "1";
?>