<?php
/*============================================================================*\
|| ########################################################################## ||
|| # Timeslip Database by Dragonsys (for vBulletin 4.2.3)                   # ||
|| # Version 1.2.6 (UnReleased on December 30, 2015)                        # ||
|| ########################################################################## ||
\*============================================================================*/

error_reporting(E_ALL & ~E_NOTICE);
define('THIS_SCRIPT', 'dso_slips');
define('CSRF_PROTECTION', true);

$specialtemplates = array();
$phrasegroups = array();
$globaltemplates = array(
	'GENERIC_SHELL',
	'dso_slips_add_slip',
	'dso_slips_added',
	'dso_slips_insert_missing',
	'dso_slips_edit_slip',
	'dso_slips_updated',
	'dso_slips_delete_slip',
	'dso_slips_deleted',
	'dso_slips_list_tracks',
	'dso_slips_view_track',
	'dso_slips_add_track',
	'dso_slips_edit_track',
	'dso_slips_mod_track',
	'dso_slips_delete_track',
	'dso_slips.css'
);
$actiontemplates = array(
	'main'	=> array(
		'dso_slips_list_slips',
		'dso_slips_footer'
	),
	'viewslip'	=> array(
		'dso_slips_view_slip',
		'dso_slips_footer'
	)
);
$actiontemplates['none'] =& $actiontemplates['main'];
$pagetitle = 'Timeslip Database';
// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once('./includes/functions_misc.php');
require_once('./includes/functions_user.php');
require_once('./includes/functions_dso_slips.php');

// ### STANDARD INITIALIZATIONS ###

// Stylesheet
$includecss = array();
$includecss['dso_slips'] = 'dso_slips.css';

// Include lightbox code
//$headinclude .= vB_Template::create('dso_slips_headinclude')->render();

// Custom Title
$pagetitle = $vbulletin->options['dso_slips_navbar'];

// Start the navbar
$navbits = array('slips.php' . $vbulletin->session->vars['sessionurl_q'] => $vbulletin->options['dso_slips_navbar']);
    
// Select Action
if (empty($_REQUEST['do']))
{
    $_REQUEST['do'] = 'main';
}

// #######################################################################
// #                      VIEW A TIMESLIP'S DETAILS                      #
// #######################################################################
if ($_REQUEST['do'] == 'viewslip') {
	if ($show['dso_slips_view']) {
		$vbulletin->input->clean_array_gpc('g', array(
			'id' => TYPE_INT
		));
		$slipsqueue = $db->query_read("SELECT ID,Car_ID,Owner,Date,Track,Temp,Lane,RT,Dial,Sixty,ThreeThirty,Eighth_ET,Eighth_MPH,Thousand,Fourth_ET,Fourth_MPH,DA,Win,Notes FROM " . TABLE_PREFIX . "dso_slips WHERE ID = " . $vbulletin->GPC['id'] . " LIMIT 1");
		while ($row = $db->fetch_array($slipsqueue)) {
			if (!empty($row['DA'])) {$row['DA'] = number_format($row['DA'],0,'.',',') . " ft";}
			if (!empty($row['Temp'])) {$row['Temp'] = number_format($row['Temp'],1,'.',',') . "&deg; F";}
			if (empty($row['Win'])) {$row['Win'] = "&nbsp;";}
			if (!empty($row['Eighth_ET']) AND !empty($row['Eighth_MPH'])) { $row['Eighth_ET'] = number_format($row['Eighth_ET'],3,'.',',') . " @ " . number_format($row['Eighth_MPH'],2,'.',',') . " mph"; }
			if (!empty($row['Fourth_ET']) AND !empty($row['Fourth_MPH'])) { $row['Fourth_ET'] = number_format($row['Fourth_ET'],3,'.',',') . " @ " . number_format($row['Fourth_MPH'],2,'.',',') . " mph"; }
			if (!empty($row['Thousand'])) { $row['Thousand'] = number_format($row['Thousand'],3,'.',',');} else { $row['Thousand'] = ''; }
			if (!empty($row['Dial'])) { $row['Dial'] = number_format($row['Dial'],3,'.',',');} else { $row['Dial'] = ''; }
			if (!empty($row['RT'])) { $row['RT'] = number_format($row['RT'],3,'.',',');} else { $row['RT'] = ''; }
			if (!empty($row['Sixty'])) { $row['Sixty'] = number_format($row['Sixty'],3,'.',',');} else { $row['Sixty'] = ''; }
			if (!empty($row['ThreeThirty'])) { $row['ThreeThirty'] = number_format($row['ThreeThirty'],3,'.',',');} else { $row['ThreeThirty'] = ''; }
			$queue[] = array (
				'id'		=> $row['ID'],
				'car_id'	=> $row['Car_ID'],
				'car_name'	=> get_vehicle_name($row['Car_ID']),
				'driver_id'	=> $row['Owner'],
				'driver'	=> get_driver_name($row['Owner']),
				'date'		=> date("M d, Y - g:i a ",strtotime($row['Date'])),
				'track'		=> get_track_name($row['Track']),
				'track_www'	=> get_track_web($row['Track']),
				'temp'		=> $row['Temp'],
				'lane'		=> $row['Lane'],
				'rt'		=> $row['RT'],
				'dialin'	=> $row['Dial'],
				'60ft'		=> $row['Sixty'],
				'330ft'		=> $row['ThreeThirty'],
				'8et'		=> $row['Eighth_ET'],
				'8mph'		=> $row['Eight_MPH'],
				'1000ft'	=> $row['Thousand'],
				'4et'		=> $row['Fourth_ET'],
				'4mph'		=> $row['Fourth_MPH'],
				'da'		=> $row['DA'],
				'win'		=> $row['Win'],
				'notes'		=> $row['Notes']
				);
			}
	} else { 
		eval(standard_error(fetch_error('no_permission')));
	}
	$templater = vB_Template::create('dso_slips_view_slip');
}

// #######################################################################
// #                            ADD A TIMESLIP                           #
// #######################################################################
if ($_REQUEST['do'] == 'addslip') {
	if ($show['dso_slips_add']) {
		$dso_slips_today = date('m-d-Y H:i:s');
		$vehicle_list = $db->query_read("SELECT id,year,model FROM " . TABLE_PREFIX . "vbrides_rides WHERE userid = " . $vbulletin->userinfo['userid'] . "");
		while ($row = $db->fetch_array($vehicle_list)) {
			$vlist[] = array (
				'id'	=> $row['id'],
				'year'	=> $row['year'],
				'model'	=> $row['model']
				);
		}
		$track_list = $db->query_read("SELECT ID,Name FROM " . TABLE_PREFIX . "dso_tracks");
		while ($row = $db->fetch_array($track_list)) {
			$tlist[] = array (
				'id'	=> $row['ID'],
				'name'	=> $row['Name']
				);
		}
		$templater = vB_Template::create('dso_slips_add_slip');
		$templater->register('dso_slips_today', $dso_slips_today);
		$templater->register('vlist', $vlist);
		$templater->register('tlist', $tlist);
	}
}

// #######################################################################
// #                    PERFORM DB INSERT FOR TIMESLIP                   #
// #######################################################################
if ($_POST['do'] == "insertslip") {
	if ($_POST['temp'] AND !is_numeric($_POST['temp'])) { $not_numeric = 1; $temp_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Temperature</i></u> must be in a decimal format<br>'; }
	if ($_POST['rt'] AND !is_numeric($_POST['rt'])) { $not_numeric = 1; $rt_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Reation Time</i></u> must be in a decimal format<br>'; }
	if ($_POST['dial'] AND !is_numeric($_POST['dial'])) { $not_numeric = 1; $dial_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Dial-In</i></u> must be in a decimal format<br>'; }
	if ($_POST['sixty'] AND !is_numeric($_POST['sixty'])) { $not_numeric = 1; $sixty_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>60ft</i></u> must be in a decimal format<br>'; }
	if ($_POST['threethirty'] AND !is_numeric($_POST['threethirty'])) { $not_numeric = 1; $threethirty_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>330ft</i></u> must be in a decimal format<br>'; }
	if ($_POST['eighth_et'] AND !is_numeric($_POST['eighth_et'])) { $not_numeric = 1; $eighth_et_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>8th Mile ET</i></u> must be in a decimal format<br>'; }
	if ($_POST['eighth_mph'] AND !is_numeric($_POST['eighth_mph'])) { $not_numeric = 1; $eighth_mph_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>8th Mile MPH</i></u> must be in a decimal format<br>'; }
	if ($_POST['thousand'] AND !is_numeric($_POST['thousand'])) { $not_numeric = 1; $thousand_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>1000ft</i></u> must be in a decimal format<br>'; }
	if ($_POST['fourth_et'] AND !is_numeric($_POST['fourth_et'])) { $not_numeric = 1; $fourth_et_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Quater Mile ET</i></u> must be in a decimal format<br>'; }
	if ($_POST['fourth_mph'] AND !is_numeric($_POST['fourth_mph'])) { $not_numeric = 1; $fourth_mph_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Quater Mile MPH</i></u> must be in a decimal format<br>'; }
	if ($_POST['da'] AND !is_numeric($_POST['da'])) { $not_numeric = 1; $da_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Density Altitude</i></u> must be in a decimal format<br>'; }
	if (!$_POST['eighth_et'] AND !$_POST['fourth_et']) { $field_missing = 1; $et_missing = '&nbsp;&nbsp;&nbsp;&bull; Please enter at least one <u><i>ET</i></u> &amp; <u><i>MPH</i></u><br>'; }
	if ($_POST['eighth_et'] AND !$_POST['eighth_mph']) { $field_missing = 1; $eet_missing = '&nbsp;&nbsp;&nbsp;&bull; Please enter the <u><i>MPH</i></u> for the 8th mile<br>'; }
	if ($_POST['fourth_et'] AND !$_POST['fourth_mph']) { $field_missing = 1; $fet_missing = '&nbsp;&nbsp;&nbsp;&bull; Please enter the <u><i>MPH</i></u> for the Quarter mile<br>'; }
	if (!$_POST['eighth_et'] AND $_POST['eighth_mph']) { $field_missing = 1; $emph_missing = '&nbsp;&nbsp;&nbsp;&bull; Please enter the <u><i>ET</i></u> for the 8th mile<br>'; }
	if (!$_POST['fourth_et'] AND $_POST['fourth_mph']) { $field_missing = 1; $fmph_missing = '&nbsp;&nbsp;&nbsp;&bull; Please enter the <u><i>ET</i></u> for the Quarter mile<br>'; }

	if (!$field_missing AND !$not_numeric) {
		$vbulletin->input->clean_array_gpc('p', array(
			'date'			=> TYPE_STR,
			'vehicle'		=> TYPE_INT,
			'driver'		=> TYPE_INT,
			'track'			=> TYPE_INT,
			'lane'			=> TYPE_NOHTML,
			'temp'			=> TYPE_NUM,
			'da'			=> TYPE_NUM,
			'dial'			=> TYPE_NUM,
			'rt'			=> TYPE_NUM,
			'sixty'			=> TYPE_NUM,
			'threethirty'	=> TYPE_NUM,
			'eighth_et'		=> TYPE_NUM,
			'eighth_mph'	=> TYPE_NUM,
			'thousand'		=> TYPE_NUM,
			'fourth_et'		=> TYPE_NUM,
			'fourth_mph'	=> TYPE_NUM,
			'win'			=> TYPE_NOHTML,
			'notes'			=> TYPE_NOHTML
		));
		$submit_date = date_format(date_create_from_format('m-d-Y H:i:s', $vbulletin->GPC['date']), 'Y-m-d H:i:s');
		$db->query_write("INSERT INTO " . TABLE_PREFIX . "dso_slips (ID,Car_ID,Owner,Date,Track,Temp,Lane,RT,Dial,Sixty,ThreeThirty,Eighth_ET,Eighth_MPH,Thousand,Fourth_ET,Fourth_MPH,DA,Win,Notes) VALUES ('','" . $db->escape_string($vbulletin->GPC['vehicle']) . "','" . $db->escape_string($vbulletin->GPC['driver']) . "','" . $submit_date . "','" . $db->escape_string($vbulletin->GPC['track']) . "','" . $db->escape_string($vbulletin->GPC['temp']) . "','" . $db->escape_string($vbulletin->GPC['lane']) . "','" . $db->escape_string($vbulletin->GPC['rt']) . "','" . $db->escape_string($vbulletin->GPC['dial']) . "','" . $db->escape_string($vbulletin->GPC['sixty']) . "','" . $db->escape_string($vbulletin->GPC['threethirty']) . "','" . $db->escape_string($vbulletin->GPC['eighth_et']) . "','" . $db->escape_string($vbulletin->GPC['eighth_mph']) . "','" . $db->escape_string($vbulletin->GPC['thousand']) . "','" . $db->escape_string($vbulletin->GPC['fourth_et']) . "','" . $db->escape_string($vbulletin->GPC['fourth_mph']) . "','" . $db->escape_string($vbulletin->GPC['da']) . "','" . $db->escape_string($vbulletin->GPC['win']) . "','" . $db->escape_string($vbulletin->GPC['notes']) . "')");
		$new_slip_id = $db->insert_id(); 
		$slip_or_track = "slip";
		$templater = vB_Template::create('dso_slips_added');
		$templater->register('new_slip_id', $new_slip_id);
	} else {
		if ($field_missing) { 
			$field_required = $nodate.$novehicle.$notrack.$nolane.$et_missing.$eet_missing.$emph_missing.$fet_missing.$fmph_missing;
		}
		if ($not_numeric) { 
			$numeric_required = $temp_not_numeric.$rt_not_numeric.$dial_not_numeric.$sixty_not_numeric.$threethirty_not_numeric.$eighth_et_not_numeric.$eighth_mph_not_numeric.$thousand_not_numeric.$fourth_et_not_numeric.$fourth_mph_not_numeric.$da_not_numeric;
		}
		$templater = vB_Template::create('dso_slips_insert_missing');
		$templater->register('field_required', $field_required);
		$templater->register('numeric_required', $numeric_required);
	}
}

// #######################################################################
// #                           EDIT A TIMESLIP                           #
// #######################################################################
if ($_REQUEST['do'] == 'editslip') {
	if ($show['dso_slips_edit']) {
		$vbulletin->input->clean_array_gpc('g', array(
			'id' => TYPE_INT
		));
		$slipsqueue = $db->query_read("SELECT ID,Car_ID,Owner,Date,Track,Temp,Lane,RT,Dial,Sixty,ThreeThirty,Eighth_ET,Eighth_MPH,Thousand,Fourth_ET,Fourth_MPH,DA,Win,Notes FROM " . TABLE_PREFIX . "dso_slips WHERE ID = " . $vbulletin->GPC['id'] . " LIMIT 1");
		while ($row = $db->fetch_array($slipsqueue)) {
			if ($row['Lane'] == 'Right') {$right_selected = ' selected';} else {$right_selected = '';}
			if ($row['Lane'] == 'Left') {$left_selected = ' selected';} else {$left_selected = '';}
			if ($row['Win'] == '') {$na_selected = ' selected';} else {$na_selected = '';}
			if ($row['Win'] == 'Win') {$win_selected = ' selected';} else {$win_selected = '';}
			if ($row['Win'] == 'Loss') {$loss_selected = ' selected';} else {$loss_selected = '';}
			if ($row['Win'] == 'Solo') {$solo_selected = ' selected';} else {$solo_selected = '';}
			$driver = get_driver_name($row['Owner']);
			$queue[] = array (
				'id'		=> $row['ID'],
				'car_id'	=> $row['Car_ID'],
				'driver_id'	=> $row['Owner'],
				'driver'	=> get_driver_name($row['Owner']),
				'date'		=> date_format(date_create_from_format('Y-m-d H:i:s', $row['Date']), 'm-d-Y H:i:s'),
				'track'		=> $row['Track'],
				'temp'		=> $row['Temp'],
				'lane'		=> $row['Lane'],
				'rt'		=> $row['RT'],
				'dialin'	=> $row['Dial'],
				'60ft'		=> $row['Sixty'],
				'330ft'		=> $row['ThreeThirty'],
				'8et'		=> $row['Eighth_ET'],
				'8mph'		=> $row['Eighth_MPH'],
				'1000ft'	=> $row['Thousand'],
				'4et'		=> $row['Fourth_ET'],
				'4mph'		=> $row['Fourth_MPH'],
				'da'		=> $row['DA'],
				'win'		=> $row['Win'],
				'notes'		=> $row['Notes']
				);
			$vehicle_list = $db->query_read("SELECT id,year,model FROM " . TABLE_PREFIX . "vbrides_rides WHERE userid = " . $row['Owner'] . "");
			while ($rowv = $db->fetch_array($vehicle_list)) {
				if ($rowv['id'] == $row['Car_ID']) {$rowv['vehicle_selected'] = ' selected';} else {$rowv['vehicle_selected'] = '';}
				$vlist[] = array (
					'id'		=> $rowv['id'],
					'year'		=> $rowv['year'],
					'model'		=> $rowv['model'],
					'selected'	=> $rowv['vehicle_selected']
				);
			}
			$track_list = $db->query_read("SELECT ID,Name FROM " . TABLE_PREFIX . "dso_tracks");
			while ($rowt = $db->fetch_array($track_list)) {
				if ($rowt['ID'] == $row['Track']) {$rowt['track_selected'] = ' selected';} else {$rowt['track_selected'] = '';}
				$tlist[] = array (
					'id'		=> $rowt['ID'],
					'name'		=> $rowt['Name'],
					'selected'	=> $rowt['track_selected']
				);
			}
		} 
	} else { 
		eval(standard_error(fetch_error('no_permission')));
	}
	$templater = vB_Template::create('dso_slips_edit_slip');
	$templater->register('vlist', $vlist);
	$templater->register('tlist', $tlist);
	$templater->register('drivername', $driver);
	$templater->register('left_selected', $left_selected);
	$templater->register('right_selected', $right_selected);
	$templater->register('vehicle_selected', $vehicle_selected);
	$templater->register('track_selected', $track_selected);
	$templater->register('na_selected', $na_selected);
	$templater->register('win_selected', $win_selected);
	$templater->register('loss_selected', $loss_selected);
	$templater->register('solo_selected', $solo_selected);
}

// #######################################################################
// #                          EDIT OWN TIMESLIP                          #
// #######################################################################
if ($_REQUEST['do'] == 'editownslip') {
	if ($show['dso_slips_edit']) {
		$vbulletin->input->clean_array_gpc('g', array(
			'id' => TYPE_INT
		));
		$slipsqueue = $db->query_read("SELECT ID,Car_ID,Owner,Date,Track,Temp,Lane,RT,Dial,Sixty,ThreeThirty,Eighth_ET,Eighth_MPH,Thousand,Fourth_ET,Fourth_MPH,DA,Win,Notes FROM " . TABLE_PREFIX . "dso_slips WHERE ID = " . $vbulletin->GPC['id'] . " LIMIT 1");
		while ($row = $db->fetch_array($slipsqueue)) {
			if ($vbulletin->userinfo['userid'] != $row['Owner']) { eval(standard_error(fetch_error('no_permission'))); }
			if ($row['Lane'] == 'Right') {$right_selected = ' selected';} else {$right_selected = '';}
			if ($row['Lane'] == 'Left') {$left_selected = ' selected';} else {$left_selected = '';}
			if ($row['Win'] == '') {$na_selected = ' selected';} else {$na_selected = '';}
			if ($row['Win'] == 'Win') {$win_selected = ' selected';} else {$win_selected = '';}
			if ($row['Win'] == 'Loss') {$loss_selected = ' selected';} else {$loss_selected = '';}
			if ($row['Win'] == 'Solo') {$solo_selected = ' selected';} else {$solo_selected = '';}
			$queue[] = array (
				'id'		=> $row['ID'],
				'car_id'	=> $row['Car_ID'],
				'driver_id'	=> $row['Owner'],
				'date'		=> date_format(date_create_from_format('Y-m-d H:i:s', $row['Date']), 'm-d-Y H:i:s'),
				'track'		=> $row['Track'],
				'temp'		=> $row['Temp'],
				'lane'		=> $row['Lane'],
				'rt'		=> $row['RT'],
				'dialin'	=> $row['Dial'],
				'60ft'		=> $row['Sixty'],
				'330ft'		=> $row['ThreeThirty'],
				'8et'		=> $row['Eighth_ET'],
				'8mph'		=> $row['Eighth_MPH'],
				'1000ft'	=> $row['Thousand'],
				'4et'		=> $row['Fourth_ET'],
				'4mph'		=> $row['Fourth_MPH'],
				'da'		=> $row['DA'],
				'win'		=> $row['Win'],
				'notes'		=> $row['Notes']
				);
			$vehicle_list = $db->query_read("SELECT id,year,model FROM " . TABLE_PREFIX . "vbrides_rides WHERE userid = " . $vbulletin->userinfo['userid'] . "");
			while ($rowv = $db->fetch_array($vehicle_list)) {
				if ($rowv['id'] == $row['Car_ID']) {$rowv['vehicle_selected'] = ' selected';} else {$rowv['vehicle_selected'] = '';}
				$vlist[] = array (
					'id'		=> $rowv['id'],
					'year'		=> $rowv['year'],
					'model'		=> $rowv['model'],
					'selected'	=> $rowv['vehicle_selected']
				);
			}
			$track_list = $db->query_read("SELECT ID,Name FROM " . TABLE_PREFIX . "dso_tracks");
			while ($rowt = $db->fetch_array($track_list)) {
				if ($rowt['ID'] == $row['Track']) {$rowt['track_selected'] = ' selected';} else {$rowt['track_selected'] = '';}
				$tlist[] = array (
					'id'		=> $rowt['ID'],
					'name'		=> $rowt['Name'],
					'selected'	=> $rowt['track_selected']
				);
			}
		} 
	} else { 
		eval(standard_error(fetch_error('no_permission')));
	}
	$templater = vB_Template::create('dso_slips_edit_slip');
	$templater->register('vlist', $vlist);
	$templater->register('tlist', $tlist);
	$templater->register('left_selected', $left_selected);
	$templater->register('right_selected', $right_selected);
	$templater->register('vehicle_selected', $vehicle_selected);
	$templater->register('track_selected', $track_selected);
	$templater->register('na_selected', $na_selected);
	$templater->register('win_selected', $win_selected);
	$templater->register('loss_selected', $loss_selected);
	$templater->register('solo_selected', $solo_selected);
}

// #######################################################################
// #                    PERFORM DB UPDATE FOR TIMESLIP                   #
// #######################################################################
if ($_POST['do'] == "updateslip") {
	if ($_POST['temp'] AND !is_numeric($_POST['temp'])) { $not_numeric = 1; $temp_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Temperature</i></u> must be in a decimal format<br>'; }
	if ($_POST['rt'] AND !is_numeric($_POST['rt'])) { $not_numeric = 1; $rt_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Reation Time</i></u> must be in a decimal format<br>'; }
	if ($_POST['dial'] AND !is_numeric($_POST['dial'])) { $not_numeric = 1; $dial_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Dial-In</i></u> must be in a decimal format<br>'; }
	if ($_POST['sixty'] AND !is_numeric($_POST['sixty'])) { $not_numeric = 1; $sixty_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>60ft</i></u> must be in a decimal format<br>'; }
	if ($_POST['threethirty'] AND !is_numeric($_POST['threethirty'])) { $not_numeric = 1; $threethirty_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>330ft</i></u> must be in a decimal format<br>'; }
	if ($_POST['eighth_et'] AND !is_numeric($_POST['eighth_et'])) { $not_numeric = 1; $eighth_et_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>8th Mile ET</i></u> must be in a decimal format<br>'; }
	if ($_POST['eighth_mph'] AND !is_numeric($_POST['eighth_mph'])) { $not_numeric = 1; $eighth_mph_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>8th Mile MPH</i></u> must be in a decimal format<br>'; }
	if ($_POST['thousand'] AND !is_numeric($_POST['thousand'])) { $not_numeric = 1; $thousand_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>1000ft</i></u> must be in a decimal format<br>'; }
	if ($_POST['fourth_et'] AND !is_numeric($_POST['fourth_et'])) { $not_numeric = 1; $fourth_et_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Quater Mile ET</i></u> must be in a decimal format<br>'; }
	if ($_POST['fourth_mph'] AND !is_numeric($_POST['fourth_mph'])) { $not_numeric = 1; $fourth_mph_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Quater Mile MPH</i></u> must be in a decimal format<br>'; }
	if ($_POST['da'] AND !is_numeric($_POST['da'])) { $not_numeric = 1; $da_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Density Altitude</i></u> must be in a decimal format<br>'; }
	if (!$_POST['eighth_et'] AND !$_POST['fourth_et']) { $field_missing = 1; $et_missing = '&nbsp;&nbsp;&nbsp;&bull; Please enter at least one <u><i>ET</i></u> &amp; <u><i>MPH</i></u><br>'; }
	if ($_POST['eighth_et'] AND !$_POST['eighth_mph']) { $field_missing = 1; $eet_missing = '&nbsp;&nbsp;&nbsp;&bull; Please enter the <u><i>MPH</i></u> for the 8th mile<br>'; }
	if ($_POST['fourth_et'] AND !$_POST['fourth_mph']) { $field_missing = 1; $fet_missing = '&nbsp;&nbsp;&nbsp;&bull; Please enter the <u><i>MPH</i></u> for the Quarter mile<br>'; }
	if (!$_POST['eighth_et'] AND $_POST['eighth_mph']) { $field_missing = 1; $emph_missing = '&nbsp;&nbsp;&nbsp;&bull; Please enter the <u><i>ET</i></u> for the 8th mile<br>'; }
	if (!$_POST['fourth_et'] AND $_POST['fourth_mph']) { $field_missing = 1; $fmph_missing = '&nbsp;&nbsp;&nbsp;&bull; Please enter the <u><i>ET</i></u> for the Quarter mile<br>'; }
	if ($field_missing == 0 AND $not_numeric == 0) {
		$vbulletin->input->clean_array_gpc('p', array(
			'id'			=> TYPE_INT,
			'date'			=> TYPE_STR,
			'vehicle'		=> TYPE_INT,
			'driver'		=> TYPE_INT,
			'track'			=> TYPE_INT,
			'lane'			=> TYPE_NOHTML,
			'temp'			=> TYPE_NUM,
			'da'			=> TYPE_NUM,
			'dial'			=> TYPE_NUM,
			'rt'			=> TYPE_NUM,
			'sixty'			=> TYPE_NUM,
			'threethirty'	=> TYPE_NUM,
			'eighth_et'		=> TYPE_NUM,
			'eighth_mph'	=> TYPE_NUM,
			'thousand'		=> TYPE_NUM,
			'fourth_et'		=> TYPE_NUM,
			'fourth_mph'	=> TYPE_NUM,
			'win'			=> TYPE_NOHTML,
			'notes'			=> TYPE_NOHTML
		));
		$update_date = date_format(date_create_from_format('m-d-Y H:i:s', $vbulletin->GPC['date']), 'Y-m-d H:i:s');
		$db->query_write("UPDATE " . TABLE_PREFIX . "dso_slips SET Car_ID='" . $vbulletin->GPC['vehicle'] . "',Date='" . $update_date . "',Track='" . $vbulletin->GPC['track'] . "',Temp='" . $vbulletin->GPC['temp'] . "',Lane='" . $vbulletin->GPC['lane'] . "',RT='" . $vbulletin->GPC['rt'] . "',Dial='" . $vbulletin->GPC['dial'] . "',Sixty='" . $vbulletin->GPC['sixty'] . "',ThreeThirty='" . $vbulletin->GPC['threethirty'] . "',Eighth_ET='" . $vbulletin->GPC['eighth_et'] . "',Eighth_MPH='" . $vbulletin->GPC['eighth_mph'] . "',Thousand='" . $vbulletin->GPC['thousand'] . "',Fourth_ET='" . $vbulletin->GPC['fourth_et'] . "',Fourth_MPH='" . $vbulletin->GPC['fourth_mph'] . "',DA='" . $vbulletin->GPC['da'] . "',Win='" . $vbulletin->GPC['win'] . "',Notes='" . $vbulletin->GPC['notes'] . "' WHERE ID = " . $vbulletin->GPC['id'] . "");
		$view_slip = 'slips.php?do=viewslip&id='.$vbulletin->GPC['id'].'';
		$slip_or_track = "slip";
		$templater = vB_Template::create('dso_slips_updated');
		$templater->register('view_slip', $view_slip);
	} else {
		if ($field_missing == 1) { 
			$field_required = $nodate.$novehicle.$notrack.$nolane.$et_missing.$eet_missing.$emph_missing.$fet_missing.$fmph_missing;
		}
		if ($not_numeric == 1) { 
			$numeric_required = $temp_not_numeric.$rt_not_numeric.$dial_not_numeric.$sixty_not_numeric.$threethirty_not_numeric.$eighth_et_not_numeric.$eighth_mph_not_numeric.$thousand_not_numeric.$fourth_et_not_numeric.$fourth_mph_not_numeric.$da_not_numeric;
		}
		$templater = vB_Template::create('dso_slips_insert_missing');
		$templater->register('field_required', $field_required);
		$templater->register('numeric_required', $numeric_required);
	}
}

// #######################################################################
// #                   DELETE CONFIRMATION FOR TIMESLIP                  #
// #######################################################################
if ($_REQUEST['do'] == 'deleteslip') {
	if ($show['dso_slips_delete']) {
		$templater = vB_Template::create('dso_slips_delete_slip');
	} else {
		eval(standard_error(fetch_error('no_permission')));
	}
}

// #######################################################################
// #                 DELETE CONFIRMATION FOR OWN TIMESLIP                #
// #######################################################################
if ($_REQUEST['do'] == 'deleteownslip') {
	if ($show['dso_slips_delete_own']) {
		$templater = vB_Template::create('dso_slips_delete_slip');
	} else {
		eval(standard_error(fetch_error('no_permission')));
	}
}

// #######################################################################
// #                       REMOVE TIMESLIP FROM DB                       #
// #######################################################################
if ($_POST['do'] == 'killslip') {
	if ($show['dso_slips_delete'] OR $show['dso_slips_delete_own']) {
		$vbulletin->input->clean_array_gpc('p', array(
			'id' => TYPE_INT
		));
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "dso_slips WHERE ID = " . $vbulletin->GPC['id'] . "");
		$slip_or_track = "slip";
		$templater = vB_Template::create('dso_slips_deleted');
	} else {
		eval(standard_error(fetch_error('no_permission')));
	}
}

// #######################################################################
// #                             LIST TRACKS                             #
// #######################################################################
if ($_REQUEST['do'] == 'listtracks') {
	if ($show['dso_tracks_view']) {
		if ($show['dso_tracks_can_moderate']) {
			$tracksmodqueue = $db->query_read("SELECT ID FROM " . TABLE_PREFIX . "dso_tracks WHERE Moderate = 1");
			$tracksmodcount = $db->num_rows($tracksmodqueue);
		}
		$tracksqueue = $db->query_read("SELECT ID,Name,Website,Type,Size FROM " . TABLE_PREFIX . "dso_tracks WHERE Moderate != 1");
		while ($row = $db->fetch_array($tracksqueue)) {
			if ($row['Website']) { $track_website = explode(',', $row['Website']); }
			if ($track_website['1']) { $track_website['1'] = '&nbsp;&nbsp;&nbsp;<a href="'.$track_website['1'].'" title="Visit the '.$row['Name'].' Facebook Page" target="_blank"><i class="fa fa-facebook-square"></i></a>'; }
			if ($track_website['0']) { $track_website['0'] = '<a href="'.$track_website['0'].'" title="Visit the '.$row['Name'].' Website" target="_blank"><i class="fa fa-home"></i></a>'; }
			if ($row['Elevation'] == 0) { $row['Elevation'] = ''; }
			$queue[] = array (
				'id'			=> $row['ID'],
				'name'			=> $row['Name'],
				'www'			=> $track_website['0'],
				'fb'			=> $track_website['1'],
				'type'			=> $row['Type'],
				'size'			=> $row['Size']
			);
		}
		$templater = vB_Template::create('dso_slips_list_tracks');
		$templater->register('tracksmodcount', $tracksmodcount);
	} else {
		eval(standard_error(fetch_error('no_permission')));
	}
}

// #######################################################################
// #                        VIEW A TRACK'S DETAILS                       #
// #######################################################################
if ($_REQUEST['do'] == 'viewtrack') {
	if ($show['dso_tracks_view']) {
		$vbulletin->input->clean_array_gpc('g', array(
			'id' => TYPE_INT
		));
		$slipsqueue = $db->query_read("SELECT ID,Name,Website,Location,Coords,Elevation,Type,Size,Notes FROM " . TABLE_PREFIX . "dso_tracks WHERE Moderate != 1 AND ID = " . $vbulletin->GPC['id'] . " LIMIT 1");
		while ($row = $db->fetch_array($slipsqueue)) {
			if ($row['Coords']) { $row['Track_Link'] = "<a href=\"https://www.google.com/maps/place/" . $row['Coords'] . "\" title=\"Google Map\" target=\"_blank\">" . $row['Location'] . "</a>"; } else { $row['Track_Link'] = $row['Location']; }
			if ($row['Website']) { $track_website = explode(',', $row['Website']); }
			if ($track_website['1']) { $track_website['1'] = '<a href="'.$track_website['1'].'" title="Visit the '.$row['Name'].' Facebook Page" target="_blank"><i class="fa fa-facebook-square"></i></a>'; }
			if ($track_website['0']) { $track_website['0'] = '<a href="'.$track_website['0'].'" title="Visit the '.$row['Name'].' Website" target="_blank"><i class="fa fa-home"></i></a>'; }
			if ($row['Elevation'] == 0) { $row['Elevation'] = ''; }
			$queue[] = array (
				'id'			=> $row['ID'],
				'name'			=> $row['Name'],
				'www'			=> $track_website['0'],
				'fb'			=> $track_website['1'],
				'city'			=> $row['Location'],
				'coords'		=> $row['Coords'],
				'track_link'	=> $row['Track_Link'],
				'ele'			=> $row['Elevation'],
				'type'			=> $row['Type'],
				'size'			=> $row['Size'],
				'notes'			=> $row['Notes']
			);
		}
	} else { 
		eval(standard_error(fetch_error('no_permission')));
	}
	$templater = vB_Template::create('dso_slips_view_track');
}

// #######################################################################
// #                             ADD A TRACK                             #
// #######################################################################
if ($_REQUEST['do'] == 'addtrack') {
	if ($show['dso_tracks_add']) {
		$templater = vB_Template::create('dso_slips_add_track');
	} else {
		eval(standard_error(fetch_error('no_permission')));
	}
}

// #######################################################################
// #                      PERFORM DB INSERT FOR TRACK                    #
// #######################################################################
if ($_POST['do'] == 'inserttrack') {
	if ($_POST['elevation'] AND !is_numeric($_POST['elevation'])) { $not_numeric = 1; $elevation_not_numeric = '&nbsp;&nbsp;&nbsp;&bull; <u><i>Elevation</i></u> must be in a decimal format<br>'; }
	if ($show['dso_tracks_moderate']) { $moderate = 1; }
	if (!$field_missing AND !$not_numeric) {
		$vbulletin->input->clean_array_gpc('p', array(
			'name'		=> TYPE_NOHTML,
			'www'		=> TYPE_NOHTML,
			'fb'		=> TYPE_NOHTML,
			'address'	=> TYPE_NOHTML,
			'coords'	=> TYPE_NOHTML,
			'elevation'	=> TYPE_INT,
			'type'		=> TYPE_NOHTML,
			'size'		=> TYPE_NOHTML,
			'notes'		=> TYPE_NOHTML
		));
		$db->query_write("INSERT INTO " . TABLE_PREFIX . "dso_tracks (ID,Name,Website,Location,Coords,Elevation,Type,Size,Notes,Moderate) VALUES ('','" . $db->escape_string($vbulletin->GPC['name']) . "','" . $db->escape_string($vbulletin->GPC['www']) . "," . $db->escape_string($vbulletin->GPC['fb']) . "','" . $db->escape_string($vbulletin->GPC['address']) . "','" . $db->escape_string($vbulletin->GPC['coords']) . "','" . $vbulletin->GPC['elevation'] . "','" . $vbulletin->GPC['type'] . "','" . $vbulletin->GPC['size'] . "','" . $db->escape_string($vbulletin->GPC['notes']) . "','" . $moderate . "')");
		$slip_or_track = "track";
		$templater = vB_Template::create('dso_slips_added');
	} else {
		if ($not_numeric) { 
			$numeric_required = $elevation_not_numeric;
		}
		$templater = vB_Template::create('dso_slips_insert_missing');
		$templater->register('field_required', $field_required);
		$templater->register('numeric_required', $numeric_required);
	} 
}

// #######################################################################
// #                             EDIT TRACK                              #
// #######################################################################
if ($_REQUEST['do'] == 'edittrack') {
	if ($show['dso_tracks_edit']) {
		$slip_or_track = "track";
		$templater = vB_Template::create('dso_slips_edit_track');
	} else {
		eval(standard_error(fetch_error('no_permission')));
	}
}

// #######################################################################
// #                           MODERATE TRACK                            #
// #######################################################################
if ($_REQUEST['do'] == 'moderatetrack') {
	if ($show['dso_tracks_can_moderate']) {
		$templater = vB_Template::create('dso_slips_mod_track');
	} else {
		eval(standard_error(fetch_error('no_permission')));
	}
}

// #######################################################################
// #                      PERFORM DB UPDATE FOR TRACK                    #
// #######################################################################
if ($_POST['do'] == 'updatetrack') {
	$slip_or_track = "track";
	$templater = vB_Template::create('dso_slips_updated');
}

// #######################################################################
// #                    DELETE CONFIRMATION FOR TRACK                    #
// #######################################################################
if ($_REQUEST['do'] == 'deletetrack') {
	if ($show['dso_tracks_delete']) {
		$templater = vB_Template::create('dso_slips_delete_track');
	} else {
		eval(standard_error(fetch_error('no_permission')));
	}
}

// #######################################################################
// #                         REMOVE TRACK FROM DB                        #
// #######################################################################
if ($_POST['do'] == 'killtrack') {
	if ($show['dso_tracks_delete']) {
		$vbulletin->input->clean_array_gpc('p', array(
			'id' => TYPE_INT
		));
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "dso_tracks WHERE ID = " . $vbulletin->GPC['id'] . "");
		$slip_or_track = "track";
		$templater = vB_Template::create('dso_slips_deleted');
		$templater->register('slip_or_track', $slip_or_track);
	} else {
		eval(standard_error(fetch_error('no_permission')));
	}
}

// #######################################################################
// #                          DISPLAY MAIN PAGE                          #
// #######################################################################
if ($_REQUEST['do'] == 'main') {
	if ($show['dso_slips_view']) {
		$vbulletin->input->clean_array_gpc('r', array(
			'perpage'    => TYPE_UINT,
			'pagenumber' => TYPE_UINT,
		));
		if ($_GET['v']) {
			$vbulletin->input->clean_array_gpc('g', array(
				'v' => TYPE_INT
			));
			$slips_count = $db->query_first("SELECT COUNT('ID') AS slip_count FROM " . TABLE_PREFIX . "dso_slips WHERE Car_ID = " . $vbulletin->GPC['v'] . "");
			sanitize_pageresults($slips_count['slip_count'], $pagenumber, $perpage, 100, $vbulletin->options['dso_slips_perpage_slips']);
			if ($vbulletin->GPC['pagenumber'] < 1)
			{
				$vbulletin->GPC['pagenumber'] = 1;
			}
			else if ($vbulletin->GPC['pagenumber'] > ceil(($slips_count['slip_count'] + 1) / $perpage))
			{
				$vbulletin->GPC['pagenumber'] = ceil(($slips_count['slip_count'] + 1) / $perpage);
			}
			$limitlower = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;
			$limitupper = ($vbulletin->GPC['pagenumber']) * $perpage; 

			$slipsqueue = $db->query_read("SELECT ID,Car_ID,Owner,Date,Track,Sixty,Eighth_ET,Eighth_MPH,Fourth_ET,Fourth_MPH FROM " . TABLE_PREFIX . "dso_slips WHERE Car_ID = " . $vbulletin->GPC['v'] . " ORDER BY Date DESC LIMIT $limitlower, $perpage");
			if($db->num_rows($slipsqueue)==0){
				eval(standard_error(fetch_error('slipsqueue_noslips')));
			} else {
				while ($row = $db->fetch_array($slipsqueue)) {
					if (!empty($row['Eighth_ET']) AND !empty($row['Eighth_MPH'])) { $row['Eighth_ET'] = number_format($row['Eighth_ET'],3,'.',',') . " @ " . number_format($row['Eighth_MPH'],2,'.',',') . " mph"; }
					if (!empty($row['Fourth_ET']) AND !empty($row['Fourth_MPH'])) { $row['Fourth_ET'] = number_format($row['Fourth_ET'],3,'.',',') . " @ " . number_format($row['Fourth_MPH'],2,'.',',') . " mph"; }
					if (!empty($row['Sixty'])) { $row['Sixty'] = number_format($row['Sixty'],3,'.',',');} else { $row['Sixty'] = ''; }
					$queue[] = array (
						'id'		=> $row['ID'],
						'car_id'	=> $row['Car_ID'],
						'car_name'	=> get_vehicle_name($row['Car_ID']),
						'driver_id'	=> $row['Owner'],
						'driver'	=> get_driver_name($row['Owner']),
						'date'		=> date("M d, Y",strtotime($row['Date'])),
						'trackid'	=> $row['Track'],
						'track'		=> get_track_name($row['Track']),
						'60ft'		=> $row['Sixty'],
						'8et'		=> $row['Eighth_ET'],
						'4et'		=> $row['Fourth_ET']
						);
					}
				$pagenav = construct_page_nav(
					$vbulletin->GPC['pagenumber'],
					$perpage,
					$slips_count['slip_count'],
					'slips.php?v='.$vbulletin->GPC['v'].'' . $vbulletin->session->vars['sessionurl']
				); 
			}
		} else {
			$slips_count = $db->query_first("SELECT COUNT('ID') AS slip_count FROM " . TABLE_PREFIX . "dso_slips");
			sanitize_pageresults($slips_count['slip_count'], $pagenumber, $perpage, 100, $vbulletin->options['dso_slips_perpage_slips']);
			if ($vbulletin->GPC['pagenumber'] < 1)
			{
				$vbulletin->GPC['pagenumber'] = 1;
			}
			else if ($vbulletin->GPC['pagenumber'] > ceil(($slips_count['slip_count'] + 1) / $perpage))
			{
				$vbulletin->GPC['pagenumber'] = ceil(($slips_count['slip_count'] + 1) / $perpage);
			}
			$limitlower = ($vbulletin->GPC['pagenumber'] - 1) * $perpage;
			$limitupper = ($vbulletin->GPC['pagenumber']) * $perpage; 
			$slipsqueue = $db->query_read("SELECT ID,Car_ID,Owner,Date,Track,Sixty,Eighth_ET,Eighth_MPH,Fourth_ET,Fourth_MPH FROM " . TABLE_PREFIX . "dso_slips ORDER BY Date DESC LIMIT $limitlower, $perpage");
			if($db->num_rows($slipsqueue)==0){
				eval(standard_error(fetch_error('slipsqueue_noslips')));
			} else {
				while ($row = $db->fetch_array($slipsqueue)) {
					if (!empty($row['Eighth_ET']) AND !empty($row['Eighth_MPH'])) { $row['Eighth_ET'] = number_format($row['Eighth_ET'],3,'.',',') . " @ " . number_format($row['Eighth_MPH'],2,'.',',') . " mph"; }
					if (!empty($row['Fourth_ET']) AND !empty($row['Fourth_MPH'])) { $row['Fourth_ET'] = number_format($row['Fourth_ET'],3,'.',',') . " @ " . number_format($row['Fourth_MPH'],2,'.',',') . " mph"; }
					if (!empty($row['Sixty'])) { $row['Sixty'] = number_format($row['Sixty'],3,'.',',');} else { $row['Sixty'] = ''; }
					$queue[] = array (
						'id'		=> $row['ID'],
						'car_id'	=> $row['Car_ID'],
						'car_name'	=> get_vehicle_name($row['Car_ID']),
						'driver_id'	=> $row['Owner'],
						'driver'	=> get_driver_name($row['Owner']),
						'date'		=> date("M d, Y",strtotime($row['Date'])),
						'trackid'	=> $row['Track'],
						'track'		=> get_track_name($row['Track']),
						'60ft'		=> $row['Sixty'],
						'8et'		=> $row['Eighth_ET'],
						'4et'		=> $row['Fourth_ET']
						);
				}
				$pagenav = construct_page_nav(
					$vbulletin->GPC['pagenumber'],
					$perpage,
					$slips_count['slip_count'],
					'slips.php?' . $vbulletin->session->vars['sessionurl']
				);
			}
		}
	} else { 
		eval(standard_error(fetch_error('no_permission')));
	}
	$templater = vB_Template::create('dso_slips_list_slips');
}
//	$dso_footer = "<vb:if condition=\"$vboptions['dso_slips_copyright']\"><br /><br /><div id=\"dso_copyright\" align=\"center\">DSO Timeslips &copy; <script type=\"text/javascript\">d = new Date();y = d.getFullYear();document.write(y);</script> <a href=\"http://www.dragonsys.org\">Dragonsys.org</a></div></vb:if>";

	$templater->register('slip_or_track', $slip_or_track);
	$templater->register('queue', $queue);
	$templater->register('pagenav', $pagenav);
    $templater->register('pagenumber', $pagenumber);
    $templater->register('perpage', $perpage);
    $templater->register('output', $output);
	$page_templater .= $templater->render();

// #######################################################################
if (!empty($page_templater))
{
	// make navbar
	$navbits = construct_navbits(array('' => $vbphrase['dso_slips_nav']));
	$navbar = render_navbar_template($navbits);

    $pagetitle = empty($custompagetitle) ? $pagetitle : $custompagetitle;

	if (!$vbulletin->options['storecssasfile'])
	{
		$includecss = implode(',', $includecss);
	}

	// shell template
	$templater = vB_Template::create('GENERIC_SHELL');
		$templater->register_page_templates();
        $templater->register('includecss', $includecss);
		$templater->register('headinclude', $headinclude);
		$templater->register('headinclude_bottom', $headinclude_bottom);
		$templater->register('HTML', $page_templater);
		$templater->register('navbar', $navbar);
		$templater->register('navclass', $navclass);
		$templater->register('onload', $onload);
		$templater->register('pagetitle', $pagetitle);
		$templater->register('template_hook', $template_hook);
		$templater->register('clientscripts', $clientscripts);
	print_output($templater->render());
}
?>
