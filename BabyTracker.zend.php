<?php

require_once("BabyTracker.output.php");

set_include_path(get_include_path() . PATH_SEPARATOR . "../library");
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_AuthSub');

print("<title>Authorize Baby Tracker Account</title>");
function ZendAuthSubTestOld()
{
	vprint("Start");

	$hostedDomain = 'https://secure.iinet.com/joyofplaying.com';
	$nextUrl = 'https://secure.iinet.com/joyofplaying.com/BabyTracker/RetrieveToken.php';
	$scope = 'https://spreadsheets.google.com/feeds/ https://docs.google.com/feeds/default/private/full/';
	$secure = 1;  // set $secure=1 to request secure AuthSub tokens
	$session = 1;
	$authSubUrl = Zend_Gdata_AuthSub::getAuthSubTokenUri($nextUrl, $scope, $secure, $session) . '&hd=' . $hostedDomain;

	vprint($authSubUrl);

	$authorizationUrl = "<p>BabyTracker needs access to your Google account to install a spreadsheet. " .
						"To authorize BabyTracker to access your account, <a target='_blank' href='$authSubUrl'>log in to your account</a>.</p>";
	print($authorizationUrl);
}

function RequestSingleUseToken()
{
	vprint("Start");

	$url = "https://www.google.com/accounts/AuthSubRequest?";
	$url .= "next=https://secure.iinet.com/joyofplaying.com/BabyTracker/BabyTracker.zend.php";
	$url .=	"&scope=" . urlencode("https://spreadsheets.google.com/feeds/");
	$url .= "&session=1";
	//$url .= "&secure=1";
	//$url .= "&hd=" . UserId();

	vprint($url);

	//http(s)://spreadsheets.google.com/feeds/
	$authorizationUrl = "<p>BabyTracker needs access to your Google account to install a spreadsheet. " .
						"To authorize BabyTracker to access your account, <a target='_blank' href='$url'>log in to your account</a>.</p>";
	print($authorizationUrl);
}

function Zend_GetSessionToken()
{
	$feed_url = 'https://spreadsheets.google.com/feeds/ https://docs.google.com/feeds/default/private/full/';
	$source_url = "https://secure.iinet.com/joyofplaying.com/BabyTracker/BabyTracker.zend.php";

	if (GetCachedSessionToken()) {

		print("<h1>Baby Tracker has been Authorized</h1><br/>");
		return GetCachedSessionToken();
	}

	if (isset($_GET['token'])) {
		$session_token = Zend_Gdata_AuthSub::getAuthSubSessionToken($_GET['token']);
		vprint("SUCCESS recieved session token=$session_token");
		send_to_newfile("session_token=$session_token", "session_token.cfg");
	} else {
		print("Baby Tracker needs to be authorized for the BabyTracker@pacifier.com account");
		$googleUri = Zend_Gdata_AuthSub::getAuthSubTokenUri($source_url,$feed_url, 0, 1);
		print("<a target='_blank' href='$googleUri'><h1>Authorize Baby Tracker Account</h1></a><br/>");
	}
}

if (get_input_option("delete")) {
    send_to_newfile("", "session_token.cfg");
    vprint("delete session token");
}

Zend_GetSessionToken();

?>
