<?php
	
namespace Coordbot\Telegram;

use \Coordbot\Database\Connection;
use \Coordbot\Telegram\Model\TelegramUser;
use \Coordbot\Telegram\Model\TelegramChat;
// use \Longman\TelegramBot\Telegram;

require_once 'config/telegram.php';
require_once 'vendor/autoload.php';

	
class TelegramSubscriptionsHandler {
	
// 	private $telegram;
	
// 	function __construct() {
// 	}
	
	private static function allChatsIds() {
		
		$connection = Connection::getInstance();
		
		$q = $connection->query("
		SELECT id FROM telegram_chats
		");
		
		$ids = [];
		while($row = $q->fetch_array()) {
			$ids[] = $row[0];
		}
		
		return $ids;
	}
	
	private static function chatsForProjectNotifications($project_name) {
		
		$connection = Connection::getInstance();
		
		$q = $connection->query("
			SELECT tc.id FROM telegram_chats tc
			JOIN codebasehq_projects cp ON cp.name = '".$project_name."'
			JOIN codebasehq_assignments ca ON ca.codebasehq_project_id = cp.id
			JOIN telegram_users tu ON tu.codebasehq_user_id = ca.codebasehq_user_id
			WHERE tc.username = tu.username
		");
		
		$ids = [];
		while($row = $q->fetch_array()) {
			$ids[] = $row[0];
		}
		
		return $ids;
	}
	
	private static function sendMessageToAccountAdmin($message) {
		$telegram_admin = TelegramUser::fetch(['username' => TELEGRAM_ADMIN_USERNAME]);

		if(!$telegram_admin || $telegram_admin->getTelegramChats() == null)
			return;
			
		foreach($telegram_admin->getTelegramChats() as $chat) {
			$chat_handler = new \CodebasehqTelegramBot\Telegram\TelegramChatHandler($chat->id);
			$chat_handler->sendMessage($message);
		}
	}
	
}