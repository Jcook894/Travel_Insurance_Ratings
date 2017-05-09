<?php
/**
 * jReviews - Reviews Extension
 * Copyright (C) 2006-2009 Alejandro Schmeichler
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 **/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class SecurityComponent extends S2Component {
		
	var $invalidToken = false;
	
	function startup(&$controller) {

		if(isset($controller->data) && !empty($controller->data)) 
		{
			$this->data = & $controller->data;

			$tokenKeys = cmsFramework::getToken(false);

			#Validate token
			if (!isset($this->data['__Token']['Key']) || !in_array($this->data['__Token']['Key'], $tokenKeys['Keys'])) {				
				
				if(!$controller->xajaxRequest) {
					echo '<script>alert("Invalid Token")</script>';
					exit;
				} else {
					// pass back to xajax controller action for alert
					$controller->invalidToken = true;
					return;
				}
			} 

			# Delete used token from session and post data
			cmsFramework::removeToken($this->data['__Token']['Key']);
			unset($this->data['__Token']);
			unset($this->data['__raw']['__Token']);
		}
	}
	
	/**
	 * Used in xajax forms when validation fails because the original token is destroyed
	 *
	 */
	function reissueToken() {
		return cmsFramework::getToken();
	}
}