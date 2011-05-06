<?php

require_once("BabyTracker.output.php");

/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */

function BabyTracker_UserId() {
	return get_config_value("babytracker_userid");
}

function BabyTracker_Pwd() {
	return get_config_value("babytracker_pwd");
}

function Data_Worksheet() {
	return "Data";
}

class RowData {
	public $row;
	public $col;
	public $value;

	public function RowData($row, $col, $value)
	{
		$this->row = $row;
		$this->col = $col;
		$this->value = $value;
	}
}

class spreadsheet {

	private $spreadsheet;
	private $worksheet;
	private $spreadsheetid;
	private $worksheetid;
	private $column_array;
	private $key;
	private $curl;
	private $curl_writely;
	private $curl_wise;
	private $token_writely;
	private $token_wise;

	public function __construct() {
	}

	private function reset() {

		$this->resetSheet();
		$this->close_curl();

		$this->key = "";
		$this->token = "";
		$this->token_wise = "";
		$this->token_writely = "";
	}

	public function resetSheet()
	{
		$this->spreadsheet = "";
		$this->spreadsheetid = "";
		$this->worksheet = "";
		$this->worksheetid = "";
		$this->column_array = 0;
	}

	public function close() {
		$this->reset();
	}

	public function GetSessionToken($token)
	{
		if (isset($_SESSION['google_token'])) {
			vprint("<b>session token already recieved</b>");
			return $_SESSION['google_token'];
		}

		if (!$token)
		{
			$url = "https://www.google.com/accounts/AuthSubRequest?";
			$url .= "next=https://secure.iinet.com/joyofplaying.com/BabyTracker/BabyTracker.php";
			$url .=	"&scope=" . urlencode("https://spreadsheets.google.com/feeds/");
			$url .= "&session=1";
			//$url .= "&secure=1";
			//$url .= "&hd=" . UserId();

			vprint($url);

			//http(s)://spreadsheets.google.com/feeds/
			$authorizationUrl = "<p>BabyTracker needs access to your Google account to install a spreadsheet. " .
								"To authorize BabyTracker to access your account, <a target='_blank' href='$url'>log in to your account</a>.</p>";
			print($authorizationUrl);
			return;
		}
		$url = "https://www.google.com/accounts/AuthSubSessionToken";
		$headers = array(
			//"Content-Type: application/x-www-form-urlencoded",
			"GET /accounts/AuthSubSessionToken HTTP/1.1",
			"Authorization: AuthSub token='$token'",
			//"User-Agent: " . $_SERVER['HTTP_USER_AGENT'],
			//"Host: www.google.com",
			//"Accept: text/html, image/gif, image/jpeg, *; q=.2, q=.2",
			//"Connection: keep-alive",
			//"service" => "wise",
			);

		vprint("url=$url");
		array_print($headers);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, false);
		$response = curl_exec($curl);
		vprint($response);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        set_last_response($response, $status, curl_errno($curl));

		if ($response === false || ($status != 200))
			error("html return code = $status errno=" . curl_errno($curl) . " response=$response");

		curl_close($curl);
		$curl = "";
	}

	private function authenticate_wise($username, $password)
	{
        //vprint("$username ***");
		if ($username == UserId() && GetCachedSessionToken()) {
			$this->token_wise = GetCachedSessionToken();
			return $this->token_wise;
		}

		$url = "https://www.google.com/accounts/ClientLogin";
		$fields = array(
			"accountType" => "HOSTED_OR_GOOGLE",
			"Email" => $username,
			"Passwd" => $password,
			"service" => "wise",
			"source" => "pfbc"
		);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
		$response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        set_last_response($response, $status, curl_errno($curl));

		if ($response === false || ($status != 200))
			error("html return code = $status errno=" . curl_errno($curl));

		curl_close($curl);
		$curl = "";

		if(stripos($response, "auth=") == false)
			error("auth value missing from response $response");

		preg_match("/auth=([a-z0-9_\-]+)/i", $response, $matches);
		$this->token_wise = $matches[1];
		return $this->token_wise;
	}

	private function authenticate_writely($username, $password)
	{
		//vprint("$username *****");
		if ($username == UserId() && GetCachedSessionToken()) {
			$this->token_writely = GetCachedSessionToken();
			return $this->token_writely;
		}

		$url = "https://www.google.com/accounts/ClientLogin";
		$fields = array(
			"accountType" => "GOOGLE",
			"Email" => $username,
			"Passwd" => $password,
			"service" => "writely",
			"source" => "Baby Tracker"
		);

		$curl = curl_init("https://www.google.com/accounts/ClientLogin");
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

		$response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        set_last_response($response, $status, curl_errno($curl));

		if ($response === false || ($status != 200))
			error("authenticate_writely() html return code = $status errno=" . curl_errno($curl));

		curl_close($curl);
		$curl = "";

		if(stripos($response, "auth=") == false)
			error("authenticate_writely() auth value missing from response $response");

		preg_match("/auth=([a-z0-9_\-]+)/i", $response, $matches);
		$this->token_writely = $matches[1];
		return $this->token_writely;
	}

    public function authenticate($username, $password)
    {
		//vprint("starting");
        $this->authenticate_wise($username, $password);
        $this->authenticate_writely($username, $password);
        return $this->token_writely;
    }

	public function setSpreadsheet($title) {
		$this->spreadsheet = $title;
	}

	public function setSpreadsheetId($id) {
		$this->spreadsheetid = $id;
	}

	public function getSpreadsheetId() {
		return $this->spreadsheetid;
	}

	public function setWorksheet($title) {
		$this->worksheet = $title;
	}

	public function setWorksheetId($id) {
		$this->worksheetid = $id;
		if ($id)
			vprint("worksheetid = " . $this->worksheetid);
	}

	public function getWorksheetId() {
		return $this->worksheetid;
	}

	public function setKey($key) {
		$this->key = $key;
	}

	public function getKey() {
		return $this->key;
	}

	public function getColumnArray()
	{
		if ($this->column_array)
			return $this->column_array;

		vprint("Starting");
		$url = "https://spreadsheets.google.com/feeds/cells/" . $this->spreadsheetid . "/" . $this->worksheetid . "/private/full?max-row=1";
		vprint("url=$url");
		$response = $this->exec_curl_get($url, true);

		$columnIDs = array();
		$xml = simplexml_load_string($response);
		if($xml->entry)
		{
			//xml_print($xml->entry[0]->content);
			$columnSize = sizeof($xml->entry);
			for($c = 0; $c < $columnSize; ++$c)
				$columnIDs[] = strtolower($xml->entry[$c]->content[0]);
		}
		//varray_print($columnIDs);
		$this->column_array = $columnIDs;
		return $columnIDs;
	}

	public function getColumnIndex($name)
	{
		$columnArray = $this->getColumnArray();
		if ($columnArray)
		{
			$count = sizeof($columnArray);
			for($idx=0; $idx < $count; $idx++)
			{
				if ($columnArray[$idx] == $name)
				{
						return $idx + 1;
				}
			}
		}

		return null;
	}

	public function add($data, $verify_columns = 0)
	{
		//vprint("Starting");

		$url = $this->getPostUrl();

		if ($verify_columns)
		{
			$columnIDs = $this->getColumnIDs();
			if(!$columnIDs)
				error("add() missing column IDs");
		}

		array_print($data);

		$post_data = '<entry xmlns="http://www.w3.org/2005/Atom" xmlns:gsx="http://schemas.google.com/spreadsheets/2006/extended">';
		foreach($data as $key => $value) {
			$key = $this->formatColumnID($key);
			if(!$verify_columns || in_array($key, $columnIDs)) {
				$post_data .= "<gsx:$key><![CDATA[$value]]></gsx:$key>";
                //vprint("adding $key , $value");
            }
		}
		$post_data .= '</entry>';
		vxml_print($post_data);

		return $this->exec_curl_post($url, $post_data, true);
	}

	public function update($row, $col, $data)
	{
		vprint("Starting $row, $col, $data");

		$url = $this->getLinkUrl("http://schemas.google.com/spreadsheets/2006#cellsfeed");
		$cell = "R" . $row . "C" . $col;

		$fields = '<entry xmlns="http://www.w3.org/2005/Atom" xmlns:gs="http://schemas.google.com/spreadsheets/2006">';
		$fields .=  "<id>https://spreadsheets.google.com/feeds/cells/$this->spreadsheetid/$this->worksheetid/private/full/$cell</id>";
		$fields .= "<link rel='edit' type='application/atom+xml' ";
		$fields .= "href='https://spreadsheets.google.com/feeds/cells/$this->spreadsheetid/$this->worksheetid/private/full/$cell/latest'/>";
		$fields .= "<gs:cell row='$row' col='$col' inputValue='$data'/>";
		$fields .= '</entry>';
		vxml_print($fields);

		return $this->exec_curl_post($url, $fields, true);
	}

	public function updateRow($data)
	{
		vprint("Starting");
		array_print($data);
		$row = $data["sheetrowid"];

		$dataset = array();
		$dataset[] = new RowData($row, $this->getColumnIndex("date"), $data["date"]);
		$dataset[] = new RowData($row, $this->getColumnIndex("time"), $data["time"]);
		$dataset[] = new RowData($row, $this->getColumnIndex("type"), $data["type"]);
		$dataset[] = new RowData($row, $this->getColumnIndex("amount"), $data["amount"]);
		$dataset[] = new RowData($row, $this->getColumnIndex("description"), $data["description"]);
		$dataset[] = new RowData($row, $this->getColumnIndex("sqlrowid"), $data["sqlrowid"]);

		return $this->batch_update($dataset);
	}

	public function batch_update($dataset)
	{
		vprint("Starting");

		$url = $this->getLinkUrl("http://schemas.google.com/spreadsheets/2006#cellsfeed");
		$post_url = $this->getPostUrl();
		$sheetid = "$this->spreadsheetid/$this->worksheetid";

		$batch_url    = "https://spreadsheets.google.com/feeds/cells/$sheetid/private/full/batch";
		$id_url       = "https://spreadsheets.google.com/feeds/cells/$sheetid/private/full";
		$cellfeed_url = "https://spreadsheets.google.com/feeds/cells/$sheetid/private/full";
		$url = $batch_url;

		$entry = "";
		for ($idx = 0; $idx < sizeof($dataset); $idx++)
		{
			$row = $dataset[$idx]->row;
			$col = $dataset[$idx]->col;
			$cell = "R" . $row . "C" . $col;
			$data = htmlentities($dataset[$idx]->value);
			$batchid = "A$idx";
			$entry .= "<entry>" . PHP_EOL .
						"<batch:id>$batchid</batch:id>" .PHP_EOL .
						"<batch:operation type='update'/>" .PHP_EOL .
						"<id>$cellfeed_url/$cell</id>" .PHP_EOL .
						"<link rel='edit' type='application/atom+xml' " .PHP_EOL .
							"href='$cellfeed_url/$cell/latest'/>" .PHP_EOL .
							"<gs:cell row='$row' col='$col' inputValue='$data'/>" .PHP_EOL .
					"</entry>" . PHP_EOL;
		}

		$feed = "<feed xmlns='http://www.w3.org/2005/Atom' " .
				"xmlns:batch='http://schemas.google.com/gdata/batch' " .
				"xmlns:openSearch='http://a9.com/-/spec/opensearchrss/1.0/'".PHP_EOL.
				"xmlns:g='http://base.google.com/ns/1.0'".PHP_EOL.
				"xmlns:gs='http://schemas.google.com/spreadsheets/2006' >" .PHP_EOL .

					"<id>$id_url</id> " .PHP_EOL .
					$entry .PHP_EOL .
				"</feed>";

		$results = $this->exec_curl_post($url, $feed, true);

		vprint("processing results");
		$results = str_replace("batch:", "", $results);
		$results = str_replace("batch:", "", $results);
		$results = str_replace("gs:", "", $results);
		$xml = simplexml_load_string($results);
		$count = sizeof($xml->entry);
		for($idx=0; $idx < $count; $idx++)
		{
			$entry = $xml->entry[$idx];
			//vprint("Processing result row=" . $entry->title . " code=" . $entry->status->attributes()->code . " reason=" . $entry->status->attributes()->reason);

			if ($entry->status->attributes()->code != 200 || $entry->status->attributes()->reason != "Success")
			{
				set_last_response($results, $entry->status->attributes()->reason, $entry->status->attributes()->code);
				error("Updating row=" . $entry->title . " code=" . $entry->status->attributes()->code . " reason=" . $entry->status->attributes()->reason);
			}
		}
		return $xml;
	}

	public function get_cells($minrow, $mincol, $maxrow, $maxcol)
	{
		vprint("Staring range=$minrow $mincol $maxrow $maxcol");

		if (!$this->getPostUrl())
            error("failed to get post url");

        $url = "https://spreadsheets.google.com/feeds/cells/$this->spreadsheetid/$this->worksheetid/private/basic?min-row=$minrow&min-col=$mincol&max-col=$maxcol";
		return $this->exec_curl_get($url, true);
	}

	public function CopyDocument($key, $newtitle)
	{
		vprint("copy $key to $newtitle");

		$fields = "<?xml version='1.0' encoding='UTF-8'?>".
			"<entry xmlns='http://www.w3.org/2005/Atom'>".
			"<id>https://docs.google.com/feeds/default/private/full/document%3A".$key."</id>".
			"<title>".$newtitle."</title>".
			"</entry>";
		//vxml_print($fields);

		$url = "https://docs.google.com/feeds/default/private/full/";
		$response = $this->exec_curl_post($url, $fields, false);

		$res = "gd:resourceId";
		$pos = stripos($response, $res);
		if($pos == false)
			error("CopyDocument() failed to find gd:resourceId tag in response");

		preg_match("/([%:A-Za-z0-9]+)/i", substr($response, $pos + strlen($res)), $matches);
		$newKey = $matches[0];

		if ($newKey == "")
			error("CopyDocument() Failed to find spreadsheet id within gd:resourceId tag");

		vprint("$key copied to new key=$newKey");
		return $newKey;
	}

	function AddCollaborator($key, $collaborator)
	{
		vprint("$collaborator to key=$key");
		if ($key == "")
			error("AddCollaborator() missing key for request");

		$fields = "<entry xmlns='http://www.w3.org/2005/Atom' xmlns:gAcl='http://schemas.google.com/acl/2007'>".
				"<category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/acl/2007#accessRule'/>".
				"<gAcl:role value='writer'/>".
				"<gAcl:scope type='user' value='$collaborator'/>".
				"</entry>";
		vxml_print($fields);

		$url = "https://docs.google.com/feeds/default/private/full/$key/acl";
		$response = $this->exec_curl_post($url, $fields, false);
		return $response;
	}

	private function getColumnIDs()
	{
		$url = "https://spreadsheets.google.com/feeds/cells/" . $this->spreadsheetid . "/" . $this->worksheetid . "/private/full?max-row=1";
		vprint("url=$url");
		$response = $this->exec_curl_get($url, true);

		$columnIDs = array();
		$xml = simplexml_load_string($response);
		if($xml->entry)
		{
			$columnSize = sizeof($xml->entry);
			for($c = 0; $c < $columnSize; ++$c)
				$columnIDs[$xml->entry[$c]->content] = $this->formatColumnID($xml->entry[$c]->content);
		}
		varray_print($columnIDs);
		return $columnIDs;
	}

	private function get_curl_writely($url)
	{
		vprint("url=$url");

		if(empty($this->token_writely))
			error("get_curl_writely() no security token");

		if ($this->curl_writely == "")
		{
			$headers = array();
			$headers[] = "Host: docs.google.com";
			$headers[] = "Content-Type: application/atom+xml";
			if ($this->token_wise == GetCachedSessionToken())
				$headers[] = "Authorization: AuthSub token=" . GetCachedSessionToken();
			else
				$headers[] = "Authorization: GoogleLogin auth=" . $this->token_writely;
			$headers[] = "GData-Version: 3.0";
			$headers[] = "Content-Type: application/atom+xml; charset=UTF-8; type=entry";
			$headers[] = "Accept: application/atom+xml; charset=UTF-8";
			varray_print($headers);

			$curl_writely = curl_init();
			curl_setopt($curl_writely, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl_writely, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_writely, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl_writely, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl_writely, CURLOPT_VERBOSE, 1);
			$this->curl_writely = $curl_writely;
		}

		curl_setopt($this->curl_writely, CURLOPT_URL, $url);
		return $this->curl_writely;
	}


	private function get_curl_wise($url)
	{
        //vprint("Starting");

		if(empty($this->token_wise))
			error("get_curl_wise() no security token");

		if ($this->curl_wise == "")
		{
			$headers = array();
			$headers[] = "Content-Type: application/atom+xml";
			if ($this->token_wise == GetCachedSessionToken())
				$headers[] = "Authorization: AuthSub token=" . GetCachedSessionToken();
			else
				$headers[] = "Authorization: GoogleLogin auth=" . $this->token_wise;
			$headers[] = "GData-Version: 3.0";
			varray_print($headers);

			$curl_wise = curl_init();
			curl_setopt($curl_wise, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl_wise, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_wise, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl_wise, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl_wise, CURLOPT_VERBOSE, 1);
			$this->curl_wise = $curl_wise;
		}

		curl_setopt($this->curl_wise, CURLOPT_URL, $url);
		return $this->curl_wise;
	}

	private function get_curl($url, $wise_version)
	{
		if ($wise_version)
			return $this->get_curl_wise($url);
		else
			return $this->get_curl_writely($url);
	}

	private function exec_curl_get($url, $wise_version)
	{
		vprint("GET ******************************************************************");
		vprint("url=$url");

		$curl = $this->get_curl($url, $wise_version);
		curl_setopt($curl, CURLOPT_POST, false);
		$response = curl_exec($curl);
		vxml_print($response);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        set_last_response($response, $status, curl_errno($curl));

		if ($response === false || ($status != 200 && $status != 201))
			error("Http Return Code=<b>$status</b><br>" .
                "Http Error=<b>" . curl_errno($curl) . "</b><br/>" .
                "response=[<h2>$response</h2>]<br/>" .
                "url=$url<br/>");

		vprint("END GET ******************************************************************");
		return $response;
	}

	private function exec_curl_post($url, $post_data, $wise_version)
	{
		if(empty($url))
			error("exec_curl_post() missing url");

		vprint("POST ******************************************************************");
		vprint("url=$url");
		vxml_print($post_data);
		$curl = $this->get_curl($url, $wise_version);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		$response = curl_exec($curl);
		vprint("response=");
		vxml_print($response);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        set_last_response($response, $status, curl_errno($curl));

		if ($response === false || ($status != 200 && $status != 201))
			error("<br/>Http Return Code=<b>$status</b><br>" .
                "Http Error=<b>" . curl_errno($curl) . "</b><br/>" .
                "response=<h2>$response</h2>" .
                "url=$url<br/>" .
                "post data=$post_data<br/>" .
                "post data (xml)=<br/>" . htmlentities($post_data)  . "<br/>");

		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_POSTFIELDS, "");
		vprint("END POST ******************************************************************");

		return $response;
	}

	private function exec_curl_put($url, $fields, $wise_version)
	{
		if(empty($url))
			error("missing url");

		/* Prepare the data for HTTP PUT. */
		$file = tmpfile();
		fwrite($file, $fields);
		fseek($file, 0);

		vxml_print($fields);

		vprint("PUT ******************************************************************");
		vprint("url=$url");
		vxml_print($fields);
		$curl = $this->get_curl($url, $wise_version);
		curl_setopt($curl, CURLOPT_PUT, true);
		curl_setopt($curl, CURLOPT_INFILE, $file);
		curl_setopt($curl, CURLOPT_INFILESIZE, strlen($fields));

		$response = curl_exec($curl);
		vxml_print($response);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        set_last_response($response, $status, curl_errno($curl));

		if ($response === false || ($status != 200 && $status != 201))
			error("html return code = $status errno=" . curl_errno($curl) . "<br/>response=[$response] <br/> url=$url<br/>");

		curl_setopt($curl, CURLOPT_PUT, false);
		curl_setopt($curl, CURLOPT_INFILE, "");
		curl_setopt($curl, CURLOPT_INFILESIZE, 0);
		vprint("END POST ******************************************************************");

		fclose($file);
		return $response;
	}

	private function close_curl()
	{
		if ($this->curl != "")
		{
			vprint("spreadsheet closed");
			curl_close($this->curl);
			$this->curl = "";
		}
	}

	private function getPostUrl()
	{
		if(!empty($this->spreadsheetid) && !empty($this->worksheetid))
		{
			$url = "https://spreadsheets.google.com/feeds/list/" . $this->spreadsheetid . "/" . $this->worksheetid . "/private/full";
			vprint("returning $url");
			return $url;
		}

		vprint("title=$this->spreadsheet sid=$this->spreadsheetid wid=$this->worksheetid");

		if (!$this->spreadsheetid)
			$this->spreadsheetid = $this->getID();

		if (!$this->spreadsheetid)
		{
			vprint("did not find id for " . $this->spreadsheet);
			return "";
		}

		$url = "https://spreadsheets.google.com/feeds/worksheets/" . $this->spreadsheetid . "/private/full";
		if(empty($this->worksheet))
		{
			vprint("worksheet name not set");
			return $url;
		}

		vprint("getting worksheet id for sheet=" . $this->worksheet);
		$url .= "?title=" . $this->worksheet;
		vprint("worksheet post url = $url");
		$response = $this->exec_curl_get($url, true);

		$worksheetXml = simplexml_load_string($response);
		//varray_print($worksheetXml);
		if($worksheetXml->entry)
		{
			$this->worksheetid = basename(trim($worksheetXml->entry[0]->id));
			vprint("worksheetid = " . $this->worksheetid);
		}
		else
			error("getPostUrl() failed to get worksheetid");

		if(!empty($this->spreadsheetid) && !empty($this->worksheetid))
		{
			$url = "https://spreadsheets.google.com/feeds/list/" . $this->spreadsheetid . "/" . $this->worksheetid . "/private/full";
			vprint("returning $url");
			return $url;
		}

		return "";
	}

	public function GetSpreadsheetIds()
	{
		vprint("sid=" . $this->spreadsheetid . " key=" . $this->key);

		if ($this->key != "" && $this->spreadsheetid != "" && $this->worksheetid != "")
			return $this->key;

		$this->getPostUrl();

        $url = "https://spreadsheets.google.com/feeds/spreadsheets/private/full/" . $this->spreadsheetid;
		//$url = "https://spreadsheets.google.com/feeds/spreadsheets/private/full/" . $this->spreadsheetid;
		$response = $this->exec_curl_get($url, true);

		$spreadsheetXml = simplexml_load_string($response);
		if(!$spreadsheetXml->link)
			error("GetSpreadsheetIds() found no entries in response");

		$idx = 0;
		while ($spreadsheetXml->link[$idx])
		{
			if ($spreadsheetXml->link[$idx]->attributes()->rel == "alternate")
			{
				$href = $spreadsheetXml->link[$idx]->attributes()->href;
				if(stripos($href, "key=") !== false)
				{
					preg_match("/key=([:a-z0-9_\-]+)/i", $href, $matches);
					$this->key = $matches[1];
					vprint("returning " . $this->key);
					return $this->key;
				}
			}
			$idx++;
		}

		error("GetSpreadsheetIds() did not find a key for spreadsheet " . $this->spreadsheetId);
	}

	private function getID()
	{
		vprint("for " . $this->spreadsheet);
		if ($this->spreadsheet)
			$url = "https://spreadsheets.google.com/feeds/spreadsheets/private/full?title=" . urlencode($this->spreadsheet);
		else
			$url = "https://spreadsheets.google.com/feeds/spreadsheets/private/full";

		$response = $this->exec_curl_get($url, true);
		$spreadsheetXml = simplexml_load_string($response);
		if(!$spreadsheetXml->entry)
		{
			iprint("getID() found no entries in response");
            xml_print($response);
			return "";
		}

		$idx = 0;
		while ($spreadsheetXml->entry[$idx])
		{
			if (!$this->key && $spreadsheetXml->entry[$idx]->title == $this->spreadsheet)
			{
				$this->spreadsheetid = basename(trim($spreadsheetXml->entry[$idx]->id));
				vprint("returning " . $this->spreadsheetid);
				return($this->spreadsheetid);
			}

			$idxLink = 0;
			while ($spreadsheetXml->entry[$idx]->link)
			{
				if ($spreadsheetXml->entry[$idx]->link[$idxLink]->attributes()->rel == "alternate")
				{
					$href = $spreadsheetXml->entry[$idx]->link[$idxLink]->attributes()->href;
					if(stripos($href, "key="))
					{
						preg_match("/key=([a-z0-9_\-:]+)/i", $href, $matches);
						$keyFound = $matches[1];

						if ($keyFound == $this->key || "spreadsheet:$keyFound" == $this->key )
						{
							$this->spreadsheetid = basename(trim($spreadsheetXml->entry[$idx]->id));
							vprint("returning " . $this->spreadsheetid);
							return $this->spreadsheetid;
						}
					}
					break;
				}
				$idxLink++;
			}

			$idx++;
		}

		vprint("returning " . $this->spreadsheetid);
        vxml_print($response);
		return $this->spreadsheetid;
	}

	private function getLinkUrl($linkrel)
	{
		vprint("$linkrel id=" . $this->spreadsheetid . " key=" . $this->key);

		if (!$this->spreadsheetid && ($this->key || $this->spreadsheet))
			$this->spreadsheetid = $this->getID();

		if (!$this->spreadsheetid)
			error("getLinkUrl() could not find spreadsheetid from " . $this->key);

		$url = "https://spreadsheets.google.com/feeds/worksheets/" . $this->spreadsheetid . "/private/full";
		if(!empty($this->worksheet))
			$url .= "?title=" . $this->worksheet;

		vprint("worksheet url = $url");
		$response = $this->exec_curl_get($url, true);
		$worksheetXml = simplexml_load_string($response);
		if($worksheetXml->entry)
		{
			$this->worksheetid = basename(trim($worksheetXml->entry[0]->id));
			vprint("worksheetid = " . $this->worksheetid);

			if ($worksheetXml->entry[0]->link->attributes()->rel == $linkrel)
				return $worksheetXml->entry[0]->link->attributes()->href;
		}

		error("getLinkUrl() did not find a link url for $linkrel");
	}

	private function formatColumnID($val) {
	return preg_replace("/[^a-zA-Z0-9.-]/", "", strtolower($val));
	}

	public function OpenExisting()
	{
		vprint("title=" . $this->spreadsheet . " key= " . $this->key);
		$url = $this->getPostUrl();
		vprint("key=" . $this->key . " id=" . $this->spreadsheetid . " $url");
		return $this->getSpreadsheetId();
	}

	public function GetRowCount($sheetname)
	{
		if (!$this->getPostUrl())
			error('Missing url for spreadsheet');

		$url = 'https://spreadsheets.google.com/feeds/worksheets/' . $this->getSpreadsheetId() . '/private/full';
		$response = $this->exec_curl_get($url, true);
		$response = str_replace('gs:', '', $response);
		$xml = simplexml_load_string($response);

		for($idx=0; $xml->entry[$idx]; $idx++)
		{
			$entry = $xml->entry[$idx];
			if ($entry->title == $sheetname)
			{
				return($entry->rowCount);
			}
		}
	}

	public function GetRowBySqlId($sqlrowid, $client)
	{
		vprint("Starting $sqlrowid");

		if (!$this->getPostUrl())
            error("failed to get post url");

		$rowCount = $client["last_row"];
		if (!$rowCount)
			$rowCount = $this->GetRowCount(Data_Worksheet());
		if ($rowCount)
		{
			$minRow = $rowCount > 10 ? $rowCount - 10 : 2;
			$url = 'https://spreadsheets.google.com/feeds/cells/' . $this->getSpreadsheetId() . '/' . $this->getWorksheetId() . "/private/full?min-row=$minRow" . '&min-col=7&max-col=7';
			$response = $this->exec_curl_get($url, true);
			$response = str_replace('gs:', '', $response);
			$xml = simplexml_load_string($response);
			//array_print($xml);

			for($idx=sizeof($xml->entry); $idx >= 0; $idx--)
			{
				$entry = $xml->entry[$idx];
				if ($entry->content == $sqlrowid)
				{
					$sheetrowid = str_replace('G', '', $entry->title);
					return($sheetrowid);
				}
			}
		}
	}


} // class spreadsheet

/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */
/* ******************************************************************************************* */

function AuthorizeApp($token)
{
	vprint("$token");
	$doc = new spreadsheet();
	$doc->GetSessionToken($token);
}

$g_SpreadsheetArray = array();

function CloseSpreadsheets()
{
	global $g_SpreadsheetArray;
	foreach($g_SpreadsheetArray as $key => $sheet)
	{
		if ($sheet)
			$sheet->close();
		$g_SpreadsheetArray[$key] = 0;
	}
}

function GetSpreadsheet($client, $userid = 0, $pwd = 0)
{
	if ($userid == 0)
		$userid = UserId();

	if ($pwd == 0)
		$pwd = Pwd();

	$title = $client['title'];
	$key = $client['key'];
	$spreadsheetid = $client['spreadsheetid'];
	$worksheetid = $client['worksheetid'];
	vprint("$title, $key, $spreadsheetid, $userid, $pwd");

	global $g_SpreadsheetArray;
	$doc = @$g_SpreadsheetArray[$userid];
	if (!$doc) {
		$doc = new spreadsheet();
		$doc->authenticate($userid, $pwd);
		$g_SpreadsheetArray[$userid] = $doc;
	}
	else
	{
		$doc->resetSheet();
	}

	$doc->setSpreadsheet($title);
	$doc->setKey($key);
	$doc->setSpreadsheetId($spreadsheetid);

	return $doc;
}

function SpreadsheetAdd($data, $row, $client)
{
	$worksheetid = $client['worksheetid'];

	$doc = GetSpreadsheet($client);
	$doc->setWorksheet(Data_Worksheet());
	$doc->setWorksheetId($worksheetid);

	switch($data["action_state"])
	{
		default:
			//SetActionState("SpreadsheetAdd_start");
			$doc->add($data);
			SetActionState("SpreadsheetAdd_added", $row["id"]);

		case "SpreadsheetAdd_added":
			$sheetrowid = $doc->GetRowBySqlId($data["sqlrowid"], $client);
			if ($sheetrowid)
			{
				// update sheet row id
				$data["sheetrowid"] = $sheetrowid;
				SetUserTableSheetRowId($data["sqlrowid"], $sheetrowid, $client);
			}
			else
				error("Failed to get sheetrowid for data");
			break;
	}

}

function SpreadsheetUpdate($data, $client)
{
	vprint("Starting");
	array_print($data);

	$worksheetid = $client['worksheetid'];

	$doc = GetSpreadsheet($client);
	$doc->setWorksheet(Data_Worksheet());
	$doc->setWorksheetId($worksheetid);
	UpdateUploadSheetRowId($data, $client);
	if (!$data["sheetrowid"])
		error("Missing sheetrowid");

	return $doc->updateRow($data);
}

function SpreadsheetDelete($data, $client)
{
	vprint("Starting");
	$data["date"] = "";
	$data["time"] = "";
	array_print($data);

	$worksheetid = $client['worksheetid'];

	$doc = GetSpreadsheet($client);
	$doc->setWorksheet(Data_Worksheet());
	$doc->setWorksheetId($worksheetid);
	UpdateUploadSheetRowId($data, $client);
	if (!$data["sheetrowid"])
		error("Missing sheetrowid");

	$data["sqlrowid"] = "";
	return $doc->updateRow($data);
}

function GetSpreadsheetStatsPage($client)
{
    //r1c1:r30c14
    //$range="R1C1:R30C14";

    $range="R1C1";
	$docApp = GetSpreadsheet($client);
	$docApp->setWorksheet("Stats");
	$results = $docApp->get_cells(1,1,30,14);
	return $results;
}

function GetSpreadssetStatsCell($client)
{
	$results = GetSpreadsheetStatsPage($client);
	$xml = simplexml_load_string($results);
	//array_print($xml);

	$cells = array("item" => "value");

	foreach ($xml as $item => $value)
	{
		if ($item == "entry")
		{
			$title = $value->title;
			$cells["$title"] = $value->content;
		}
	}
	return $cells;
}

// Structure Query
// https://spreadsheets.google.com/feeds/list/key/worksheetId/private/full?sq=name%3DJohn%20and%20age%3E25
// https://spreadsheets.google.com/feeds/list/key/worksheetId/private/full?reverse=true
// GET https://spreadsheets.google.com/feeds/cells/key/worksheetId/private/full?min-row=2&min-col=4&max-col=4
// GET https://spreadsheets.google.com/feeds/list/key/worksheetId/private/full?reverse=true
// GET https://spreadsheets.google.com/feeds/list/t3vdXR0YBfLVHxPBfwOB3MA/od7/private/full?reverse=true
// GET https://spreadsheets.google.com/feeds/worksheets/t3vdXR0YBfLVHxPBfwOB3MA/private/full
?>
