<?php
/**
 *  A class to send an email
 *  
 *  Copyright (C) 2011 paj@gaiterjones.com
 *
 *	This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @category   PAJ
 *  @package    
 *  @license    http://www.gnu.org/licenses/ GNU General Public License
 * 
 *
 */

class GetCustomerFeedbackMail{
    var $to = null;
	var $bcc = null;
	var $cc = null;
    var $from = null;
    var $subject = null;
    var $body = null;
    var $headers = null;

     function GetCustomerFeedbackMail($to,$from,$subject,$body,$bcc="",$cc=""){
        $this->to      = $to;
		$this->bcc     = $bcc;
		$this->cc      = $cc;		
        $this->from    = $from;
        $this->subject = $subject;
        $this->body    = $body;
    }

    function send(){
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

    function addHeader($header){
        $this->headers .= $header;
    }

}
?>