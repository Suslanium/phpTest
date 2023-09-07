<?php
function route($requestMethod, $requestPath, $requestData)
{
    switch ($requestMethod) {
        case 'GET':
            getUser();
            break;
        case 'POST':
            createUser($requestData);
            break;
        default:
            setHTTPStatus("400", "Only GET and POST requests are allowed here");
            break;
    }
}

function getUser()
{
    global $Link;

    $token = substr(getallheaders()['Authorization'], 7);
    $userFromToken = $Link->query("SELECT userID FROM tokens WHERE value='$token'")->fetch_assoc();

    if (!is_null($userFromToken)) {

        $id = $userFromToken['userID'];
        $res = $Link->query("SELECT * FROM users WHERE id='$id'");

        if (!$res) {
            setHTTPStatus("500", "Failed to execute DB query to find user");
        } else {

            if ($row = $res->fetch_assoc()) {
                $message = [];
                $message['user'] = [
                    'id' => $row['id'],
                    'login' => $row['login'],
                    'name' => $row['name']
                ];
                echo json_encode($message);
            } else {
                setHTTPStatus("404", "User with specified ID was not found");
            }
        }
    } else {
        setHTTPStatus("401");
    }
}

function createUser($requestData)
{
    global $Link;

    $login = $requestData->body->login;
    $user = $Link->query("SELECT id FROM users WHERE login='$login'")->fetch_assoc();

    if (is_null($user)) {

        $password = hash("sha256", $requestData->body->password);
        $name = $requestData->body->name;
        $userInsertResult = $Link->query("INSERT INTO users(name, login, password) VALUES('$name', '$login', '$password')");

        if (!$userInsertResult) {
            setHTTPStatus("500", "Unable to insert user into the database");
        } else {
            setHTTPStatus("200", "Success");
        }
    } else {
        setHTTPStatus("403", "User already exists");
    }
}

?>
