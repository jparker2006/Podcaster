<?php

if (isset($_POST['pullPodcasts']))
    $jsonPullData = $_POST['pullPodcasts'];
else if (isset($_POST['insertBug']))
    $sBug = $_POST['insertBug'];
else if (isset($_POST['pullBugs']))
    $bPullTheBugs = $_POST['pullBugs'];
else if (isset($_POST['uniqueUN']))
    $sUsername = $_POST['uniqueUN'];
else if (isset($_POST['login']))
    $jsonCredentials = $_POST['login'];
else if (isset($_POST['createAccount']))
    $jsonUserData = $_POST['createAccount'];
else if (isset($_POST['updateData']))
    $jsonPCData = $_POST['updateData'];
else if (isset($_POST['addPodcastToDatabase']))
    $jsonNewPodcast = $_POST['addPodcastToDatabase'];

if ($jsonPullData)
    $sFeedback = pullPodcasts ($jsonPullData);
else if ($sBug)
    $sFeedback = insertBug ($sBug);
else if ($bPullTheBugs)
    $sFeedback = pullBugs ();
else if ($sUsername)
    $sFeedback = uniqueUN ($sUsername);
else if ($jsonCredentials)
    $sFeedback = login ($jsonCredentials);
else if ($jsonUserData)
    $sFeedback = createAccount ($jsonUserData);
else if ($jsonPCData)
    $sFeedback = updateData ($jsonPCData);
else if ($jsonNewPodcast)
    $sFeedback = addPodcastToDatabase ($jsonNewPodcast);

echo $sFeedback;

function pullPodcasts ($jsonPullData) {
    $objPullData = json_decode ($jsonPullData);

    $dbhost = 'localhost';
    $dbuser = 'podcasts_site';
    $dbpass = '0ShF3HctflFXwkhQSYte';
    $db = "podcaster";
    $dbconnect = new mysqli($dbhost, $dbuser, $dbpass, $db);
    $aPodcasts = [];

    if (!$objPullData->bSearching) {
        $sSQL = "SELECT COUNT(*) FROM podcasts";
        $tResult = QueryDB($sSQL);
        $nPodcasts = $tResult->fetch_assoc()["COUNT(*)"];
        $aRandomPulls = [random_int(1, $nPodcasts)];
        for ($x=1; $x<15; $x++) {
            $randCurr = random_int(1, $nPodcasts);
            while (in_array($randCurr, $aRandomPulls)) {
                $randCurr = random_int(1, $nPodcasts);
            }
            $aRandomPulls[$x] = $randCurr;
        }

        for ($i=0; $i<15; $i++) {
            $sSQL = "SELECT * FROM podcasts WHERE id=" . $aRandomPulls[$i];
            $tResult = QueryDB($sSQL);
            $row = $tResult->fetch_assoc();
            $aPodcasts[$i] = new stdClass();
            $aPodcasts[$i]->id = $row["id"];
            $aPodcasts[$i]->title = $row["title"];
            $aPodcasts[$i]->description = $row["description"];
            $aPodcasts[$i]->link = $row["link"];
            $aPodcasts[$i]->created = $row["created"];
        }
    }
    else {
        $objPullData->search = "%" . $objPullData->search . "%";
        $stmt = $dbconnect->prepare("SELECT * FROM podcasts WHERE title LIKE ? OR description LIKE ? ORDER BY id DESC LIMIT 15");
        $stmt->bind_param("ss", $objPullData->search, $objPullData->search);
        $stmt->execute();
        $tResult = $stmt->get_result();
        $stmt->close();
        $dbconnect->close();
        for ($i=0; $i<$tResult->num_rows; $i++) {
            $row = $tResult->fetch_assoc();
            $aPodcasts[$i] = new stdClass();
            $aPodcasts[$i]->id = $row["id"];
            $aPodcasts[$i]->title = $row["title"];
            $aPodcasts[$i]->description = $row["description"];
            $aPodcasts[$i]->link = $row["link"];
            $aPodcasts[$i]->created = $row["created"];
        }
    }
    if (0 == sizeof($aPodcasts))
        return null;
    return json_encode ($aPodcasts);
}

function insertBug ($sBug) {
    $dbhost = 'localhost';
    $dbuser = 'podcasts_site';
    $dbpass = '0ShF3HctflFXwkhQSYte';
    $db = "podcaster";
    $dbconnect = new mysqli($dbhost, $dbuser, $dbpass, $db);

    $stmt = $dbconnect->prepare("INSERT INTO bugs (issue) VALUES (?)");
    $stmt->bind_param("s", $sBug);
    $bStatus = $stmt->execute();
    $stmt->close();

    return $bStatus;
}

function pullBugs () {
    $sSQL = "SELECT * FROM bugs LIMIT 15";
    $tResult = QueryDB($sSQL);
    $aBugs = [];
    for ($i=0; $i<$tResult->num_rows; $i++) {
        $row = $tResult->fetch_assoc();
        $aBugs[$i] = new stdClass();
        $aBugs[$i]->bug = $row["issue"];
        $aBugs[$i]->fix_started = $row["created"];
        $aBugs[$i]->created = $row["created"];
    }
    if (0 == sizeof($aBugs))
        return null;
    return json_encode ($aBugs);
}

function uniqueUN ($sUsername) {
    $dbhost = 'localhost';
    $dbuser = 'podcasts_site';
    $dbpass = '0ShF3HctflFXwkhQSYte';
    $db = "podcaster";
    $dbconnect = new mysqli($dbhost, $dbuser, $dbpass, $db);

    $stmt = $dbconnect->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $sUsername);
    $stmt->execute();
    $tResult = $stmt->get_result();
    $stmt->close();
    $dbconnect->close();

    return 0 == $tResult->num_rows ? $sUsername : null;
}

function login ($jsonCredentials) {
    $objCredentials = json_decode($jsonCredentials);
    $dbhost = 'localhost';
    $dbuser = 'podcasts_site';
    $dbpass = '0ShF3HctflFXwkhQSYte';
    $db = "podcaster";
    $dbconnect = new mysqli($dbhost, $dbuser, $dbpass, $db);

    $stmt = $dbconnect->prepare("SELECT * FROM users WHERE username=? AND password=?");
    $stmt->bind_param("ss", $objCredentials->username, $objCredentials->password);
    $stmt->execute();
    $tResult = $stmt->get_result();
    $row = $tResult->fetch_assoc();
    $stmt->close();
    $dbconnect->close();

    if (1 != $tResult->num_rows)
        return false;

    $sSQL = "UPDATE users SET last_used=CURRENT_TIMESTAMP WHERE id=" . $row["id"];
    QueryDB ($sSQL);

    $objUserData = new stdClass();
    $objUserData->id= $row["id"];
    $objUserData->data = $row["data"];

    return json_encode($objUserData);
}

function createAccount ($jsonUserData) {
    $objUserData = json_decode($jsonUserData);

    $dbhost = 'localhost';
    $dbuser = 'podcasts_site';
    $dbpass = '0ShF3HctflFXwkhQSYte';
    $db = "podcaster";
    $dbconnect = new mysqli($dbhost, $dbuser, $dbpass, $db);

    $stmtI = $dbconnect->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmtI->bind_param("ss", $objUserData->username, $objUserData->password);
    $stmtI->execute();
    $stmtI->close();

    $stmtS = $dbconnect->prepare("SELECT id FROM users WHERE username=?");
    $stmtS->bind_param("s", $objUserData->username);
    $stmtS->execute();
    $tResult = $stmtS->get_result();
    $row = $tResult->fetch_assoc();
    $dbconnect->close();

    return $row["id"];
}

function updateData ($jsonPCData) {
    $objPCData = json_decode($jsonPCData);

    $dbhost = 'localhost';
    $dbuser = 'podcasts_site';
    $dbpass = '0ShF3HctflFXwkhQSYte';
    $db = "podcaster";
    $dbconnect = new mysqli($dbhost, $dbuser, $dbpass, $db);

    $stmt = $dbconnect->prepare("UPDATE users SET data=? WHERE id=?");
    $stmt->bind_param("si", $objPCData->data, $objPCData->id);
    $bStatus = $stmt->execute();
    $stmt->close();
    $dbconnect->close();
    if (isset($objPCData->podID))
        return $objPCData->podID;
    return $objPCData->pData;
}

function addPodcastToDatabase ($jsonNewPodcast) {
    $objNewPodcast = json_decode($jsonNewPodcast);

    $dbhost = 'localhost';
    $dbuser = 'podcasts_site';
    $dbpass = '0ShF3HctflFXwkhQSYte';
    $db = "podcaster";
    $dbconnect = new mysqli($dbhost, $dbuser, $dbpass, $db);

    $stmt = $dbconnect->prepare("SELECT * FROM podcasts WHERE link=?");
    $stmt->bind_param("s", $objNewPodcast->link);
    $stmt->execute();
    $tResult = $stmt->get_result();
    if (0 < $tResult->num_rows) {
        $row = $tResult->fetch_assoc();
        $objPodcast = new stdClass();
        $objPodcast->id = $row["id"];
        $objPodcast->link = $objNewPodcast->link;
        return json_encode($objPodcast);
    }
    $stmt = $dbconnect->prepare("INSERT INTO podcasts (title, description, link) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $objNewPodcast->title, $objNewPodcast->description, $objNewPodcast->link);
    $stmt->execute();
    $sSQL = "SELECT COUNT(*) FROM podcasts";
    $tResult = QueryDB($sSQL);
    $nID = $tResult->fetch_assoc()["COUNT(*)"];
    $objPodcast = new stdClass();
    $objPodcast->id = $nID;
    $objPodcast->link = $objNewPodcast->link;
    return json_encode($objPodcast);
}

function QueryDB ($sSQL) {
    $dbhost = 'localhost';
    $dbuser = 'podcasts_site';
    $dbpass = '0ShF3HctflFXwkhQSYte';
    $db = "podcaster";
    $dbconnect = new mysqli($dbhost, $dbuser, $dbpass, $db);
    $Result = $dbconnect->query($sSQL);
    $dbconnect->close();
    return $Result;
}

?>
