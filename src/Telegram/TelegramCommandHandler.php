<?php
	
namespace Coordbot\Telegram;

use \Longman\TelegramBot\Telegram;
use \Longman\TelegramBot;
use \Longman\TelegramBot\Entities\Keyboard;
use \Longman\TelegramBot\Entities\KeyboardButton;
use \Coordbot\Telegram\Model\TelegramUser;
use \Coordbot\Telegram\Model\TelegramChat;

require_once 'config/telegram.php';
require_once 'vendor/autoload.php';

	
class TelegramCommandHandler {
	
	protected $telegram;
	public $chat_id;
	public $username;
	protected $input;
	
// 	protected $step = 0; 
	
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
		$this->username = $this->input->message->chat->username;
		$message = $this->input->message->text;
		
		preg_match('/^\/(\w+)(\s(.+))?/', $message, $matches);

		$command = @$matches[1];
		$parameter = @$matches[3];
		
		if(!isset($command)) return;
		
		switch ($command) {
			case 'start': case 'help':
				$this->welcomeMessage();
				break;
			case 'echo':
				$this->echoMessage($parameter);
				break;
		}
	}
	
	private function welcomeMessage() {
		$keyboard = new Keyboard();
		$keyboard->hide();
		
		$telegram_user = $this->getTelegramUser();
		
		$welcome_message = 'OK';
		$this->sendMessage($welcome_message, $keyboard);
		
		$help_message =  'OK';
		$this->sendMessage($help_message);
	}
	
	private function echoMessage($text) {
		$this->sendMessage($text);
	}
	
	private function getTelegramUser() {
		$telegram_chat = TelegramChat::fetch(['id' => $this->chat_id]);
		$telegram_user = $telegram_chat->getTelegramUser();
		return $telegram_user;
	}
	
		
	public function sendMessage($message, $keyboard = null) {
		$parameters = ['chat_id' => $this->chat_id, 'text' => $message, 'parse_mode' => 'HTML'];
		if(isset($keyboard))
			$parameters = array_merge($parameters, ['reply_markup' => $keyboard]);
		$result = TelegramBot\Request::sendMessage(['chat_id' => $this->chat_id, 'text' => $message, 'parse_mode' => 'HTML']);
		return $result;
	}
	
}