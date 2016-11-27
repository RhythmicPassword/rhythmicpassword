CREATE TABLE IF NOT EXISTS `keygroups` (
  `group_id` int(20) NOT NULL AUTO_INCREMENT,
  `viewer_id` int(20) NOT NULL,
  `closed_group` tinyint(1) NOT NULL DEFAULT '0',
  `plaintext` varchar(100) COLLATE latin1_general_ci NOT NULL,
  `group_name` varchar(100) COLLATE latin1_general_ci NOT NULL,
  `selected_login` int(20) NOT NULL DEFAULT '0',
  `threshold` int(20) NOT NULL DEFAULT '100',
  `threshold2` int(11) NOT NULL DEFAULT '500',
  `time_created` int(20) NOT NULL,
  `time_deleted` int(20) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `music_pref` int(20) NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `keylogins` (
  `login_id` int(20) NOT NULL AUTO_INCREMENT,
  `group_id` int(20) NOT NULL,
  `viewer_id` int(20) NOT NULL DEFAULT '0',
  `time` int(20) NOT NULL DEFAULT '0',
  `plaintext` varchar(100) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`login_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `keystrokes` (
  `keystroke_id` int(20) NOT NULL AUTO_INCREMENT,
  `login_id` int(20) NOT NULL,
  `keycode` int(20) NOT NULL,
  `keytime` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`keystroke_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `pv_browserstrings` (
  `browserstring_id` int(30) NOT NULL AUTO_INCREMENT,
  `viewer_id` int(20) NOT NULL,
  `browser_string` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  `browser_id` int(20) NOT NULL,
  `name` varchar(100) COLLATE latin1_german2_ci NOT NULL,
  `version` varchar(100) COLLATE latin1_german2_ci NOT NULL,
  `platform` varchar(100) COLLATE latin1_german2_ci NOT NULL,
  `pattern` varchar(150) COLLATE latin1_german2_ci NOT NULL,
  PRIMARY KEY (`browserstring_id`),
  KEY `v1` (`viewer_id`),
  KEY `b1` (`browser_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `pv_identifiers` (
  `identifier_id` int(20) NOT NULL AUTO_INCREMENT,
  `viewer_id` int(20) NOT NULL,
  `type` enum('ip','cookie') COLLATE latin1_german2_ci NOT NULL DEFAULT 'ip',
  `identifier` varchar(100) COLLATE latin1_german2_ci NOT NULL,
  PRIMARY KEY (`identifier_id`),
  KEY `vv1` (`viewer_id`),
  KEY `vt1` (`viewer_id`,`type`),
  KEY `ii1` (`identifier`),
  KEY `it1` (`type`,`identifier`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `pv_pageviews` (
  `pageview_id` int(20) NOT NULL AUTO_INCREMENT,
  `time` int(20) NOT NULL,
  `browserstring_id` int(20) NOT NULL DEFAULT '0',
  `ip_id` int(20) NOT NULL DEFAULT '0',
  `cookie_id` int(20) NOT NULL,
  `viewer_id` int(20) NOT NULL,
  `pv_page_id` int(20) NOT NULL DEFAULT '0',
  `refer_url` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  `pv_type` enum('','property') COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `property_id` int(20) NOT NULL DEFAULT '0',
  `event_id` int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pageview_id`),
  KEY `pv1` (`time`),
  KEY `pv2` (`viewer_id`),
  KEY `pv3` (`pv_page_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `pv_page_urls` (
  `page_url_id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`page_url_id`),
  KEY `url1` (`url`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `pv_viewers` (
  `viewer_id` int(20) NOT NULL AUTO_INCREMENT,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `time_created` int(20) NOT NULL DEFAULT '0',
  `music_pref` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  PRIMARY KEY (`viewer_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `pv_browsers` (
  `browser_id` int(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  `display_name` varchar(255) COLLATE latin1_german2_ci NOT NULL,
  PRIMARY KEY (`browser_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci AUTO_INCREMENT=9 ;

INSERT INTO `pv_browsers` (`browser_id`, `name`, `display_name`) VALUES
(1, 'tor', 'Tor Browser'),
(2, 'mozilla_firefox', 'Firefox'),
(3, 'internet_explorer', 'Internet Explorer'),
(4, 'google_chrome', 'Chrome'),
(5, 'apple_safari', 'Safari'),
(6, 'opera', 'Opera'),
(7, 'netscape', 'Netscape'),
(8, 'other', 'Other');