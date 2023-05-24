
<link rel="stylesheet" href="<?php echo(plugin_dir_url(__FILE__)); ?>tipso.css">
<link rel="stylesheet" href="<?php echo(plugin_dir_url(__FILE__)); ?>schedules.css">
<?php /*if(($balance=sbs_credits::get_balance())===false) { ?>Du bist nicht eingeloggt!<?php } else { ?> Du bist eingeloggt, hast <?php echo($balance); ?> Credits und gehörst zur Gruppe &quot;<?php echo(sbs_credits::get_group_name()); ?>&quot;<?php if(!$frontend) { ?> [<?php echo(sbs_credits::get_group_id()); ?>]<?php } } */?>

<?php
wp_enqueue_script('tipso', plugin_dir_url(__FILE__) . 'tipso.js', array('jquery'));
wp_enqueue_script('tipso');

if(!isset($_REQUEST['week']) || $frontend) $_REQUEST['week']=false;

if(!isset($_REQUEST['year']) || $frontend) $_REQUEST['year']=false;

if(date('W') == "01" AND date('m') == "12")
{
	//schon erste Kalenderwoche aber noch letzter Monat im Jahr
	$y = (int) date("Y");
	$yn = $y+1;
	if(true) $kw = sbs_schedule::get_start_end_of_week($_REQUEST['week'], $yn);
}
elseif(date('W') == "52" AND date('m') == "01")
{
	//schon Januar aber noch letzte Kalenderwoche vom vorherigen Jahr
	$y = (int) date("Y");
	$yn = $y-1;
	if(true) $kw = sbs_schedule::get_start_end_of_week($_REQUEST['week'], $yn);
}
else {
	// ansonsten normal
//$kw = sbs_schedule::get_start_end_of_week($_REQUEST['week'], 2019);
if(true) $kw = sbs_schedule::get_start_end_of_week($_REQUEST['week'], (int) date("Y"));

}


if(!$frontend) {
	if(!$_REQUEST['year']) $_REQUEST['year']=date("Y");
	$kw = sbs_schedule::get_start_end_of_week($_REQUEST['week'], $_REQUEST['year']);
	//print_r($kw);
}

if($frontend) {

	date_default_timezone_set('Europe/Berlin');

	//echo((int)date("H"));
	if((int)date("N")==7) {

		if((int)date("H")>=21) {
			$kw = sbs_schedule::get_start_end_of_week($kw['next_week'], $kw['next_year']);
			//$kw = sbs_schedule::get_start_end_of_week($kw['next_week'], 2019);
		}

	}

} else {
	//$kw = sbs_schedule::get_start_end_of_week($_REQUEST['week'], (int) date("Y"))
}
//echo $kw['week']." - ".$kw['year'];
?>

<style type="text/css">


.eventdiv {

text-align:left;
-webkit-user-select: none;
-moz-user-select: none;
-ms-user-select: none;
user-select: none;
background-color:#333333;
color:#FFFFFF;

<?php if($frontend) { ?>
font-size:10px;
line-height:13px;

<?php } else { ?>
font-size:9px;
line-height:10px;
<?php } ?>
}
</style>

<?php if(!$frontend) { ?>

<div id="spacer" style="width:100%; height:200px;"><br /><br /><br />Admin-Einstellungen zum Trainingsplan. Entsprechende Kalenderwoche wählen und mit der Maus über die Einträge fahren um weitere Informationen und Optionen zu erhalten.</div>

<?php } ?>



<div id="schedule_container_room_x" class="schedule_container">

	<div id="switch_room">
        <div id="toggle1" style="margin-left:35px; height:20px; width:130px; border:0px solid #000; color:#FFF; background-color: #333;  float:left;  text-align:left; z-index:100; position:relative;; cursor:pointer" onclick="selectRoom(1);">Raum 1</div>
        <div id="toggle2" style="margin-top:3px; height:20px; width:130px; border:0px solid #000; background-color:#09F; color:#FFF;  float:left; text-align:right; margin-left:-70px; z-index:10; position:relative; cursor:pointer;" onclick="selectRoom(2);">Raum 2</div>
    </div>
    <div style="clear:both;"></div>

    <script type="text/javascript">
function selectRoom(rm) {
    //alert(rm);
    if(rm==1) {

        jQuery('#toggle2').animate({
            marginLeft: '5px'
          }, 200, function() {
            // Animation complete.
            jQuery('#toggle1').css( "zIndex", 100);
            jQuery('#toggle2').css( "zIndex", 10);
          jQuery('#toggle2').animate({
            marginLeft: '-70px'
          }, 200, function() {
            // Animation complete.

        });

        });

        jQuery('#toggle1').animate({
            marginLeft: '0px'
          }, 200, function() {
            // Animation complete.
          jQuery('#toggle1').animate({
            marginLeft: '35px'
          }, 200, function() {
            // Animation complete.

        });

        });

		jQuery('.room_one').css('z-index', 500);
		jQuery('.room_two').css('z-index', 400);


    } else if(rm==2) {
        jQuery('#toggle2').animate({
            marginLeft: '5px'
          }, 200, function() {
            // Animation complete.
            jQuery('#toggle1').css( "zIndex", 10);
            jQuery('#toggle2').css( "zIndex", 100);
          jQuery('#toggle2').animate({
            marginLeft: '-70px'
          }, 200, function() {
            // Animation complete.

        });

        });

        jQuery('#toggle1').animate({
            marginLeft: '0px'
          }, 200, function() {
            // Animation complete.
          jQuery('#toggle1').animate({
            marginLeft: '35px'
          }, 200, function() {
            // Animation complete.

        });

        });

		jQuery('.room_one').css('z-index', 400);
		jQuery('.room_two').css('z-index', 500);


    }
}
</script>
<div style="clear:both;"></div>
    <div id="schedule_container_header_room_x" class="schedule_container_header">

    	KW <?php echo("{$kw['week']} / {$kw['year']} (" . date("d.m.Y", $kw['start']) . " - " . date("d.m.Y", $kw['end']) . ")"); ?><br />
		<?php

		for($y=0; $y<8; $y++) { ?>

        	<div class="schedule_cell schedule_header<?php if($y==0) echo(' schedule_time'); ?>" style="float:left;">

				<?php if($y>0) echo(sbs_schedule::weekday($y)); ?>

            </div>

        <?php }
		?>

    </div>

		<?php


		echo '<div id="schedule_container_main_room_x" class="schedule_container_main">';
			$events = sbs_schedule::get_events($kw['year'], $kw['week']);


		//print_r($events);

		$item=array();
		?>

		<div class="schedule_desktop">
		<?php
		foreach($events as $event) {

			if(!isset($event['EventData']['cancelled'])) $event['EventData']['cancelled']=false; else $event['EventData']['cancelled']=true;

			$item_desc=array('bookings'=>sbs_schedule::get_bookings($event['ID'], true), 'id'=>$event['ID'], 'duration'=>$event['EventData']['duration'], 'cancelled'=>$event['EventData']['cancelled'], 'credits'=>$event['EventData']['credits'], 'room'=>$event['EventData']['room'], 'start_min'=>$event['EventData']['from_minute'], 'title'=>$event['EventData']['title'], 'title_long'=>$event['EventData']['title_long'], 'details'=>$event['EventData']['description'], 'spaces'=>$event['EventData']['spaces']);

			if(!isset($item[$event['EventData']['from_day']][$event['EventData']['from_hour']])) $item[$event['EventData']['from_day']][$event['EventData']['from_hour']]=array();

			$item[$event['EventData']['from_day']][$event['EventData']['from_hour']][] = $item_desc;
			//echo $event['ID'];
		}
		?>
		</div>


		<?php
		$starthour = 7;

		$endhour = 22;

		$hour_height[0] = 40;

		$hour_height[1] = 40;

		$hour_height[2] = 40;

		$hour_height[3] = 40;

		$hour_height[4] = 40;

		$hour_height[5] = 40;

		$hour_height[6] = 40;

		$hour_height[7] = 40;

		$hour_height[8] = 40;

		$hour_height[9] = 40;

		$hour_height[10] = 40;

		$hour_height[11] = 40;

		$hour_height[12] = 40;

		$hour_height[13] = 40;

		$hour_height[14] = 40;

		$hour_height[15] = 40;

		$hour_height[16] = 40;

		$hour_height[17] = 40;

		$hour_height[18] = 40;

		$hour_height[19] = 40;

		$hour_height[20] = 40;

		$hour_height[21] = 40;

		$hour_height[22] = 40;

		$hour_height[23] = 40;

		$top=0;

		for($x=0; $x<24; $x++) {

			$hour_top[$x] = $top;

			if($x>=$starthour) $top += $hour_height[$x];

		}

		for($x=$starthour; $x<$endhour+1; $x++) {

		for($y=0; $y<8; $y++) {

				?>

				<div class="schedule_cell<?php if($y==0) echo(' schedule_time'); if($x%2){echo " cell_white";} else {echo " cell_grey";}?>" style="height:<?php echo($hour_height[$x]); ?>px; float:left;"<?php if($y>0 && !$frontend) { ?> onclick="preFillOut(<?php echo($y); ?>, <?php echo($x); ?>, true);"<?php } ?>>

                    <?php if($y==0) echo('<a name="' . $x . '"></a>' . str_pad($x, 2, '0', STR_PAD_LEFT) . ':00'); ?>


                </div>

				<?php

			}


			?>

            <div style="clear:both;"></div>

			<?php



		}

		?>

        <div id="events_container" style="position:absolute; top:0px; left:0px; width:100%;">

        <?php

		$x=0;

		$tooltips=array();

		foreach($item as $day=>$dayitem) {

			foreach($dayitem as $hour=>$houritem) {

				foreach($houritem as $eventdata) {
					$eventdata['duration'] = (float)$eventdata['duration'];

					$x++;

					//Calculate top

					$position['top'] = floor(($hour_top[$hour])+($hour_height[$hour]/60*$eventdata['start_min']));
					if ( 1==0 ) {
					$position['left'] = 9; //%
					}
					else {
					$position['left'] = 9 + ($day*13) - 13; //%
					}
					if($eventdata['room']==2) $position['left'] += 2;

					$position['height'] = floor(40/60*(float)$eventdata['duration']);

					if($position['height']<30) $position['height']=30;

					$position['width'] = 11; //%



					?>

                    <div class="eventdiv<?php if($eventdata['room']==2) echo(' eventdivalt room_two'); else echo(' room_one'); ?>" id="event_<?php echo($x); ?>" style="position:absolute; top:<?php echo($position['top']); ?>px; left:<?php echo($position['left']); ?>%; height:<?php echo($position['height']); ?>px; width:<?php echo($position['width']); ?>%;"><strong><?php echo($eventdata['title']); ?></strong><br /><span class="eventtime"><?php echo($hour . ':' . str_pad($eventdata['start_min'], 2, '0', STR_PAD_LEFT)); $starttime=mktime($hour, $eventdata['start_min'], 0); $starttime+=($eventdata['duration']*60); echo(' - ' . date("H:i", $starttime)); ?></span><br/><?php if(!$eventdata['cancelled']) { echo($eventdata['spaces']-$eventdata['bookings']['count']); /*?> von <?php echo($eventdata['spaces']); */?> Plätze frei <?php } else { ?><strong><?php echo($eventdata['spaces']); ?> Plätze frei</strong> <?php } if(!$frontend && $eventdata['bookings']['count_testtraining']>0) echo(" [{$eventdata['bookings']['count_testtraining']} Probe]"); ?></div>

                    <div id="event_user_list_<?php echo($x); ?>" style="display:none;">

                        <?php

						if(is_user_logged_in()) {

							if(count($eventdata['bookings']['items'])==0) {

								?><p>Noch keine Anmeldungen</p><?php

							} else {

								?><ol><?php

								foreach($eventdata['bookings']['items'] as $item) {

									if($item['UserInfo']==NULL) {
										?><li><?php echo('Probetraining'); ?></li><?php
									} else {
										?><li><?php echo($item['UserInfo']->user_login); ?></li><?php
									}
								}

								?></ol><?php

							}

						}

						?>

                    </div>

                    <?php
					
					$date = date("Y-m-d", strtotime($kw['year'].'W'.str_pad($kw['week'], 2, 0, STR_PAD_LEFT).' +'.($day -1).' days')); //calculate date of event from week number, day and year
							

					$tooltips[$x]['day']=$day;
					$tooltips[$x]['content'] = "a";
					$tooltips[$x]['content'] .= stripslashes(htmlspecialchars($eventdata['details'], ENT_QUOTES, 'UTF-8'));
					$tooltips[$x]['content'] .= "<br>";
					$tooltips[$x]['titel']= $eventdata['title_long'] . ' <br>' . sbs_schedule::weekday_short($day) . '. ' . $hour . ':' . str_pad($eventdata['start_min'], 2, '0', STR_PAD_LEFT) . ' Uhr';


					if(is_user_logged_in()) {
						if ($frontend && (date('m',strtotime($date)) == date('m')) OR (date('d') == date('t') AND date('Hi') >= '2100' ) ) { //check if event is in current month or if it is the last day of the current month after 21:00
								if(!$eventdata['bookings']['i_booked']) {

									//Schon Warteliste?
									global $wpdb;
									$UserID = get_current_user_id();
									$event_id = $eventdata['id'];
									$warte_count = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."sbs_schedule_warteliste WHERE UserID = '".$UserID."' AND EventID = '".$event_id."' " );

									$tooltips[$x]['content'].='<form style="float:left;" action="" method="post"><input type="hidden" name="sbs_schedule_book_item" value="' . $eventdata['id'] . '" />';
									if(!$eventdata['cancelled'] && $frontend && $eventdata['spaces']-$eventdata['bookings']['count']>0) {
										$tooltips[$x]['content'].='<input class="buchungsbutton" type="submit" value="Buchen für ' . $eventdata['credits'] . ' Credits" />&nbsp;&nbsp;</form>';
									}
									if($eventdata['spaces']-$eventdata['bookings']['count']<1 && $frontend) {
										$tooltips[$x]['content'].='Dieser Kurs ist ausgebucht.';
										if($warte_count>0) {
											$tooltips[$x]['content'].='<br>Du stehst bereits auf der Warteliste. ';
											$tooltips[$x]['content'].='<form style="float:left;" action="" method="post"><input type="hidden" name="sbs_schedule_warteliste_delete" value="' . $eventdata['id'] . '" /><input class="buchungsbutton" type="submit" value="von Warteliste entfernen" /></form>';
										}
									}


									if($eventdata['spaces']-$eventdata['bookings']['count']==0 && $warte_count==0) {
											$tooltips[$x]['content'].='<br><form style="float:left;" action="" method="post"><input type="hidden" name="sbs_schedule_warteliste" value="' . $eventdata['id'] . '" /><input class="buchungsbutton" type="submit" value="Warteliste" /></form>';
										}
											

								} 
								
								elseif($frontend) {
									$tooltips[$x]['content'].='<form style="float:left;" action="" method="post"><input type="hidden" name="sbs_schedule_unbook_item" value="' . $eventdata['id'] . '" /><input class="buchungsbutton" type="submit" value="Buchung löschen" /></form>';
								}
								
								$tooltips[$x]['content'].='<form style="float:left;" action="" method="post"><input class="buchungsbutton" type="button" value="Liste der Teilnehmer", onclick="openPopUpXUsr(' . $x . ');" /></form>';
						
						}
						else {
							$tooltips[$x]['content'].= "Kurse für den nächsten Monat können erst am letzten Tag des Monats ab 21:00 Uhr gebucht werden.";
						}
						
						
						if(!$frontend) {
							
							$tooltips[$x]['content'].='<form style="float:left;" action="" method="post"><input class="buchungsbutton" type="button" value="Liste der Teilnehmer", onclick="openPopUpXUsr(' . $x . ');" /></form>';
						

							$tooltips[$x]['content'].='<form style="float:left;" action="" method="post">&nbsp;&nbsp;<input type="hidden" name="sbs_schedule_delete_item" value="' . $eventdata['id'] . '" /><input type="submit" value="Löschen" /><input type="hidden" name="week" value="' . $kw['week'] . '" /><input type="hidden" name="year" value="' . $kw['year'] . '" /></form>';

							$tooltips[$x]['content'].='<form style="float:left;" action="" method="post">&nbsp;&nbsp;<input type="hidden" name="sbs_schedule_edit_item" value="' . $eventdata['id'] . '" /><input type="submit" value="Edit" /><input type="hidden" name="week" value="' . $kw['week'] . '" /><input type="hidden" name="year" value="' . $kw['year'] . '" /></form>';

							$tooltips[$x]['content'].='<form style="float:left;" action="" method="post">&nbsp;&nbsp;<input type="hidden" name="sbs_schedule_add_test" value="' . $eventdata['id'] . '" /><input type="submit" value="Probe+" /><input type="hidden" name="week" value="' . $kw['week'] . '" /><input type="hidden" name="year" value="' . $kw['year'] . '" /></form>';

							$tooltips[$x]['content'].='<form style="float:left;" action="" method="post">&nbsp;&nbsp;<input type="hidden" name="sbs_schedule_remove_test" value="' . $eventdata['id'] . '" /><input type="submit" value="Probe-" /><input type="hidden" name="week" value="' . $kw['week'] . '" /><input type="hidden" name="year" value="' . $kw['year'] . '" /></form>';

						}

					} else {

						$tooltips[$x]['content'].='<br><br>Zum Buchen bitte <a href="/login" style="color:#ffffff;"><b>einloggen</b></a>';

					}


					$tooltips[$x]['content'] .= "a";
					?>

                    <?php

				}

			}

		}



		?>

        </div>

    </div>

    <?php if(!$frontend) { ?>

    <div id="schedule_container_footer_room_x" class="schedule_container_footer">

    	<div style="position:absolute; bottom:5px; left:5px;"><form action="" method="post"><input type="hidden" name="week" value="<?php echo($kw['prev_week']); ?>" /><input type="hidden" name="year" value="<?php echo($kw['prev_year']); ?>" /><input type="submit" value="Vorherige Woche" /></form></div>

    	<div style="position:absolute; bottom:5px; right:50%; width:200px; text-align:right;"><form action="" method="post"><input type="hidden" name="week" value="<?php echo($kw['week']); ?>" /><input type="hidden" name="year" value="<?php echo($kw['year']); ?>" /><input type="hidden" name="sbs_schedule_copy_from_previous_week" value="<?php echo($kw['prev_week']); ?>" /><input type="hidden" name="sbs_schedule_copy_from_previous_year" value="<?php echo($kw['prev_year']); ?>" /><input type="submit" value="Aus vorheriger Woche kopieren" /></form></div>

    	<div style="position:absolute; bottom:5px; left:50%; width:200px; text-align:left;"><form action="" method="post" id="delete_week"><input type="hidden" name="week" value="<?php echo($kw['week']); ?>" /><input type="hidden" name="sbs_schedule_delete_week" value="<?php echo($kw['week']); ?>" /><input type="hidden" name="year" value="<?php echo($kw['year']); ?>" /><input type="submit" value="Woche löschen" /></form></div>

        <div style="position:absolute; bottom:5px; right:5px; text-align:right;"><form action="" method="post"><input type="hidden" name="week" value="<?php echo($kw['next_week']); ?>" /><input type="hidden" name="year" value="<?php echo($kw['next_year']); ?>" /><input type="submit" value="Nächste Woche" /></form></div>

    </div>

    <?php } ?>

</div>





<script type="text/javascript">

jQuery('#delete_week').submit(function() {
return confirm("Bist du dir sicher, dass du diese Woche löschen möchtest?");
});


//$(".eventdiv").tooltip();
jQuery(document).ready(function() {
<?php foreach($tooltips as $id=>$tooltip) { ?>


var content = '<?php $tooltipc = $tooltip['content']; echo json_encode($tooltipc); ?>';
content = content.replace('"a','');
content = content.replace('a"','');
//hier werden die Anfuehrungszeichen die durch json_encode entstehen entfernt
var tipsotitel = '<b><?php echo $tooltip['titel']; ?></b>';
jQuery('#event_<?php echo($id); ?>').tipso({
				position: 'bottom',
				background: 'rgba(0,0,0,0.9)',
				tooltipHover: true,
				titleContent: tipsotitel,
				width: 240,
				content: content
			});








<?php } ?>
});

</script>

<div>

<script type="text/javascript">

//document.getElementById('schedule_container_main_room_x').scrollTop=280;

function closePopUpX() {

	var obj = document.getElementById('inlineContentDim');

	obj.style.display = 'none';

}



function openPopUpX() {

	var obj = document.getElementById('inlineContentDim');

	obj.style.display = 'block';
	
	


}



function closePopUpXMsg() {

	var obj = document.getElementById('inlineContentDimMsg');

	obj.style.display = 'none';

}



function openPopUpXMsg() {

	var obj = document.getElementById('inlineContentDimMsg');

	obj.style.display = 'block';
	
	

}



function closePopUpXUsr() {

	var obj = document.getElementById('inlineContentDimUsr');

	obj.style.display = 'none';

}



function openPopUpXUsr(id) {

	var sobj = document.getElementById('event_user_list_' + id);

	var dobj = document.getElementById('inlineContentDynamicUsr');

	dobj.innerHTML = sobj.innerHTML;

	var obj = document.getElementById('inlineContentDimUsr');

	obj.style.display = 'block';
	jQuery(".tipso_bubble").hide();

}



function preFillOut(day, hour, openbox) {

	var obj = document.getElementById('sbs_schedule_new_from_day');

	obj.selectedIndex=day-1;



	obj = document.getElementById('sbs_schedule_new_from_hour');

	obj.selectedIndex=hour;



	//obj = document.getElementById('description');

	//obj.innerHTML = 'TEST';



	obj = document.getElementById('sbs_schedule_new_link');

	//if(openbox) obj.click();



	openPopUpX();

}



</script>

<div id="inlineContentDim" style="width:100%; height:100%; position:fixed; top:0px; left:0px; background-color:#333333; opacity:0.95; display:none; z-index:10000;">

    <div id="inlineContent" style="display:block; position:absolute; top:50%; left:50%; transform: translate(-50%, -50%); height:auto;width:400px; border:1px solid #000000; background-color:#FFFFFF; padding:10px; z-index:5000;">

	    <div id="inlineContentClose" onclick="closePopUpX();" style="position:absolute; left:-20px; top:-20px; height:30px; width:30px; background:url(<?php echo(plugin_dir_url(__FILE__)); ?>buttons.png);" ></div>

        <?php

		if(isset($_POST['sbs_schedule_edit_item']) && is_numeric($_POST['sbs_schedule_edit_item'])) {

			$prefill=sbs_schedule::get_event_by_id($_POST['sbs_schedule_edit_item']);

			if($prefill===false || !is_array($prefill)) unset($prefill);

			echo('Editieren:');

			//echo('<pre>');

			//print_r($prefill);

			//echo('</pre>');

			?>

            <?php

		} else {

			echo('Neu:');

		}

		?>

        <form action="" method="post" name="sbs_schedule_new_form">

          <label>Von:

          <select name="sbs_schedule_new[from_day]" id="sbs_schedule_new_from_day">

          <?php for($x=1; $x<8; $x++) { ?>

              <option value="<?php echo($x); ?>"<?php if(isset($prefill[0]['EventData']['from_day']) && $prefill[0]['EventData']['from_day']==$x) echo(' selected="selected"');  ?>><?php echo(sbs_schedule::weekday($x)); ?></option>

          <?php } ?>

          </select>

          <select name="sbs_schedule_new[from_hour]" id="sbs_schedule_new_from_hour">

          <?php for($x=0; $x<24; $x++) { ?>

              <option value="<?php echo($x); ?>"<?php if(isset($prefill[0]['EventData']['from_hour']) && $prefill[0]['EventData']['from_hour']==$x) echo(' selected="selected"');  ?>><?php echo(str_pad($x, 2, '0', STR_PAD_LEFT)); ?></option>

          <?php } ?>

          </select>

          :

          <select name="sbs_schedule_new[from_minute]">

          <?php for($x=0; $x<60; $x++) { ?>

              <option value="<?php echo($x); ?>"<?php if(isset($prefill[0]['EventData']['from_minute']) && $prefill[0]['EventData']['from_minute']==$x) echo(' selected="selected"');  ?>><?php echo(str_pad($x, 2, '0', STR_PAD_LEFT)); ?></option>

          <?php } ?>

          </select>

          </label><br>



          <label>Dauer in Minuten:

          <input type="text" name="sbs_schedule_new[duration]" id="duration" value="<?php echo(@$prefill[0]['EventData']['duration']); ?>" /><br />

          </label>

          <label>Erste Anmeldung spätestens X Stunden vor Beginn:
			<?php //if(!isset($prefill[0]['EventData']['lastcall'])) $prefill[0]['EventData']['lastcall']=0; ?>
          <input type="text" name="sbs_schedule_new[lastcall]" id="lastcall" value="<?php if(!isset($prefill[0]['EventData']['lastcall'])) echo('0'); else echo(@$prefill[0]['EventData']['lastcall']); ?>" /><br />

          </label>

          <label>Titel kurz:

          <input type="text" name="sbs_schedule_new[title]" id="title" value="<?php echo(@$prefill[0]['EventData']['title']); ?>" /><br />

          </label>

          <label>Titel lang:

          <input type="text" name="sbs_schedule_new[title_long]" id="title_long" value="<?php echo(@$prefill[0]['EventData']['title_long']); ?>" /><br />

          </label>

          <label>Credits:

          <input type="text" name="sbs_schedule_new[credits]" id="credits" value="<?php echo(@$prefill[0]['EventData']['credits']); ?>" /><br />

          </label>

          <label>Beschreibung:

          <textarea name="sbs_schedule_new[description]" id="description"><?php echo(@$prefill[0]['EventData']['description']); ?></textarea><br />



          </label>

          <label>Raum:

          <select name="sbs_schedule_new[room]">

            <option value="1"<?php if(isset($prefill[0]['EventData']['room']) && $prefill[0]['EventData']['room']==1) echo(' selected="selected"');  ?>>1</option>

            <option value="2"<?php if(isset($prefill[0]['EventData']['room']) && $prefill[0]['EventData']['room']==2) echo(' selected="selected"');  ?>>2</option>

          </select>

          </label>

          <label>Plätze verfügbar:

          <select name="sbs_schedule_new[spaces]">

          <?php for($x=1; $x<60; $x++) { ?>

              <option value="<?php echo($x); ?>"<?php if(isset($prefill[0]['EventData']['spaces']) && $prefill[0]['EventData']['spaces']==$x) echo(' selected="selected"');  ?>><?php echo(str_pad($x, 2, '0', STR_PAD_LEFT)); ?></option>

          <?php } ?>

          </select>

          </label><br />

          <?php

				$groups = sbs_credits::get_groups();

				foreach($groups as $group) {

					?>

                    <label><input type="checkbox" name="sbs_schedule_new[sbs_group][<?php echo($group['ID']); ?>]" value="ok"<?php if(isset($prefill[0]['EventData']['sbs_group'][$group['ID']]) && $prefill[0]['EventData']['sbs_group'][$group['ID']]=='ok') echo(' checked="checked"'); ?> /><?php echo($group['Name']); ?>&nbsp;&nbsp;</label>

					<?php

				}

		  ?>

          <input type="hidden" name="sbs_schedule_new[id]" value="<?php echo(@$prefill[0]['ID']); ?>" />

          <input type="hidden" name="sbs_schedule_new[week]" value="<?php echo($kw['week']); ?>" />

          <input type="hidden" name="sbs_schedule_new[year]" value="<?php echo($kw['year']); ?>" /><br />
		<input type="hidden" name="week" value="<?php echo($kw['week']); ?>" />
		<input type="hidden" name="year" value="<?php echo($kw['year']); ?>" />

          <input type="submit" name="submit" value="Speichern" />

        </form>
		<br>
		<a href="https://www.urbangladiators.de/checkin/?kurs=<?php echo(@$prefill[0]['ID']); ?>" target="_blank">Check-In</a><br> 
    </div>

</div>

<div id="inlineContentDimMsg" onclick="closePopUpXMsg();" style="width:100%; height:100%; position:fixed; top:0px; left:0px; background-color:#333333; opacity:0.95; display:none; z-index:10000; color:#000000;">

    <div id="inlineContentMsg" >

	    <div id="inlineContentCloseMsg" style="position:absolute; left:-20px; top:-20px; height:30px; width:30px; background:url(<?php echo(plugin_dir_url(__FILE__)); ?>buttons.png);" onclick="closePopUpXMsg();"></div>

			<?php echo(@$_SESSION['sbs_schedule_message']); ?><br />

			<br />

            <input type="button" value="OK" onclick="closePopUpXMsg();" />

    </div>

</div>

<div id="inlineContentDimUsr" onclick="closePopUpXUsr();" style="width:100%; height:100%; position:fixed; top:0px; left:0px; background-color:#333333; opacity:0.95; display:none; z-index:10000; color:#000000;">

    <div id="inlineContentUsr"  style="display:block; position:absolute; top:50%; left:50%; margin-top:-200px; height:400px; margin-left:-100px; width:200px; border:1px solid #000000; background-color:#FFFFFF; padding:10px;">

	    <div id="inlineContentCloseUsr" style="position:absolute; left:-20px; top:-20px; height:30px; width:30px; background:url(<?php echo(plugin_dir_url(__FILE__)); ?>buttons.png);" onclick="closePopUpXUsr();"></div>

		<div id="inlineContentDynamicUsr"></div>

    </div>

</div>



<?php if(isset($_SESSION['sbs_schedule_message'])) { ?>

<script type="text/javascript">

	openPopUpXMsg();

</script>

<?php } ?>

<?php if(isset($prefill)) { ?>

<script type="text/javascript">

	openPopUpX();

</script>

<?php } ?>



<?php if(!$frontend) { ?>

<br>

<br>

<br>

<br>

<br>

<br>

<br>



<pre>

<?php

//var_dump(mail('test@vsm-dev.com', 'subject', 'message'));

//print_r(sbs_schedule::get_event_by_id(17));

//print_r($kw);

//print_r($_SERVER);

?>

</pre>

<?php } ?>
