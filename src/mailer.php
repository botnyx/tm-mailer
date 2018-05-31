<?php

namespace botnyx\tmmailer;

class mailer {
	
	var $senderEmail=false;
	var $senderName=false;
	
	var $echodebug = false;
	var $arraydebug = true;
	
	/*
		$x = new \botnyx\tmmailer\mailer();
		$x->setFrom($senderEmail,$senderName);
		$x->setTransport($server,$port,$username,$password);
		$data['name']='joep';
		$data['email']='joep@ik.nl';
		$data['web_directory']='http://webrootforimagesorsuch';
		$x->zend($data,$emailtemplate);
	
	*/
	
	
	function __construct($templatedir,$cachedir=false,$dkim=false){
		$loader = new \Twig_Loader_Filesystem($templatedir);
		$this->twig = new \Twig_Environment($loader, array(
			'cache' => $cachedir,
		));
		$this->dkim = $dkim;
		if($dkim){
			// DKIM 用の Signer を作成する
			$privateKey = file_get_contents('./default.private');
			$domainName = 'example.com';
			$selector = 'default';
			$this->signer = new \Swift_Signers_DKIMSigner($privateKey, $domainName, $selector);
		}
		
	}
	
	public function setFrom($senderEmail,$senderName){
		$this->senderEmail = $senderEmail;
		$this->senderName = $senderName;
	}
	
	public function setTransport($server,$port,$username,$password){
		// Create the Transport
		$transport = (new \Swift_SmtpTransport($server, $port))->setUsername($username)->setPassword($password);
		// Create the Mailer using your created Transport
		$this->mailer = new \Swift_Mailer($transport);
		if($this->echodebug) {
			$logger = new \Swift_Plugins_Loggers_EchoLogger();
			$this->mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($logger));
		}
		
		if($this->arraydebug) {
			// To use the ArrayLogger
			$this->arraylogger = new \Swift_Plugins_Loggers_ArrayLogger();
			$this->mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($this->arraylogger));
		}
	}
	
	
	public function dkim(){
		$transport = Swift_SmtpTransport::newInstance('localhost', 25);
		$mailer = Swift_Mailer::newInstance($transport);
		
		
		// DKIM mail Signer 
		$privateKey = file_get_contents('./default.private');
		$domainName = 'example.com';
		$selector = 'default';
		$this->signer = new \Swift_Signers_DKIMSigner($privateKey, $domainName, $selector);
		
		$message = \Swift_SignedMessage::newInstance();
		$message->attachSigner($this->signer);
		
		$message
			->setFrom(['suzuki@example.com'])
			->setTo(['YOUR_GMAIL_ADDRESS'])
			->setSubject('abc')
			->setBody('abc')
			;
		$result = $mailer->send($message);
	}
	
	
	
	function send($data,$emailtemplate='twig-mail.phtml'){
		//error_log("send( ".json_encode($data).",$emailtemplate)");
		// to address check.
		if (!array_key_exists('email',$data)){
			$error = "Missing email in data structure";
			error_log($error);
			throw new \Exception($error);
		}
		if (!array_key_exists('name',$data)){
			$error = "Missing name in data structure";
			error_log($error);
			throw new \Exception($error);
		}
		if (!array_key_exists('web_directory',$data)){
			$data['web_directory']="";
		}
		//error_log('senderEmail');
		// who sent this email?
		if($this->senderEmail===false or $this->senderName===false){
			error_log("error, no sender name/email");
			throw new \Exception("error, no sender name/email");
		}
		
		$data['recipient'] = ['name' => $data['name'], 'email' => $data['email']];
		
		
		// create mailer-templating instance.
		$swiftMailerTemplateHelper = new \botnyx\SwiftmailerTwigBundle\Mailer\TwigSwiftHelper($this->twig, $data['web_directory']);

		
		if($this->dkim){
			$message = \Swift_SignedMessage::newInstance();
			$message->attachSigner($this->signer);
		}else{
			// create the message.
			$message = $this->mailer->createMessage();
		}
		
		$message->setFrom([$this->senderEmail => $this->senderName])->setTo([$data['email']]);
		$message->setPriority(\Swift_Mime_SimpleMessage::PRIORITY_HIGH);
		
		
		
		// populate the mail.
		$swiftMailerTemplateHelper->populateMessage($message, $emailtemplate, $data);
	
		// Send the message
		$result = $this->mailer->send($message, $failures);
		
		
		if($this->arraydebug) {
			$logarray = $this->arraylogger->dump();
		}
		
		if(!$result){
		  	$error = json_encode($failures);
			error_log($error);
			throw new \Exception($error);
		}else{
			error_log("Mail sent! (".$data['email'].")");
		}
		return $logarray;
		
	}
}