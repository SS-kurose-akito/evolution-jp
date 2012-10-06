<?php
session_start();

$host = $_POST['host'];
$uid  = $_POST['uid'];
$pwd  = $_POST['pwd'];
$installMode = $_POST['installMode'];

require_once('../manager/includes/default.config.php');
require_once('functions.php');
$language = getOption('install_language');
includeLang('japanese-utf8');

$output = $_lang['status_checking_database'];

if (!$conn = @ mysql_connect($host, $uid, $pwd))
{
	$output .= span_fail($_lang['status_failed']);
}
else
{
	$dbase                      = getOption('dbase');
	$table_prefix               = getOption('table_prefix');
	$database_collation         = getOption('database_collation');
	$database_connection_method = getOption('database_connection_method');
	
	if(get_magic_quotes_gpc())
	{
		$dbase                      = stripslashes($dbase);
		$table_prefix               = stripslashes($table_prefix);
		$database_collation         = stripslashes($database_collation);
		$database_connection_method = stripslashes($database_connection_method);
	}
	$dbase                      = modx_escape($dbase);
	$table_prefix               = modx_escape($table_prefix);
	$database_collation         = modx_escape($database_collation);
	$database_connection_method = modx_escape($database_connection_method);
	$tbl_site_content = "{$dbase}.`{$table_prefix}site_content`";
	
	if (!@ mysql_select_db($dbase, $conn))
	{
		// create database
		
		if(isset($_POST['database_connection_charset'])) setOption('database_connection_charset',$database_connection_charset);
		$database_connection_charset = getOption('database_connection_charset');
		
		if (function_exists('mysql_set_charset'))
		{
			mysql_set_charset($database_connection_charset);
		}
		$query = "CREATE DATABASE `{$dbase}` CHARACTER SET {$database_connection_charset} COLLATE {$database_collation}";
		
		if(!@ mysql_query($query)) $output .= span_fail($query.$_lang['status_failed_could_not_create_database']);
		else                       $output .= span_pass($_lang['status_passed_database_created']);
	}
	elseif(($installMode == 0) && (@ mysql_query("SELECT COUNT(id) FROM {$tbl_site_content}")))
		$output .= span_fail($_lang['status_failed_table_prefix_already_in_use']);
	else
		$output .= span_pass($_lang['status_passed']);
		
	setOption('dbase',$dbase);
	setOption('table_prefix',$table_prefix);
}

echo $output;

function span_pass($str)
{
	return '<span id="database_pass" style="color:#388000;">' . $str . '</span>';
}

function span_fail($str)
{
	return '<span id="database_fail" style="color:#FF0000;">' . $str . '</span>';
}
