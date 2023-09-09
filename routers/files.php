<?php
function route($requestMethod, $requestPath, $requestData)
{
    switch ($requestMethod) {
        case "POST":
            getUploadedFile($requestPath, $requestData);
            break;
        case "GET":
            handleFileGetRequest($requestPath);
            break;
        default:
            setHTTPStatus("400", "Only GET and POST requests are allowed here");
            break;
    }
}

function getUploadedFile($requestPath, $requestData)
{
    switch ($requestPath[1]) {
        case "base64":
            getUploadedFileBase64($requestData);
            break;
        case "formdata":
            getUploadedFileFormData();
            break;
        default:
            setHTTPStatus("400", "Invalid upload method, only base64 (json) and formdata are allowed");
            break;
    }
}

function handleFileGetRequest($requestPath)
{
    switch ($requestPath[1]) {
        case "list":
            listUserFiles();
            break;
        default:
            sendRequestedFile($requestPath[1]);
            break;
    }
}

function listUserFiles()
{
    global $Link;
    $userID = getUserIDFromBearerToken();
    if (!$userID) {
        setHTTPStatus("401", "Authentication is required to see uploaded files");
        return;
    }

    $queryResult = $Link->query("SELECT path FROM uploads WHERE ownerID=$userID");

    if (!$queryResult) {
        setHTTPStatus("500", "Unable to get file list from database");
        return;
    }

    $fileList = [];
    while ($row = $queryResult->fetch_assoc()) {
        $fileList['files'][] = $row['path'];
    }

    echo json_encode($fileList);
}

function sendRequestedFile($fileName)
{
    global $Link, $UploadPath;
    if (is_null($fileName)) {
        setHTTPStatus("403", "File name is not specified");
        return;
    }
    $userID = getUserIDFromBearerToken();
    if (!$userID) {
        setHTTPStatus("401", "Authentication is required to see uploaded files");
        return;
    }

    $queryResult = $Link->query("SELECT path FROM uploads WHERE ownerID=$userID AND path='$fileName'");

    if (!$queryResult) {
        setHTTPStatus("500", "Unable to check file existence in the database");
        return;
    }

    if (!($row = $queryResult->fetch_assoc())) {
        setHTTPStatus("404", "File with name $fileName does not exist");
        return;
    }

    $fileName = $row['path'];
    $filePath = $UploadPath . DIRECTORY_SEPARATOR . $fileName;

    if (!file_exists($filePath)) {
        setHTTPStatus("404", "File not found on the server");
        return;
    }

    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    header('Content-Type: ' . finfo_file($fileInfo, $filePath));
    finfo_close($fileInfo);

    header('Content-Disposition: attachment; filename=' . basename($fileName));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    ob_clean();
    flush();
    readfile($filePath);
    exit;
}

function getUploadedFileBase64($requestData)
{
    global $Link, $UploadPath;
    $encodedFile = $requestData->body->file;
    $fileName = $requestData->body->fileName;
    $mimeType = $requestData->body->mimeType;
    //TODO size limit
    $sizeBytes = $requestData->body->sizeBytes;

    if (is_null($encodedFile) || is_null($fileName) || is_null($mimeType) || is_null($sizeBytes)) {
        setHTTPStatus("403", "Information for file upload is partially or completely missing");
        return;
    }

    $fileName = basename($fileName);

    if (preg_match("(audio/([a-zA-Z\d])+)", $mimeType)) {
        $userID = getUserIDFromBearerToken();
        if (!$userID) {
            setHTTPStatus("401", "Authentication is required to upload files");
            return;
        }

        $decodedFile = base64_decode($encodedFile);
        $fileName = "upload_" . time() . $fileName;
        $filePath = $UploadPath . DIRECTORY_SEPARATOR . $fileName;

        $fileStream = fopen($filePath, "wb");
        if (!$fileStream) {
            setHTTPStatus("500", "Unable to create file on the server");
            return;
        }
        if (!fwrite($fileStream, $decodedFile)) {
            setHTTPStatus("500", "Unable to write file to the server");
            return;
        }
        fclose($fileStream);

        $insertionResult = $Link->query("INSERT INTO uploads(path, ownerID) VALUES ('$fileName', $userID)");

        if (!$insertionResult) {
            setHTTPStatus("500", "Unable to insert file info to the database");
            return;
        }

        setHTTPStatus("200", "File was successfully uploaded, name: $fileName");
    } else {
        setHTTPStatus("403", "Forbidden mimetype: $mimeType");
    }
}

function getUploadedFileFormData()
{
    global $Link, $UploadPath;
    $file = $_FILES['input'];

    if (is_null($file)) {
        setHTTPStatus("403", "No file found");
        return;
    }

    if (preg_match("(audio/([a-zA-Z\d])+)", $file['type'])) {
        $userID = getUserIDFromBearerToken();
        if (!$userID) {
            setHTTPStatus("401", "Authentication is required to upload files");
            return;
        }

        $fileName = "upload_" . time() . $file['name'];
        $filePath = $UploadPath . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            setHTTPStatus("500");
            return;
        }

        $insertionResult = $Link->query("INSERT INTO uploads(path, ownerID) VALUES ('$fileName', $userID)");

        if (!$insertionResult) {
            setHTTPStatus("500", "Unable to insert file info to the database");
            return;
        }

        setHTTPStatus("200", "File was successfully uploaded, name: $fileName");
    } else {
        $mimeType = $file['type'];
        setHTTPStatus("403", "Forbidden mimetype: $mimeType");
    }
}

?>
