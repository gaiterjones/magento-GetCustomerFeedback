<?php
class PAJ_GetCustomerFeedback_Model_Email extends Mage_Core_Model_Abstract
{
    var $to = null;
	var $bcc = null;
	var $cc = null;
    var $from = null;
    var $subject = null;
    var $body = null;
    var $headers = null;

    public function __construct($to,$from,$subject,$body,$bcc="",$cc=""){
        $this->to      = $to;
		$this->bcc     = $bcc;
		$this->cc      = $cc;		
        $this->from    = $from;
        $this->subject = $subject;
        $this->body    = $body;
    }

    public function send(){
		
		$fqdn_hostname= $_SERVER['SERVER_NAME'];
		$sMessageId="<" . sha1(microtime()) . "@" . $fqdn_hostname . ">";
		$this->addHeader('From: '.$this->from."\r\n");
		if (!empty($this->cc)) {$this->addHeader('CC: '.$this->from."\r\n");}
		if (!empty($this->bcc)) {$this->addHeader('BCC: '.$this->bcc."\r\n");}
        $this->addHeader('Reply-To: '.$this->from."\r\n");
	    $this->addHeader("Content-Type:text/html; charset=\"iso-8859-1\"\r\n");		
        $this->addHeader('Return-Path: '.$this->from."\r\n");
		$this->addHeader("Message-ID: " .$sMessageId. "\r\n");
		$this->addHeader('X-mailer: MyMagentoModule 1.0'."\r\n");
		
		if (mail($this->to,$this->subject,$this->body,$this->headers)) 
		{
			return true;
		} else {
			return false;
		}
        
    }

    protected function addHeader($header){
        $this->headers .= $header;
    }
	
}