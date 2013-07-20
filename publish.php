<?php

session_start();

// name and path of the configuration file for this script
$config_file = "config.ini";

// check that the configuration file is readable
if (file_exists($config_file) && is_readable($config_file)) {

	$config = parse_ini_file($config_file);
}
else {

    $json = array(
        "status" => "error",
        "message" => "Could not load configuration file $config_file"
    );

    die(json_encode($json));
}

// metadata document to be published
$gvq_doc = "";

if ($_FILES["publish-metadata"]["size"] > 0 && $_FILES["publish-metadata"]["error"] === UPLOAD_ERR_OK) {

    // read the contents of the uploaded file
    $gvq_doc = file_get_contents($_FILES["publish-metadata"]["tmp_name"]);

    // simple check to see if it's a well-formed XML document, suppressing warnings
    libxml_use_internal_errors(true);
    $valid = simplexml_load_string($gvq_doc);
    libxml_clear_errors();

    if ($valid === false) {

        $json = array(
            "status" => "error",
            "message" => "The XML document could not be parsed, please check that the XML is well-formed."
        );

        die(json_encode($json));
    }
}
else {

    $path_parts = pathinfo($_FILES['publish-metadata']['name']);
    $filename = $path_parts['filename'];
    $message = "";

    // something went wrong
    switch($_FILES["publish-metadata"]["error"]) {

        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $message = "$filename must be smaller than " . ini_get("upload_max_filesize") . " in size.";
            break;
        case UPLOAD_ERR_PARTIAL:
            $message = "$filename was only partially uploaded.";
            break;
        case UPLOAD_ERR_NO_FILE:
            $message = "No file was selected for upload.";
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
        case UPLOAD_ERR_EXTENSION:
            $message = "Unable to write $filename to disk.";
            break;
        default:
            $message = "Please select your transformed metadata document.";
            break;
    }

    $json = array(
        "status" => "error",
        "message" => $message
    );

    die(json_encode($json));
}

if (authenticateWithGeoNetwork($config["editor_username"], $config["editor_password"])) {

    // create a metadata insert request
    $xml = new DOMDocument("1.0");
    $xml->formatOutput = true;
    $root_node = $xml->createElement("request");
    $xml->appendChild($root_node);
    // group element
    $group_node = $xml->createElement("group");
    $group_node_value = $xml->createTextNode("1"); // all
    $group_node->appendChild($group_node_value);
    // category element
    $category_node = $xml->createElement("category");
    $category_node_value = $xml->createTextNode("_none_");
    $category_node->appendChild($category_node_value);
    // stylesheet element
    $stylesheet_node = $xml->createElement("stylesheet");
    $stylesheet_node_value = $xml->createTextNode("_none_");
    $stylesheet_node->appendChild($stylesheet_node_value);
    // data element
    $data_node = $xml->createElement("data");
    $data_node_value = $xml->createCDATASection($gvq_doc); // the uploaded document embedded as CDATA
    $data_node->appendChild($data_node_value);
    // append child elements to root
    $root_node->appendChild($group_node);
    $root_node->appendChild($category_node);
    $root_node->appendChild($stylesheet_node);
    $root_node->appendChild($data_node);
    // save XML as string
    $xml_request = $xml->saveXML();

    // send the request to GeoNetwork
    $response = sendXMLRequest("xml.metadata.insert", $xml_request);

    if (!errorCheck($response["body"]) &&
        authenticateWithGeoNetwork($config["admin_username"], $config["admin_password"])) {

        // parse the reponse for the metadata's catalog id and uuid
        $parsed = new SimpleXMLElement($response["body"]);

        // create a privileges request
        $xml = new DOMDocument("1.0");
        $xml->formatOutput = true;
        $root_node = $xml->createElement("request");
        $xml->appendChild($root_node);
        // id element
        $id_node = $xml->createElement("id");
        $id_node_value = $xml->createTextNode($parsed->id);
        $id_node->appendChild($id_node_value);
        // append privilege elements to root
        $root_node->appendChild($xml->createElement("_1_0")); // group 1 (all) can view
        $root_node->appendChild($xml->createElement("_1_1")); // group 1 (all) can download
        $root_node->appendChild($xml->createElement("_" . $config["editor_group_id"] . "_2")); // editor's user group can edit
        // append all other child elements to root
        $root_node->appendChild($id_node);
        // save XML as string
        $xml_request = $xml->saveXML();

        // send the request to GeoNetwork
        $response = sendXMLRequest("xml.metadata.privileges", $xml_request);

        if (!errorCheck($response["body"])) {

            // parse the reponse for the metadata's catalog id
            $parsed = new SimpleXMLElement($response["body"]);

            // job done, send a success response
            $json = array(
                "status" => "success",
                "data"  => array(
                    "geonetwork" => array(
                        "username" => $config["editor_username"],
                        "password" => $config["editor_password"],
                        "url" => $config["geonetwork_baseURL"],
                        "metadata_id" => (int) $parsed->id
                    )
                )
            );

            die(json_encode($json));
        }
    }
}

/**
* Creates and sends a login request to the configured GeoNetwork instance,
* using the provided username and password.
* If a successful authentication has previously occurred during the session
* a logout request will first be made to invalidate that token.
*/
function authenticateWithGeoNetwork($username, $password) {

    // :(
    global $config;

    if (isset($_SESSION["auth_cookie"])) {

        // log out first
        $xml = new DOMDocument("1.0");
        $xml->formatOutput = true;
        $root_node = $xml->createElement("request");
        $xml->appendChild($root_node);
        $xml_request = $xml->saveXML();

        $response = sendXMLRequest("xml.user.logout", $xml_request);

        if (!errorCheck($response["body"])) {

            unset($_SESSION["auth_cookie"]);
        }
    }

    // create a login request
    $xml = new DOMDocument("1.0");
    $xml->formatOutput = true;
    $root_node = $xml->createElement("request");
    $xml->appendChild($root_node);
    // username element
    $username_node = $xml->createElement("username");
    $username_node_value = $xml->createTextNode($config["admin_username"]);
    $username_node->appendChild($username_node_value);
    // password element
    $password_node = $xml->createElement("password");
    $password_node_value = $xml->createTextNode($config["admin_password"]);
    $password_node->appendChild($password_node_value);
    // append child elements to root
    $root_node->appendChild($username_node);
    $root_node->appendChild($password_node);
    // save XML as string
    $xml_request = $xml->saveXML();

    // send the request to GeoNetwork
    $response = sendXMLRequest("xml.user.login", $xml_request);

    if (!errorCheck($response["body"])) {

        // we're authenticated, keep for future requests
        $_SESSION["auth_cookie"] = $response["header"]["Set-Cookie"];
    }

    return true;
}


/**
* Helper method to send an XML request to GeoNetwork. The base URL is defined
* in config.ini and the required XML service is then passed as a parameter,
* forming a valid endpoint for the targeted GeoNetwork instance.
* Some requests require authentication, in which case a cookie should be provided
* via a session variable.
*/
function sendXMLRequest($xml_service, $xml_request) {

    // :(
    global $config;

    // start off with an empty response
    $response = array("header" => false, "body" => false);

    // configure cURL to request the desired XML service
    $ch = curl_init($config["geonetwork_baseURL"] . $xml_service);

    // POST with correct content type & length, returns HTTP header and response as a string
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/xml",
        "Content-Length: " . strlen($xml_request))
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_request);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // provide authentication if we have it
    if (isset($_SESSION["auth_cookie"])) {

        curl_setopt($ch, CURLOPT_COOKIE, $_SESSION["auth_cookie"]);
    }

    // send the request
    $output = curl_exec($ch);

    if (curl_errno($ch)) {

        // emulate a GeoNetwork error response and provide cURL's error
        $response["body"] = '<error id="curl"><message>' . curl_error($ch) . '</message></error>';
    }
    else {

        // separate the head from the body
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header_text = substr($output, 0, $header_size);
        $response["body"] = substr($output, $header_size);

        // explode the header into an array
        foreach (explode("\r\n", $header_text) as $i => $line) {

            if ($i === 0) {

                $response["header"]["HTTP_Code"] = $line;
            }
            else {

                list($key, $val) = explode(": ", $line);
                $response["header"][$key] = $val;
            }
        }
    }

    return $response;
}

/**
* Checks the response body for a standard GeoNetwork error structure [1].
* If the response is an error, encode the relevant information as a JSON response and terminate the script.
*
* [1] http://www.geonetwork-opensource.org/manuals/2.8.0/eng/developer/xml_services/services_calling.html
*/
function errorCheck($response) {

    $xml = new DOMDocument("1.0");
    $xml->loadXML($response);
    $errors = $xml->getElementsByTagName("error");

    if (!empty($errors)) {

        foreach ($errors as $error) {

            $error_message = $error->getElementsByTagName("message")->item(0)->nodeValue;
            $json = array(
                "status" => "error",
                "message" => $error_message,
                "data" => array(
                    "id" => $error->getAttribute('id'),
                    "request" => $error->getElementsByTagName("object")->item(0)->nodeValue
                )
            );

            die(json_encode($json));
        }
    }

    return false;
}

?>