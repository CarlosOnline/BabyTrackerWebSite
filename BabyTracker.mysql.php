<?php

require_once("BabyTracker.output.php");

// Attributes
define("attrFormula"    , "b'00000000'");
define("attrBreastMilk" , "b'00000001'");

define("attrLeft"       , "b'00000010'");
define("attrRight"      , "b'00000100'");

$g_mysql = 0;

/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */

class mysql
{
	private $database;
	private $server;
	private $con;

	public function __construct() {
	}

	public function connection() {
		return $this->con;
	}

	function RegTableName() {
	    return "BabyTracker_RegisteredUsers";
	}

	public function Database() {
		return $this->database;
	}

	public function close() {
		mysql_close($this->con);
		$this->con = "";
	}

	public function open($server, $userid, $pwd, $database)
	{
		$this->con = mysql_connect($server, $userid, $pwd);
		if (!$this->con)
			sql_error("open() failed to connect to database.");

		if (!mysql_select_db($database, $this->con))
			sql_error("open() failed to select database - $database");

		$this->database = $database;
	}

	public function query($sql)
	{
        global $no_sql;

        if (!$no_sql) vprint("mysql(<small>$sql</small>)");
		$result = mysql_query($sql, $this->con);
		if (!$result)
			sql_error("query() mysql_query failed for query=$sql");
		return $result;
	}

	public function query_nr($sql)
	{
		$result = $this->query($sql);
		$this->query_close($result);
	}

	public function insert_id()
	{
		return mysql_insert_id($this->con);
	}

	public function query_results($results)
	{
		return mysql_fetch_array($results, MYSQL_ASSOC);
	}

	public function query_results_num($results)
	{
		return mysql_fetch_array($results, MYSQL_NUM);
	}

	public function query_close($results)
	{
		mysql_free_result($results);
	}

	public function exec_file($filename, $search, $replace)
	{
		vprint($filename);

		$fp = fopen($filename, "r");
		$contents = fread($fp, filesize($filename));
		fclose($fp);
		$contents = rtrim($contents);

		// remo utf8 encoding prefix
		$specials = array(
			chr(239),
			chr(187),
			chr(191),
		);

		$contents = str_replace($specials, "", $contents);
		if ($search)
			$contents = str_replace($search, $replace, $contents);

	        $count = 0;
		$results = "";
		$contents_end = strlen($contents);
		$start = 0;
		$end = false;
		do {
			$end = strpos($contents, ";", $start);
			$sql = substr($contents, $start, $end - $start);
            //vprint("start=$start end=$end sql=$sql");
			if (!($end === false))
				$start = $end + 1;

			$results = $this->query($sql);
            $count++;

		} while(!($end === false) && ($start < $contents_end) && ($count < 1000));

		return $results;
	}

} // class mysql

class mysqlx
{
	private $database;
	private $server;
	private $con;
	private $mysqli;

	public function __construct() {
	}

	public function connection() {
		return $this->mysqli;
	}

	public function Database() {
		return $this->database;
	}

	public function close() {
		mysqli_close($this->mysqli);
		$this->mysqli = "";
	}

	public function open($server, $userid, $pwd, $database)
	{
		$this->mysqli = new mysqli($server, $userid, $pwd, $database);
		if ($this->mysqli->connect_error) {
			error("failed to connect. errno=" .
				  $this->mysqli->connect_errno .
				  " error=" .
				  $this->mysqli->connect_error);
		}

		$this->database = $database;
	}

	public function query($sql)
	{
        global $no_sql;

        if (!$no_sql) vprint("mysql(<small>$sql</small>)");
		$result = $this->mysqli->query($sql);
		if (!$result)
			error("query=$sql error=" . $this->mysqli->error);
		return $result;
	}

	public function query_nr($sql)
	{
		$result = $this->query($sql);
		$this->query_close($result);
	}

	public function insert_id()
	{
		return $this->mysqli->insert_id;
	}

	public function query_results($results)
	{
		return $results->fetch_assoc();
	}

	public function query_results_num($results)
	{
		return $results->fetch_array(MYSQLI_NUM);
	}

	public function query_close($results)
	{
		$results->close();
	}

} // class mysqlx

/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */

function GetMysql()
{
	global $config;
	global $g_mysql;

	if ($g_mysql === 0)
	{
		$dbserver = $config["dbserver"];
		$dbuserid = $config["dbuserid"];
		$dbpwd = $config["dbpwd"];
		$database = $config["database"];

		$g_mysql = new mysql();
		$g_mysql->open($dbserver, $dbuserid, $dbpwd, $database);
	}

    return $g_mysql;
}

function CloseMysql()
{
	global $g_mysql;

	if ($g_mysql === 0) { /* no op */ }
	else
	{
		$g_mysql->close();
		$g_mysql = 0;
	}
}

function SetupLockTable()
{
	vprint("Starting");
    $mysql = GetMysql();
    $table = get_config_value("lock_table_name");
	$engine = "MyISAM";
	$lock_name = get_config_value("log_table_lock_name");

	$sqlCreateTable =
		"CREATE TABLE IF NOT EXISTS `$table` (" .
		"`id` INT NOT NULL AUTO_INCREMENT, " .
		"`name` varchar(256) NOT NULL, " .
		"`value` varchar(256) NULL, " .
		"PRIMARY KEY (`id`)) ENGINE = $engine";
	$mysql->query($sqlCreateTable);

	$sql = "insert into `$table` set `name`='$lock_name';";
	$mysql->query($sql);
	$new_id = $mysql->insert_id();
	return $new_id;
}

function AcquireLock($lock_type)
{
	//vprint("Starting $lock_type");

    $mysql = GetMysql();
    $table = get_config_value("lock_table_name");
	$lock_name = "";

	switch ($lock_type)
	{
		case "log_table":
			$lock_name = get_config_value("log_table_lock_name");
			break;

		default:
			error("unknown lock type $lock_type");
			break;
	}

    //DumpQueryResults($mysql->query("select * from $table"));

	$lock_value = uniqid($lock_name . "_", 1);
	vprint("lock_value = $lock_value lock_name=$lock_name");

	$result = $mysql->query("START TRANSACTION;\n");
	$result = $mysql->query("BEGIN;\n");
    $result = $mysql->query("update `$table` set `value`='$lock_value' where `name`='$lock_name' AND (`value`='0' OR `value` IS NULL);\n");
	$result = $mysql->query("COMMIT");

	$sql = "select `value` from `$table` where `name`='$lock_name'";
	$result = $mysql->query($sql);
	$row = $mysql->query_results_num($result);
	//vprint("current lock value for $lock_name=" . $row[0] . " passed in $lock_value");

    //DumpQueryResults($mysql->query("select * from $table"));

    if ($row[0] == $lock_value)
    {
        //vprint("lock acquired $lock_value");
        return $lock_value;
    }

    vprint("Lock not acquired");
    return 0;
}

function ReleaseLock($lock_type, $lock_value, $force = false)
{
	vprint("Starting $lock_type $lock_value $force");

    $mysql = GetMysql();
    $table = get_config_value("lock_table_name");
	$lock_name = "";

	switch ($lock_type)
	{
		case "log_table":
			$lock_name = get_config_value("log_table_lock_name");
			break;

		default:
			error("unknown lock type $lock_type");
			break;
	}

    $value_clause = "";
	if (!$force)
		$value_clause = "AND `value`='$lock_value'";

	$result = $mysql->query("START TRANSACTION;\n");
	$result = $mysql->query("BEGIN;\n");
    $result = $mysql->query("update `$table` set `value`=NULL where `name`='$lock_name' $value_clause;\n");
	$result = $mysql->query("COMMIT");

	$sql = "select `value` from `$table` where `name`='$lock_name'";
	$result = $mysql->query($sql);
	$row = $mysql->query_results_num($result);
	vprint("current lock value for $lock_name=[" . $row[0] . "] passed in value=$lock_value");

    if ($row[0] == NULL)
    {
        vprint("lock release for $lock_value");
        return 1;
    }

    vprint("Lock not released");
    return 0;
}

function GetEngineName()
{
	$lock = get_config_value("lock_tables");
    if ($lock)
        $engine = "InnoDB";
    else
        $engine = "MyISAM";
	return $engine;
}

function SetupUploadTable()
{
	$mysql = GetMysql();
    $table = get_config_value("uploads_table_name");
    $engine = GetEngineName();
	$search = array(
		"\$table",
		"\$engine",
	);
	$replace = array(
		"$table",
		"$engine",
	);
	$results = $mysql->exec_file("sql/create_upload_table.sql", $search, $replace);
}

function SetupUserTable($table)
{
	$mysql = GetMysql();
    $engine = GetEngineName();
	$search = array(
		"\$table",
		"\$engine",
	);
	$replace = array(
		"$table",
		"$engine",
	);
	$results = $mysql->exec_file("sql/create_user_table.sql", $search, $replace);
}

function SetupRegistrationTable()
{
	$mysql = GetMysql();
	$table = get_config_value("registered_users_table_name");
    $engine = GetEngineName();
	$search = array(
		"\$table",
		"\$engine",
	);
	$replace = array(
		"$table",
		"$engine",
	);
	$results = $mysql->exec_file("sql/create_registration_table.sql", $search, $replace);
}

function DeleteSystemTables()
{
    $mysql = GetMysql();
    $table = get_config_value("lock_table_name");

	$sqlDropTable = "DROP TABLE IF EXISTS `" . get_config_value("lock_table_name") . "`";
	$mysql->query($sqlDropTable);

	$sqlDropTable = "DROP TABLE IF EXISTS `" . get_config_value("uploads_table_name") . "`";
	$mysql->query($sqlDropTable);

	$sqlDropTable = "DROP TABLE IF EXISTS `" . get_config_value("registered_users_table_name") . "`";
	$mysql->query($sqlDropTable);
}

function SetupSystemTables()
{
	SetupLockTable();
	SetupUploadTable();
	SetupRegistrationTable();
}

function SetupNewUser(&$client)
{
	$userid = $client["userid"];
	$pwd = $client["pwd"];
	$title = $client["title"];
	@$key = $client["key"];
	@$spreadsheetid = $client["spreadsheetid"];
	@$worksheetid = $client["worksheetid"];
	$dob = $client["dob"];
	$name = $client["name"];
	@$token = $client["token"];
	$tablename = $client["tablename"];

	SetupUserTable($tablename);

    $mysql = GetMysql();
	$cached_client = GetClient($userid, $name, 0, $token);

	if (!$token)
		$token = $cached_client["token"];

	$cached_key = $cached_client["key"];
    if ($cached_key)
    {
	    $sql = "update `" . get_config_value("registered_users_table_name") . "` set\n" .
			    "`dob`=STR_TO_DATE('$dob', '%m/%d/%Y'),\n" .
				"`token`='$token',\n" .
			    "`title`='$title',\n" .
			    "`key`='$key',\n" .
			    "`spreadsheetid`='$spreadsheetid',\n" .
			    "`worksheetid`='$worksheetid' " .
				"where `key`='$cached_key';\n";

	    $mysql->query($sql);

        $client["token"] = GetUserRegValue("token", $client);
        $client["tablename"] = GetUserRegValue("tablename", $client);
        $new_id = GetUserRegValue("id", $client);
    }
    else
    {
	    $sql = "insert into `" . get_config_value("registered_users_table_name") . "` set\n" .
			    "`name`='$name',\n" .
			    "`dob`=STR_TO_DATE('$dob', '%m/%d/%Y'),\n" .
			    "`userid`='$userid',\n" .
			    "`token`='$token',\n" .
			    "`tablename`='$tablename',\n" .
			    "`title`='$title',\n" .
			    "`key`='$key',\n" .
			    "`spreadsheetid`='$spreadsheetid',\n" .
			    "`worksheetid`='$worksheetid'\n" .
                "ON DUPLICATE KEY UPDATE\n" .
			    "`dob`=STR_TO_DATE('$dob', '%m/%d/%Y'),\n" .
			    "`key`='$key',\n" .
			    "`spreadsheetid`='$spreadsheetid',\n" .
			    "`worksheetid`='$worksheetid';\n";

	    $mysql->query($sql);
    	$new_id = $mysql->insert_id();
    }

	return $new_id;
}

function DropTable($table)
{
	$mysql = GetMysql();
	$mysql->query("DROP TABLE IF EXISTS `$table`;");
}

function DumpQueryResults($results)
{
    vprint("results=$results");

	$use_i = 0;
	if ($use_i)
	{
		while ($row = $results->fetch_assoc()) {
			array_print($row);
		}
	}
	else
	{
		while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
			array_print($row);
		}

	}
}

function GetQueryResults($results)
{
	$use_i = 1;
	if ($use_i)
		return $results->fetch_assoc();
	else
		return mysql_fetch_array($results, MYSQL_ASSOC);
}

function GetQueryValueEx($results, $row_idx = 0, $col_idx = 0, $all = 0)
{
    if ($all)
        return mysql_fetch_array($results, MYSQL_NUM);
    else
        return GetQueryValue($results, $row_idx, $col_idx);
}

function GetQueryValue($results, $row_idx = 0, $col_idx = 0)
{
	$idx = 0;
	do
	{
		$row = mysql_fetch_array($results, MYSQL_NUM);

	} while ($idx++ < $row_idx);

	if ($row)
		return $row[$col_idx];
}

function SetUserTableSheetRowId($sqlrowid, $sheetrowid, &$client)
{
    vprint("<hr>Starting");
	if (!$sheetrowid || !$sqlrowid)
		return;

	$mysql = GetMysql();
	$key = $client["key"];
	$table = $client["tablename"];
    $uploads_table = get_config_value("uploads_table_name");
    $reg_table = get_config_value("registered_users_table_name");

	// update user table row
	$sql = "update `$table` set `sheetrowid`='$sheetrowid' where `id`='$sqlrowid';";
	$mysql->query($sql);

	// set last row in client
	$client["last_row"] = GetQueryValue($mysql->query("select MAX(`sheetrowid`) from `$table`"));
	$results = $mysql->query("update `$reg_table` set `last_row`='" . $client["last_row"] . "' where `key`='$key'");

	// set sheetrowid in uploads table for any pending records on this row
	$data = array();
	$data["sheetrowid"] = $sheetrowid;
	UpdateUploadSheetRowId($data, $client);
}

function UpdateUploadSheetRowId(&$data, $client)
{
	$sqlrowid = $data["sqlrowid"];

	$mysql = GetMysql();

	$key = $client["key"];
	$table = $client["tablename"];
    $uploads_table = get_config_value("uploads_table_name");

	$sql = "UPDATE `$uploads_table` as B ";
	$sql .= "INNER JOIN `$table` as U ";
	$sql .= "ON U.id=B.`sqlrowid` ";
	$sql .= "SET B.`sheetrowid`=U.`sheetrowid` ";
	$sql .= "WHERE B.`key`='$key' AND U.`sheetrowid` IS NOT NULL AND B.`sqlrowid`=U.`id`";
	$mysql->query($sql);

	$sheetrowid = @$data["sheetrowid"];
	if (!$sheetrowid) {
		//$sheetrowid = GetQueryValue($mysql->query("select `sheetrowid` from `$table` where `id`='$sqlrowid'"));
		if (!$sheetrowid)
		{
			//array_print($client);
			//error("missing sheetrowid");
			return;
		}

		$data["sheetrowid"] = $sheetrowid;
	}

// UNDONE: delete from user_table where `type`='deleted' and `id` not in (select `sqlrowid` from `uploads_table`)
	return @$client["sheetrowid"];
}

function UpdateAllUploadSheetRowId()
{
    $table = get_config_value("uploads_table_name");
	$mysql = GetMysql();
	$results = $mysql->query("select DISTINCT `key` from `$table`");
	while ($row = $mysql->query_results($results))
	{
		$client = GetClient(0, 0, $row["key"]);
		UpdateUploadSheetRowId($row, $client);
	}
}

function AddRowToUploadTable($mysql, $data, $client)
{
    $date = $data["date"];
    $time = $data["time"];
    $type = $data["type"];
    $amount = @$data["amount"];
    $description = @$data["description"];

	// Client Data
	$title = $client["title"];
    $key = $client["key"];
    $spreadsheetid = $client["spreadsheetid"];
    $worksheetid = $client["worksheetid"];
	$token = $client["token"];

    vprint("Starting");

    $row_data = "";
	if ($amount)        $row_data .= "amount='$amount', ";
	if ($description)   $row_data .= "description='$description', ";
	if ($title)         $row_data .= "title='$title', ";
	if ($spreadsheetid) $row_data .= "spreadsheetid='$spreadsheetid', ";
	if ($worksheetid)   $row_data .= "worksheetid='$worksheetid', ";
	$row_data .= "date=STR_TO_DATE('$date', '%m/%d/%Y'), ";
	$row_data .= "`time`=TIME(STR_TO_DATE('$time', '%l:%i %p')), ";
	$row_data .= "`type`='$type', ";
	$row_data .= "`key`='$key', ";
	$row_data .= "`sqlrowid`=" . $data["sqlrowid"] . ', ';
	$row_data .= "`action`='insert'";

	$sql = "insert into `" . get_config_value("uploads_table_name") . "` set $row_data;";
	$mysql->query($sql);
	$new_id = $mysql->insert_id();

    DumpQueryResults($mysql->query("select * from " . get_config_value("uploads_table_name") . " where `sqlrowid`=" . $data["sqlrowid"]));

	return $new_id;
}

function AddRowToUserTable($mysql, $data, $client)
{
    $date = $data["date"];
    $time = $data["time"];
    $type = $data["type"];
    $amount = @$data["amount"];
    $description = @$data["description"];
	$formula = @$data["formula"];
	$breastmilk = @$data["breastmilk"];
	$left = @$data["left"];
	$right = @$data["right"];
	$table = $client["tablename"];

    vprint("Starting");

    $row_data = "";
	if ($amount)        $row_data .= "`amount`='$amount', \n";
	if ($description)   $row_data .= "`description`='$description', \n";
	if ($formula)		$row_data .= "`attr`=`attr` | " . attrFormula . ", \n";
	if ($breastmilk)		$row_data .= "`attr`=`attr` | " . attrBreastMilk . ", \n";
	if ($left)		$row_data .= "`attr`=`attr` | " . attrLeft . ", \n";
	if ($right)		$row_data .= "`attr`=`attr` | " . attrRight . ", \n";
	$row_data .= "`datetime`=STR_TO_DATE('$date $time', '%m/%d/%Y %l:%i %p'), ";
	$row_data .= "`type`='$type' \n";

	$sql = "insert into `$table` set $row_data;";
	$mysql->query($sql);
	$new_id = $mysql->insert_id();

	$mysql->query("update `$table` set amount_oz=IF(ISNULL(amount), NULL, IF((type='breast' OR amount < 9), amount, amount / 29.5735296)) WHERE `id`='$new_id';");

	$results = $mysql->query("select * from `$table` where `id`='$new_id'");
    $row = $mysql->query_results($results);

	return $row;
}

function AddUpdateToUploadTable($mysql, $data, $client)
{
	array_print($data);

	$sqlrowid = $data["sqlrowid"];
	$sheetrowid = @$data["sheetrowid"];
    $date = $data["date"];
    $time = $data["time"];
    $type = $data["type"];
    $amount = @$data["amount"];
    $description = @$data["description"];

	// Client Data
	$title = $client["title"];
    $key = $client["key"];
    $spreadsheetid = $client["spreadsheetid"];
    $worksheetid = $client["worksheetid"];
	$token = $client["token"];

    vprint("Starting");

	UpdateUploadSheetRowId($data, $client);
	$sheetrowid = $data["sheetrowid"];

    $row_data = "";
	if ($amount)        $row_data .= "amount='$amount', ";
	if ($description)   $row_data .= "description='$description', ";
	if ($title)         $row_data .= "title='$title', ";
	if ($spreadsheetid) $row_data .= "spreadsheetid='$spreadsheetid', ";
	if ($worksheetid)   $row_data .= "worksheetid='$worksheetid', ";
	if ($date) 			$row_data .= "date=STR_TO_DATE('$date', '%m/%d/%Y'), ";
	if ($time) 			$row_data .= "`time`=TIME(STR_TO_DATE('$time', '%l:%i %p')), ";
	if ($type)			$row_data .= "`type`='$type', ";
	if ($sheetrowid)	$row_data .= "`sheetrowid`='$sheetrowid', ";
	$row_data .= "`sqlrowid`='$sqlrowid', ";
	$row_data .= "`key`='$key', ";
	$row_data .= "`action`='update'";

	$sql = "insert into `" . get_config_value("uploads_table_name") . "` set $row_data;";
	$mysql->query($sql);
	$new_id = $mysql->insert_id();

    DumpQueryResults($mysql->query("select * from " . get_config_value("uploads_table_name") . " where id=$new_id"));

	return $new_id;
}

function UpdateUserTableRow($mysql, $data, $client)
{
	$sqlrowid = $data["sqlrowid"];
    $date = $data["date"];
    $time = $data["time"];
    $type = $data["type"];
    $amount = @$data["amount"];
    $description = @$data["description"];

	// Client Data
	$table = $client["tablename"];

    vprint("Starting");

    $row_data = "`timestamp`=NOW(), ";
	if ($amount)        $row_data .= "amount='$amount', ";
	if ($description)   $row_data .= "description='$description', ";
	if ($date && $time) $row_data .= "`datetime`=STR_TO_DATE('$date $time', '%m/%d/%Y %l:%i %p'), ";
	if ($type) 			$row_data .= "`type`='$type'";

	$sql = "update `$table` set $row_data where `id`='$sqlrowid';";
	$mysql->query($sql);

	$results = $mysql->query("select * from `$table` where `id`='$sqlrowid'");
    $row = $mysql->query_results($results);

	return $row;
}

function AddDeleteToUploadTable($mysql, $data, $client)
{
	// Client Data
	$title = $client["title"];
    $key = $client["key"];
    $spreadsheetid = $client["spreadsheetid"];
    $worksheetid = $client["worksheetid"];
	$token = $client["token"];
	$sheetrowid = $data["sheetrowid"];
	$sqlrowid = $data["sqlrowid"];
	$table = $client["tablename"];
    vprint("Starting");

	UpdateUploadSheetRowId($data, $client);
	$sheetrowid = $data["sheetrowid"];

	vprint("sheetrowid = $sheetrowid");
	if ($sheetrowid) {
		// sheetrowid is valid so row can be deleted from user table
		$mysql->query("delete from `$table` where `id`='$sqlrowid'");
		//$sqlrowid = 0;
	}

    $row_data = "";
	if ($title)         $row_data .= "title='$title', ";
	if ($spreadsheetid) $row_data .= "spreadsheetid='$spreadsheetid', ";
	if ($worksheetid)   $row_data .= "worksheetid='$worksheetid', ";
	if ($sheetrowid)    $row_data .= "`sheetrowid`='$sheetrowid', ";
	if ($sqlrowid)    $row_data .= "`sqlrowid`='$sqlrowid', ";
	$row_data .= "`type`='Deleted', ";
	$row_data .= "`key`='$key', ";
	$row_data .= "`action`='delete'";

	$sql = "insert into `" . get_config_value("uploads_table_name") . "` set $row_data;";
	$mysql->query($sql);
	$new_id = $mysql->insert_id();

    DumpQueryResults($mysql->query("select * from " . get_config_value("uploads_table_name") . " where id=$new_id"));

	return $new_id;
}

function DeleteUserTableRow($mysql, $sqlrowid, $client)
{
	// Client Data
	$title = $client["title"];
    $key = $client["key"];
    $spreadsheetid = $client["spreadsheetid"];
    $worksheetid = $client["worksheetid"];
	$table = $client["tablename"];

    vprint("Starting");

	$sql = "update `$table` set `amount`=NULL, `description`=NULL, `version`=0, `amount_oz`=NULL,`type`='deleted', `timestamp`=NOW() where `id`='$sqlrowid';";
	$mysql->query($sql);

	$results = $mysql->query("select * from `$table` where `id`='$sqlrowid'");
    $row = $mysql->query_results($results);

	return $row;
}

function DumpLogTableResults()
{
    vprint(__FUNCTION__);
    $mysql = GetMysql();
	$results = $mysql->query("select count(*) from " . get_config_value("uploads_table_name"));
	if ($row = $mysql->query_results_num($results))
	{
		vprint($row[0] . " rows in table");
	}

	$results = $mysql->query("select * from " . get_config_value("uploads_table_name") . " order by `timestamp` desc");
    vprint(ResultsToTable($mysql, $results));
    /*
	while ($row = $mysql->query_results($results))
	{
		array_print($row);
	}
    */
}

function GetProcessingErrors($key = "")
{
    vprint("Starting key=$key");

    $mysql = GetMysql();
	set_output_flag("no_sql", 0);

    $table = get_config_value("uploads_table_name");
	$key_clause = "";
	if ($key) $key_clause = "and `key`='$key'";

	//$timestamp = "TIMESTAMPDIFF(HOUR, `timestamp`, NOW()) > 2" ;
	$results = $mysql->query("select count(*) from $table where (`state`='failed') $key_clause");
    $count = $mysql->query_results_num($results);
	if ($count[0] > 0)
	{
    	$results = $mysql->query("select * from $table where (`state`='failed') $key_clause");

        // Clear previous output, so new output can be sent into an email
		flush_buffers(true);

		print("Found " . $count[0] . " errors");
        $tableHtml = ResultsToTable($mysql, $results);
        print($tableHtml);

		$headers = 'From: Baby Tracker <babytracker@pacifier.com>' . "\r\n" .
				   'Reply-To: Baby Tracker <babytracker@pacifier.com>' . "\r\n" .
				   'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		mail(BabyTracker_UserId(), "Baby Tracker Error List " . date('Y-m-d'), ob_get_contents(), $headers);

	    flush_to_file("output/errorlist.htm");
		flush_buffers(true);
	}

	set_output_flag("no_sql", 0);
}

function ResetErrors($key = "")
{
    vprint("Starting key=$key");

    $mysql = GetMysql();
    $table = get_config_value("uploads_table_name");
	$where_clause = "";
	if ($key) $where_clause = "where `key`='$key'";
	$results = $mysql->query("update $table set `failure_message`=NULL, `errno`=NULL, `state`='new', `attempt`=0, `changedby`=NULL, `timestamp`=NOW() $where_clause");
}

function TouchAllRecords($key = "")
{
    vprint("Starting key=$key");

    $mysql = GetMysql();
    $table = get_config_value("uploads_table_name");
	$where_clause = "";
	if ($key) $where_clause = "where `key`='$key'";
	$results = $mysql->query("update $table set `timestamp`=NOW() $where_clause");
}

function StopAllRuns($key = "")
{
    vprint("Starting key=$key");

    $mysql = GetMysql();
    $table = get_config_value("uploads_table_name");
	$where_clause = "";
	if ($key) $where_clause = "where `key`='$key'";
	$results = $mysql->query("update $table set `timestamp`=NOW(), `attempt`='300', `changedby`='StopAllRuns' $where_clause");
}

function ResultsToTable($mysql, $results)
{
    $tablehtml = "";
	while ($row = $mysql->query_results($results))
	{
        if ($tablehtml == "")
            $tablehtml = MakeTableHeader($row);
        $tablehtml .= MakeTableRow($row);
	}
    $tablehtml .= MakeTableFooter();
    return $tablehtml;
}

function UserTableName($userid, $name, $token)
{
    $table = @$_COOKIE["tablename"];
    if ($table)
        return $table;

    $table = get_input_option("tablename");
    if ($table)
        return $table;

    $table = GetRegisteredTableName($token, $userid, $name);
    if ($table)
        return $table;

    return MakeNewUserTableName($userid, $name);
}

function MakeNewUserTableName($userid, $name)
{
	$specials = array(
		"@",
		".",
	);

	return "user_table_" . str_replace($specials, "_", $userid) . "_$name";
}

function GetRegisteredTableName($token="", $userid="", $name="")
{
    $mysql = GetMysql();
    $table = get_config_value("registered_users_table_name");
	$where_clause = "";
	if ($token) $where_clause .= "`token`='$token' OR ";
	if ($userid && $name) $where_clause .= "(`userid`='$userid' AND `name`='$name') OR ";

	$results = $mysql->query("select `tablename` from $table where $where_clause 0=1");
    $ret = $mysql->query_results($results);

    return @$ret["tablename"];
}

function ExecSqlFile($filename, $tablename)
{
	$mysql = GetMysql();
    $engine = GetEngineName();
	$search = array(
		"\$table",
		"\$engine",
	);
	$replace = array(
		"$tablename",
		"$engine",
	);
	$results = $mysql->exec_file($filename, $search, $replace);
}

function GetUserRegValue($item, $client)
{
    @$token = $client["token"];
    $userid = $client["userid"];
    $name = $client["name"];

    $mysql = GetMysql();
    $table = get_config_value("registered_users_table_name");
	$where_clause = "";
	if ($token) $where_clause .= "`token`='$token' OR ";
	if ($userid && $name) $where_clause .= "(`userid`='$userid' AND `name`='$name') OR ";

	$results = $mysql->query("select `$item` from $table where $where_clause 0=1");
    $ret = $mysql->query_results_num($results);
    if ($ret)
        return $ret[0];
}

function DeleteUserRegRow($client)
{
    @$token = $client["token"];
    $userid = $client["userid"];
    $name = $client["name"];

    $mysql = GetMysql();
    $table = get_config_value("registered_users_table_name");
	$where_clause = "";
	if ($token) $where_clause .= "`token`='$token' OR ";
	if ($userid && $name) $where_clause .= "(`userid`='$userid' AND `name`='$name') OR ";

	$results = $mysql->query("delete from `$table` where $where_clause 0=1");
	return $results;
}

function GetUserRegValueEx($item, $key)
{
    $mysql = GetMysql();
    $table = get_config_value("registered_users_table_name");
	$results = $mysql->query("select `$item` from $table where `key`='$key'");
    $ret = $mysql->query_results_num($results);
    if ($ret)
        return $ret[0];
}

function GetClient($userid=0, $name=0, $key=0, $token=0)
{
    $mysql = GetMysql();
    $table = get_config_value("registered_users_table_name");

	$where_clause = "";
	if ($key) $where_clause .= "`key`='$key' OR ";
	if ($token) $where_clause .= "`token`='$token' OR ";
	if ($userid && $name) $where_clause .= "(`userid`='$userid' AND `name`='$name') OR ";

	$results = $mysql->query("select * from $table where $where_clause 0=1");
    return $mysql->query_results($results);
}

function SetActionState($state, $id)
{
    $mysql = GetMysql();
    $table = get_config_value("uploads_table_name");

	$results = $mysql->query("update $table set `action_state`='$state' where `id`='$id'");
    return $mysql->query_results($results);
}
?>
