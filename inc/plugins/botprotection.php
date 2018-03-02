<?php
/**
 * Bot Protection Plugin for MyBB 1.8
 * By Brian.
 *
 * Website: https://community.mybb.com/user-115119.html
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("member_register_start", "botprotection_printcode");
$plugins->add_hook("member_do_register_start", "botprotection_remembercode");
$plugins->add_hook("datahandler_user_validate", "botprotection_checkcode");

function botprotection_info()
{
	global $lang;
	$lang->load("forum_botprotection", false, true);
	return array(
		"name"		=> $lang->botprotection_title,
		"description"	=> $lang->botprotection_desc,
		'website'	=> 'https://community.mybb.com/user-115119.html',
		"author"	=> "Brian.",
		"authorsite"	=> "https://community.mybb.com/user-115119.html",
		"version"	=> "1.0",
		"compatibility" => "18*",
	);
}


function botprotection_install()
{
	global $db, $mybb, $lang;

	// DELETE ALL SETTINGS TO AVOID DUPLICATES
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN(
		'botprotectionswitch',
		'botprotectioncode',
		'botprotectionfield'
		)");
	$db->delete_query("settinggroups", "name = 'botprevention'");
	$db->delete_query("settinggroups", "name = 'bbotprotection'");

	$query = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query, "rows");

	$insertarray = array(
		'name' => 'botprotection',
		'title' => 'Bot Protection',
		'description' => 'Options to configure Bot Protection',
		'disporder' => $rows+1,
		'isdefault' => 0
	);
	$group['gid'] = $db->insert_query("settinggroups", $insertarray);
	$mybb->akismet_insert_gid = $group['gid'];

	$insertarray = array(
		'name' => 'botprotectionswitch',
		'title' => 'Bot Protection Main Switch',
		'description' => 'Turns on or off the protection.',
		'optionscode' => 'onoff',
		'value' => 1,
		'disporder' => 0,
		'gid' => $group['gid']
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'botprotectioncode',
		'title' => 'Code used for validation.',
		'description' => $db->escape_string('This code can be defined freely. This is to prevent Bots from automatically submitting the correct value without parsing JS.'),
		'optionscode' => 'text',
		'value' => '1337',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'botprotectionfield',
		'title' => 'Fieldname used for validation.',
		'description' => $db->escape_string('Fieldname in registration form. Should not conflict with other fieldnames, and should only consist of a-z and 0-9. Can be changed for more security.'),
		'optionscode' => 'text',
		'value' => 'messagemode',
		'disporder' => 1,
		'gid' => $group['gid']
	);
	$db->insert_query("settings", $insertarray);
	rebuild_settings();
}

function botprotection_is_installed()
{
	global $db;

	$query = $db->simple_select("settings", "COUNT(*) as rows", "name like 'botprotectionswitch'");
	$rows = $db->fetch_field($query, "rows");
	if ($rows > 0)
	{
		return true;
	}
	return false;
}

function botprotection_activate()
{
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_register", "#".preg_quote('{$botprotection_script}')."#i", '', 0);
	find_replace_templatesets("member_register", "#".preg_quote('{$requiredfields}')."#i", '{$botprotection_script}{$requiredfields}');
}

function botprotection_deactivate()
{
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_register", "#".preg_quote('{$botprotection_script}')."#i", '', 0);
}

function botprotection_uninstall()
{
	global $db;

	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN(
		'botprotectionswitch',
		'botprotectioncode',
		'botprotectionfield'
		)");
	$db->delete_query("settinggroups", "name = 'botprotection'");
	rebuild_settings();
}

/**
 * This function creates some javascript code and the form field, which will be updated via javascript
 *
 */
function botprotection_printcode() {
	global $mybb, $botprotection_script, $lang;
	if($mybb->settings['botprotectionswitch'] == 1) {
		$randomcode = array();
		// we create 16 random strings with a length between 6 and 14, just to create 'some' randomness within the js code
		for ($rcode = 0; $rcode <= 16; $rcode++)
		{
			do {
				$length = rand(6,14);
				$temp = '';
				for ($i = 0; $i < $length; $i++)
				{
					$temp .= chr(97+rand(0,25));
				}
			}
			while(in_array($temp,$randomcode));
			$randomcode[$rcode] = $temp;
		}

		// $e for $expression, contains several lines of js statements
		$e = array();

		/**
		 * to SEE what all this does, just change this 'eval' to 'alert', you will get 4 alerts similar to this:
		 *
		 * var select_field  = document.getElementById( 'SETTING_FIELD');
		 * var new_option    = new Option( 'SETTING_CODE', 'SETTING_CODE',true ,true);
		 * select_field.options [  selectfield.length  ]= new_option;
		 * select_field.selectedIndex=1;
		 *
		 * to check that there is no bad boo in this, you may even change the 'eval' at the end botprot_jsshredder()
		 */
		$e[] 	= 'var '.$randomcode[1].' = eval;';
		$e[] 	= 'var '.$randomcode[2]." = 'document';";
		$e[] 	= 'var '.$randomcode[3]." = 'getElementById';";
		$e[]	= 'var '.$randomcode[4]." = '".$mybb->settings['botprotectionfield']."';";
		$e[]	= 'var '.$randomcode[5]." = 'var ';";
		// r9 	=  var containing select_field
		$e[]	= 'var '.$randomcode[6]." = '".$randomcode[9]."';";
		$e[]	= 'var '.$randomcode[7]." = 'new Option';";
		$e[]	= 'var '.$randomcode[8]." = '".addslashes($mybb->settings['botprotectioncode'])."';";
		// r11  =  var containing new option
		$e[]	= 'var '.$randomcode[10]." = '".$randomcode[11]."';";
		$e[]	= 'var '.$randomcode[12]." = true;";
		$e[]	= 'var '.$randomcode[13]." = 'options';";
		$e[]	= 'var '.$randomcode[14]." = 'selectedIndex';";
		$e[]	= 'var '.$randomcode[16]." = 'length';";


		sort($e);
		//        eval            (        'var '    +    select_field  +'='+ 'document'       +"."+ 'getElementById' +"( '"+  SETTING_FIELD   +" ');")
		$e[] 	= $randomcode[1]."(".$randomcode[5].'+'.$randomcode[6]."+'='+".$randomcode[2].'+"."+'.$randomcode[3].'+"(\'"+'.$randomcode[4].'+"\');");';
		//        eval            (   'var '     +      new_option         =  'new Option'      +"( '"+ SETTING_CODE     +" ', '"+ SETTING_CODE     +" ',"+ true              +","+ true              +");")
		$e[] 	= $randomcode[1]."(".$randomcode[5].'+'.$randomcode[10]."+'='+".$randomcode[7].'+"(\'"+'.$randomcode[8].'+"\',\'"+'.$randomcode[8].'+"\',"+'.$randomcode[12].'+","+'.$randomcode[12].'+");");';
		//        eval            (  select_field    +"."+    'options'      +"["+    selectfield   +"."+ 'length'          +"]   ="+    new_option     +";");
		$e[]	= $randomcode[1]."(".$randomcode[6].'+"."+'.$randomcode[13].'+"["+'.$randomcode[6].'+"."+'.$randomcode[16].'+"]"+"="+'.$randomcode[10].'+";");';
		//        eval            (  select_field    +"."+  'selectedIndex'  +"=   1;");
		$e[]	= $randomcode[1]."(".$randomcode[6].'+"."+'.$randomcode[14].'+"="+"1;");';


		$lang->load("botprotection", false, true);
		// we create the select field, with some random style class
		$botprotection_script = "\n".
				'<style>.'.$randomcode[15].' {display:none;}</style>'."\n".
				'<select id="'.$mybb->settings['botprotectionfield'].'" name="'.$mybb->settings['botprotectionfield'].'" class="'.$randomcode[15].'"><option value="'.$randomcode[0].'">'.$randomcode[0].'</option></select>'."\n".
				'<script language="javascript" type="text/javascript">'."\n".
				'<!--'."\n";

		// we add all javascript statements as shreddered eval statements
		for ($i = 0; $i < count($e); $i++)
		{
			$botprotection_script .= botprot_jsshredder($e[$i])."\n";
		}

		// and we close the javascript.
		$botprotection_script .=
				'-->'."\n".
				'</script>'."\n".
				'<noscript><br />'."\n".
				'<fieldset><legend><strong>'.$lang->botprotection_error_jsdisabled_title.'</strong></legend>'."\n".
				$lang->botprotection_error_jsdisabled.
				'</fieldset>'."\n".
				'</noscript>'."\n";




	}
}

/**
 * this function sets an input value, so we will know later on that we have to check the protection hidden field
 */
function botprotection_remembercode() {
	global $mybb;
	if($mybb->settings['botprotectionswitch'] == 1)
	{
		// we set an input value to detect a user registration later on
		$mybb->input['botprotectioncheck'] = 1;
	}
}

/**
 * we check if we have to check if the hidden value is set properly, using the $mybb->input['botprotectioncheck']
 * if the value is invalid, we set an error within the user object
 *
 * @param userobject $user
 */
function botprotection_checkcode(&$user)
{
	global $mybb, $lang;
	if($mybb->settings['botprotectionswitch'] == 1)
	{
		// if we created the user registration detection, check it!
		if (isset($mybb->input['botprotectioncheck']) && $mybb->input[$mybb->settings['botprotectionfield']] != $mybb->settings['botprotectioncode'])
		{
			$lang->load("jsbotprotection", false, true);
			$user->set_error($lang->botprotection_error_jsdisabled);
		}
	}
}

/**
 * below are functions only used within the plugin
 */

/**
 * quote the string with single quotes ' and escape included single quotes \'
 *
 * @param string $string
 * @return string
 */
function botprot_singlequote($string)
{
	return "'".str_replace("'","\\'",$string)."'";
}
/**
 * quote the string with double quotes " and escape included double quotes \"
 *
 * @param string $string
 * @return string
 */
function botprot_doublequote($string)
{
	return '"'.str_replace('"','\\"',$string).'"';
}

/**
 * split up a javascript statement into random parts, and run it via "eval".
 * should make any approaches of parsing this with regular expressions virtually impossible.
 * only way to get a "clean" result should be real js parsing.
 *
 * @param string javascript code
 * @return string
 */
function botprot_jsshredder($string)
{
	$parts = array();
	$oldstring = $string;
	while (strlen($oldstring))
	{
		$nextlen = rand(1,strlen($oldstring));
		$partstring = substr($oldstring,0,$nextlen);
		$oldstring = substr($oldstring, $nextlen);
		switch(rand(0,1))
		{
			case 1:
				$parts[] = botprot_singlequote($partstring);
				break;
			case 0:
				$parts[] = botprot_doublequote($partstring);
				break;
		}
	}
	$newstring = 'eval('.implode('+', $parts).");";
	return $newstring;
}
?>
