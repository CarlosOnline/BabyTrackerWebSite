function ShowCookies()
{
    var cookies = document.cookie;
    alert("Cookies=" + cookies.replace(/;/g, ";\n"));
}

function ExtractValue(Tag, Message)
{
    var openTag = '<' + Tag + '>';
    var closeTag = '</' + Tag + '>';
    var start = Message.indexOf(openTag);
    var end = Message.indexOf(closeTag);
    if (start == -1 || end == -1)
        return "";
    return Message.substring(start + openTag.length, end);
}

function ExtractAndSave(tag, data)
{
    var value = ExtractValue(tag, data);
    if (value == "") {
        //document.writeln(data);
        alert("Program Error: Did not find value for tag " + tag);
        return "";
    }

    createCookie(tag, value);
    return value;
}

function _DebugMsg(func, msg) {
    var txt = document.getElementById("txtDebug");
    txt.value = txt.value + "\n" + func + "() " + msg;
    txt.parentNode.style.display = "";
}

function _FrameMsg(func, msg) {
    var frameDebug = document.getElementById("frameDebug");
    if (frameDebug)
    {
        var old = frameDebug.innerHTML;
        frameDebug.innerHTML = func + "() " + msg + "<hr><hr><hr>" + old;
        frameDebug.style.display = "";
    }
}

function ProductVersionEx() {
    return "BabyDiaperApp_version_1.0_a";
}

function uniqid() {
    var newDate = new Date;
    var maxvalue = 999999999999;
    return hex(newDate.getTime()) + "." + hex(randomNumber(maxvalue)) + "." + hex(randomNumber(maxvalue)) + "." + hex(randomNumber(maxvalue));
}

function hex(num)
{
    return num.toString(16);
}

function randomNumber(max) {
    var randomnumber = Math.floor(Math.random() * (max + 1))
    return randomnumber;
}

function GetDayOfWeek(date) {
    var d = new Date(date);
    var weekday = new Array(7);
    weekday[0] = "Sunday";
    weekday[1] = "Monday";
    weekday[2] = "Tuesday";
    weekday[3] = "Wednesday";
    weekday[4] = "Thursday";
    weekday[5] = "Friday";
    weekday[6] = "Saturday";
    return (weekday[d.getDay()]);
}

function encodeDateForUrl(date) {
    var str = date;
    var idx = str.indexOf('/');
    while (idx != -1) {
        str = str.replace('/', "%2F");
        idx = str.indexOf('/');
    }
    return str;
}

function DateString() {
    var d = new Date();
    var curr_date = d.getDate();
    var curr_month = d.getMonth() + 1;
    var curr_year = d.getFullYear();
    var strDate = curr_month + "/" + curr_date + "/" + curr_year;
    return (strDate);
}

function DateStringShort() {
    var d = new Date();
    var curr_date = d.getDate();
    var curr_month = d.getMonth() + 1;
    var strDate = curr_month + "/" + curr_date;
    return (strDate);
}

function TimeString() {
    var now = new Date();
    var hour = now.getHours();
    var minute = now.getMinutes();
    var ap = "AM";
    if (hour > 11) { ap = "PM"; }
    if (hour > 12) { hour = hour - 12; }
    if (hour == 0) { hour = 12; }
    if (hour < 10) { hour = "0" + hour; }
    if (minute < 10) { minute = "0" + minute; }
    var timeString = hour +
                ':' +
                minute +
                " " +
                ap;
    return timeString;
}

function TimeStringShort() {
    var now = new Date();
    var hour = now.getHours();
    var minute = now.getMinutes();
    if (hour > 12) { hour = hour - 12; }
    if (hour == 0) { hour = 12; }
    if (hour < 10) { hour = "0" + hour; }
    if (minute < 10) { minute = "0" + minute; }
    var timeString = hour + ':' + minute;
    return timeString;
}

function CurrentAMPM() {
    var now = new Date();
    var hour = now.getHours();
    if (hour > 11) return "PM";
    return "AM";
}

function createCookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    }
    else var expires = "";
    document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return "";
}

function deleteAllCookies() {
    var cookienames = document.cookie.match(/\w+(?==)/g);
    if (cookienames == null)
       return;

    for (var idx = 0; idx < cookienames.length; idx++) {
        eraseCookie(cookienames[idx]);
        //alert("deleteAllCookies - deleting " + cookienames[idx]);
    }
    var cookies = document.cookie;
    var start = 0;
    while (start < cookies.length) {
        var semi = cookies.indexOf(";", start);
        if (semi == -1) semi = cookies.length;

        var name = cookies.substr(start, semi - start);
        eraseCookie(name);
        //_DebugMsg("deleteAllCookies", "deleting " + name);

        start = semi + 1;
    }

    var cookies = document.cookie.split(";");
    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i];
        var eqPos = cookie.indexOf("=");
        var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
        document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
        //_DebugMsg("deleteAllCookies", "deleting " + name);
    }
    //alert(document.cookie);
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}

function getCheckedValueEx(radioObj) {
    if (radioObj.checked)
        return "checked";
    else
        return "";
}

function setCheckedValueEx(radioObj, newValue) {
    if (newValue)
        radioObj.checked = true;
    else if (radioObj.checked)
        radioObj.checked = false;
}

function LoadCheckboxFromCookie(checkbox, default_value) {
    var save = readCookie(checkbox);
    if (save == "checked")
        document.getElementById(checkbox).checked = true;
    else if (save == "unchecked")
        document.getElementById(checkbox).checked = false;
    else
        document.getElementById(checkbox).checked = default_value;
}

var g_request = null;

function getXmlHttp() {
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    if (xmlhttp == null) {
        alert("Your browser does not support XMLHTTP.");
    }
    return xmlhttp;
}

function DoXmlHttpPost(data, OnReadyStateChangeFunction) {

    g_request = getXmlHttp();
    g_request.onreadystatechange = OnReadyStateChangeFunction;
    //g_request.open("POST", "http://www.JoyOfPlaying.com/BabyTracker/BabyTracker.php", true);
    //g_request.open("POST", "https://secure.iinet.com/joyofplaying.com/BabyTracker/BabyTracker.php", true);
    g_request.open("POST", "http://localhost:8888/BabyTracker/php/BabyTracker.php", true);
    g_request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    g_request.send(data + "&" + ProductVersionEx());
    return g_request;
}

function DoXmlHttpPostEx(xmlhttp, data, OnReadyStateChangeFunction) {

    xmlhttp.onreadystatechange = OnReadyStateChangeFunction;
    //xmlhttp.open("POST", "http://www.JoyOfPlaying.com/BabyTracker/BabyTracker.php", true);
    //xmlhttp.open("POST", "https://secure.iinet.com/joyofplaying.com/BabyTracker/BabyTracker.php", true);
    xmlhttp.open("POST", "http://localhost:8888/BabyTracker/BabyTracker.php", true);
    xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xmlhttp.send(data + "&" + ProductVersionEx());
    return xmlhttp;
}


function GetResponseState(Response) {
    var end = Response.indexOf(":");
    if (end != -1)
        return Response.substr(0, end);
    alert("missing response state");
    return "";
}

function GetFormResponseData(Response) {
    var start = Response.indexOf(":");
    var end = Response.indexOf("&&");
    if (start != -1)
        return Response.substr(start + 1, end - (start + 1));

    alert("Did not find data in string " + Response);
    return "";
}

function IsItemInArray(originalArray, itemToDetect) {
    var j = 0;
    while (j < originalArray.length) {
        if (originalArray[j] == itemToDetect) {
            return true;
        } else { j++; }
    }
    return false;
}

//remove item (string or number) from an array
function RemoveArrayItem(originalArray, itemToRemove) {
    var j = 0;
    while (j < originalArray.length) {
        //	alert(originalArray[j]);
        if (originalArray[j] == itemToRemove) {
            originalArray.splice(j, 1);
        } else { j++; }
    }
    //	assert('hi');
    return originalArray;
}

// Declaring valid date character, minimum year and maximum year
var dtCh = "/";
var minYear = 1900;
var maxYear = 2100;

function isInteger(s) {
    var i;
    for (i = 0; i < s.length; i++) {
        // Check that current character is number.
        var c = s.charAt(i);
        if (((c < "0") || (c > "9"))) return false;
    }
    // All characters are numbers.
    return true;
}

function isRealNumber(s) {
    var i;
    var foundDecimal = false;
    for (i = 0; i < s.length; i++) {
        // Check that current character is number.
        var c = s.charAt(i);
        if (c == '.') {
            if (foundDecimal) return false;
            foundDecimal = true;
        }
        if (!((c >= "0" && c <= "9") || (c == "."))) return false;
    }
    // All characters are numbers.
    return true;
}

function stripCharsInBag(s, bag) {
    var i;
    var returnString = "";
    // Search through string's characters one by one.
    // If character is not in bag, append to returnString.
    for (i = 0; i < s.length; i++) {
        var c = s.charAt(i);
        if (bag.indexOf(c) == -1) returnString += c;
    }
    return returnString;
}

function daysInFebruary(year) {
    // February has 29 days in any year evenly divisible by four,
    // EXCEPT for centurial years which are not also divisible by 400.
    return (((year % 4 == 0) && ((!(year % 100 == 0)) || (year % 400 == 0))) ? 29 : 28);
}
function DaysArray(n) {
    for (var i = 1; i <= n; i++) {
        this[i] = 31
        if (i == 4 || i == 6 || i == 9 || i == 11) { this[i] = 30 }
        if (i == 2) { this[i] = 29 }
    }
    return this
}

function isDate(dtStr, quiet) {
    var currDate = new Date();
    var daysInMonth = DaysArray(12)
    var pos1 = dtStr.indexOf(dtCh)
    var pos2 = dtStr.indexOf(dtCh, pos1 + 1)
    var strMonth = dtStr.substring(0, pos1)
    var strDay = ""
    if (pos2 == -1)
        strDay = dtStr.substring(pos1 + 1)
    else
        strDay = dtStr.substring(pos1 + 1, pos2)
    var strYear = dtStr.substring(pos2 + 1)
    if (pos2 == -1 || strYear == "") {
        strYear = new String(currDate.getFullYear());
        pos2 = dtStr.length - 1;
    }
    strYr = strYear
    if (strDay.charAt(0) == "0" && strDay.length > 1) strDay = strDay.substring(1)
    if (strMonth.charAt(0) == "0" && strMonth.length > 1) strMonth = strMonth.substring(1)
    for (var i = 1; i <= 3; i++) {
        if (strYr.charAt(0) == "0" && strYr.length > 1) strYr = strYr.substring(1)
    }
    month = parseInt(strMonth)
    day = parseInt(strDay)
    year = parseInt(strYr)

    if (pos1 == -1 || (pos2 == -1 && strYear == "")) {
        if (!quiet) alert("The date format should be : mm/dd/yyyy")
        return false
    }
    if (strMonth.length < 1 || month < 1 || month > 12) {
        if (!quiet) alert("Please enter a valid month")
        return false
    }
    if (strDay.length < 1 || day < 1 || day > 31 || (month == 2 && day > daysInFebruary(year)) || day > daysInMonth[month]) {
        if (!quiet) alert("Please enter a valid day")
        return false
    }
    if (strYear.length != 4 || year == 0 || year < minYear || year > maxYear) {
        if (!quiet) alert("Please enter a valid 4 digit year between " + minYear + " and " + maxYear)
        return false
    }
    if (dtStr.indexOf(dtCh, pos2 + 1) != -1 || isInteger(stripCharsInBag(dtStr, dtCh)) == false) {
        if (!quiet) alert("Please enter a valid date")
        return false
    }
    return true
}

function IsValidTime(timeStr) {
    // Checks if time is in HH:MM:SS AM/PM format.
    // The seconds and AM/PM are optional.

    var timePat = /^(\d{1,2}):(\d{2})(:(\d{2}))?(\s?(AM|am|PM|pm))?$/;

    var matchArray = timeStr.match(timePat);
    if (matchArray == null) {
        alert("Time is not in a valid format.");
        return false;
    }
    hour = matchArray[1];
    minute = matchArray[2];
    second = matchArray[4];
    ampm = matchArray[6];

    if (second == "") { second = null; }
    if (ampm == "") { ampm = "am" }

    if (hour < 0 || hour > 23) {
        alert("Hour must be between 1 and 12. (or 0 and 23 for military time)");
        return false;
    }
    if (hour <= 12 && ampm == null) {
        if (confirm("Please indicate which time format you are using.  OK = Standard Time, CANCEL = Military Time")) {
            alert("You must specify AM or PM.");
            return false;
        }
    }
    if (hour > 12 && ampm != null) {
        alert("You can't specify AM or PM for military time.");
        return false;
    }
    if (minute < 0 || minute > 59) {
        alert("Minute must be between 0 and 59.");
        return false;
    }
    if (second != null && (second < 0 || second > 59)) {
        alert("Second must be between 0 and 59.");
        return false;
    }
    return true;
}

function IsValidTime_Quiet(timeStr) {
    // Checks if time is in HH:MM:SS AM/PM format.
    // The seconds and AM/PM are optional.

    var timePat = /^(\d{1,2}):(\d{2})(:(\d{2}))?(\s?(AM|am|PM|pm))?$/;

    var matchArray = timeStr.match(timePat);
    if (matchArray == null) {
        return false;
    }
    hour = matchArray[1];
    minute = matchArray[2];
    second = matchArray[4];
    ampm = matchArray[6];

    if (second == "") { second = null; }
    if (ampm == "") { ampm = "am" }

    if (hour < 0 || hour > 23) {
        return false;
    }
    if (hour <= 12 && ampm == null) {
        return false;
    }
    if (hour > 12 && ampm != null) {
        return false;
    }
    if (minute < 0 || minute > 59) {
        return false;
    }
    if (second != null && (second < 0 || second > 59)) {
        return false;
    }
    return true;
}

function isText(xStr) {
    var regExp = /<\/?[^>]+>/gi;
    xStr = xStr.replace(regExp, "");
    return xStr;
}

Object.prototype.nextObject = function () {
    var n = this;
    do n = n.nextSibling;
    while (n && n.nodeType != 1);
    return n;
}

Object.prototype.previousObject = function () {
    var p = this;
    do p = p.previousSibling;
    while (p && p.nodeType != 1);
    return p;
}

var g_PendingPosts = new Array();

function HtmlPost() {

    var xmlHttpReq = null;

    this.InitPostAction = function _InitPostAction(func, action) {
        if (g_PendingPosts[action] == undefined || g_PendingPosts[action] < 0)
            g_PendingPosts[action] = 0;
    }

    this.PostSingleton = function _PostSingleton(action, data, callback) {
        this.InitPostAction("PostSingleton", action);
        if (g_PendingPosts[action])
            return false;

        return this.Post(action, data, callback, "");
    }

    this.PostMultiple = function _PostMultiple(action, data, callback, max) {
        this.InitPostAction("PostMultiple", action);

        if (g_PendingPosts[action] >= max) {
            //_DebugMsg("PostMultiple() too many pending " + action + " " + g_PendingPosts[action]);
            return false;
        }

        return this.Post(action, data, callback, "");
    }

    this.Post = function _Post(action, data, callback, cookie, privateCallback) {
        this.InitPostAction("Post", action);

        var postdata = "postaction=" + action + "&" + data + "&" + ProductVersionEx();
        var self = this;
        self.xmlHttpReq = getXmlHttp();

        g_PendingPosts[action]++;
        //_DebugMsg("Post", action + " " + g_PendingPosts[action]);
        //_FrameMsg("_Post " + postdata);

        xmlhttp.onreadystatechange = function () { DoPostCallback(self.xmlHttpReq, this, action, cookie, callback, privateCallback); };
        //xmlhttp.open("POST", "https://secure.iinet.com/joyofplaying.com/BabyTracker/BabyTracker.php", true);
        xmlhttp.open("POST", "http://localhost:8888/BabyTracker/php/BabyTracker.php", true);
        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xmlhttp.send(postdata);
    }

    function DoPostCallback(xmlHttpReq, self, action, cookie, callback, privateCallback) {
        if (xmlHttpReq.readyState != 4 /* complete */)
            return 0;

        //_DebugMsg("DoPostCallback", action + " " + g_PendingPosts[action]);

        var status = xmlHttpReq.status;
        var response = xmlHttpReq.responseText;
        if (status != 200 && response == "")
            alert('DoPostCallback error ' + status);

        callback(xmlHttpReq.status, response, action, cookie, privateCallback);

        g_PendingPosts[action]--;
        if (g_PendingPosts[action] < 0)
            g_PendingPosts[action] = 0;

        return 1;
    }

} // HtmlPost function class
