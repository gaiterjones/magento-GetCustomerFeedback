<?php
/**
 *  Get Customer Feedback Module
 *  
 *  Copyright (C) 2013 paj@gaiterjones.com & Zuiko
 *
 *  v0.0.12 - 25.11.2011 - dev BETA release
 *  v0.0.13 - 25.11.2011 - bug fixes - store name and image links
 *  v0.0.15 - 25.11.2011 - implemented detection for invisible simple products
 *						   that are children of a grouped product.
 *  v0.0.16 - 09.12.2011 - implemented change to allow removal of duplicates
 *						   products from generated cart array and force UTF8
 *						   conversion of description text.
 *  v0.0.17 - 16.12.2011 - Improved customer name handling
 *                         added multistore functionality to allow for for multi language
 *  v0.0.18 - 04.01.2012 - Improved email formatting and options for translations
 *  v0.0.19 - 05.01.2012 - Added max feedback item control
 *  v0.0.19a - 05.01.2012 - Bug Fix
 *	v0.0.20	- 20.02.2012 - Added locale file for translations
 *	v0.0.23	- 21.03.2012 - Added controls to prevent empty customer name from breaking code.
 * 	v0.0.3 - 28.02.2013  - Zuiko enhanced code to validate cache files against orders before sending.
 *                         
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
 *  @package    GetCustomerFeedback
 *  @license    http://www.gnu.org/licenses/ GNU General Public License
 * 
 *
 */

class PAJ_GetCustomerFeedback_Model_Observer
{

	public function GetCustomerFeedback()
	{
		require_once Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'classes'. DS . 'class.GetCustomerFeedback.Email.php';
		$cacheFolder  = Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS;
		$orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
		$order = Mage::getModel('sales/order')->load($orderId);
		$customerEmail = $order->getCustomerEmail();
		$realOrderId = $order->getRealOrderId();
		$orderStatus = $order->getStatus();
		// clean customer name, First + LAST from session
		$customerFirstName = strtolower(utf8_decode(Mage::getSingleton('customer/session')->getCustomer()->getFirstname()));
		$customerLastName = strtolower(utf8_decode(Mage::getSingleton('customer/session')->getCustomer()->getLastname()));
		$customerName=ucfirst($customerFirstName). " ". ucfirst($customerLastName);
		$customerName=trim($customerName);
		// if no session i.e. guest checkout get customer name from billing address
		if (empty($customerName)) { $customerName=$order->getBillingAddress()->getName();}
		// if no customer name use default to stop blank name from breaking things
		if (empty($customerName)) { $customerName="Customer";}
		
		// store id
		$orderStoreID=$order->getStoreId();
		$items = $order->getAllItems();
		$itemcount=count($items);
		$ids=array();
		$name=array();
		$unitPrice=array();
		$sku=array();
		$qty=array();
		$itemCount=1;
		$emailFeedbackIconURL=trim(Mage::getStoreConfig('getcustomerfeedback_section1/general/email_feedback_icon',(int)$orderStoreID));
		$urlTrackingTags = trim(Mage::getStoreConfig('getcustomerfeedback_section1/general/url_tracking_tags',(int)$orderStoreID));
		$feedbackProductIDs=array();

		try {
			// dump cart to html file
			if (is_writable($cacheFolder)) 
			{
				$newline="\n";
				$timeStamp=time();
				$fp1 = fopen($cacheFolder. 'GetCustomerFeedback'. $timeStamp. '.html', 'w');		
				$fp2 = fopen($cacheFolder. 'GetCustomerFeedback'. $timeStamp. '.dat', 'w');		
				// product html in fp1
				fwrite($fp1, '<table cellspacing="0" cellpadding="0" border="0" width="650" style="border:1px solid #EAEAEA;">'. $newline);	
				fwrite($fp1, '<tbody bgcolor="#F6F6F6">');
				
				// customer data in fp2
				// line 0 Order ID
				fwrite($fp2, $realOrderId. $newline);
				// line 1 Customer Name
				fwrite($fp2, $customerName. $newline);
				// line 2 Customer Email
				fwrite($fp2, $customerEmail. $newline);
				// line 3 HTML File
				fwrite($fp2, 'GetCustomerFeedback'. $timeStamp. '.html'. $newline);
				// line 4 Date/time
				fwrite($fp2, $timeStamp. $newline);
				// line 5 Internal Order ID
				fwrite($fp2, $orderId. $newline);
				// line 6 Internal Store ID
				fwrite($fp2, $orderStoreID. $newline);
				// LAST line Magento Version
				fwrite($fp2, "Magento Version ". Mage::getVersion(). $newline);						
				
		
				// parse cart for saleable/visible order items

				foreach ($items as $itemId => $item)
				{
					$cartProduct = Mage::getModel('catalog/product')->load($item->getProductId());
				
					if ($cartProduct->getVisibility()!= "4")
					{
					
					// check if the *invisible* product is a child of a grouped product
					
					if (Mage::getVersion() >= 1.4)
					{
					// Magento v1.42 +
					$parentIdArray = Mage::getModel('catalog/product_type_grouped')
						->getParentIdsByChild( $cartProduct->getId() );
					} else {
					// pre 1.42
					$parentIdArray = $cartProduct->loadParentProductIds()->getData('parent_product_ids');
					}
						
						if (!empty($parentIdArray[0]))
						{
							// use parent product if parent is grouped otherwise move on, these are not the products you are looking for...
							$cartProduct = Mage::getModel('catalog/product')->load($parentIdArray[0]);
							
							if($cartProduct->getTypeId() != "grouped") {
								continue;
							}
			
						}

					}
					
					// load the visible cart products into array
					if ($cartProduct->getVisibility()=== "4")
					{
						$feedbackProductIDs[]=$cartProduct->getId();
					}
					
				}

				// clean up feedback product array to avoid duplicate products
				$feedbackProductIDs = array_unique($feedbackProductIDs);
				
				// get max feedback items
				$maxFeedbackItems=(int)Mage::getStoreConfig('getcustomerfeedback_section1/general/max_feedback_items',$orderStoreID);
				if (!is_numeric($maxFeedbackItems)) {
					$maxFeedbackItems=0;
				}
				$feedbackItemCount=0;
				
				// create the product html
				foreach ($feedbackProductIDs as $key => $item)
				{
				
				$cartProduct = Mage::getModel('catalog/product')->load($item);
					
					// get product attributes
					$cartProductID=$cartProduct->getId();
					$cartProductName=utf8_decode($cartProduct->getName());
					$cartProductImageURL=$cartProduct->getImageUrl();
					$cartProductImageURL=str_replace("https","http",$cartProductImageURL);
					$cartProductVisibility=$cartProduct->getVisibility();
					
					if ($cartProduct->getVisibility()=== "4") // products must be visible in search and catalogue
					{
						fwrite($fp1, '<tr>'. $newline);
						fwrite($fp1, '<td align="left" valign="top" style="font-size:11px; padding:3px 9px; border-bottom:1px dotted #CCCCCC;">'. $itemCount. '</td>'. $newline);
						fwrite($fp1, '<td align="center" valign="top" style="font-size:11px; padding:3px 9px; border-bottom:1px dotted #CCCCCC;"><img height="64" width="64" src="'. $cartProductImageURL. '"></td>'. $newline);
						fwrite($fp1, '<td align="left" valign="top" style="font-size:11px; padding:3px 9px; border-bottom:1px dotted #CCCCCC;">'. htmlentities($cartProductName). '</td>'. $newline);
						
						// these cart item properties are not being used yet...
						#fwrite($fp1, '<td>'. $item->getQtyToInvoice(). '</td>'. $newline);
						#fwrite($fp1, '<td>'. $item->getSku(). '</td>'. $newline);
						#fwrite($fp1, '<td>'. $item->getPrice(). '</td>'. $newline);
						/* YJC
						if (empty($emailFeedbackIconURL))
						{
							fwrite($fp1, '<td align="center" valign="top" style="font-size:11px; padding:3px 9px; border-bottom:1px dotted #CCCCCC;"><a href="'. Mage::getBaseUrl(). 'review/product/list/id/'. $cartProductID. '/#review-form'. $urlTrackingTags. '">Leave Feedback</a></td>'. $newline);
						} else {
							fwrite($fp1, '<td align="center" valign="top" style="font-size:11px; padding:3px 9px; border-bottom:1px dotted #CCCCCC;"><a href="'. Mage::getBaseUrl(). 'review/product/list/id/'. $cartProductID. '/#review-form'. $urlTrackingTags. '"><img src="'. $emailFeedbackIconURL. '"></a></td>'. $newline);						
						} */
						if (empty($emailFeedbackIconURL))
						{
							fwrite($fp1, '<td align="center" valign="top" style="font-size:11px; padding:3px 9px; border-bottom:1px dotted #CCCCCC;"><a href="'. Mage::getBaseUrl(Mage_Core_Model_Store:: URL_TYPE_WEB). 'review/product/list/id/'. $cartProductID. '/#review-form'. $urlTrackingTags. '">Leave Feedback</a></td>'. $newline);
						} else {
							fwrite($fp1, '<td align="center" valign="top" style="font-size:11px; padding:3px 9px; border-bottom:1px dotted #CCCCCC;"><a href="'. Mage::getBaseUrl(Mage_Core_Model_Store:: URL_TYPE_WEB). 'review/product/list/id/'. $cartProductID. '/#review-form'. $urlTrackingTags. '"><img src="'. $emailFeedbackIconURL. '"></a></td>'. $newline);						
						}
						
						fwrite($fp1, '</tr>'. $newline);
						$itemCount ++;
					}
					
					$feedbackItemCount++;
					
					if ($maxFeedbackItems > 1) 	// check max feedback item control, must be greater than 1 or feedback is empty.
					{
						
						if ($feedbackItemCount >= $maxFeedbackItems)
						{
							break;
						}
						
					}
				}
				
				fwrite($fp1, '</tbody>'. $newline);
				fwrite($fp1, '</table>'. $newline);
				fclose($fp1);
				fclose($fp2);

			} else {
			    throw new Exception('The GetCustomerFeedback module cache folder -'. $cacheFolder. ' is not writable, please check the folder permissions.');
			}
		} catch (Exception $e) {
			
		   if (empty($e))
			{
				$this->sendAlertEmail("An undefined error occurred preparing the customer feedback html cache file.");
			} else {
		   		$this->sendAlertEmail($e->getMessage());
			}
		}
		

	}

	// function called by Magento scheduling system to format and send feedback emails
	//
	public function GetCustomerFeedbackCron()
	{
	/* YJC adding this part to clean cache folder with cancelled or incorrect orders */
		try {
				$cacheFolder  = Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS;
				if (is_writable($cacheFolder)) 
				{
					// get list of files in cache folder
					require_once Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'classes'. DS . 'class.GetCustomerFeedback.Email.php';
					$fileArray=$this->getDirectoryList(Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS);
					$nbfiles = count($fileArray);
					if ($nbfiles > 0) // if files exist in cache folder then go get 'em...
					{
						sort($fileArray);	
						// get first file contents
						for ($file_index = 0; $file_index < $nbfiles; $file_index++)
						{
						/* YJC loop to clean folder with cancelled or incorrect orders */
						$newestOrderFile=Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. $fileArray[$file_index];
						$orderData=file($newestOrderFile);
									// determine order status
									$order = Mage::getModel('sales/order')->load(trim($orderData[5]));
									if (!$order->getEntityId()) 
									{
										// clean up  YJC inexisting order for any reason
										unlink($newestOrderFile);
										unlink(Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. trim($orderData[3]));							
									} 
									else {
										$orderStatus=$order->getStatus();
										if (empty($orderStatus))
										{
											// clean up YJC unknown status order
											unlink($newestOrderFile);
											unlink(Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. trim($orderData[3]));
										}
										else {										
										if ($orderStatus==="canceled")
											{
												// clean up YJC canceled order
												unlink($newestOrderFile);
												unlink(Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. trim($orderData[3]));
											}
										}
									}
						}
						/* /YJC loop to clean folder with cancelled or incorrect orders */
					}
					
				}
					
						
		}catch (Exception $e) {
					
				   if (empty($e))
					{
						$this->sendAlertEmail("An undefined error occurred preparing the customer feedback html cache file.");
					} else {
						$this->sendAlertEmail($e->getMessage());
					}
				}
	/* /YJC adding this part to clean cache folder with cancelled or incorrect orders */					
// YJC part to send an email of the first concerned order, working normally on correct orders, exceptions remain in case of modified order status  in the meantime
	try {
			$cacheFolder  = Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS;
			if (is_writable($cacheFolder)) 
			{
				// get list of files in cache folder
				require_once Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'classes'. DS . 'class.GetCustomerFeedback.Email.php';
				$fileArray=$this->getDirectoryList(Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS);

				if (count($fileArray) > 0) // if files exist in cache folder then go get 'em...
				{
					$newline="\n";
					sort($fileArray);	
					// get first file contents
					$newestOrderFile=Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. $fileArray[0];
					$orderData=file($newestOrderFile);
					// get store id from .dat file
					$storeID=trim($orderData[6]);
					// get customer name from .dat file
					$customerName=trim($orderData[1]);
					// catch empty customer name as this will break email
					if (empty($customerName)){ $customerName=$this->getTranslation('Customer',$storeID); }
					// get customer email address from .dat file
					$customerEmail=trim($orderData[2]);
					
						// determine time lapsed since order
						$elaspedHoursSinceOrder= floor((time()-(int)$orderData[4])/3600);
						// waiting period in days from config
						$emailNotificationWaitingPeriod=(int)Mage::getStoreConfig('getcustomerfeedback_section1/general/elapsed_time_from_order');
						// change from days to hours
						$emailNotificationWaitingPeriod=$emailNotificationWaitingPeriod*24;
						
						if ($elaspedHoursSinceOrder >= $emailNotificationWaitingPeriod)
						{
							// construct email
							// check for test mode
							if (Mage::getStoreConfig('getcustomerfeedback_section1/general/test_mode_enabled')) {
								$to = Mage::getStoreConfig('trans_email/ident_general/name'). ' <'. Mage::getStoreConfig('trans_email/ident_general/email'). '>';
							} else {
								$to = $customerName. ' <'. $customerEmail. '>';
							}
							
							// check for bcc mode
							$bcc=null;
							if (Mage::getStoreConfig('getcustomerfeedback_section1/general/bcc_emails_enabled')) {
								$bcc = Mage::getStoreConfig('trans_email/ident_general/name'). ' <'. Mage::getStoreConfig('trans_email/ident_general/email'). '>';
							}

							$storeID=(int)$orderData[6];
							// from address uses store sales address
							$from = Mage::getStoreConfig('trans_email/ident_sales/name'). ' <'. Mage::getStoreConfig('trans_email/ident_sales/email',$storeID). '>';

							// set email subject text
							$subject=Mage::getStoreConfig('getcustomerfeedback_section1/general/email_subject',$storeID);
							if (empty($subject)) {
								$subject = Mage::getStoreConfig('getcustomerfeedback_section1/general/email_footer_link',$storeID). ' : '. $this->getTranslation('Your Order',$storeID). ' # '. trim($orderData[0]);
							}
							
							$style='<style type="text/css">
body,td { color:#2f2f2f; font:11px/1.35em Verdana, Arial, Helvetica, sans-serif; }
a:visited {color: #000000}
</style>';
							
							$header='<body style="background:#F6F6F6; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; margin:0; padding:0;">
<div style="background:#F6F6F6; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; margin:0; padding:0;">
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
<td align="center" valign="top" style="padding:20px 0 20px 0">
<table bgcolor="#FFFFFF" cellspacing="0" cellpadding="10" border="0" width="650" style="border:1px solid #E0E0E0;">';
									
							$greeting = '<tr><td valign="top"><h1 style="font-size:22px; font-weight:normal; line-height:22px; margin:0 0 11px 0;"">'. $this->getTranslation('Hello',$storeID). ' '. $customerName. ',</h1>';
							$orderInfo = '<tr><td><h2 style="font-size:18px; font-weight:normal; margin:0;">'. $this->getTranslation('Your Order',$storeID). ' #'. trim($orderData[0]). '<small> ('. (date("j.n.Y h:i:s A",(int)$orderData[4])). ')</small></h2></td></tr>';
							$intro = '<p style="font-size:12px; line-height:16px; margin:0;">'. Mage::getStoreConfig('getcustomerfeedback_section1/general/email_text1',$storeID). '</p></tr></td>';
							$products = '<tr><td>'. file_get_contents (Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. trim($orderData[3])). '</td></tr>';
							$footer = '<tr><td><p>'. Mage::getStoreConfig('getcustomerfeedback_section1/general/email_text2',$storeID). '</p><p style="font-size:12px; margin:0 0 10px 0"></p></td></tr>
<tr>
<td bgcolor="#EAEAEA" align="center" style="background:#EAEAEA; text-align:center;"><center><p style="font-size:12px; margin:0;"><strong><a href="'. Mage::getBaseUrl(Mage_Core_Model_Store:: URL_TYPE_WEB).'">'. Mage::getStoreConfig('getcustomerfeedback_section1/general/email_footer_link',$storeID).'</a></strong></p></center></td>
</tr>
</tr>
</table>
</div>
</body>';
							
							// construct email html
							$body=$style.$header.$greeting.$intro.$orderInfo.$products.$footer;
							// if check status is set to yes
							if (Mage::getStoreConfig('getcustomerfeedback_section1/general/check_order_status'))
							{
								// determine order status
								$order = Mage::getModel('sales/order')->load(trim($orderData[5]));
								 
								if (!$order->getEntityId()) 
								{
									// clean up
									unlink($newestOrderFile);
									unlink(Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. trim($orderData[3]));
									throw new Exception('Error - GetCustomerFeedbackCron non existant order number: '.$orderData[0].' erasing file: '.$newestOrderFile.' and his companion .html');
								} /* YJC erased order or inexisting number for an unknown reason */
								
								$orderStatus=$order->getStatus();							
								if (empty($orderStatus))
								{
									// clean up
									unlink($newestOrderFile);
									unlink(Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. trim($orderData[3]));
									throw new Exception('Error - GetCustomerFeedbackCron unknown status or order number: '.$orderData[0].' erasing file: '.$newestOrderFile.' and his companion .html'); 
									/* YJC unknown status order */
								}
								
								if ($orderStatus==="complete")
								{
								// send get feedback email
								$oMail = new GetCustomerFeedbackMail($to,$from,$subject,$body,$bcc);
								if ($oMail->send())
								{
									// mail sent
									$oMail = null;
								} else {
									$oMail = null;
									// clean up
									unlink($newestOrderFile);
									unlink(Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. trim($orderData[3]));
									throw new Exception('An error occurred trying to send customer feedback email, command : '.$orderData[0].' erasing file: '.$newestOrderFile.' and his companion .html'); 
									/* YJC impossible to send email */
								}
								
								if (Mage::getStoreConfig('getcustomerfeedback_section1/general/test_mode_enabled')) {
									// dont update order if in test mode								
								} else {
									// add order note
									$order->addStatusToHistory($order->getStatus(), '<i>GetCustomerFeedback</i><br/>Customer feedback email sent to <strong>'. $customerEmail. '</strong>.', true);
									$order->save();
								}
								
								// clean up
								unlink($newestOrderFile);
								unlink(Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. trim($orderData[3]));
								return;
								
								} else {
									if ($orderStatus==="cancelled")
									{
										// clean up
										unlink($newestOrderFile);
										unlink(Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. trim($orderData[3]));
									}
								
									// wait until order status is complete
									return;
								}
								
							} else {
								// send get feedback email
								$oMail = new GetCustomerFeedbackMail($to,$from,$subject,$body);
								if ($oMail->send())
								{
									// mail sent
									$oMail = null;
								} else {
									$oMail = null;
									// clean up
									unlink($newestOrderFile);
									unlink(Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. trim($orderData[3]));
									throw new Exception('An error occurred trying to send customer feedback email, order: '.$orderData[0].' erasing file: '.$newestOrderFile.' and the companion .html file'); 
									/* YJC impossible to send email */
								}
								
								if (Mage::getStoreConfig('getcustomerfeedback_section1/general/test_mode_enabled')) {
									// dont update order if in test mode
								} else {
								// add order note
									$order = Mage::getModel('sales/order')->load(trim($orderData[5]));
									$order->addStatusToHistory($order->getStatus(), '<i>GetCustomerFeedback</i><br/>Customer feedback email sent to <strong>'. $customerEmail. '</strong>.', true);
									$order->save();
								}
								
								// clean up
								unlink($newestOrderFile);
								unlink(Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS . 'cache'. DS. trim($orderData[3]));
								return;
							}
							
						} else {
							// wait until elasped time correct
							return;
						}
				
				} // files to process check
				
			} else { // folder permissions check
			    throw new Exception('The GetCustomerFeedback module cache folder -'. $cacheFolder. ' is not writable, please check the folder permissions.');
			}
							
		} catch (Exception $e) {
			
		   if (empty($e))
			{
				$this->sendAlertEmail("An undefined error occurred preparing the customer feedback html cache file.");
			} else {
		   		$this->sendAlertEmail($e->getMessage());
			}
		}
	}
	
	// function to return filenames in a directory as an array
	//
	public function getDirectoryList ($directory) 
	{
	  // create an array to hold directory list
	  $results = array();
	  // create a handler for the directory
	  $handler = opendir($directory);
	  // open directory and walk through the filenames
	  while ($file = readdir($handler)) {
	    // read files and match against the files we are interested in
	    if (substr($file, 0, 19) === "GetCustomerFeedback") {
			if (substr($file, -4) === ".dat") {
				$results[] = $file;
			}
	    }
	  }
	  // tidy up: close the handler
	  closedir($handler);
	  // return array containing filenames
	  return $results;
	}
	

	// function to return translations from locale file
	//
	public function getTranslation($word,$storeID=1) 
	{
	  $translationFile=Mage::getModuleDir('', 'PAJ_GetCustomerFeedback') . DS. 'translate_store_id_'. (string)$storeID. '.txt';
	  
		if (file_exists($translationFile))
		{
	  
		  $file_handle = fopen($translationFile, "rb");

			while (!feof($file_handle) ) {
			$line_of_text = fgets($file_handle);
			$parts = explode('=', $line_of_text);
			
				if ($parts[0]===$word)
				{
					$word=trim($parts[1]);
					break;
				}
			}
			fclose($file_handle);
		}
		
		return $word;
	}	


	public function sendAlertEmail($message)
    {
		if (Mage::getStoreConfig('getcustomerfeedback_section1/general/send_alert_email')) {
			$message = wordwrap($message, 70);
			$from = Mage::getStoreConfig('trans_email/ident_general/email');
			$headers = "From: $from";

			mail(Mage::getStoreConfig('trans_email/ident_general/email'), 'Alert from Get Customer Feedback module', $message, $headers);
		}
	}
	
// class
}

?>