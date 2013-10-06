<?php

session_start();

// name and path of the configuration file for this script
$config_file = dirname(__FILE__) . "/app/config/config.ini";

// check that the configuration file is readable
if (file_exists($config_file) && is_readable($config_file)) {

  $config = parse_ini_file($config_file);
}

if (isset($_SESSION['response'])) {

  $valid = $_SESSION['response']['valid'];
  $errors = $_SESSION['response']['errors'];
  $fields = $_SESSION['response']['fields'];

  unset($_SESSION['response']);

  if (!$valid) {

      $submit_message = 'There were some problems with your submission.';

      if (isset($errors['locked_out'])) {

        $submit_message .= '<br />' . $errors['locked_out'];
      }

      $response_type = 'error';
  }
  else {

      $submit_message = 'Thank you! Your email has been submitted.';
      $response_type = 'success';
  }
}

?>

<!DOCTYPE html>
<html lang="en" id="top">
  <head>
    <meta charset="utf-8">
    <title>GeoViQua hands-on workshop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--[if lt IE 9]>
      <script src="js/html5.js"></script>
    <![endif]-->

    <link href="img/geoviqua_Tots_2.png" type="image/x-icon" rel="shortcut icon">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-responsive.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/bootswatch.css" rel="stylesheet">
    <link href="js/fancybox/jquery.fancybox.css" rel="stylesheet">
    <link href="js/video-js/video-js.min.css" rel="stylesheet">

    <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

      ga('create', 'UA-43014903-1', 'geoviqua.org');
      ga('send', 'pageview');

    </script>
  </head>

  <body class="preview">

  <!-- Navbar
    ================================================== -->
 <div class="navbar navbar-fixed-top">
   <div class="navbar-inner header">
     <div class="container">
       <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
         <span class="icon-bar"></span>
         <span class="icon-bar"></span>
         <span class="icon-bar"></span>
       </a>
       <div class="nav-collapse collapse" id="main-menu">
        <ul class="nav pull-right" id="main-menu-right">
          <li><a rel="tooltip" target="_blank" href="http://www.geoviqua.org/" onclick="trackOutbound(this, 'external', 10); return false;" title="QUAlity aware VIsualization for the Global Earth Observation System of systems">Learn more at GeoViQua.org <i class="icon-share-alt"></i></a></li>
        </ul>
       </div>
     </div>
   </div>
 </div>

  <div class="container">

    <div class="tab-content">


<!-- Masthead
================================================== -->
<header class="tab-pane active jumbotron subhead" id="overview">
  <div class="row">
    <div class="span12">
      <h2>GeoViQua Hands-On Workshop</h2>
      <p class="lead"><i class="icon-reply icon-2x icon-rotate-270 pull-left"></i>Click to begin transforming your own metadata with richer quality information.</p>
    </div>
  </div>
  <div class="tabbable subnav">
    <ul id="homeNav" class="nav nav-pills">
      <li><a href="#producer" data-toggle="pill">1. The Producer Quality Model</a></li>
      <li><a href="#feedback" data-toggle="pill">2. The User Feedback Model</a></li>
      <li><a href="#label" data-toggle="pill">3. The GEO label</a></li>
    </ul>
  </div>
  <div class="row hero-preview">
    <div class="span4" data-sibling="producer">
      <div class="img-polaroid">
        <img src="img/producer-hero.png" />
      </div>
    </div>
    <div class="span4" data-sibling="feedback">
      <div class="img-polaroid">
        <img src="img/user-hero.png" />
      </div>
    </div>
    <div class="span4" data-sibling="label">
      <div class="img-polaroid">
        <img src="img/label-hero.png" />
      </div>
    </div>
  </div>
  <div class="row">
    <div class="span7">
      <legend>About</legend>
      <blockquote>
        <p><i class="icon-quote-left icon-4x pull-left icon-muted"></i> GeoViQua provides a set of software components and services to facilitate creation, search and visualization of quality information on EO in the GEOSS Common Infrastructure.</p>
      </blockquote>
      <p>Our Producer Quality Model extends ISO 19115 and 19157 standards to allow traditional metadata documents to be supplemented with richer information such as citations, discovered issues, information about reference datasets and full statistical representations of uncertainty.</p>
      <p>Our User Quality Model permits users of datasets to submit ratings, comments, citations and assessments of those datasets to a Feedback server where their comments can be collated and combined with the more tradiational metadata to help other users assess the data's fitness for purpose.</p>
      <p>Our proposed GEO label presents a condensed visual summary of the producer and use metadata, allowing a quick assessment of the availability of data quality information on a dataset, as well as a drill-down feature so that users can query the quality information in more detail.</p>
    </div>
    <div class="span5">
      <form id="contact" class="form-horizontal" method="post" action="email.php">
        <?php

        $csrf_name = "csrf_" . mt_rand(0, mt_getrandmax());
        $csrf_token = md5(md5($csrf_name) . uniqid(rand(), true));

        if (isset($_SESSION)) {

          unset($_SESSION['csrf_token']);
          $_SESSION['csrf_token'][$csrf_name] = $csrf_token;
        }

        ?>
        <input type="hidden" name="csrf" value="<?php echo $csrf_name; ?>" />
        <input type="hidden" name="token" value="<?php echo $csrf_token ?>" />
        <legend>Contact Us</legend>
        <div class="<?php echo (isset($valid) ? 'alert alert-' . $response_type : 'hidden'); ?>">
          <?php if(isset($valid)) { echo $submit_message; } ?>
        </div>
        <fieldset>
          <div class="control-group stage clear">
            <label for="name" class="control-label"><strong>Name: <em>*</em></strong></label>
            <div class="controls">
              <input type="text" name="contactname" id="contactname" value="<?php echo $fields['contactname']; ?>" class="span3 required <?php if (isset($errors['contactname'])) { echo 'error'; } ?>" role="input" aria-required="true" />
              <?php if (isset($errors['contactname'])): ?><label class="error"><?php echo $errors['contactname']; ?></label><?php endif; ?>
            </div>
          </div>
          <div class="control-group stage clear">
            <label for="email" class="control-label"><strong>Email: <em>*</em></strong></label>
            <div class="controls">
              <input type="text" name="email" id="email" value="<?php echo $fields['email']; ?>" class="span3 required email <?php if (isset($errors['email'])) { echo 'error'; } ?>" role="input" aria-required="true" />
              <?php if (isset($errors['email'])): ?><label class="error"><?php echo $errors['email']; ?></label><?php endif; ?>
            </div>
          </div>
          <div class="control-group stage clear">
            <label for="subject" class="control-label"><strong>Subject: <em>*</em></strong></label>
            <div class="controls">
              <input type="text" name="subject" id="subject" value="<?php echo $fields['subject']; ?>" class="span3 required <?php if (isset($errors['subject'])) { echo 'error'; } ?>" role="input" aria-required="true" />
              <?php if (isset($errors['subject'])): ?><label class="error"><?php echo $errors['subject']; ?></label><?php endif; ?>
            </div>
          </div>
          <div class="control-group stage clear">
            <label for="message" class="control-label"><strong>Message: <em>*</em></strong></label>
            <div class="controls">
              <textarea rows="8" name="message" id="message" class="span3 required <?php if (isset($errors['message'])) { echo 'error'; } ?>" role="textbox" aria-required="true"><?php echo $fields['message']; ?></textarea>
              <?php if (isset($errors['message'])): ?><label class="error"><?php echo $errors['message']; ?></label><?php endif; ?>
            </div>
          </div>
          <input type="text" name="dob" id="dob" value="" role="input" />
          <p class="requiredNote"><em>*</em> Denotes a required field.</p>
          <div class="form-actions">
            <button class="btn btn-info submit" type="submit">Submit</button>
            <button class="btn" type="reset">Clear</button>
          </div>
        <fieldset>
      </form>
    </div>
  </div>
</header>




<!-- The Producer Quality Model
================================================== -->
<section class="tab-pane active" id="producer">
  <div class="tabbable subnav">
    <ul id="producerNav" class="nav nav-pills">
      <li class="home"><a data-toggle="pill" href="#overview"><i class="icon-home icon-large"></i></a></li>
      <li class="active"><a href="#producer" data-toggle="pill">1. The Producer Quality Model</a></li>
      <li><a href="#feedback" data-toggle="pill">2. The User Feedback Model</a></li>
      <li><a href="#label" data-toggle="pill">3. The GEO label</a></li>
    </ul>
  </div>
  <div class="page-header">
    <h3>The Producer Quality Model</h3>
  </div>

  <div class="row">
    <div class="span12">
      <div class="row">
        <div class="span8">
          <blockquote>
          <p><i class="icon-quote-left icon-4x pull-left icon-muted"></i> The GeoViQua quality model allows ISO 19115/19139 to be extended with richer quality information, reference to publications and datasets and documentation of discovered issues with a dataset. In this first exercise, we are going to take a typical ISO producer metadata document, and extend it in just this way.</p>
          </blockquote>
        </div>
        <div class="span4" style="position: relative;">
          <a href="#producer-player" class="video-preview" data-video="producer">
            <div class="play no-js">
              <span class="icon-stack icon-3x">
                <i class="icon-circle icon-stack-base"></i>
                <i class="icon-youtube-play icon-light"></i>
              </span>
            </div>
            <img src="img/producer-hero.png" class="img-polaroid">
            <div style="display:none">
              <video id="producer-player" class="video-js vjs-default-skin"
                width="853" height="480"
                data-setup='{ "controls": true, "autoplay": false, "preload": "auto" }'>
                <source src="resources/producer_video/video.mp4" type='video/mp4' />
                <source src="resources/producer_video/video.webm" type='video/webm' />
                <source src="resources/producer_video/video.ogv" type='video/ogg' />
              </video>
            </div>
          </a>
        </div>
      </div>
      <br />

      <h4 id="t1s1">Step 1: Find your document</h4>
      <p>
        We have supplied an  
        <a title="Example ISO19139 metadata document" href="http://schemas.geoviqua.org/GVQ/4.0/example_documents/19139/DigitalClimaticAtlas19139.xml" target="_blank">example metadata document <i class="icon-external-link" style="text-decoration: none;"></i></a>
        but you may have an example of your own which you would like to use. Locate your document and download it.
      </p>

      <br />

      <h4 id="t1s2">Step 2: Transform your document to the new schema</h4>
      <p>We have supplied an XSLT stylesheet which will allow you to transform a traditional document into a GeoViQua-compliant one.</p>

      <br />

      <div class="row">
        <div class="tabbable span12">
          <ul class="nav nav-tabs">
            <li id="transform-tab" class="active"><a data-toggle="tab" href="#tabs1-pane1">Transform metadata XML document</a></li>
            <li id="results-tab" class=""><a data-toggle="tab" href="#tabs1-pane2">View &amp; save result</a></li>
          </ul>
          <div class="tab-content">
            <div id="tabs1-pane1" class="tab-pane active">
              <form id="transform-form" class="form-horizontal" enctype="multipart/form-data" method="post" action="transform.php" target="_blank">
                <fieldset>
                  <div class="control-group">
                    <label for="metadata" class="control-label">Select metadata document:</label>
                    <div class="controls">
                      <input type="hidden" name="download" value="xml" />
                      <input type="file" name="metadata" class="input-file" id="metadata">
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label"><b>OR</b></label>
                  </div>
                  <div class="control-group">
                    <label for="metadata_url" class="control-label">Enter metadata URL location:</label>
                    <div class="controls">
                      <input type="text" name="metadata_url" placeholder="http://" class="input-xlarge" id="metadata_url">
                    </div>
                  </div>
                  <div class="form-actions">
                    <button class="btn btn-info submit" type="submit">Submit</button>
                    <button class="btn" type="reset">Clear</button>
                  </div>
                </fieldset>
              </form>
            </div>
            <div id="tabs1-pane2" class="tab-pane">
              <div class="alert alert-success">
                Your transformed document should now have been sent back to your browser, save it somewhere locally where you can find it later.
              </div>
            </div>
          </div><!-- /.tab-content -->
        </div><!-- /.tabbable -->
      </div>

      <h4 id="t1s3">Step 3: Publish the metadata using a catalogue</h4>
      <p>Metadata documents like this can be published in catalogues such as the GCI and retrieved using catalogue searches and brokering services. For the purposes of this demonstration, we will publish the metadata using the popular open-source Geonetwork software.</p>

      <br />

      <div class="row no-js">
        <div class="tabbable span12">
          <ul class="nav nav-tabs">
            <li id="publish-tab" class="active"><a data-toggle="tab" href="#tabs2-pane1">Publish to GeoNetwork</a></li>
            <li id="publish-results-tab" class=""><a data-toggle="tab" href="#tabs2-pane2">View result</a></li>
          </ul>
          <div class="tab-content">
            <div id="tabs2-pane1" class="tab-pane active">
              <form id="publish-form" class="form-horizontal" enctype="multipart/form-data" method="post" action="publish.php">
                <div class="alert alert-error" style="display: none;"></div>
                <fieldset>
                  <div class="control-group">
                    <label for="browse" class="control-label">Select metadata document:</label>
                    <div id="publish-upload-container" class="controls">
                      <button id="browse" class="btn" href="javascript:;">Browse...</button>
                      <span class="filename">No file selected.</span>
                      <noscript>
                        <input type="file" name="publish-metadata" class="input-file" id="publish-metadata">
                      </noscript>
                    </div>
                  </div>
                  <div class="form-actions">
                    <div class="loading"><img src="img/loading.gif" /></div>
                    <button class="btn btn-info submit" type="submit">Submit</button>
                    <button class="btn" type="reset">Clear</button>
                  </div>
                </fieldset>
              </form>
            </div>
            <div id="tabs2-pane2" class="tab-pane">
              <div class="alert alert-error">
                You have not yet published your transformed metadata document to GeoNetwork.
              </div>
              <div class="alert alert-success publish-steps">
                Your document has been successfully published to our GeoNetwork instance with the following ID: <strong id="publish-ID"></strong>
                <br />
                <a id="publish-URL" href="" target="_blank">View your published metadata document directly <i class="icon-external-link" style="text-decoration: none;"></i></a>
              </div>
              <div class="alert alert-block publish-steps">
                <strong>Important:</strong> Keep a note of the ID under which your metadata is published, as you will need this later!
              </div>

              <br />

              <div class="row publish-steps">
                <div class="span8">
                  <p>
                    <i class="icon-info-sign icon-large pull-left"></i>
                    In your browser, go to <a href="http://uncertdata.aston.ac.uk:8080/geonetwork" target="_blank">http://uncertdata.aston.ac.uk:8080/geonetwork <i class="icon-external-link" style="text-decoration: none;"></i></a> and log in with the following details:
                    <br />
                    <ul>
                      <li>Username: <strong id="publish-username"></strong></li>
                      <li>Password: <strong id="publish-password"></strong></li>
                    </ul>
                  </p>
                </div>
                <div class="span4">
                  <a class="fancy" href="img/tutorial/p8.png"><img src="img/tutorial/p8.png" class="img-polaroid" /></a>
                </div>
              </div>

              <br />

              <div class="row publish-steps">
                <div class="span8">
                  <p>
                    <i class="icon-info-sign icon-large pull-left"></i>
                    Use the Simple Search on the right-hand side to locate your metadata document and then click on its title in the results list.
                  </p>
                  <div class="alert alert-info">
                    Alternatively, you can edit the URL below, replacing &lsquo;<strong>xx</strong>&rsquo; with the GeoNetwork ID:
                    <br />
                    <a href="javascript:void(0)">http://uncertdata.aston.ac.uk:8080/geonetwork/srv/eng/metadata.show?id=<strong>xx</strong>&amp;currTab=simple</a>
                  </div>
                </div>
                <div class="span4">
                  <a class="fancy" href="img/tutorial/p4.png"><img src="img/tutorial/p4.png" class="img-polaroid" /></a>
                </div>
              </div>

            </div>
          </div><!-- /.tab-content -->
        </div><!-- /.tabbable -->
      </div>

      <!-- instructions if JS-enabled publish form isn't supported -->
      <noscript>
        <div class="row">
          <div class="span8">
            <p>
              <i class="icon-info-sign icon-large pull-left"></i>
              In your browser, go to <a href="http://uncertdata.aston.ac.uk:8080/geonetwork" target="_blank">http://uncertdata.aston.ac.uk:8080/geonetwork <i class="icon-external-link" style="text-decoration: none;"></i></a>
              and log in with the username <strong><?php echo $config['editor_username']; ?></strong> and password <strong><?php echo $config['editor_password']; ?></strong>. Go to the &lsquo;Administration&rsquo; panel and select &lsquo;Metadata insert&rsquo; to import your metadata.
            </p>
          </div>
          <div class="span4">
            <a class="fancy" href="img/tutorial/p1.png"><img src="img/tutorial/p1.png" class="img-polaroid" /></a>
          </div>
        </div>

        <br />
        <div class="row">
          <div class="span8">
            <p>
              <i class="icon-info-sign icon-large pull-left"></i>
              Browse for the file and add it by clicking &lsquo;Insert&rsquo;.
            </p>
            <div class="alert alert-block">
              <strong>Important:</strong> Make sure to specify that a new UUID should be generated.
            </div>
          </div>
          <div class="span4">
            <a class="fancy" href="img/tutorial/p2.png"><img src="img/tutorial/p2.png" class="img-polaroid" /></a>
          </div>
        </div>

        <br />
        <div class="row">
          <div class="span8">
            <p>
              <i class="icon-info-sign icon-large pull-left"></i>
              If the insert was successful you will see the ID that your metadata has been published under in the catalogue.
            </p>
            <div class="alert alert-block">
              <strong>Important:</strong> Keep a note of the ID under which your metadata is published, as you will need this later!
            </div>
          </div>
          <div class="span4">
            <a class="fancy" href="img/tutorial/p3.png"><img src="img/tutorial/p3.png" class="img-polaroid" /></a>
          </div>
        </div>

        <br />
        <div class="row">
          <div class="span8">
            <p>
              <i class="icon-info-sign icon-large pull-left"></i>
              If you now select &lsquo;Metadata&rsquo; you will be able to view the imported document. It contains some detailed data quality information, among other things.
            </p>
          </div>
          <div class="span4">
            <a class="fancy" href="img/tutorial/p4.png"><img src="img/tutorial/p4.png" class="img-polaroid" /></a>
          </div>
        </div>
      </noscript>

      <br />
      <h4 id="t1s4">Step 4: Add some information to your document</h4>

      <br />
      <div class="row">
        <div class="span8">
          <p>
            <i class="icon-info-sign icon-large pull-left"></i>
            The &lsquo;identification&rsquo; section of your metadata document in GeoNetwork should look similar to the example metadata document pictured to the right.
            The <strong>unique resource identifier</strong> highlighted above is the identifier <strong>code</strong> of the dataset. It is taken from the citation element, contained in the <strong>MD_DataIdentification</strong> part of the document.
          </p>
          <p>
            In GeoViQua, we propose to use the latest version of the 19115 <strong>MD_Identifier</strong> element, which also allows a <strong>codespace</strong> to be supplied which unambiguously defines the namespace for the identifier.
            The combination of code and codespace forms a unique identifier which will permit digital citations to datasets, opening up many opportunities for automated discovery and referencing of data.
          </p>
          <div class="alert alert-info">
            For more discussion of codespaces and their function, see <a href="https://geo-ide.noaa.gov/wiki/index.php?title=ISO_Identifiers" onclick="trackOutbound(this, 'external'); return false;" title="ISO Identifiers - NOAA Environmental Data Management Wiki" target="_blank">this NOAA EDM wiki article</a>.
          </div>
        </div>
        <div class="span4">
          <a class="fancy" href="img/tutorial/p9.png"><img src="img/tutorial/p9.png" class="img-polaroid" /></a>
        </div>
      </div>

      <div class="row">
        <div class="span8">
            <div class="alert alert-info">
              The data quality reports have all been restructured to comply with the new ISO 19157 standard.
            </div>
        </div>
        <div class="span4">
          <a class="fancy" href="img/tutorial/p6.png"><img src="img/tutorial/p6.png" class="img-polaroid" /></a>
        </div>
      </div>

      <br />
      <div class="row">
        <div class="span8">
          <p>
            <i class="icon-info-sign icon-large pull-left"></i>
            If you click &lsquo;edit&rsquo;, you can add a codespace to your dataset, pictured to the right.
          </p>
        </div>
        <div class="span4">
          <a class="fancy" href="img/tutorial/p10.png"><img src="img/tutorial/p10.png" class="img-polaroid" /></a>
        </div>
      </div>

      <br />
      <div class="row">
        <div class="span8">
          <p>
            <i class="icon-info-sign icon-large pull-left"></i>
            Click &lsquo;Save and Close&rsquo; and you will now see your codespace displayed in the metadata record.
          </p>
        </div>
        <div class="span4">
          <a class="fancy" href="img/tutorial/p11.png"><img src="img/tutorial/p11.png" class="img-polaroid" /></a>
        </div>
      </div>

      <br />
      <div class="row">
        <div class="span8">
          <p>
            <i class="icon-info-sign icon-large pull-left"></i>
            The edited document can be exported in XML format using the &lsquo;&lt;&gt;&rsquo; button top right.
          </p>
        </div>
        <div class="span4">
          <a class="fancy" href="img/tutorial/p12.png"><img src="img/tutorial/p12.png" class="img-polaroid" /></a>
        </div>
      </div>

      <br />
      <button class="btn btn-large btn-block btn-info btn-next no-js" type="button" data-next="#feedback">Continue to Part 2: The User Feedback Model</button>

    </div>
  </div>

</section>


<!-- The User Feedback Model
================================================== -->
<section class="tab-pane active" id="feedback">
  <div class="tabbable subnav">
    <ul id="feedbackNav" class="nav nav-pills">
      <li class="home"><a data-toggle="pill" href="#overview"><i class="icon-home icon-large"></i></a></li>
      <li><a href="#producer" data-toggle="pill">1. The Producer Quality Model</a></li>
      <li class="active"><a href="#feedback" data-toggle="pill">2. The User Feedback Model</a></li>
      <li><a href="#label" data-toggle="pill">3. The GEO label</a></li>
    </ul>
  </div>
  <div class="page-header">
    <h3>The User Feedback Model</h3>
  </div>

  <div class="row">
    <div class="span12">
      <div class="row">
        <div class="span8">
          <blockquote>
          <p><i class="icon-quote-left icon-4x pull-left icon-muted"></i> GeoViQua offers a feedback server where users may record ratings, comments, reports of usage and citations for any datasets which have a unique identifier. In this way, user information about data can be harvested from one or many feedback servers, and combined with the producer metadata to give an up-to-date record of usage and of quality as assessed by other domain experts.</p>
          </blockquote>
        </div>
        <div class="span4" style="position: relative;">
          <a href="#feedback-player" class="video-preview" data-video="feedback">
            <div class="play no-js">
              <span class="icon-stack icon-3x">
                <i class="icon-circle icon-stack-base"></i>
                <i class="icon-youtube-play icon-light"></i>
              </span>
            </div>
            <img src="img/user-hero.png" class="img-polaroid">
            <div style="display:none">
              <video id="feedback-player" class="video-js vjs-default-skin"
                width="853" height="480"
                data-setup='{ "controls": true, "autoplay": false, "preload": "auto" }'>
                <source src="resources/feedback_video/video.mp4" type='video/mp4' />
                <source src="resources/feedback_video/video.webm" type='video/webm' />
                <source src="resources/feedback_video/video.ogv" type='video/ogg' />
              </video>
            </div>
          </a>
        </div>
      </div>
      <br />

      <h4 id="t2s1">Step 1: Generate your dataset identifier</h4>
      <p>For the purposes of this demo, you can make up your own unique identifier – a combination of a codespace (e.g., &lsquo;<strong>lucy.bastin.org</strong>&rsquo;) and a code (e.g., &lsquo;<strong>dataset1</strong>&rsquo;). When you submit feedback using this combination, it will be submitted to the feedback server, from where it can be retrieved using these two fields.</p>
      <div class="alert alert-block">
        <strong>Important:</strong> Make a note of the code and codespace you decide on.
      </div>

      <br />

      <h4 id="t2s2">Step 2: Submit some feedback</h4>

      <p>
        Use the form below to visit the feedback server, entering the code and codespace that you decided on in step 1.
      </p>
      <form action="https://geoviqua.stcorp.nl/submit_feedback.html" method="get" class="well form-search feedback" target="_blank" data-stage="submit">
          <input name="target_code" class="span3" placeholder="Code" type="text" />
          <input name="target_codespace" class="span3" placeholder="Codespace" type="text" />
          <button type="submit" class="btn btn-info">Submit</button>
          <button class="btn" type="reset">Clear</button>
      </form>
      <div class="alert alert-info">
        Alternatively, you can manually edit the URL below, replacing &lsquo;<strong>xx</strong>&rsquo; with the code and &lsquo;<strong>yyy</strong>&rsquo; with the codespace you chose: 
        <br />
        <a href="javascript:void(0)">https://geoviqua.stcorp.nl/submit_feedback.html?target_code=<strong>xx</strong>&amp;target_codespace=<strong>yyy</strong></a>
      </div>

      <br />
      <div class="row">
        <div class="span8">
          <p>
            <i class="icon-info-sign icon-large pull-left"></i>
            Now you are creating a new feedback item. You can add ratings, information on why you chose that rating, etc.
            <br /><br />
            For &lsquo;domainURN&rsquo; you can choose a meaningful term from a thesaurus such as GEMET (<a href="http://www.eionet.europa.eu/gemet/" target="_blank">http://www.eionet.europa.eu/gemet/ <i class="icon-external-link" style="text-decoration: none;"></i></a>), or in this quick demo, you can just make one up! 
            <br /><br />
            In the example above, we chose <a href="http://www.eionet.europa.eu/gemet/concept/4118" target="_blank">http://www.eionet.europa.eu/gemet/concept/4118 <i class="icon-external-link" style="text-decoration: none;"></i></a> which maps to the &lsquo;water >hydrology&rsquo; concept. This can help others find feedback which is relevant to their specific field. Tags are also useful for this purpose.
          </p>
        </div>
        <div class="span4">
          <a class="fancy" href="img/tutorial/f1.png"><img src="img/tutorial/f1.png" class="img-polaroid" /></a>
        </div>
      </div>

      <br />
      <div class="row">
        <div class="span8">
          <p>
            <i class="icon-info-sign icon-large pull-left"></i>
            On the next page, you can specify the type of resource on which you are commenting. In this case, it will be &lsquo;dataset&rsquo;. These choices are based on ISO scope code lists.
          </p>
        </div>
        <div class="span4">
          <a class="fancy" href="img/tutorial/f2.png"><img src="img/tutorial/f2.png" class="img-polaroid" /></a>
        </div>
      </div>

      <br />
      <div class="row">
        <div class="span8">
          <p>
            <i class="icon-info-sign icon-large pull-left"></i>
            If you wish, you can submit information on ways in which you used the data, and whether you discovered any issues with it.
          </p>
        </div>
        <div class="span4">
          <a class="fancy" href="img/tutorial/f3.png"><img src="img/tutorial/f3.png" class="img-polaroid" /></a>
        </div>
      </div>

      <br />
      <div class="row">
        <div class="span8">
          <p>
            <i class="icon-info-sign icon-large pull-left"></i>
            When you submit the feedback, you'll be prompted for a username and password. The username is <strong>inspire_user</strong>, and the password is <strong>Insp1357</strong>.
          </p>
        </div>
        <div class="span4">
          <a class="fancy" href="img/tutorial/f4.png"><img src="img/tutorial/f4.png" class="img-polaroid" /></a>
        </div>
      </div>

      <br />
      <div class="row">
        <div class="span8">
          <p>
            <i class="icon-info-sign icon-large pull-left"></i>
            When the feedback is submitted, you'll see a link which leads to a summary of the feedback you submitted.
          </p>
        </div>
        <div class="span4">
          <a class="fancy" href="img/tutorial/f5.png"><img src="img/tutorial/f5.png" class="img-polaroid" /></a>
        </div>
      </div>

      <br />
      <h4 id="t2s3">Step 3: Search for your feedback to make sure it's there!</h4>

      <p>
        Use the form below to retrieve a summary of your feedback from the server, entering your code and codespace if it's not already there.
      </p>
      <form action="https://geoviqua.stcorp.nl/api/v1/feedback/items/search" method="get" class="well form-search feedback" target="_blank" data-stage="search">
          <input name="format" type="hidden" value="xml" />
          <input name="target_code" class="span3" placeholder="Code" type="text" />
          <input name="target_codespace" class="span3" placeholder="Codespace" type="text" />
          <button type="submit" class="btn btn-info">Submit</button>
          <button class="btn" type="reset">Clear</button>
      </form>
      <div class="alert alert-info">
        Alternatively, you can manually edit the URL below, replacing &lsquo;<strong>xx</strong>&rsquo; with your code and &lsquo;<strong>yyy</strong>&rsquo; with your codespace: 
        <br />
        <a href="javascript:void(0)">https://geoviqua.stcorp.nl/api/v1/feedback/items/search?format=xml&amp;target_codespace=<strong>yyy</strong>&amp;target_code=<strong>xx</strong></a>
      </div>

      <br />
      <div class="row">
        <div class="span8">
          <p>
            <i class="icon-info-sign icon-large pull-left"></i>
            This will show an aggregation of your feedback items, with an average rating and a count.
          </p>
        </div>
        <div class="span4">
          <a class="fancy" href="img/tutorial/f6.png"><img src="img/tutorial/f6.png" class="img-polaroid" /></a>
        </div>
      </div>

      <br />
      <button class="btn btn-large btn-block btn-info btn-next no-js" type="button" data-next="#label">Continue to Part 3: The GEO label</button>

    </div>
  </div>

</section>



<!-- The GEO label
================================================== -->
<section class="tab-pane active" id="label">
  <div class="tabbable subnav">
    <ul id="labelNav" class="nav nav-pills">
      <li class="home"><a data-toggle="pill" href="#overview"><i class="icon-home icon-large"></i></a></li>
      <li><a href="#producer" data-toggle="pill">1. The Producer Quality Model</a></li>
      <li><a href="#feedback" data-toggle="pill">2. The User Feedback Model</a></li>
      <li class="active"><a href="#label" data-toggle="pill">3. The GEO label</a></li>
    </ul>
  </div>
  <div class="page-header">
    <h3>The GEO label</h3>
  </div>

  <div class="row">
    <div class="span8">
      <blockquote>
        <p><i class="icon-quote-left icon-4x pull-left icon-muted"></i> The GEO label is a quick way to assess and interrogate the metadata that's available for a dataset, by pulling together producer documents and user feedback into a simple, clickable symbol.</p>
        <br />
        <p>The GEO label has 8 facets, and each will be coloured only if that type of information is available. A quick summary of what's available can be obtained by hovering over the facet.</p>
      </blockquote>
    </div>
    <div class="span4" style="position: relative;">
      <a href="#label-player" class="video-preview" data-video="label">
        <div class="play no-js">
          <span class="icon-stack icon-3x">
            <i class="icon-circle icon-stack-base"></i>
            <i class="icon-youtube-play icon-light"></i>
          </span>
        </div>
        <img src="img/label-hero.png" class="img-polaroid">
        <div style="display:none">
          <video id="label-player" class="video-js vjs-default-skin"
            width="853" height="480"
            data-setup='{ "controls": true, "autoplay": false, "preload": "auto" }'>
            <source src="resources/label_video/video.mp4" type='video/mp4' />
            <source src="resources/label_video/video.webm" type='video/webm' />
            <source src="resources/label_video/video.ogv" type='video/ogg' />
          </video>
        </div>
      </a>
    </div>
  </div>

  <br />
  <div class="row">
    <div class="span8">
      <p>
        <i class="icon-info-sign icon-large pull-left"></i>
        Clicking on a coloured facet will show the retrieved information: for example, here, the recorded citations for a dataset are listed.
      </p>
    </div>
    <div class="span4">
      <a class="fancy" href="img/tutorial/g3.png"><img src="img/tutorial/g3.png" class="img-polaroid" /></a>
    </div>
  </div>

  <br />
  <h4 id="t3s1">Step 1: See a GEO label for our example documents</h4>

  <div class="row">
    <div class="span8">
      <p>
        <i class="icon-info-sign icon-large pull-left"></i>
        In your browser, go to <a href="http://www.geolabel.net/demo.html" target="_blank">http://www.geolabel.net/demo.html <i class="icon-external-link" style="text-decoration: none;"></i></a>
        <br /><br />
        Select the tab &lsquo;Example metadata documents&rsquo;, select the documents as shown in the image below, and click &lsquo;Submit&rsquo;. There will probably be a short delay: in this time, the GEO label service is retrieving the producer document, but also querying the feedback service, and aggregating all the results.
      </p>
      <div class="alert alert-info">
        When the GEO label is returned, hover over its facets to find out more about the available information, and click on them to find out more.
      </div>
    </div>
    <div class="span4">
      <a class="fancy" href="img/tutorial/g2.png"><img src="img/tutorial/g2.png" class="img-polaroid" /></a>
    </div>
  </div>

  <br />
  <h4 id="t3s2">Step 2: Construct a GEO label from your own documents and feedback</h4>

  <p>You can also specify your own URLs, codes and codespaces, or even upload a local document, using the different tabs. You can now build your own GEO label from the producer document you published in tutorial 1 and the feedback you generated in tutorial 2.</p>

  <br />
  <div class="row">
    <div class="span8">
      <p>
        <i class="icon-info-sign icon-large pull-left"></i>
        First, copy the URL for your metadata document. This can be the URL of a traditional ISO document, e.g. our <a href="http://schemas.geoviqua.org/GVQ/4.0/example_documents/19139/DigitalClimaticAtlas19139.xml" target="_blank">example metadata document <i class="icon-external-link" style="text-decoration: none;"></i></a> or a <a href="http://schemas.geoviqua.org/GVQ/4.0/example_documents/PQMs/DigitalClimaticAtlas_mt_an_v10.xml" target="_blank">GeoViQua-transformed document <i class="icon-external-link" style="text-decoration: none;"></i></a>
      </p>
      <p>
        Visit <a href="http://www.geolabel.net/demo.html" target="_blank">The GEO label website <i class="icon-external-link" style="text-decoration: none;"></i></a> again and paste this URL into the top text box on the &lsquo;Enter metadata URL location&rsquo; tab</a>, ensuring that you enter your own code and codespace values from step 1 of tutorial 2.
      </p>
      <div class="alert alert-success">
        Now click &lsquo;Submit&rsquo; and you should get your very own GEO label! Explore and experiment to see how different labels are returned from different datasets.
      </div>
    </div>
    <div class="span4">
      <a class="fancy" href="img/tutorial/g4.png"><img src="img/tutorial/g4.png" class="img-polaroid" /></a>
    </div>
  </div>

  <br />
  <div class="row">
    <div class="span12">
      <p>
        <i class="icon-info-sign icon-large pull-left"></i>
        Even better, we can generate a GEO label for the GeoViQua compliant document that we transformed and stored in GeoNetwork earlier
        by using the GEO label API. The form below will fetch the metadata record that you published and submit it along with your chosen
        code &amp; codespace for feedback to the GEO label service, which will then return your very own GEO label!
      </p>
    </div>
  </div>

  <br />
  <div class="row">
    <div class="tabbable span12">
      <ul class="nav nav-tabs">
        <li id="geolabel-tab" class="active"><a data-toggle="tab" href="#tabs3-pane1">Generate GEO label</a></li>
        <li id="geolabel-results-tab" class=""><a data-toggle="tab" href="#tabs3-pane2">View result</a></li>
      </ul>
      <div class="tab-content">
        <div id="tabs3-pane1" class="tab-pane active">
          <form id="geolabel-form" class="form-horizontal" enctype="multipart/form-data" method="post" action="geolabel.php" target="_blank">
            <div class="alert alert-error" style="display: none;"></div>
            <fieldset>
              <div class="control-group">
                <label for="geonetwork_id" class="control-label">Enter the ID of your dataset:</label>
                <div class="controls">
                  <input type="text" name="geonetwork_id" placeholder="5" class="input-xlarge" id="geonetwork_id">
                </div>
              </div>
              <div class="control-group">
                <label for="target_code" class="control-label">Enter target code:</label>
                <div class="controls">
                  <input type="text" name="target_code" placeholder="mtri2an1ib" class="input-xlarge" id="target_code">
                </div>
              </div>
              <div class="control-group">
                <label for="target_codespace" class="control-label">Enter target codespace:</label>
                <div class="controls">
                  <input type="text" name="target_codespace" placeholder="opengis.uab.cat" class="input-xlarge" id="target_codespace">
                </div>
              </div>
              <div class="form-actions">
                <div class="loading"><img src="img/loading.gif" /></div>
                <button class="btn btn-info submit" type="submit">Submit</button>
                <button class="btn" type="reset">Clear</button>
              </div>
            </fieldset>
          </form>
        </div>
        <div id="tabs3-pane2" class="tab-pane">
          <div class="alert alert-error">
            You have not yet submitted the required data to the GEO label service.
          </div>
          <div class="alert alert-success" style="display: none;">
            Your GEO label was successfully generated!
          </div>
          <div id="geolabel-result"></div>
          <br />
        </div>
      </div><!-- /.tab-content -->
    </div><!-- /.tabbable -->
  </div>

  <div class="row">
    <div class="span12">
      <div class="alert alert-info">
        You could also submit your document manually by following the previous example and using the URL <a href="javascript:void(0)">http://uncertdata.aston.ac.uk:8080/geonetwork/srv/eng/xml_geoviqua?id=<strong>YOURDATSETID</strong>&amp;styleSheet=xml_iso19139.geoviqua.xsl</a>,
        replacing &lsquo;<strong>YOURDATASETID</strong>&rsquo; with the numeric ID.
      </div>
    </div>
  </div>


</section>

</div> <!-- /tab-content -->

<br><br><br><br>

     <!-- Footer
      ================================================== -->
      <hr>

      <footer id="footer">
        <p class="pull-right"><a href="#top">Back to top</a></p>
        <div class="links">
          <a href="http://www.geoviqua.org/" onclick="trackOutbound(this, 'external', 5); return false;" title="GeoViQua project website" target="_blank">GeoViQua</a>
          <a href="http://geolabel.info/" onclick="trackOutbound(this, 'external', 5); return false;" title="GEO label project website" target="_blank">GEO label</a>
          <a href="http://ec.europa.eu/research/fp7/" onclick="trackOutbound(this, 'external'); return false;" title="EC FP7 Research website" target="_blank">EC FP7</a>
          <a href="http://www.earthobservations.net/index.shtml" onclick="trackOutbound(this, 'external'); return false;" title="GEO - Group on Earth Observations website" target="_blank">GEO - Group on Earth Observations</a>
        </div>
        For more information on the topics presented in this tutorial, take a look at our <a href="http://inspire.jrc.ec.europa.eu/events/conferences/inspire_2013/pdfs/23-06-2013_ROOM-4_16.00%20-%2017.30_273-J%20Maso_J-Maso.pdf" onclick="trackOutbound(this, 'download'); return false;" title="INSPIRE Conference 2013" target="_blank">INSPIRE Conference 2013 workshop</a> presentation.
      </footer>

    </div><!-- /container -->

    <div class="footer-base">
      <div class="container">
        <div class="row">
          <div class="span12">
            This work was supported by the European Commission through the Seventh Framework Programme under grant agreement 265178 (QUAlity aware VIsualisation for the Global Earth Observation System of Systems (GeoViQua)).
          </div>
        </div>
      </div>
    </div>



    <script src="js/jquery.min.js"></script>
    <script src="js/jquery.smooth-scroll.min.js"></script>
    <script src="js/validate/jquery.validate.min.js"></script>
    <script src="js/validate/additional-methods.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/fancybox/jquery.fancybox.pack.js"></script>
    <script src="js/video-js/video.js"></script>
    <script src="js/plupload/plupload.full.min.js"></script>
    <script src="js/scripts.js"></script>


  </body>
</html>
