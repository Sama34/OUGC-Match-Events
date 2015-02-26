<?php

/***************************************************************************
 *
 *	OUGC Match Events plugin (/inc/plugins/ougc_matchevents.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012 - 2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Adds a powerful match events system to your forum.
 *
 ***************************************************************************

****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// Tell MyBB when to run the hook
define('ACPEVENTS_URL', 'index.php?module=config-ougc_matchevents');
if(defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_config_menu', 'ougc_matchevents_menu');
	$plugins->add_hook('admin_config_action_handler', 'ougc_matchevents_action_handler');
	$plugins->add_hook('admin_config_permissions', 'ougc_matchevents_admin_permissions');
	$plugins->add_hook('admin_tools_get_admin_log_action', 'ougc_matchevents_log_action');
}
elseif(!defined('IN_ADMINCP') && defined('THIS_SCRIPT'))
{
	$plugins->add_hook('global_start', 'ougc_matchevents_global_start');
	$plugins->add_hook('global_end', 'ougc_matchevents_global_end');
	/*global $plugins;

	$templcache = false;
	switch(THIS_SCRIPT)
	{
		case 'showthread.php':
			$templcache = true;
			$plugins->add_hook('showthread_end', 'ougc_matchevents');
		case 'editpost.php':
			$templcache = true;
			$plugins->add_hook('editpost_end', 'ougc_matchevents');
			break;
		case 'newreply.php':
			$templcache = true;
			$plugins->add_hook('newreply_end', 'ougc_matchevents');
			break;
		case 'newthread.php':
			$templcache = true;
			$plugins->add_hook('newthread_end', 'ougc_matchevents');
			break;
		case 'forumdisplay.php':
			$templcache = true;
			$plugins->add_hook('forumdisplay_end', 'ougc_matchevents');
			break;
	}

	if($templcache)
	{
		global $templatelist;

		if(isset($templatelist))
		{
			$templatelist .= ',';
		}
		$templatelist .= 'forumdisplay_rules,forumdisplay_rules_link';
	}*/
}

// Plugin API
function ougc_matchevents_info()
{
	global $lang;
	ougc_matchevents_lang_load();

	return array(
		'name'			=> 'OUGC Match Events',
		'description'	=> $lang->ougc_matchevents_d,
		'website'		=> 'http://udezain.com.ar/',
		'author'		=> 'Omar Gonzalez',
		'authorsite'	=> 'http://udezain.com.ar/',
		'version'		=> '1.0',
		'guid' 			=> '5419c02974929364bd98f6389fe0fb94',
		'compatibility' => '16*'
	);
}

// We do no much when activating
function ougc_matchevents_activate()
{
	ougc_matchevents_deactivate(false);
	change_admin_permission('config', 'ougc_matchevents', 1);
	ougc_matchevents_update_cache();

	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('header', "#".preg_quote('{$pm_notice}')."#i", '{$pm_notice}<!--OUGC_SHOWBAR-->');

/*
ougc_matchevents
===
<html>
<head>
<title>{$mybb->settings['bbname']}</title>
{$headerinclude}
</head>
<body>
{$header}
{$content}
{$footer}
</body>
</html>

ougc_matchevents_bar
===
<p class="pm_alert">
	{$lang->ougc_matchevents_showbar}
</p>
<br />

ougc_matchevents_events
===
<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
<thead>
<tr>
<td class="thead" colspan="5"><strong>Events</strong></td>
</tr>
</thead>
<tbody>
<tr>
<td class="tcat smalltext" width="1" align="center"><strong>Status</strong></td>
<td class="tcat smalltext" width="20%"><strong>Name</strong></td>
<td class="tcat smalltext"><strong>Description</strong></td>
<td class="tcat smalltext" width="25%" align="center"><strong>Date</strong></td>
<td class="tcat smalltext" width="10%" align="center"><strong>Matches</strong></td>
</tr>
{$events}
</tbody>
</table>
<br />

ougc_matchevents_events_empty
===
<tr>
<td class="{$trow}" colspan="5" class="smalltext" align="center">There are currently no events to show.</td>
</tr>

ougc_matchevents_events_row
===
<tr>
<td class="{$trow}" align="center"><img src="{$theme['imgdir']}/{$status}folder.gif" alt="" title="" /></td>
<td class="{$trow}"><a href="{$settings['bburl']}/matchevents.php?eid={$event['eid']}">{$event['name']}</a></td>
<td class="{$trow}">{$event['description']}</td>
<td class="{$trow}" align="center">{$dateline}</td>
<td class="{$trow}" align="center">{$matches}</td>
</tr>
*/
}

// We do no much when activating
function ougc_matchevents_deactivate($hard=true)
{
	global $db, $cache;

	change_admin_permission('config', 'ougc_matchevents', 0);

	if(is_object($cache->hanlder))
	{
		$cache->hanlder->delete('ougc_matchevents');
	}
	$db->delete_query('datacache', "title='ougc_matchevents'");

	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('header', "#".preg_quote('<!--OUGC_SHOWBAR-->')."#i", '', 0);
}

// Install the plugin
function ougc_matchevents_install()
{
	global $db;
	ougc_matchevents_unisntall(false);

	$collation = $db->build_create_table_collation();
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_matchevents_events` (
			`eid` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NOT NULL DEFAULT '',
			`description` varchar(255) NOT NULL DEFAULT '',
			`enable` smallint(1) NOT NULL DEFAULT '1',
			`dateline` int(10) NOT NULL DEFAULT '0',
			PRIMARY KEY (`eid`)
		) ENGINE=MyISAM{$collation};"
	);
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_matchevents_matches` (
			`fid` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT,
			`eid` bigint(30) NOT NULL DEFAULT '0',
			`name` varchar(100) NOT NULL DEFAULT '',
			`description` varchar(255) NOT NULL DEFAULT '',
			`disporder` smallint(5) NOT NULL DEFAULT '0',
			PRIMARY KEY (`fid`,`eid`)
		) ENGINE=MyISAM{$collation};"
	);

	$db->insert_query('templates', array(
		'title'		=>	'ougc_matchevents_bar',
		'template'	=>	$db->escape_string('<p class="pm_alert">
	{$lang->ougc_matchevents_showbar}
</p>
<br />'),
		'sid'		=>	-1,
	));
}

// Check if installed
function ougc_matchevents_is_installed()
{
	global $db;

	return (bool)$db->table_exists('ougc_matchevents_events');
}

// Uninstall the plugin
function ougc_matchevents_unisntall($hard=true)
{
	global $db;

	$db->drop_table('ougc_matchevents_events');
	$db->drop_table('ougc_matchevents_matches');

	!$db->field_exists('ougc_matchevents_won', 'users') or $db->drop_column('users', 'ougc_matchevents_won');
	!$db->field_exists('ougc_matchevents_lose', 'users') or $db->drop_column('users', 'ougc_matchevents_lose');
	!$db->field_exists('ougc_matchevents_draw', 'users') or $db->drop_column('users', 'ougc_matchevents_draw');

	$db->delete_query('templates', "title IN ('ougc_matchevents_bar')");
}

// Insert our menu at users section.
function ougc_matchevents_menu(&$sub_menu)
{
	global $lang;
	ougc_matchevents_lang_load();

	$sub_menu[] = array('id' => 'ougc_matchevents', 'title' => $lang->ougc_matchevents_acp_nav, 'link' => ACPEVENTS_URL);
}

// Insert our action handler in users section awards page.
function ougc_matchevents_action_handler(&$action)
{
	$action['ougc_matchevents'] = array('active' => 'ougc_matchevents', 'file' => 'ougc_matchevents.php');
}

// Insert our plugin into the admin permissions page.
function ougc_matchevents_admin_permissions(&$admin_permissions)
{
  	global $lang;

	$admin_permissions['ougc_matchevents'] = $lang->ougc_matchevents_acp_permissions;
}

// Try to do something about those 'ugly' logs
function ougc_matchevents_log_action(&$args)
{
	global $lang;
	ougc_matchevents_global_end();
	$lang->admin_log_config_ougc_matchevents_delete = "Match Events: {$args['logitem']['profilelink']} deleted event.";
}

// Cache our templates
function ougc_matchevents_global_start()
{
	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}

	$templatelist .= 'ougc_matchevents_bar';
	// TEMP
	global $settings;

	$settings['ougc_matchevents_showbar'] = 1;
}

// Show the bar
function ougc_matchevents_global_end()
{
	global $settings;

	if($settings['ougc_matchevents_showbar'])
	{
		global $templates, $header, $lang, $cache;
		ougc_matchevents_lang_load();
		$event = $cache->read('ougc_matchevents');

		if($event['eid'])
		{
			$lang->ougc_matchevents_showbar = $lang->sprintf($lang->ougc_matchevents_showbar, $settings['bburl'], $event['eid'], htmlspecialchars_uni($event['name']));
			eval('$showbar = "'.$templates->get('ougc_matchevents_bar').'";');
			$header = str_replace('<!--OUGC_SHOWBAR-->', $showbar, $header);
		}
	}
}

// Load our lang file if necessary
function ougc_matchevents_lang_load()
{
	global $lang;

	isset($lang->ougc_matchevents) or $lang->load((defined('IN_ADMINCP') ? 'config_' : '').'ougc_matchevents');
}

// Fiendly redirect for admins
function ougc_matchevents_admin_redirect($message='', $url_append='', $error=false)
{
	$type = ($error ? 'error' : 'success');

	if(trim($message))
	{
		flash_message($message, $type);
	}

	if($url_append)
	{
		$url_append = '&amp;action='.$url_append;
	}

	admin_redirect(ACPEVENTS_URL.$url_append);
	exit;
}

// Validate an event
function ougc_matchevents_validate_event($data=array())
{
	global $lang, $db;
	ougc_matchevents_lang_load();

	$valid_event = array('valid' => true, 'errors' => array(), 'cleandata' => array());

	if(!isset($data['name']))
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invalidname;
	}
	elseif(!trim($data['name']) || my_strlen($data['name']) > 100)
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invalidname;
	}
	else
	{
		$valid_event['cleandata']['name'] = $db->escape_string($data['name']);
	}

	if(!isset($data['description']))
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invaliddesc;
	}
	elseif(trim($data['description']) && my_strlen($data['description']) > 255)
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invaliddesc;
	}
	else
	{
		$valid_event['cleandata']['description'] = $db->escape_string($data['description']);
	}

	if(!isset($data['enable']))
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invalidenable;
	}
	elseif(!in_array((int)$data['enable'], array(0, 1)))
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invalidenable;
	}
	else
	{
		$valid_event['cleandata']['enable'] = (int)$data['enable'];
	}

	$day = (int)$data['day'];
	$month = (int)$data['month'];
	$year = (int)$data['year'];
	if(!(isset($data['day']) && isset($data['month']) && isset($data['year'])))
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invaliddateline;
	}
	elseif($day > 31 || $day < 1 || $month > 12 || $month < 1 || $year < 2000 || $year > 2020)
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invaliddateline;
	}
	else
	{
		$valid_event['cleandata']['dateline'] = gmmktime(0, 0, 0, $month, $day, $year);
	}

	if(!verify_post_check($data['my_post_key'], true))
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invalidpostkey;
	}

	if(!$valid_event['valid'])
	{
		$valid_event['errors'] or ($valid_event['errors'] = 'uknown');
	}

	return $valid_event;
}

// Validate an match
function ougc_matchevents_validate_match($data=array())
{
	global $lang, $db;
	ougc_matchevents_lang_load();

	$valid_event = array('valid' => true, 'errors' => array(), 'cleandata' => array());

	$event = ougc_matchevents_get_event($data['eid']);
	if(!$event['eid'] || (TIME_NOW > $event['dateline']))
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invalidevent;
	}
	else
	{
		$valid_event['cleandata']['eid'] = (int)$event['eid'];
	}

	if(!isset($data['name']))
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invalidname;
	}
	elseif(!trim($data['name']) || my_strlen($data['name']) > 100)
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invalidname;
	}
	else
	{
		$valid_event['cleandata']['name'] = $db->escape_string($data['name']);
	}

	if(!isset($data['description']))
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invaliddesc;
	}
	elseif(!trim($data['description']) || my_strlen($data['description']) > 255)
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invaliddesc;
	}
	else
	{
		$valid_event['cleandata']['description'] = $db->escape_string($data['description']);
	}

	if(isset($data['disporder']))
	{
		$valid_event['cleandata']['disporder'] = (int)$data['disporder'];
	}
	else
	{
		global $db;

		$query = $db->simple_select('ougc_matchevents_matches', 'MAX(disporder) AS disporder');
		$valid_event['cleandata']['disporder'] = (int)$db->fetch_field($query, 'disporder')+1;
	}

	if(!verify_post_check($data['my_post_key'], true))
	{
		$valid_event['valid'] = false;
		$valid_event['errors'][] = $lang->ougc_matchevents_error_invalidpostkey;
	}

	if(!$valid_event['valid'])
	{
		$valid_event['errors'] or ($valid_event['errors'] = 'uknown');
	}

	return $valid_event;
}

// We want a nice "Newest event" bar in the forum, right?
function ougc_matchevents_update_cache()
{
	global $db, $cache;

	$time = TIME_NOW;
	$content = array();
	// This currently only fetch the next event, but remind me to do it so that it fetch next events with actual matches in it.
	$query = $db->simple_select('ougc_matchevents_events', 'eid, name, dateline', "enable='1' AND dateline>'{$time}'", array('limit' => 1, 'order_by' => 'dateline DESC, eid'));
	$event = $db->fetch_array($query);

	if($event['eid'])
	{
		$content = $event;
	}

	$cache->update('ougc_matchevents', $content);
}

// Get a event from the database
function ougc_matchevents_get_event($eid)
{
	$eid = (int)$eid;
	static $matchevents_events = array();

	if(!isset($matchevents_events[$eid]))
	{
		global $db;

		$query = $db->simple_select('ougc_matchevents_events', '*', "eid='{$eid}'", array('limit' => 1));
		$matchevents_events[$eid] = $db->fetch_array($query);

		if(!isset($matchevents_events[$eid]['eid']))
		{
			$matchevents_events[$eid] = false;
		}
	}

	return $matchevents_events[$eid];
}

// Get a event from the database
function ougc_matchevents_get_match($eid, $fid)
{
	$eid = (int)$eid;
	$fid = (int)$fid;
	static $matchevents_matches = array();
	$matchevents_matches[$eid] = array();

	if(!isset($matchevents_matches[$eid][$fid]))
	{
		global $db;

		$query = $db->simple_select('ougc_matchevents_matches', '*', "fid='{$fid}' AND eid='{$eid}'", array('limit' => 1));
		$matchevents_matches[$eid][$fid] = $db->fetch_array($query);

		if(!isset($matchevents_matches[$eid][$fid]['fid']))
		{
			$matchevents_matches[$eid][$fid] = false;
		}
	}

	return $matchevents_matches[$eid][$fid];
}














/*

define("SECOND", 1);
define("MINUTE", 60 * SECOND);
define("HOUR", 60 * MINUTE);
define("DAY", 24 * HOUR);
define("MONTH", 30 * DAY);
function relativeTime($time)
{   
    $delta = time() - $time;

    if ($delta < 1 * MINUTE)
    {
        return $delta == 1 ? "one second ago" : $delta . " seconds ago";
    }
    if ($delta < 2 * MINUTE)
    {
      return "a minute ago";
    }
    if ($delta < 45 * MINUTE)
    {
        return floor($delta / MINUTE) . " minutes ago";
    }
    if ($delta < 90 * MINUTE)
    {
      return "an hour ago";
    }
    if ($delta < 24 * HOUR)
    {
      return floor($delta / HOUR) . " hours ago";
    }
    if ($delta < 48 * HOUR)
    {
      return "yesterday";
    }
    if ($delta < 30 * DAY)
    {
        return floor($delta / DAY) . " days ago";
    }
    if ($delta < 12 * MONTH)
    {
      $months = floor($delta / DAY / 30);
      return $months <= 1 ? "one month ago" : $months . " months ago";
    }
    else
    {
        $years = floor($delta / DAY / 365);
        return $years <= 1 ? "one year ago" : $years . " years ago";
    }
}

function contextualTime($small_ts, $large_ts=false) {
  if(!$large_ts) $large_ts = time();
  $n = $large_ts - $small_ts;
  if($n <= 1) return 'less than 1 second ago';
  if($n < (60)) return $n . ' seconds ago';
  if($n < (60*60)) { $minutes = round($n/60); return 'about ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago'; }
  if($n < (60*60*16)) { $hours = round($n/(60*60)); return 'about ' . $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago'; }
  if($n < (time() - strtotime('yesterday'))) return 'yesterday';
  if($n < (60*60*24)) { $hours = round($n/(60*60)); return 'about ' . $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago'; }
  if($n < (60*60*24*6.5)) return 'about ' . round($n/(60*60*24)) . ' days ago';
  if($n < (time() - strtotime('last week'))) return 'last week';
  if(round($n/(60*60*24*7))  == 1) return 'about a week ago';
  if($n < (60*60*24*7*3.5)) return 'about ' . round($n/(60*60*24*7)) . ' weeks ago';
  if($n < (time() - strtotime('last month'))) return 'last month';
  if(round($n/(60*60*24*7*4))  == 1) return 'about a month ago';
  if($n < (60*60*24*7*4*11.5)) return 'about ' . round($n/(60*60*24*7*4)) . ' months ago';
  if($n < (time() - strtotime('last year'))) return 'last year';
  if(round($n/(60*60*24*7*52)) == 1) return 'about a year ago';
  if($n >= (60*60*24*7*4*12)) return 'about ' . round($n/(60*60*24*7*52)) . ' years ago'; 
  return false;
}

   1:  function relativeTime($time)
   2:   
   3:  // this function will calculate a friendly date difference string
   4:  // based upon $time and how it compares to the current time
   5:  // for example it will return "1 minute ago" if the difference
   6:  // in seconds is between 60 and 120 seconds
   7:  // $time is a GM-based Unix timestamp, this makes for a timezone
   8:  // neutral comparison
   9:   
  10:  {   
  11:      $delta = strtotime(gmdate("Y-m-d H:i:s", time())) - $time;
  12:      
  13:      if ($delta < 1 * MINUTE)
  14:      {
  15:          return $delta == 1 ? "one second ago" : $delta . " seconds ago";
  16:      }
  17:      if ($delta < 2 * MINUTE)
  18:      {
  19:        return "a minute ago";
  20:      }
  21:      if ($delta < 45 * MINUTE)
  22:      {
  23:          return floor($delta / MINUTE) . " minutes ago";
  24:      }
  25:      if ($delta < 90 * MINUTE)
  26:      {
  27:        return "an hour ago";
  28:      }
  29:      if ($delta < 24 * HOUR)
  30:      {
  31:        return floor($delta / HOUR) . " hours ago";
  32:      }
  33:      if ($delta < 48 * HOUR)
  34:      {
  35:        return "yesterday";
  36:      }
  37:      if ($delta < 30 * DAY)
  38:      {
  39:          return floor($delta / DAY) . " days ago";
  40:      }
  41:      if ($delta < 12 * MONTH)
  42:      {
  43:        $months = floor($delta / DAY / 30);
  44:        return $months <= 1 ? "one month ago" : $months . " months ago";
  45:      }
  46:      else
  47:      {
  48:          $years = floor($delta / DAY / 365);
  49:          return $years <= 1 ? "one year ago" : $years . " years ago";
  50:      }
  51:  }


*/
