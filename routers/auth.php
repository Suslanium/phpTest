<?php
function route($requestMethod, $requestPath, $requestData)
{
    if ($requestMethod != "POST") {
        setHTTPStatus("400", "Only POST requests are allowed here");
        return;
    }

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
}

function login($requestData)
{
    global $Link;

    $login = $requestData->body->login;

    if (is_null($login) || is_null($requestData->body->password)) {
        setHTTPStatus("403", "Login and/or password are not specified");
        return;
    }

    $password = hash("sha256", $requestData->body->password);
    $user = $Link->query("SELECT id FROM users WHERE login='$login' AND password='$password'")->fetch_assoc();

    if (is_null($user)) {
        setHTTPStatus("401", "Incorrect login and/or password");
        return;
    }

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
}

?>
