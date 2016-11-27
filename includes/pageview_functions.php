<?php
class PageviewController {
	public $dbh;
	
	public function __construct($dbh) {
		$this->dbh = $dbh;
	}
	
	public function run_query($query) {
		$result = $this->dbh->query($query) or die("Error in query: $query, ".$this->dbh->errorInfo()[2]);
		return $result;
	}
	
	public function get_viewer($viewer_id) {
		$q = "SELECT * FROM pv_viewers WHERE viewer_id='".$viewer_id."';";
		$r = $this->run_query($q);
		if ($r->rowCount() == 1) {
			return $r->fetch();
		}
		else return NULL;
	}

	public function getBrowser() {
		$u_agent = $_SERVER['HTTP_USER_AGENT'];
		$bname = 'Unknown';
		$platform = 'Unknown';
		$version= "";

		//First get the platform?
		if (preg_match('/linux/i', $u_agent)) {
			$platform = 'linux';
		}
		elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
			$platform = 'mac';
		}
		elseif (preg_match('/windows|win32/i', $u_agent)) {
			$platform = 'windows';
		}
	   
		// Next get the name of the useragent yes seperately and for good reason
		if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) {
			$bname = 'Internet Explorer';
			$ub = "MSIE";
		}
		elseif(preg_match('/Firefox/i',$u_agent)) {
			$bname = 'Mozilla Firefox';
			$ub = "Firefox";
		}
		elseif(preg_match('/Chrome/i',$u_agent)) {
			$bname = 'Google Chrome';
			$ub = "Chrome";
		}
		elseif(preg_match('/Safari/i',$u_agent)) {
			$bname = 'Apple Safari';
			$ub = "Safari";
		}
		elseif(preg_match('/Opera/i',$u_agent)) {
			$bname = 'Opera';
			$ub = "Opera";
		}
		elseif(preg_match('/Netscape/i',$u_agent)) {
			$bname = 'Netscape';
			$ub = "Netscape";
		}
	   
		// finally get the correct version number
		$known = array('Version', $ub, 'other');
		$pattern = '#(?<browser>' . join('|', $known) .
		')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		if (!preg_match_all($pattern, $u_agent, $matches)) {
			// we have no matching number just continue
		}
	   
		// see how many we have
		$i = count($matches['browser']);
		if ($i != 1) {
			//we will have two since we are not using 'other' argument yet
			//see if version is before or after the name
			if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
				$version= $matches['version'][0];
			}
			else {
				$version= $matches['version'][1];
			}
		}
		else {
			$version= $matches['version'][0];
		}
	   
		// check if we have a number
		if ($version==null || $version=="") {$version="?";}
	   
		return array(
			'userAgent'	=> $u_agent,
			'name'		=> $bname,
			'version'	=> $version,
			'platform'	=> $platform,
			'pattern'	=> $pattern
		);
	}

	public function ip_identifier() {
		$q = "SELECT * FROM pv_identifiers WHERE type='ip' AND identifier='".$_SERVER['REMOTE_ADDR']."';";
		$r = $this->run_query($q);
		if ($r->rowCount() > 0) {
			$identifier = $r->fetch();
			return $identifier;
		}
		else return -1;
	}
	public function cookie_identifier() {
		if (isset($_COOKIE["uuu"])) {
			$q = "SELECT * FROM pv_identifiers WHERE type='cookie' AND identifier='".$_COOKIE["uuu"]."';";
			$r = $this->run_query($q);
			if ($r->rowCount() > 0) {
				$identifier = $r->fetch();
				return $identifier;
			}
			else return -1;
		}
		else return -1;
	}
	public function insert_pageview() {
		$ip_identifier = $this->ip_identifier();
		$cookie_identifier = $this->cookie_identifier();
		
		if ($ip_identifier != -1 && $cookie_identifier != -1) {}
		else if ($ip_identifier == -1 && $cookie_identifier == -1) {
			$q = "INSERT INTO pv_viewers SET time_created='".time()."';";
			$r = $this->run_query($q);
			$viewer_id = $this->dbh->lastInsertId();
			$q = "INSERT INTO pv_identifiers SET type='ip', identifier='".$_SERVER['REMOTE_ADDR']."', viewer_id='".$viewer_id."';";
			$r = $this->run_query($q);
			$rando = random_string(64);
			$q = "INSERT INTO pv_identifiers SET type='cookie', identifier='$rando', viewer_id='".$viewer_id."';";
			$r = $this->run_query($q);
			setcookie("uuu", $rando, time()+(10 * 365 * 24 * 60 * 60));
		}
		else if ($ip_identifier == -1) {
			$q = "INSERT INTO pv_identifiers SET type='ip', identifier='".$_SERVER['REMOTE_ADDR']."', viewer_id='".$cookie_identifier['viewer_id']."';";
			$r = $this->run_query($q);
			$ip_id = $this->dbh->lastInsertId();
			$q = "SELECT * FROM pv_identifiers WHERE identifier_id='".$ip_id."';";
			$r = $this->run_query($q);
			$ip_identifier = $r->fetch();
		}
		else if ($cookie_identifier == -1) {
			$rando = random_string(64);
			setcookie("uuu", $rando, time()+(10 * 365 * 24 * 60 * 60));
			$q = "INSERT INTO pv_identifiers SET viewer_id='".$ip_identifier['viewer_id']."', type='cookie', identifier='".$rando."';";
			$r = $this->run_query($q);
			$cookie_id = $this->dbh->lastInsertId();
			$q = "SELECT * FROM pv_identifiers WHERE identifier_id='".$cookie_id."';";
			$r = $this->run_query($q);
			$cookie_identifier = $r->fetch();
		}
		
		$page_url = $_SERVER['REQUEST_URI'];
		$q = "SELECT page_url_id FROM pv_page_urls WHERE url=".$this->dbh->quote($page_url).";";
		$r = $this->run_query($q);
		if ($r->rowCount() > 0) {
			$pv_page_id = $r->fetch();
			$pv_page_id = $pv_page_id[0];
		}
		else {
			$q = "INSERT INTO pv_page_urls SET url=".$this->dbh->quote($page_url).";";
			$r = $this->run_query($q);
			$pv_page_id = $this->dbh->lastInsertId();
		}
		$q = "INSERT INTO pv_pageviews SET viewer_id='".$cookie_identifier['viewer_id']."', ip_id='".$ip_identifier['identifier_id']."', cookie_id='".$cookie_identifier['identifier_id']."', time='".time()."', pv_page_id='".$pv_page_id."';";
		$r = $this->run_query($q);
		$pageview_id = $this->dbh->lastInsertId();
		
		$result[0] = $pageview_id;
		$result[1] = $cookie_identifier['viewer_id'];
		return $result;
	}
	public function viewer2user_byIP($user_id) {
		$q = "SELECT * FROM pv_pageviews P, pv_identifiers V WHERE P.ip_id > 0 AND P.ip_id=V.identifier_id AND V.type='ip' AND P.ip_processed=0";
		if ($user_id != "all_users") $q .= " AND P.user_id='".$user_id."'";
		$q .= ";";
		$r = $this->run_query($q);
		while ($pv = $r->fetch()) {
			$qq = "SELECT * FROM pv_viewer_connections WHERE type='viewer2user' AND from_id='".$pv['viewer_id']."' AND to_id='".$pv['user_id']."';";
			$rr = $this->run_query($qq);
			if ($rr->rowCount() > 0) {}
			else {
				$qq = "INSERT INTO pv_viewer_connections SET type='viewer2user', from_id='".$pv['viewer_id']."', to_id='".$pv['user_id']."';";
				$rr = $this->run_query($qq);
				echo "Connected viewer #".$pv['viewer_id']." to user account #".$pv['user_id']."<br/>\n";
			}
			$qq = "UPDATE pageviews SET ip_processed=1 WHERE pageview_id='".$pv['pageview_id']."';";
			$rr = $this->run_query($qq);
		}
	}
	public function viewer2user_byCookie($user_id) {
		$q = "SELECT * FROM pv_pageviews P, pv_viewer_identifiers V WHERE P.cookie_id > 0 AND P.cookie_id=V.identifier_id AND V.type='cookie' AND P.cookie_processed=0";
		if ($user_id != "all_users") $q .= " AND P.user_id='$user_id'";
		$q .= ";";
		$r = $this->run_query($q);
		while ($pv = $r->fetch()) {
			$qq = "SELECT * FROM pv_viewer_connections WHERE type='viewer2user' AND from_id='".$pv['viewer_id']."' AND to_id='".$pv['user_id']."';";
			$rr = $this->run_query($qq);
			if ($rr->rowCount() > 0) {}
			else {
				$qq = "INSERT INTO pv_viewer_connections SET type='viewer2user', from_id='".$pv['viewer_id']."', to_id='".$pv['user_id']."';";
				$rr = $this->run_query($qq);
				echo "Connected viewer #".$pv['viewer_id']." to user account #".$pv['user_id']."<br/>\n";
			}
			$qq = "UPDATE pv_pageviews SET cookie_processed=1 WHERE pageview_id='".$pv['pageview_id']."';";
			$rr = $this->run_query($qq);
		}
	}
	public function viewer2user_IPlookup() {
		$html  = "";
		$q = "SELECT viewer_id, user_id FROM pv_identifiers I, pv_pageviews P WHERE I.type='ip' AND I.identifier=P.ip_address GROUP BY viewer_id;";
		$r = $this->run_query($q);
		while ($pair = $r->fetch()) {
			$qq = "SELECT * FROM pv_viewer_connections WHERE type='viewer2user' AND from_id='".$pair[0]."' AND to_id='".$pair[1]."';";
			$rr = $this->run_query($qq);
			if ($rr->rowCount() == 0) {
				$qq = "INSERT INTO pv_viewer_connections SET type='viewer2user', from_id='".$pair[0]."', to_id='".$pair[1]."';";
				$rr = $this->run_query($qq);
				$html .= "Connected viewer #".$pair[0]." to user #".$pair[1]."<br/>\n";
			}
		}
		return $html;
	}
	public function IsTorExitPoint(){
		if (gethostbyname($this->ReverseIPOctets($_SERVER['REMOTE_ADDR']).".".$_SERVER['SERVER_PORT'].".".$this->ReverseIPOctets($_SERVER['SERVER_ADDR']).".ip-port.exitlist.torproject.org")=="127.0.0.2") {
			return true;
		}
		else {
			return false;
		}
	}
	public function ReverseIPOctets($inputip){
		$ipoc = explode(".",$inputip);
		return $ipoc[3].".".$ipoc[2].".".$ipoc[1].".".$ipoc[0];
	}
}
?>