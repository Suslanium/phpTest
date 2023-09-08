<?php
include_once 'helpers/headers.php';
include_once 'helpers/validation.php';

global $Link;

function getRequestMethod()
{
    return $_SERVER['REQUEST_METHOD'];
}

function getRequestPath()
{
    $rawPath = $_GET['q'] ?? '';
    $rawPath = rtrim($rawPath, '/');
    return explode('/', $rawPath);
}

function getData($requestMethod): stdClass
{
    $data = new stdClass();
    if ($requestMethod != "GET") {
        $data->body = json_decode(file_get_contents('php://input'));
    }
    $data->parameters = [];
    foreach ($_GET as $key => $value) {
        if ($key != "q") {
            $data->parameters[$key] = $value;
        }
    }
    return $data;
}

header('Content-type: application/json');

$Link = mysqli_connect("127.0.0.1", "backend_demo", "testpassword", "backend_demo");
if (!$Link) {
    setHTTPStatus("500", "Unable to connect to SQL database");
    exit;
}

$requestPath = getRequestPath();
$requestMethod = getRequestMethod();
$requestData = getData($requestMethod);
$routerName = $requestPath[0];
if (file_exists(realpath(dirname(__FILE__)) . '/routers/' . $routerName . '.php')) {
    include_once 'routers/' . $routerName . '.php';
    route($requestMethod, $requestPath, $requestData);
} else {
    setHTTPStatus("404", "Router not found");
}

mysqli_close($Link);
return;
?>