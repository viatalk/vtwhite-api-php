<?php

require_once(dirname(__FILE__).'/vtwhiteapi.php');

$vtwhite = new VTWhiteProvisioningAPI('username', 'password');

$req = $vtwhite->GetNumbers(array('state' => 'DC'));
//$req = $vtwhite->AddNumber(202, 521, 'test');
//$req = $vtwhite->RemoveNumber(2025211111);

print_r($req->return);