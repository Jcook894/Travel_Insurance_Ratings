<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.user.authentication');
jimport('joomla.user.helper');

/**
 * Joomla Picture Manager authentication service.
 */
class Authentication
{
    private $params;

    public function Authentication()
    {
        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "Authentication");
        
//        $this->loadParams();

//        if (!$this->params->get('publish', 1))
//        {
//        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "Authentication3");
//            trigger_error('This plugin is unpublished.');
//        }
    }

	// NOTE - not used for now
    private function loadParams()
    {
        if (empty($this->params))
        {
            $config_path = JPATH_BASE . DS . 'components' . DS . 'com_jepumextension' . DS . 'authentication' . DS . 'authentication.xml';
            
            $db = JFactory::getDBO();

			// Joomla 1.5
//            $query = 'SELECT params FROM #__plugins WHERE folder="com_jepumextension" AND element="authentication"';

			// Joomla 1.6
            $query = 'SELECT params FROM #__extensions WHERE folder="com_jepumextension" AND element="authentication"';
            $db->setQuery($query);

//            $this->params = new JParameter($db->loadResult());
        }

        return true;
    }

    /**
     * Checks to see who is currently logged in through this session.
     * 
     * @returns The username of the logged in user
     */
    public function getAuthUser()
    {
        $user = JFactory::getUser();

        return $user->name;
    }

    public function login($username, $password)
    {
        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "Trying to log in with: " . $username . " - pass: " . $password);

//		$db = & JFactory::_createDBO();
		$db = JFactory::getDBO();

		$query = 'SELECT id, password FROM #__users WHERE username=' . $db->Quote($username);
		$db->setQuery( $query );

		$result = $db->loadObject();

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "result from database: " . $result->password);

		$parts = explode( ':', $result->password);
		$crypt = $parts[0];
        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "crypt: " . $crypt);
		$salt = @$parts[1];
        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "salt: " . $salt);
		$testcrypt = JUserHelper::getCryptedPassword($password, $salt);

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "testcrypt: " . $testcrypt);

		if ($testcrypt == $crypt) {
			return true;
		} else {
			return false;
		}
	}

    /**
     * Logs in the current user
     * @param A username
     * @param The user's password
     * @returns True on success
     */
    public function login_old($username, $password)
    {
        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "Trying to log in with: " . $username . " - pass: " . $password);

        $credentials = array (
            'username' => $username,
            'password' => $password
        );
        $options = array();

        $authenticate = & JAuthentication::getInstance();
        $response     = $authenticate->authenticate($credentials, $options);

        $this->debug(__FILE__." (".__LINE__.") ".__CLASS__.":".__FUNCTION__, "login " . ($response->status === JAUTHENTICATE_STATUS_SUCCESS) ? "successful" : "unsuccessful");
        
        if ($response->status === JAUTHENTICATE_STATUS_SUCCESS)
        {
            return true;
        }

        return false;

        /*
        global $mainframe;
        return $mainframe->login($credentials, $options);
        */
    }
    
    /**
     * Logs out the current user
     * @returns True if no errors
     */
    public function logout()
    {
//      $app = JAMFPHP::getInstance('amfphp');
//      Joomla does not yet allow us to register 3rd party clients
//      So we have to use $mainframe instead of $app
        global $mainframe;

        $mainframe->logout();

        return true;
    }

    /**
     * Logs a message and file info into debug log text file.
     * 
     * @param $info
     * @param $msg
     */
    function debug($info, $msg)
    {
//        file_put_contents("../../../logs/debuglog.txt", $info.": ".$msg."\n", FILE_APPEND);
    }
}
?>
