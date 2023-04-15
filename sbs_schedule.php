<?php

/*

Plugin Name: SBS Schedule

Plugin URI: http://www.step-byte-step.com/

Description: Weekly Schedule / Woechentlicher Plan fuer die Kurse; Dieses Plugin benoetigt SBS Credits.

Version: 0.9

Author: Step-Byte-Step UG

Author URI: http://www.step-byte-step.com/

*/

//Hooks

add_action('admin_menu', array('sbs_schedule', 'add_option_pages'));

add_action('init', array('sbs_schedule', 'init'));

register_activation_hook(__FILE__,array('sbs_schedule', 'create_tables'));



class sbs_schedule {

	function init() {

		if(isset($_REQUEST['sbs_schedule_cron']) && $_REQUEST['sbs_schedule_cron']=='true') { 

			sbs_schedule::execute_cron(); 

			die();

		}		

		if(!$frontend) $_SESSION['kursefrei']=true;

		wp_enqueue_script('jquery');

		if(isset($_POST['sbs_schedule_new']) && is_array($_POST['sbs_schedule_new'])) {

			if (!current_user_can('manage_options')) {

				wp_die(__('You do not have sufficient permissions to access this page.'));

			}

			sbs_schedule::add_event($_POST['sbs_schedule_new']);

		}

		if(isset($_POST['sbs_schedule_delete_item']) && is_numeric($_POST['sbs_schedule_delete_item'])) {

			if (!current_user_can('manage_options')) {

				wp_die(__('You do not have sufficient permissions to access this page.'));

			}

			sbs_schedule::remove_event($_POST['sbs_schedule_delete_item']);

		}
		
		if(isset($_POST['sbs_schedule_delete_week']) && isset($_POST['week']) && isset($_POST['year']) ) {

			if (!current_user_can('manage_options')) {

				wp_die(__('You do not have sufficient permissions to access this page.'));

			}

			sbs_schedule::delete_week($_POST['week'],$_POST['year']);

		}
		

		if(isset($_POST['sbs_schedule_add_test']) && is_numeric($_POST['sbs_schedule_add_test'])) {

			if (!current_user_can('manage_options')) {

				wp_die(__('You do not have sufficient permissions to access this page.'));

			}
			sbs_schedule::book($_POST['sbs_schedule_add_test'], true);
			//sbs_schedule::remove_event($_POST['sbs_schedule_delete_item']);

		}

		if(isset($_POST['sbs_schedule_remove_test']) && is_numeric($_POST['sbs_schedule_remove_test'])) {

			if (!current_user_can('manage_options')) {

				wp_die(__('You do not have sufficient permissions to access this page.'));

			}
			sbs_schedule::unbooktest($_POST['sbs_schedule_remove_test']);
			//sbs_schedule::remove_event($_POST['sbs_schedule_delete_item']);

		}

		if(isset($_POST['sbs_schedule_copy_from_previous_week']) && is_numeric($_POST['sbs_schedule_copy_from_previous_week']) && isset($_POST['sbs_schedule_copy_from_previous_year']) && is_numeric($_POST['sbs_schedule_copy_from_previous_year'])) {

			if (!current_user_can('manage_options')) {

				wp_die(__('You do not have sufficient permissions to access this page.'));

			}

			sbs_schedule::copy_from_previous_week($_POST['sbs_schedule_copy_from_previous_year'], $_POST['sbs_schedule_copy_from_previous_week']);

		}

		if(isset($_POST['sbs_schedule_book_item']) && is_numeric($_POST['sbs_schedule_book_item'])) {

			$_REQUEST['week']=false;
			$_REQUEST['year']=false;
			sbs_schedule::book($_POST['sbs_schedule_book_item']);

		}
		
		if(isset($_POST['sbs_schedule_warteliste']) && is_numeric($_POST['sbs_schedule_warteliste'])) {

			$_REQUEST['week']=false;
			$_REQUEST['year']=false;
			sbs_schedule::warteliste($_POST['sbs_schedule_warteliste']);

		}
		if(isset($_POST['sbs_schedule_warteliste_delete']) && is_numeric($_POST['sbs_schedule_warteliste_delete'])) {

			$_REQUEST['week']=false;
			$_REQUEST['year']=false;
			sbs_schedule::warteliste_delete($_POST['sbs_schedule_warteliste_delete']);

		}
		if(isset($_POST['sbs_schedule_unbook_item']) && is_numeric($_POST['sbs_schedule_unbook_item'])) {

			sbs_schedule::unbook($_POST['sbs_schedule_unbook_item']);

		}

		if(isset($_REQUEST['sbs_schedule_show_mine']) && is_numeric($_REQUEST['sbs_schedule_show_mine'])) { 

			sbs_schedule::show_my_booking($_REQUEST['sbs_schedule_show_mine']); 

			die();

		}

		

	}


	function show_my_booking($user_id) {

		global $current_user;

		if(!is_user_logged_in()) wp_die(__('You do not have sufficient permissions to access this page.'));

      	get_currentuserinfo();

		if(!is_numeric($user_id)) wp_die(__('Incorrect value for user ID.'));

		//Check permissions

		if (!current_user_can('manage_options')) {

			//Not Adminf

			if($current_user->ID != $user_id) {

				//Not Admin AND not own stats

				wp_die(__('You do not have sufficient permissions to access this page.'));

			}

		}

		$plugin_path = plugin_dir_url(__FILE__);

		require_once(plugin_dir_path(__FILE__) . 'sbs_schedule.mybooking.php');

	}

	

	function execute_cron() {

		global $wpdb;

		date_default_timezone_set('Europe/Berlin');

		$table_name = $wpdb->prefix . "sbs_schedule_events";

		$year = (int)date("Y");

		$week = (int)date("W");

		$day = (int)date("N");
		
		$kw = sbs_schedule::get_start_end_of_week(false, false);

		$minuteofday = ((int)date("H") * 60) + ((int)date("i"));

		//Stundenplan kopieren
		if($day==7 && $minuteofday>=480 && $minuteofday<=540) {
			echo("Stundenplan kopiert!\nTag: {$day}\r\nMinute des Tages: {$minuteofday}\r\n");
			print_r($kw);
			sbs_schedule::copy_from_previous_week($kw['year'], $kw['week']);
		} else {
			echo("Stundenplan wird nicht kopiert!\r\nTag: {$day}\nMinute des Tages: {$minuteofday}\r\n");
			//print_r($kw);
		}
		
		return(true); //KEINE E-MAILS MEHR!

		$ret = $wpdb->get_results($wpdb->prepare( 

			"

				SELECT * 

				FROM $table_name 

				WHERE Year=$year AND Week=$week AND Day=$day AND Day=$day AND StartMinuteOfDay=$minuteofday 

				ORDER BY ID ASC

			",NULL

		), ARRAY_A);

		if(count($ret)==0) echo('KEIN KURS ' . date("d.m.Y H:i:s") . "\r\n");

		foreach($ret as $key=>$item) {

			$ret[$key]['EventData'] = unserialize(base64_decode($ret[$key]['EventData']));

			$memberlist = sbs_schedule::get_bookings($item['ID'], true);

			$str_out = '';

			$cnt=1;

			foreach($memberlist['items'] as $item) {

				$str_out .= $cnt . '. ' . $item['UserInfo']->user_login . "\n";

				$cnt++;

			}

			if(trim($str_out)=='') $str_out='Keine Buchungen';

			wp_mail(sbs_credits::get_meta_value('sbs_schedule_notify_email'), 'Teilnehmerliste ' . $ret[$key]['EventData']['title'], $str_out);

			echo('NACHRICHT GESENDET an ' . sbs_credits::get_meta_value('sbs_schedule_notify_email') . date("d.m.Y H:i:s") . "\r\n");

		}

		return(true);	

	}	

	

	function add_option_pages() {

		//Menu

		add_menu_page('Trainingspläne', 'Trainingspläne', 'manage_options', 'sbs_schedule_menu', array('sbs_schedule', 'show_schedules_page'));

		add_submenu_page('sbs_schedule_menu', 'Trainingspläne', 'Einstellungen', 'manage_options', 'sbs_schedule_schedules', array('sbs_schedule', 'show_options_page'));

	}




	function show_options_page() {

		if (!current_user_can('manage_options')) {

			wp_die(__('You do not have sufficient permissions to access this page.'));

		}	

		require_once(plugin_dir_path(__FILE__) . 'sbs_schedule.options.php');

	}



	function show_schedules_page($frontend=false) {

		if (!current_user_can('manage_options')) {

			//wp_die(__('You do not have sufficient permissions to access this page.'));

			$frontend=true;

		}	

		require_once(plugin_dir_path(__FILE__) . 'sbs_schedule.schedules.php');

	}

	



	function get_start_end_of_week($week=false, $year=false) {

		date_default_timezone_set('Europe/Berlin');

		if($week===false) $week = (int)date("W");

		if($year===false) $year = (int)date("Y");

		$ret['week']=$week;

		$ret['year']=$year;
		
		

		$current_week = (int)date("W");

		$current_time = mktime(0, 0, 0, 1, 1, $year);

		$current_time += 86400*6*($week-1);
		
		//echo(date("W", $current_time));

		while((int)date("W", $current_time)!=$week) $current_time+=(86400*6);

		$weekday = (int)date("w", $current_time);

		$firstdayofweek=1; //TODO: Get this from settings; 0=Sunday; 1=Monday; ...

		$daysago = $weekday-$firstdayofweek;

		while($daysago<0) $daysago+=7;

		$timestamp = $current_time - ($daysago*86400);

		$ret['start'] = mktime(0, 0, 0, (int)date("m", $timestamp), (int)date("d", $timestamp), (int)date("Y", $timestamp));

		$ret['end'] = mktime(23, 59, 59, (int)date("m", $timestamp), ((int)date("d", $timestamp)+6), (int)date("Y", $timestamp));

		$ret['start_formatted'] = date("d.m.Y H:i:s", $ret['start']);

		$ret['end_formatted'] = date("d.m.Y H:i:s", $ret['end']);

		$ret['next_week'] = (int)date("W", $ret['end']+2);

		$ret['next_year'] = (int)date("Y", $ret['end']+2);

		if($ret['next_week']<$ret['week'] && $ret['next_year']==$year) $ret['next_year']++;

		$ret['prev_week'] = (int)date("W", $ret['start']-2);

		$ret['prev_year'] = (int)date("Y", $ret['start']-2);

		if($ret['prev_week']>$ret['week'] && $ret['prev_year']==$year) $ret['prev_year']--;

		return($ret);	

	}

	

	function weekday($day) {

		$def[1]='Montag';

		$def[2]='Dienstag';

		$def[3]='Mittwoch';

		$def[4]='Donnerstag';

		$def[5]='Freitag';

		$def[6]='Samstag';

		$def[7]='Sonntag';

		return($def[$day]);

	}



	function weekday_short($day) {

		$def[1]='Mo';

		$def[2]='Di';

		$def[3]='Mi';

		$def[4]='Do';

		$def[5]='Fr';

		$def[6]='Sa';

		$def[7]='So';

		return($def[$day]);

	}

	

	function remove_event($id) {

		global $wpdb, $current_user;

		$table_name = $wpdb->prefix . "sbs_schedule_events";

		$wpdb->query($wpdb->prepare( 

			"

				DELETE 

				FROM $table_name 

				WHERE ID = '$id'

			",NULL

		));	

	}
	
	function delete_week($week, $year) {

		global $wpdb, $current_user;

		$table_name = $wpdb->prefix . "sbs_schedule_events";

		$wpdb->query($wpdb->prepare( 

			"

				DELETE 

				FROM $table_name 

				WHERE week = '$week' AND year = '$year'

			",NULL

		));	

	}
	
	
	function cancel_event($id) {
		global $wpdb;
		$table_name = $wpdb->prefix . "sbs_schedule_events";
		$ret = $wpdb->get_results($wpdb->prepare( 
			"
				SELECT * 
				FROM $table_name 
				WHERE ID=$id 
				ORDER BY ID ASC 
				LIMIT 1
			",null
		), ARRAY_A);

		if(count($ret)==0) return(false);
		foreach($ret as $key=>$item) {
			$ret[$key]['EventData'] = unserialize(base64_decode($ret[$key]['EventData']));
			$ret[$key]['EventData']['cancelled']='true';
			$ret[$key]['EventData'] = base64_encode(serialize($ret[$key]['EventData']));
			$match['ID'] = $ret[$key]['ID'];
			unset($ret[$key]['ID']);
			$s=$wpdb->update($table_name, $ret[$key], $match);
		}
		return($s);	
	}


	function uncancel_event($id) {
		global $wpdb;
		$table_name = $wpdb->prefix . "sbs_schedule_events";
		$ret = $wpdb->get_results($wpdb->prepare( 
			"
				SELECT * 
				FROM $table_name 
				WHERE ID=$id 
				ORDER BY ID ASC 
				LIMIT 1
			",NULL
		), ARRAY_A);

		if(count($ret)==0) return(false);
		foreach($ret as $key=>$item) {
			$ret[$key]['EventData'] = unserialize(base64_decode($ret[$key]['EventData']));
			$s=false;
			if(isset($ret[$key]['EventData']['cancelled'])) {
				unset($ret[$key]['EventData']['cancelled']);
				$ret[$key]['EventData'] = base64_encode(serialize($ret[$key]['EventData']));
				$match['ID'] = $ret[$key]['ID'];
				unset($ret[$key]['ID']);
				$s=$wpdb->update($table_name, $ret[$key], $match);
			}
		}
		return($s);	
	}

	

	function add_event($eventdata) {

		global $wpdb, $current_user;

		$table_name = $wpdb->prefix . "sbs_schedule_events";

		$data['Year']=$eventdata['year'];

		unset($eventdata['year']);

		$data['Week']=$eventdata['week'];

		unset($eventdata['week']);

		$data['Day']=$eventdata['from_day'];

		if(isset($eventdata['from_hour']) && is_numeric($eventdata['from_hour'])) {

			$data['StartMinuteOfDay'] = 60*$eventdata['from_hour'];

			if(isset($eventdata['from_minute']) && is_numeric($eventdata['from_minute'])) {

				$data['StartMinuteOfDay'] += $eventdata['from_minute'];

			}

		}

		if(isset($data['StartMinuteOfDay']) && isset($eventdata['duration']) && is_numeric($eventdata['duration'])) {

			$data['EndMinuteOfDay'] = $data['StartMinuteOfDay'] + $eventdata['duration'];

		}

		$data['Room'] = $eventdata['room'];

		//Find overlapping

		global $wpdb;

		$table_name = $wpdb->prefix . "sbs_schedule_events";

		$check_set_num=array('Year', 'Year', 'Year', 'Year', 'Year', 'Year');

		foreach($check_set_num as $item) {

			if(!isset($data[$item]) || !is_numeric($data[$item])) return(false);
			
			//Rapha
			//$data[$item] = mysql_real_escape_string($data[$item]);

		}

		$request = 

			"

				SELECT * 

				FROM $table_name 

				WHERE (Year={$data['Year']} AND Week={$data['Week']} AND Room={$data['Room']} AND Day={$data['Day']}) AND 

				(({$data['StartMinuteOfDay']}>=StartMinuteOfDay AND {$data['StartMinuteOfDay']}<EndMinuteOfDay) OR ({$data['EndMinuteOfDay']}>StartMinuteOfDay AND {$data['EndMinuteOfDay']}<=EndMinuteOfDay)) 

				ORDER BY ID ASC

			";	

		

		$ret = $wpdb->get_results($wpdb->prepare("SELECT * 

				FROM $table_name 

				WHERE (Year=%d AND Week=%d AND Room=%s AND Day=%d) AND 

				((%d>=StartMinuteOfDay AND %d<EndMinuteOfDay) OR (%d>StartMinuteOfDay AND %d<=EndMinuteOfDay)) 

				ORDER BY ID ASC",$data['Year'],$data['Week'],$data['Room'],$data['Day'],$data['StartMinuteOfDay'],$data['StartMinuteOfDay'],$data['EndMinuteOfDay'],$data['EndMinuteOfDay']), ARRAY_A);

		

                // debug code added by harshal 06-feb-2020  Mobile: +91 9888434518 OR email: harshaldhingra18@gmail.com Start
                    //if(empty($eventdata['credits']) || $eventdata['credits'] == "" ){ $eventdata['credits'] = "1";}
                // debug code added by harshal 06-feb-2020 Mobile: +91 9888434518 OR email: harshaldhingra18@gmail.com End

		if(count($ret)==0) {

			unset($eventdata['id']);

			$data['EventData']=base64_encode(serialize($eventdata));

			$wpdb->insert($table_name, $data);

			return($wpdb->insert_id);	

		} elseif(count($ret)==1) {

			//Is Edit?

			if(isset($eventdata['id']) && is_numeric($eventdata['id']) && $eventdata['id']==$ret[0]['ID']) {

				$match['ID'] = $eventdata['id'];

				unset($eventdata['id']);

				$data['EventData']=base64_encode(serialize($eventdata));

				$wpdb->update($table_name, $data, $match);

				return($match['ID']);	

			}		

		} else {

			return(false);

		}

	}

	

	function get_events($year, $week) {

		global $wpdb;

		$table_name = $wpdb->prefix . "sbs_schedule_events";

		$ret = $wpdb->get_results($wpdb->prepare( 

			"

				SELECT * 

				FROM $table_name 

				WHERE Year=$year AND Week=$week 

				ORDER BY ID ASC

			",null

		), ARRAY_A);

		foreach($ret as $key=>$item) {
			
			$ret[$key]['EventData'] = unserialize(base64_decode($ret[$key]['EventData']));
			if(isset($ret[$key]['EventData']['lastcall']) && (int)$ret[$key]['EventData']['lastcall']>0 && !isset($ret[$key]['EventData']['cancelled'])) {
				//echo('LastCall Set!');
				$ret[$key]['StartTimestamp']=sbs_schedule::convert_to_timestamp($ret[$key]['Year'], $ret[$key]['Week'], $ret[$key]['Day'], $ret[$key]['StartMinuteOfDay']);
				//echo("-{$ret[$key]['StartTimestamp']}-");
				$deadline = $ret[$key]['StartTimestamp']-((int)$ret[$key]['EventData']['lastcall']*3600);
				//echo("-{$deadline}-");
				if($deadline<time()) {
					$fret=sbs_schedule::get_bookings($ret[$key]['ID']);
					//echo("-{$fret['count']}-");
					if($fret['count']==0) {
						sbs_schedule::cancel_event($ret[$key]['ID']);
						$ret[$key]['EventData']['cancelled']=true;
					}
				}
			}
		}

		return($ret);	

	}
	function get_events_day($year, $week, $day_a) {

		global $wpdb;
		
		
		$table_name = $wpdb->prefix . "sbs_schedule_events";

		$ret = $wpdb->get_results($wpdb->prepare( 

			"

				SELECT * 

				FROM $table_name 

				WHERE Year=$year AND Week=$week AND Day=$day_a

				ORDER BY ID ASC

			",null

		), ARRAY_A);

		foreach($ret as $key=>$item) {

			$ret[$key]['EventData'] = unserialize(base64_decode($ret[$key]['EventData']));
			if(isset($ret[$key]['EventData']['lastcall']) && (int)$ret[$key]['EventData']['lastcall']>0 && !isset($ret[$key]['EventData']['cancelled'])) {
				//echo('LastCall Set!');
				$ret[$key]['StartTimestamp']=sbs_schedule::convert_to_timestamp($ret[$key]['Year'], $ret[$key]['Week'], $ret[$key]['Day'], $ret[$key]['StartMinuteOfDay']);
				//echo("-{$ret[$key]['StartTimestamp']}-");
				$deadline = $ret[$key]['StartTimestamp']-((int)$ret[$key]['EventData']['lastcall']*3600);
				//echo("-{$deadline}-");
				if($deadline<time()) {
					$fret=sbs_schedule::get_bookings($ret[$key]['ID']);
					//echo("-{$fret['count']}-");
					if($fret['count']==0) {
						sbs_schedule::cancel_event($ret[$key]['ID']);
						$ret[$key]['EventData']['cancelled']=true;
					}
				}
			}
		}

		return($ret);	

	}
	

	function get_event_by_id($id) {

		global $wpdb;

		$table_name = $wpdb->prefix . "sbs_schedule_events";

		$ret = $wpdb->get_results($wpdb->prepare( 

			"

				SELECT * 

				FROM $table_name 

				WHERE ID=$id 

				ORDER BY ID ASC

			",NULL

		), ARRAY_A);

		if(count($ret)==0) return(false);

		foreach($ret as $key=>$item) {

			$ret[$key]['EventData'] = unserialize(base64_decode($ret[$key]['EventData']));

			$ret[$key]['StartTimestamp']=sbs_schedule::convert_to_timestamp($ret[$key]['Year'], $ret[$key]['Week'], $ret[$key]['Day'], $ret[$key]['StartMinuteOfDay']);

		}

		return($ret);	

	}

	

	function get_bookings($event_id, $users_details=false) {

		global $wpdb, $current_user;

		$current_user = wp_get_current_user();	

		$table_name = $wpdb->prefix . "sbs_schedule_bookings";

		$ret = $wpdb->get_results($wpdb->prepare( 

			"

				SELECT * 

				FROM $table_name 

				WHERE EventID=$event_id 

				ORDER BY ID ASC

			",null

		), ARRAY_A);

		$func_ret['count'] = 0;
		$func_ret['count_testtraining'] = 0;

		$func_ret['i_booked'] = false;

		foreach($ret as $key=>$item) {

			$func_ret['count']++;

			if($ret[$key]['UserID']==$current_user->ID) $func_ret['i_booked'] = true;
			if($ret[$key]['UserID']==NULL) $func_ret['count_testtraining']++;
			$ret[$key]['Data'] = unserialize(base64_decode($ret[$key]['Data']));

			if($users_details) {

				$usrinfo = get_userdata($ret[$key]['UserID']);

				$ret[$key]['UserInfo'] = $usrinfo->data;

			}

		}

		$func_ret['items']=$ret;

		return($func_ret);	

	

	}

	

	function convert_to_timestamp($year, $week, $day, $minuteofday) {

		$kw = sbs_schedule::get_start_end_of_week($week, $year);

		$event_start_timestamp = $kw['start'];

		if($day > 1) $event_start_timestamp += ($day-1) * 86400;

		$event_start_timestamp += $minuteofday * 60;

		return($event_start_timestamp);

	}

	

	function get_bookings_by_user($user_id=false) {

		global $wpdb, $current_user;

		if($user_id===false || !is_numeric($user_id)) {

			$current_user = wp_get_current_user();	

			$user_id = $current_user->id;

		}

		$table_name = $wpdb->prefix . "sbs_schedule_bookings";

		$mintime = time();

		$ret = $wpdb->get_results($wpdb->prepare( 

			"

				SELECT * 

				FROM $table_name 

				WHERE UserID=$user_id AND StartTime > $mintime 

				ORDER BY ID ASC

			",NULL

		), ARRAY_A);

		foreach($ret as $key=>$item) {

			$ret[$key]['event'] = sbs_schedule::get_event_by_id($item['EventID']);

			$sorter[$key] = $ret[$key]['event'][0]['StartTimestamp']; 

		}

		if(isset($sorter) && is_array($sorter)) array_multisort($sorter, $ret);

		return($ret);	



	}

	

	function book($event_id, $testtraining=false) {

		global $wpdb, $current_user;
                
                $hdTrackPost = ""; // debug code added by harshal
                
		$current_user = wp_get_current_user();

		$event = sbs_schedule::get_event_by_id($event_id);

		$balance = sbs_credits::get_balance();

		$group['id'] = sbs_credits::get_group_id();

		$group['name'] = sbs_credits::get_group_name();

		$kw = sbs_schedule::get_start_end_of_week(false, false);

		$ispast = false;

		$kwcomp1 = $kw['year'] . str_pad($kw['week'], 2, "0", STR_PAD_LEFT);

		$kwcomp2 = $event[0]['Year'] . str_pad($event[0]['week'], 2, "0", STR_PAD_LEFT);

		//if($kwcomp1>$kwcomp2) $ispast = true;

		if($kwcomp1==$kwcomp2) {

			$event_start_timestamp = $kw['start'];

			if($event[0]['Day'] > 1) $event_start_timestamp += ($event[0]['Day']-1) * 86400;

			$event_start_timestamp += $event[0]['StartMinuteOfDay'] * 60;

			if($event_start_timestamp < (time() - 0)) $ispast = true;

		} else {

			$kw_calc = sbs_schedule::get_start_end_of_week($event[0]['Week'], $event[0]['Year']);

			$event_start_timestamp = $kw_calc['start'];

			if($event[0]['Day'] > 1) $event_start_timestamp += ($event[0]['Day']-1) * 86400;

			$event_start_timestamp += $event[0]['StartMinuteOfDay'] * 60;

		}

		if($ispast) {

			$_SESSION['sbs_schedule_message'] = "Dieser Kurs ist nicht buchbar, da sein Beginn in der vergangenheit liegt!{$kwcomp1}/{$kwcomp2}";

			return(false);

		}
                if(isset($_POST)){$hdTrackPost = json_encode($_POST);}// debug code added by harshal
                    
                
                
		//Already booked?

		$bookings = sbs_schedule::get_bookings($event_id);
		if($testtraining) $bookings['i_booked']=false;
		if(!$bookings['i_booked']) {

			if($bookings['count']<$event[0]['EventData']['spaces']) {

				if($testtraining) $balance=100;
				if($balance!==false && $balance>=$event[0]['EventData']['credits']) {

					if(isset($event[0]['EventData']['sbs_group'][$group['id']]) && $event[0]['EventData']['sbs_group'][$group['id']]=='ok') {

						$table_name = $wpdb->prefix . "sbs_schedule_bookings";

						$data['EventID'] = $event_id;

						$data['UserID'] = $current_user->ID;
						if($testtraining) unset($data['UserID']);
						$data['StartTime'] = $event_start_timestamp;
                                                // debug code added by harshal start
                                                $data['Track'] = "entry with book function - ".time()." - Event Starttime=".date("d.m.Y H:i", $event_start_timestamp)." - Track code=".$hdTrackPost;
                                                // debug code added by harshal end
                                                
						$wpdb->insert($table_name, $data);

						$booking_text = "Buchung Kurs {$event[0]['EventData']['title']}";

						if(isset($event_start_timestamp)) $booking_text .= " am " . date("d.m.Y H:i", $event_start_timestamp);

						if(!$testtraining) {
							sbs_credits::book_account_item($data['UserID'], ($event[0]['EventData']['credits']*-1), $booking_text, false, 10);					

							$_SESSION['sbs_schedule_message'] = "Buchung erfolgreich! Dir wurden {$event[0]['EventData']['credits']} Credits abgezogen.<br />Der Kurs ist bis zu 90 Minuten vor Kursbeginn kostenlos stornierbar.";
						} else {
							$_SESSION['sbs_schedule_message'] = "Buchung Probetraining erfolgreich!";
							
						// Debug randomly disappearing try-outs (Probetraining), add all tryouts to log table
			
						$table_probe = $wpdb->prefix . "sbs_schedule_probetrainings";
						$data_probe['EventID'] = $event_id;
						$data_probe['Track'] = "entry with admin Probe book function - TS: ".time()." - Track code=".$hdTrackPost;
						$wpdb->insert($table_probe, $data_probe);						
						// Debug End
						}
			//die('OK');

						return($wpdb->insert_id);	

					} else {

						$_SESSION['sbs_schedule_message'] = "Dieser Kurs ist für Mitglieder der Gruppe &quot;{$group['name']}&quot; nicht buchbar!";
			//die('GR');

						return(false);

					}

				} else {

					$_SESSION['sbs_schedule_message'] = "Du hast nicht genügend Credits um diesen Kurs zu buchen!";
			//die('CR');

					return(false);

				}

			} else {

				$_SESSION['sbs_schedule_message'] = "Leider keine freien Plätze mehr in diesem Kurs!";
			//die('FP');

				return(false);

			}

		} else {

			$_SESSION['sbs_schedule_message'] = "Du hast diesen Kurs bereits gebucht!";
			//die('AB');

			return(false);

		}		

	}


		function warteliste($event_id, $testtraining=false) {

		global $wpdb, $current_user;

		$current_user = wp_get_current_user();

		$event = sbs_schedule::get_event_by_id($event_id);

		$balance = sbs_credits::get_balance();

		$group['id'] = sbs_credits::get_group_id();

		$group['name'] = sbs_credits::get_group_name();

		$kw = sbs_schedule::get_start_end_of_week(false, false);

		$ispast = false;

		$kwcomp1 = $kw['year'] . str_pad($kw['week'], 2, "0", STR_PAD_LEFT);

		$kwcomp2 = $event[0]['Year'] . str_pad($event[0]['week'], 2, "0", STR_PAD_LEFT);

		//if($kwcomp1>$kwcomp2) $ispast = true;

		if($kwcomp1==$kwcomp2) {

			$event_start_timestamp = $kw['start'];

			if($event[0]['Day'] > 1) $event_start_timestamp += ($event[0]['Day']-1) * 86400;

			$event_start_timestamp += $event[0]['StartMinuteOfDay'] * 60;

			if($event_start_timestamp < (time() - 0)) $ispast = true;

		} else {

			$kw_calc = sbs_schedule::get_start_end_of_week($event[0]['Week'], $event[0]['Year']);

			$event_start_timestamp = $kw_calc['start'];

			if($event[0]['Day'] > 1) $event_start_timestamp += ($event[0]['Day']-1) * 86400;

			$event_start_timestamp += $event[0]['StartMinuteOfDay'] * 60;

		}

		if($ispast) {

			$_SESSION['sbs_schedule_message'] = "Dieser Kurs ist nicht buchbar, da sein Beginn in der vergangenheit liegt!{$kwcomp1}/{$kwcomp2}";

			return(false);

		}

		//Already booked?

		$bookings = sbs_schedule::get_bookings($event_id);
		if($testtraining) $bookings['i_booked']=false;
		if(!$bookings['i_booked']) {

			if($bookings['count']==$event[0]['EventData']['spaces']) {

				if($testtraining) $balance=100;
				if($balance!==false && $balance>=$event[0]['EventData']['credits']) {

					if(isset($event[0]['EventData']['sbs_group'][$group['id']]) && $event[0]['EventData']['sbs_group'][$group['id']]=='ok') {

						$table_name = $wpdb->prefix . "sbs_schedule_warteliste";

						$data['EventID'] = $event_id;

						$data['UserID'] = $current_user->ID;
						if($testtraining) unset($data['UserID']);
						$data['StartTime'] = $event_start_timestamp;

						$wpdb->insert($table_name, $data);

						$booking_text = "Buchung Kurs {$event[0]['EventData']['title']}";

						if(isset($event_start_timestamp)) $booking_text .= " am " . date("d.m.Y H:i", $event_start_timestamp);

						if(!$testtraining) {
							//sbs_credits::book_account_item($data['UserID'], ($event[0]['EventData']['credits']*-1), $booking_text, false, 10);					

							$_SESSION['sbs_schedule_message'] = "Warteliste erfolgreich! Bitte behalte<b> {$event[0]['EventData']['credits']} Credits</b> auf deinem Account um nachzurutschen, falls bis zu <b>90 Minuten</b> vor dem Kurs ein Platz frei wird. Solltest du nachrutschst wirst du per <b>E-Mail</b> dar&uuml;ber informiert. Bitte auch den <b>Spam Ordner</b> &uuml;berpr&uuml;fen.";
						} else {
							$_SESSION['sbs_schedule_message'] = "Buchung Probetraining erfolgreich!";
						}
			//die('OK');

						return($wpdb->insert_id);	

					} else {

						$_SESSION['sbs_schedule_message'] = "Dieser Kurs ist für Mitglieder der Gruppe &quot;{$group['name']}&quot; nicht buchbar!";
			//die('GR');

						return(false);

					}

				} else {

					$_SESSION['sbs_schedule_message'] = "Du hast nicht genügend Credits um diesen Kurs zu buchen!";
			//die('CR');

					return(false);

				}

			} else {

				$_SESSION['sbs_schedule_message'] = "Leider keine freien Plätze mehr in diesem Kurs!";
			//die('FP');

				return(false);

			}

		} else {

			$_SESSION['sbs_schedule_message'] = "Warteliste nur wenn Kurs voll ist!";
			//die('AB');

			return(false);

		}		

	}
	
	
	
	
	
	function warteliste_book($event_id, $nachrutschid) {

		global $wpdb, $current_user;

		$current_user = $nachrutschid;

		$event = sbs_schedule::get_event_by_id($event_id);

		$balance = sbs_credits::get_balance();

		$group['id'] = sbs_credits::get_group_id();

		$group['name'] = sbs_credits::get_group_name();

		$kw = sbs_schedule::get_start_end_of_week(false, false);

		$ispast = false;

		$kwcomp1 = $kw['year'] . str_pad($kw['week'], 2, "0", STR_PAD_LEFT);

		$kwcomp2 = $event[0]['Year'] . str_pad($event[0]['week'], 2, "0", STR_PAD_LEFT);

		//if($kwcomp1>$kwcomp2) $ispast = true;

		if($kwcomp1==$kwcomp2) {

			$event_start_timestamp = $kw['start'];

			if($event[0]['Day'] > 1) $event_start_timestamp += ($event[0]['Day']-1) * 86400;

			$event_start_timestamp += $event[0]['StartMinuteOfDay'] * 60;

			if($event_start_timestamp < (time() - 0)) $ispast = true;

		} else {

			$kw_calc = sbs_schedule::get_start_end_of_week($event[0]['Week'], $event[0]['Year']);

			$event_start_timestamp = $kw_calc['start'];

			if($event[0]['Day'] > 1) $event_start_timestamp += ($event[0]['Day']-1) * 86400;

			$event_start_timestamp += $event[0]['StartMinuteOfDay'] * 60;

		}

		if($ispast) {

			$_SESSION['sbs_schedule_message'] = "Dieser Kurs ist nicht buchbar, da sein Beginn in der vergangenheit liegt!{$kwcomp1}/{$kwcomp2}";

			return(false);

		}

		//Already booked?

		$bookings = sbs_schedule::get_bookings($event_id);
		if($testtraining) $bookings['i_booked']=false;
		if(!$bookings['i_booked']) {

			if($bookings['count']<$event[0]['EventData']['spaces']) {

				if($testtraining) $balance=100;
				if($balance!==false && $balance>=$event[0]['EventData']['credits']) {

					if(isset($event[0]['EventData']['sbs_group'][$group['id']]) && $event[0]['EventData']['sbs_group'][$group['id']]=='ok') {

						$table_name = $wpdb->prefix . "sbs_schedule_bookings";

						$data['EventID'] = $event_id;

						$data['UserID'] = $nachrutschid;
						if($testtraining) unset($data['UserID']);
						$data['StartTime'] = $event_start_timestamp;

						$wpdb->insert($table_name, $data);

						$booking_text = "Buchung Kurs {$event[0]['EventData']['title']}";

						if(isset($event_start_timestamp)) $booking_text .= " am " . date("d.m.Y H:i", $event_start_timestamp);

						if(!$testtraining) {
							sbs_credits::book_account_item($data['UserID'], ($event[0]['EventData']['credits']*-1), $booking_text, false, 10);					

							$_SESSION['sbs_schedule_message'] = "Buchung erfolgreich! Dir wurden {$event[0]['EventData']['credits']} Credits abgezogen.<br />Der Kurs ist bis zu 90 Minuten vor Kursbeginn kostenlos stornierbar.";
						} else {
							$_SESSION['sbs_schedule_message'] = "Buchung Probetraining erfolgreich!";

						}
			//die('OK');

						return($wpdb->insert_id);	

					} else {

						$_SESSION['sbs_schedule_message'] = "Dieser Kurs ist für Mitglieder der Gruppe &quot;{$group['name']}&quot; nicht buchbar!";
			//die('GR');

						return(false);

					}

				} else {

					$_SESSION['sbs_schedule_message'] = "Du hast nicht genügend Credits um diesen Kurs zu buchen!";
			//die('CR');

					return(false);

				}

			} else {

				$_SESSION['sbs_schedule_message'] = "Leider keine freien Plätze mehr in diesem Kurs!";
			//die('FP');

				return(false);

			}

		} else {

			$_SESSION['sbs_schedule_message'] = "Du hast diesen Kurs bereits gebucht!";
			//die('AB');

			return(false);

		}		

	}
	
	
	
	
		function warteliste_delete($event_id) {
			global $wpdb;
			if(isset($event_id)) {
			$user_id = get_current_user_id();
			$table_name = $wpdb->prefix . "sbs_schedule_warteliste";
			$wpdb->query($wpdb->prepare(" DELETE FROM $table_name WHERE EventID = '".$event_id."' AND UserID = '" . $user_id . "' ",NULL));	
			$_SESSION['sbs_schedule_message'] = "Von Warteliste gelöscht.";
			return(true);
			}
			else {
			$_SESSION['sbs_schedule_message'] = "Error.";
			return(false);
			}
		}
	
	
	
	
	
	function unbook($event_id) {

		$old_tz = date_default_timezone_get();
    
                $hdTrackUnPost = ""; // debug code added by harshal
                
		date_default_timezone_set('Europe/Berlin');

		global $wpdb, $current_user;

		$current_user = wp_get_current_user();

		$event = sbs_schedule::get_event_by_id($event_id);

		$kw = sbs_schedule::get_start_end_of_week(false, false);

		

		$ispast = false;

		$is_refund = true;
		
		$warteliste_frist = true;

		$kwcomp1 = $kw['year'] . $kw['week'];

		$kwcomp2 = $event[0]['Year'] . $event[0]['Week'];

		if($kwcomp1>$kwcomp2) $ispast = true;

		if($kwcomp1==$kwcomp2) {

			$event_start_timestamp = $kw['start'];

			if($event[0]['Day'] > 1) $event_start_timestamp += ($event[0]['Day']-1) * 86400;

			$event_start_timestamp += $event[0]['StartMinuteOfDay'] * 60;

			if(($event_start_timestamp - 900) < time()) $ispast = true;

			if(($event_start_timestamp - 5400) < time()) $is_refund = false;
			
			if(($event_start_timestamp - 5400) < time()) $warteliste_frist = false; //Warteliste nachrutschen NUR wenn mehr 1h Zeit bis zum Kurs

		}

		if($ispast) {

			$_SESSION['sbs_schedule_message'] = "Stornierung nur bis 15 Minuten vor Kursbeginn möglich!";

			return(false);

		}

		
              if(isset($_POST)){$hdTrackUnPost = json_encode($_POST);}// debug code added by harshal
  
		$bookings = sbs_schedule::get_bookings($event_id);

		if($bookings['i_booked']) {

			$data['EventID'] = $event_id;

			$data['UserID'] = $current_user->ID;			

			if($is_refund) {

				$booking_text = "Storno Kurs {$event[0]['EventData']['title']}";

				if(isset($event_start_timestamp)) $booking_text .= " am " . date("d.m.Y H:i", $event_start_timestamp);

				sbs_credits::book_account_item($data['UserID'], ($event[0]['EventData']['credits']), $booking_text, false, 10);					

			} else {

				$event[0]['EventData']['credits']=0;

			}
			//check who is next on waiting list
			if($bookings['count']==$event[0]['EventData']['spaces']) {
			$table_name = $wpdb->prefix . "sbs_schedule_warteliste";			
			$warteids = $wpdb->get_results("SELECT UserID FROM $table_name WHERE EventID = '$event_id' ORDER BY Timestap DESC");
			foreach ( $warteids as $warteid ){
				$creditbalance = sbs_credits::get_account_sum($warteid->UserID);
				if($creditbalance>=$event[0]['EventData']['credits']) {
					$nachrutschid = $warteid->UserID;
				}
				
			}
			
			}
			
			// Debug randomly disappearing try-outs (Probetraining), create unbooking entry to log all unbookings
			
			$table_name = $wpdb->prefix . "sbs_schedule_unbookings";
			$data_unbook['EventID'] = $event_id;
			$data_unbook['UserID'] = $current_user->ID;
			$data_unbook['Track'] = "entry with unbook function - TS: ".time()." - Track code=".$hdTrackUnPost;
			$wpdb->insert($table_name, $data_unbook);
			
			// Debug End

			$table_name = $wpdb->prefix . "sbs_schedule_bookings";

			$wpdb->query($wpdb->prepare( 

				"

					DELETE 

					FROM $table_name 

					WHERE EventID = '$event_id' AND UserID = '" . $current_user->ID . "'

				",NULL

			));	
			
			
			

			
			$_SESSION['sbs_schedule_message'] = "Stornierung erfolgreich! Dir wurden {$event[0]['EventData']['credits']} Credits gutgeschrieben.";
			
			
			if($nachrutschid AND $warteliste_frist == TRUE) {
				$creditbalance = sbs_credits::get_account_sum($nachrutschid);
				if($creditbalance!==false && $creditbalance>=$event[0]['EventData']['credits']) {
					//book class for next person on waiting list
					
					$table_name = $wpdb->prefix . "sbs_schedule_bookings";

					$dataw['EventID'] = $event_id;

					$dataw['UserID'] = $nachrutschid;
					
					$dataw['StartTime'] = NULL;

                                        // debug code added by harshal start
                                           $dataw['Track'] = "entry with unbook auto select user function-".$nachrutschid." - ".time()." - Event Starttime=".date("d.m.Y H:i", $event_start_timestamp)." - Track code=".$hdTrackUnPost;
                                        // debug code added by harshal end
                                        
					$wpdb->insert($table_name, $dataw);
					
					$booking_text = "Buchung Kurs {$event[0]['EventData']['title']}";
					if(isset($event_start_timestamp)) $booking_text .= " am " . date("d.m.Y H:i", $event_start_timestamp);
					$booking_text .= " durch Warteliste";
					sbs_credits::book_account_item($nachrutschid, ($event[0]['EventData']['credits']*-1), $booking_text, false, 10);	
					
					//delete entry on waiting list because person was booked
					
					$table_name = $wpdb->prefix . "sbs_schedule_warteliste";
					$wpdb->query($wpdb->prepare(" DELETE FROM $table_name WHERE EventID = '".$event_id."' AND UserID = '" . $nachrutschid . "' ",NULL));	
					
					//send email
				
					
					$user_info = get_userdata($nachrutschid);
					$kursstarttime = date("d.m.Y H:i", $event_start_timestamp);
					$headers = 'From: Urban Gladiators <info@urbangladiators.de>' . "\r\n";
					$message = "Hallo ".$user_info->nickname.",\nsoeben ist ein Platz fuer folgenden Kurs freigeworden:\n";
					$message .= $event[0]['EventData']['title']."\n";
					$message .= $kursstarttime."\n";
					$message .= "Da du dich auf die Warteliste eingetragen hast wurde der Kurs automatisch fuer dich gebucht. \nDabei wurden dir ".$event[0]['EventData']['credits']." Credits abgezogen.\n\nMit freundlichen Grueßen,\nDie UG Warteliste";
					//$message .= "\n\nDebug:\ndata[UserID] = ".$nachrutschid."\nMinus-Credits:".($event[0]['EventData']['credits']*-1)."\nBooking-Text:".$booking_text;
					//$message .= "\n\nWarteliste Frist: ".$warteliste_zeit;
					wp_mail( $user_info->user_email, 'UG Warteliste', $message, $headers);
				}
			}
			return(true);

		} else {

			$_SESSION['sbs_schedule_message'] = "Du hattest diesen Kurs nicht gebucht oder bereits storniert!";

			return(false);

		}

	}



	function unbooktest($event_id) {
		global $wpdb, $current_user;
		$hdTrackUnPost = ""; //Debug
		if(isset($_POST)){$hdTrackUnPost = json_encode($_POST);} //Debug
		$table_name = $wpdb->prefix . "sbs_schedule_bookings";
		$affected = $wpdb->query($wpdb->prepare( 
			"
				DELETE 
				FROM $table_name 
				WHERE EventID = '$event_id' AND UserID = 0 LIMIT 1
			",NULL
		));	

		$_SESSION['sbs_schedule_message'] = "Stornierung von {$affected} Probetraining erfolgreich!";
		
			// Debug randomly disappearing try-outs (Probetraining), create unbooking entry to log all unbookings
			
			$table_name = $wpdb->prefix . "sbs_schedule_unbookings";
			$data_unbook['EventID'] = $event_id;
			$data_unbook['UserID'] = 0;
			$data_unbook['Track'] = "entry with admin Probe unbook function - TS: ".time()." - Track code=".$hdTrackUnPost;
			$wpdb->insert($table_name, $data_unbook);
			
			// for debug also add to separate try-out (Probetraining) log table
			
			$table_probe = $wpdb->prefix . "sbs_schedule_probetrainings";
			$data_probe['EventID'] = $event_id;
			$data_probe['Track'] = "entry with admin Probe UNbook function - TS: ".time()." - Track code=".$hdTrackUnPost;
			$wpdb->insert($table_probe, $data_probe);	
			
			// Debug End
		
		return(true);
	}

	

	function copy_from_previous_week($year, $week) {

		echo("COPY {$year}/{$week}\n");
		
		$events = sbs_schedule::get_events($year, $week);

		$kw = sbs_schedule::get_start_end_of_week($week, $year);

		foreach($events as $event) {

			$eventdata = $event['EventData'];
			//$eventdata = unserialize(base64_decode($eventdata));
			unset($eventdata['cancelled']);
			//$eventdata = base64_encode(serialize($eventdata));

			$eventdata['year'] = $kw['next_year'];

			$eventdata['week'] = $kw['next_week'];

			sbs_schedule::add_event($eventdata);

		}

		//print_r($events);

	}

	

	function create_tables() {

		global $wpdb;

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$table_name = $wpdb->prefix . "sbs_schedule_events";

        $sql = "CREATE TABLE " . $table_name . " (

              ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,

			  Year smallint(4) UNSIGNED NOT NULL,

			  Week tinyint(2) UNSIGNED NOT NULL,

			  Day tinyint(2) UNSIGNED NOT NULL,

			  StartMinuteOfDay int(10) UNSIGNED NULL,

			  EndMinuteOfDay int(10) UNSIGNED NULL,

			  Room tinyint(3) UNSIGNED NULL,

			  EventData text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,

              PRIMARY KEY ID (ID),

			  KEY yw (Year,Week)

            );";

		dbDelta($sql);



		$table_name = $wpdb->prefix . "sbs_schedule_bookings";

        $sql = "CREATE TABLE " . $table_name . " (

              ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,

			  EventID bigint(20) UNSIGNED NOT NULL,

			  UserID bigint(20) UNSIGNED NOT NULL,

			  StartTime bigint(20) UNSIGNED NULL,

			  Data text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,

              PRIMARY KEY ID (ID),

			  KEY ev (EventID)

            );";

		dbDelta($sql);

	}	

}