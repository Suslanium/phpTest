<?php
include_once 'routers/user/user_helper.php';

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

    $id = getUserIDFromBearerToken();
    if (!$id) {
        setHTTPStatus("401", "Authentication is required to view user profile");
        return;
    }
    $res = $Link->query("SELECT * FROM users WHERE id='$id'");

    if (!$res) {
        setHTTPStatus("500", "Failed to execute DB query to find user");
        return;
    }

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

function createUser($requestData)
{
    global $Link;

    $login = $requestData->body->login;
    $name = $requestData->body->name;

    if (is_null($login) || is_null($name) || is_null($requestData->body->password)) {
        setHTTPStatus("403", "Name/login and/or password were not specified");
        return;
    }

    $dataIsValid = true;
    $validationMessages = [];

    if (!validatePassword($requestData->body->password)) {
        $dataIsValid = false;
        $validationMessages['password'] = "Password should be at least 8 characters long and contain at least one lowercase letter, one uppercase letter, one number and one special symbol";
    }

    if (!validateStringLength($login, 4)) {
        $dataIsValid = false;
        $validationMessages['login'] = "Login should be at least 4 characters long";
    }

    if (!validateStringLength($name, 2)) {
        $dataIsValid = false;
        $validationMessages['name'] = "Name should be at least 2 characters long";
    }

    if (!$dataIsValid) {
        $message = "";
        foreach ($validationMessages as $key => $value) {
            $message .= "$key: $value \r\n";
        }
        setHTTPStatus("403", $message);
        return;
    }

    $password = hash("sha256", $requestData->body->password);
    $userInsertResult = $Link->query("INSERT INTO users(name, login, password) VALUES('$name', '$login', '$password')");

    if (!$userInsertResult) {
        if ($Link->errno == 1062) {
            setHTTPStatus("409", "User with login '$login' already exists");
            return;
        }
        setHTTPStatus("500", "Unable to insert user into the database");
        return;
    }

    setHTTPStatus("200", "Success");
}

?>
