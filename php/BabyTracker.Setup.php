<?php
require_once("BabyTracker.output.php");
$login_mode = get_input_bool('login_mode');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
    if ($login_mode)
        echo '<title>Login to Baby Tracker</title>';
    else
        echo '<title>Baby Tracker Setup</title>';
?>
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
        <?php
            if ($login_mode)
                echo '.SetupTableRow { display: none; }';
            else
                echo '.SetupTableRow { display:; }';
        ?>

        </style>
</head>
<body onload="OnLoad();">
<?php
    if ($login_mode)
        echo '<h2>Login to Baby Tracker</h2>';
    else
        echo '<h2>Baby Tracker Setup</h2>';
?>
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
            <tr class='SetupTableRow'>
                <td>
                    <label for='txtDOB'>Date of Birth</label>
                </td>
                <td>
                    <input id='txtDOB' name='txtDOB' type='text' value=""/>
                </td>
            </tr>
            <tr class='SetupTableRow'>
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
                </td>
                <td>
                    <?php
                        require_once('recaptchalib.php');
                        $public_captcha_key='6LdtMMQSAAAAALUhUlWYy9SbFHBU3_4QGnMCpvYx';
                        echo recaptcha_get_html($public_captcha_key);
                    ?>
                </td>
            </tr>
            <tr>
                <td>
                    <input id='chkSetupDebugMode' name='chkSetupDebugMode' type='checkbox' value="" />
                    <label for='chkSetupDebugMode'>Debug</label>
                </td>
                <td>
                    <div style="float:right; padding-right:0px">
                        <?php
                            if ($login_mode)
                                echo "<a href='javascript:Register_Click()' title='Register' name='Register'>Register</a><br/>";
                            else
                                echo "<a href='javascript:SignIn_Click()' title='Sign In' name='SignIn'>Sign In</a><br/>";
                        ?>
                        <a href="javascript:ForgotPassword_Click()" title='Forgot Username or Password' >Forgot password</a><br/>
                        <a href="javascript:EraseData_Click()" title='Clear Data' >Clear Data</a><br/>
                    </div>
                </td>
            </tr>
        </table>
        <input class='default' id='btnDone' name='submit' type='submit' value='Done' onclick="Done_Click();" />
        <input class='default' id='btnCancel' name='cancel' type='button' value='Cancel' onclick="Cancel_Click();" />
        <img title='Wait' id='imgBusy' src="../images/wait30trans.gif" alt='Wait' style="display:none;"/>
    </form>
    <br />
    <small>
    Need help?, Have a suggestion? Contact: <a href="mailto:cgomes@iinet.com">Carlos Gomes</a>
    <a href="http://www.JoyOfPlaying.com" style="float:right">Joy of Playing Productions</a>
    </small>
    <div id='output' style="width:500px;"/>

<script language='javascript' type="text/javascript">
<?php
    if ($login_mode)
        echo 'var fLoginMode=true';
    else
        echo 'var fLoginMode=false';
?>
</script>

<script language='javascript' type="text/javascript">

    var xmlhttp = null;

    function OnLoad() {
        var childname = readCookie('childname');
        if (childname != "")
            document.getElementById('txtBabyName').value = childname;

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
        createCookie('childname', document.getElementById('txtBabyName').value, 999);
        createCookie('dob', document.getElementById('txtDOB').value, 999);
        createCookie('username', document.getElementById('txtUserName').value, 999);
        createCookie('userid', document.getElementById('txtEmail').value, 999);
        //createCookie('password', document.getElementById('txtPassword').value, 999);
        history.go(-1);
    }

    function Done_Click() {

        var txtUserName = document.getElementById('txtUserName');
        if (!fLoginMode)
        {
            if (txtUserName.value == "") {
                alert('Missing User Name');
                txtUserName.focus();
                return;
            }
        }

        var txtDOB = document.getElementById('txtDOB');
        if (!fLoginMode)
        {
            if (txtDOB.value == "") {
                alert("Missing date of birth, or incorrect date");
                txtDOB.focus();
                return;
            }
            else if (!isDate(txtDOB.value))
            {
                txtDOB.focus();
                txtDOB.select();
                return;
            }
        }

        var txtBabyName = document.getElementById('txtBabyName');
        if (txtBabyName.value == "") {
            alert('Missing Baby Name');
            txtBabyName.focus();
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

        var txtCaptchaChallenge = document.getElementById('recaptcha_challenge_field');
        var txtCaptchaResponse = document.getElementById('recaptcha_response_field');
        if (txtCaptchaChallenge.value == "" || txtCaptchaResponse == "")
        {
            alert("The reCAPTCHA wasn't entered correctly. Go back and try it again.");
            txtCaptchaChallenge.focus();
            return;
        }

        var chkSetupDebugMode = document.getElementById('chkSetupDebugMode');

        createCookie('childname', txtBabyName.value, 999);
        createCookie('dob', txtDOB.value, 999);
        createCookie('username', txtUserName.value, 999);
        createCookie('userid', txtEmail.value, 999);
        //createCookie('password', txtPassword.value, 999);

        var postData = "";
        if (!fLoginMode)
        {
            postData += "dob=" + encodeDateForUrl(encodeURI(txtDOB.value)) + "&";
            postData += "username=" + encodeURI(txtUserName.value) + "&";
        }
        postData += "childname=" + encodeURI(txtBabyName.value) + "&";
        postData += "userid=" + encodeURI(txtEmail.value) + "&";
        postData += "pwd=" + encodeURI(txtPassword.value) + "&";
        postData += "recaptcha_challenge_field=" + encodeURI(txtCaptchaChallenge.value) + "&";
        postData += "recaptcha_response_field=" + encodeURI(txtCaptchaResponse.value) + "&";
        if (chkSetupDebugMode.checked == true) postData += "verbose=1&";
        if (!fLoginMode)
            postData += "postaction=" + "setup_new_user";
        else
            postData += "postaction=" + "login_user";

        var btnDone = document.getElementById('btnDone');
        btnDone.style.display = 'none';

        var btnCancel = document.getElementById('btnCancel');
        btnCancel.style.display = 'none';

        var imgBusy = document.getElementById('imgBusy');
        imgBusy.style.display = "";

        //alert("postData=" + postData);
        xmlhttp = DoXmlHttpPost(postData, OnPostResponse);
    }

    function OnPostResponse() {

        if (xmlhttp.readyState == 4 /* complete */) {
            var response = xmlhttp.responseText;
            var status = xmlhttp.status;
            if (status == 200) {

                var success = ExtractValue('Success', response);
                if (success != 'true')
                {
                    if (document.getElementById('chkSetupDebugMode').checked == true)
                        document.writeln(response);

                    var error = ExtractValue('ErrorMessage', response);
                    if (error == "")
                        error = "Unknown Error.  Email babytracker@pacifier.com for support";
                    alert(error);

                    window.location.reload(true);
                    return;
                }

                if (document.getElementById('chkSetupDebugMode').checked == true) {
                    document.writeln(response);
                    return;
                }

                alert(ExtractValue('SuccessMessage', response));
                history.go(-1);
                return;
            }
            else {
                document.writeln('Failed to Setup User ' + status);
                //document.writeln("HTTP Status Code = " + status);
                document.writeln(response);
                alert("Failed to register/login.  Error Code = " + status + " response = " + response);
            }
        }
    }

    function EraseData_Click() {

        deleteAllCookies();

        eraseCookie('childname');
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

    function Register_Click()
    {
        //window.location = "https://secure.iinet.com/joyofplaying.com/BabyTracker/php/BabyTracker.Setup.php";
        window.location.replace("http://localhost:8888/BabyTracker/php/BabyTracker.Setup.php");
    }

    function SignIn_Click()
    {
        //window.location = "https://secure.iinet.com/joyofplaying.com/BabyTracker/php/BabyTracker.Setup.php?login_mode=1";
        window.location.replace("http://localhost:8888/BabyTracker/php/BabyTracker.Setup.php?login_mode=1");
    }

    function ForgotPassword_Click()
    {
        alert('Not yet implemented');
    }

</script>
<script language='javascript' src="../BabyTracker.Utility.js" type="text/javascript"></script>
</body>
</html>
