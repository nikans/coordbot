<?php
	
namespace Coordbot\Telegram;

use \Rmccue\Requests;
use \Longman\TelegramBot\Telegram;
use \Longman\TelegramBot;
use \Coordbot\Telegram\TelegramSubscriptionsHandler;

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
		$input = TelegramBot\Request::getInput();
		if($input == null || strlen($input) == 0) { 
			return; 
		}
		
		$this->input = json_decode($input);
		$this->chat_id = $this->input->message->chat->id;
		$this->username = $this->input->message->from->username;
		$message = $this->input->message->text;
		
// 		$editRef = ['chat_id' => $this->chat_id, 'message_id' => $this->input->message->message_id, 'text' => $message." а так же ты хуй."];
// 		var_dump($editRef);
// 		TelegramBot\Request::editMessageText($editRef);
// 		$this->editMessageText(['chat_id' => $this->chat_id, 'message_id' => $this->input->message->message_id, 'text' => $message." а так же ты хуй.", 'parse_mode' => 'HTML']);
		$this->findCoordinates($message);

		$this->logInput();
		$cords = $this->findCoordinates($message);
		if (!empty($cords)) {
			for($i=0; $i<count($cords); $i++) {
				$lat = $cords[$i]["lat"];
				$lon = $cords[$i]["lon"];
// 				$message = str_replace($cords[$i]["pattern"], " maps.yandex.ru/?ll=".$lon."%2C".$lat.".&z=14&pt=".$lon."%2C".$lat." ", $message);
				$ya_web_link = "maps.yandex.ru/?ll=".$lon."%2C".$lat.".&z=15&pt=".$lon."%2C".$lat;
// 				$ya_app_link = "yandexmaps://maps.yandex.ru/?ll=".$lon."%2C".$lat.".&z=14&pt=".$lon."%2C".$lat;
				$gm_web_link = "https://www.google.com/maps?ll=".$lat.",".$lon."&q=".$lat.",".$lon."&z=15";
				$this->sendMessageSilently("Coordinate #".($i+1)." <a href='".$ya_web_link."'>Yandex</a> | <a href='".$gm_web_link."'>Google</a>");
				$this->sendMessageSilently($lat." ".$lon);
				$geocode = $this->reverseGeocode($lat, $lon);
				$address = $geocode->results[0]->formatted_address;
				if (!empty($address)) {
					$this->sendMessageSilently($address);
				}
				TelegramBot\Request::sendLocation(['chat_id' => $this->chat_id, 'latitude' => $lat, 'longitude' => $lon, 'disable_notification' => true]);
			}
// 			$this->sendMessage($message);
		}
	}
	
	public function prettyInput() {
		return json_encode($this->input, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
	}
	
	public function sendMessage($message) {
		$result = TelegramBot\Request::sendMessage(['chat_id' => $this->chat_id, 'text' => $message, 'parse_mode' => 'HTML']);
		return $result;
	}
	
	public function sendMessageSilently($message) {
		$result = TelegramBot\Request::sendMessage(['chat_id' => $this->chat_id, 'text' => $message, 'parse_mode' => 'HTML', 'disable_notification' => true, 'disable_web_page_preview' => true]);
		return $result;
	}
	
	public function findCoordinates($message) {
		if (preg_match_all("/(?P<lat>\d+\.\d+)[\"“”',;:\s]+(?P<lon>\d+\.\d+)/", $message, $matches)) {
			$cords = [];
			for($i=0; $i<count($matches[0]); $i++) {
				$cords[$i] = ["pattern" => $matches[0][$i], "lat" => $matches["lat"][$i], "lon" => $matches["lon"][$i]];
			}
        	return $cords;
        }
        return null;
	}
	
	public function reverseGeocode($lat, $lon) {
		$headers = array(
			'Accept' => 'application/json',
			'Content-type' => 'application/json' 
		);
		$request = \Requests::get("https://maps.googleapis.com/maps/api/geocode/json?latlng=".$lat.",".$lon."&language=ru&key=".GOOGLE_MAPS_API_KEY, $headers);
		$data = json_decode($request->body);
		return $data;
	}
	
	public function logInput() {
		$filename = "test/telegram-".time().".json";
		file_put_contents($filename, $this->prettyInput());
	}
	
}