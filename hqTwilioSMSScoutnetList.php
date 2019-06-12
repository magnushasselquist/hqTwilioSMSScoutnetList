<?php

require_once '../vendor/autoload.php'; 
use Twilio\Rest\Client; 

function fixNumber($nr) {
	$nr = str_replace(" ", "", $nr); // ta bort mellanslag
	$nr = preg_replace('/\s+/', '', $nr); // all spaces
	$nr = str_replace("-", "", $nr); // ta bort bindestreck
	$nr = "+46".substr($nr, 1);
	return $nr;
}

function sendSMS($sms_nr, $sms_body) {
	echo " - ".$sms_nr;
	// echo $sms_body." ";
	$account_sid = 'XXX'; // SET YOUR OWN
	$auth_token = 'XXX'; // SET YOUR OWN
  $sender = 'XXX' // SET YOUR OWN
	$client = new Client($account_sid, $auth_token); 	
	
	$smsresult = $client->messages->create(
		$sms_nr,
		array(
			'from' => $sender,				
			'body' => $sms_body
		)
	); 
	echo " : ".$smsresult."<br/>"; //DEBUG		
	return $smsresult;
}

if (isset($_GET["listid"]) and isset($_GET["text"])) { // lista vald
	$listid = $_GET["listid"];
	$text = $_GET["text"];
	echo "Getting List ".$listid.".. ";
	$remotePageUrlLista = "https://KÅRID:API_KEY@www.scoutnet.se/api/group/customlists?list_id=".$listid;
	$remotePageLista = file_get_contents($remotePageUrlLista); //get the remote page
	// echo $remotePageLista; //DEBUG CMJ
	// $remotePageLista = json_decode($remotePageLista, true);
	echo "Done.<br/>";
	
	echo "Getting ALL MEMBERS JSON data.. ";
	$remotePageUrlAll = "https://KÅRID:API_KEY@www.scoutnet.se/api/group/memberlist";
	$remotePageAll = file_get_contents($remotePageUrlAll); //get the remote page
	// echo $remotePageAll; //DEBUG CMJ
	$remotePageAll = json_decode($remotePageAll, true);
	echo "Done.<br/>";
	
	echo "Bearbetar listan..<br/><pre>";
	foreach($remotePageAll["data"] as $medlem) {	
		$medlemsnr = "".$medlem["member_no"]["value"]."";
		$medlemOnList = (strpos($remotePageLista, $medlemsnr) !== false);
		//	echo $medlemsnr;
		if ($medlemOnList) {
			// do shit
			$firstname = $medlem["first_name"]["value"];
			$lastname = $medlem["last_name"]["value"];
			$sms_telefon = fixNumber($medlem["contact_mobile_phone"]["value"]);
			$sms_telefon_mum = fixNumber($medlem["contact_mobile_mum"]["value"]);
			$sms_telefon_dad = fixNumber($medlem["contact_mobile_dad"]["value"]);			
			// TODO: Flera nummer till samma person, Mamma, Pappa
			// echo $medlem["contact_mobile_phone"]["value"]; // DEBUG
			echo "Skickar till ".$firstname." ".$lastname."<br/>";
			if ($sms_telefon !== "+46") { // om det är något nummer att skicka till
				sendSMS($sms_telefon, $text);
			} else echo " - : FEL - Inget nummer registrerat på personen.<br/>";
			if ($_GET['mum']==true and $sms_telefon_mum !== "+46") { //inkludera nummer till Mamma
				sendSMS($sms_telefon_mum, $text);
			}
			if ($_GET['dad']==true and $sms_telefon_dad !== "+46") { //inkludera nummer till Pappa
				sendSMS($sms_telefon_dad, $text);
			}			
			echo "<br/>";
		}
	}
	echo "</pre><br/>Done.";
	
} else {
	// LISTA INTE VALD
	echo "Lista<br/>";
	echo "<form action='#'><select name='listid'>";
    //echo "Getting All lists..<br/>";
	$remotePageUrlListor = "KÅRID:API_KEY@www.scoutnet.se/api/group/customlists";
	$remotePageListor = file_get_contents($remotePageUrlListor); //get the remote page
	// echo $remotePageListor; //DEBUG CMJ
	$remotePageListor = json_decode($remotePageListor, true);
	// echo "Done.<br/>";
	foreach($remotePageListor as $lista) {	
		echo "<option value='".$lista["id"]."'>".$lista["title"]."</option>";
	}
	echo "</select><br/>";
	echo "Meddelande:<br/><textarea name='text' rows='3' cols='40'></textarea><br/>";
	echo "<input type='checkbox' name='mum' value='true'>Inkludera Mammas mobil<br>";	
	echo "<input type='checkbox' name='dad' value='true'>Inkludera Pappas mobil<br>";	
	echo "<input type='submit' value='Skicka SMS'/>";
	echo "</form>";
}


?>
