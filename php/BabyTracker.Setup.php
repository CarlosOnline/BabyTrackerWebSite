<?php
require_once("BabyTracker.output.php");
session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Baby Tracker Setup</title>
    <link media="only screen and (max-device-width: 480px)" rel='stylesheet' type="text/css" href="../BabyTracker.css"/>
    <link href='../BabyTracker.css' type='text/css' rel='stylesheet'/>
    <style type="text/css">
        ol,ul,li
        {
            margin-left: 0.6em;
        }
        .OrangeText
        {
            color: #FF6600;
            font: bold;
        }
        .OrangeTextBox
        {
            color: #FF6600;
            font: bold;
            width: 30em;
        }
        .BlueText
        {
            color: #3333CC;
        }
        input
        {
            font-size: large;
        }
        </style>
</head>
<body onload="OnLoad();">
    <h2>Setup for Baby Tracker</h2>
    <br />
    <form action="javascript:void(0);" id='EntryForm'>
        <table>
            <tr>
                <td>
                    <label for='txtBabyName'>Baby&#39;s First Name</label>
                </td>
                <td>
                    <input id='txtBabyName' type='text' value=""/>
                </td>
            </tr>
            <tr>
                <td>
                    <label for='txtDOB'>Date of Birth</label>
                </td>
                <td>
                    <input id='txtDOB' name='txtDOB' type='text' value=""/>
                </td>
            </tr>
            <tr>
                <td>
                    <label for='txtUserName'>Your Name</label>
                </td>
                <td>
                    <input id='txtUserName' type='text' value=""/>
                </td>
            </tr>
            <tr>
                <td>
                    <label for='txtEmail'>Your Email:</label>
                </td>
                <td>
                    <input id='txtEmail' name='txtEmail' type='text' value=""  size='18'/>
                </td>
            </tr>
            <tr>
                <td>
                    <label for='txtPassword'>Password:</label>
                </td>
                <td>
                    <input id='txtPassword' name='txtPassword' type='password' value="" size='19'/>
                </td>
            </tr>
            <tr>
                <td>
                    <label for='imgCaptcha'>Code:</label>
                </td>
                <td>
                    <img src="BabyTracker.captcha.php?rand=<?php echo rand(); ?>" id='imgCaptcha' ><br/>
                    <small>Can't read the image? click <a href='javascript: refreshCaptcha();'>here</a> to refresh</small>
                </td>
            </tr>
            <tr>
                <td>
                    <label for='txtCaptcha'>Enter Code Above Here:</label>
                </td>
                <td>
                    <input id='txtCaptcha' name='txtCaptcha' type='text' value="" size='19'/>
                </td>
            </tr>
        </table>
        <br />
        <table>
            <tr>
                <td colspan='2'>
                    <input id='chkSetupDebugMode' name='chkSetupDebugMode' type='checkbox' value="" />
                    <label for='chkSetupDebugMode'>Debug</label>
                </td>
            </tr>
        </table>
        <br />
        <input class='default' id='btnDone' name='submit' type='submit' value='Done' onclick="Done_Click();" />
        <input class='default' id='btnCancel' name='cancel' type='button' value='Cancel' onclick="Cancel_Click();" />
        <img title='Wait' id='imgBusy' src="images/wait30trans.gif" alt='Wait' style="display:none;"/>
        <a href="javascript:EraseData_Click()" title='Erase Data' style="float:right">Erase Data</a>
    </form>
    <br />
    <small>
    Need help?, Have a suggestion? Contact: <a href="mailto:cgomes@iinet.com">Carlos Gomes</a>
    <a href="http://www.JoyOfPlaying.com" style="float:right">Joy of Playing Productions</a>
    </small>
    <div id='output' style="width:500px;"/>

<script language='javascript' type="text/javascript">

    var xmlhttp = null;

    function OnLoad() {
        var name = readCookie('name');
        if (name != "")
            document.getElementById('txtBabyName').value = name;

        var key = readCookie('dob');
        if (key != "")
            document.getElementById('txtDOB').value = key;

        var name = readCookie('username');
        if (name != "")
            document.getElementById('txtUserName').value = name;

        var key = readCookie('userid');
        if (key != "")
            document.getElementById('txtEmail').value = key;

        document.getElementById('txtBabyName').focus();
    }

    function Cancel_Click() {
        createCookie('name', document.getElementById('txtBabyName').value, 999);
        createCookie('dob', document.getElementById('txtDOB').value, 999);
        createCookie('username', document.getElementById('txtUserName').value, 999);
        createCookie('userid', document.getElementById('txtEmail').value, 999);
        //createCookie('password', document.getElementById('txtPassword').value, 999);
        history.go(-1);
    }

    function Done_Click() {

        var txtUserName = document.getElementById('txtUserName');
        if (txtUserName.value == "") {
            alert('Missing User Name');
            txtUserName.focus();
            return;
        }

        var txtEmail = document.getElementById('txtEmail');
        if (txtEmail.value == "") {
            alert('Missing email alias');
            txtEmail.focus();
            return;
        }

        var txtPassword = document.getElementById('txtPassword');
        if (txtPassword.value == "") {
            alert('Missing password');
            txtPassword.focus();
            return;
        }

        var txtBabyName = document.getElementById('txtBabyName');
        if (txtBabyName.value == "") {
            alert('Missing Baby Name');
            txtBabyName.focus();
            return;
        }

        var txtDOB = document.getElementById('txtDOB');
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

        var txtCaptcha = document.getElementById('txtCaptcha');
        if (txtCaptcha.value == "") {
            alert('Missing verification code');
            txtCaptcha.focus();
            return;
        }

        var chkSetupDebugMode = document.getElementById('chkSetupDebugMode');

        createCookie('name', txtBabyName.value, 999);
        createCookie('dob', txtDOB.value, 999);
        createCookie('username', txtUserName.value, 999);
        createCookie('userid', txtEmail.value, 999);
        //createCookie('password', txtPassword.value, 999);

        var postData = "";
        postData += "name=" + encodeURI(txtBabyName.value) + "&";
        postData += "dob=" + encodeDateForUrl(encodeURI(txtDOB.value)) + "&";
        postData += "username=" + encodeURI(txtUserName.value) + "&";
        postData += "userid=" + encodeURI(txtEmail.value) + "&";
        postData += "pwd=" + encodeURI(txtPassword.value) + "&";
        postData += "captcha=" + encodeURI(txtCaptcha.value) + "&";
        if (chkSetupDebugMode.checked == true) postData += "debugmode=true&";
        postData += "postaction=" + "setup_new_user";

        var btnDone = document.getElementById('btnDone');
        btnDone.style.display = 'none';

        var btnCancel = document.getElementById('btnCancel');
        btnCancel.style.display = 'none';

        var imgBusy = document.getElementById('imgBusy');
        imgBusy.style.display = "";

        //alert("postData=" + postData);
        xmlhttp = DoXmlHttpPost(postData, OnPostResponse);
    }

    function ReadSetting(data, field) {

        var pattern = field + "=";
        var idxStart = data.indexOf(pattern);
        if (idxStart == -1) {
            document.writeln(data);
            alert("Program Error: Did not find key " + field);
            return;
        }

        idxStart += pattern.length;
        var idxEnd = data.indexOf(";", idxStart);
        var value = data.substr(idxStart, idxEnd - idxStart);
        createCookie(field, value);
        return value;
    }

    function OnPostResponse() {
        if (xmlhttp.readyState == 4 /* complete */) {
            var response = xmlhttp.responseText;
            var status = xmlhttp.status;
            if (status == 200) {

                var end = response.indexOf('Successfully setup user');
                if (end == -1) {
                    document.writeln(response);
                    alert('did not find text Successfully setup user in response');
                    return;
                }
                var endOfResponse = response.substr(end);
                ReadSetting(endOfResponse, 'token');

                if (document.getElementById('chkSetupDebugMode').checked == true) {
                    document.writeln(response);
                    return;
                }

                alert("Successfully setup account for - [Baby " + readCookie('name') + " Tracker]");
                history.go(-1);
                return;
            }
            else {
                document.writeln('Failed to Setup User ' + status);
                //document.writeln("HTTP Status Code = " + status);
                document.writeln(response);
                alert("Failed to submit entry status code = " + status + " response = " + response);
            }
        }
    }

    function EraseData_Click() {

        deleteAllCookies();

        eraseCookie('name');
        document.getElementById('txtBabyName').value = "";

        eraseCookie('dob');
        document.getElementById('txtDOB').value = "";

        eraseCookie('username');
        document.getElementById('txtUserName').value = "";

        eraseCookie('userid');
        document.getElementById('txtEmail').value = "";

        eraseCookie('password');
        document.getElementById('txtPassword').value = "";

        eraseCookie('checkDebugMode');

        alert('Done');
    }

</script>
<script language='javascript' src="BabyTracker.Utility.js" type="text/javascript"></script>
</body>
</html>