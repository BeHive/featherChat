<?php
date_default_timezone_set('Europe/Lisbon');
require_once('lib/Spyc/Spyc.php');
$settings = Spyc::YAMLLoad('featherChat.yml');

$host = $settings['server']['host']; //host
$port = $settings['server']['port']; //port
$address = $settings['server']['address']; //address
$null = NULL; //null var

$messageLog;

//Create TCP/IP sream socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//reuseable port
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

//bind socket to specified host
socket_bind($socket, 0, $port);

//listen to port
socket_listen($socket);

//create & add listening socket to the list
//$clients = array("featherChat" => $socket);
$users = array("featherChat" => $socket);
$clients = array();		
	
//start endless loop, so that our script doesn't stop
while (true) {
	//manage multiple connections
	$changed = array_merge($users,$clients);
	//returns the socket resources in $changed array
	socket_select($changed, $null, $null, 0, 10);
			
	//check for new socket
	if (in_array($socket, $changed)) {
		
		$socket_new = socket_accept($socket); //accept new socket
		$users[] = $socket_new; //add socket to client array
		
		$header = socket_read($socket_new, 1024); //read data sent by the socket
		perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake
		
		//socket_getpeername($socket_new, $ip); //get ip address of connected socket
		
		//make room for new socket
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
		
	}
	
	//loop through all connected sockets
	foreach ($changed as $changed_socket) {	
		
		//check for any incomming data
		while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
		{
			$received_text = unmask($buf); //unmask data
			$tst_msg = json_decode($received_text); //json decode
			
			if(isset($tst_msg->type)){
				if($tst_msg->type == 'entry'){
				
					if(array_key_exists($tst_msg->user,$clients)){
						unset($logEntry);
						$logEntry[] = "featherChat";
						$logEntry[] = 'user '.$tst_msg->user.' already in chat';
						$logEntry[] = date(" H:i", time());
						send_message('loginNok',$logEntry,$changed_socket);
					}
					else{
						unset($logEntry);
						$logEntry[] = "featherChat";
						$logEntry[] = 'ok';
						$logEntry[] = date(" H:i", time());
						send_message('loginOk',$logEntry,$changed_socket);
						
						$clients[strip_tags($tst_msg->user)] = $changed_socket;
						unset($users[$changed_socket]);
						
						//send message history to new client
						if(isset($messageLog)){
							foreach($messageLog as $messageItem){
								send_message('usermsg',$messageItem,$changed_socket);
							}
						}
						
						unset($logEntry);
						$logEntry[] = "featherChat";
						$logEntry[] = strip_tags($tst_msg->user).' connected';
						$logEntry[] = date(" H:i", time());
							
						send_message('system',$logEntry); //notify all users about new connection
					}
				}
				elseif($tst_msg->type == 'message'){
					if(isset($tst_msg->message)){
						
						$user_name = array_search($changed_socket,$clients); //sender name
		
						$user_message = strip_tags($tst_msg->message); //message text
						
						unset($logEntry);
						$logEntry[] = $user_name;
						$logEntry[] = $user_message;
						$logEntry[] = date(" H:i", time());
									
						if(isset($messageLog) && sizeof($messageLog) >= 20)
							array_shift($messageLog);
			
						$messageLog[] = $logEntry;
							
						if($user_name != $null && $user_message != null){
							send_message('usermsg',$logEntry); //send data
						}
					}
				}
			}
			
			break 2; //exist this loop
		}
		
		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === false) { // check disconnected client
			// remove client for $clients array
			$found_socket = array_search($changed_socket, $clients);
			$found_guest_socket = array_search($changed_socket, $users);
			
			if($found_socket != false){
				unset($logEntry);
				$logEntry[] = "featherChat";
				$logEntry[] = array_search($changed_socket,$clients).' disconnected';
				$logEntry[] = date(" H:i", time());
				unset($clients[$found_socket]);
				send_message('system',$logEntry);
				
				
			}
			elseif($found_guest_socket != false){
				unset($users[$found_guest_socket]);
			}
			
		}
	}
}
// close the listening socket
socket_close($sock);

function send_message($type,$logEntry,$socket = NULL)
{

	global $clients;

	$clientList = array_keys($clients);
	
	$msg = mask(json_encode(array('type'=>$type, 'timestamp'=>$logEntry[2], 'counter'=>count($clients)-1, 'name'=>$logEntry[0], 'message'=>$logEntry[1], 'clients'=>$clientList)));

	if($socket == NULL){
		foreach($clients as $changed_socket)
		{
			@socket_write($changed_socket,$msg,strlen($msg));
		}
	}
	else{
		@socket_write($socket,$msg,strlen($msg));
	}
	
	return true;
}


//Unmask incoming framed message
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

//Encode message for transfer to client.
function mask($text)
{
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);
	
	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}

//handshake new client.
function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	global $host,$port,$address;
	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//hand shaking header
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"WebSocket-Origin: ".$host."\r\n" .
	"WebSocket-Location: ws://".$host.":".$port."/".$address."\r\n".
	"Sec-WebSocket-Accept:".$secAccept."\r\n\r\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}
