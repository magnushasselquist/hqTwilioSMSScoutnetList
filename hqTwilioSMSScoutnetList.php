<?php

require_once '../vendor/autoload.php'; 
use Twilio\Rest\Client; 

$account_sid = 'XXX'; // SET YOUR OWN FROM TWILIO
$auth_token = 'XXX'; // SET YOUR OWN FROM TWILIO
$sender = 'XXX' // SET YOUR OWN NUMBER FROM TWILIO (ie +46707123456)
	
$scoutnet_kar_id = 'XXX' // SET YOUR OWN
$scoutnet_custom_lists_url = 'www.scoutnet.se/api/group/customlists';
$scoutnet_custom_lists_api_key = 'XXX'; //SET YOUR OWN
$scoutnet_member_list_url = 'www.scoutnet.se/api/group/memberlist'; 	
$scoutnet_member_list_api_key = 'XXX'; //SET YOUR OWN
	

function fixNumber($nr) {
	$nr = str_replace(" ", "", $nr); // ta bort mellanslag
	$nr = preg_replace('/\s+/', '', $nr); // all spaces
	$nr = str_replace("-", "", $nr); // ta bort bindestreck
	$nr = "+46".substr($nr, 1);
	return $nr;
}

function sendSMS($sms_nr, $sms_body) {
	$client = new Client($account_sid, $auth_token); 	
	echo " - ".$sms_nr;
	// echo $sms_body." "; // DEBUG	
	
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

if (isset($_GET["listid"]) and isset($_GET["text"])) { // LISTA VALD, NU SKA VI HÄMTA MEDLEMMARNA OCH SKICKA SMS
	$listid = $_GET["listid"];
	$text = $_GET["text"];
	echo "Getting List ".$listid.".. ";
	$remotePageUrlLista = "https://".$scoutnet_kar_id.":".$$scoutnet_custom_lists_api_key."@".$scoutnet_custom_lists_url."?list_id=".$listid;
	$remotePageLista = file_get_contents($remotePageUrlLista); //get the remote page
	// echo $remotePageLista; //DEBUG CMJ
	// $remotePageLista = json_decode($remotePageLista, true);
	echo "Done.<br/>";
	
	echo "Hämtar medlemmar från listan.. ";
	$remotePageUrlAll = "https://".$scoutnet_kar_id.":".$scoutnet_member_list_api_key."@".$scoutnet_member_list_url."";
	$remotePageAll = file_get_contents($remotePageUrlAll); //get the remote page
	// echo $remotePageAll; //DEBUG CMJ
	$remotePageAll = json_decode($remotePageAll, true);
	echo "Klar.<br/>";
	
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
	echo "</pre><br/>Klar.";
	
} else {
	// LISTA INTE VALD, NU BER VI ANVÄNDAREN VÄLJA LISTA
	echo "Lista<br/>";
	echo "<form action='#'><select name='listid'>";
    //echo "Getting All lists..<br/>"; // DEBUG
	$remotePageUrlListor = "https://".$scoutnet_kar_id.":".$scoutnet_custom_lists_api_key."@".$scoutnet_custom_lists_url."";
	$remotePageListor = file_get_contents($remotePageUrlListor); //get the remote page
	// echo $remotePageListor; //DEBUG 
	$remotePageListor = json_decode($remotePageListor, true);
	// echo "Done.<br/>"; // DEBUG
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
