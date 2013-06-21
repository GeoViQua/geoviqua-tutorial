<?php

$producer_doc = '';
$filename = '';
$response = array('error' => '');

if ($_FILES['metadata']['size'] > 0) {

  if ($_FILES['metadata']['error'] === UPLOAD_ERR_OK) {

    $producer_doc = file_get_contents($_FILES['metadata']['tmp_name']);
    $path_parts = pathinfo($_FILES['metadata']['name']);
    $filename = $path_parts['filename'];
  }
  else {

    $response['error'] = 'Error uploading document.';
  }
}
else if ($_POST['metadata_url']) {

  $producer_url = urldecode($_POST['metadata_url']);

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_URL, $producer_url); // get the url contents
  $producer_doc = curl_exec($ch); // execute curl request

  if (curl_errno($ch)) {
    
    $response['error'] = 'Error when requesting metadata document: ' . curl_error($ch);
  }
  else {
    
    $path_parts = pathinfo($producer_url);
    $filename = $path_parts['filename'];
  }
}

if ($producer_doc != '' && $response['error'] == '') {

  // create a DOM document and load the XML data
  $xml_doc = new DOMDocument();
  $xml_doc->formatOutput = true;
  $xml_doc->loadXML($producer_doc);

  // change the namespaces to GVQ compliant ones
  modifyNamespaces($xml_doc);

  $xp = new XSLTProcessor();
  // create a DOM document and load the XSL stylesheet
  $xsl = new DOMDocument();
  $xsl->load('19139-to-gvq.xsl');

  // import the XSL styelsheet into the XSLT process
  $xp->importStylesheet($xsl);

  // transform using the XSL file
  if ($xml = $xp->transformToXML($xml_doc)) {
      
      $transformed = new DOMDocument();
      $transformed->loadXML($xml);

      // XSLT 1.0 unsupported copy-namespaces workaround
      modifyNamespaces($transformed);

      if ($_GET['format'] != 'json' && $_POST['download'] == 'xml') {

        // send the XML back to the browser as a file
        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="GVQ_TRANSFORMED_' . $filename . '.xml"');
        die($transformed->saveXML());
      }
      else {

        $response['original_doc'] = $xml_doc->saveXML();
        $response['transformed_doc'] = $transformed->saveXML();
      }
  } 
  else {

      // try to catch the error thrown by XSLTProcessor
      $error = error_get_last();
      $response['error'] = 'XSL transformation failed:' . $error['message'];
  } // if
}
else {
  
  // user didn't provide a document
  $response['error'] = "Please provide a metadata document either by uploading a local document or entering a URL.";
}

if ($_GET['format'] == 'json') {

  die(json_encode($response));
}
else {

    die($response['error']);
}

function modifyNamespaces($domDocument) {

  // Modify document namespaces
  $xmlRoot = $domDocument->documentElement;
  $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:gvq', 'http://www.geoviqua.org/QualityInformationModel/4.0');
  $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:updated19115', 'http://www.geoviqua.org/19115_updates');
  $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:gmx', 'http://www.isotc211.org/2005/gmx');
  $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xlink', 'http://www.w3.org/1999/xlink');
  $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
  $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:gmd', 'http://www.isotc211.org/2005/gmd');
  $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:gco', 'http://www.isotc211.org/2005/gco');
  $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:gml', 'http://www.opengis.net/gml/3.2');
  $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:gmd19157', 'http://www.geoviqua.org/gmd19157');
  $xmlRoot->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:un', 'http://www.uncertml.org/2.0');
  $xmlRoot->setAttribute('xsi:schemaLocation', 'http://www.isotc211.org/2005/gmx http://schemas.opengis.net/iso/19139/20070417/gmx/gmx.xsd http://www.geoviqua.org/QualityInformationModel/4.0 http://schemas.geoviqua.org/GVQ/4.0/GeoViQua_PQM_UQM.xsd http://www.uncertml.org/2.0 http://www.uncertml.org/uncertml.xsd');
  $xmlRoot->setAttribute('id', 'dataset_MD');
}

?>