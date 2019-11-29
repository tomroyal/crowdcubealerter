<?php

// crowdcube investment alerter script
// logs investment value to a db, and sends you an email alert via postmarkapp.com
// run it via the Advanced Scheduler addon - 'php bin/ccscrape.php'
// Tom Royal 2019

// CONFIGURATION
// There are 4 items you need to set as config vars:

  // CC_URL = the complete https URL of your CC investment page
  $cc_url = getenv('CC_URL');

  // POSTMARK_KEY = an api key for postmarkapp.com. If omitted no emails will be sent.
  $pmapi = getenv('POSTMARK_KEY') ?? '';
  // Also set SENDER_EMAIL and RECIP_EMAIL to valid email addresses

// This is set automatically by Heroku Postgres
$db = pg_connect(getenv('DATABASE_URL'));
// This can be changed if needed
$db_schema_name = 'crowdcube'; 

// check if db is  set up
$r1 = pg_query('SELECT schema_name FROM information_schema.schemata WHERE schema_name = \''.$db_schema_name.'\'');
if (pg_num_rows($r1) != 1){
  // need to create schema and table
  $r1 = pg_query('CREATE SCHEMA '.$db_schema_name);
  $r1 = pg_query('CREATE TABLE '.$db_schema_name.'.ccdata (
    id serial,
    invested integer,
    logtime TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
    )');
    error_log('Configured new schema '.$db_schema_name.' and table');
};

// get the crowdcube page
$ch = curl_init($cc_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);
if ($response == '' ){
  error_log('failed to get CC investment page');
  die;
}

// get current investment value from that page
function findandtrim($startat,$fromstr,$tostr,$response){
  // $response = substr($response,(strpos((string)$response,$startat)+strlen($startat)));
  $frompos = strpos($response,$fromstr) + strlen($fromstr);
  $topos = strpos($response,$tostr);
  $target = substr($response,$frompos,($topos-$frompos));
  return($target);
};
$fetched_value = findandtrim(0,'"pitch_funding":',',"pitch_progress"',$response);

// if we have a value, handle it
if ($fetched_value != ''){
  // get last value from db
  $query = "SELECT * FROM crowdcube.ccdata ORDER BY id desc LIMIT 1";
  $r1 = pg_query($query);
  $current_status = pg_fetch_array($r1);
  
  if  ((pg_num_rows($r1) == 0) || (($current_status['invested'] != '') && ($current_status['invested'] < $fetched_value))){
    // either this is our first check, or the current investment value is > last time
    
    // store new record in db
    $fetched_value = pg_escape_string($fetched_value);
    $query = 'INSERT INTO crowdcube.ccdata ("invested") VALUES ('.$fetched_value.')';
    $r1 = pg_query($query);
      
    if ($pmapi != ''){
      // send email
      $ch2 = curl_init("https://api.postmarkapp.com/email");
      $data = [];
      $data['From'] = getenv('SENDER_EMAIL');
      $data['To'] = getenv('RECIP_EMAIL');
      $data['Subject'] = 'Crowdcube value now £'.$fetched_value;
      $data['TextBody'] = 'I just checked the site, and £'.$fetched_value.' has been pledged.';
      curl_setopt($ch2, CURLOPT_POST, true);
      curl_setopt($ch2, CURLOPT_HTTPHEADER, array('X-Postmark-Server-Token:'.$pmapi,'Accept: application/json',"Content-Type: application/json"));
      curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($data));
      $res = curl_exec($ch2);
      if($res === false){
        error_log('Postmark email error '.curl_error($ch2));
      }
      curl_close($ch2);
    };  
    
  };
}
else {
  error_log('failed to scrape CC value');
}

?>