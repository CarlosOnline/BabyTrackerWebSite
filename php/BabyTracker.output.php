<?php
ignore_user_abort(1);
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set("display_errors", 1);

setcookie("babytracker_version", "2.1");

function validate_input($value, $type)
{
	switch($type)
	{
		case 'date':
			$value = utf8_decode($value);
			$value = htmlentities($value, ENT_QUOTES);
			$value = strip_tags($value);
			if (!preg_match("#^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$#", $value))
				$value = '';
			return $value;
			break;

		case 'time':
			$value = utf8_decode($value);
			$value = htmlentities($value, ENT_QUOTES);
			$value = strip_tags($value);
			if (!preg_match("/^[0-9]{1,2}:[0-9]{2} (AM|PM)$/", $value))
				$value = '';
			return $value;
			break;

		case 'number':
		case 'int':
			$value = utf8_decode($value);
			$value = htmlentities($value, ENT_QUOTES);
			$value = strip_tags($value);
			if (!preg_match("/^[0-9]+$/", $value))
				$value = '';
			return $value;
			break;

		case 'bool':
			$value = utf8_decode($value);
			$value = htmlentities($value, ENT_QUOTES);
			$value = strip_tags($value);
			if ($value != '1')
				$value = '';
			return $value;
			break;

		case 'decimal':
			$value = utf8_decode($value);
			$value = htmlentities($value, ENT_QUOTES);
			$value = strip_tags($value);
			if (!preg_match("/^[0-9.]+$/", $value))
				$value = '';
			return $value;
			break;

		case 'email':
			$value = utf8_decode($value);
			$value = htmlentities($value, ENT_QUOTES);
			$value = strip_tags($value);
			//if (!preg_match("/^[a-zA-Z_0-9]+@$[a-zA-Z_0-9.]/", $value))
			//	$value = '';
			return $value;
			break;

		case 'pwd':
			$value = utf8_decode($value);
			$value = htmlentities($value, ENT_QUOTES);
			$value = strip_tags($value);
			//if (!preg_match("/^[a-zA-Z_0-9]+@$[a-zA-Z_0-9.]/", $value))
			//	$value = '';
			return $value;
			break;

		case 'id':
			$value = utf8_decode($value);
			$value = htmlentities($value, ENT_QUOTES);
			$value = strip_tags($value);
			if (!preg_match("/^[a-zA-Z_0-9]+$/", $value))
				$value = '';
			return $value;
			break;

		case 'word':
			$value = utf8_decode($value);
			$value = htmlentities($value, ENT_QUOTES);
			$value = strip_tags($value);
			if (!preg_match("/^[a-zA-Z_]+$/", $value))
				$value = '';
			return $value;
			break;

		case 'words':
			$value = utf8_decode($value);
			$value = htmlentities($value, ENT_QUOTES);
			$value = strip_tags($value);
			if (!preg_match("/^[a-zA-Z_ ]+$/", $value))
				$value = '';
			return $value;
			break;

		case 'text':
			//$value = utf8_decode($value);
			$value = htmlentities($value, ENT_QUOTES);
			$value = strip_tags($value);
			return $value;
			break;

		case 'filename':
			$value = utf8_decode($value);
			$value = htmlentities($value, ENT_QUOTES);
			$value = strip_tags($value);
			if (!preg_match("/^[a-zA-Z_.]+$/", $value))
				$value = '';
			return $value;
			break;
	}
	error("Unknown type $type");
}

function check_injected($str)
{
  $injections = array('(\n+)',
              '(\r+)',
              '(\t+)',
              '(%0A+)',
              '(%0D+)',
              '(%08+)',
              '(%09+)'
              );
	$inject = join('|', $injections);
	$inject = "/$inject/i";
	if(preg_match($inject,$str))
		return true;
	else
		return false;
}

function get_input_option($item, $type)
{
	$value = @$_POST["$item"];
	if ($value)
	{
		$value = validate_input($value, $type);
		$value = check_injected($value) ? '' : $value;
		return $value;
	}
	$value = @$_GET["$item"];
	if ($value)
	{
		$value = validate_input($value, $type);
		$value = check_injected($value) ? '' : $value;
		return $value;
	}
}

function get_input_bool($item)   	{ return get_input_option($item, 'bool'); }
function get_input_date($item)   	{ return get_input_option($item, 'date'); }
function get_input_time($item)   	{ return get_input_option($item, 'time'); }
function get_input_int($item)    	{ return get_input_option($item, 'int'); }
function get_input_number($item) 	{ return get_input_option($item, 'number'); }
function get_input_decimal($item) 	{ return get_input_option($item, 'decimal'); }
function get_input_word($item) 		{ return get_input_option($item, 'word'); }
function get_input_words($item) 	{ return get_input_option($item, 'words'); }
function get_input_text($item) 		{ return get_input_option($item, 'text'); }
function get_input_email($item)  	{ return get_input_option($item, 'email'); }
function get_input_pwd($item)  		{ return get_input_option($item, 'pwd'); }
function get_input_filename($item) 	{ return get_input_option($item, 'filename'); }
function get_input_id($item)  		{ return get_input_option($item, 'id'); }

$verbose = get_input_bool('verbose');
$xml_view = get_input_bool("xml_view");
$no_xml = get_input_bool("no_xml");
$no_sql = get_input_bool("no_sql");
$submission_data = "";
$last_response = "";
$last_http_errors = "";
$last_error = "";
$last_errno = 0;
$throw_on_error = 0;
$output_filename = "";
$flush = 0;

//session_write_close();
// log command to file

LogCommandLine("START:");

//phpinfo();
if (get_input_bool('debug') || get_input_bool('debugmode')) {
	print("verbose turned on via debug mode<br/>");
	$verbose = 1;
}

$browser = $_SERVER['HTTP_USER_AGENT'];
if (stripos($browser, 'Firefox'))
{
	// turn of xml for firefox
	$xml_view = 0;
}

if (@$_GET["output_file"])
{
	set_output_filename("output/" . $_GET["output_file"]);
	delete_output_file(get_output_filename());
}
elseif (get_input_word('postaction'))
{
	set_output_filename("output/" . get_input_word('postaction') . ".htm");
	delete_output_file(get_output_filename());
	//if ($verbose)
	//	array_print($_POST);
}
elseif (get_input_word('testaction'))
{
	set_output_filename("output/" . get_input_word('testaction') . ".htm");
	delete_output_file(get_output_filename());
}

if ($xml_view != 0) {
	@header('Content-type: text/xml');
	echo "<responses>";
}
else {
	@header('Content-type: text/html');
}

$config = parse_ini_file("BabyTracker.config.ini");

function get_config_value($key)
{
	global $config;
	if (@$config[$key])
		return $config[$key];

	vprint("<b>warning</b> config $key not found");
}

function GetCachedSessionToken() {
    return get_config_value_ex("session_token", "session_token.cfg");
}

function get_config_value_ex($key, $file)
{
	$settings = parse_ini_file($file);
	if (@$settings[$key])
		return $settings[$key];

	//vprint("<b>warning</b> config $key not found");
}


$rand = rand(0,2000);
if (!$xml_view)
	vprint("uri=[" . $_SERVER["REQUEST_URI"] . "]");
vprint("rand=$rand php version = " . phpversion() . " fileversion=" . getlastmod());

function throw_on_error()
{
    global $throw_on_error;
	return $throw_on_error;
}

function set_throw_on_error($set)
{
    global $throw_on_error;
	$throw_on_error = $set;
}

function set_last_errno($error)
{
	global $last_errno;
    $last_errno = $error;
}

function set_last_response($string, $status, $error)
{
	global $last_response, $last_errno;
	$last_response = "response=$string http status=$status http error=$error";
    $last_errno = $status;
}

function get_last_response()
{
	global $last_response;
	return $last_response;
}

function get_last_errno()
{
	global $last_errno;
	return $last_errno;
}

function set_last_error($string)
{
    global $last_error;
    $last_error = $string;
}

function get_last_error()
{
    global $last_error;
    return $last_error;
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

function set_output_flag($string, $value)
{
    global $no_xml, $no_sql, $xml_view, $verbose;

    switch($string)
    {
        case 'sql':
        case "no_sql":
            $no_sql = $value;
            break;

        case 'xml':
        case "no_xml":
            $no_xml = $value;
            break;
    }
}

function get_caller_method()
{
    $traces = debug_backtrace();

    if (isset($traces[2]))
    {
        return $traces[2]['function'];
    }

    return null;
}

function get_caller_func()
{
    $traces = debug_backtrace();

    if (isset($traces[2]))
    {
        return $traces[2]['function'];
    }

    return null;
}

function set_output_filename($filename)
{
	global $output_filename, $flush;
	$output_filename = $filename;
	//echo "output_filename=$filename <br/>";
	$flush = 1;
	ob_start();
}

function get_output_filename()
{
	global $output_filename;
	return $output_filename;
}

function send_to_file($string, $filename)
{
	$fp = fopen("$filename", "a+");
	fwrite($fp, $string);
	fflush($fp);
	fclose($fp);
}

function send_to_newfile($string, $filename)
{
	$fp = fopen("$filename", 'w');
	fwrite($fp, $string);
	fflush($fp);
	fclose($fp);
}

function flush_to_file($filename)
{
	$fp = fopen("$filename", 'a');
	fwrite($fp, ob_get_contents());
	fflush($fp);
	fclose($fp);
}

function delete_output_file($filename)
{
    @unlink($filename);
}

function delete_all_output_files()
{
	if ($handle = opendir('output')) {
		while (false !== ($file = readdir($handle))) {
			if ($file == "." || $file == "..")
				continue;

			vprint("deleting file output/$file");
			unlink("output/$file");
		}
		closedir($handle);
	}
}

function flush_buffers($force = 0)
{
	global $flush;
	if (!$flush && !$force)
		return;

    if (get_output_filename())
	    flush_to_file(get_output_filename());

    @ob_end_flush();
    @ob_flush();
    flush();
	//@ob_end_clean();
    ob_start();
}

function print_array_selective($var, $arrayOfObjectsToHide=null, $fontSize=11)
{
    $text = print_r($var, true);

    if (is_array($arrayOfObjectsToHide)) {

        foreach ($arrayOfObjectsToHide as $objectName) {

            $searchPattern = '#('.$objectName.' Object\n(\s+)\().*?\n\2\)\n#s';
            $replace = "$1<span style=\"color: #FF9900;\">--&gt; HIDDEN - courtesy of wtf() &lt;--</span>)";
            $text = preg_replace($searchPattern, $replace, $text);
        }
    }

    // color code objects
    $text = preg_replace('#(\w+)(\s+Object\s+\()#s', '<span style="color: #079700;">$1</span>$2', $text);
    // color code object properties
    $text = preg_replace('#\[(\w+)\:(public|private|protected)\]#', '[<span style="color: #000099;">$1</span>:<span style="color: #009999;">$2</span>]', $text);

    echo '<pre style="font-size: '.$fontSize.'px; line-height: '.$fontSize.'px;">'.$text.'</pre>';
}

function array_print($obj)
{
	global $verbose;
	if ($verbose) {
		$func = get_caller_func();
		print("$func array_print(): ");
		print_array_selective($obj);
	}
}

function varray_print($obj)
{
    global $verbose;
    if ($verbose) {
		$func = get_caller_func();
		print("$func array_print(): ");
        print_array_selective($obj);
    }
}

function __print($string, $type = 'text', $verbose_string = 'true')
{
	global $no_xml, $xml_view, $verbose;

	if ($verbose_string == "" || $verbose != 0)
	{
		switch($type)
		{
			case 'xml':
				if ($no_xml) {
					// noop
				}
				elseif ($xml_view != 0)
					echo "$string";
				else
                {
					echo htmlentities($string) . "<br/>";
                    //@$obj = simplexml_load_string($string);
                    //array_print($obj);
                }
				break;

			case 'text':
			default:
				if ($xml_view != 0)
					echo "<i>$string</i>";
				else
					echo "$string<br/>";
				break;
		}
	}
}

function xml_print($string)
{
	__print($string, 'xml');
}

function vxml_print($string)
{
	__print($string, 'xml');
}

function vprint($string)
{
	$func = get_caller_func();
	__print("<b>$func()</b> $string", 'text', 'verbose');
}

function iprint($string)
{
	__print($string, 'text');
}

function error($string)
{
	flush_buffers(true);
	global $submission_data, $last_response;

	$func = get_caller_func();

	__print("<h3>BabyTracker Server Error</h3>", 'text', "");
	__print("Send the following text to <a href='mailto:babytracker@pacifier.com' title='Email Baby Tracker Support'>babytracker@pacifier.com</a>", 'text', "");
	__print("********************************************", 'text', "");
	__print("<b>$func()</b> <span style='color: #FF0000; font-size: medium;'>$string</span>", 'text', "");
	__print("<ErrorMessage>$string</ErrorMessage>", 'text', "");
	add_response_value('Success', 'false');
	add_response_value('Error', 'true');

	if ($submission_data)
		__print("Data: $submission_data", 'text', "");

    if ($last_response)
        __print($last_response, 'xml', "");

    set_last_error(get_last_response());

    //__print("********************************************");
	//array_print(debug_backtrace());
	//__print("********************************************");

	flush_to_file("output/errors.htm");
	flush_buffers(true);

	if (throw_on_error())
	{
		throw new Exception("Generic Error Occurred for $submission_data.");
	}
	else
	{
		die("</responses>");
	}
}

function sql_error($string)
{
	flush_buffers(true);

	global $no_sql, $submission_data;
    __print("sql_error $string " . mysql_error());

	__print("<h1>BabyTracker Sql Server sql_error</h1>");
	__print("Send the following text to <a href='mailto:babytracker@pacifier.com' title='Email Baby Tracker Support'>babytracker@pacifier.com</a>");
	__print("********************************************");
	if ($no_sql)
        __print("sql sql_error=" . mysql_error());
    else
        __print("$string, sql sql_error=" . mysql_error());

	__print("<ErrorMessage>$string</ErrorMessage>", 'text', "");
	add_response_value('Success', 'false');
	add_response_value('Error', 'true');

	if ($submission_data)
		__print("Data: $submission_data");
	__print("********************************************");
	array_print(debug_backtrace());
	__print("********************************************");

	flush_to_file("output/errors.htm");
	flush_buffers(true);
	die("</responses>");
}

function success($string)
{
	flush_buffers(true);

	$func = get_caller_func();

	__print("<h3>BabyTracker Success</h3>", 'text', "");
	__print("********************************************", 'text', "");
	__print("<b>$func()</b> <span style='font-size: medium;'>$string</span>", 'text', "");
	__print("<SuccessMessage>$string</SuccessMessage>", 'text', "");
	add_response_value('Success', 'true');
}

function add_response_value($tag, $value)
{
	//flush_buffers(true);
	__print("<$tag>$value</$tag>", 'text', "");
}

function DataToString($data)
{
	$string = "";
	foreach($data as $key => $value) {
		$string .= "$key=$value ";
	}
	return $string;
}

function DataToStringEx($data)
{
	$string = "";
	foreach($data as $key => $value) {
		$string .= "$key=$value& ";
	}
	return $string;
}

function CommandLineToString($data)
{
	$string = "";
	foreach($data as $key => $value) {
		if ($key != 'pwd' && $key != 'password')
			$string .= "$key=$value; ";
	}
	return $string;
}

function LogCommandLine($prefix)
{
	$data = "<span style='font-size: 11;'>";
	$data .= "$prefix ";
	$data .= date('Y-m-d H-i-s', time());
	$data .= "<b>";
	$data .= get_input_word('testaction');
	$data .= ".";
	$data .= get_input_word('postaction');
	$data .= "</b> POST=[";
	$data .= CommandLineToString($_POST);
	$data .= "] GET=[";
	$data .= CommandLineToString($_GET);
	$data .= "]</span><br/>";
	send_to_file($data, "output/BabyTracker.history.htm");
}

function LogCommandLineMessage($prefix, $message)
{
	$data = "<span style='font-size: 11;'>";
	$data .= "$prefix ";
	$data .= date('Y-m-d H-i-s', time());
	$data .= "<i>";
	$data .= get_input_word('testaction');
	$data .= ".";
	$data .= get_input_word('postaction');
	$data .= "</i>";
	$data .= " $message</span><br/>";
	send_to_file($data, "output/BabyTracker.history.htm");
}

function GetTableStyle() {
    return "style='font-size: 12; border-style: solid solid solid solid ; border-width: thin; border-color: #808080; padding:0;'";
}

function GetTableRowStyle() {
    //return "style='font-size: 8; border-style: none none solid solid ; border-width: thin; border-color: #808080; padding:0;'";
    return "style='font-size: 12; border-style: solid dotted; border-width: thin; border-color: #808080; padding:0;'";
}

function MakeTableRow($data)
{
    $style = GetTableRowStyle();
	$string = "<tr>";
    foreach ($data as $key => $value) {
        $string .= "<td>" . FormatValue($value) . "</td>";
    }
	$string .= "</tr>";
	return $string;
}

function MakeTableHeader($data)
{
    $styleTable = GetTableStyle();
    $style = GetTableRowStyle();
	$string = "<span style='font-size: 11;'>";
    $string .= "<table class='dataTable' $styleTable><tr class='dataRow' $styleTable>";
    foreach($data as $key => $value) {
        $string .= "<th class='dataHeader' $style>" . $key . "</th>";
    }
	$string .= "</tr>";
	return $string;
}

function MakeDisplayTableHeader($data)
{
    $styleTable = GetTableStyle();
    $style = GetTableRowStyle();
	$string = "<span style='font-size: 11;'>";
    $string .= "<table class='dataTable' $styleTable><tr class='dataRow' $styleTable>";
	$string .= "<th class='dataHeader' $style>Edit</th>";
	$string .= "<th class='dataHeader' $style>Date</th>";
	$string .= "<th class='dataHeader' $style>Time</th>";
	$string .= "<th class='dataHeader' $style>Type</th>";
	$string .= "<th class='dataHeader' $style>Amount</th>";
	$string .= "<th class='dataHeader' $style>Description</th>";
	$string .= "<th class='dataHeader' $style>Delete</th>";
	$string .= "</tr>";
	return $string;
}

function MakeTableFooter()
{
	return "</table></span>";
}

function DataToTableRow($data, $timestamp)
{
	$dataString = DataToStringEx($data) . "timestamp=" . $timestamp . "& ";
	$editTag = "<a href='javascript:OnEditRow_Click(\"$dataString\");'><img src='../images/edit.png' /></a>";
	$deleteTag = "<a href='javascript:OnDeleteRow_Click(\"$dataString\");'><img src='../images/delete.png' /></a>";

	$string = "<tr class='dataRow'>";
	$string .= "<td class='dataCell'>$editTag</td>";
	$string .= "<td class='dataCell'>" . @$data['date'] . "</td>";
	$string .= "<td class='dataCell'>" . @$data['time'] . "</td>";
	$string .= "<td class='dataCell'>" . @$data['type'] . "</td>";
	$string .= "<td class='dataCell'>" . @$data['amount'] . "</td>";
	$string .= "<td class='dataCell'>" . @$data['description'] . "</td>";
	$string .= "<td class='dataCell'>$deleteTag</td>";
	$string .= "</tr>";

	return $string;
}

function MakeTableHeader_trans($data)
{
    //$table_id = "rounded-corner";
    //$table_id = "hor-minimalist-a";
    //$table_id = "hor-minimalist-b";
    //$table_id = "ver-minimalist";
    //$table_id = "box-table-a";
    //$table_id = "box-table-b";
    //$table_id = "hor-zebra";
    //$table_id = "ver-zebra";
    //$table_id = "vzebra-odd";
    //$table_id = "vzebra-even";
    //$table_id = "one-column-emphasis";
    //$table_id = "newspaper-a";
    //$table_id = "newspaper-b";
    //$table_id = "newspaper-c";
    //$table_id = "rounded-corner";

  $table_id = "gradient-style";
    //$table_id = "pattern-style-a";
    //$table_id = "pattern-style-b";

    if (sizeof($data) == 0)
        return "<table id='$table_id'><tr>";

    print("<head><link href='../table.css' type='text/css' rel='stylesheet'/><link href='table.css' type='text/css' rel='stylesheet'/></head>");

	$string = ""; //"<span style='font-size: 11;'>";
    $string .= "<table id='$table_id'><tr>";
    foreach($data as $array) {
        $string .= "<th>" . $array[0] . "</th>";
    }
	$string .= "</tr>";
	return $string;
}

function MakeTableRow_trans($data)
{
    if (sizeof($data) < 1)
        return;

    $html = "";
    $count = sizeof($data[0]);
    for($idx=1; $idx < $count; $idx++)
    {
        $row = array();
        foreach($data as $array) {
            @$row[] = $array[$idx];
        }
        $html .= MakeTableRow($row);
    }

    return $html;
}


function transpose($data)
{
    $new_array = array();

    $count = sizeof($data[0]);
    for($idx=0; $idx < $count; $idx++)
    {
        $row = array();
        foreach($data as $array) {
            $row[] = $array[$idx];
        }
        $new_array[] = $row;
    }

    return $new_array;
}

function SetHtmlCookie($key, $value)
{
	define('SecondsPerMinute', 60);
	define('MinutesPerHour', 60);
	define('HoursPerDay', 24);
	define('OneDayInSeconds', 60*60*24);

	$seconds = OneDayInSeconds * 724;
    vprint("setcookie($key)=$value time=$seconds");
	setcookie($key, $value, time() + $seconds);
}

function hex_chars($data) {
    $mb_chars = '';
    $mb_hex = '';
    for ($i=0; $i<mb_strlen($data, 'UTF-8'); $i++) {
        $c = mb_substr($data, $i, 1, 'UTF-8');
        $mb_chars .= '{'. ($c). '}';

        $o = unpack('N', mb_convert_encoding($c, 'UCS-4BE', 'UTF-8'));
        $mb_hex .= '{'. hex_format($o[1]). '}';
    }
    $chars = '';
    $hex = '';
    for ($i=0; $i<strlen($data); $i++) {
        $c = substr($data, $i, 1);
        $chars .= '{'. ($c). '}';
        $hex .= '{'. hex_format(ord($c)). '}';
    }
    return array(
        'data' => $data,
        'chars' => $chars,
        'hex' => $hex,
        'mb_chars' => $mb_chars,
        'mb_hex' => $mb_hex,
    );
}

function hex_format($o) {
    $h = strtoupper(dechex($o));
    $len = strlen($h);
    if ($len % 2 == 1)
        $h = "0$h";
    return $h;
}

function read_input_option($key, &$array, $type)
{
	$value = get_input_option($key, $type);
	if ($value != "") $array[$key] = $value;
}

function read_config_option($config_val, $key, &$array)
{
	$value = get_config_value($config_val);
	if ($value != "") $array[$key] = $value;
}

function FormatNumberValue($value)
{
	$decimal = strpos($value, ".00");
	if ($decimal != false)
		return substr($value, 0, $decimal);

	$decimal = strpos($value, ".");
	if ($decimal != false & (strlen($value) - $decimal) > 3)
		return number_format((float) $value, 2);

	switch($value)
	{
		case '0':
		case "0.0":
		case "0.00":
			return "";
	}

    return $value;
}

function FormatValue($value)
{
	if (!$value)
	    return $value;

	if (is_numeric($value))
		return FormatNumberValue($value);

	switch($value)
	{
		case "#N/A":
			$value = "";
			break;

		case 'Pump':
		case 'Breast':
		case 'Nurse':
		case 'Bottle':
		case 'Daipering':
		case 'Diapering':
            $value = "<b>$value</b>";
			break;
	}

    return $value;
}

function array_transpose($array, $selectKey = false) {
    if (!is_array($array)) return false;
    $return = array();
    foreach($array as $key => $value) {
        if (!is_array($value)) return $array;
        if ($selectKey) {
            if (isset($value[$selectKey])) $return[] = $value[$selectKey];
        } else {
            foreach ($value as $key2 => $value2) {
                $return[$key2][$key] = $value2;
            }
        }
    }
    return $return;
}

function flipDiagonally($arr) {
    $out = array();
    foreach ($arr as $key => $subarr) {
        foreach ($subarr as $subkey => $subvalue) {
                $out[$subkey][$key] = $subvalue;;
        }
    }
    return $out;
}

//echo 'display_errors = ' . ini_get('display_errors') . "<br\>\n";
//echo 'register_globals = ' . ini_get('register_globals') . "<br\>\n";
//echo 'post_max_size = ' . ini_get('post_max_size') . "<br\>\n";
//echo 'post_max_size+1 = ' . (ini_get('post_max_size')+1) . "<br\>\n";
//echo 'post_max_size in bytes = ' . return_bytes(ini_get('post_max_size'));
?>
