key g_kHttpRequest;
key g_kUser = NULL_KEY;

default
{
    state_entry()
    {
    }
    
    touch_end(integer i)
    {
        key kID = llDetectedKey(0);
        if (kID == NULL_KEY) return;
        if (g_kUser != NULL_KEY) return; // in use by another user
        
        string sName = llKey2Name(kID);
        if (~llSubStringIndex(sName, "@")) {
            llDialog(kID, "This terminal can only be used by local grid residents", ["OK"], -1234);
            return;
        }
        
        g_kUser = kID;
        llSetText("In use by "+sName, <1,1,1>, 1);
        string sBody = "userID="+(string)g_kUser;
        g_kHttpRequest = llHTTPRequest(
            "http://192.168.0.10/lslhttp/trash.php",
            [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"],
            sBody);
    }
    
    http_response(key kHttpRequest, integer iStatus, list lMetadata, string sBody)
    {
        if (kHttpRequest != g_kHttpRequest) return;

        if (iStatus == 200) {
            llDialog(g_kUser, sBody, ["OK"], -1234);
        } else {
            llDialog(g_kUser, "Status: "+(string)iStatus, ["OK"], -1234);
        }
        g_kUser = NULL_KEY;
        llSetText("", <1,1,1>, 1);
    }
}
