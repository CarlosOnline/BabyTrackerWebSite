<?php

//session_destroy();
session_start();
//require_once("BabyTracker.output.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Baby Tracker Test</title>
    <link href='../BabyTracker.css' type='text/css' rel='stylesheet'/>
    <style type="text/css">
        body
        {
            width: 95%;
            font-size: small;
        }
        iframe
        {
            font-size: small;
        }
    </style>
</head>
<body onload="LoadBody();">
<span style="float:right; padding-right:0px">
<div><input type="checkbox" id="chk1" value="verbose" checked/><label for="chkVerbose">verbose</label></div>
<div><input type="checkbox" id="chk2" name="xml_view" value="xml_view"/><label for="chkXml">xml_view</label></div>
<div><input type="checkbox" id="chk3" value="no_xml"/><label for="chkNoXml">no_xml</label></div>
<div><input type="checkbox" id="chk4" value="no_sql"/><label for="chkNoSql">no_sql</label></div>
<a target="_blank" href="output/">output directory</a><br />
<a target="_blank" href="output/BabyTracker.history.htm">history file</a><br />
<a target="Setup for Baby Tracker" href="http://localhost:8888/BabyTracker/BabyTrackerSetup.htm">Setup</a>
<br />
</span>
<span style="float:right;">
<label for="txtExtraValue">extra value</label><input type="text" value="" id="txtExtraValue"/><br />
<label for="txtOutputFile">Output File</label><input type="text" value="" id="txtOutputFile"/><br />
</span>
<span width="80%" >
    <span style='float:none'>
        <input type="button" onclick="SetupUser(this);" value="setup_new_user" />
        <input type="button" onclick="LoginUser(this);" value="login_user" />
        <input type="button" onclick="OnTestClick(this);" value="last_rows" />
        <input type="button" onclick="OnTestClick(this);" value="dump_user_table" />
        <input type="button" onclick="OnTestClick(this);" value="dump_reg_table" />
        <input type="button" onclick="OnTestClick(this);" value="stats_sql" />
        <input type="button" onclick="OnTestClick(this);" value="stats_counts" />
        <input type="button" onclick="OnTestClick(this);" value="stats_sql_col&stats_item=total" />
    </span>
    <span style='float:left'>
        <input type="button" onclick="TestAddClick(this, 10);" value="TestAdd" />
        <input type="button" onclick="TestUpdateClick(this, 1);" value="TestUpdate" />
        <input type="button" onclick="TestDeleteClick(this, 1);" value="TestDelete" />
    </span>
	<br style='clear:left;'/>
    <span style='float:left'>
		<label for="txtBabyName">Baby&#39;s First Name</label>
		<input id="txtBabyName" type="text" value=""/>
		<label for="txtDOB">DOB</label>
		<input id="txtDOB" name="txtDOB" type="text" value=""/>
    </span>
	<br style='clear:left;'/>
    <span style='float:left'>
		<label for="txtUserName">UserName</label>
		<input id="txtUserName" type="text" value=""/>
		<label for="txtEmail">Your Email:</label>
		<input id="txtEmail" name="txtEmail" type="text" value=""  size="18"/>
		<label for="txtPassword">password</label>
		<input id="txtPassword" name="txtPassword" type="password" value="" size="19"/>
    </span>
	<br style='clear:left;'/>
    <span style='float:left'>
	  <?php
		  require_once('recaptchalib.php');
		  $public_captcha_key='6LdtMMQSAAAAALUhUlWYy9SbFHBU3_4QGnMCpvYx';
		  echo recaptcha_get_html($public_captcha_key);
	  ?>
    </span>
</span>
<br style='clear:both'/>
<span style='float:right'>
	<input type="button" onclick="OnRunSql(this);" value="run_sql_file" /><br />
	<input type="button" onclick="OnTestClick(this);" value="setup_system_tables" /><br />
	<input type="button" onclick="OnDangerousClick(this);" value="delete_system_tables" /><br />
	<input type="button" onclick="OnDangerousClick(this);" value="delete_reg_table" /><br />
	<input type="button" onclick="OnDangerousClick(this);" value="delete_all_output_files" /><br />
	<input type="button" onclick="OnDangerousClick(this);" value="delete_log_table" /><br />
	<input type="button" onclick="OnDangerousClick(this);" value="delete_log_table_all" /><br />
	<input type="button" onclick="OnTestClick(this);" value="stats" /><br />
	<input type="button" onclick="OnTestClick(this);" value="stats_col&col=F" /><br />
	<input type="button" onclick="OnTestClick(this);" value="stats_col&col=B&col_end=F" /><br />
	<input type="button" onclick="ShowCookies_Click()" value="Show Cookies" /><br />
	<input type="button" onclick="EraseCookies_Click();" value="Erase Cookies" /><br />
	<input type="button" onclick="LaunchPhp_Click(this);" value="Test.php" /><br />
</span>
<span>
    <input id="lblUrl" value="" size='200' readonly='readonly'  style='background-color:#fed;'/>
    <div id="divFrame">
	  <iframe src="" id="frm" width="80%" height='500px'>Click a button</iframe></div>
</span>

<script type="text/javascript" language="javascript" src="../BabyTracker.Utility.js""></script>
<script type="text/javascript" language="javascript">

    function LoadBody(){

        document.getElementById("frm").innerHTML = "";
        document.getElementById("frm").src = "";
        document.getElementById("lblUrl").value = "";

        var name = readCookie("childname");
        if (name != "")
            document.getElementById("txtBabyName").value = name;

        var key = readCookie("dob");
        if (key != "")
            document.getElementById("txtDOB").value = key;

        var name = readCookie("username");
        if (name != "")
            document.getElementById("txtUserName").value = name;

        var name = readCookie("password");
        if (name != "")
            document.getElementById("txtPassword").value = name;

        var key = readCookie("userid");
        if (key != "")
            document.getElementById("txtEmail").value = key;

        document.getElementById("txtBabyName").focus();
    }

    function SetupUser(Obj) {

        var txtUserName = document.getElementById("txtUserName");
        if (txtUserName.value == "") {
            alert("Missing User Name");
            txtUserName.focus();
            return;
        }

        var txtEmail = document.getElementById("txtEmail");
        if (txtEmail.value == "") {
            alert("Missing email alias");
            txtEmail.focus();
            return;
        }

        var txtPassword = document.getElementById("txtPassword");
        if (txtPassword.value == "") {
            alert("Missing password");
            txtPassword.focus();
            return;
        }

        var txtBabyName = document.getElementById("txtBabyName");
        if (txtBabyName.value == "") {
            alert("Missing Baby Name");
            txtBabyName.focus();
            return;
        }

        var txtDOB = document.getElementById("txtDOB");
        if (txtDOB.value == "") {
            alert("Missing date of birth, or incorrect date");
            txtDOB.focus();
            return;
        }
        else if (!isDate(txtDOB.value)) {
            txtDOB.focus();
            txtDOB.select();
            return;
        }

        var txtCaptchaChallenge = document.getElementById('recaptcha_challenge_field');
        var txtCaptchaResponse = document.getElementById('recaptcha_response_field');
        if (txtCaptchaChallenge.value == "" || txtCaptchaResponse == "")
        {
            alert("The reCAPTCHA wasn't entered correctly. Go back and try it again.");
            txtCaptchaChallenge.focus();
            return;
        }

        createCookie("childname", txtBabyName.value, 999);
        createCookie("dob", txtDOB.value, 999);
        createCookie("username", txtUserName.value, 999);
        createCookie("userid", txtEmail.value, 999);
        createCookie("password", txtPassword.value, 999);

        var postData = "";
        postData += "childname=" + encodeURI(txtBabyName.value) + "&";
        postData += "dob=" + encodeDateForUrl(encodeURI(txtDOB.value)) + "&";
        postData += "username=" + encodeURI(txtUserName.value) + "&";
        postData += "userid=" + encodeURI(txtEmail.value) + "&";
        postData += "pwd=" + encodeURI(txtPassword.value) + "&";
        postData += "recaptcha_challenge_field=" + encodeURI(txtCaptchaChallenge.value) + "&";
        postData += "recaptcha_response_field=" + encodeURI(txtCaptchaResponse.value) + "&";
        postData += "postaction=setup_new_user";

        var url = "BabyTracker.php?testaction=setup_new_user&" + postData;
        url += GetCheckboxOptions();
        url += OutputFileOption(Obj);
        url += "&ignore=" + randomNumber(200000);

        document.title = Obj.value;
        document.getElementById("lblUrl").value = url;
        document.getElementById("frm").src = url;
    }

    function LoginUser(Obj) {

        var txtEmail = document.getElementById("txtEmail");
        if (txtEmail.value == "") {
            alert("Missing email alias");
            txtEmail.focus();
            return;
        }

        var txtPassword = document.getElementById("txtPassword");
        if (txtPassword.value == "") {
            alert("Missing password");
            txtPassword.focus();
            return;
        }

        var txtBabyName = document.getElementById("txtBabyName");
        if (txtBabyName.value == "") {
            alert("Missing Baby Name");
            txtBabyName.focus();
            return;
        }

        var txtCaptchaChallenge = document.getElementById('recaptcha_challenge_field');
        var txtCaptchaResponse = document.getElementById('recaptcha_response_field');
        if (txtCaptchaChallenge.value == "" || txtCaptchaResponse == "")
        {
            alert("The reCAPTCHA wasn't entered correctly. Go back and try it again.");
            txtCaptchaChallenge.focus();
            return;
        }

        createCookie("childname", txtBabyName.value, 999);
        createCookie("userid", txtEmail.value, 999);
        createCookie("password", txtPassword.value, 999);

        var postData = "";
        postData += "childname=" + encodeURI(txtBabyName.value) + "&";
        postData += "userid=" + encodeURI(txtEmail.value) + "&";
        postData += "pwd=" + encodeURI(txtPassword.value) + "&";
        postData += "recaptcha_challenge_field=" + encodeURI(txtCaptchaChallenge.value) + "&";
        postData += "recaptcha_response_field=" + encodeURI(txtCaptchaResponse.value) + "&";
        postData += "postaction=login_user";

        var url = "BabyTracker.php?testaction=login_user&" + postData;
        url += GetCheckboxOptions();
        url += OutputFileOption(Obj);
        url += "&ignore=" + randomNumber(200000);

        document.title = Obj.value;
        document.getElementById("lblUrl").value = url;
        document.getElementById("frm").src = url;
    }

    function randomNumber(max) {
        var randomnumber = Math.floor(Math.random() * (max + 1))
        return randomnumber;
    }

    function GetCheckboxOptions() {
        var options = "";
        var idx = 0;
        for (idx = 1; idx <= 4; idx++) {
            var Obj = document.getElementById("chk" + idx);
            if (Obj.checked)
                options += "&" + Obj.value + "=1";
        }

        return options;
    }

    function OutputFileOption(Obj) {
        var ofile = document.getElementById("txtOutputFile").value;
        if (ofile) {
            url = "output/" + ofile;
        }

        return "";
    }

    function OnDeleteclick(Obj) {
        var value = Obj.value;
        Obj.value = "delete_file&filename=output.run.htm";
        OnTestClick(Obj);
        Obj.value = value;
    }

    function OnDangerousClick(Obj) {

        var r = confirm("Are you sure you want to: " + Obj.value + "?");
        if (r != true) {
            return;
        }

        alert("about to " + Obj.value);

        r = confirm("Final Warning: Are you sure you want to: " + Obj.value + "?");
        if (r != true) {
            return;
        }

        var url = "BabyTracker.php?testaction=" + Obj.value;
        url += GetCheckboxOptions();
        url += OutputFileOption(Obj);
        url += "&ignore=" + randomNumber(200000);

        document.title = Obj.value;
        document.getElementById("lblUrl").value = url;
        document.getElementById("frm").src = url;
    }

    function OnTestClick(Obj) {

        var url = "BabyTracker.php?testaction=" + Obj.value;
        url += GetCheckboxOptions();
        url += OutputFileOption(Obj);
        url += "&ignore=" + randomNumber(200000);

        document.title = Obj.value;
        document.getElementById("lblUrl").value = url;
        document.getElementById("frm").src = url;
    }

    function OnTestExClick(Obj) {

        var url = "BabyTracker.php?testaction=" + Obj.value;
        url += GetCheckboxOptions();
        url += "&ignore=" + randomNumber(200000);
        url += OutputFileOption(Obj);

        document.title = Obj.value;
        document.getElementById("lblUrl").value = url;
        document.getElementById("divFrame").innerHTML = "<iframe src='" + url + "' id='frm' width='80%' height='500px'></iframe>";
    }

    function OnTestOutputClick(Obj) {

        var url = "BabyTracker.output.php?testaction=" + Obj.value;
        url += GetCheckboxOptions();
        url += OutputFileOption(Obj);
        url += "&ignore=" + randomNumber(200000);

        document.title = Obj.value;
        document.getElementById("lblUrl").value = url;
        document.getElementById("frm").src = url;
    }

    function Type(idx) {
        var rgTypes = ["Breast", "Bottle", "Pump", "Wet Diaper", "Poopy Diaper"];
        return(rgTypes[idx]);
    }

    function TestAddClick(Obj, Max) {

        var idx=0;
        for (idx = 0; idx < Max; idx++)
        {
            var url = "BabyTracker.php?testaction=addrow";
            url += GetCheckboxOptions();
            url += OutputFileOption(Obj);
            url += "&ignore=" + randomNumber(200000);
            url += "&date=" + DateString();
            url += "&time=" + TimeString();
            url += "&type=" + Type(randomNumber(4));
            url += "&amount=" + randomNumber(200);

            document.title = Obj.value;
            document.getElementById("lblUrl").value = url;
            document.getElementById("divFrame").innerHTML = "<iframe src='" + url + "' id='frm' width='80%' height='500px'></iframe>";
        }
    }

    function TestUpdateClick(Obj, Max) {

        var idx=0;
        for (idx = 0; idx < Max; idx++)
        {
            var url = "BabyTracker.php?testaction=updaterow";
            url += GetCheckboxOptions();
            url += OutputFileOption(Obj);
            url += "&ignore=" + randomNumber(200000);

            url += "&date=" + DateString();
            url += "&time=" + TimeString();
            url += "&type=" + Type(randomNumber(4));
            url += "&amount=" + randomNumber(200);
            url += "&sqlrowid=" + document.getElementById("txtExtraValue").value;

            document.title = Obj.value;
            document.getElementById("lblUrl").value = url;
            document.getElementById("divFrame").innerHTML = "<iframe src='" + url + "' id='frm' width='80%' height='500px'></iframe>";
        }
    }

    function TestDeleteClick(Obj, Max) {

        var idx=0;
        for (idx = 0; idx < Max; idx++)
        {
            var url = "BabyTracker.php?testaction=deleterow";
            url += GetCheckboxOptions();
            url += OutputFileOption(Obj);
            url += "&ignore=" + randomNumber(200000);

            url += "&sqlrowid=" + document.getElementById("txtExtraValue").value;

            document.title = Obj.value;
            document.getElementById("lblUrl").value = url;
            document.getElementById("divFrame").innerHTML = "<iframe src='" + url + "' id='frm' width='80%' height='500px'></iframe>";
        }
    }

    function ShowCookies_Click() {
        cookie = document.cookie;
        cookie = cookie.replace(/;/g, ";\n");
        alert(cookie);
    }

    function EraseCookies_Click() {

        deleteAllCookies();

        eraseCookie("token");
        eraseCookie("childname");
        eraseCookie("name");
        eraseCookie("dob");
        eraseCookie("username");
        eraseCookie("userid");
        eraseCookie("password");
        alert("Done.  Cookies=[" + document.cookie + "]");
    }

    function LaunchPhp_Click(Obj) {

		var url = "http://localhost:8888/BabyTracker/php/Test.php";
		//url += GetCheckboxOptions();
		//url += OutputFileOption(Obj);
		//url += "&ignore=" + randomNumber(200000);

		document.title = Obj.value;
		document.getElementById("lblUrl").value = url;
		document.getElementById("divFrame").innerHTML = "<iframe src='" + url + "' id='frm' width='80%' height='500px'></iframe>";
    }

</script>
<script language="javascript" src="../BabyTracker.Utility.js" type="text/javascript"></script>
</body>
</html>
