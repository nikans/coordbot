<?php
	
namespace Coordbot\Telegram;

use \Rmccue\Requests;
use \Longman\TelegramBot\Telegram;
use \Longman\TelegramBot;
use \Coordbot\Geolocation;

require_once 'config/telegram.php';
require_once 'config/google.php';
require_once 'vendor/autoload.php';

	
class TelegramChatHandler {
	
	private $telegram;
	public $chat_id;
	public $username;
	private $input;
	
	function __construct($chat_id = null, $username = null) {
		$this->telegram = new Telegram(TELEGRAM_API_KEY, TELEGRAM_BOT_NAME);
				
		$this->chat_id = $chat_id;
		$this->username = $username;
	}
	
	public function handleInput() {
		
		http_response_code(200);
		
		if (is_callable('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}
		
		$input = TelegramBot\Request::getInput();
		if($input == null || strlen($input) == 0) { 
			return; 
		}
		
		$this->input = json_decode($input);
		$this->chat_id = $this->input->message->chat->id;
		$this->username = $this->input->message->from->username;
		
		$message = $this->input->message->text;
		$messageId = $this->input->message->message_id;

		//$this->logInput();
		
		$geolocation = new Geolocation\GeolocationHandler($message);
		
		$cords = $geolocation->coordinates;
		if (!empty($cords)) {
			$this->handleCoords($cords, $messageId, $message, $geolocation);
		}
	}
	
	private function handleCoords($cords, $messageId, $message, $geolocation) {
		$lang = substr($this->input->message->from->language_code, 0, 2);
		$dict = [
			'en' => [
				'coordsFound' => 'coordinates found.',
				'coordNumber' => 'Point #'
			],
			'ru'  => [
				'coordsFound' => 'координат найдено.',
				'coordNumber' => 'Точка #'
			]
		];
		
		$count = count($cords);
		$hasReplied = false;
		
		if ($count > 1) {
			$this->sendMessage($count.' '.$dict[$lang]['coordsFound'], true, $messageId);
			$hasReplied = true;
		}
		
		for($i=0; $i<$count; $i++) {
			$c = $cords[$i];
			
			if ($count == 1 && $c->string != trim($message)) {
				$replyToId = !$hasReplied ? $messageId : null;
				$this->sendMessage($c->string, true, $messageId, $replyToId);
				if (!is_null($replyToId)) { $hasReplied = true; }
			}
			
			$urls = $geolocation->mapUrls($c->lat, $c->lon);
			if(is_array($urls) && count($urls) > 0) {
				$replyToId = !$hasReplied ? $messageId : null;
				$text = ($count > 1 ? $dict[$lang]['coordNumber'].($i+1).': ' : '').join(' | ', $urls);
				$this->sendMessage($text, true, $replyToId);
				if (!is_null($replyToId)) { $hasReplied = true; }
			}
			
			
			$address = $geolocation->reverseGeocode($c->lat, $c->lon, $lang);
			if (!empty($address)) {
				$replyToId = !$hasReplied ? $messageId : null;
				$this->sendMessage($address, true, $replyToId);
				if (!is_null($replyToId)) { $hasReplied = true; }
			}
			
			$replyToId = !$hasReplied ? $messageId : null;
			$this->sendLocation($c->lat, $c->lon, true, $replyToId);
			if (!is_null($replyToId)) { $hasReplied = true; }
		}
	}
	
	public function prettyInput() {
		return json_encode($this->input, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
	}
	
	public function sendMessage($message, $silently = false, $replyToId = null) {
		$parameters = [
			'chat_id' 	 => $this->chat_id, 
			'text' 		 => $message, 
			'parse_mode' => 'HTML',
			'disable_web_page_preview' => true
		];
		
		if ($silently === true) {
			$parameters['disable_notification'] = true;
		}
		
		if (!is_null($replyToId) && is_integer($replyToId)) {
			$parameters['reply_to_message_id'] = $replyToId;
		}
		
		$result = TelegramBot\Request::sendMessage($parameters);
		return $result;
	}
	
	public function sendLocation($lat, $lon, $silently = false, $replyToId = null) {
		$parameters = [
			'chat_id' 	=> $this->chat_id, 
			'latitude' 	=> $lat, 
			'longitude' => $lon
		];
		
		if ($silently === true) {
			$parameters['disable_notification'] = true;
		}
		
		if (!is_null($replyToId) && is_integer($replyToId)) {
			$parameters['reply_to_message_id'] = $replyToId;
		}
		
		$result = TelegramBot\Request::send('sendLocation', $parameters);
		return $result;
	}
		
	public function logInput() {
		$filename = "test/telegram-".time().".json";
		file_put_contents($filename, $this->prettyInput());
	}
}
