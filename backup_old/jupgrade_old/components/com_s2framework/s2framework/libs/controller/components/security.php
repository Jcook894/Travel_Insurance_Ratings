<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/


defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class SecurityComponent extends S2Component 
{
    function startup(&$controller) 
    {
        $controller->invalidToken = true;
        
        $token = cmsFramework::getToken();

        Sanitize::getString($controller->params['form'],$token) and $controller->invalidToken = false;    
    }
}
