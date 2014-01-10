<?php

$is_cors_request = array_key_exists("cors", $_GET);

if ($is_cors_request) {

    // enable cross-origin resource sharing
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

// name and path of the configuration file for this script
$config_file = dirname(__FILE__) . "/app/config/config.ini";

// check that the configuration file is readable
if (file_exists($config_file) && is_readable($config_file)) {

    $config = parse_ini_file($config_file);

    // cross-domain request made by the schema plugin to call the GEO label service
    if ($is_cors_request) {

        if (isset($_POST["metadata"])) {

            $metadata = trim($_POST["metadata"]);
            $size = trim($_POST["size"]);

            $svg = call_geolabel_service($config["geolabel_endpoint"], array(
                "metadata" => $metadata,
                "size" => $size
            ), "POST");

            // job done, send a success response
            send_response(array(
                "status" => "success",
                "data"  => array(
                    "label_svg" => $svg
                )
            ));
        }
    }
    // usual request made by the tutorial
    else {

        // validate user input
        $validation_error = "";
        $geonetwork_id = trim($_POST["geonetwork_id"]);
        $target_code = trim($_POST["target_code"]);
        $target_codespace = trim($_POST["target_codespace"]);

        if ($geonetwork_id === "") {

            $validation_error = "Please enter the GeoNetwork ID of the published document";
        }
        else if (!is_numeric($geonetwork_id)) {

            $validation_error = "Invalid ID provided: IDs must be numeric";
        }

        if ($validation_error !== "") {

            send_response(array(
                "status" => "error",
                "message" => $validation_error
            ));
        }

        // fetch the XML document from GeoNetwork
        $metadata_url = $config["geonetwork_baseURL"] . "xml_geoviqua?id=" . urlencode($geonetwork_id) . "&styleSheet=xml_iso19139.geoviqua.xsl";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $metadata_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        // something went wrong
        if (curl_errno($ch)) {

            send_response(array(
                "status" => "error",
                "message" => curl_error($ch)
            ));
        }
        else {

            // check that the output is an XML document
            $xml = new DOMDocument();
            $previous_errors = libxml_use_internal_errors(true);
            $valid = $xml->loadXML($output);
            libxml_clear_errors();
            libxml_use_internal_errors($previous_errors);

            if ($valid === false || strpos($output, "<h2>Privileges Error</h2>") !== false) {

                send_response(array(
                    "status" => "error",
                    "message" => 'The <a href="' . $metadata_url . '" target="_blank">requested metadata document</a> for ID <strong>' . $geonetwork_id . '</strong> could not be found or is malformed'
                ));
            }
            else {

                $svg = call_geolabel_service($config["geolabel_endpoint"], array(
                    "metadata" => $metadata_url,
                    "feedback" => $config["feedback_endpoint"] . '/collections?format=xml&target_code=' . $target_code . '&target_codespace=' . $target_codespace
                ));

                // job done, send a success response
                send_response(array(
                    "status" => "success",
                    "data"  => array(
                        "id" => $geonetwork_id,
                        "label_svg" => $svg
                    )
                ));
            }
        }
    }
}
else {

    send_response(array(
        "status" => "error",
        "message" => "Could not load configuration file $config_file"
    ));
}

function call_geolabel_service($url, $data, $method = "GET") {

    // build the curl request
    $ch = curl_init();
    switch ($method) {
        case "GET":
            curl_setopt($ch, CURLOPT_URL, $url . "?" . http_build_query($data));
            break;
        case "POST":
            // create a tmp file containing the XML for upload
            $file = tempnam(sys_get_temp_dir(), "geolabel_POST_");
            file_put_contents($file, $data["metadata"]);
            $data["metadata"] = "@".$file;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            break;
    }

    // request a GEO label
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);

    // remove the tmp file
    if ($method == "POST") {
        unlink($file);
    }

    // something went wrong
    if (curl_errno($ch)) {

        send_response(array(
            "status" => "error",
            "message" => curl_error($ch)
        ));
    }
    else {

        // check that the output is valid XML
        $svg = new DOMDocument();
        $previous_errors = libxml_use_internal_errors(true);
        $valid = $svg->loadXML($output);
        libxml_clear_errors();
        libxml_use_internal_errors($previous_errors);

        if ($valid === false) {

            send_response(array(
                "status" => "error",
                "message" => "Unable to parse response (" . htmlentities($output) . ")"
            ));
        }
        else {

            return $output;
        }
    }
}

function send_response($response) {

    if ($response["status"] === "error") {

        header("HTTP/1.1 500 Internal Server Error");
    }

    // not an ajax request
    if(empty($_SERVER["HTTP_X_REQUESTED_WITH"]) || strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) !== "xmlhttprequest") {

        if ($response["status"] === "error") {

            die($response["message"]);
        }
        else {

            die($response["data"]["label_svg"]);
        }
    }
    else {

        header("Content-type: application/json");
        die(json_encode($response));
    }
}

?>