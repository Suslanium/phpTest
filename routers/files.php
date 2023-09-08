<?php
function route($requestMethod, $requestPath, $requestData)
{
    switch ($requestMethod) {
        case "POST":
            getUploadedFile($requestPath, $requestData);
            break;
        case "GET":
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

    if (preg_match("(audio/([a-zA-Z\d])+)", $mimeType)) {
        $userID = getUserIDFromBearerToken();
        if (!$userID) {
            setHTTPStatus("401", "Authentication is required to upload files");
            return;
        }

        $decodedFile = base64_decode($encodedFile);
        $filePath = $UploadPath . DIRECTORY_SEPARATOR . "upload_" . time() . $fileName;

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

        $insertionResult = $Link->query("INSERT INTO uploads(path, ownerID) VALUES ('$filePath', $userID)");

        if (!$insertionResult) {
            setHTTPStatus("500", "Unable to insert file info to the database");
            return;
        }

        setHTTPStatus("200", "File was successfully uploaded, path: $filePath");
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

        $filePath = $UploadPath . DIRECTORY_SEPARATOR . "upload_" . time() . $file['name'];

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            setHTTPStatus("500");
            return;
        }

        $insertionResult = $Link->query("INSERT INTO uploads(path, ownerID) VALUES ('$filePath', $userID)");

        if (!$insertionResult) {
            setHTTPStatus("500", "Unable to insert file info to the database");
            return;
        }

        setHTTPStatus("200", "File was successfully uploaded, path: $filePath");
    } else {
        $mimeType = $file['type'];
        setHTTPStatus("403", "Forbidden mimetype: $mimeType");
    }
}

?>
