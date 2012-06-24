<?php
/**
 * SimpleMailer Class
 * Small utility to send formated or non-formated emails.
 * Is possible to use an external file as a template with placeholders to be replaced with variables added to the object.
 * 
 * @author Cristian Conedera <cristian.conedera@gmail.com>
 * @package SimpleMailer PHP
 * @copyright BothMedia 2011
 * @version 1.1a
 * @copyright 2011-2012
 * @license Free http://unlicense.org/
 */

class SimpleMailer 
{

	/**
	 * @var array $from
	 * @access protected
	 */
	protected $from = array();

	/**
	 * @var array $to
	 * @access protected
	 */	
	protected $to = array();

	/**
	 * @var array $cc
	 * @access protected
	 */	
	protected $cc = array();

	/**
	 * @var array $bcc
	 * @access protected
	 */		
	protected $bcc = array();

	/**
	 * @var string $message (default value : NULL)
	 * @access protected
	 */	
	protected $message = null;

	/**
	 * @var string $subject (default value : NULL)
	 * @access protected
	 */		
	protected $subject = null;

	/**
	 * @var array $headers
	 * @access protected
	 */	
	protected $headers = array();
	
	/**
	 * Placeholders array, these will be replaced in the email template.
	 * @var array $vars
	 * @access protected
	 */		
	protected $vars = array();

	/**
	 * @var bool $verbose (default value: false)
	 * @access protected
	 */	
	protected $verbose = false;

	/**
	 * @var string $charset (default value: utf-8)
	 * @access protected
	 */		
	protected $charset = 'utf-8';

	/**
	 * Determine if the email will be send as HTML
	 * @var bool $isHtml (default value: false)
	 * @access protected
	 */		
	protected $isHtml = false;

	/**
	 * @var string $templateUri (default value: null)
	 * @access protected
	 */		
	protected $templateUri = null;

	/**
	 * @var string $priority (default value: 3)
	 * @access protected
	 */
	protected $priority = 3;


	/**
	 * @var string $template (default value: null)
	 * @access protected
	 */
	protected $template = null;

	/**
	 * @var string $parsed_message (default value: null)
	 * @access protected
	 */	
	protected $parsed_message = null;	

	/**
	 * @var bool $debug (default value: false)
	 * @access protected
	 */		
	protected $debug = false;

	/**
	 * @var string $log (default value: null)
	 * @access protected
	 */		
	protected $log = null;

	/**
	 * Create a new Mailer class. 
	 * 
	 * @param bool $debug Turn on the Debug Feature
	 */
	public function __construct( $debug = false ) {

		if ( !function_exists('mail') ) {
			throw new Exception('This Class needs mail() function to work.');
		} 

		$this->debug = $debug;

	}


	/**
	 * Destination email/name
	 * 
	 * @param string $email
	 * @param string $name
	 */
	public function setTo( $email, $name ) {

		$email = $this->_filterEmail( $email );
		$name = $this->_filterName( $name );

		if ( !$this->_validateEmail( $email ) ) {
			throw new InvalidArgumentException("Valid email needed.");
		}

		$this->to = array(
				'email' => $email,
				'name' => $name
			);
	}

	

	/**
	 * CC Destination email
	 * 
	 * @param string $email
	 */	
	public function addCC( $email ) {

		$email = $this->_filterEmail( $email );

		if ( !$this->_validateEmail( $email ) ) {
			throw new InvalidArgumentException("Valid email needed.");
		}

		array_push( $this->cc, $email );
	}	


	/**
	 * BCC Destination email
	 * 
	 * @param string $email
	 */	
	public function addBCC( $email ) {

		$email = $this->_filterEmail( $email );

		if ( !$this->_validateEmail( $email ) ) {
			throw new InvalidArgumentException("Valid email needed.");
		}

		array_push( $this->bcc, $email );

	}		


	/**
	 * Sender email/name
	 * 
	 * @param string $email
	 */	
	public function setFrom( $email, $name ) {

		$email = $this->_filterEmail( $email );
		$name = $this->_filterName( $name );

		if ( !$this->_validateEmail( $email ) ) {
			throw new InvalidArgumentException("Valid email needed.");
		}

		$this->from = array(
				'email' => $email,
				'name' => $name
			);

	}


	/**
	 * Sets the Email Charset Encoding
	 * 
	 * @param string $charset (utf8 | iso)
	 */	
	public function setCharset( $charset ) {

		// Check the supported charsets.
		switch ($charset) {
			case 'utf8':
				$this->charset = 'utf-8';
				break;
			case 'iso':
				$this->charset = 'iso-8859-1';
				break;
			default:
				throw new InvalidArgumentException("Invalid charset or not supported");
		}

	}



	/**
	 * Sets the email subject.	 
	 * 
	 * @param string $subject The email Subject.
	 */
	public function setSubject($subject) {

		$this->log('Setting subject to: ' . $subject);
		$this->subject = $this->_filterGeneric( $subject );
		
		$this->vars['subject'] = $subject;

	}


	/**
	 * Sets the email main message.
	 * 
	 * @param string $message The email message. Can have template "placeholders" to be replaced by data of the 'vars' class option.
	 * @return void
	 */
	public function setMessage( $message ) {

		$this->log('Setting message...');

		$this->message = str_replace("\n.", "\n..", $message);

		$this->vars['message'] = $message;

	}	


	/**
	 * Switch between HTML email and plain text email
	 * 
	 * @param bool $switch (true|false)
	 */
	public function htmlEmail ( $switch ) {

		if( !is_bool($switch) ) {

			throw new InvalidArgumentException();

		}

		$this->log( 'Setting email to : ' . (($switch)?'HTML':'plain text') );

		$this->isHtml = $switch;

	}


	/**
	 * Determine the email template using a file.
	 * 
	 * @param string $file File URI to use as Email template.
	 */
	public function setTemplate ( $file ) {


		$this->log('Setting template file to : ' . $file);

		if( !is_string($file) ) {
			throw new InvalidArgumentException('Needs file URI string');
		}

		if( !(file_exists($file) && is_file($file)) ) {
			throw new Exception('File specified does not exist or is not a file.');
		}

		$this->templateUri = $file;

	}


	/**
	 * Set the email priority
	 * 
	 * @param int $priority [1-5]
	 */
	public function setPriority( $priority ) {

		if( !is_int($priority) || !($priority >= 1 && $priority <= 5) ) {
			throw new InvalidArgumentException("Invalid priority value. It must be an integer between 1 and 5");
		}

		$this->priority = $priority;

	}

	
	/**
	 * Add a placeholder to be replaced by the value in the template.
	 * 
	 * @param string $key The key is the name of the placeholder that will be replaced on the message template. 
	 * @param mixed $value Value to be replaced.
	 */
	public function addVariable( $key, $value ) {

		array_push($this->vars, array($key, $value));

	}


	/**
	 * Add a placeholders to be replaced by the value in the template.
	 * 
	 * @param array $variables Array of key -> value that will be replaced in the template.
	 */
	public function addVariables( $variables ) {

		array_merge($this->vars, $variables);

	}	

	
	/**
	 * Saves a log of the current email progress.
	 * 
	 * @param string $str Log message. 
	 * @param bool $br 
	 * @return void
	 */
	private function log( $str, $br = true ) {

		$str = "<strong>[" . date('H:i:s') . "]</strong> " . $str . "<br />";
		
		if ($this->debug) {
			echo $str;
		}
		
		$this->log .= $str;

	}


	/**
	 * @return string Returns the log of the current email.
	 */
	public function getLog() {

		return $this->log;

	}

	
	/**
	 * Read the template files, parse the placeholders and generate the email headers.
	 * @return void
	 */
	private function process() {

		
		if( $this->templateUri ) {
			// Read the Template from file
			$this->readTemplate(); 
		}

		// Setting main replacement variables for the template.
		$this->vars['fromName']  = $this->from['name'];
		$this->vars['fromEmail'] = $this->from['email'];
		$this->vars['toEmail']   = $this->to['email'];
		$this->vars['toName']    = $this->to['name'];

		$this->parsePlaceholders();
		$this->generateHeader();

	}

	
	/**
	 * Reads external file used as template.
	 */
	private function readTemplate() {

		$src = $this->templateUri;
		$this->log('Attemting to read template file ' . $src . '... ', false);
		
		// If file exists save the HTML into the property.
		if ( file_exists($src) ) {
			$this->template = file_get_contents($src);
		} 
		else {
			throw new Exception("Template file not found or don't have enough permissions to read it (file: {$src})");
		}
		
		$this->log('Ok');

	}

	
	/**
	 * Parse the placeholders and replace them for variables from 'vars' class option.
	 * @return void
	 */
	private function parsePlaceholders() {

		$this->log('Parsing email placeholders... ', false);
		
		$parsed = $this->template;
		
		foreach($this->vars as $key => $value) {
			$parsed = str_replace("{".$key."}", $value, $parsed);
		}
		
		$this->parsed_message = $parsed;

	}

	
	/**
	 * Generate Email headers before send.
	 * @return string $headers The Headers String to use in the email.
	 */
	private function generateHeader() {
		
		$timestamp = time();
		$remote_address = $_SERVER["REMOTE_ADDR"];		
		$boundary = md5(uniqid(time()));

		$this->log('Setting headers...', false);

		$this->addHeader("MIME-Version: 1.0");
		$this->addHeader("Content-type: multipart/mixed; boundary={$boundary}\r\n");
		$this->addHeader("This is a multi-part message in MIME format.");
		$this->addHeader("--" . $uid);
		
		if ($this->isHtml) {
			$this->addHeader("Content-type: text/html; charset={$this->charset};");
		} 
		else {
			$this->addHeader("Content-type: text/plain; charset={$this->charset};");
		}
		
		$this->addHeader( "Content-Transfer-Encoding: 7bit\r\n");
		$this->addHeader( $this->parsed_message . "\r\n");
		$this->addHeader("--" . $uid);


		if (!empty($this->from['name']) && !empty($this->from['email'])) {
			$this->addHeader("From: {$this->from['name']} <{$this->from['email']}>");
		} 
		elseif ( !empty($this->from['email']) ) {
			$this->addHeader("From: {$this->from['email']}");
		}

		if (count($this->cc) > 0) {
			$this->addHeader("CC: " . implode(',', $this->cc) );
		}


		if (count($this->bcc) > 0) {
			$this->addHeader("BCC: " . implode(',', $this->bcc) );
		}		
		

		if ($this->priority) {
			$this->addHeader("X-Priority: {$this->priority}");
		}	

		//$this->addHeader("Message-Id: <p{$timestamp}@[{$remote_address}]>");
		$this->addHeader("X-Sender: {$this->from['email']}");
		$this->addHeader("X-Sender-IP: {$remote_address}");
		$this->addHeader("X-Mailer: PHP/" . phpversion());
		
		$this->log('Ok');

	}


	/**
	 * Return the generated headers to be used.
	 * @return string
	 */
	private function getHeaders() {


		$plain_headers = '';

		foreach($this->headers as $header) {

			$plain_headers .= $header . "\r\n";

		}

		return $plain_headers;

	}


	/**
	 * Add a header to the headers array.
	 * @return void
	 */
	private function addHeader( $header ) {

		array_push($this->headers, $header);

	}

	
	/**
	 * Actually sends the resulting email.
	 * @return void
	 */
	public function send() {

		$this->log('Processing options...');
		$this->process();

		$headers = $this->getHeaders();
			
		$this->log('Sending email...', false);
		
		$status = false;
		
		$this->log('Message: ');
		$this->log( $this->message );	
		$this->log('Headers: ');
		$this->log(nl2br($headers));			
		
		if (!$this->debug) {

			if (mail($this->to['email'], $this->subject, $this->message, $headers)) {
				$this->log('Ok');
				$status = true;
			} 
			else {
				$this->log('Failure!');
			}

		} else {

			$this->printDebug();

		}

		return $status;

	}


	/**
	 * Prints the processed message on the screen for debug purposes.
	 * @return void
	 */
	private function printDebug() {

		$print_message = ($this->isHtml)?$this->parsed_message:nl2br($this->parsed_message);

		echo '<div style="border: 1px solid red; padding: 10px;">' . $print_message . '</div>';

	}


	/**
	 * Filters and Sanitize an Email Address
	 * @param string $email
	 * @return string
	 */
    protected function _filterEmail($email) {

		$rule = array("\r" => '',
					  "\n" => '',
					  "\t" => '',
					  '"'  => '',
					  ','  => '',
					  '<'  => '',
					  '>'  => '',
		);

		$email = strtr($email, $rule);
		$email = filter_var($email, FILTER_SANITIZE_EMAIL);

		return $email;

	}


	/**
	 * Filters and Sanitize a Name
	 * @param string $name
	 * @return string
	 */
	protected function _filterName($name) {

		$rule = array("\r" => '',
					  "\n" => '',
					  "\t" => '',
					  '"'  => "'",
					  '<'  => '[',
					  '>'  => ']',
		);

		return trim(strtr(filter_var($name, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH), $rule));

	}


	/**
	 * Filters and Sanitize an Generic Text
	 * @param string $data
	 * @return string
	 */
	protected function _filterGeneric($data) {

		$rule = array("\r" => '',
					  "\n" => '',
					  "\t" => '',
		);

		return strtr(filter_var($data, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH), $rule);
	}


	/**
	 * Validate an Email Address
	 * @param string $email
	 * @return string
	 */
	protected function _validateEmail( $email ) {

		return filter_var( $email, FILTER_VALIDATE_EMAIL );

	}

}

