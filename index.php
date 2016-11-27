<?php
include("includes/connect.php");
include("includes/pageview_functions.php");
include("includes/config.php");
$pageview = $pageviewController->insert_pageview();
$viewer_id = $pageview[1];
$viewer = $pageviewController->get_viewer($viewer_id);

$pagetitle = "RhythmicPassword.com - Home of the rhythmic password. Improve security with our rhythmic password plugin.";
include("includes/html_start.php");

if (!empty($_REQUEST['password'])) $plaintext = $_REQUEST['password'];
else $plaintext = "";

if (!empty($_REQUEST['action'])) {
	if ($_REQUEST['action'] == "delete_group" && $_REQUEST['group_id'] > 0) {
		$app->delete_group($_REQUEST['group_id']);
		$_REQUEST['group_id'] = "";
	}

	if ($_REQUEST['action'] == "delete_all") {
		$q = "SELECT * FROM keygroups WHERE viewer_id='".$viewer_id."';";
		$r = $app->do_query($q);
		while ($keygroup = $r->fetch()) {
			$app->delete_group($keygroup['group_id']);
		}
	}

	if ($_REQUEST['action'] == "delete_selected") {
		$selected = explode(",", $_REQUEST['selected']);
		foreach ($selected as $sel) {
			if ($sel > 0) {
				$app->delete_login($sel);
			}
		}
	}

	if ($_REQUEST['action'] == "close_group") {
		$group_id = (int) $_REQUEST['group_id'];
		$name = $app->quote_escape(urldecode($_REQUEST['name']));
		$q = "UPDATE keygroups SET closed_group=1, group_name=".$name." WHERE group_id='".$group_id."';";
		$r = $app->do_query($q);
		$_REQUEST['group_id'] = "";
	}
	
	if ($_REQUEST['action'] == "login") {
		$keycodes = $_REQUEST['keycodes'];
		$keytimes = $_REQUEST['keytimes'];
		$keycodes = explode(",", $keycodes);
		$keytimes = explode(",", $keytimes);
		
		$q = "SELECT * FROM keylogins l JOIN keygroups g ON l.group_id=g.group_id WHERE l.viewer_id='".$viewer_id."' AND l.plaintext=".$app->quote_escape($plaintext)." AND g.closed_group=0 GROUP BY g.group_id;";
		$r = $app->do_query($q);
		
		if ($r->rowCount() > 0) {
			$keygroup = $r->fetch();
		}
		else {
			$q = "INSERT INTO keygroups SET plaintext=".$app->quote_escape($plaintext).", viewer_id='".$viewer_id."', time_created='".time()."';";
			$r = $app->do_query($q);
			$keygroup_id = $app->last_insert_id();
			
			$q = "SELECT * FROM keygroups WHERE group_id='".$keygroup_id."';";
			$r = $app->do_query($q);
			$keygroup = $r->fetch();
		}
		
		$_REQUEST['group_id'] = $keygroup['group_id'];
		
		$q = "INSERT INTO keylogins SET viewer_id='".$viewer_id."', group_id='".$keygroup['group_id']."', plaintext=".$app->quote_escape($plaintext).", time='".time()."';";
		$r = $app->do_query($q);
		$loginid = $app->last_insert_id();
		$newest_loginid = $loginid;
		
		for ($i=0; $i<count($keycodes); $i++) {
			$q = "INSERT INTO keystrokes SET login_id='".$loginid."', keytime=".$app->quote_escape($keytimes[$i]).", keycode=".$app->quote_escape($keycodes[$i]).";";
			$r = $app->do_query($q);
		}
	}
}

if (!empty($_REQUEST['group_id'])) {
	$q = "SELECT * FROM keygroups g JOIN keylogins l ON g.group_id=l.group_id WHERE g.group_id='".$_REQUEST['group_id']."' GROUP BY g.group_id;";
	$r = $app->do_query($q);
	$keygroup = $r->fetch();
	$plaintext = $keygroup['plaintext'];
	
	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == "start") {
		$q = "UPDATE keygroups SET closed_group=1 WHERE viewer_id='".$viewer_id."' AND plaintext=".$app->quote_escape($keygroup['plaintext']).";";
		$r = $app->do_query($q);
		$q = "UPDATE keygroups SET closed_group=0 WHERE viewer_id='".$viewer_id."' AND group_id='".$keygroup['group_id']."';";
		$r = $app->do_query($q);
		$keygroup['closed_group'] = 0;
		$plaintext = $keygroup['plaintext'];
	}
	
	if (TRUE || $_REQUEST['action'] == "save_as_pass" || $_REQUEST['action2'] == "save_as_pass") {
		$q = "INSERT INTO keylogins SET viewer_id='".$viewer_id."', group_id=0, plaintext=".$app->quote_escape($plaintext).", time='".time()."';";
		$r = $app->do_query($q);
		$loginid = $app->last_insert_id();
		
		$avg = $app->get_key_averages($keygroup['group_id']);
		$averages = $avg[0];
		$averagetotal = $avg[1];
		$keycodes = $avg[2];
		
		$typetime = $averagetotal;
		
		$time_elapsed = 0;
		for ($i=0; $i<strlen($plaintext); $i++) {
			$q = "INSERT INTO keystrokes SET login_id='".$loginid."', keytime='".((time()*1000)+$time_elapsed)."', keycode=".$app->quote_escape($keycodes[$i]).";";
			$r = $app->do_query($q);
			
			$time_elapsed += $averages[$i];
		}
		$q = "INSERT INTO keystrokes SET login_id='".$loginid."', keytime='".((time()*1000)+$time_elapsed)."', keycode=".$app->quote_escape($keycodes[$i]).";";
		$r = $app->do_query($q);
		
		$q = "UPDATE keygroups SET selected_login='".$loginid."' WHERE group_id='".$keygroup['group_id']."';";
		$r = $app->do_query($q);
		
		$keygroup['selected_login'] = $loginid;
	}
}

?>
<h1>Rhythmic Password</h1>

<div style="float: right">
	<select id="music_pref" onchange="music_pref_changed();">
		<option value="0">No Music</option>
		<option value="1">Jeopardy</option>
		<option value="2">Wheel of Fortune</option>
		<option value="3">Pop goes the Weasel</option>
		<option value="4">Row, Row, Row Your Boat</option>
		<option value="5">Ring around the rosie</option>
	</select>
</div>
<form method="post" action="index.php<?php if (!empty($keygroup)) echo "?group_id=".$keygroup['group_id']; ?>" id="loginform" onsubmit="do_login();">
	<input type="hidden" name="action" value="login" />
	<?php
	$placeholder = "Please type something here then hit enter";
	if (!empty($plaintext)) $placeholder = "Please type '".$plaintext."' again consistently";
	?>
	<input type="text" size="60" style="line-height: 15px; padding: 5px; font-size: 18px;" value="" name="password" id="password" placeholder="<?php echo $placeholder; ?>" autocomplete="off" />
	<input type="hidden" name="keycodes" value="" id="keycodes" />
	<input type="hidden" name="keytimes" value="" id="keytimes" />
	<input type="submit" value="Check my Speed" onclick="pretend_enterkey();" style="line-height: 15px; padding: 3px 10px; font-size: 18px;"/>
</form>
<?php
if (!empty($keygroup)) {
	if ($keygroup['selected_login'] > 0 && !empty($newest_loginid)) {
		$diff_ms = $app->interkey_diff_total($keygroup['selected_login'], $newest_loginid);
		$rscore = $app->rhythm_score($keygroup['selected_login'], $newest_loginid);
		
		echo "<div class=\"display_feedback\"><b class=\"feedback_label\" ";
		if ($diff_ms > $keygroup['threshold'] && $rscore < $keygroup['threshold2']) echo "style=\"color: #f00;\">Incorrect</b> ";
		else if ($diff_ms > $keygroup['threshold']) echo "style=\"color: #f80;\">Timing Incorrect</b> ";
		else if ($rscore < $keygroup['threshold2']) echo "style=\"color: #f80;\">Rhythm Score Incorrect</b> ";
		else echo "style=\"color: #0b0;\">Correct</b> ";
		
		echo "<br/><b class=\"inline_100\">Timing Error:</b>$diff_ms milliseconds.<br/>\n";
		echo "<b class=\"inline_100\">Rhythm score:</b>".number_format($rscore)."<br/>\n";
		echo "Overall you were ";
		$yourtime = $app->login_totaltime($newest_loginid);
		$passtime = $app->login_totaltime($keygroup['selected_login']);
		$diff = round($yourtime - $passtime);
		if ($diff > 0) echo $diff." milliseconds too slow.";
		else echo (-1)*$diff." milliseconds too fast.";
		
		echo "</div><br/>";
	}
	
	$gwidth = 960;
	$gheight = 40;
	
	$qqq = "SELECT * FROM keylogins WHERE group_id='".$keygroup['group_id']."' ORDER BY login_id DESC;";
	$rrr = $app->do_query($qqq);
	$logincount = $rrr->rowCount();
	
	$potentialheight = $gheight*$logincount;
	if ($potentialheight > 500) {
		$gheight = round(max(500/$logincount, 20));
	}
	if ($logincount > 0) {
		$result = $app->get_key_averages($keygroup['group_id']);
		
		$averages = $result[0];
		$averagetotal = $result[1];
		
		echo "<br style=\"line-height: 8px;\"/>";
		echo "<a style=\"float: right; margin-right: 15px;\" href=\"/?group_id=".$keygroup['group_id']."&action=save_as_pass\">Save average as my password</a>";
		echo "<b>Average Pattern:</b><br/>\n";
		
		$typetime = $averagetotal;
		$timeperpix = $typetime/$gwidth;
		
		echo '<div style="position: relative; display: inline-block; width: '.$gwidth.'px; height: '.$gheight.'px; border: 1px solid black;">';
		$px_from_left = 0;
		for ($i=0; $i<strlen($plaintext); $i++) {
			$width = floor($averages[$i]/$timeperpix);
			$left = $px_from_left;
			$px_from_left += $width;
			$mid = $left+($width/2);
			
			$color = 256 - round($i/count($averages)*256);
			
			$textcolor = "#000";
			if ($color < 190) $textcolor = "#fff";
			
			echo "<div class=\"charbox\" style=\"background-color: rgb(".$color.",".$color.",".$color."); color: ".$textcolor."; left: ".$left."px; width: ".$width."px; height: ".$gheight."px; line-height: ".$gheight."px;\">";
			echo "</div>";
		}
		echo "</div>";
	}
	
	if ($keygroup['selected_login'] > 0) {
		?>
		<br/><br style="line-height: 8px;"/>
		<div style="float: right;">
			<div id="threshold" class="noUiSlider"></div>
			<div id="threshold2" class="noUiSlider"></div>
		</div>
		<div id="threshold_disp" style="float: right; display: inline-block; height: 25px; width: 120px; text-align: center;">
			<?php echo $keygroup['threshold']; ?> milliseconds<br/>
			<?php echo number_format($keygroup['threshold2']); ?>
		</div>
		<div style="float: right; font-weight: bold; margin-right: 10px;">
			Total Time Threshold:<br/>
			Rhythm Score Threshold:
		</div>
		
		<b>Your Password</b><br/>
		<?php
		echo $app->render_login($keygroup['selected_login'], "index", $gwidth, $gheight, FALSE);
	}
	?>
	<br/><br style="line-height: 8px;"/>
	<div style="float: right; display: inline-block;">
	<a href="" onclick="delete_outlier(); return false;">Delete outlier</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<a href="" onclick="delete_group(); return false;">Delete this group</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<a href="" onclick="close_group(<?php echo $keygroup['group_id']; ?>, '<?php echo $keygroup['group_name']; ?>'); return false;">Save & close this group</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<a href="" onclick="delete_selected(); return false;">Delete Selected</a>
	</div>
	<b>
	<?php
	if ($keygroup['closed_group'] == 1) echo "Closed Group";
	else echo "Your Pattern History";
	?>
	</b>
	<br/>
	<div id="pattern_history">
		<?php
		$worst = 0;
		
		$qqq = "SELECT * FROM keylogins WHERE group_id='".$keygroup['group_id']."' ORDER BY login_id DESC;";
		$rrr = $app->do_query($qqq);
		$logincount = $rrr->rowCount();
		
		if ($logincount > 0) {
			$login_id = 0;
			$outlier = 0;
			while ($login = $rrr->fetch()) {
				echo $app->render_login($login['login_id'], "index", $gwidth, $gheight, TRUE);
				
				if ($keygroup['selected_login'] > 0) {
					$diff_ms = $app->interkey_diff_total($keygroup['selected_login'], $login['login_id']);
					if ($diff_ms > $worst) {
						$worst = $diff_ms;
						$outlier = $login['login_id'];
					}
				}
			}
		}
		?>
	</div>
	<?php
}

?>
<script type="text/javascript">
function delete_outlier() {
	window.location = '/?action=delete_selected&group_id=<?php if (!empty($keygroup)) echo $keygroup['group_id']; ?>&selected=<?php if (!empty($outlier)) echo $outlier; ?>&action2=save_as_pass';
}
</script>
<br/>
<div class="data_controls">
	<center>
	<?php
	$q = "SELECT * FROM keygroups g JOIN keylogins l ON g.group_id=l.group_id WHERE g.deleted=0 AND g.viewer_id='".$viewer_id."' GROUP BY g.group_id ORDER BY g.group_id ASC;";
	$r = $app->do_query($q);
	while ($group = $r->fetch()) {
		$groupname = $group['plaintext'];
		if ($groupname == "") $groupname = "NULL";
		if ($group['group_name'] != "") $groupname = $group['group_name'];
		echo "<a style=\"display: inline-block; padding: 0px 10px;\" href=\"/?group_id=".$group['group_id']."&action=start\">".$groupname."</a>\n";
	}
	echo "</center>\n";
	
	if (!empty($keygroup)) { ?>
		<hr style="height: 1px; border: 0px; background-color: #888;">
		<center>
		<a href="" style="padding: 0px 10px;" onclick="delete_selected(); return false;">Delete Selected</a>
		<a style="padding: 0px 10px;" href="" onclick="delete_group(); return false;">Delete this group</a>
		<a style="padding: 0px 10px;" href="" onclick="delete_all(); return false;">Delete all my keystrokes</a>
		<a style="padding: 0px 10px;" href="/save_as_csv.php?group_id=<?php echo $keygroup['group_id']; ?>">Download as CSV</a>
		</center>
		<?php
	}
	?>
</div>

<script type="text/javascript">
function delete_group() {
	var url = "/?action=delete_group&group_id=<?php if (!empty($keygroup)) echo $keygroup['group_id']; ?>";
	var ans = confirm("Delete this group?");
	if (ans) {
		window.location = url;
	}
	return false;
}
function delete_all() {
	var url = "/?action=delete_all";
	var ans = confirm("Delete everything?");
	if (ans) {
		window.location = url;
	}
	return false;
}
function music_pref_changed() {
	$.get("/ajax/set_music.php?pref="+encodeURIComponent($('#music_pref').val()), function(result) {
		window.location = "/?group_id=<?php if (!empty($keygroup)) echo $keygroup['group_id']; ?>";
	});
}
$(document).ready(function() {
	<?php if ($viewer['music_pref'] > 0) { ?>
	document.getElementById('music_<?php echo $viewer['music_pref']; ?>').play();
	<?php } ?>
});

function doit() {
	setTimeout("doit();", 1000);
}

var logincount = <?php if (empty($logincount)) $logincount = "0"; echo $logincount; ?>;

function currentkeystroke(id, keycode, keytime) {
	this.id = id;
	this.keycode = keycode;
	this.keytime = keytime;
}
var passtemp = new Array();
var passlength = 0;

$("body").on("keypress", function (e) {
	var key = (e.which) ? e.which : e.keyCode;
	if ($('#password').val() == "") {
		passtemp.length = 0;
		passlength = 0;
	}
	if ($('#password').is(":focus")) {
		passtemp[passlength] = new currentkeystroke(passlength, key, new Date().getTime());
		passlength++;
	}
});
function do_login() {
	var codes = "";
	var times = "";
	for (var i=0; i<passlength; i++) {
		codes += passtemp[i].keycode+",";
		times += passtemp[i].keytime+",";
	}
	if (codes != "") {
		codes = codes.substr(0, codes.length-1);
		times = times.substr(0, times.length-1);
	}
	$('#keycodes').val(codes);
	$('#keytimes').val(times);
}

function pretend_enterkey() {
	passtemp[passlength] = new currentkeystroke(passlength, 13, new Date().getTime());
	passlength++;
}
$(document).ready(function() {
	$('#password').focus();
});
function loginclicked(id) {
	if (!$('#logincheckbox'+id).prop('checked')) {
		$('#logincheckbox'+id).prop('checked', true);
	} 
	else {
		$('#logincheckbox'+id).prop('checked', false);
	}
}
function delete_selected() {
	var csv = "";
	var count = 0;
	$('#pattern_history input:checkbox:checked').each(function(){
		csv += $(this).attr("id").replace("logincheckbox", "")+",";
		count++;
	});
	if (count == 0) {
		alert("Select a pattern to delete");
	}
	else {
		var ans;
		if (count == 1) ans = confirm("Are you sure you want to delete this pattern?");
		else ans = confirm("Are you sure you want to delete these "+count+" patterns?");
		if (ans) {
			csv = csv.substr(0, csv.length-1);
			window.location = '/?action=delete_selected&group_id=<?php if (!empty($keygroup)) echo $keygroup['group_id']; ?>&selected='+csv;
		}
	}
}
$(document).ready(function() {
	$('#music_pref').val('<?php echo $viewer['music_pref']; ?>');
});
</script>

<br/>
<?php
$numsounds = 5;
for ($i=1; $i<=$numsounds; $i++) {
	?>
	<audio id="music_<?php echo $i; ?>" loop="true">
		<source src="sound/<?php echo $i; ?>.mp3">
	</audio>
	<?php
}
?>
<div class="hint">
	<b>Hint:</b> Create a secure rhythm-based password by choosing an easy to spell word (to avoid typos). Then think of a tune or rhythm to go along with your keystrokes.<br/>
</div>

Rhythmic Password is open source. <a target="_blank" href="https://github.com/RhythmicPassword/rhythmicpassword">View it on GitHub</a>

<?php
if (!empty($keygroup)) {
	?>
	<script type="text/javascript">
	$(document).ready(function() {
		loop_event();
	});
	var threshold = <?php if (empty($keygroup['threshold'])) $keygroup['threshold'] = "0"; echo $keygroup['threshold']; ?>;
	var threshold2 = <?php if (empty($keygroup['threshold2'])) $keygroup['threshold2'] = "0"; echo $keygroup['threshold2']; ?>;

	function addCommas(nStr) {
		nStr += '';
		x = nStr.split('.');
		x1 = x[0];
		x2 = x.length > 1 ? '.' + x[1] : '';
		var rgx = /(\d+)(\d{3})/;
		while (rgx.test(x1)) {
			x1 = x1.replace(rgx, '$1' + ',' + '$2');
		}
		var result = x1 + x2;
		return result;
	}

	function loop_event() {
		if ($('#threshold').val() != threshold) refresh_val();
		if (typeof $('#threshold2').val() != "undefined" && $('#threshold2').val() != threshold2) {
			refresh_val("/index.php?group_id=<?php if (!empty($keygroup['group_id'])) echo $keygroup['group_id']; ?>");
		}
		setTimeout("loop_event()", 500);
	}
	$('#threshold').noUiSlider({
		range: [0, <?php echo round(max($worst*1.5, $keygroup['threshold']*1.5)); ?>]
	   ,start: <?php echo $keygroup['threshold']; ?>
	   ,step: 5
	   ,handles: 1
	   ,connect: "lower"
	   ,serialization: {
			 to: [ false, false ]
			,resolution: 1
		}
	   ,slide: function(){
			$('#threshold_disp').html($('#threshold').val()+" milliseconds<br/>"+addCommas($('#threshold2').val()));
	   }
	});
	$('#threshold2').noUiSlider({
		range: [0, <?php echo round(max(1000, $keygroup['threshold2']*2)); ?>]
	   ,start: <?php echo $keygroup['threshold2']; ?>
	   ,step: 5
	   ,handles: 1
	   ,connect: "lower"
	   ,serialization: {
			 to: [ false, false ]
			,resolution: 1
		}
	   ,slide: function(){
			$('#threshold_disp').html($('#threshold').val()+" milliseconds<br/>"+addCommas($('#threshold2').val()));
	   }
	});
	function refresh_val(goto_url) {
		$.get("/ajax/set_threshold.php?group_id=<?php if (!empty($keygroup['group_id'])) echo $keygroup['group_id']; ?>&threshold="+$('#threshold').val()+"&threshold2="+$('#threshold2').val(), function(result) {
			threshold = $('#threshold').val();
			threshold2 = $('#threshold2').val();
			if (typeof goto_url != "undefined" && goto_url != "") {
				window.location = goto_url;
			}
		});
	}
	function close_group(group_id, name) {
		if (name == "") name = prompt("Please name this group:");
		var url = "/?action=close_group&group_id="+group_id+"&name="+encodeURIComponent(name);
		window.location = url;
	}
	</script>
	<?php
}
include("includes/html_end.php");
?>