<?php

echo $title_test;

function exec_enabled() {
  $disabled = explode(', ', ini_get('disable_functions'));
  return !in_array('exec', $disabled);
}


$path = dirname($_SERVER['SCRIPT_FILENAME']);
$filename = "$path/test.output.txt";
$fp = fopen($filename, "a");

echo exec("php foo.php");
echo "completed";

exit;

//echo phpinfo();

error_reporting(E_ALL);
ini_set('display_errors','On');

require_once("output.php");
require_once("BabyTracker.spreadsheet.php");
require_once("BabyTracker.mysql.php");

$verbose = 1;
$xml_view = 0;
$no_xml = 0;
$no_sql = 0;
$my_sql;

vprint("php version = " . phpversion() . " fileversion=" . getlastmod());

/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */

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

function RowDataToString($row)
{
    return  "date=" . $row["date"] . " " .
            "time=" . $row["time"] . " " .
            "type=" . $row["type"] . " " .
            "amount=" . $row["amount"] . " " .
            "description=" . $row["description"] . " " .
            "key=" . $row["key"] . " " .
            "spreadsheetid=" . $row["spreadsheetid"] . " " .
            "worksheetid=" . $row["worksheetid"] . " ";
}

function ProcessLogTable_Row($row)
{
    vprint(__FUNCTION__);

    vprint("processing row: " . RowDataToString($row));
	if ($row["key"] && $row["date"] && $row["time"] && $row["type"])
    {
        vprint("valid row");
		$data = array(
		    "Date" => $row["date"],
		    "Time" => $row["time"],
		    "Type" => $row["type"]);

		if ($row["amount"] != "") $data["Amount"] = $row["amount"];
		if ($row["description"] != "") $data["Description"] = $row["description"];

        try
        {
            vprint("sending to spreadsheet");
		    // UNDONE: optimize getting new spreadsheet - hash spreadsheet by key, spreadsheetid
            // UNDONE: get title from table
		    $doc = GetSpreadsheet($row["title"], $row["key"], $row["spreadsheetid"], UserId(), Pwd());
		    $doc->setWorksheet("Tracking");
		    $doc->setWorksheetId($row["worksheetid"]);
		    $doc->add($data, 0);
		    $doc->close();

            vprint("done sending to spreadsheet");
            return 1;
        }
        catch (Exception $e)
        {
            vprint("Caught exception: " . $e->getMessage());
            return 0;
        }
    }
    return -1;
}


function ProcessLogTable()
{
    vprint(__FUNCTION__);

    $mysql = GetMysql();

    //set_error_handler('error_handler');
    set_output_flag("no_sql", 1);

    $table = $mysql->LogTableName();
    $lock = 0;
    $count = 0;
	$for_update = "";

	if ($lock)
		$for_update = "FOR UPDATE";  // for row locking

    while ($count < 10)
    {
        vprint(__FUNCTION__ . "() count = $count");
        $count++;

        if ($lock)
	        $mysql->query("START TRANSACTION");

	    $results = $mysql->query("select * from $table where `state`='new' LIMIT 20 $for_update");
	    while ($row = mysql_fetch_array($results, MYSQL_ASSOC))
	    {
            $mysql->query("update $table set `state`='processing' where `id`='" . $row["id"] . "'");
            $result =  ProcessLogTable_Row($row);

            if ($result == 1)
            {
		        $mysql->query("update $table set `state`='completed' where `id`='" . $row["id"] . "'");

                if ($lock)
	                $mysql->query("COMMIT");
            }
            else
            {
                $error = get_last_error();
                $mysql->query("update $table set `state`='failed', `failure_message`='$error' where `id`='" . $row["id"] . "'");

                if ($lock)
                    $mysql->query("ROLLBACK");
            }
	    }

    }

    restore_error_handler();
    set_output_flag("no_sql", 0);
    $mysql->close();
}

function RunProcessLogTable()
{
    vprint("Starting");

    $file = DIRNAME($_SERVER["SCRIPT_FILENAME"]) . "/lock.txt";
    $block;

    $fp = fopen($file, "r+");
    if (!$fp)
        error("() failed to fopen $file");

    // Try 3 times to get exclusive lock
    $count = 0;
    while ($count < 3)
    {
        $count++;
        if (flock($fp, LOCK_EX | LOCK_NB, $block)) { // do an exclusive lock
            vprint("exclusive ProcessLogTable");

            ProcessLogTable();

            flock($fp, LOCK_UN); // release the lock
            break;
        }

        sleep(3);
    }

    fclose($fp);
}

function AddRowToGoogleSpreadsheet()
{
	$rand = rand(0, 2000);
	vprint("Adding Row Amount=$rand");
	$data = array(
		"Date" => "10/1/2010",
		"Time" => "10:01 am",
		"Type" => "Wet Diaper",
		"Amount" => $rand);

	$title = "Baby Kai Tracker";
	$key="spreadsheet:0AhcdWBEGjPZWdFd0NU9vU19xLXhuLWx0RG9nRDdZemc";
	$spreadsheetid="tWt5OoS_q-xn-ltDogD7Yzg";
	$worksheetid="od7";

	$docApp = GetSpreadsheet($title, $key, $spreadsheetid, UserId(), Pwd());
	$docApp->setWorksheet("Tracking");
	$docApp->setWorksheetId($worksheetid);
	$docApp->add($data, 0);
	$docApp->close();
}

function AddRowToLogTable()
{
	$rand = rand(0, 2000);
	vprint(__FUNCTION__ . " Adding Row Amount=$rand");

	$data = array(
		"date" => "10/1/2010",
		"time" => "10:01 am",
		"type" => "Wet Diaper",
		"amount" => $rand);

	$title = "Baby Kai Tracker";
	$key="spreadsheet:0AhcdWBEGjPZWdFd0NU9vU19xLXhuLWx0RG9nRDdZemc";
	$spreadsheetid="tWt5OoS_q-xn-ltDogD7Yzg";
	$worksheetid="od7";

    $mysql = GetMysql();
	AddRowToSql($mysql,
				$data["date"],
				$data["time"],
				$data["type"],
				$data["amount"],
				"remo",
				$title,
				$key,
				$spreadsheetid,
				$worksheetid);
    $mysql->close();
}

/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */

function TestMe($template_key, $app_userid, $app_pwd)
{
	if ($_POST["PostAction"] != "") return;

	vprint("Starting");
    RunProcessLogTable();
goto ExitTest;

	// UNDONE: Restore passwords
	$userid = "xxxxx@pacifier.com";
	$pwd = "xxxx";
	$title = "xxxxx";

//    SetupLogTable();

	//AddRowToGoogleSpreadsheet();

	AddRowToLogTable();
	AddRowToLogTable();
	AddRowToLogTable();
	AddRowToLogTable();
	AddRowToLogTable();

    DumpLogTableResults();

//    RunProcessLogTable();
goto ExitTest;

goto SetupSql;
//goto AddRowToGoogleSpreadsheet;
	$doc = GetSpreadsheet($title, $key, $id, $userid, $pwd);

	$reuse = 1;
	if ($reuse) {
		$id = $doc->OpenExisting();
		vprint("found existing spreadsheet - id=$id");
	}

	$newKey = $doc->CopyDocument($template_key, "Baby Kai Tracker");

	$doc->AddCollaborator($newKey, "babytracker@pacifier.com");
	$doc->close();

	vprint("*****************************************************************");

UpdateDOB:

	$randMonth = rand(1, 12);
	$randDay = rand(1, 28);
	$date = "$randMonth/$randDay/2009";
	vprint("Updating DOB to $date");

	$docApp = GetSpreadsheet($title, $newKey, "", $app_userid, $app_pwd);
	$docApp->setWorksheet("Variables");
	$docApp->update("3", "2", $date);

	$docApp->setWorksheet("Tracking");
	$docApp->setWorksheetId("");
	$docApp->GetSpreadsheetIds();

	$key = $docApp->getKey();
	$spreadsheetid = $docApp->getSpreadsheetId();
	$worksheetid = $docApp->getWorksheetId();
	$docApp->close();
	vprint("key=$key, sid=$spreadsheetid, wid=$worksheetid");
	vprint("*****************************************************************");

AddRowToGoogleSpreadsheet:
	$rand = rand(0, 2000);
	vprint("Adding Row Amount=$rand");
	$data = array(
		"Date" => "10/1/2010",
		"Time" => "10:01 am",
		"Type" => "Wet Diaper",
		"Amount" => $rand);

	$key="spreadsheet:0AhcdWBEGjPZWdFd0NU9vU19xLXhuLWx0RG9nRDdZemc";
	$spreadsheetid="tWt5OoS_q-xn-ltDogD7Yzg";
	$worksheetid="od7";

	$docApp = GetSpreadsheet($title, $key, $spreadsheetid, $app_userid, $app_pwd);
	$docApp->setWorksheet("Tracking");
	$docApp->setWorksheetId($worksheetid);
	$docApp->add($data, 0);
	$docApp->close();

	vprint("*****************************************************************");

SetupSql:
    SetupLogTable();
	vprint("*****************************************************************");

AddSqlRowTest:
    AddRowToLogTable();
    AddRowToLogTable();
    AddRowToLogTable();
    AddRowToLogTable();

    DumpLogTableResults();

	vprint("*****************************************************************");

    // Update google spreadsheet
    ProcessLogTable();

ExitTest:
	die("Done!!!!</responses>");
}

/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */

$babytracker_userid = UserId();
$babytracker_pwd = Pwd();
$babytracker_template_key = "0AuM4WbOw9IfddC1GZU1VblFEajRQNExQenU5MWc1aWc";
$babytracker_public_spreadsheet_url = "https://spreadsheets.google.com/ccc?key=0AuM4WbOw9IfddC1GZU1VblFEajRQNExQenU5MWc1aWc&hl=en";

$source_dir = dirname($_SERVER["SCRIPT_FILENAME"]);
vprint("Source Directory=$source_dir");

TestMe($babytracker_template_key, $babytracker_userid, $babytracker_pwd);

$title  = "Baby " . $_POST["Name"]." Tracker";

switch($_POST["PostAction"])
{
	case "InstallSpreadsheet":
	{
		if ($_POST["Name"]=="") error("Missing Name");
		if ($_POST["DOB"]=="") error("Missing Date of Birth");
		if ($_POST["Email"]=="") error("Missing Email");
		if ($_POST["Password"]=="") error("Missing Password");

    	$doc = GetSpreadsheet($title, $_POST["Key"], $_POST["Id"], $_POST["Email"], $_POST["Password"]);

		if ($_POST["Reuse"])
			$id = $doc->OpenExisting();

	    if ($id != "" && $key == "")
	    {
	        $doc->GetSpreadsheetIds();
	        $key = $doc->getKey();
	    }

	    if ($key == "")
	        $key = $doc->CopyDocument($babytracker_template_key, $title);

	    $doc->AddCollaborator($key, $babytracker_userid);
	    $doc->close();

	    $docApp = GetSpreadsheet($title, $key, $id, $babytracker_userid, $babytracker_pwd);
	    $docApp->setWorksheet("Variables");
	    $docApp->update("3", "2", $_POST["DOB"]);

	    $docApp->setWorksheet("Tracking");
        $docApp->setWorksheetId("");
	    $docApp->GetSpreadsheetIds();

	    $key = $docApp->getKey();
	    $spreadsheetid = $docApp->getSpreadsheetId();
	    $worksheetid = $docApp->getWorksheetId();

        SetupLogTable();

	    print("Successfully Installed the [$title] spreadsheet. Key=$key;" .
		    "SpreadsheetId=" . $docApp->getSpreadsheetId() . "; " .
		    "WorksheetId=" . $docApp->getWorksheetId() . "; ");

    	$docApp->close();
		break;
	}

	case "AddRow":
	{
		vprint("AddRow post action");

		if ($_POST["Date"] == "") error("Missing Date");
		if ($_POST["Time"] == "") error("Missing Time");
		if ($_POST["Type"] == "") error("Missing Time");
		if (($_POST["Type"] != "Wet Diaper") && ($_POST["Type"] != "Poopy Diaper"))
		if ($_POST["Amount"] == "") error("Missing Amount");
		if ($_POST["Key"]=="") error ("Missing Key");

		$data = array(
		  "Date" => $_POST["Date"],
		  "Time" => $_POST["Time"],
		  "Type" => $_POST["Type"]);

		if ($_POST["Amount"] != "") $data["Amount"] = $_POST["Amount"];
		if ($_POST["Notes"] != "") $data["Description"] = $_POST["Notes"];

		$submission_data = "";
		foreach($data as $key => $value) {
			$submission_data .= "$key=$value ";
		}
		$submission_data .= "Key=" . $_POST["Key"] . " ";
		$submission_data .= "SpreadsheetId=" . $_POST["SpreadsheetId"] . " ";
		$submission_data .= "WorksheetId=" . $_POST["WorksheetId"] . " ";

		$mysql = GetMysql();
		AddRowToSql($mysql,
					$_POST["Date"],
					$_POST["Time"],
					$_POST["Type"],
					$_POST["Amount"],
					$_POST["Notes"],
                    $title,
					$_POST["Key"],
					$_POST["SpreadsheetId"],
					$_POST["WorksheetId"]);
		$mysql->close();

		print("Successfully added the data. Data: $submission_data");

	break;
	}

	case "ProcessLogTable":
	{
		vprint("ProcessLogTable post action");
        RunProcessLogTable();
		break;
	}

	default:
		error("Missing PostAction");
		break;
}

if ($xml_view != 0) {
    echo "<done>done</done></responses>";
}

?>