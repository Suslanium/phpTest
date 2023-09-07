<?php
function route($requestMethod, $requestPath, $requestData)
{
    if ($requestMethod == "POST") {
        switch ($requestPath[1]) {
            case "login":
                login($requestData);
                break;
            case "logout":
                break;
            default:
                setHTTPStatus("404", "Specified path does not exist");
                break;
        }
    } else {
        setHTTPStatus("400", "Only POST requests are allowed here");
    }
}

function login($requestData)
{
    global $Link;

    $login = $requestData->body->login;
    $password = hash("sha256", $requestData->body->password);
    $user = $Link->query("SELECT id FROM users WHERE login='$login' AND password='$password'")->fetch_assoc();

    if (!is_null($user)) {

        $token = bin2hex(random_bytes(16));
        $userID = $user['id'];
        $tokenInsertResult = $Link->query("INSERT INTO tokens(value, userID) VALUES('$token', '$userID')");

        if (!$tokenInsertResult) {
            setHTTPStatus("500", "Unable to login");
        } else {
            echo json_encode([
                'accessToken' => $token
            ]);
        }
    } else {
        setHTTPStatus("401", "Incorrect login and/or password");
    }
}

?>
