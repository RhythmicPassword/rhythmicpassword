<?php
//ini_set('display_errors', 'Off');

function new_db_conn($server, $user, $password, $database) {
	$conn = new PDO("mysql:host=".$server.";charset=utf8", $user, $password) or die("Error, failed to connect to the database.");
	$conn->query("USE ".$database);
	return $conn;
}

function random_string($length) {
	$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$string ="";

	for ($p = 0; $p < $length; $p++) {
		$string .= $characters[mt_rand(0, strlen($characters))];
	}

	return $string;
}

class App {
	public $dbh;
	
	public function __construct($dbh) {
		$this->dbh = $dbh;
	}
	
	public function quote_escape($string) {
		return $this->dbh->quote($string);
	}
	
	function do_query($query) {
		$result = $this->dbh->query($query) or die("Error in query: $query, ".$this->dbh->errorInfo()[2]);
		return $result;
	}

	public function last_insert_id() {
		return $this->dbh->lastInsertId();
	}

	public function get_key_averages($group_id) {
		$keycodes = "";
		$qqq = "SELECT * FROM keylogins WHERE group_id='".$group_id."' ORDER BY login_id DESC;";
		$rrr = $this->do_query($qqq);
		$login_id = 0;
		while ($login = $rrr->fetch()) {
			$q = "SELECT * FROM keystrokes WHERE login_id='".$login['login_id']."' ORDER BY keystroke_id ASC;";
			$r = $this->do_query($q);
			$length = $r->rowCount();
			
			if ($login_id == 0) {
				for ($i=0;  $i<$length; $i++) {
					$keytimesum[$i] = 0;
				}
			}
			
			$count = 0;
			while ($key = $r->fetch()) {
				if ($count == $length-1) {}
				else {
					$keycodes[$count] = $key['keycode'];
					
					$qq = "SELECT * FROM keystrokes WHERE keystroke_id='".($key['keystroke_id']+1)."';";
					$rr = $this->do_query($qq);
					$next = $rr->fetch();
					
					$keytimesum[$count] += $next['keytime']-$key['keytime'];
					$count++;
				}
			}
			$login_id++;
		}
		
		$averages = "";
		$averagetotal = 0;
		for ($i=0; $i<$length; $i++) {
			$averages[$i] = $keytimesum[$i]/$login_id;
			$averagetotal += $averages[$i];
		}
		
		$result[0] = $averages;
		$result[1] = $averagetotal;
		$result[2] = $keycodes;
		return $result;
	}

	public function render_login($login_id, $colormode, $gwidth, $gheight, $show_checkboxes) {
		$temploginid = $login_id;
		$html = "";
		$q = "SELECT MIN(keytime), MAX(keytime) FROM keystrokes WHERE login_id='".$login_id."';";
		$r = $this->do_query($q);
		$minmax = $r->fetch();
		$typetime = $minmax[1] - $minmax[0];
		$timeperpix = $typetime/$gwidth;
		
		$html .= '<div class="loginholder" style="width: '.$gwidth.'px; height: '.$gheight.'px;" onclick=\'loginclicked('.$temploginid.');\'>';
		
		$q = "SELECT * FROM keystrokes WHERE login_id='".$login_id."' ORDER BY keystroke_id ASC;";
		$r = $this->do_query($q);
		$length = $r->rowCount();
		
		$count = 0;
		while ($key = $r->fetch()) {
			if ($count == $length-1) {}
			else {
				$qq = "SELECT * FROM keystrokes WHERE keystroke_id='".($key['keystroke_id']+1)."';";
				$rr = $this->do_query($qq);
				$next = $rr->fetch();
				
				$width = floor(($next['keytime']-$key['keytime'])/$timeperpix)+1;
				$left = floor(($key['keytime']-$minmax[0])/$timeperpix);
				$mid = $left+($width/2);
				
				if ($colormode == "completion") $color = 256-round(256*$mid/$gwidth);
				else if ($colormode == "index") $color = 256 - round(($count/$length)*256);
				//else if ($colormode == "speed") $color = round(256*($width/$gwidth));
				
				$textcolor = "#000";
				if ($color < 190) $textcolor = "#fff";
				
				$html .= "<div class=\"charbox\" style=\"background-color: rgb(".$color.",".$color.",".$color."); color: ".$textcolor."; left: ".$left."px; width: ".$width."px; height: ".$gheight."px; line-height: ".$gheight."px;\">";
				$html .= chr($key['keycode']);
				$html .= "</div>\n";
				$count++;
			}
			$login_id++;
		}
		if ($show_checkboxes) $html .= "<div class=\"logincheckholder\"><input type=\"checkbox\" class=\"logincheckbox\" id=\"logincheckbox".$temploginid."\"></div>";
		$html .= '</div><br/>'."\n\n";
		
		return $html;
	}

	public function delete_group($group_id) {
		$q = "DELETE l.*, s.* FROM keylogins l, keystrokes s WHERE s.login_id=l.login_id AND l.group_id='".$group_id."';";
		$r = $this->do_query($q);
		$q = "UPDATE keygroups SET time_deleted='".time()."', deleted=1, selected_login=0 WHERE group_id='".$group_id."';";
		$r = $this->do_query($q);
	}

	public function delete_login($login_id) {
		$q = "DELETE FROM keylogins WHERE login_id='".$login_id."';";
		$r = $this->do_query($q);
		$q = "DELETE FROM keystrokes WHERE login_id='".$login_id."';";
		$r = $this->do_query($q);
	}

	public function interkey_diff_total($login1_id, $login2_id) {
		$q = "SELECT * FROM keylogins WHERE login_id='".$login1_id."';";
		$r = $this->do_query($q);
		$login1 = $r->fetch();
		
		$q = "SELECT * FROM keylogins WHERE login_id='".$login2_id."';";
		$r = $this->do_query($q);
		$login2 = $r->fetch();
		
		$q = "SELECT * FROM keystrokes WHERE login_id='".$login1['login_id']."' ORDER BY keystroke_id ASC;";
		$r1 = $this->do_query($q);
		$login1_numrows = $r1->rowCount();
		
		$q = "SELECT * FROM keystrokes WHERE login_id='".$login2['login_id']."' ORDER BY keystroke_id ASC LIMIT ".$login1_numrows.";";
		$r2 = $this->do_query($q);
		
		$diff_total = 0;
		
		for ($i=0; $i<$login1_numrows-1; $i++) {
			$key1 = $r1->fetch();
			$key2 = $r2->fetch();
			
			$q = "SELECT * FROM keystrokes WHERE keystroke_id='".($key1['keystroke_id']+1)."';";
			$r = $this->do_query($q);
			$next_key1 = $r->fetch();
			
			$q = "SELECT * FROM keystrokes WHERE keystroke_id='".($key2['keystroke_id']+1)."';";
			$r = $this->do_query($q);
			$next_key2 = $r->fetch();
			
			$time1 = $next_key1['keytime'] - $key1['keytime'];
			$time2 = $next_key2['keytime'] - $key2['keytime'];
			
			$diff_total += abs($time2-$time1);
		}
		return round($diff_total);
	}

	public function rhythm_score($login1_id, $login2_id) {
		$login1_total = $this->login_totaltime($login1_id);
		$login2_total = $this->login_totaltime($login2_id);
		
		$q = "SELECT * FROM keylogins WHERE login_id='".$login1_id."';";
		$r = $this->do_query($q);
		$login1 = $r->fetch();
		
		$q = "SELECT * FROM keylogins WHERE login_id='".$login2_id."';";
		$r = $this->do_query($q);
		$login2 = $r->fetch();
		
		$q = "SELECT * FROM keystrokes WHERE login_id='".$login1['login_id']."' ORDER BY keystroke_id ASC;";
		$r1 = $this->do_query($q);
		
		$q = "SELECT * FROM keystrokes WHERE login_id='".$login2['login_id']."' ORDER BY keystroke_id ASC;";
		$r2 = $this->do_query($q);
		
		$cursor1 = 0;
		$cursor2 = 0;
		$score = 0;
		
		for ($i=0; $i<strlen($login1['plaintext']); $i++) {
			$key1 = $r1->fetch();
			$key2 = $r2->fetch();
			
			$q = "SELECT * FROM keystrokes WHERE keystroke_id='".($key1['keystroke_id']+1)."';";
			$r = $this->do_query($q);
			$next_key1 = $r->fetch();
			
			$q = "SELECT * FROM keystrokes WHERE keystroke_id='".($key2['keystroke_id']+1)."';";
			$r = $this->do_query($q);
			$next_key2 = $r->fetch();
			
			$time1 = $next_key1['keytime'] - $key1['keytime'];
			$time2 = $next_key2['keytime'] - $key2['keytime'];
			
			$pct1 = $cursor1/$login1_total;
			$pct2 = $cursor2/$login2_total;
			
			$distfromsides1 = min(1-$pct1, $pct1);
			$distfromsides2 = min(1-$pct2, $pct2);
			
			$thisscore = pow(abs($pct2-$pct1), 3)/(0.5+$distfromsides1);
			$score += $thisscore;
			
			$cursor1 += $time1;
			$cursor2 += $time2;
		}
		if ($score > 0) return round(0.1/$score);
		else return 0;
	}

	public function login_totaltime($login_id) {
		$q = "SELECT * FROM keylogins WHERE login_id='".$login_id."';";
		$r = $this->do_query($q);
		$login = $r->fetch();
		
		$q = "SELECT * FROM keystrokes WHERE login_id='".$login['login_id']."' ORDER BY keystroke_id ASC LIMIT ".strlen($login['plaintext']).";";
		$r = $this->do_query($q);
		
		$time = 0;
		
		for ($i=0; $i<$r->rowCount(); $i++) {
			$key = $r->fetch();
			
			$qq = "SELECT * FROM keystrokes WHERE keystroke_id='".($key['keystroke_id']+1)."';";
			$rr = $this->do_query($qq);
			$next_key = $rr->fetch();
			
			$time += $next_key['keytime'] - $key['keytime'];
		}
		
		return $time;
	}
}
?>