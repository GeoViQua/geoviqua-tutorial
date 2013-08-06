<?php

session_start();

// error tracking
$error = array();

// name and path of the configuration file for this script
$config_file = dirname(__FILE__) . "/app/config/config.ini";

// check that the configuration file is readable
if (file_exists($config_file) && is_readable($config_file)) {

	$config = parse_ini_file($config_file);

    // metadata document to be published
    $gvq_doc = new DOMDocument();

    if ($_FILES["publish-metadata"]["size"] > 0 && $_FILES["publish-metadata"]["error"] === UPLOAD_ERR_OK) {

        // read the contents of the uploaded file
        $xml = file_get_contents($_FILES["publish-metadata"]["tmp_name"]);

        // simple check to see if it's a well-formed XML document, suppressing warnings
        $previous_errors = libxml_use_internal_errors(true);
        $valid = $gvq_doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous_errors);

        if ($valid === false) {

            $error = array(
                "status" => "error",
                "message" => "The XML document could not be parsed, please check that the XML is well-formed."
            );
        }
        else {

            // begin the upload process
            if (authenticateWithGeoNetwork($config["editor_username"], $config["editor_password"])) {

                // track the number of iterations to prevent an infinite loop
                $upload_attempts = 0;

                // get the original UUID
                $xpath = new DOMXPath($gvq_doc);
                $uuid_query = $xpath->query('//gmd:fileIdentifier/gco:CharacterString');
                $original_uuid = $uuid_query->item(0)->nodeValue;

                do {

                    // try to upload the document
                    $response = uploadToGeoNetwork($gvq_doc->saveXML());
                    $error = errorCheck($response["body"]);
                    $uuid_violation = strpos($error['message'], 'Unique index or primary key violation: "CONSTRAINT_INDEX_1 ON PUBLIC.METADATA(UUID)";');

                    if ($uuid_violation !== false) {

                        // there was a UUID violation so we have to generate a new one
                        // http://stackoverflow.com/a/2040279/1983684
                        mt_srand(crc32(serialize(microtime(true))));
                        $new_uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            // 32 bits for "time_low"
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

                            // 16 bits for "time_mid"
                            mt_rand(0, 0xffff),

                            // 16 bits for "time_hi_and_version",
                            // four most significant bits holds version number 4
                            mt_rand(0, 0x0fff) | 0x4000,

                            // 16 bits, 8 bits for "clk_seq_hi_res",
                            // 8 bits for "clk_seq_low",
                            // two most significant bits holds zero and one for variant DCE1.1
                            mt_rand(0, 0x3fff) | 0x8000,

                            // 48 bits for "node"
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );

                        // update the UUID for the next upload attempt
                        $uuid_query->item(0)->nodeValue = $new_uuid;
                    }

                    $upload_attempts++;
                }
                while (($uuid_violation !== false) && ($upload_attempts < 5));

                if (empty($error) &&
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
                    $error = errorCheck($response["body"]);

                    if (empty($error)) {

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

        $error = array(
            "status" => "error",
            "message" => $message
        );
    }
}
else {

    $error = array(
        "status" => "error",
        "message" => "Could not load configuration file $config_file"
    );
}

// execution was not successful
if (!empty($error)) {

    header("HTTP/1.1 500 Internal Server Error");
    die(json_encode($error));
}

/**
* Creates and sends a login request to the configured GeoNetwork instance,
* using the provided username and password.
* If a successful authentication has previously occurred during the session
* a logout request will first be made to invalidate that token.
*/
function authenticateWithGeoNetwork($username, $password) {

    // :(
    global $config, $error;

    if (isset($_SESSION["auth_cookie"])) {

        // log out first
        $xml = new DOMDocument("1.0");
        $xml->formatOutput = true;
        $root_node = $xml->createElement("request");
        $xml->appendChild($root_node);
        $xml_request = $xml->saveXML();

        $response = sendXMLRequest("xml.user.logout", $xml_request);
        $error = errorCheck($response["body"]);

        if (empty($error)) {

            unset($_SESSION["auth_cookie"]);
        }
        else {

            return false;
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
    $error = errorCheck($response["body"]);

    if (empty($error)) {

        // we're authenticated, keep for future requests
        $_SESSION["auth_cookie"] = $response["header"]["Set-Cookie"];
    }
    else {

        return false;
    }

    return true;
}

/**
* Helper method to upload an XML document to GeoNetwork.
*/
function uploadToGeoNetwork($xml_doc) {

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
    $data_node_value = $xml->createCDATASection($xml_doc); // the uploaded document embedded as CDATA
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

    return $response;
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
    $return_error = array();

    if (!empty($errors)) {

        foreach ($errors as $error) {

            $error_message = $error->getElementsByTagName("message")->item(0)->nodeValue;
            $return_error = array(
                "status" => "error",
                "message" => $error_message,
                "data" => array(
                    "id" => $error->getAttribute('id'),
                    "request" => $error->getElementsByTagName("object")->item(0)->nodeValue
                )
            );
        }
    }

    return $return_error;
}

?>