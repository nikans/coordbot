<?php
	
namespace Coordbot\Geolocation;

use \Rmccue\Requests;
/*
use \Longman\TelegramBot\Telegram;
use \Longman\TelegramBot;
use \Coordbot\Telegram\TelegramSubscriptionsHandler;
*/

require_once 'config/google.php';
require_once 'vendor/autoload.php';

	
class GeolocationHandler {
	
	private $message;
	public $coordinates = [];
	
	function __construct($message) {
		$this->message = $message;
		
		$this->coordinates = $this->findCoordinates($message);
	}
	
	private function findCoordinates($message) {
		if (preg_match_all("/(?P<lat>\d+\.\d+)[\"“”',;:\s]+(?P<lon>\d+\.\d+)/", $message, $matches)) {
			$cords = [];
			for($i=0; $i<count($matches[0]); $i++) {
				$c = new \stdClass;
				$c->pattern = $matches[0][$i];
				$c->lat = $matches["lat"][$i];
				$c->lon = $matches["lon"][$i];
				$c->string = $matches["lat"][$i]." ".$matches["lon"][$i];
				$cords[$i] = $c;
			}
        	return $cords;
        }
        return null;
	}
	
	public function mapUrls($lat, $lon, $z = 15) {
		$u = new \stdClass;
		$u->yandex = '<a href="maps.yandex.ru/?ll='.$lon.'%2C'.$lat.'.&z='.$z.'&pt='.$lon.'%2C'.$lat.'">Yandex</a>';
		$u->google = '<a href="https://www.google.com/maps?ll='.$lat.','.$lon.'&q='.$lat.','.$lon.'&z='.$z.'">Google</a>';
		
		return [$u->yandex, $u->google];
	}
	
	public function reverseGeocode($lat, $lon, $language = 'en') {
		$headers = array(
			'Accept' => 'application/json',
			'Content-type' => 'application/json' 
		);
		$request = \Requests::get('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$lat.','.$lon.'&language='.$language.'&key='.GOOGLE_MAPS_API_KEY, $headers);
		$data = json_decode($request->body);
		
		$address = $data->results[0]->formatted_address;
		if (!empty($address)) {
			return $address;
		}
		return null;
	}
}
