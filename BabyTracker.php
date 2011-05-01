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

function CommonActions($action, $sqlrowid, $client)
{
	$file = get_input_option("filename");
	$babytracker_userid = BabyTracker_UserId();
	$babytracker_pwd = BabyTracker_Pwd();
	$babytracker_template_key = get_config_value("babytracker_template_key");
	$babytracker_public_spreadsheet_url = get_config_value("babytracker_public_spreadsheet_url");

	@$userid = $client["userid"];
	@$pwd = $client["pwd"];
	$title = $client["title"];
	@$key = $client["key"];
	@$spreadsheetid = $client["spreadsheetid"];
	@$worksheetid = $client["worksheetid"];
	@$dob = $client["dob"];
	$name = $client["name"];
	@$token = $client["token"];
	@$table = $client["tablename"];

    switch ($action)
    {
	case "delete_file":
		delete_output_file($file);
		break;

	case "delete_log_table":
		DeleteLogTable($key, $sqlrowid);
		break;

	case "delete_log_table_all":
		DeleteLogTable();
		break;

	case "delete_all_output_files":
		delete_all_output_files();
		break;

	case "reset_and_run":
	    ResetErrors();
		RunProcessLogTable();
		break;

	case "run":
		RunProcessLogTable();
		break;

	case "runall":
		ProcessLogTable("", "", 1);
		break;

	case "run.key":
		RunProcessLogTable($key);
		break;

	case "run.id":
		RunProcessLogTable($key, $sqlrowid);
		break;

	case "get_errors":
	    GetProcessingErrors();
	    break;

	case "reset_errors":
	    ResetErrors();
	    break;

	case "reset_key_errors":
	    ResetErrors($key);
	    break;

        case "sqlprocess":
            ProcessLogTable();
            break;

	case "addrow":
	    if (get_input_option("date") == "") error("Missing date");
	    if (get_input_option("time") == "") error("Missing time");
	    if (get_input_option("type") == "") error("Missing type");
	    if ((get_input_option("type") != "Wet Diaper") && (get_input_option("type") != "Poopy Diaper"))
	    if (get_input_option("amount") == "") error("Missing amount");
	    if (!$key) error ("Missing key");

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

	    // Client Data
	    if (!$client["tablename"]) $client["tablename"] = UserTableName($userid, $name, $token);

	    $mysql = GetMysql();
	    $row = AddRowToUserTable($mysql, $data, $client);
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

        case "stats":
            $cells = GetSpreadssetStatsCell($title, $key, "", $babytracker_userid, $babytracker_pwd);

			print("<table>");
			for ($row=1; $row <= 30; $row++)
			{
				print("<tr>");
				for ($col='A'; $col <= 'N'; $col++)
				{
					$index = "$col$row";
					$val = @$cells["$index"];
					$val = FormatCellValue($val);
					if ($row == 1)
						print("<th>$val</th>");
					else
						print("<td>$val</td>");
				}
				print("</tr>");
			}
			print("</table>");
			break;

        case "stats_col":
            $cells = GetSpreadssetStatsCell($title, $key, "", $babytracker_userid, $babytracker_pwd);
			$col = get_input_option("col");
			if (!$col)
				$col = "B";
			$col_end = get_input_option("col_end");
			if (!$col_end)
				$col_end = $col;

			print("<table class='statsTable'>");
			for ($row=1; $row <= 30; $row++)
			{
				$valName = @$cells["A$row"];
				if (IsSectionName($valName)) {
					echo "<tr><td class='statsSection' >$valName</td>";
				}
				else {
					$valName = FormatCellValue($valName);
					echo "<tr><td class='statsItem' >$valName</td>";
				}
				for ($colidx=$col; $colidx <= $col_end; $colidx++) {
					$val = FormatCellValue(@$cells["$colidx$row"]);
					if ($row == 1)
						echo "<td class='statsHeader'>$val</td>";
					else
						echo "<td class='statsData'>$val</td>";
				}
				echo "</tr>";
			}
			print("</table>");
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
		{
			if (get_input_option("name")=="") error("Missing Name");
			if (get_input_option("dob")=="") error("Missing date of Birth");
			if (get_input_option("userid")=="") error("Missing email");
			if (get_input_option("pwd")=="") error("Missing password");

			vprint("<hr><b>Add New User</b>");
			$client["tablename"] = MakeNewUserTableName($userid, $name);
			SetHtmlCookie("tablename", $client["tablename"]);

			$client["dob"] = get_input_option("dob");
			$sqlid = SetupNewUser($client);

			print("Successfully Installed the [$title] spreadsheet. key=$key;" .
				"spreadsheetid=" . $docApp->getSpreadsheetId() . "; " .
				"worksheetid=" . $docApp->getWorksheetId() . "; " .
				"sqlid=$sqlid; " .
				"token=$token; " .
                "tablename=$table; ");

			break;
		}
		break;

		case "setupspreadsheet":
		case "installspreadsheet":
		{
			if (get_input_option("name")=="") error("Missing Name");
			if (get_input_option("dob")=="") error("Missing date of Birth");
			if (get_input_option("userid")=="") error("Missing email");
			if (get_input_option("pwd")=="") error("Missing password");

			$doc = GetSpreadsheet($client, get_input_option("userid"), get_input_option("pwd"));

			$id = "";
			if (get_input_option("reuse")){
				vprint("<hr><b>OpenExisting</b>");
				$id = $doc->OpenExisting();
			}

			if ($id != "" && $key == "")
			{
				$doc->GetSpreadsheetIds();
				$key = $doc->getKey();
			}

			if ($key == "") {
				vprint("<hr><b>CopyDocument</b>");
				$key = $doc->CopyDocument($babytracker_template_key, $title);
			}

			vprint("<hr><b>AddCollaborator</b>");
			$doc->AddCollaborator($key, $babytracker_userid);

			vprint("<hr><b>Update DOB</b>");
			$docApp = GetSpreadsheet($client);
			$docApp->setWorksheet("Variables");
			$docApp->update("3", "2", get_input_option("dob"));

			vprint("<hr><b>Get ids</b>");
			$docApp->setWorksheet(BabyTracker_Data_Worksheet());
			$docApp->setWorksheetId("");
			$docApp->GetSpreadsheetIds();

			$key = $docApp->getKey();
			$spreadsheetid = $docApp->getSpreadsheetId();
			$worksheetid = $docApp->getWorksheetId();
			$client["key"] = $key;
			$client["spreadsheetid"] = $spreadsheetid;
			$client["worksheetid"] = $worksheetid;

			vprint("<hr><b>Add New User</b>");
			$client["tablename"] = MakeNewUserTableName($userid, $name);
			SetHtmlCookie("tablename", $client["tablename"]);

			$client["dob"] = get_input_option("dob");
			$sqlid = SetupNewUser($client);

			print("Successfully Installed the [$title] spreadsheet. key=$key;" .
				"spreadsheetid=" . $docApp->getSpreadsheetId() . "; " .
				"worksheetid=" . $docApp->getWorksheetId() . "; " .
				"sqlid=$sqlid; " .
				"token=$token; " .
                "tablename=$table; ");

			break;
		}
		break;

		case "authorize":
		case "zend_request_token":
			print("<a target='_blank' href='https://secure.iinet.com/joyofplaying.com/BabyTracker/BabyTracker.zend.php?verbose=1'>Authorize Baby Tracker Application</a><br/>");
			//Zend_GetSessionToken();
			break;

        case "run.background":
            curl_post_async("https://secure.iinet.com/joyofplaying.com/BabyTracker/BabyTracker.runall.background.php", 0);
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
	$key = get_input_option("key");

	$client = GetClient(0, 0, $key);
	if (!$client)
	{
		$client = array();
		read_input_option("name", $client);
		read_input_option("dob", $client);
		read_input_option("title", $client);
		read_input_option("key", $client);
		read_input_option("spreadsheetid", $client);
		read_input_option("worksheetid", $client);
		read_input_option("token", $client);
		read_input_option("userid", $client);
		read_input_option("tablename", $client);
		if (get_input_option("tablename") == "") $client["tablename"] = UserTableName($userid, $name, $client["token"]);
		if (get_input_option("title") == "") $client["title"] = "Baby $name Tracker";
		if (get_input_option("title") == "") $client["token"] = uniqid("");
	}
	read_input_option("pwd", $client);

	@$table = $client["tablename"];
	$title = $client["title"];

    if (CommonActions(get_input_option("postaction"),
					  get_input_option("sqlrowid"),
					  $client))
	{
		return;
	}

	switch(get_input_option("postaction"))
	{
        case "addspreadsheetrow":
			if (get_input_option("date") == "") error("Missing date");
			if (get_input_option("time") == "") error("Missing time");
			if (get_input_option("type") == "") error("Missing type");
			if ((get_input_option("type") != "Wet Diaper") && (get_input_option("type") != "Poopy Diaper"))
			if (get_input_option("amount") == "") error("Missing amount");
			if (get_input_option("key")=="") error ("Missing key");

			$data = array(
			  "date" => get_input_option("date"),
			  "time" => get_input_option("time"),
			  "type" => get_input_option("type"));

			read_input_option("amount", $data);
			read_input_option("description", $data);

        	SpreadsheetAdd($data, $client);

            break;

		default:
			error("Missing postaction");
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
	$babytracker_template_key = get_config_value("babytracker_template_key");
	$babytracker_public_spreadsheet_url = get_config_value("babytracker_public_spreadsheet_url");

	if (get_input_option("testaction") != "setup_system_tables" && get_input_option("postaction") != "setup_system_tables")
		$client = GetClient(get_config_value("test_userid"), get_config_value("test_name"));
	if (!$client)
	{
		$client = array();
		read_config_option("test_name", "name", $client);
		read_config_option("test_dob", "dob", $client);
		read_config_option("test_userid", "userid", $client);
		$client["title"] = "Baby " . $client["name"] . " Tracker";
		read_config_option("test_title", "title", $client);
		$client["tablename"] = MakeNewUserTableName($client["userid"], $client["name"]);
		$client["token"] = uniqid("");
	}
	read_config_option("test_pwd", "pwd", $client);

	//array_print($client);

	$name = $client["name"];
	$dob = $client["dob"];
	$userid = $client["userid"];
	$pwd = $client["pwd"];
	$title = $client["title"];
	$table = $client["tablename"];
	$key = $client["key"];
	$spreadsheetid = $client["spreadsheetid"];
	$worksheetid = $client["worksheetid"];
	$token = $client["token"];

	$sqlrowid = get_config_value("test_row_id");
    $reuse = get_config_value("test_reuse");

    vprint("testaction = " . get_input_option("testaction"));

    switch (get_input_option("testaction"))
    {
		case "post":
			CommonActions(get_input_option("postaction"), "", $client);
			break;

		case "lock_setup";
			SetupLockTable();
			break;

		case "lock_acquire";
			$lock_value = AcquireLock("log_table");
			break;

		case "lock_release";
			ReleaseLock("log_table", get_input_option("lock_value"));
			break;

		case "force_lock_release":
			ReleaseLock("log_table", "", true);
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
			SetupLockTable();
            break;

        case "sqldump":
            DumpLogTableResults();
            break;

        case "spreadsheetsetup":
	        $doc = GetSpreadsheet($client, $userid, $pwd);

	        $reuse = 1;
	        if ($reuse) {
		        $id = $doc->OpenExisting();
		        vprint("found existing spreadsheet - id=$id");
	        }

	        $newKey = $doc->CopyDocument($babytracker_template_key, $title);

	        $doc->AddCollaborator($newKey, BabyTracker_UserId());

	        $randMonth = rand(1, 12);
	        $randDay = rand(1, 28);
	        $date = "$randMonth/$randDay/2009";
	        vprint("Updating dob to $date");

			$client["key"] = $newKey;
	        $docApp = GetSpreadsheet($client, $userid , $pwd);
	        $docApp->setWorksheet("Variables");
	        $docApp->update("3", "2", $date);

	        $docApp->setWorksheet(BabyTracker_Data_Worksheet());
	        $docApp->setWorksheetId("");
	        $docApp->GetSpreadsheetIds();

	        $key = $docApp->getKey();
	        $spreadsheetid = $docApp->getSpreadsheetId();
	        $worksheetid = $docApp->getWorksheetId();
			$client["key"] = $key;
			$client["spreadsheetid"] = $spreadsheetid;
			$client["worksheetid"] = $worksheetid;

			vprint("<hr><b>Setup SQL</b>");
			SetupSystemTables();

			$sqlid = SetupNewUser($client);

			print("Successfully Installed the [$title] spreadsheet. key=$key;" .
				"spreadsheetid=" . $docApp->getSpreadsheetId() . "; " .
				"worksheetid=" . $docApp->getWorksheetId() . "; " .
				"sqlid=$sqlid;" .
				"token=$token;");

            break;

        case "spreadsheetadd":
            TestAddToSpreadsheet($client);
            break;

        case "touch_all":
            TouchAllRecords();
            break;

        case "stop_all":
            StopAllRuns();
            break;

		case "setup_user_table":
            DropTable($table);
            SetHtmlCookie("tablename", $table);
			$sqlid = SetupNewUser($client);
			break;

        case "run_sql_file":
            $filename = get_input_option("filename");
            ExecSqlFile($filename, MakeNewUserTableName($userid, $name));
            break;

        case "dump_user_table":
            $mysql = GetMysql();

            $table = UserTableName($userid, $name, $token);
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
            break;

		case "get_sheet_row":
			$doc = GetSpreadsheet($client
								  );
			$doc->setWorksheet(BabyTracker_Data_Worksheet());
			$doc->setWorksheetId($worksheetid);
			$doc->GetRowBySqlId(2007);
			break;

        default:
			$result = CommonActions(get_input_option("testaction"), $sqlrowid, $client);
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
