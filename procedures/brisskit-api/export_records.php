<?php
/////////////////////////////////////////////////////////////////
//Have a go at building a lookup for the data in the results sets
//instead of returning meaningful data it generally returns codes
//that correspond to their real meaning. 
//
//Olly Butters 23/9/11
/////////////////////////////////////////////////////////////////

//the class that performs the API call
require_once('RestCallRequest.php');

//0643BE0462C35EAD8032DE004505F584 - no_priv
//$token="0F5B9179EBFA4B77AF9B528692B8E6A0"; // - some_priv
//$token="5C255C3A25E712994139193FF5533B04"; //some_priv/briccs
//806C0555BCF08E2E62492F453B7A2844 - olly
$token="73D3F14B991C65A2760A2A00513E54A8"; //olly/briccs


//header("Content-type: text/xml; charset=utf-8");

////////////////////////////////////////////////////////
//Metadata
$options = array('content' => 'metadata', 'format' => 'xml', 'token' => $token);

//create a new API request object
$request = new RestCallRequest("localhost/api/", 'POST', $options);

//initiate the API request
$request->execute();

//Get the metadata
$metadata_xml=$request->getResponseBody();

//$metadata = new simplexmlelement($metadata_xml);
//echo $metadata->getName()."<br/>";
//echo $metadata->children()->getName();

$metadata = simplexml_load_string($metadata_xml);

$number_of_items = count($metadata->item);
echo $number_of_items." items to process\n";

//Cycle through each item (field) and build the array
for($i=0;$i<$number_of_items;$i++)
{
  echo "|".$metadata->item[$i]->field_name;
  echo "|".$metadata->item[$i]->field_label;
  echo "|".$metadata->item[$i]->field_type;

  $field_name = $metadata->item[$i]->field_name;
  $field_type = $metadata->item[$i]->field_type;


  //A process field is set as being 1 if it needs to be looked up
  //and zero otherwise.

  if($field_type == 'text')
    {
      //field is text. This may have been validated against a regex 
      $metadata_lookup["$field_name"]['process']=0;
    }
  elseif($metadata->item[$i]->field_type == 'yesno')
    {
      //field is a simple yes/no
      $metadata_lookup["$field_name"]['process']=1;

      $metadata_lookup["$field_name"]['key'][1]='yes';
      $metadata_lookup["$field_name"]['key'][0]='no';
    }
  elseif($metadata->item[$i]->field_type == 'radio')
    {
      //field is a set of radio buttons

      //field is a simple yes/no
      $metadata_lookup["$field_name"]['process']=1;

      // \n separated list
      $options_string = $metadata->item[$i]->select_choices_or_calculations;

      $options_list = explode("\\n",$options_string);
      
      foreach($options_list as $this_option)
	{
	  //comma separated now
	  $temp = explode(",",$this_option);
	  $metadata_lookup["$field_name"]['key'][trim($temp[0])]=trim($temp[1]);
	}
    }
  elseif($metadata->item[$i]->field_type == 'sql')
    {
      //field is derived from an SQL SELECT query. I can get the query, but not
      //the values it returned here. So the query will need to be parsed and run....
      $metadata_lookup["$field_name"]['process']=-1;
      echo "Bugger!\n";
    }
  elseif($metadata->item[$i]->field_type == 'descriptive')
    {
      //This is just for displaying headers etc on the form - there isnt
      //actually any data for this field.
    }
  else
    {
      echo "Unexpected data type!\n";
      exit();
    }

  echo "\n";
}

echo "<hr/>\n";

//echo $metadata_lookup['pid']['process'];


print_r($metadata_lookup);

echo "<hr/>\n";

//print_r($metadata);


//foreach($metadata->children() as $child)
//{
//      echo $child->getName() . ": " . $child . "<br />";
//}

//print the data to the screen
//echo $metadata_xml;







///////////////////////////////////////////////////////
//Records
$options = array('content' => 'record', 'type' => 'flat', 'format' => 'xml', 'token' => $token);

//create a new API request object
$request = new RestCallRequest("localhost/api/", 'POST', $options);

//initiate the API request
$request->execute();


$data_xml = $request->getResponseBody();
$data = simplexml_load_string($data_xml, 'SimpleXMLElement', LIBXML_NOBLANKS);

$number_of_items = count($data->item);
echo $number_of_items." items to process\n";
//Cycle through each item (field) and build the array
for($i=0;$i<$number_of_items;$i++)
{
  $number_of_subitems = count($data->item[$i]);
  echo $number_of_subitems." subitems to process\n";

  echo "<item>\n";

  foreach ($data->item[$i]->children() as $child)
  {
    //Only want the data if it is not blank. 
    if($child != "")
    {
      $node_name = $child->getName();
      
      if(!$metadata_lookup["$node_name"]['process'] != 1)
      {
	//echo $node_name. " ". $child . " LOOKUP '".$metadata_lookup["$node_name"]['key']["$child"]."' <br />\n";
	echo "  <".$node_name.">".$metadata_lookup["$node_name"]['key']["$child"]."</".$node_name.">\n";	
      }
      else
      {
	//echo $node_name. " ". $child . "<br />\n";
	echo "  <".$node_name.">".$child."</".$node_name.">\n";
      }
    }    
  }
  echo "</item>\n";
}


echo "startdata:\n".$data_xml."\n:enddata";
