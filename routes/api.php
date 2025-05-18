<?php
    checkRoute("GET", "/api/list", function() {
        modelCall("helpers", "checkLogged", [true]);
        modelCall("helpers", "checkConnectedWithDie");

        $connectionUri = modelCall("helpers", "getConnectionUri");
        $data = modelCall("libvirt", "getList", [$connectionUri]);

        if ($data === null) {
            http_response_code(400);
            resourceView([
                "code" => 400,
                "message" => "Error getting data",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }

        http_response_code(200);
        resourceView([
            "code" => 200,
            "message" => "Data retrieved",
            "time" => date("Y-m-d H:i:s"),
            "data" => $data
        ], "json");
    });

    checkRoute("GET", "/api/disks", function() {       
        modelCall("helpers", "checkLogged", [true]);
        modelCall("helpers", "checkConnectedWithDie");

        $connectionUri = modelCall("helpers", "getConnectionUri");
        $domain = $_GET["domain"] ?? null;
        if ($domain === null) {
            http_response_code(400);
            resourceView([
                "code" => 400,
                "message" => "Missing parameters",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }


        $data = modelCall("libvirt", "getDisks", [$connectionUri, $domain]);
        if ($data === null) {
            http_response_code(400);
            resourceView([
                "code" => 400,
                "message" => "Error getting data",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }

        http_response_code(200);
        resourceView([
            "code" => 200,
            "message" => "Data retrieved",
            "time" => date("Y-m-d H:i:s"),
            "data" => $data
        ], "json");
    });

    checkRoute("POST", "/api/disks", function() {
        $_POST = json_decode(file_get_contents("php://input"), true);

        modelCall("helpers", "checkLogged", [true]);
        modelCall("helpers", "checkConnectedWithDie");

        $connectionUri = modelCall("helpers", "getConnectionUri");
        $domain = $_POST["domain"] ?? null;
        $drivePath = $_POST["drivePath"] ?? null;
        $driveName = $_POST["driveName"] ?? null;
        $driveType = $_POST["driveType"] ?? null;

        if ($domain === null || $drivePath === null || $driveName === null || $driveType === null) {
            http_response_code(400);
            resourceView([
                "code" => 400,
                "message" => "Missing parameters",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }

        $data = modelCall("libvirt", "plugDisk", [$connectionUri, $domain, $drivePath, $driveName, $driveType]);
        if ($data === null) {
            http_response_code(400);
            resourceView([
                "code" => 400,
                "message" => "Error getting data",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }

        http_response_code(200);
        resourceView([
            "code" => 200,
            "message" => "Data retrieved",
            "time" => date("Y-m-d H:i:s"),
        ], "json");
    });

    checkRoute("DELETE", "/api/disks", function() {
        modelCall("helpers", "checkLogged", [true]);
        modelCall("helpers", "checkConnectedWithDie");

        $connectionUri = modelCall("helpers", "getConnectionUri");
        $domain = $_GET["domain"] ?? null;
        if ($domain === null) {
            http_response_code(400);
            resourceView([
                "code" => 400,
                "message" => "Missing parameters",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }

        $disk = $_GET["disk"] ?? null;
        if ($disk === null) {
            http_response_code(400);
            resourceView([
                "code" => 400,
                "message" => "Missing parameters",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }

        $data = modelCall("libvirt", "unplugDisk", [$connectionUri, $domain, $disk]);
        if ($data === null) {
            http_response_code(400);
            resourceView([
                "code" => 400,
                "message" => "Error getting data",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }

        http_response_code(200);
    });

    checkRoute("POST", "/api/action", function() {
        $_POST = json_decode(file_get_contents("php://input"), true);

        modelCall("helpers", "checkLogged", [true]);
        modelCall("helpers", "checkConnectedWithDie");

        $connectionUri = modelCall("helpers", "getConnectionUri");
        if (!isset($_POST["action"]) && !isset($_POST["domain"])) {
            http_response_code(400);
            resourceView([
                "code" => 400,
                "message" => "Missing parameters",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }

        $action = $_POST["action"];
        $domain = $_POST["domain"];

        $validActions = ["start", "shutdown", "suspend", "resume", "destroy"];
        if (!in_array($action, $validActions)) {
            http_response_code(400);
            resourceView([
                "code" => 400,
                "message" => "Invalid action",
                "time" => date("Y-m-d H:i:s")
            ], "json");
        }

        if ($action == "start") {
            $result = modelCall("libvirt", "startDomain", [$connectionUri, $domain]);
        } elseif ($action == "shutdown") {
            $result = modelCall("libvirt", "shutdownDomain", [$connectionUri, $domain]);
        } elseif ($action == "suspend") {
            $result = modelCall("libvirt", "suspendDomain", [$connectionUri, $domain]);
        } elseif ($action == "resume") {
            $result = modelCall("libvirt", "resumeDomain", [$connectionUri, $domain]);
        } elseif ($action == "destroy") {
            $result = modelCall("libvirt", "destroyDomain", [$connectionUri, $domain]);
        }


        http_response_code(200);
        resourceView([
            "code" => 200,
            "message" => "Action performed",
            "time" => date("Y-m-d H:i:s"),
        ], "json");
    });
?>