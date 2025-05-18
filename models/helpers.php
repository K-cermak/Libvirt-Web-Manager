<?php
    function checkLogged($shouldBeLogged) {
        if (!isset($_SESSION["logged"]) && $shouldBeLogged) {
            header("Location: " . getAppEnvVar("BASE_URL") . "/");
            die();
        } elseif (isset($_SESSION["logged"]) && !$shouldBeLogged) {
            header("Location: " . getAppEnvVar("BASE_URL") . "/dashboard");
            die();
        }
    }

    function checkConnected() {
        return isset($_SESSION["connectionUri"]);
    }

    function checkConnectedWithDie() {
        if (!checkConnected()) {
            http_response_code(400);
            resourceView([
                "code" => 400,
                "message" => "Not connected",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }
    }

    function getConnectionUri() {
        return isset($_SESSION["connectionUri"]) ? $_SESSION["connectionUri"] : "";
    }

    function setConnectionUri($uri) {
        $_SESSION["connectionUri"] = $uri;
    }
?>