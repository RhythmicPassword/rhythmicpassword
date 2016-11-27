<?php
include("../includes/connect.php");
include("../includes/pageview_functions.php");
include("../includes/config.php");
$pageview = $pageviewController->insert_pageview();
$viewer_id = $pageview[1];
$group_id = (int) $_REQUEST['group_id'];

if ($group_id > 0) {
	$threshold = $_REQUEST['threshold'];
	$threshold2 = $_REQUEST['threshold2'];
	$q = "UPDATE keygroups SET threshold=".$app->quote_escape($threshold).", threshold2=".$app->quote_escape($threshold2)." WHERE group_id=".$group_id.";";
	$r = $app->do_query($q);
}
?>