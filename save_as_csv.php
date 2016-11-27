<?php
include("includes/connect.php");
include("includes/pageview_functions.php");
include("includes/config.php");
$pageview = $pageviewController->insert_pageview();
$viewer_id = $pageview[1];

$group_id = $_REQUEST['group_id'];
if ($group_id > 0) {
	header('Content-Type: application/csv');
	header('Content-Disposition: attachment; filename=example.csv');
	header('Pragma: no-cache');
	
	$qqq = "SELECT * FROM keylogins WHERE group_id='".$group_id."' ORDER BY login_id DESC;";
	$rrr = do_query($qqq);
	$logincount = mysql_numrows($rrr);
	
	if ($logincount > 0) {
		$login_id = 0;
		
		while ($login = mysql_fetch_array($rrr)) {
			if ($login_id == 0) {
				$headerrow = "";
				for ($i=0; $i<strlen($login['plaintext']); $i++) {
					$headerrow .= $login['plaintext'][$i].",";
				}
				$headerrow = substr($headerrow, 0, strlen($headerrow)-1);
				echo $headerrow."\n";
			}
			$q = "SELECT * FROM keystrokes WHERE login_id='".$login['login_id']."' ORDER BY keystroke_id ASC LIMIT ".strlen($login['plaintext']).";";
			$r = do_query($q);
			$length = mysql_numrows($r);
			
			$line = "";
			while ($key = mysql_fetch_array($r)) {
				$qq = "SELECT * FROM keystrokes WHERE keystroke_id='".($key['keystroke_id']+1)."';";
				$rr = do_query($qq);
				$next = mysql_fetch_array($rr);
				$line .= $next['keytime']-$key['keytime'].",";
			}
			$line = substr($line, 0, strlen($line)-1);
			echo $line."\n";
			$login_id++;
		}
	}
}
?>