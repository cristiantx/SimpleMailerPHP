#README

##Description

####Version 1.1a

SimpleMailer Class is a simple way to send emails via PHP using an external file as a Template. It supports placeholders to be replaced on-the-fly.

	$mailer = new SimpleMailer( true ); // Sets true to turn debug on.
	$mailer->setTo('test@gmail.com', 'Test Destinatary');
	$mailer->setSubject('Test Message');
	$mailer->setFrom('no-reply@bothmedia.com', 'BothMedia.com');
	$mailer->addCC('copy@domain.com');
	$mailer->setTemplate( 'template/sample-template.html' ); // Sample Template file
	$mailer->htmlEmail( false ); // Turn this on for HTML Content
	$mailer->setMessage('Hello World!'); 
	$sent = $mailer->send(); // Sends email
	
	echo ($send) ? 'Email sent successfully' : 'Could not send email';

### Coming Soon

* Attachment Support

## License
SimpleMailerPHP is free and unencumbered public domain software. For more information, see http://unlicense.org/ or the accompanying UNLICENSE file.