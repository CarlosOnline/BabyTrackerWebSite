<?php

require_once("BabyTracker.output.php");

set_include_path(get_include_path() . PATH_SEPARATOR . "../library");
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Http_Client');
Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_Spreadsheets');

function Zend_InserRow($data, $client)
{
	vprint("Starting");
	varray_print($data);
	varray_print($client);

    vprint(GetCachedSessionToken());

	$babytracker_userid = UserId();
	$babytracker_pwd = Pwd();

	$service = Zend_Gdata_Spreadsheets::AUTH_SERVICE_NAME;
	$login = Zend_Gdata_ClientLogin::getHttpClient($babytracker_userid, $babytracker_pwd, $service);
	vprint("1");
	$spreadsheetService = new Zend_Gdata_Spreadsheets($login);
	if (!$spreadsheetService) {
		vprint("Failed to get the spreadsheet service");
	}
	//$spreadsheetService = new Zend_Gdata_Spreadsheets(GetCachedSessionToken());
	vprint("2  !!!");
	$insertedEntry = $spreadsheetService->insertRow($data,
                                                    $client["spreadsheetid"],
                                                    $client["worksheetid"]);

	//$entry = $insertedEntry instanceof Zend_Gdata_Spreadsheets_ListEntry;
	$entry = $insertedEntry;

	vprint($entry->getText());
	vxml_print($entry->getXML());

	vprint("Done!");
}

?>
