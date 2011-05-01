function BtnClick_Common(Button) {

    var handled = false;

    switch (Button.id) {
        case "DateEntry":
        case "TimeEntry":
        case "AmountEntry":
        case "DescriptionEntry":
            g_selected = Button;
            if (g_IPhoneVersion) {
                g_selectedIndex = EntryIdx(Button);
                SelectEntry("");
            }
            handled = true;
            break;

        case "imgNursing":
        case "imgBottle":
        case "imgPump":
        case "imgWetDiaper":
        case "imgPoopDiaper":
            SelectImageType(Button);
            handled = true;
            break;

        case "typeBreast":
        case "typeBottle":
        case "typePump":
        case "typeWetDiaper":
        case "typePoopyDiaper":
            SelectImageType(Button);
            handled = true;
            break;
    }

    if (!g_selected) {
        alert("No selected object");
        return false;
    }

    if (!handled) {
        switch (Button.value) {

            case "Update":
            case "Submit":
                OnSubmit_Click();
                document.getElementById("btnSubmit").value = "Submit";
                document.getElementById("btnCancel").style.display = "none";
                handled = true;
                break;

            case "Cancel":
                document.getElementById("btnSubmit").value = "Submit";
                document.getElementById("btnCancel").style.display = "none";
                g_UpdateRowID = null;
                handled = true;
                break;

            case "AM":
            case "PM":
                SetAMPM(Button.value);
                handled = true;
                break;

            case "Breast":
            case "Bottle":
            case "Pump":
            case "Wet":
            case "Poop":
                SetTypeButtonStyle(Button.value, btnBreast);
                SetTypeButtonStyle(Button.value, btnBottle);
                SetTypeButtonStyle(Button.value, btnPump);
                SetTypeButtonStyle(Button.value, btnWetDiaper);
                SetTypeButtonStyle(Button.value, btnPoopyDiaper);
                handled = true;
                break;
        }
    }

    return handled;
}

function LoadFromCookies() {

    SetAMPM(readCookie("BabyTracker_AMPM"));

    g_EntryForm.DateEntry.value = readCookie("BabyTracker_Date");
    if (g_EntryForm.DateEntry.value == "") g_EntryForm.DateEntry.value = DateStringShort();
    DateEntryChange(g_EntryForm.DateEntry);

    g_EntryForm.TimeEntry.value = readCookie("BabyTracker_Time");
    if (g_EntryForm.TimeEntry.value == "") g_EntryForm.TimeEntry.value = TimeStringShort();

    g_EntryForm.AmountEntry.value = readCookie("BabyTracker_Amount");
    g_EntryForm.DescriptionEntry.value = readCookie("BabyTracker_Note");

    LoadCheckboxFromCookie("checkSavePreviousData", true);
    LoadCheckboxFromCookie("checkDebugMode", false);
    LoadCheckboxFromCookie("checkTestMode", false);

    if (readCookie("BabyTracker_Type") != "")
        g_selectedType = g_BtnTypeArray[TypeIdxFromName(readCookie("BabyTracker_Type"))];

    if (document.getElementById("checkTestMode").checked == false) {
        if (g_IPhoneVersion) g_EntryForm.TimeEntry.value = "";
        g_EntryForm.AmountEntry.value = "";
        g_EntryForm.DescriptionEntry.value = "";
    }
}

function PersistCheckboxCookie(checkbox) {
    if (document.getElementById(checkbox).checked)
        createCookie(checkbox, "checked");
    else
        createCookie(checkbox, "unchecked");
}

function ResetSavedData() {
    eraseCookie("BabyTracker_Date");
    eraseCookie("BabyTracker_date");
    eraseCookie("BabyTracker_Time");
    eraseCookie("BabyTracker_time");
    eraseCookie("BabyTracker_Type");
    eraseCookie("BabyTracker_type");
    eraseCookie("BabyTracker_Amount");
    eraseCookie("BabyTracker_amount");
    eraseCookie("BabyTracker_Note");
    eraseCookie("BabyTracker_note");
    eraseCookie("BabyTracker_description");
    eraseCookie("BabyTracker_Description");
    eraseCookie("BabyTracker_AMPM");

    eraseCookie("BabyTracker_email");
    eraseCookie("BabyTracker_Email");
    eraseCookie("BabyTracker_Password");
    eraseCookie("BabyTracker_Name");

    eraseCookie("checkSavePreviousData");
    eraseCookie("checkDebugMode");
    eraseCookie("checkTestMode");

    // Defaults to checked
    document.getElementById("checkSavePreviousData").checked = true;
    PersistCheckboxCookie("checkSavePreviousData");
}

function PersistCookieData() {
    createCookie("BabyTracker_Date", g_EntryForm.DateEntry.value, 7);
    createCookie("BabyTracker_Time", g_EntryForm.TimeEntry.value, 7);
    createCookie("BabyTracker_Type", TypeName(g_selectedType), 7);
    createCookie("BabyTracker_Amount", g_EntryForm.AmountEntry.value, 7);
    createCookie("BabyTracker_Note", g_EntryForm.DescriptionEntry.value, 7);
    createCookie("BabyTracker_AMPM", GetAmPm(), 7);

    PersistCheckboxCookie("checkSavePreviousData");
    PersistCheckboxCookie("checkDebugMode");
    PersistCheckboxCookie("checkTestMode");
}

function SelectImageType(Obj) {
    if (g_selectedType && g_selectedType != Obj)
        g_selectedType.className = "TypeImage";

    if (Obj != RadioType(Obj))
        RadioType(Obj).checked = true;

    g_selectedType = ImageType(Obj);
    g_selectedType.className = "TypeImageActive";

    switch (TypeName(g_selectedType)) {
        case "Wet Diaper":
        case "Poopy Diaper":
            document.getElementById("divAmountEntry").className = "divEntryHidden";
            break;
        default:
            document.getElementById("divAmountEntry").className = "divEntry";
            break;
    }

}

function EntryIdx(Obj) {
    if (!Obj) {
        alert("missing object");
        return -1;
    }

    var idx = 0;
    for (idx = 0; idx < g_EntryArray.length; idx++) {
        if (g_EntryArray[idx] == Obj) return idx;
    }

    switch (Obj.id) {
        case "DateEntry": return 0; break;
        case "TimeEntry": return 1; break;
        case "AmountEntry": return 2; break;
        case "DescriptionEntry": return 3; break;
        default:
            alert("Unknown selected object");
            return -1;
            break;
    }

    return -1;
}
function EntryTypeFromIdx(Index) {
    switch (Index) {
        case 0: return "Date"; break;
        case 1: return "Time"; break;
        case 2: return "Amount"; break;
        case 3: return "Note"; break;
        default: assert("Unknown idx " + Index); break;
    }
}
function EntryType(Obj) {
    switch (EntryIdx(Obj)) {
        case 0: return "Date"; break;
        case 1: return "Time"; break;
        case 2: return "Amount"; break;
        case 3: return "Note"; break;
        default: assert("Unknown idx " + Index); break;
    }
}

function TypeIdx(Obj) {
    if (!Obj) {
        alert("missing type object");
        return -1;
    }

    var idx = 0;
    for (idx = 0; idx < g_BtnTypeArray.length; idx++) {
        if (g_BtnTypeArray[idx] == Obj) return idx;
    }

    var idx = 0;
    for (idx = 0; idx < g_RadioTypeArray.length; idx++) {
        if (g_RadioTypeArray[idx] == Obj) return idx;
    }
}

function TypeName(Obj) {
    if (!Obj) {
        alert("missing type object");
        return -1;
    }

    return Obj.title;
}

function TypeIdxFromName(Name) {

    var idx = 0;
    for (idx = 0; idx < g_BtnTypeArray.length; idx++) {
        if (g_BtnTypeArray[idx].title == Name)
            return idx;
    }

    for (idx = 0; idx < g_RadioTypeArray.length; idx++) {
        if (g_RadioTypeArray[idx].title == Name)
            return idx;
    }

    alert("Unknown Type Name " + Name);
    return -1;
}

function ImageType(Obj) {
    return g_BtnTypeArray[TypeIdx(Obj)];
}

function RadioType(Obj) {
    return g_RadioTypeArray[TypeIdx(Obj)];
}

function GetAmPm() {
    if (g_EntryForm.btnAM.checked) return "AM";
    if (g_EntryForm.btnPM.checked) return "PM";
    return "";
}

function SavePreviousData_Click(Obj) {
    if (Obj.checked)
        createCookie("SavePreviousData", "checked");
    else
        createCookie("SavePreviousData", "unchecked");
}

var g_StatsPageRefreshIntervalId = 0;
var g_StatsPageRefreshTimer = 0;

function ShowStatsPage() {

    var div = document.getElementById('StatsPageContainer');
    var visible = (div.style.display == "none") ? false : true;
    div.style.display = visible ? "none" : "";

    var aShow = document.getElementById("aShowStatsPageTop");
    var showString = visible ? "Show Stats" : "Hide Stats";
    aShow.title = showString;
    aShow.innerHTML = showString;

    RefreshStatsPage();
}

g_RefreshStatusPending = false;

function RefreshStatsPage() {

    //DebugMsg("RefreshStatsPage", g_RefreshStatusPending);
/*
    if (g_RefreshStatusPending)
        return;
    g_RefreshStatusPending = true;

    if (g_StatsPageRefreshTimer != 0)
        clearTimeout(g_StatsPageRefreshTimer);
*/
    PostRefreshStats();
}

function SetRefreshStatsPageTimer(delay) {
    if (g_StatsPageRefreshTimer == 0)
        g_StatsPageRefreshTimer = setTimeout(RefreshStatsPage, delay);
}

function ShowOptions() {
    var divOptionsPage = document.getElementById('divOptionsPage');
    var divPage = document.getElementById('divPage');

    if (divOptionsPage.style.display == "none") {
        divOptionsPage.style.display = "";
        divPage.style.display = "none";
    }
    else {
        divOptionsPage.style.display = "none";
        divPage.style.display = "";
    }
}

function OptionsDone_Click() {

    PersistCheckboxCookie("checkSavePreviousData");
    PersistCheckboxCookie("checkDebugMode");
    PersistCheckboxCookie("checkTestMode");

    document.getElementById('divOptionsPage').style.display = "none";
    document.getElementById('divPage').style.display = "";

    if (document.getElementById('checkResetSavedData').checked) {
        ResetSavedData();
        window.location.reload();
    }
}

function labelResetSavedData_Click(Obj) {
    Obj.nextObject.click();
}

function GetTimeEntryValue() {
    return g_EntryForm.TimeEntry.value + " " + GetAmPm();
}

function GetValidDate(quiet) {
    // validate data
    var date = g_EntryForm.DateEntry.value;
    if (!isDate(date, quiet)) {
        return "";
    }

    // tack on year if missing
    var idxYear = date.indexOf("/", 3);
    if (idxYear == -1) {
        var currDate = new Date();
        date += "/" + currDate.getFullYear();
    }
    if (!isDate(date, quiet)) {
        return "";
    }
    return date;
}

function OnSubmit_Click() {

    if (g_Entered == g_Submitted)
        document.getElementById("imgBusy").style.display = "none";

    // validate data
    var date = g_EntryForm.DateEntry.value;
    if (!isDate(date, false)) {
        g_EntryForm.DateEntry.select();
        return;
    }

    if (!IsValidTime(GetTimeEntryValue())) {
        g_EntryForm.TimeEntry.select();
        return;
    }

    if (!isRealNumber(g_EntryForm.AmountEntry.value)) {
        g_EntryForm.AmountEntry.select();
        alert("Enter valid number");
        return;
    }

    if (AmountValidForType() && !g_EntryForm.AmountEntry.value) {
        g_EntryForm.AmountEntry.select();
        alert("Enter an Amount");
        return;
    }

    PersistCookieData();

    if (readCookie("BabyTracker_key") == "") {
        alert("Baby Tracker has not been setup yet.  Click OK to be directed to setup, and then please resubmit your entry");
        window.location = "https://secure.iinet.com/joyofplaying.com/BabyTracker/BabyTrackerSetup.htm";
    }

    PostAddRow();

    var btn = document.getElementById("btnSubmit");
    btn.disabled = true;
    btn.className = "btnTypeDepressed";

    document.getElementById("imgBusy").style.display = "";

    if (document.getElementById("checkTestMode").checked == false) {
        if (g_IPhoneVersion) g_EntryForm.TimeEntry.value = "";
        g_EntryForm.AmountEntry.value = "";
        g_EntryForm.DescriptionEntry.value = "";
    }

    g_selectedIndex = 1;
    SelectEntry("TimeEntry");

    RefreshCookies();
}

function AmountValidForType() {
    var type = TypeName(g_selectedType);
    if (type == "" || type == "Wet Diaper" || type == "Poopy Diaper")
        return (false);
    return (true);
}

function RefreshCookieHelper(key) {
    var value = readCookie(key);
    if (value)
        createCookie(key, value, 2000);
}

function RefreshCookies() {
    RefreshCookieHelper("BabyTracker_key");
    RefreshCookieHelper("BabyTracker_spreadsheetid");
    RefreshCookieHelper("BabyTracker_worksheetid");
    RefreshCookieHelper("BabyTracker_sqlid");
    RefreshCookieHelper("BabyTracker_token");
    RefreshCookieHelper("BabyTracker_tablename");
}
function GetSubmitData() {

    //DateEntry=12%2F01&entry.0.single=12%2F01&entry.1.single=2%3A09+pm&TypeGroup=Breast&entry.3.single=40&entry.5.single=Long+breast+feed
    var data = "";
    if (g_EntryForm.DateEntry.value) data += "DateEntry=" + encodeDateForUrl(encodeURI(g_EntryForm.DateEntry.value)) + "&";
    if (g_EntryForm.DateEntry.value) data += "entry.0.single=" + encodeDateForUrl(encodeURI(g_EntryForm.DateEntry.value)) + "&";
    if (g_EntryForm.TimeEntry.value) data += "entry.1.single=" + encodeURI(GetTimeEntryValue()) + "&";
    if (AmountValidForType() && g_EntryForm.AmountEntry.value) data += "entry.3.single=" + encodeURI(g_EntryForm.AmountEntry.value) + "&";
    if (g_EntryForm.DescriptionEntry.value) data += "entry.5.single=" + encodeURI(g_EntryForm.DescriptionEntry.value) + "&";
    if (TypeName(g_selectedType)) data += "TypeGroup=" + encodeURI(TypeName(g_selectedType)) + "&";

    return data;
}

function GetLocalSubmitData() {

    var data = "";
    if (g_EntryForm.DateEntry.value) data += "&date=" + encodeDateForUrl(encodeURI(GetValidDate(false)));
    if (g_EntryForm.TimeEntry.value) data += "&time=" + encodeURI(GetTimeEntryValue());
    if (TypeName(g_selectedType)) data += "&type=" + encodeURI(TypeName(g_selectedType));
    if (AmountValidForType() && g_EntryForm.AmountEntry.value) data += "&amount=" + encodeURI(g_EntryForm.AmountEntry.value);
    if (g_EntryForm.DescriptionEntry.value) data += "&description=" + encodeURI(g_EntryForm.DescriptionEntry.value);
    if (document.getElementById("checkDebugMode").checked) data += "&debugmode=true";
    if (document.getElementById("checkTestMode").checked) data += "&testmode=true";
    if (g_UpdateRowID) data += "&sqlrowid=" + g_UpdateRowID;

    return data;
}

function GetLocalSubmitDataEx() {
    var data = "";
    if (g_EntryForm.DateEntry.value) data += "date=" + g_EntryForm.DateEntry.value + "&";
    if (g_EntryForm.TimeEntry.value) data += "time=" + GetTimeEntryValue() + "&";
    if (TypeName(g_selectedType)) data += "type=" + TypeName(g_selectedType) + "&";
    if (AmountValidForType() && g_EntryForm.AmountEntry.value) data += "amount=" + g_EntryForm.AmountEntry.value + "&";
    if (g_EntryForm.DescriptionEntry.value) data += "description=" + g_EntryForm.DescriptionEntry.value;
    // Remove dangling &
    while (data.substr(data.length - 1) == "&")
        data = data.substr(0, data.length - 1);
    return data;
}

function ActionCallback(status, response, action, cookie) {
    //DebugMsg("ActionCallback", action + " status=" + status);
    switch (action) {
        case "addrow":
            return AddRowCallback(status, response);
            break;

        case "stats_col":
            return RefreshStatusCallback(status, response);
            break;

        case "run":
            //PostAction("runall", 1);
            break;

        case "runall":
            //if (g_Entered > g_Submitted)
                //PostAction("runall", 1);
            break;
    }
}

function PostAddRow()
{
    var data = GetLocalSubmitData() + ReadCachedPostData();
    var post = new HtmlPost();
    var action = g_UpdateRowID==null ? "addrow" : "updaterow";
    post.Post(action, data, AddRowCallback, GetLocalSubmitDataEx());
    g_Entered++;
    document.getElementById("lblSubmitOutput").innerHTML = "Processing: " + g_Submitted + " of " + g_Entered + " submitted";
}

function AddRowCallback(status, response, action, cookie) {

    var retVal = -1;

    var btn = document.getElementById("btnSubmit");
    btn.disabled = false;
    btn.className = "btnSubmit";
    btn.value = "Submit";
    g_UpdateRowID = null;

    var data = "";
    var idxData = response.indexOf("Data:");
    if (idxData != -1) {
        data = response.substr(idxData + 5);
        DisplayAddedDataRow(data);
    }

    if (status == 200) {
        if (document.getElementById("checkDebugMode").checked == true) {
			var old = document.getElementById("frameDebug").innerHTML;
            document.getElementById("frameDebug").innerHTML = response + "<hr><hr><hr>" + old;
            document.getElementById("frameDebug").style.display = "";
        }

        if (response.indexOf("Successfully added the data.") != -1) {

            g_Submitted++;
            document.getElementById("lblSubmitOutput").innerHTML = "SUCCESS: " + g_Submitted + " of " + g_Entered + " submitted";
            if (g_Entered == g_Submitted)
                document.getElementById("imgBusy").style.display = "none";

            PostAction("run", 3);
            PostAction("runall", 1);

            retVal = 1;
        }
    }
    else
    {
        document.getElementById("lblSubmitOutput").innerHTML = "FAILED to add data to spreadsheet. " + g_Submitted + " of " + g_Entered + " submitted";

        var row = FailedDataToTableRow("failed", cookie);
        DisplayAddedDataRow(row);

        alert("Failed to submit entry. status=" + status + " Data: " + cookie);
        document.getElementById("frameDebug").innerHTML += response;
        document.getElementById("frameDebug").style.display = "";

        if (g_Entered == g_Submitted)
            document.getElementById("imgBusy").style.display = "none";

        return -1;
    }

    RefreshCookies();
}

function DeleteRowCallback(status, response, action, cookie) {

    var retVal = -1;

    var btn = document.getElementById("btnSubmit");
    btn.disabled = false;
    btn.className = "btnSubmit";

    var data = "";
    var idxData = response.indexOf("Data:");
    if (idxData != -1) {
        data = response.substr(idxData + 5);
        DisplayDeletedDataRow(data);
    }

    if (status == 200) {
        if (document.getElementById("checkDebugMode").checked == true) {
			var old = document.getElementById("frameDebug").innerHTML;
            document.getElementById("frameDebug").innerHTML = response + "<hr><hr><hr>" + old;
            document.getElementById("frameDebug").style.display = "";
        }

        if (response.indexOf("Successfully deleted the data.") != -1) {

            g_Submitted++;
            document.getElementById("lblSubmitOutput").innerHTML = "SUCCESS: " + g_Submitted + " of " + g_Entered + " submitted";
            if (g_Entered == g_Submitted)
                document.getElementById("imgBusy").style.display = "none";

            PostAction("run", 3);
            PostAction("runall", 1);

            retVal = 1;
        }
    }
    else
    {
        document.getElementById("lblSubmitOutput").innerHTML = "FAILED to delete data to spreadsheet. " + g_Submitted + " of " + g_Entered + " submitted";

        var row = FailedDataToTableRow("failed", cookie);
        DisplayAddedDataRow(row);

        alert("Failed to submit entry. status=" + status + " Data: " + cookie);
        document.getElementById("frameDebug").innerHTML += response;
        document.getElementById("frameDebug").style.display = "";

        if (g_Entered == g_Submitted)
            document.getElementById("imgBusy").style.display = "none";

        return -1;
    }

    RefreshCookies();
}

function PostDeleteRow(rowid)
{
    var data = "sqlrowid=" + rowid + ReadCachedPostData();
    var post = new HtmlPost();
    var action = "deleterow";
    post.Post(action, data, DeleteRowCallback, GetLocalSubmitDataEx());
    g_Entered++;
    document.getElementById("lblSubmitOutput").innerHTML = "Processing: " + g_Submitted + " of " + g_Entered + " submitted";
}

function PostRefreshStats() {
    var key = readCookie("BabyTracker_key");
    if (key == "")
        return;

    var data = "col=B&col_end=F";
    data += ReadCachedPostData();

    var post = new HtmlPost();
    post.PostSingleton("stats_sql", data, RefreshStatusCallback);
}

function RefreshStatusCallback(status, response, action, cookie) {

    var retVal = -1;
    var oPage = document.getElementById("StatsPage");
    if (status == 200) {
        oPage.innerHTML = response;
        retVal = 1;
        SetRefreshStatsPageTimer(10);
    }
    else {
        oPage.innerHTML = "ERROR: status = " + status + " response = " + response;
        retVal = -1;
    }
    return retVal;
}

function PostAction(action, max) {

    var data = "verbose=1";
    data += ReadCachedPostData();

    var post = new HtmlPost();
    post.PostMultiple(action, data, ActionCallback, max);
}

function ReadCachedPostData() {
    var data = "";
    data += "&name=" + readCookie("BabyTracker_name");
    data += "&key=" + readCookie("BabyTracker_key");
    data += "&spreadsheetid=" + readCookie("BabyTracker_spreadsheetid");
    data += "&worksheetid=" + readCookie("BabyTracker_worksheetid");
    //data += "&uid=" + uniqid();
    if (readCookie("BabyTracker_sqlid")) data += "&sqlid=" + readCookie("BabyTracker_sqlid");
    if (readCookie("BabyTracker_token")) data += "&token=" + readCookie("BabyTracker_token");
    if (readCookie("BabyTracker_tablename")) data += "&tablename=" + readCookie("BabyTracker_tablename");
    if (readCookie("BabyTracker_userid")) data += "&userid=" + readCookie("BabyTracker_userid");

    return data;
}

function EntryFromString(key, source) {
    var idx = source.indexOf(key + "=");
    if (idx == -1)
        return "";

    idx += key.length + 1;

    var idxEnd = source.indexOf("&", idx);
    if (idxEnd == -1)
        idxEnd = source.length;

    var value = source.substr(idx, idxEnd - idx);

    return value;
}

function DataToTableRow(state, data) {
    var row = "<tr class='dataRow'>";
    row += "<td class='dataCell'><img src='images/edit.png' /><img src='images/delete.png' /></td>";
    row += "<td class='dataCell'>" + EntryFromString("date", data) + "</td>";
    row += "<td class='dataCell'>" + EntryFromString("time", data) + "</td>";
    row += "<td class='dataCell'>" + EntryFromString("type", data) + "</td>";
    row += "<td class='dataCell'>" + EntryFromString("amount", data) + "</td>";
    row += "<td class='dataCell'>" + EntryFromString("description", data) + "</td>";
    row += "</tr>";
    return row;
}

function FailedDataToTableRow(state, data) {
    var row = "<tr class='dataRow'>";
    row += "<td class='dataCellStateFailed'>" + state + "</td>";
    row += "<td class='dataCellFailed'>" + EntryFromString("date", data) + "</td>";
    row += "<td class='dataCellFailed'>" + EntryFromString("time", data) + "</td>";
    row += "<td class='dataCellFailed'>" + EntryFromString("type", data) + "</td>";
    row += "<td class='dataCellFailed'>" + EntryFromString("amount", data) + "</td>";
    row += "<td class='dataCellFailed'>" + EntryFromString("description", data) + "</td>";
    row += "</tr>";
    return row;
}

function DisplayAddedDataRow(dataRow) {

    var div = document.getElementById("divDataTable");
    var table = document.getElementById("dataTable");
    var body = div.innerHTML;
    body = body.replace("<!--NewRow-->", "<!--NewRow-->" + dataRow);
    //tableHTML = "<table id='dataTable' class='dataTable'>" + body + "</table>";
    div.innerHTML = body;
    div.style.display = "";
}

function DisplayDeletedDataRow(dataRow) {

    var div = document.getElementById("divDataTable");
    var table = document.getElementById("dataTable");
    var body = div.innerHTML;

	var sqlrowid = EntryFromString("sqlrowid", dataRow);
	var timestamp = EntryFromString("timestamp", dataRow);

	var idx = body.indexOf("sqlrowid="+sqlrowid);
	//alert(idx + " sqlrowid=" + sqlrowid);
	while (idx != -1)
	{
		var idxTRStart = body.lastIndexOf("<tr", idx);
		var idxTREnd = body.indexOf("</tr>", idx);

		if (idxTRStart != -1 && idxTREnd != -1)
		{
			var trEnd = "</tr>";
			idxTREnd += trEnd.length;
			var tr = body.substr(idxTRStart, idxTREnd - idxTRStart);
			//alert("tr=" + tr);
			body = body.replace(tr, "<tr><td>deleted</td></tr>");
			//alert("new body = " + body);
		}

		idx = body.indexOf("sqlrowid="+sqlrowid);
	}
    //tableHTML = "<table id='dataTable' class='dataTable'>" + body + "</table>";
    div.innerHTML = body;
    div.style.display = "";
}

function sleep(milliSeconds) {
    var startTime = new Date().getTime(); // get the current time
    while (new Date().getTime() < startTime + milliSeconds); // hog cpu
}

function TestSubmit_Click() {

    var uid = uniqid();
    var count = 0;
    while (count < 80) {
        for (typeIdx = 0; typeIdx < 5; typeIdx++) {
            g_selectedType = g_RadioTypeArray[typeIdx];
            g_selectedType.checked = true;
            document.getElementById("AmountEntry").value = count;
            document.getElementById("DescriptionEntry").value = "Test Submit " + uid + " idx=" + count;
            DebugMsg("TestSubmit_Click()", "Test type=" + TypeName(g_selectedType) + " amount=" + document.getElementById("AmountEntry").value + " description=" + document.getElementById("DescriptionEntry").value);
            OnSubmit_Click();
            count++;
            //sleep(randomNumber(2) * 1000);
        }
    }
}

function DebugMsg(func, msg) {
    var txt = document.getElementById("txtDebug");
    txt.value = txt.value + "\n" + func + "() " + msg;
    txt.parentNode.style.display = "";
}

function DateEntryChange(Obj) {

    var lbl = document.getElementById("DayOfWeek");
    if (!Obj.value) {
        lbl.innerHTML = "";
    }

    var date = GetValidDate(true);
    if (date == "") {
        lbl.innerHTML = "...";
        return;
    }

    lbl.innerHTML = GetDayOfWeek(date);
}

function RunSetup() {
    window.location = "BabyTrackerSetup.htm";
}

function EraseData_Click() {
    deleteAllCookies();
    alert("Done");
}

function OnEditRow_Click(data)
{
	g_UpdateRowID = EntryFromString("sqlrowid", data);
    if (g_UpdateRowID == "") {
        alert("Missing row id for entry " + data);
        return;
    }

    var timeString = EntryFromString("time", data);
    if (timeString) {
        var idxAMPM = timeString.indexOf("AM");
        if (idxAMPM == -1)
            idxAMPM = timeString.indexOf("PM");
        var ampm = timeString.substr(idxAMPM);
        SetAMPM(ampm);

        var timeStringShort = timeString.substr(0, idxAMPM);
        g_EntryForm.TimeEntry.value = timeStringShort.trim();
    }

    g_EntryForm.DateEntry.value = EntryFromString("date", data);
    g_EntryForm.AmountEntry.value = EntryFromString("amount", data);
    g_EntryForm.DescriptionEntry.value = EntryFromString("description", data);

	var type = EntryFromString("type", data);
	g_selectedType = g_BtnTypeArray[TypeIdxFromName(type)];
	SelectImageType(g_selectedType);

    document.getElementById("btnSubmit").value = "Update";
    document.getElementById("btnCancel").style.display = "";
}

function OnDeleteRow_Click(data)
{
    var rowid = EntryFromString("rowid", data);
    if (rowid == "") {
        alert("Missing row id for entry " + data);
        return;
    }

	PostDeleteRow(rowid);
}
