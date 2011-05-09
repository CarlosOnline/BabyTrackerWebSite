<?php

ignore_user_abort(1);
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set("display_errors", 1);

session_start();

require_once("BabyTracker.output.php");
require_once("BabyTracker.mysql.php");
require_once("BabyTracker.stats.php");
require_once("BabyTracker.process.php");
require_once('recaptchalib.php');

function shutdown()
{
    // This is our shutdown function, in
    // here we can do any last operations
    // before the script is complete.

	CloseMysql();
}

register_shutdown_function('shutdown');

function error_handler($level, $message, $file, $line, $context)
{
    global $mysql;

    if ($mysql)
    {
        $mysql->query('ROLLBACK');
    }

    //Handle user errors, warnings, and notices ourself
    if($level === E_USER_ERROR || $level === E_USER_WARNING || $level === E_USER_NOTICE) {
        echo '<strong>Error:</strong> '.$message;
        return(true); //And prevent the PHP error handler from continuing
    }
    return(false); //Otherwise, use PHP's error handler
}

function CommonActions()
{
	$file = get_input_text('filename');
	$action = get_input_word('postaction');
	@$sqlrowid = get_input_number('sqlrowid');
	@$token = $_COOKIE['token'];

    switch ($action)
    {
	case "delete_file":
		delete_output_file($file);
		break;

	case "delete_all_output_files":
		delete_all_output_files();
		break;

	case 'addrow':
	    if (get_input_date('date') == "") error('Missing date');
	    if (get_input_time('time') == "") error('Missing time');
	    if (get_input_words('type') == "") error('Missing type');
	    if ((get_input_words('type') != 'Food') && (get_input_words('type') != 'Wet Diaper') && (get_input_words('type') != 'Poopy Diaper'))
			if (get_input_decimal('amount') == "") error('Missing amount');
	    if ($token == "") error ("Missing token, Not signed in");

	    $data = array(
	      'date' => get_input_date('date'),
	      'time' => get_input_time('time'),
	      'type' => get_input_words('type'));

	    read_input_option('amount', $data, 'decimal');
	    read_input_option('description', $data, 'text');

	    AddRowToChildTable($data, $token);
		success_quiet(ChildTableResults($token));
	    break;

	case 'updaterow':
	    if (get_input_number('sqlrowid') == "") error('Missing sqlrowid');

	    $data = array(
	      'sqlrowid' => get_input_number('sqlrowid'));

	    read_input_option('date', $data, 'date');
	    read_input_option('time', $data, 'time');
	    read_input_option('type', $data, 'words');
	    read_input_option('amount', $data, 'decimal');
	    read_input_option('description', $data, 'text');

	    UpdateChildTableRow($data, $token);
		success_quiet(ChildTableResults($token));
	    break;

	case 'deleterow':
	    if (get_input_int('sqlrowid') == "") error('Missing sqlrowid');
	    $data = array(
	      'sqlrowid' => get_input_int('sqlrowid'));

	    DeleteChildTableRow($data['sqlrowid'], $token);
		success_quiet(ChildTableResults($token));
	    break;

	case "last_rows":
		success_quiet(ChildTableResults($token));
		break;

	case "stats_sql":
		DisplaySqlStats($client);
		break;

	case "stats_sql_col":
		$item = get_input_word("stats_item");
		$day_max_delta = get_input_int("day_max_delta");
		$day_min_delta = get_input_int("day_min_delta");
		DisplaySqlStats_Col($client, $item, $day_max_delta, $day_min_delta);
		break;

	case "stats_counts":
		DisplaySqlStats_Counts($client, $item, $day_max_delta, $day_min_delta);
		break;

	case 'sqlsetup':
	case 'setuplogtable':
	case "setup_system_tables":
		SetupSystemTables();
		break;

	case "delete_system_tables":
		DeleteSystemTables();
		break;

	case "delete_reg_table":
		$mysql = GetMysql();
		$sql = "delete from `" . get_config_value("registered_users_table_name") . "` where `userid`='$userid'";
		$mysql->query($sql);
		break;

	case "setup_user":
	case "setup_new_user":
		if (get_input_text('username')=="") error('Missing User Name');
		if (get_input_text('childname')=="") error('Missing Child Name');
		if (get_input_date('dob')=="") error('Missing date of Birth');
		if (get_input_email('userid')=="") error('Missing email');
		if (get_input_pwd('pwd')=="") error('Missing password');

		$private_captcha_key = get_config_value('private_captcha_key');
		$resp = recaptcha_check_answer($private_captcha_key,
									  $_SERVER["REMOTE_ADDR"],
									  get_input_text('recaptcha_challenge_field'),
									  get_input_text('recaptcha_response_field'));
/*
		if (!$resp->is_valid)
		  error("The reCAPTCHA wasn't entered correctly. Go back and try it again." .
			   "(reCAPTCHA said: " . $resp->error . ")");
*/
		$user = RegisterUser(get_input_text('username'), get_input_email('userid'), get_input_pwd('pwd'));
		$child = RegisterChild(get_input_text('childname'), get_input_date('dob'), $user);
		$session = RegisterSession($user, $child);
		$token = $session['token'];

		SetHtmlCookie('token', $token);

		success('Successfully registered baby ' . get_input_text('childname'));
		break;

	case 'login':
	case "login_user":
		if (get_input_text('childname')=="") error('Missing Child Name');
		if (get_input_email('userid')=="") error('Missing email');
		if (get_input_pwd('pwd')=="") error('Missing password');

		$user = LoginUser(get_input_email('userid'), get_input_pwd('pwd'));
		$child = RegisterChild(get_input_text('childname'), null, $user);
		$session = RegisterSession($user, $child);
		$token = $session['token'];

		SetHtmlCookie('token', $token);

		success('Successfully logged in baby ' . get_input_text('childname'));
		break;

	default:
		return false; // error
    }

    return true;
}

function PostProcessing()
{
    if (CommonActions())
	{
		return;
	}

	$postaction = get_input_words('postaction');
	switch($postaction)
	{
		default:
			error("Missing postaction $postaction");
			break;
	}
}

function TestMe()
{
	read_config_option('testaction', 'postaction', $_POST);

	$_POST['postaction'] = get_input_words('testaction');
	@$token = $_COOKIE['token'];

    vprint("testaction = " . get_input_words('testaction'));
	//varray_print($_POST);
	//varray_print($_GET);
	//varray_print($_COOKIE);

    switch (get_input_words('testaction'))
    {
		case 'post':
			CommonActions();
			break;

        case "get_data_rows":
            $mysql = GetMysql();
            $table = GetChildTableName($token);
	        $results = $mysql->query("select * from $table order by id desc LIMIT 50");
			$html = ResultsToTable($mysql, $results);
			vprint($html);
            break;

        case "dump_user_table":
            $mysql = GetMysql();

            $table = GetChildTableName($token);
	        $results = $mysql->query("select * from $table order by id desc LIMIT 500");
			$html = ResultsToTable($mysql, $results);
			vprint($html);
            break;

        case "dump_reg_table":
            $mysql = GetMysql();

        	$table = get_config_value("registered_users_table_name");
	        $results = $mysql->query("select * from $table LIMIT 100");
			$html = ResultsToTable($mysql, $results);
			vprint($html);

        	$table = get_config_value("registered_children_table_name");
	        $results = $mysql->query("select * from $table LIMIT 100");
			$html = ResultsToTable($mysql, $results);
			vprint($html);

        	$table = get_config_value("registered_sessions_table_name");
	        $results = $mysql->query("select * from $table LIMIT 100");
			$html = ResultsToTable($mysql, $results);
			vprint($html);
            break;

        default:
			$result = CommonActions();
			if ($result === true)
				return; // completed successfully

			varray_print($_POST);
			varray_print($_GET);
            error('Missing testaction');
            break;
    }
}

varray_print($_GET);
varray_print($_POST);

if (get_input_words('testaction'))
	TestMe();
else
	PostProcessing();

flush_buffers();

LogCommandLine("END__:");
vprint("<strong>complete</strong>");

?>
