<?php
include("trash_config.php");

// Don't change below this line -----------------------------------------
$NULL_KEY = "00000000-0000-0000-0000-000000000000";

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(404);
    die();
}

// Only inworld objects owned by ALLOWED_STAFF may call this script
$objectOwner = isset($_SERVER["HTTP_X_SECONDLIFE_OWNER_KEY"]) ? $_SERVER["HTTP_X_SECONDLIFE_OWNER_KEY"] : $NULL_KEY;
if ($objectOwner != $ALLOWED_STAFF || $objectOwner == $NULL_KEY || strlen($objectOwner) != 36) {
    http_response_code(401);
    die();
}

// COMMENT OUT WHEN ONLY LISTENING ON A PRIVATE IP, SUCH AS 192.168.0.10
// NEEDED WHEN LISTENING ON A PUBLIC IP! FILL IN ALLOWED_HOST ABOVE WITH THE
// FQDN OF THE SERVER THAT RUNS THE SIM WITH THE TRASH TERMINAL.
/*
// Only grid-local inworld objects hosted on ALLOWED_HOST may call this script
$hostip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "unknown";
if ($hostip == "unknown") {
    // couldn't resolve client ip
    http_response_code(401);
    die();
} else {
    $hostname = gethostbyaddr($hostip);
    if (!str_ends_with($hostname, $ALLOWED_HOST)) {
        // end of hostname DOESN'T match $ALLOWED_HOST
        http_response_code(401);
        die();
    }
}
*/

// Get the user of whom to empty trash for
$userID = isset($_POST["userID"]) ? $_POST["userID"] : $NULL_KEY;
if ($userID == $NULL_KEY || strlen($userID) != 36) {
    http_response_code(403);
    die();
}

function GetSubFolders($parentFolder)
{
    global $conn, $userID;
    $sql = "SELECT folderName, folderID FROM inventoryfolders WHERE parentFolderID=? AND agentID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $parentFolder, $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $folderList = array();
    if ($result->num_rows > 0) {
        for ($i = 0; $i < $result->num_rows; $i++) {
            $row = $result->fetch_assoc();
            $folderName = $row["folderName"];
            $folderID = $row["folderID"];
            //print("Adding folder $folderName\n");
            array_push($folderList, $folderID);
            $subFolderIDs = GetSubFolders($folderID);
            if (!empty($subFolderIDs)) {
                $folderList = array_merge($folderList, $subFolderIDs);
            }
        }
    }
    $stmt->close();
    return $folderList;
}

function DeleteSubFolders($parentFolder)
{
    global $conn, $userID;
    $sql = "DELETE FROM inventoryfolders WHERE parentFolderID=? AND agentID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $parentFolder, $userID);
    $stmt->execute();
    $stmt->close();
}

function DeleteItems($parentFolder)
{
    global $conn, $userID;
    $sql = "DELETE FROM inventoryitems WHERE parentFolderID=? AND avatarID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $parentFolder, $userID);
    $stmt->execute();
    $stmt->close();
}

// Create and check db connection
$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

// Get trash folder UUID
$sql = "SELECT folderID FROM inventoryfolders WHERE type=14 AND agentID=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $trashFolderID = $row["folderID"];
    //print("Trash folder UUID: " . $trashFolderID . "\n");
    $allTrashFolders = GetSubFolders($trashFolderID);
    //print("Found " . sizeof($allTrashFolders) . " total folders in trash\n");

    //print("Emptying trash...\n");

    // Delete items and folders not directly under Trash rootfolder
    if (sizeof($allTrashFolders) > 0) {
        // Delete items and folders in every subfolder of Trash
        foreach($allTrashFolders as $folder) {
            DeleteItems($folder);
            DeleteSubFolders($folder);
        }
    }

    // Delete items and folders directly under Trash rootfolder
    DeleteItems($trashFolderID);
    DeleteSubFolders($trashFolderID);
    http_response_code(200);
    print("\nTrash has been emptied.\n\nYou may now also empty the Trash folder in your viewer inventory.");
} else {
    http_response_code(200);
    print("\nERROR: No trash folder found.\n\nContact an admin!");
}

$conn->close();

?>
