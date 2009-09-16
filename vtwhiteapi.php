<?php
/**
 * VTWhite API
 *
 * Package description
 *
 * @package     VTWhiteAPI
 * @author      Joshua Priddle <itspriddle@nevercraft.net>
 * @copyright   Copyright (c) 2009, ViaTalk, LLC
 * @link        http://github.com/itspriddle/vtwhite-api
 * @version      1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * VTWhiteAPI
 * 
 * This is the core class used to interface with VTWhite's API
 *
 * @package     VTWhiteAPI
 * @author      Joshua Priddle <itspriddle@nevercraft.net>
 */


class VTWhiteAPI {

  var $curl_method = 'curl_php'; // Use curl_unix to use /usr/bin/curl
  
  function __construct($username = NULL, $password = NULL, $test = FALSE)
  {
    if (isset($username) && isset($password))
    {
      $this->api_username = $username;
      $this->api_password = $password;
    }
  }
  
  // --------------------------------------------------------------------

  function send($function, $args = array(), $return = 'output')
  {
    
    $xml = $this->_create_xml_request($function, $args);
    
    if ($this->curl_method == 'curl_php')
    {
      $res = $this->_curl_php($xml);
    }
    elseif ($this->curl_method == 'curl_unix')
    {
      $res = $this->_curl_unix($xml);
    }
    else
    {
      die("Undefined cURL library.  Aborting.\n");
    }

    
    $parsed = $this->parse_response($res);

    if ($return == 'output')
    {
      return $parsed;
    }
    elseif ($return == 'status')
    {
      if (strtoupper($parsed->success) == 'TRUE')
      {
        return TRUE;
      }
      elseif ($parsed->error != '')
      {
        return $parsed->error;
      }

    }
  }
  
  // --------------------------------------------------------------------

  function parse_response($res)
  {
    if ( ! function_exists('simplexml_load_string'))
    {
      die("Error: function `simplexml_load_string` not found.\n");
    }
    
    /**
     * VTWhite's API returns results as <1>, <2>, etc
     * This causes problems for simplexml_load_string
     * as PHP vars cannot begin with numbers.
     * We'll use this simple preg_replace to convers
     * <1> to <result_1>, etc
     */

    $res = preg_replace("/\<([0-9]+)\>/", "<result_\\1>", $res);
    $res = preg_replace("/\<\/([0-9]+)\>/", "</result_\\1>", $res);

    $xml = simplexml_load_string($res);
    return $xml->data;
  }
  
  // --------------------------------------------------------------------

  function _create_xml_request($function, $args = array())
  {
    $out =  "<packet>\n".
            "  <auth>\n".
            "    <user>{$this->api_username}</user>\n".
            "    <pass>{$this->api_password}</pass>\n".
            "  </auth>\n".
            "  <function>$function</function>\n".
            "  <data>\n";
        
    foreach ($args as $key => $value)
    {
      $out .= "    <$key>$value</$key>\n";
    }
    
    $out .= "  </data>\n".
        "</packet>";
    return $out;
  }
  
  // --------------------------------------------------------------------
  
  function _curl_unix($post_data)
  {
    exec("/usr/bin/curl -k -H \"Content-Type: application/x-www-form-urlencoded\" -d \"packet=$post_data\" {$this->api_uri}", $h);
    return join("\n", $h);
  }
  
  // --------------------------------------------------------------------
  
  function _curl_php($post_data)
  {
    if ( ! function_exists('curl_init'))
    {
      die("Error: Required function `curl_init` not found!\n");
    }  
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->api_uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "packet=$post_data");
    
    $res = curl_exec($ch);
    curl_close($ch);

    return $res;

  }
  
  // --------------------------------------------------------------------
  
}

// ------------------------------------------------------------------------

class VTWhiteProvisioningAPI extends VTWhiteAPI {

  function __construct($username = NULL, $password = NULL, $test = FALSE)
  {
    parent::__construct($username, $password);

    $this->api_uri = ($test) ? 'https://api.vtwhite.com/provisioning/testprovisioning.api.php' 
                             : 'https://api.vtwhite.com/provisioning/provisioning.api.php';

  }
  
  // --------------------------------------------------------------------

  function GetNumbers($args = array())
  {
    if ( ! array_key_exists('npa', $args) && ! array_key_exists('nxx', $args) && ! array_key_exists('state', $args))
    {
      die("Invalid input:  GetNumbers argument requires an associative array containing one of the following keys: npa, nxx, or state\n");
    }
    return $this->send('GetNumbers', $args);
  }
  
  // --------------------------------------------------------------------

  function AddNumber($npa, $nxx, $route)
  {
    if ( ! preg_match("/^[2-9][0-8][0-9]$/", $npa) || ! preg_match("/^[2-9][0-9]{2}$/", $nxx))
    {
      die("Invalid input: AddNumber function requires a valid NPA ([2-9][0-8][0-9]) and NXX ([2-9][0-9][0-9])\n");
    }
    if ( ! isset($route) || trim($route) == '')
    {
      die("Invalid input: AddNumber function requires a valid route\n");
    }
    return $this->send('AddNumber', array('npa' => $npa, 'nxx' => $nxx, 'route' => $route));
  }
  
  // --------------------------------------------------------------------

  function RemoveNumber($number)
  {
    if ( ! preg_match("/^[2-9][0-8][0-9][2-9][0-9]{2}[0-9]{4}$/", $number))
    {
      die("Invalid input: RemoveNumber function requires a valid 10-digit US phone number\n");
    }
    return $this->send('RemoveNumber', array('number' => $number), 'status');
  }
  
  // --------------------------------------------------------------------

}

// ------------------------------------------------------------------------

class VTWhite911API extends VTWhiteAPI {

  function __construct($username = NULL, $password = NULL, $test = FALSE)
  {
    parent::__construct($username, $password);
    $this->api_uri = ($test) ? 'https://api.vtwhite.com/provisioning/test-911.api.php' 
                             : 'https://api.vtwhite.com/provisioning/911.api.php';
  }
  
  // --------------------------------------------------------------------

  function Query()
  {

  }
  
  // --------------------------------------------------------------------

  function Update()
  {

  }
  
  // --------------------------------------------------------------------

  function Validate()
  {

  }
  
  // --------------------------------------------------------------------

  function Remove()
  {

  }
  
  // --------------------------------------------------------------------

}


