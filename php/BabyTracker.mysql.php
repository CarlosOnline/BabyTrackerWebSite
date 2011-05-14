<?php

$g_mysql = 0;
$g_mysql_babytracker_version = 2.1;

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
	    return 'RegisteredUsers';
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

		$fp = fopen($filename, 'r');
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
		$dbserver = $config['dbserver'];
		$dbuserid = $config['dbuserid'];
		$dbpwd = $config['dbpwd'];
		$database = $config['database'];

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

function GetEngineName()
{
/*
	$lock = get_config_value("lock_tables");
    if ($lock)
        $engine = 'InnoDB';
    else
*/
        $engine = 'MyISAM';
	return $engine;
}

function CreateChildTable($table, $current_version)
{
	$mysql = GetMysql();
    $engine = GetEngineName();
	$search = array(
		"\$table",
		"\$engine",
		"\$current_version",
	);
	$replace = array(
		"$table",
		"$engine",
		"$current_version",
	);
	$results = $mysql->exec_file("sql/create_user_table.sql", $search, $replace);
}

function SetupRegistrationTable()
{
	$mysql = GetMysql();
	$search = array(
		"\$reg_table",
		"\$children_table",
		"\$sessions_table",
		"\$engine",
	);
	$replace = array(
		get_config_value("registered_users_table_name"),
		get_config_value("registered_children_table_name"),
		get_config_value("registered_sessions_table_name"),
		GetEngineName(),
	);
	$results = $mysql->exec_file("sql/create_registration_table.sql", $search, $replace);
}

function DeleteSystemTables()
{
    $mysql = GetMysql();

	$sqlDropTable = "DROP TABLE IF EXISTS `" . get_config_value("uploads_table_name") . "`";
	$mysql->query($sqlDropTable);

	$sqlDropTable = "DROP TABLE IF EXISTS `" . get_config_value("registered_users_table_name") . "`";
	$mysql->query($sqlDropTable);

	$sqlDropTable = "DROP TABLE IF EXISTS `" . get_config_value("registered_users_table_name");
	$mysql->query($sqlDropTable);

	$sqlDropTable = "DROP TABLE IF EXISTS `" . get_config_value("registered_children_table_name");
	$mysql->query($sqlDropTable);

	$sqlDropTable = "DROP TABLE IF EXISTS `" . get_config_value("registered_sessions_table_name");
	$mysql->query($sqlDropTable);
}

function SetupSystemTables()
{
	SetupRegistrationTable();
}

function RegisterUser($name, $userid, $pwd)
{
	global $g_mysql_babytracker_version;
    $mysql = GetMysql();

	$sql = "select * from `" . get_config_value("registered_users_table_name") . "` \n" .
			"where `userid`='$userid';\n";
	$results = $mysql->query($sql);
    $user = $mysql->query_results($results);
	if ($user)
	{
		return LoginUser($userid, $pwd);
	}

	$token = uniqid();
	vprint("Allocate token=$token");

	$sql = "insert into `" . get_config_value("registered_users_table_name") . "` set\n" .
			"`name`='$name',\n" .
			"`userid`='$userid',\n" .
			"`password`='$pwd',\n" .
			"`token`='$token',\n" .
			"`version`=$g_mysql_babytracker_version\n" .
			";\n";
	$mysql->query($sql);

	$sql = "select * from `" . get_config_value("registered_users_table_name") . "` \n" .
			"where `userid`='$userid';\n";
	$results = $mysql->query($sql);
    $user = $mysql->query_results($results);
	return $user;
}

function DeleteRegisteredUser($name, $userid)
{
    $mysql = GetMysql();

	$sql = "delete from `" . get_config_value("registered_users_table_name") . "` \n" .
			"where `userid`='$userid';\n";
    $mysql->query($sql);
}

function LoginUser($userid, $pwd)
{
    $mysql = GetMysql();

	$sql = "select * from `" . get_config_value("registered_users_table_name") . "` \n" .
			"where `userid`='$userid';\n";
	$results = $mysql->query($sql);
    $user = $mysql->query_results($results);
	if (!$user || ($user['password'] != $pwd))
	{
		vprint("$userid --- $pwd");
		error("LoginUser failed for $userid.  Invalid userid or password.");
	}

	return $user;
}

function RegisterChild($childname, $dob, $user)
{
	global $g_mysql_babytracker_version;
    $mysql = GetMysql();
	$user_token = $user['token'];
	$tablename = MakeNewUserTableName($user['userid'], $childname);

	$sql = "select * from `" . get_config_value("registered_children_table_name") . "` \n" .
			"where `user_token`='$user_token' AND `name`='$childname';\n";
	$results = $mysql->query($sql);
    $child = $mysql->query_results($results);

	if ($child)
	{
		$token = $child['token'];
		$sql = "update `" . get_config_value("registered_children_table_name") . "` set\n" .
				"`dob`='$dob'\n" .
				"where `token`='$token' \n" .
				";\n";
		$mysql->query($sql);
	}
	else
	{
		$token = uniqid();
		vprint("Allocate token=$token");

		$title = "Baby $childname Tracker";

		$sql = "insert into `" . get_config_value("registered_children_table_name") . "` set\n" .
				"`user_token`='$user_token',\n" .
				"`name`='$childname',\n" .
				"`dob`='$dob',\n" .
				"`token`='$token',\n" .
				"`tablename`='$tablename',\n" .
				"`title`='$title', \n" .
				"`version`=$g_mysql_babytracker_version\n" .
				";\n";
		$mysql->query($sql);
	}

	$sql = "select * from `" . get_config_value("registered_children_table_name") . "` \n" .
			"where `token`='$token';\n";
	$results = $mysql->query($sql);
    $child = $mysql->query_results($results);

	CreateChildTable($tablename, $child['version']);

	if ($child)
		return $child;

	error("ERROR: RegisterChild failed");
}

function RegisterSession($user, $child)
{
	global $g_mysql_babytracker_version;
    $mysql = GetMysql();
	$user_token = $user['token'];
	$child_token = $child['token'];
	$address = $_SERVER["REMOTE_ADDR"];

	$sql = "select * from `" . get_config_value("registered_sessions_table_name") . "` \n" .
			"where `user_token`='$user_token' AND `child_token`='$child_token' AND `registered_address`='$address';\n";
	$results = $mysql->query($sql);
    $session = $mysql->query_results($results);
	if ($session)
	{
		$token = $session['token'];
	}
	else
	{
		$token = uniqid();
		vprint("Allocate token=$token");

		$sql = "insert into `" . get_config_value("registered_sessions_table_name") . "` set\n" .
				"`user_token`='$user_token',\n" .
				"`child_token`='$child_token',\n" .
				"`token`='$token',\n" .
				"`registered_address`='$address',\n" .
				"`version`=$g_mysql_babytracker_version\n" .
				";\n";
		$mysql->query($sql);
	}

	$sql = "select * from `" . get_config_value("registered_sessions_table_name") . "` \n" .
			"where `token`='$token' AND \n" .
			"`registered_address`='$address'\n";
	$results = $mysql->query($sql);
    $session = $mysql->query_results($results);
	if ($session)
		return $session;

	error("ERROR: RegisterSession failed");
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

function GetChildData($token)
{
    $mysql = GetMysql();

	$sessionTable = get_config_value("registered_sessions_table_name");
	$childrenTable = get_config_value("registered_children_table_name");
	$address = $_SERVER["REMOTE_ADDR"];

	$sql = "select * from `$sessionTable`, `$childrenTable` \n" .
			"where `$sessionTable`.token='$token' AND `registered_address`='$address' AND " .
			"`$childrenTable`.token=`$sessionTable`.child_token;\n";
	$results = $mysql->query($sql);
    $child = $mysql->query_results($results);
	return $child;
}

function GetChildTableName($token)
{
	$child = GetChildData($token);
	return $child['tablename'];
}

function GetChildTableRow($sqlrowid, $token)
{
	$mysql = GetMysql();
	$table = GetChildTableName($token);

	$results = $mysql->query("select DATE_FORMAT('timestamp', '%c/%e/%Y') as Date, DATE_FORMAT('timestamp', '%l:%i %r') as Time, Type, Amount, Description from `$table` where `id`='$sqlrowid'");
    $row = $mysql->query_results($results);
	return $row;
}

function AddRowToChildTable($data, $token)
{
    $date = $data['date'];
    $time = $data['time'];
    $type = $data['type'];
    $amount = @$data['amount'];
    $description = @$data['description'];
	$formula = @$data['formula'];
	$breastmilk = @$data['breastmilk'];
	$left = @$data['left'];
	$right = @$data['right'];
	$mysql = GetMysql();
	$table = GetChildTableName($token);

    vprint('Starting');

    $row_data = "";
	if ($amount)        $row_data .= "`amount`='$amount', \n";
	if ($description)   $row_data .= "`description`='$description', \n";
	if ($formula)		$row_data .= "`attr`=`attr` | " . attrFormula . ", \n";
	if ($breastmilk)	$row_data .= "`attr`=`attr` | " . attrBreastMilk . ", \n";
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

function UpdateChildTableRow($data, $token)
{
	$sqlrowid = $data['sqlrowid'];
    $date = $data['date'];
    $time = $data['time'];
    $type = $data['type'];
    $amount = @$data['amount'];
    $description = @$data['description'];

	$mysql = GetMysql();
	$table = GetChildTableName($token);

    vprint('Starting');

    $row_data = "`timestamp`=NOW(), ";
	if ($amount)   		$row_data .= "amount='$amount', ";
	// Handle 0 value for amount
	if ($amount=="" && $amount!=="")   $row_data .= "amount='0', ";
	if ($description)   $row_data .= "description='$description', ";
	if ($date && $time) $row_data .= "`datetime`=STR_TO_DATE('$date $time', '%m/%d/%Y %l:%i %p'), ";
	if ($type) 			$row_data .= "`type`='$type'";

	$results = $mysql->query("select * from `$table` where `id`='$sqlrowid'");
    $row = $mysql->query_results($results);
	if ($row == null)
		error("Update failed rowid='$sqlrowid'");

	$sql = "update `$table` set $row_data where `id`='$sqlrowid';";
	$mysql->query($sql);

	$results = $mysql->query("select * from `$table` where `id`='$sqlrowid'");
    $row = $mysql->query_results($results);

	return $row;
}

function DeleteChildTableRow($sqlrowid, $token)
{
    vprint('Starting');
	$mysql = GetMysql();
	$table = GetChildTableName($token);

	$results = $mysql->query("select * from `$table` where `id`='$sqlrowid'");
    $row = $mysql->query_results($results);
	if ($row == null)
		error("Delete failed rowid='$sqlrowid'");

	$sql = "delete from `$table` where `id`='$sqlrowid';";
	$mysql->query($sql);

	return $row;
}

function ResultsToDisplayTable($mysql, $results)
{
    $tablehtml = "";
	while ($row = $mysql->query_results($results))
	{
        if ($tablehtml == "")
            $tablehtml = MakeDisplayTableHeader($row);
		$tablehtml .= DataToTableRow($row, $row['timestamp']);
	}
    $tablehtml .= MakeTableFooter();
    return $tablehtml;
}

function ChildTableResults($token)
{
	$mysql = GetMysql();
	$table = GetChildTableName($token);
	$results = $mysql->query("select id as sqlrowid, " .
							 "DATE_FORMAT(`datetime`, '%c/%e/%Y') as date, " .
							 "DATE_FORMAT(`datetime`, '%l:%i %p') as time, " .
							 "type, amount, description, timestamp " .
							 "from `$table` order by id desc LIMIT 50");
	$html = ResultsToDisplayTable($mysql, $results);
	return $html;
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

function MakeNewUserTableName($userid, $name)
{
	$specials = array(
		"@",
		".",
	);

	return "user_table_" . str_replace($specials, "_", $userid) . "_$name";
}

?>
