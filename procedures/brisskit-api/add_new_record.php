<?php
//Add a new entry to REDCap for this API token.
//27/3/2012
//Olly Butters

//the class that performs the API call
require_once('RestCallRequest.php');

//Collection of API tokens needed to get data from REDCap
$token="FD72BD71353EE1ABAAD4B641BA8245F5"; //olly/BRISSkit sample/eduserv


$new_participant = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><records><item><brisskit_id>123</brisskit_id></item></records>";

$data = array('content' => 'record', 'type' => 'flat', 'format' => 'xml', 'token' => $token, 'data' => $new_participant);

# create a new API request object
$request = new RestCallRequest("localhost/redcap/api/", 'POST', $data);

# initiate the API request
$request->execute();

# the following line will print out the entire HTTP request object 
# good for testing purposes to see what is sent back by the API and for debugging 
echo '<pre>' . print_r($request, true) . '</pre>';

# print the output from the API 
echo $request->getResponseBody();



?>
