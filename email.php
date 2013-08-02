<?php

require_once __DIR__ . '/app/includes/Swift-5.0.1/lib/swift_required.php';

session_start();

// name and path of the configuration file for this script
$config_file = dirname(__FILE__) . "/app/config/config.ini";

// check that the configuration file is readable
if (file_exists($config_file) && is_readable($config_file)) {

	$config = parse_ini_file($config_file);
}
else {

	header("HTTP/1.1 500 Internal Server Error");
	echo "Could not read " . $config_file;
	exit();
}

// determine the base URL for redirects back to the homepage
$path = pathinfo('//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
$url = $path['dirname'];

// check that the CSRF name & token is part of the POST request and that it matches the values set in session by index.php
if (isset($_POST['csrf']) && isset($_POST['token']) && ($_SESSION['csrf_token'][$_POST['csrf']] === $_POST['token'])) {

	// prevent re-use of this session
	unset($_SESSION['csrf_token'][$_POST['csrf']]);

	// check if a timestamp has been recorded of a successful email being sent
	if (isset($_SESSION['csrf_time_since_request'])) {

		$seconds_passed = (time() - $_SESSION['csrf_time_since_request']);

		// can't submit another form for a minute
		if ($seconds_passed < 60) {

			$_SESSION['csrf_lockout_time'] = (60 - $seconds_passed);
		}
		else {

			unset($_SESSION['csrf_lockout_time']);
		}
	}

	$errors = array();
	$valid = false;

	if (!(isset($_SESSION['csrf_lockout_time']))) {

		$fields = array();

		// sanitize POST fields
		foreach ($_POST as $key => $value) {

			$fields[$key] = trim($value);
		}

		// validate each field
		if ($fields['contactname'] === '') {

			$errors['contactname'] = 'This field is required.';
		}
		if ($fields['email'] === '') {

			$errors['email'] = 'This field is required.';
		}
		elseif (!(filter_var($fields['email'], FILTER_VALIDATE_EMAIL))) {

			$errors['email'] = 'Please enter a valid email address.';
		}
		if ($fields['subject'] === '') {

			$errors['subject'] = 'This field is required.';
		}
		if ($fields['message'] === '') {

			$errors['message'] = 'This field is required.';
		}
		if ($fields['dob'] !== '') {

			// our spambot honeypot has been filled in
			$errors['honeypot'] = true;
		}

		if (empty($errors)) {

			try {

				// create the transport
				switch ($config['transport_method']) {
					case 'smtp':
						$transport = Swift_SmtpTransport::newInstance();
						if ($config['smtp_host'] !== '') $transport->setHost($config['smtp_host']);
						if ($config['smtp_port'] !== '') $transport->setPort($config['smtp_port']);
						if ($config['smtp_encryption'] !== '') $transport->setEncryption($config['smtp_encryption']);
						if ($config['smtp_username'] !== '') $transport->setUsername($config['smtp_username']);
						if ($config['smtp_password'] !== '') $transport->setPassword($config['smtp_password']);
						break;
					default:
						// default to PHP's mail() function
						$transport = Swift_MailTransport::newInstance();
						break;
				}

				// create the mailer using the transport
				$mailer = Swift_Mailer::newInstance($transport);

				// create a message
				$message = Swift_Message::newInstance('GeoViQua tutorial contact form submission')
					->setFrom(array($config['from_address'] => 'GeoViQua tutorial'))
					->setTo(explode(';', $config['to_address']))
					->setBody(
						'The following form was submitted:' . PHP_EOL . PHP_EOL .
						'Name: ' . $fields['contactname'] . PHP_EOL .
						'Email address: ' . $fields['email'] . PHP_EOL .
						'Subject: ' . $fields['subject'] . PHP_EOL .
						'Message: ' . $fields['message']
					);

				// send the message
				$numSent = $mailer->send($message);

				if ($numSent > 0) {

					// the email was sent
					$valid = true;

					// make a note of the time this successful request was made
					$_SESSION['csrf_time_since_request'] = time();
				}
			}
			catch (Exception $e) {

				header("HTTP/1.1 500 Internal Server Error");
				echo "Exception occurred: " . $e->getMessage();
				exit();
			}
		}
	}
	else {

		// inform the user of the number of seconds that they must wait
		$errors['locked_out'] = 'You must wait ' . $_SESSION['csrf_lockout_time'] . ' seconds before you can submit this form again.';
	}

	// the response to send back
	$data = array(
		'valid' => $valid,
		'errors' => $errors,
		'fields' => $fields
	);

	if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {

		// if this isn't an ajax request, save the response to session & redirect
		$_SESSION['response'] = $data;
		header('Location: ' . $url);
		exit();
	}
	else {

		// send a JSON response
		die(json_encode($data));
	}
}
else {

	// possible CSRF forgery, silently redirect back to homepage
	header('Location: ' . $url);
	exit();
}

?>