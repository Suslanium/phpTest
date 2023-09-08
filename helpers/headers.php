<?php
function setHTTPStatus($status = '200', $message = null)
{
    switch ($status) {
        default:
        case "200":
            $status = 'HTTP/1.0 200 OK';
            break;
        case "400":
            $status = 'HTTP/1.0 400 Bad Request';
            break;
        case "401":
            $status = 'HTTP/1.0 401 Unauthorized';
            break;
        case "403":
            $status = 'HTTP/1.0 403 Forbidden';
            break;
        case "404":
            $status = 'HTTP/1.0 404 Not found';
            break;
        case "409":
            $status = 'HTTP/1.0 409 Conflict';
            break;
        case "500":
            $status = 'HTTP/1.0 500 Internal Server Error';
            break;
    }
    header($status);
    if (!is_null($message)) {
        echo json_encode([
            'message' => $message
        ]);
    }
}

function getUserIDFromBearerToken()
{
    global $Link;
    //Removing Bearer part
    $token = substr(getallheaders()['Authorization'], 7);
    $userFromToken = $Link->query("SELECT userID FROM tokens WHERE value='$token'")->fetch_assoc();

    if (is_null($userFromToken)) {
        return false;
    }

    return $userFromToken['userID'];
}

?>
