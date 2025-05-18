<?php
    checkRoute("GET", "/", function() {
        modelCall("helpers", "checkLogged", [false]);

        $template = processTemplate("login", ["title" => "Login"]);
        finishRender($template);
    });

    checkRoute("GET", "/dashboard/logout", function() {
        modelCall("helpers", "checkLogged", [true]);

        session_destroy();
        header("Location: " . getAppEnvVar("BASE_URL") . "/");
        die();
    });

    checkRoute("POST", "/auth", function() {
        $_POST = json_decode(file_get_contents("php://input"), true);

        if (!isset($_POST["token"]) || $_POST["token"] != getAppEnvVar("ACCESS_TOKEN")) {
            http_response_code(403);
            resourceView([
                "code" => 403,
                "message" => "Invalid token",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }

        $_SESSION["logged"] = true;
        http_response_code(200);
        resourceView([
            "code" => 200,
            "message" => "Logged in",
            "time" => date("Y-m-d H:i:s")
        ], "json");
    });

    checkRoute("GET", "/dashboard", function() {
        modelCall("helpers", "checkLogged", [true]);
        if (!modelCall("helpers", "checkConnected")) {
            header("Location: " . getAppEnvVar("BASE_URL") . "/dashboard/connect");
            die();
        }

        $template = processTemplate("dashboard", ["title" => "Dashboard", "connectionUri" => modelCall("helpers", "getConnectionUri")]);
        finishRender($template);
    });

    checkRoute("GET", "/dashboard/connect", function() {
        modelCall("helpers", "checkLogged", [true]);

        $template = processTemplate("connect", ["title" => "Connect", "connectionUri" => modelCall("helpers", "getConnectionUri")]);
        finishRender($template);
    });

    checkRoute("POST", "/dashboard/connect", function() {
        $_POST = json_decode(file_get_contents("php://input"), true);
        modelCall("helpers", "checkLogged", [true]);

        if (!isset($_POST["connectionUri"]) || $_POST["connectionUri"] == "") {
            http_response_code(400);
            resourceView([
                "code" => 400,
                "message" => "Invalid URI",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }

        modelCall("helpers", "setConnectionUri", [$_POST["connectionUri"]]);
        http_response_code(200);
    });


?>