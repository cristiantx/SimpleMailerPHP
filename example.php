<?php

	require ('SimpleMailer.class.php');

	$mailer = new SimpleMailer();
	$mailer->setTo('test@gmail.com', 'Test Destinatary');
	$mailer->setSubject('Test Message');
	$mailer->setFrom('no-reply@bothmedia.com', 'BothMedia.com');
	$mailer->addCC('copy@domain.com');
	$mailer->setTemplate( 'template/sample-template.html' );
	$mailer->htmlEmail( false );
	$mailer->setMessage('<strong>Hello World!</strong>');
	$sent = $mailer->send();

	if($send) {
		echo 'Sent succefully'; 
	} else {
		echo 'Mail Failed<br />';
		echo $mailer->getLog();
	}

