<?php

//require_once("BabyTracker.output.php");
//require_once("BabyTracker.zend.spreadsheet.php");
//require_once("BabyTracker.mysql.php");
//require_once("BabyTracker.stats.php");

define('MLPerOunce', 29.5735296);
$html_space = "&nbsp;";

function DeleteLogTable($key = "", $sqlrowid = "")
{
    vprint("Starting key=$key sqlrowid=$sqlrowid");

    $mysql = GetMysql();

    $table = get_config_value("uploads_table_name");
	$key_clause = "";
	$rowid_clause = "";

	if ($key) $key_clause = "and `key`='$key'";
	if ($sqlrowid) $rowid_clause = "and `id`='$sqlrowid'";

	$mysql->query('START TRANSACTION');
	$mysql->query('BEGIN');
	$mysql->query("delete from $table where 1=1 $key_clause $rowid_clause");
	$mysql->query('COMMIT');
}

function GetExcludedKeyList($mysql)
{
    $table = get_config_value("uploads_table_name");
    $sql = "SELECT DISTINCT `key` FROM $table WHERE (`errno`>=400 AND `errno`<=500) AND (TIMESTAMPDIFF(HOUR, `timestamp`, NOW() ) <= 4)";
    $results = $mysql->query($sql);
    $keylist = $mysql->query_results($results);
    $exclude_list = "";
    foreach($keylist as $key) {
        $exclude_list .= " AND `key`<>'$key'";
    }
    vprint($exclude_list);
    return $exclude_list;
}

function ProcessLogTable($key, $sqlrowid, $all = 0)
{
    vprint("Starting key=$key sqlrowid=$sqlrowid");

    $mysql = GetMysql();
	set_output_flag("no_sql", 0);

    $table = get_config_value("uploads_table_name");
    $count = 0;
	$for_update = 'FOR UPDATE';  // for row locking
	$key_clause = "";
	$rowid_clause = "";
	$uniqid = uniqid("", true);
	$common_update_sql = ", `timestamp`=NOW() ,`attempt`=`attempt`+1 ";
	$changedby_clause = "";
	$failed_clause = "";
    $key_exclude_list = GetExcludedKeyList($mysql);

	if ($key) $key_clause = "and `key`='$key'";
	if ($sqlrowid) $rowid_clause = "and `id`='$sqlrowid'";
	if ($all) {
        $errno_clause = "and (`errno`<400 or `errno`>500)";  // skip certain error states
		$failed_clause = "OR (`state`='failed' $errno_clause $changedby_clause and (TIMESTAMPDIFF(MINUTE, `timestamp`, NOW()) > 2))";
	}

	$exclude_rows = "and (`action`='insert' or `sheetrowid` IS NOT NULL)";

    while ($count < 10)
    {
        $count++;

		flush_buffers();

		UpdateAllUploadSheetRowId();

		$mysql->query('START TRANSACTION');
		$mysql->query('BEGIN');
		$mysql->query("update $table set `state`='processing', `changedby`='$uniqid' $common_update_sql " .
					  "where `attempt`<= 20 and (`state`='new' $failed_clause) $exclude_rows $key_exclude_list ORDER BY `attempt` LIMIT 1");
	    $results = $mysql->query("select * from $table where `state`='processing' AND `changedby`='$uniqid' $for_update");
		$mysql->query('COMMIT');
	    while ($row = $mysql->query_results($results))
	    {
        	LogCommandLineMessage("TRANS:", "<b>row id=" . $row['id'] . "</b> from ProcessLogs() all=$all uid=$uniqid " . DataToStringEx($row));

			$attempt=$row['attempt'] + 1;
			$id_clause = " `id`='" . $row['id'] . "' ";

			vprint("processing row id=[" . $row['id'] ."]");
            $result = ProcessLogTable_Row($row); // cant be rolled back

			// NOTE: if catastrophic then spreadsheet will be updated but not sql.
			// fix is to search for sql sqlrowid or uniqueid in google spreadsheet and
			// detect if already added

			$mysql->query('START TRANSACTION');
			$mysql->query('BEGIN');
            if ($result === 0)
            {
				vprint("success row id=[" . $row['id'] ."]");
		        $mysql->query("delete from $table where $id_clause");
            }
            else
            {
				vprint("failed row id=[" . $row['id'] ."] error=$result");
                $errno = get_last_errno();
                $mysql->query("update $table set `state`='failed', `failure_message`='$result', `errno`='$errno' $common_update_sql where $id_clause");
                if ($errno >= 400 && $errno <= 500)
                    $key_exclude_list .= " AND `key`<>'" . $row['key'] . "'";
            }
			$mysql->query('COMMIT');
			mysql_free_result($result);
			if ($all) {
				$count = 0;
			}
			//sleep(1);
	    }
		mysql_free_result($results);

		flush_buffers();
    }

	set_output_flag("no_sql", 0);

	GetProcessingErrors($key);
}

function RunProcessLogTable($key = "", $sqlrowid = "")
{
    vprint('Starting ' . strftime("%b %d %Y %X", time()));
    ProcessLogTable($key, $sqlrowid);
	vprint('End ' . strftime("%b %d %Y %X", time()));
}

function TestAddToSpreadsheet($client)
{
	$rand = rand(0, 2000);
	vprint("Adding Row amount=$rand");
	$data = array(
		'date' => "10/1/2010",
		'time' => "10:01 am",
		'type' => 'Wet Diaper',
		'amount' => $rand);

	$title = $client['title'];
	$key = $client['key'];
	$spreadsheetid = $client['spreadsheetid'];
	$worksheetid = $client['worksheetid'];
	Zend_InserRow($data, $client);
}

function TestAddRowToLogTable()
{
	$rand = rand(0, 2000);
	vprint(" Adding Row amount=$rand");

	$title = get_config_value("test_title");
	$key=get_config_value("test_key");
	$spreadsheetid=get_config_value("test_spreadsheetid");
	$worksheetid=get_config_value("test_worksheetid");

	$data = array(
		'date' => "10/1/2010",
		'time' => "10:01 am",
		'type' => 'Bottle',
		'amount' => $rand,
        'description' => 'remo',
        'key' => $key,
        'spreadsheetid' => $spreadsheetid,
        'worksheetid' => $worksheetid);

    $mysql = GetMysql();
	$sqlrowid = AddRowToChildTable($mysql, $title, $data);
	vprint("added sqlrowid=$sqlrowid");
}

?>
