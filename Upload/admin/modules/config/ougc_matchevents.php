<?php

/***************************************************************************
 *
 *	OUGC Match Events plugin (/admin/modules/config/ougc_matchevents.php)
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
(defined('IN_MYBB') && defined('IN_ADMINCP') && function_exists('ougc_matchevents_lang_load')) or die('Direct initialization of this file is not allowed.');

// Load lang
ougc_matchevents_lang_load();
$mybb->input['eid'] = intval($mybb->input['eid']);
$mybb->input['fid'] = intval($mybb->input['fid']);

$page->add_breadcrumb_item($lang->ougc_matchevents, ACPEVENTS_URL);
$page->output_header($lang->ougc_matchevents_acp_nav);

// MANAGING EVENT START
if(isset($mybb->input['manage']))
{
	$mybb->input['eid'] = (int)$mybb->input['manage'];
	if(!($event = ougc_matchevents_get_event($mybb->input['eid'])))
	{
		ougc_matchevents_admin_redirect($lang->ougc_matchevents_error_invalidevent, '', true);
	}
	$this_url = ACPEVENTS_URL.'&amp;manage='.$mybb->input['eid'];

	$sub_tabs['ougc_matchevents_main'] = array(
		'title'			=> $lang->ougc_matchevents_tab_main,
		'link'			=> ACPEVENTS_URL
	);
	$sub_tabs['ougc_matchevents_view'] = array(
		'title'			=> $lang->ougc_matchevents_tab_view,
		'link'			=> $this_url,
		'description'		=> $lang->sprintf($lang->ougc_matchevents_tab_view_f_d, $event['name'])
	);
	$sub_tabs['ougc_matchevents_add'] = array(
		'title'			=> $lang->ougc_matchevents_tab_add,
		'link'			=> $this_url.'&amp;action=add',
		'description'		=> $lang->sprintf($lang->ougc_matchevents_tab_add_f_d, $event['name'])
	);

	if($mybb->input['action'] == 'edit')
	{
		$sub_tabs['ougc_matchevents_edit'] = array(
			'title'			=> $lang->ougc_matchevents_tab_edit,
			'link'			=> $this_url.'&amp;action=edit&amp;fid='.$mybb->input['fid'],
			'description'		=> $lang->ougc_matchevents_tab_edit_f_d
		);
	}

	if($mybb->input['action'] == 'add')
	{
		if($mybb->request_method == 'post')
		{
			$validate = ougc_matchevents_validate_match(array(
				'name'			=>	$mybb->input['name'],
				'description'		=>	$mybb->input['description'],
				'disporder'		=>	$mybb->input['disporder'],
				'eid'			=>	$event['eid'],
				'my_post_key'		=>	$mybb->input['my_post_key']
			));

			if($validate['valid'])
			{
				$id = (int)$db->insert_query('ougc_matchevents_matches', $validate['cleandata']);
				log_admin_action();
				ougc_matchevents_update_cache();
				ougc_matchevents_admin_redirect($lang->ougc_matchevents_success_add, '&amp;manage='.$event['eid']);
			}
			else
			{
				$page->output_inline_error($validate['errors']);
			}
		}
		$page->output_nav_tabs($sub_tabs, 'ougc_matchevents_add');
		$form = new Form($sub_tabs['ougc_matchevents_add']['link'].'&amp;my_post_key='.$mybb->post_code, 'post');
		$form_container = new FormContainer($sub_tabs['ougc_matchevents_add']['description']);
		$form_container->output_row($lang->ougc_matchevents_f_name.' <em>*</em>', $lang->ougc_matchevents_f_name_d, $form->generate_text_box('name', $mybb->input['name']));
		$form_container->output_row($lang->ougc_matchevents_f_desc.' <em>*</em>', $lang->ougc_matchevents_f_desc_d, $form->generate_text_box('description', $mybb->input['description']));
		$form_container->output_row($lang->ougc_matchevents_f_disporder, $lang->ougc_matchevents_f_enabled_d, $form->generate_text_box('disporder', (int)$mybb->input['enable'], array('class' => 'align_center field50')));
		$form_container->end();
		$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_matchevents_submit), $form->generate_reset_button($lang->ougc_matchevents_reset)));
		$form->end();
	}
	elseif($mybb->input['action'] == 'edit')
	{
		if(!($match = ougc_matchevents_get_match($event['eid'], $mybb->input['fid'])))
		{
			ougc_matchevents_admin_redirect($lang->ougc_matchevents_error_invalidmatch, '&amp;manage='.$event['eid'], true);
		}

		$input = array(
			'name'			=> $match['name'],
			'description'		=> $match['description'],
			'disporder'		=> $match['disporder'],
		);

		if($mybb->request_method == 'post')
		{
			$input = array(
				'name'			=> $mybb->input['name'],
				'description'		=> $mybb->input['description'],
				'disporder'		=> $mybb->input['disporder'],
			);

			$validate = ougc_matchevents_validate_match(array(
				'name'			=>	$mybb->input['name'],
				'description'		=>	$mybb->input['description'],
				'disporder'		=>	$mybb->input['disporder'],
				'eid'			=>	$match['eid'],
				'my_post_key'		=>	$mybb->input['my_post_key']
			));

			if($validate['valid'])
			{
				unset($validate['valid']['eid']);
				$id = (int)$db->update_query('ougc_matchevents_matches', $validate['cleandata'], "fid={$match['fid']}");
				log_admin_action();
				ougc_matchevents_update_cache();
				ougc_matchevents_admin_redirect($lang->ougc_matchevents_success_add, '&amp;manage='.$event['eid']);
			}
			else
			{
				$page->output_inline_error($validate['errors']);
			}
		}

		$page->output_nav_tabs($sub_tabs, 'ougc_matchevents_edit');
		$form = new Form($sub_tabs['ougc_matchevents_edit']['link'].'&amp;fid=2&amp;my_post_key='.$mybb->post_code, 'post');
		$form_container = new FormContainer($sub_tabs['ougc_matchevents_edit']['description']);
		$form_container->output_row($lang->ougc_matchevents_f_name.' <em>*</em>', $lang->ougc_matchevents_f_name_d, $form->generate_text_box('name', $input['name']));
		$form_container->output_row($lang->ougc_matchevents_f_desc.' <em>*</em>', $lang->ougc_matchevents_f_desc_d, $form->generate_text_box('description', $input['description']));
		$form_container->output_row($lang->ougc_matchevents_f_disporder, $lang->ougc_matchevents_f_enabled_d, $form->generate_text_box('disporder', (int)$input['disporder'], array('class' => 'align_center field50')));
		$form_container->end();
		$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_matchevents_submit), $form->generate_reset_button($lang->ougc_matchevents_reset)));
		$form->end();
	}
	elseif($mybb->input['action'] == 'delete')
	{
		if(!($match = ougc_matchevents_get_match($event['eid'], $mybb->input['fid'])))
		{
			ougc_matchevents_admin_redirect($lang->ougc_matchevents_error_invalidmatch, '&amp;manage='.$event['eid'], true);
		}


		if($mybb->request_method == 'post')
		{
			if(!isset($mybb->input['no']))
			{
				$db->delete_query('ougc_matchevents_matches', "fid='{$match['fid']}'");
				log_admin_action();
				ougc_matchevents_update_cache();
				ougc_matchevents_admin_redirect($lang->ougc_matchevents_success_deletedmatch, '&amp;manage='.$event['eid']);
			}
			ougc_matchevents_admin_redirect('', '&amp;manage='.$event['eid']);
		}

		$form = new Form($this_url.'&amp;action=delete&amp;fid='.$match['fid'], 'post');
		echo "<div class=\"confirm_action\">\n<p>{$lang->confirm_action}</p>\n<br />\n
	<p class=\"buttons\">\n{$form->generate_submit_button($lang->yes, array('class' => 'button_yes'))}{$form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'))}</p>\n
	</div>\n";
		$form->end();
		$page->output_footer();
	}
	else
	{
		if($mybb->request_method == 'post')
		{
			foreach((array)$mybb->input['disporder'] as $key => $val)
			{
				$db->update_query('ougc_matchevents_matches', array('disporder' => (int)$val), 'fid=\''.intval($key).'\'');
			}
		}

		$page->output_nav_tabs($sub_tabs, 'ougc_matchevents_view');

		$form = new Form($this_url, 'post');
		$form_container = new FormContainer($lang->sprintf($lang->in_forums, $forum_cache[$fid]['name']));
		$table = new Table;
		$table->construct_header($lang->ougc_matchevents_f_name, array('width' => '20%'));
		$table->construct_header($lang->ougc_matchevents_f_desc, array('width' => '30%'));
		$table->construct_header($lang->ougc_matchevents_f_disporder, array('width' => '15%', 'class' => 'align_center'));
		$table->construct_header($lang->options, array('width' => '10%', 'class' => 'align_center'));

		$query = $db->simple_select('ougc_matchevents_matches', '*', "eid='{$mybb->input['eid']}'", array('order_by' => 'disporder, fid'));
		if($db->num_rows($query) < 1)
		{
			$table->construct_cell('<div align="center">'.$lang->ougc_matchevents_v_empty_f.'</div>', array('colspan' => 4));
			$table->construct_row();
		}
		else
		{
			while($match = $db->fetch_array($query))
			{
				$table->construct_cell(htmlspecialchars_uni($match['name']));
				$table->construct_cell(htmlspecialchars_uni($match['description']));
				$table->construct_cell($form->generate_text_box('disporder['.$match['fid'].']', (int)$match['disporder'], array('class' => 'align_center field50')), array('class' => 'align_center'));


				$popup = new PopupMenu('event_'.$match['fid'], $lang->options);
				$popup->add_item($lang->ougc_matchevents_tab_edit, $this_url.'&amp;action=edit&amp;fid='.$match['fid']);
				$popup->add_item($lang->ougc_matchevents_f_delete, $this_url.'&amp;action=delete&amp;fid='.$match['fid']);
				$table->construct_cell($popup->fetch(), array('class' => 'align_center'));

				$table->construct_row();
			}
		}
		$table->output($sub_tabs['ougc_matchevents_view']['description']);
		$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_matchevents_submit), $form->generate_reset_button($lang->ougc_matchevents_reset)));
		$form->end();
	}
	$page->output_footer();
	exit;
}

# VIEWING EVENTS START
$sub_tabs['ougc_matchevents_view'] = array(
	'title'			=> $lang->ougc_matchevents_tab_view,
	'link'			=> ACPEVENTS_URL,
	'description'	=> $lang->ougc_matchevents_tab_view_d
);
$sub_tabs['ougc_matchevents_add'] = array(
	'title'			=> $lang->ougc_matchevents_tab_add,
	'link'			=> ACPEVENTS_URL.'&amp;action=add',
	'description'	=> $lang->ougc_matchevents_tab_add_d
);

if($mybb->input['action'] == 'edit')
{
	$sub_tabs['ougc_matchevents_edit'] = array(
		'title'			=> $lang->ougc_matchevents_tab_edit,
		'link'			=> ACPEVENTS_URL.'&amp;action=edit&amp;eid='.$mybb->input['eid'],
		'description'	=> $lang->ougc_matchevents_tab_edit_d
	);
}

if($mybb->input['action'] == 'add')
{
	if($mybb->request_method == 'post')
	{
		$validate = ougc_matchevents_validate_event(array(
			'name'			=>	$mybb->input['name'],
			'description'	=>	$mybb->input['description'],
			'enable'		=>	$mybb->input['enable'],
			'day'			=>	$mybb->input['dateline_day'],
			'month'			=>	$mybb->input['dateline_month'],
			'year'			=>	$mybb->input['dateline_year'],
			'my_post_key'	=>	$mybb->input['my_post_key']
		));
		if($validate['valid'])
		{
			$id = (int)$db->insert_query('ougc_matchevents_events', $validate['cleandata']);
			log_admin_action();
			ougc_matchevents_update_cache();
			ougc_matchevents_admin_redirect($lang->ougc_matchevents_success_add);
		}
		else
		{
			$page->output_inline_error($validate['errors']);
		}
	}

	$page->output_nav_tabs($sub_tabs, 'ougc_matchevents_add');
	$form = new Form(ACPEVENTS_URL.'&amp;action=add&amp;my_post_key='.$mybb->post_code, 'post');
	$form_container = new FormContainer($lang->ougc_matchevents_tab_add_d);

	$form_container->output_row($lang->ougc_matchevents_f_name.' <em>*</em>', $lang->ougc_matchevents_f_name_d, $form->generate_text_box('name', $mybb->input['name']));
	$form_container->output_row($lang->ougc_matchevents_f_desc, $lang->ougc_matchevents_f_desc_d, $form->generate_text_box('description', $mybb->input['description']));
	$form_container->output_row($lang->ougc_matchevents_f_enabled, $lang->ougc_matchevents_f_enabled_d, $form->generate_yes_no_radio('enable', (int)$mybb->input['enable']));
	$form_container->output_row($lang->ougc_matchevents_f_date.' <em>*</em>', $lang->ougc_matchevents_f_date_d, $form->generate_date_select('dateline', (int)$mybb->input['dateline_day'], (int)$mybb->input['dateline_month'], (int)$mybb->input['dateline_year']));

	$form_container->end();
	$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_matchevents_submit), $form->generate_reset_button($lang->ougc_matchevents_reset)));
	$form->end();
}
elseif($mybb->input['action'] == 'edit')
{
	if(!($event = ougc_matchevents_get_event($mybb->input['eid'])))
	{
		ougc_matchevents_admin_redirect($lang->ougc_matchevents_error_invalidevent, '', true);
	}

	$input = array(
		'name'				=> $event['name'],
		'description'		=> $event['description'],
		'enable'			=> $event['enable'],
		'dateline_day'		=> date('d', $event['dateline']),
		'dateline_month'	=> date('m', $event['dateline']),
		'dateline_year'		=> date('Y', $event['dateline']),
	);

	if($mybb->request_method == 'post')
	{
		$input = array(
			'name'				=> $mybb->input['name'],
			'description'		=> $mybb->input['description'],
			'enable'			=> $mybb->input['enable'],
			'dateline_day'		=> $mybb->input['dateline_day'],
			'dateline_month'	=> $mybb->input['dateline_month'],
			'dateline_year'		=> $mybb->input['dateline_year'],
		);
		$validate = ougc_matchevents_validate_event(array(
			'name'			=>	$mybb->input['name'],
			'description'	=>	$mybb->input['description'],
			'enable'		=>	$mybb->input['enable'],
			'day'			=>	$mybb->input['dateline_day'],
			'month'			=>	$mybb->input['dateline_month'],
			'year'			=>	$mybb->input['dateline_year'],
			'my_post_key'	=>	$mybb->input['my_post_key']
		));

		if($validate['valid'])
		{
			$db->update_query('ougc_matchevents_events', $validate['cleandata'], "eid='{$event['eid']}'");
			log_admin_action();
			ougc_matchevents_update_cache();
			ougc_matchevents_admin_redirect($lang->ougc_matchevents_success_edit);
		}
		else
		{
			$page->output_inline_error($validate['errors']);
		}
	}

	$page->output_nav_tabs($sub_tabs, 'ougc_matchevents_edit');
	$form = new Form(ACPEVENTS_URL.'&amp;action=edit&amp;my_post_key='.$mybb->post_code.'&amp;eid='.$event['eid'], 'post');
	$form_container = new FormContainer($lang->ougc_matchevents_tab_edit_d);

	$form_container->output_row($lang->ougc_matchevents_f_name.' <em>*</em>', $lang->ougc_matchevents_f_name_d, $form->generate_text_box('name', $input['name']));
	$form_container->output_row($lang->ougc_matchevents_f_desc, $lang->ougc_matchevents_f_desc_d, $form->generate_text_box('description', $input['description']));
	$form_container->output_row($lang->ougc_matchevents_f_enabled, $lang->ougc_matchevents_f_enabled_d, $form->generate_yes_no_radio('enable', (int)$input['enable']));
	$form_container->output_row($lang->ougc_matchevents_f_date.' <em>*</em>', $lang->ougc_matchevents_f_date_d, $form->generate_date_select('dateline', (int)$input['dateline_day'], (int)$input['dateline_month'], (int)$input['dateline_year']));

	$form_container->end();
	$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_matchevents_submit), $form->generate_reset_button($lang->ougc_matchevents_reset)));
	$form->end();
}
elseif($mybb->input['action'] == 'delete')
{
	if(!($event = ougc_matchevents_get_event($mybb->input['eid'])))
	{
		ougc_matchevents_admin_redirect($lang->ougc_matchevents_error_invalidevent, '', true);
	}

	if($mybb->request_method == 'post')
	{
		if(!isset($mybb->input['no']))
		{
			$db->delete_query('ougc_matchevents_events', "eid='{$event['eid']}'");
			log_admin_action();
			ougc_matchevents_update_cache();
			ougc_matchevents_admin_redirect($lang->ougc_matchevents_success_deletedevent);
		}
		ougc_matchevents_admin_redirect();
	}

	$form = new Form(ACPEVENTS_URL.'&amp;action=delete&amp;eid='.$event['eid'], 'post');
	echo "<div class=\"confirm_action\">\n<p>{$lang->confirm_action}</p>\n<br />\n
<p class=\"buttons\">\n{$form->generate_submit_button($lang->yes, array('class' => 'button_yes'))}{$form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'))}</p>\n
</div>\n";
	$form->end();
	$page->output_footer();
}
else
{
	$page->output_nav_tabs($sub_tabs, 'ougc_matchevents_view');

	$table = new Table;
	$table->construct_header($lang->ougc_matchevents_f_name, array('width' => '20%'));
	$table->construct_header($lang->ougc_matchevents_f_desc, array('width' => '30%'));
	$table->construct_header($lang->ougc_matchevents_f_enabled, array('width' => '15%', 'class' => 'align_center'));
	$table->construct_header($lang->ougc_matchevents_f_date, array('width' => '25%', 'class' => 'align_center'));
	$table->construct_header($lang->options, array('width' => '10%', 'class' => 'align_center'));

	$query = $db->simple_select('ougc_matchevents_events', '*', '', array('order_by' => 'dateline DESC, eid'));
	if($db->num_rows($query) < 1)
	{
		$table->construct_cell('<div align="center">'.$lang->ougc_matchevents_v_empty.'</div>', array('colspan' => 5));
		$table->construct_row();
	}
	else
	{
		while($event = $db->fetch_array($query))
		{
			$table->construct_cell(htmlspecialchars_uni($event['name']));
			$table->construct_cell(htmlspecialchars_uni($event['description']));
			$table->construct_cell(($event['enable'] ? $lang->yes : $lang->no), array('class' => 'align_center'));
			$table->construct_cell($lang->sprintf($lang->ougc_matchevents_f_datelang, my_date('F d, Y', $event['dateline'])/*, my_date($mybb->settings['timeformat'], $event['dateline'])*/), array('class' => 'align_center'));

			$popup = new PopupMenu('event_'.$event['eid'], $lang->options);
			$popup->add_item($lang->ougc_matchevents_tab_edit, ACPEVENTS_URL.'&amp;action=edit&amp;eid='.$event['eid']);
			$popup->add_item($lang->ougc_matchevents_f_manage, ACPEVENTS_URL.'&amp;manage='.$event['eid']);
			$popup->add_item($lang->ougc_matchevents_f_delete, ACPEVENTS_URL.'&amp;action=delete&amp;eid='.$event['eid']);
			$table->construct_cell($popup->fetch(), array('class' => 'align_center'));

			$table->construct_row();
		}
	}
	$table->output($lang->ougc_matchevents_tab_view_d);
}

$page->output_footer();
