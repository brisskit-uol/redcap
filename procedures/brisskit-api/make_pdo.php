<?php
/////////////////////////////////////////////////////////////////
//Have a go at building a patient data object from the XML
//output of REDCap. 
//The data in REDCap is in user defined 'forms', the definition
//of the forms comes from a 'metadata' request to the API, and the
//patient data from from a 'record' API request. So the metadata
//has to be collected and parsed in order to understand the records.
//
//The PDO is based on what is in:
//https://www.i2b2.org/software/projects/datarepo/CRC_Design_15.pdf
//
//The PDO model has tables with concepts and providers in, these map
//map to an ontology and name/location of who is taking the data. I
//am of the opinion that these tables should be fully populated BEFORE
//an import from REDCap is done. Then the patient/observation/visit
//bit of data can be cross referenced against these existing tables.
//The alternative of having to define the concept and provider in
//every import seems rather silly!
//
///////////////////////////////////////////////////////////////////
//REQUIREMENTS IN REDCAP FORM BUILDING
// - name of person filling in the form
// - date/time the form was started
// - patient id
//
///////////////////////////////////////////////////////////////////
//ISSUES:
//-No obvious way to figure out where data belongs i.e. is it a 
// patient detail or an observation or an event etc etc. Can SNOMED 
// codes solve this? Otherwise I think prepending variable names
// in REDCap is the only way to go. 
//-Do I want to figure out the data type on the fly? Ironically
// text boxes are the only ones I can be sure are numbers (the
// validation gives it away), but others (e.g. dropdown lists)
// could be. In fact those could have e.g. 'greater than' properties
// which is something that i2b2 can handle. But what if someone puts
// a number in a text box once, how can we then compare against the
// rest of the patients where it is text?
//
///////////////////////////////////////////////////////////////////
//
//I'd like to see this as a progress page, with e.g. a table with
//a row for each entry and a column for each step that gets updated
//as each step is done. Any errors can then be highlighted and 
//clicked on to fix. 
//
/////////////////////////////////////////////////////////////////
//Olly Butters 6/10/11
/////////////////////////////////////////////////////////////////

//This sticks an auto reload on the page - useful for developing
?>
<head>
<META HTTP-EQUIV="Refresh" CONTENT="1; URL=http://localhost/redcap_api_examples/make_pdo.php">
</head>

<?php

//the class that performs the API call
require_once('RestCallRequest.php');

//Collection of API tokens needed to get data from REDCap
//0643BE0462C35EAD8032DE004505F584 - no_priv
//$token="0F5B9179EBFA4B77AF9B528692B8E6A0"; // - some_priv
//$token="5C255C3A25E712994139193FF5533B04"; //some_priv/briccs
//806C0555BCF08E2E62492F453B7A2844 - olly
//$token="73D3F14B991C65A2760A2A00513E54A8"; //olly/briccs
$token="C837FD6FC94C2CD463C69BBCEF0497E5"; //olly/briccs/home


////////////////////////////////////////////////////////
//Metadata
//The form definitions. Here I essentially build a lookup
//array which defines how the records are processed.
//The following parameters in each case:
//
//$metadata_lookup[field_name][form_name]   - Text - Name of the form this field is from
//$metadata_lookup[field_name][number]      - Bool - If this field is a number
////////////////////////////////////////////////////////
$options = array('content' => 'metadata', 'format' => 'xml', 'token' => $token);

//create a new API request object
$request = new RestCallRequest("localhost/api/", 'POST', $options);

//initiate the API request
$request->execute();

//Get the metadata
$metadata_xml=$request->getResponseBody();
//echo $metadata_xml;

$metadata = simplexml_load_string($metadata_xml);

$number_of_items = count($metadata->item);
echo $number_of_items." items to process\n";

//Cycle through each item (field) and see if it is a number or text field.
for($i=0;$i<$number_of_items;$i++)
{
  echo "<br/>|".$metadata->item[$i]->field_name;
  echo "|".$metadata->item[$i]->field_label;
  echo "|".$metadata->item[$i]->field_type;
  echo "|".$metadata->item[$i]->text_validation_type_or_show_slider_number;
  echo "|";

  $field_name = $metadata->item[$i]->field_name;
  $field_type = $metadata->item[$i]->field_type;

  //Which form this was from
  $temp = $metadata->item[$i]->form_name;
  $metadata_lookup["$field_name"]['form_name'] = "$temp";

  //Lets make a list of all the form names
  $form_name_list[] = "$temp";

  if($field_type == 'text')
    {
      //field is text. This may have been validated against a regex 

      //Only text fields can explicitly say something is numeric, ironicly the rest are text.
      if($metadata->item[$i]->text_validation_type_or_show_slider_number == "number" || 
         $metadata->item[$i]->text_validation_type_or_show_slider_number == "integer")
	{
	  $metadata_lookup["$field_name"]['number']=1;
	}
      else
	{
	  $metadata_lookup["$field_name"]['number']=0;
	}

    }
  elseif($metadata->item[$i]->field_type == 'yesno')
    {
      //field is a simple yes/no

      $metadata_lookup["$field_name"]['number']=0;
    }
  elseif($metadata->item[$i]->field_type == 'radio' || $metadata->item[$i]->field_type == 'dropdown')
    {
      //field is a set of radio buttons

      $metadata_lookup["$field_name"]['number']=0;
    }
  elseif($metadata->item[$i]->field_type == 'sql')
    {
      //Data from an SQL query

      $metadata_lookup["$field_name"]['number']=0;
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


//Our list of form names has many duplicates in it, so lets get rid of them
$form_name_list = array_unique($form_name_list);

//Print the list of form names
echo "<hr/>\n";
echo "<h2>Form name list</h2>";
$temp = "<pre>";
$temp .= print_r($form_name_list,1);
$temp .= "</pre>";
echo $temp;
echo "<hr/>\n";


//Print the look up array 
echo "\n<h2>Meta data look up array</h2>";
$temp = "<pre>";
$temp .= print_r($metadata_lookup,1);
$temp .= "</pre>";
echo $temp;
echo "<hr/>\n";


//print the data to the screen
//echo $metadata_xml;







///////////////////////////////////////////////////////
//Records
//This is the actual patient data
///////////////////////////////////////////////////////
//The rawOrLabel flag does the look up for me to get the real data out.
$options = array('content' => 'record', 'type' => 'flat', 'format' => 'xml', 'rawOrLabel' => 'label', 'token' => $token);

//create a new API request object
$request = new RestCallRequest("localhost/api/", 'POST', $options);

//initiate the API request
$request->execute();


$data_xml = $request->getResponseBody();

//By getting rid of the blank XML elements here we dont have to deal with 
//empty fields that haven't been filled in due to branching logic
//excluding them.
$data = simplexml_load_string($data_xml, 'SimpleXMLElement', LIBXML_NOBLANKS);


//////////////////////////////////////////////////////////////////
//Ideally we would have sanitized all the data by now and made 
//sure all the required fields are present.

$number_of_patients = count($data->item);
echo $number_of_patients." patients to process<br/>\n";

//Cycle through each set of forms. 
//A key point here is that I pick out the fields that I expect to be there for
//non-observations, use them, then unset them. Everything that is then left
//gets thrown in as an observation.
for($patient_number=0;$patient_number<$number_of_patients;$patient_number++)
{
  echo "<hr/>Patient number: ".$patient_number."<br/>";

  //Get rid of the list of concept and observer codes used to hack the PDO fr i2b2
  unset($concept_cd_list);
  unset($observer_cd_list);

  //The data is split into different dimension tables, a difficult
  //problem is going to be a way of making it obvious which dimension
  //a field belongs to. For instance how will I know that date of
  //birth is about the patient and not an observation? This will 
  //either have to fall out of the SNOMED code, or have a prefix
  //in the variable name.


  //Lets check that each of the forms has been filled in before we process this patient.
  //If they haven't then we shall skip this one and hope it will be filled in later...
  //I am assuming that each form will always have "_complete" appended to it to indicate
  //it has been filled in properly.
  foreach($form_name_list as $this_form_name)
    {
      $this_form_name_complete = $this_form_name."_complete";
      if($data->item[$patient_number]->$this_form_name_complete == "Complete")
	{
	  echo $this_form_name." complete :)<br/>\n";
	  unset($data->item[$patient_number]->$this_form_name_complete);
	}
      else
	{
	  echo $this_form_name." IS NOT COMPLETE. SKIPPING.<br/>\n";
	  exit();
	}
    }
  
  
  //Lets start to build a PDO... Based on what is in:
  //https://www.i2b2.org/software/projects/datarepo/CRC_Design_15.pdf
  
  //Define where this data came from
  $sourcesystem_cd = "redcap_import_v0.1";
  
  //When this data was imported into i2b2 YYYY-MM-DDTHH:MM:SS
  $import_date = date("Y-m-d\TH:i:s");
  

  //Figure out who filled the form in - it would be nice if this was pulled from the API/DB not as a form field.
  $nurse_name = $data->item[$patient_number]->nurse_name;
  //Why do I need to do this?????!
  $nurse_name = "$nurse_name";
  unset($data->item[$patient_number]->nurse_name);

  //Figure when the form was filled in.
  $redcap_start_date = $data->item[$patient_number]->redcap_start_date;
  //Why do I need to do this?????!
  $redcap_start_date = "$redcap_start_date";
  $redcap_start_date = preg_replace('/ /','T',$redcap_start_date);  
  unset($data->item[$patient_number]->redcap_start_date);

  
  //pid gets used a lot, so lets get it here and make
  //sure it is correct
  if(!isset($data->item[$patient_number]->pid))
  {
    echo "pid not found in the patient data! Giving up.\n";
    exit();
  }
  $pid = $data->item[$patient_number]->pid;
  //Why do I need to do this?????!
  $pid = "$pid";

  //Check its a number
  if(!ctype_digit($pid))
  {
    echo "pid has non-numeric characters in it! Giving up.\n";
    exit();
  }

  //It is possible that this pid already exists in i2b2, so somehow
  //we need to check it doesnt. 
  unset($data->item[$patient_number]->pid);



  //A lot of the data is linked together based on the event id. Rather than
  //making one up on the input forms lets make one up here! A combination
  //of pid and redcap_start_date should give a nice unique event number...
  $temp=preg_replace('/-/','',$redcap_start_date);  
  $temp=preg_replace('/ /','',$temp);  
  $temp=preg_replace('/:/','',$temp);
  $calculated_event_id=$pid.$temp;

  //Start to actually build the PDO
  //Header - uncertain what this means...
  $pdo  = "<repository:patient_data xmlns:repository=\"\">\n";
  
  
  ////////////////////////////////////////////////////////////
  //Event set 
  //Essentially describes a visit (ie when questionare was done)
  //visit_num must be UNIQUE
  //start_date NOT nullable
  //end_date nullable
  //active_status_cd F/P/A/null
  ////////////////////////////////////////////////////////////
  $pdo .= "  <event_set>\n";
  $pdo .= "    <event sourcesystem_cd=\"$sourcesystem_cd\" import_date=\"$import_date\">\n";
  $pdo .= "      <event_id source=\"redcap_import_script\">$calculated_event_id</event_id>\n";
  $pdo .= "      <patient_id source=\"redcap\">$pid</patient_id>\n";
  $pdo .= "      <start_date>$redcap_start_date</start_date>\n";
  $pdo .= "      <end_date></end_date>\n";
  $pdo .= "      <active_status>F</active_status>\n";
  $pdo .= "      <param name=\"site\">GH</param>\n";
  $pdo .= "    </event>\n";
  $pdo .= "  </event_set>\n\n";

    
  //////////////////////////////////////////////////////////
  //pid set
  //The pid is probably the most important bit of information
  //so lets make doubley sure it is ok!
  //////////////////////////////////////////////////////////
  $pdo .= "  <pid_set>\n";
  $pdo .= "    <patient_id source=\"redcap\">".$pid."</patient_id>\n";
  $pdo .= "  </pid_set>\n\n";
  
  
  //////////////////////////////////////////////////////////
  //eid set
  //event id
  //////////////////////////////////////////////////////////
  $pdo .= "  <eid_set>\n";
  $pdo .= "";
  $pdo .= "  </eid_set>\n\n";
  
  
  
  ////////////////////////////////////////////////////////////////
  //Patient set
  ////////////////////////////////////////////////////////////////
  //All the information about the patient eg age, race, religion etc. NOT MEDICAL DATA.
  //There are four REQUIRED fields - see p15 of Data Repository (CRC) cell document.
  //- patient_id
  //- birth_date nullable
  //- death_data nullable
  //- vital_status_cd N/Y/M/X
  $pdo .= "  <patient_set>\n";
  $pdo .= "    <patient sourcesystem_cd=\"$sourcesystem_cd\" import_date=\"$import_date\">\n";

  $pdo .= "      <patient_id source=\"redcap\">$pid</patient_id>\n";
  $pdo .= "      <birth_date>".$data->item[$patient_number]->birth_date."</birth_date>\n";
  $pdo .= "      <vital_status_cd>".$data->item[$patient_number]->vital_status_cd."</vital_status_cd>\n";
  $pdo .= "      <death_date>".$data->item[$patient_number]->death_date."</death_date>\n";
  
  
  //Delete these nodes so they dont turn up in the observation set 
  unset($data->item[$patient_number]->birth_date);
  unset($data->item[$patient_number]->vital_status_cd);
  unset($data->item[$patient_number]->death_date);
  
  //Now follows all the user defined fields in the format
  //<param name="variable_name">value</param> 
  //***********************************************************************
  
  //THIS IS HARD CODED IN - NEEDS TO BE SMARTER
  $pdo .= "      <param name=\"name\">".$data->item[$patient_number]->name."</param>\n";
  unset($data->item[$patient_number]->name);
  
  $pdo .= "    </patient>\n";
  $pdo .= "  </patient_set>\n\n";
  ////////////////////////////////////////////////////////////////
  
  
  
  ////////////////////////////////////////////////////////////////
  //observation set
  ////////////////////////////////////////////////////////////////
  //The actual data gets dealt with here, each question gets to be
  //its own observation
  $pdo .= "  <observation_set>\n";
  
  
  $number_of_subitems = count($data->item[$patient_number]);
  
  foreach ($data->item[$patient_number]->children() as $child)
    {
      //Only want the data if it is not blank. 
      if($child != "")
	{
	  $node_name = $child->getName();
	  
	  //foreach bit of data. Essentially each question that is not patient meta-data.
	  $pdo .= "    <observation sourcesystem_cd=\"$sourcesystem_cd\" import_date=\"$import_date\">\n";
	  //Required fields
	  $pdo .= "      <event_id>$calculated_event_id</event_id>\n";
	  $pdo .= "      <patient_id>$pid</patient_id>\n";
	  $pdo .= "      <concept_cd>$node_name</concept_cd>\n";  //This *really has to be* the SNOMED code...

	  //For now, as a hack, to keep i2b2 happy I will make a concept set....
	  $concept_cd_list[] = $node_name;

	  $pdo .= "      <observer_cd>$nurse_name</observer_cd>\n";

	  //and an observer set....
	  $observer_cd_list[] = $nurse_name;

	  $pdo .= "      <start_date>$redcap_start_date</start_date>\n";
	  
	  
	  //Describe the format of this bit of data.
	  if(!$metadata_lookup["$node_name"]['number'] == 1)
	    {
	      $valType_cd="T";
	    }
	  else
	    {
	      $valType_cd="N";
	    }
	  $pdo .= "      <valType_cd>$valType_cd</valType_cd>\n";  //N=numeric, T=Text, B=raw, NLP.
	  
	  //The following tags are dependent on what the valType_cd was.
	  switch ($valType_cd)
	    {
	    case "N":
	      //Its a number and we are only concerned with it being equal to
	      $pdo .= "      <tval_char>E</tval_char>\n";
	      $pdo .= "      <nval_num>$child</nval_num>\n";
	      //Could add units here if we had them.
	      break;
	    case "T":
	      //Its text we so just stick the text in
	      $pdo.= "      <tval_char>".$child."</tval_char>\n";
	      break;
	    case "B":
	      break;
	    case "NLA":
	      break;
	    default:
	      echo "Wrong valType_cd\n";
	      exit;
	      break;
	    }

	  $pdo .= "    </observation>\n"; 
	}
    }
  
  $pdo .= "  </observation_set>\n\n";
  ////////////////////////////////////////////////////////////////



  //These two should be defined already outside of this import.
  ///////////////////////////////////////////////////////////
  //Observer set
  ///////////////////////////////////////////////////////////
  //Name of the dr/nurse filling in the questionnarre
  $observer_cd_list = array_unique($observer_cd_list);
  
  $pdo .= "  <observer_set>\n";
  foreach($observer_cd_list as $this_observer_cd)
    {
      $pdo .= "    <observer sourcesystem_cd=\"$sourcesystem_cd\" import_date=\"$import_date\">\n";
      $pdo .= "      <observer_path>GH/PATH/TO/THIS/OBSERVER</observer_path>\n";
      $pdo .= "      <observer_cd>$this_observer_cd</observer_cd>\n";
      $pdo .= "      <name_char>Wordy description</name_char>\n";
      $pdo .= "    </observer>\n";
    }
  $pdo .= "  </observer_set>\n\n";  


  ////////////////////////////////////////////////////////////
  //Concept set
  //aka the ontology table.
  //I think this should live outside of the PDO import, and
  //should be made in advance to it. It seems silly to keep 
  //importing the same data over and over, not to mention
  //the path and name have to come from somewhere too....
  ////////////////////////////////////////////////////////////
  $concept_cd_list = array_unique($concept_cd_list);

  $pdo .= "  <concept_set>\n";
  foreach($concept_cd_list as $this_concept_cd)
    {
      $pdo .= "    <concept sourcesystem_cd=\"$sourcesystem_cd\" import_date=\"$import_date\">\n";
      $pdo .= "      <concept_path>GH/PATH/THIS/CODE/MAPS/TO/IN/SNOMED</concept_path>\n";
      $pdo .= "      <concept_cd>$this_concept_cd</concept_cd>\n";
      $pdo .= "      <name_char>Wordy description</name_char>\n";
      $pdo .= "    </concept>\n";
    }
  $pdo .= "  </concept_set>\n\n";
  
  
  
  ///////////////////////////////////////////////////////////////
  //code set
  ///////////////////////////////////////////////////////////////
  //Provides a lookup for all the different codes used.
  //Presumably this doesnt need to be here once all the codes
  //have been defined the first time?
  $pdo .= "  <code_set>\n";
  $pdo .= "";
  $pdo .= "  </code_set>\n";
  
  
  $pdo .= "</repository:patient_data>\n";
  




  ///////////////////////////////////////////////////////////////////
  //Once we have got to here we should check the data has been inserted ok,
  //then think about deleting this RECORD from the REDCap DB.... 
  //Being prudent perhaps we should keep a copy of it elsewhere - i.e. write
  //the raw XML to file somewhere.
  ///////////////////////////////////////////////////////////////////
  
  
  
  
  //////////////////////////////////////////////////////////////
  //Lets see what we've got.
  
  echo "<br/>FINAL PDO:<br/>";
  
  //Swap the < and > so I can print to screen.
  $pdo=preg_replace('/</','&lt;',$pdo);
  $pdo=preg_replace('/>/','&gt;',$pdo);
  echo "<pre>$pdo</pre>";

  //Finished this patient, now do another...
}
