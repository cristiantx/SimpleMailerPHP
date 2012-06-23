<?php
/**
 * SimpleMailer Class
 * Small utility to send formated or non-formated emails.
 * With different parameters using jQuery-like options, just for more confortable use.
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
	 * 
	 */
	protected $message;
	protected $subject;

	protected $debug = false;
	protected $log;
	protected $options;
	protected $html;
	
	/**
	 * Create a new Mailer class. 
	 * Receive and array of options.
	 * @param array $options 
	 * @param bool $debug
	 */
	public function __construct($options, $debug = false) {

		if ( !function_exists('mail') ) {
			throw new Exception('This Class needs mail() function to work.')
		} 

		$this->debug = $debug;
		
		// Mailer default options.
		$defaults = array(
			'from' => array(
				'name' => null, // From name
				'email' => null  
			),
			'to' => array(
				'name' => null,
				'email' => null,
				'CC' => null, // array of CC addresses
				'CCO' => null, // array of CCO addresses
			),
			'isHtml' => false, // Specify if the email will be HTML format.
			'templateHtmlSrc' => null, // In case email is in HTML format, specify a base template.
			'charset' => 'utf8',
			'priority' => 3,
			'vars' => null,
			'verbose' => false,
		);
		
		$this->log('Setting options and maintaining defaults...');
		//$this->log('Received options: ' . var_export($options, true));
		$this->options = array_merge($defaults, $options);
		
		//$this->log('Final options: ' . var_export($options, true));
		$this->log('Initializating SimpleMailer...');
		$this->process();

	}

	
	private function log($str, $br = true ) {

		$str = "<strong>[" . date('H:i:s') . "]</strong> " . $str . "<br />";
		
		if ($this->options['verbose']) {
			echo $str;
		}
		
		$this->log .= $str;

	}

	
	/**
	 * Validates & Process the options parameter.
	 */
	private function process() {

		if ( !filter_var($this->options['from']['email'], FILTER_VALIDATE_EMAIL) ) {
			throw new Exception("Mailer needs a valid From address.");
		}
		
		if ( !filter_var($this->options['to']['email'], FILTER_VALIDATE_EMAIL)) {
			throw new Exception("Mailer needs at least a valid destination address.");
		}
		
		if ($this->options['isHtml']) {
			// Check if the template html src is specified.
			if(!empty($this->options['templateHtmlSrc'])) {
				// Read the HTML from file
				$this->readHtml(); 
			}
			/*else {
				throw new Exception("If you turn on the 'isHTML' flag. You need a 'templateHtmlSrc'");
			}*/
		}
		
		// Check the supported charsets.
		switch ($this->options['charset']) {
			case 'utf8':
				$this->options['charset'] = 'utf-8';
				break;
			case 'iso':
				$this->options['charset'] = 'iso-8859-1';
				break;
			default:
				throw new Exception("Invalid charset or not supported");
		}
		
		// Check if the priority is between the allowed values.
		if ($this->options['priority'] > 5 || $this->options['priority'] < 1) {
			throw new Exception("Invalid priority value. It must be between 1 and 5");
		}
		
		// Setting main replacement variables for the template.
		$this->options['vars']['fromName']	= $this->options['from']['name'];
		$this->options['vars']['fromEmail'] = $this->options['from']['email'];
		$this->options['vars']['toEmail']	= $this->options['to']['name'];
		$this->options['vars']['toName']	= $this->options['to']['email'];

	}

	
	/**
	 * Reads external HTML file used as template.
	 */
	private function readHtml() {

		$src = $this->options['templateHtmlSrc'];
		$this->log('Attemting to read template file ' . $src . '... ', false);
		
		// If file exists save the HTML into the property.
		if (file_exists($src)) {
			$this->html = file_get_contents($src);
		} 
		else {
			throw new Exception("Template file not found or don't have enough permissions to read it (file: {$src})");
		}
		
		$this->log('Ok');

	}

	
	/**
	 * Sets the email subject.
	 * 
	 * @param string $subject The email Subject.
	 */
	public function setSubject($subject) {

		$this->log('Setting subject to: ' . $subject);
		$this->subject = $subject;
		
		$this->options['vars']['subject'] = $subject;

	}

	/**
	 * Sets the email main message.
	 * 
	 * @param string $message The email message. Can have template "tags" to be replaced by data of the 'vars' class option.
	 * @return void
	 */
	public function setMessage($message) {

		$this->log('Setting message...');
		$this->message = $message;
		
		$this->options['vars']['message'] = $message;

	}

	
	/**
	 * Parse the placeholders and replace them for variables from 'vars' class option.
	 * @return void
	 */
	private function parsePlaceholders() {

		$this->log('Parsing email placeholders... ', false);
		
		if ($this->options['isHtml']) {
			$this->log('as HTML file.');
			$parsed = $this->html;
		}
		else {
			$this->log('as plain text.');
			$parsed = $this->message;
		}
		
		foreach($this->options['vars'] as $key => $value) {
			$parsed = str_replace("{".$key."}", $value, $parsed);
		}
		
		$this->setMessage($parsed);

	}

	
	/**
	 * Turn on the Debug feature.
	 * NOTE: This feature just turn off the mail sender and prints the email content on the actual page when send() is called.
	 * @return void
	 */
	public function debug() {

		$this->debug = true;

	}

	
	/**
	 * Generate Email headers before send.
	 * @return string $headers The Headers String to use in the email.
	 */
	private function generateHeader() {

		$options = $this->options;
		$headers = '';
		
		$timeStamp = time();
		$remote_address = $_SERVER["REMOTE_ADDR"];		
		$boundary = uniqid('np');


		$this->log('Setting headers...', false);

		$header .= "MIME-Version: 1.0\r\n"; 

		if (!empty($options['from']['name']) && !empty($options['from']['email'])) {
			$headers .= "From: {$options['from']['name']} <{$options['from']['email']}>\r\n";
		} 
		elseif (!empty($options['from']['email'])) {
			$headers .= "From: {$options['from']['email']}\r\n";
		}

		if (!empty($options['to']['CC'])) {

			if (is_array($options['to']['CC'])) {
				$headers .= "CC: " . implode(',', $options['to']['CC']) . "\r\n";
			}
			else {
				$headers .= "CC: <{$options['to']['CC']}>\r\n";
			}

		}
		
		if (!empty($options['to']['BCC'])) {

			if (is_array($options['to']['BCC'])) {
				$headers .= "BCC: " . implode(',', $options['to']['BCC']) . "\r\n";
			}
			else {
				$headers .= "BCC: <{$options['to']['BCC']}>\r\n";
			}

		}
		
		if ($options['isHtml']) {
			$headers .= "Content-type: text/html; charset={$options['charset']}\r\n";
		} 
		else {
			$headers .= "text/plain; charset={$options['charset']}\r\n";
		}
		
		if ($options['priority']) {
			$headers .=  "X-Priority: {$options['priority']}\r\n";
		}	
		
		$headers .= "Message-Id: <p{$timeStamp}@[{$remote_address}]>\r\n";
		
		$headers .= "X-Sender: {$options['from']['email']}\r\n";
		$headers .= "X-Sender-IP: " . $remote_address ."\r\n"; 
		$headers .= "X-Mailer: PHP/" . phpversion()."\r\n";		
		
		$this->log('Ok');
		
		return $headers;

	}

	
	/**
	 * Actually sends the resulting email.
	 * @return void
	 */
	public function send() {

		$this->parsePlaceholders();
		$headers = $this->generateHeader();
			
		$this->log('Sending email...', false);
		
		$status = false;
		
		$this->log('Message: ');
		$this->log($this->message);	
		$this->log('Headers: ');
		$this->log(nl2br($headers));			
		
		if (!$this->debug) {
			if (mail($this->options['to']['email'], $this->subject, $this->message, $headers)) {
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

		echo '<div style="border: 1px solid red; padding: 10px;">' . $this->message . '</div>';

	}

    public function __set($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function __get($name)
    {
        
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);

        return null;
    }	
}