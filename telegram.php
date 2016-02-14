<?php
/*
Attributes:
	raw_data -> For Logging of raw request from Telegram servers(users)
	decoded_data -> making them available for further process (group/supergroup events)
	type -> define which kind of request has been sent to the BOT
	
	method -> Depends on the response processed in processText() method (sendMessage for example)
	action -> related to method. Depends on the response processed. (typing, find_location etc)
*/
include 'getFile.php';

class TelegramBot
{
	private $API_URL = 'https://api.telegram.org/bot';
	private $TOKEN = 'YOUR_TOKEN_HERE';
	
	
	protected $raw_data; //Json Data
	protected $decoded_data; //Json-Decoded Data
	
	protected $method;
	protected $action;
	protected $type;
	
	private $fh;
	private $query;
	private $result;
	
	
	//
	// MAGIC METHODS
	//
	
	//On Instantiation, get Webhook data and define the kind of response to give back
	public function __construct()
	{
		//get data from Webhook call
		$this->raw_data = file_get_contents("php://input");
		//decode data
		$this->decoded_data = json_decode($this->raw_data, true);
		//Set attributes and Default actions
		$this->method = "sendMessage";
		$this->action = "typing";
		if (isset($this->decoded_data["message"]["text"]))
		{	//Get data and type (photo , text or other)
			$this->text = $this->decoded_data["message"]["text"];
			$this->type = "text";
		}else if(isset($this->decoded_data["message"]["photo"]))
		{
			$this->type = "photo";
		}else if(isset($this->decoded_data["message"]["location"]))
		{
			$this->type = "location";	
		}else if(isset($this->decoded_data["message"]["video"]))
		{
			$this->type = "video";
		}else{
			$this->type = "unknown";
		}
	}
	
	//To define what to do when unset($this)
	public function __destruct()
	{
		//Log what's happened
		$this->fh = new logger("newdebug.txt");
		$this->fh->lwrite("Request:\n".$this->raw_data);
		foreach($this->query as $key => $value)
		{
			$string .= $key." => ".$value."\n";
		}
		$this->fh->lwrite("Sent:\n".$string);
		$this->fh->lwrite("Using method:\t".$this->method);
		$this->fh->lwrite("Received: \n".$this->result);
		unset($this->fh);
	}
	
	//when printed, return this
	public function __toString()
	{
		return "Telegram Bot Client coded by Nightfox Nicita";
	}
	
	//
	// SETTERS
	//
	public function setAction($action)
	{	//Custom bot that inherits this class, must set the kind of action to perform
		$this->action = $action;
		return True;
	}
	
	public function setMethod($method)
	{
		$this->method = $method;
		return True;
	}
	
	//
	// GETTERS
	//
	public function getReqType()
	{
		return $this->type;
	}
	
	//Get Bot Action
	public function getAction()
	{
		return $this->action;
	}
	
	public function getReq($reqArray)
	{	//Retrieve data for processing the response
		$response = array();
		
		foreach($reqArray as $key)
		{
			switch($key)
			{
				case 'chat_id':
					$response['chat_id'] = $this->decoded_data['message']['chat']['id'];
				break;
				
				case 'username':
					$response['username'] = $this->decoded_data['message']['from']['username'];
				break;
				
				case 'text':
					$response['text'] = $this->decoded_data['message']['text'];
				break;
			}
			//$response[$key] = $this->decoded_data['message'][$key] or $this->decoded_data['message']['chat'][$key];
		}
		return $response;
	}
	
	
	public function getWebhookCall()
	{
		return $this->raw_data;
	}
	
	//
	// METHODS
	//
	
	//SendChatAction(curl_handler, "action")
	public function sendChatAction($ch, $chat_id)
	{	//send action of the bot(typing, sending data etc..)
		$API_URL = $this->API_URL . $this->TOKEN ."/";
		curl_setopt($ch,CURLOPT_URL, $API_URL . "sendChatAction?chat_id=".urlencode($chat_id)."&action=".$this->action);
		return curl_exec($ch);
	}
	
	//Send BOT Response
	public function sendResponse($ch,$query)
	{
		$this->query = $query;// DEBUG
		$API_URL = $this->API_URL . $this->TOKEN . "/".$this->method;
		curl_setopt($ch,CURLOPT_URL, $API_URL);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$query);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_TIMEOUT,10000); //10 sec before letting curl post die
		$this->result = curl_exec($ch);
		return $this->result;
	}
	
	
	//Create reply_markup object
	public function getKeyboard($row)
	{	
		$keyboard = array(
			'keyboard' => array(
				$row
			)
		);
		//Json encode , as API states
		return json_encode($keyboard);
	}
	
	//Emoticon string from unicode
	public function uniToEmoji($unichr)
	{	//Usage \u2b50 for istance , uniToEmoji(0x2B50)
		return iconv('UCS-4LE', 'UTF-8', pack('V', $unichr);
	}

}


?>
