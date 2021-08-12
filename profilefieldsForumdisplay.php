<?php

/**
 * Profilfelder in Forumdisplay anzeigen
 *   
 */
//error_reporting ( -1 );
//ini_set ( 'display_errors', true );

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
	
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
$plugins->add_hook('forumdisplay_thread', 'profilefieldsForumdisplay_showFields');


function profilefieldsForumdisplay_info()
{
  return array(
    "name" => "Profilfelder in Themenübersicht",
    "description" => "Zeigt Profilfelder in bestimmten Foren an. ",
    "author" => "saen",
	"authorsite" => "https://github.com/saen91",
    "version" => "1.0",
    "compatibility" => "18*"
  );
}

function profilefieldsForumdisplay_install()
{
	global $db, $mybb;
		
	// Einstellungen
	$setting_group = array (
		'name' => 'profilefieldsforumdisplay',
		'title' => 'Profilfelder in Themenübersicht',
		'description' => 'Einstellungen für das Profilfelder in Themenübersichts Plugin',
		'isdefault' => 0
		);
		
	$gid = $db->insert_query("settinggroups", $setting_group);
	
	$setting_array = [
		'profilefieldsforumdisplay_forum' => [
			'title' => 'Foren',
			'description' => 'In welchen Foren soll die Anzeige ausgeführt werden?',
			'optionscode' => 'forumselect',
			'value' => -1,
			'disporder' => 1
		],
	];
	
	//in DB hinzufügen	
	foreach ($setting_array as $name => $setting) {
		
		$setting['name'] = $name;
		$setting['gid'] = $gid;
		
		$db->insert_query('settings', $setting);
	}
	
	
	//CSS eingeben
	$css = array (
		'name' => 'threadprofilefields.css',
		'tid' => 1,
		'attachedto' => '',
		"stylesheet" =>	'
			.cdetails {
				margin: 2px 10px;
				text-transform: uppercase;
			}',
		'cachefile' => 'threadprofilefields.css',
		'lastmodified' => time ()
	);
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

	$sid = $db->insert_query("themestylesheets", $css);
	$db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=".$sid), "sid = '".$sid."'", 1);
	
	$tids = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($tids)) {
		update_theme_stylesheet_list($theme['tid']);
	}
		
	//Nicht vergessen!
	rebuild_settings();
		
}


//Hier fragen wir ab, ob das Plugin schon installiert ist.
function profilefieldsForumdisplay_is_installed()
{
	global $mybb;
    return isset($mybb->settings['profilefieldsforumdisplay_forum']) ? true : false;
	
}


//Die Deinstallation
function profilefieldsForumdisplay_uninstall()
{
	global $db;
    //Einstellungen löschen
	$db->delete_query('settings', "name LIKE 'profilefieldsforumdisplay_%'");
    $db->delete_query('settinggroups', "name = 'profilefieldsforumdisplay'");
  
    rebuild_settings();
}

//Hier wird das Plugin aktiviert. Ich werfe hier immer die Variablen rein, die in Templates eingefügt werden müssen
function profilefieldsForumdisplay_activate()
{
  
	include MYBB_ROOT . "/inc/adminfunctions_templates.php";
	find_replace_templatesets("forumdisplay_thread", "#" . preg_quote('{$thread[\'profilelink\']}') . "#i", '{$charfield}{$thread[\'profilelink\']}');

}

//Hier wird das Plugin deaktiviert. -> Variablen aus Templates wieder löschen
function profilefieldsForumdisplay_deactivate()
{

  include MYBB_ROOT . "/inc/adminfunctions_templates.php";
  find_replace_templatesets("forumdisplay_thread", "#" . preg_quote('{$charfield}') . "#i", '', 0);

}

$plugins->add_hook("forumdisplay_thread", "profilefieldsForumdisplay_showFields");
function profilefieldsForumdisplay_showFields() {
	
	global $thread, $mybb, $db, $get_authorfields, $charfield; 
	$profilefieldsfdFid = $mybb->settings['profilefieldsforumdisplay_forum'];
	
	
	//einfügen von Infos
	$profilefieldsfdFid = ",".$profilefieldsfdFid.",";
 	$fid = $mybb->input['fid'];
	if(preg_match("/,{$fid},/i", $profilefieldsfdFid)) {
		
		
		$author = $thread['uid'];
		
		$get_authorfields = $db->simple_select("userfields","*","ufid= {$author}");
		$get_authorfields = $db->fetch_array($get_authorfields);
		
		
		//AB HIER MÜSST IHR SELBST ANPASSEN!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		
		
		//HIER WIRD JETZT DAS GETEILTE FELD BLUTSTATUS UND ALTER ZUSAMMENGEFASST
		//Hier verwende ich Profilfelder in einer Variable, die sich immer wieder ähneln 
		$sharedfields = $get_authorfields['fid43']." Jahre | ". $get_authorfields['fid5']." | ";
		
		//IN $get_authorfields['fidxx'] bei immer eure fid eingeben. 
		
//SCHÜLER
		//VERTRAUENSSCHÜLER
		if($get_authorfields['fid20']=="Ja") {
			$charfield = "<div class=\"cdetails\">".$sharedfields. $get_authorfields['fid27'] . " | Vertrauensschüler:in </div>"  ;}
		//SCHULSPRECHER 
		elseif ($get_authorfields['fid22']=="Ja") {
			$charfield = "<div class=\"cdetails\">".$sharedfields. $get_authorfields['fid27'] . " | Schulsprecher:in </div>"  ;} 
		//QUIDDITCHKAPITÄN 
		elseif ($get_authorfields['fid15']=="Ja") {
			$charfield = "<div class=\"cdetails\">".$sharedfields. $get_authorfields['fid27'] . " | Quidditchkapitän:in </div>"  ;} 
		
		//SCHÜLER && ALLE AUSSCHLIESSEN (FÜR DIE NÄCHSTEN ABSÄTZE) DIE IM JAHRGANG KEINE AUSWAHL ODER NICHTS DRINNE HABEN
		elseif ($get_authorfields['fid27'] != 'keine Auswahl' AND $get_authorfields['fid27'] != '') {
			$charfield = "<div class=\"cdetails\">".$sharedfields. $get_authorfields['fid27'] . "</div>"  ;} 
		
		//HOGWARTS!!!!!!!!!!!!!!!!!!!		
		//SCHULLEITUNG
		elseif ($get_authorfields['fid28']== "Schulleiter" AND $fid=='26') {			
			$charfield = "<div class=\"cdetails\">".$sharedfields. $get_authorfields['fid28']  ."</div>";}
		//STELLV. SCHULLEITUNG
		elseif ($get_authorfields['fid28']== "Stellvertretender Schulleiter" AND $fid=='26') {			
			$charfield = "<div class=\"cdetails\">".$sharedfields. $get_authorfields['fid28']  ."</div>";}
		//HAUSLEHRER 
		elseif ($get_authorfields['fid24']== "Gryffindor" OR $get_authorfields['fid24']== "Ravenclaw" OR $get_authorfields['fid24']== "Slytherin" OR $get_authorfields['fid24']== "Hufflepuff" AND $fid=='26') {			
			$charfield = "<div class=\"cdetails\">".$sharedfields. " Professor:in | Fach: ". $get_authorfields['fid11']  ." | Hauslehrer:in  ". $get_authorfields['fid24']  ." </div>";}	
   		//LEHRERREF
		elseif ($get_authorfields['fid46']== "Ja" AND $fid=='26') {			
			$charfield = "<div class=\"cdetails\">".$sharedfields. " Referendar:in | Fach: ". $get_authorfields['fid11']  ."</div>";}
		//LEHRER
		elseif ($get_authorfields['fid46']== "Nein" AND $fid=='26') {			
			$charfield = "<div class=\"cdetails\">".$sharedfields. " Professor:in |  Fach: ". $get_authorfields['fid11']  ."</div>";}
		//SONSTIGE
		elseif ($get_authorfields['fid47']== "Ja" AND $fid=='26') {			
			$charfield = "<div class=\"cdetails\">".$sharedfields. $get_authorfields['fid35']  ."</div>";}
		
	}
	
}
