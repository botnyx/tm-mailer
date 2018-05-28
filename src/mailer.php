<?php

namespace botnyx\tmmailer;

class mailer {
	
	var $senderEmail=false;
	var $senderName=false;
	/*
		$x = new \botnyx\tmmailer\mailer();
		$x->setFrom($senderEmail,$senderName);
		$x->setTransport($server,$port,$username,$password);
		$data['name']='joep';
		$data['email']='joep@ik.nl';
		$data['web_directory']='http://webrootforimagesorsuch';
		$x->zend($data,$emailtemplate);
	
	*/
	
	
	function __construct($templatedir,$cachedir=false){
		$loader = new \Twig_Loader_Filesystem($templatedir);
		$this->twig = new \Twig_Environment($loader, array(
			'cache' => $cachedir,
		));
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
	}
	
	
	
	
	function send($data,$emailtemplate='twig-mail.phtml'){
		
		// to address check.
		if (!array_key_exists('email',$data)){
			$error = "Missing email in data structure";
			throw new \Exception($error);
		}
		if (!array_key_exists('name',$data)){
			throw new \Exception("Missing name in data structure");
		}
		if (!array_key_exists('web_directory',$data)){
			$data['web_directory']="";
		}
		//error_log('senderEmail');
		// who sent this email?
		if($this->senderEmail===false or $this->senderName===false){
			throw new \Exception("error, no sender name/email");
		}
		
		$data['recipient'] = ['name' => $data['name'], 'email' => $data['email']];
		
		//var_dump($data);
		
		// create mailer-templating instance.
		$swiftMailerTemplateHelper = new \botnyx\SwiftmailerTwigBundle\Mailer\TwigSwiftHelper($this->twig, $data['web_directory']);


		// create the message.
		$message = $this->mailer->createMessage()->setFrom([$this->senderEmail => $this->senderName])->setTo([$data['email']]);
	
		// populate the mail.
		$swiftMailerTemplateHelper->populateMessage($message, $emailtemplate, $data);
	
		// Send the message
		$result = $this->mailer->send($message, $failures);
		if(!$result){
		  $error = 'Mail Error : '.json_encode($failures);
		  throw new \Exception($error);
		}
		
		
	}
}