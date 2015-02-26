<?php

/***************************************************************************
 *
 *	OUGC Match Events plugin (/matchevents.php)
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

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'matchevents.php');

$templatelist = 'ougc_matchevents, ougc_matchevents_events, ougc_matchevents_events_empty, ougc_matchevents_events_row';

require_once './global.php';

// No permission for guests
($mybb->user['uid'] && function_exists('ougc_matchevents_lang_load')) or error_no_permission();

$plugins->run_hooks('ougc_matchevents_start');

// Load lang
ougc_matchevents_lang_load();

// Make navigation
add_breadcrumb($lang->ougc_matchevents_nav, 'matchevents.php');

if(isset($mybb->input['eid']))
{
	if(!($event = ougc_matchevents_get_event($mybb->input['eid'])))
	{
		error($lang->ougc_matchevents_invalidevent);
	}
	add_breadcrumb($lang->ougc_matchevents_nav, 'matchevents.php');
}
else
{
	$limit = 10;
	$rows = '';
	$query = $db->query("SELECT e.*, f.matches
		FROM ".TABLE_PREFIX."ougc_matchevents_events e
		LEFT JOIN (SELECT eid, COUNT(fid) AS matches FROM ".TABLE_PREFIX."ougc_matchevents_matches GROUP BY eid) f ON (f.eid=e.eid)
		ORDER BY e.dateline DESC, f.matches DESC
		LIMIT 0, {$limit};
	");

	$events = '';
	$trow = alt_trow(true);
	while($event = $db->fetch_array($query))
	{
		$trow = alt_trow();

		$event['eid'] = (int)$event['eid'];
		$event['name'] = htmlspecialchars_uni($event['name']);
		$event['description'] = htmlspecialchars_uni($event['description']);
		$event['enable'] = (int)$event['enable'];
		$event['dateline'] = (int)$event['dateline'];
		$event['matches'] = (int)$event['matches'];

		$status = '';
		if(!$event['enable'])
		{
			$status = 'lock';
		}
		$dateline = my_date('F d, Y', $event['dateline']);
		$matches = my_number_format($event['matches']);
		eval('$events .= "'.$templates->get('ougc_matchevents_events_row').'";');
	}

	if(!$events)
	{
		eval('$events = "'.$templates->get('ougc_matchevents_events_empty').'";');
	}

	eval('$content = "'.$templates->get('ougc_matchevents_events').'";');
	#_dump($content);
}

$plugins->run_hooks('ougc_matchevents_end');

eval('$page = "'.$templates->get('ougc_matchevents').'";');
output_page($page);
exit;
