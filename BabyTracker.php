<?php

ignore_user_abort(1);
//echo phpinfo();

setcookie("babytracker_version", "1.1");

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

require_once("BabyTracker.output.php");
require_once("BabyTracker.spreadsheet.php");
require_once("BabyTracker.mysql.php");
require_once("BabyTracker.stats.php");
require_once("BabyTracker.process.php");

function shutdown()
{
    // This is our shutdown function, in
    // here we can do any last operations
    // before the script is complete.

	CloseMysql();
	CloseSpreadsheets();
}

register_shutdown_function('shutdown');

function error_handler($level, $message, $file, $line, $context)
{
    global $mysql;

    if ($mysql)
    {
        $mysql->query("ROLLBACK");
    }

    //Handle user errors, warnings, and notices ourself
    if($level === E_USER_ERROR || $level === E_USER_WARNING || $level === E_USER_NOTICE) {
        echo '<strong>Error:</strong> '.$message;
        return(true); //And prevent the PHP error handler from continuing
    }
    return(false); //Otherwise, use PHP's error handler
}

/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */

function CommonActions()
{
	$file = get_input_option("filename");
	$babytracker_userid = BabyTracker_UserId();
	$babytracker_pwd = BabyTracker_Pwd();
	$babytracker_template_key = get_config_value("babytracker_template_key");
	$babytracker_public_spreadsheet_url = get_config_value("babytracker_public_spreadsheet_url");
	$action = get_input_option("postaction");
	@$sqlrowid = get_input_option("sqlrowid");
	@$token = get_input_option("token");

    switch ($action)
    {
	case "delete_file":
		delete_output_file($file);
		break;

	case "delete_all_output_files":
		delete_all_output_files();
		break;

	case "addrow":
	    if (get_input_option("date") == "") error("Missing date");
	    if (get_input_option("time") == "") error("Missing time");
	    if (get_input_option("type") == "") error("Missing type");
	    if ((get_input_option("type") != "Wet Diaper") && (get_input_option("type") != "Poopy Diaper"))
	    if (get_input_option("amount") == "") error("Missing amount");
	    if ($token == "") error ("Missing token, Not signed in");

	    $data = array(
	      "date" => get_input_option("date"),
	      "time" => get_input_option("time"),
	      "type" => get_input_option("type"));

	    read_input_option("amount", $data);
	    read_input_option("description", $data);
	    read_input_option("formula", $data);
	    read_input_option("breastmilk", $data);
	    read_input_option("left", $data);
	    read_input_option("right", $data);

	    $mysql = GetMysql();
	    $row = AddRowToChildTable($mysql, $data, $token);
		$sqlrowid = $row["id"];
	    $data["sqlrowid"] = $sqlrowid;
	    $upload_rowid = AddRowToUploadTable($mysql, $data, $client);

	    print("Successfully added the data. Data: " . DataToTableRow("", $data, $row["timestamp"]));

		DumpQueryResults($mysql->query("select * from `" . $client['tablename'] . "` where `id`='" . $data["sqlrowid"] . "'"));
	    break;

	case "updaterow":
	    if (get_input_option("sqlrowid") == "") error("Missing sqlrowid");
	    if (!$key) error ("Missing key");

	    $data = array(
	      "sqlrowid" => get_input_option("sqlrowid"));

	    read_input_option("date", $data);
	    read_input_option("time", $data);
	    read_input_option("type", $data);
	    read_input_option("amount", $data);
	    read_input_option("description", $data);
	    read_input_option("formula", $data);
	    read_input_option("breastmilk", $data);
	    read_input_option("left", $data);
	    read_input_option("right", $data);

	    // Client Data
	    if (!$client["tablename"]) $client["tablename"] = UserTableName($userid, $name, $token);

	    $mysql = GetMysql();
	    $upload_rowid = AddUpdateToUploadTable($mysql, $data, $client);
	    $row = UpdateUserTableRow($mysql, $data, $client);

	    print("Successfully added the data. Data: " . DataToTableRow("", $data, $row["timestamp"]));

		//DumpQueryResults($mysql->query("select * from `" . $client['tablename'] . "` where `id`='" . $data["sqlrowid"] . "'"));

	    break;

	case "deleterow":
	    if (get_input_option("sqlrowid") == "") error("Missing sqlrowid");
	    if (!$key) error ("Missing key");

	    $data = array(
	      "sqlrowid" => get_input_option("sqlrowid"));

	    // Client Data
	    if (!$client["tablename"]) $client["tablename"] = UserTableName($userid, $name, $token);

	    $mysql = GetMysql();
	    $row = DeleteUserTableRow($mysql, $data["sqlrowid"], $client);
	    $upload_rowid = AddDeleteToUploadTable($mysql, $data, $client);

	    print("Successfully deleted the data. Data: " . DataToTableRow("", $data, $row["timestamp"]));

		//DumpQueryResults($mysql->query("select * from `" . $client['tablename'] . "` where `id`='" . $data["sqlrowid"] . "'"));

	    break;

        case "stats_sql":
			DisplaySqlStats($client);
            break;

        case "stats_sql_col":
            $item = get_input_option("stats_item");
            $day_max_delta = get_input_option("day_max_delta");
            $day_min_delta = get_input_option("day_min_delta");
			DisplaySqlStats_Col($client, $item, $day_max_delta, $day_min_delta);
            break;

        case "stats_counts":
			DisplaySqlStats_Counts($client, $item, $day_max_delta, $day_min_delta);
            break;

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
		{
			if (get_input_option("username")=="") error("Missing User Name");
			if (get_input_option("name")=="") error("Missing Child Name");
			if (get_input_option("dob")=="") error("Missing date of Birth");
			if (get_input_option("userid")=="") error("Missing email");
			if (get_input_option("pwd")=="") error("Missing password");

			$user = RegisterUser(get_input_option("username"), get_input_option("userid"), get_input_option("pwd"));
			$child = RegisterChild(get_input_option("name"), get_input_option("dob"), $user);
			$session = RegisterSession($user, $child);
			$token = $session["token"];

			SetHtmlCookie("token", $token);

			print("Successfully setup user.<br/>token=$token;<br/>");
			break;
		}
		break;

        default:
            return false; // error
    }

    return true;
}

function PostProcessing()
{
	$babytracker_userid = BabyTracker_UserId();
	$babytracker_pwd = BabyTracker_Pwd();
	$babytracker_template_key = get_config_value("babytracker_template_key");
	$babytracker_public_spreadsheet_url = get_config_value("babytracker_public_spreadsheet_url");

	$name = get_input_option("name");
	$userid = get_input_option("userid");
	$token = get_input_option("token");
	read_input_option("pwd", $client);

	@$table = $client["tablename"];
	$title = $client["title"];

    if (CommonActions(get_input_option("postaction"),
					  get_input_option("sqlrowid"),
					  $client))
	{
		return;
	}

	$postaction = get_input_option("postaction");
	switch($postaction)
	{
		default:
			error("Missing postaction $postaction");
			break;
	}
}

/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */

function TestMe()
{
	$babytracker_userid = BabyTracker_UserId();
	$babytracker_pwd = BabyTracker_Pwd();

	read_config_option("test_username", "username", $_POST);
	read_config_option("test_name", "name", $_POST);
	read_config_option("test_dob", "dob", $_POST);
	read_config_option("test_userid", "userid", $_POST);
	read_config_option("test_pwd", "pwd", $_POST);
	read_config_option("test_row_id", "sqlrowid", $_POST);
	read_config_option("testaction", "postaction", $_POST);
	$_POST["postaction"] = get_input_option("testaction");
	$token = $_COOKIE["BabyTracker_token"];;
	$_POST["token"] = $token;

    vprint("testaction = " . get_input_option("testaction"));
	//varray_print($_POST);
	//varray_print($_GET);
	//varray_print($_COOKIE);

    switch (get_input_option("testaction"))
    {
		case "post":
			CommonActions();
			break;

		case "runex":
			//$file = $_SERVER['SCRIPT_FILENAME'];
            $file = "BabyTracker.php";
			$path = dirname($_SERVER['SCRIPT_FILENAME']);
			$idx=0;

            $count = 0;
            while ($count < 1) // UNDONE:
            {
                $count++;
			    $idx++;
                vprint("exec($file?testaction='runall'&output_file=runex.$idx.htm&ignore=100$idx)");
                $result = exec("$file?testaction='runall'&output_file=runex.$idx.htm&ignore=100$idx");
				vprint("exec result = [$result]");
                $result = exec("php runex.php");
				vprint("exec result = [$result]");
            }
			break;

        case "sqladd":
            TestAddRowToLogTable();
            break;

        case "sqladdex":
			$max = rand(10, 200);
			for ($idx=0; $idx < $max; $idx++)
				TestAddRowToLogTable();
            break;

        case "sqlsetup":
        case "setuplogtable":
            SetupSystemTables();
            break;

        case "sqldump":
            DumpLogTableResults();
            break;

        case "touch_all":
            TouchAllRecords();
            break;

        case "stop_all":
            StopAllRuns();
            break;

        case "run_sql_file":
            $filename = get_input_option("filename");
            ExecSqlFile($filename, MakeNewUserTableName($userid, $name));
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

            vprint("Missing testaction");
            vprint("valid testaction:");
            vprint("------- run");
            vprint("------- sqlprocess");
            vprint("------- sqlsetup");
            vprint("------- sqladd");
            vprint("------- setuplogtable");
            vprint("------- sqldump");
            vprint("------- spreadsheetsetup");
            vprint("------- spreadsheetadd");

			varray_print($_POST);
			varray_print($_GET);
            break;
    }
}

/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */

if (get_input_option("testaction"))
    TestMe();
else
    PostProcessing();

flush_buffers();

LogCommandLine("END__:");
vprint("<strong>complete</strong>");

?>
