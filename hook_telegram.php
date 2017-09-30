<?php

use Coordbot\Telegram\TelegramChatHandler;
use Coordbot\Telegram\TelegramCommandHandler;

require_once 'vendor/autoload.php';

$chat_handler = new TelegramChatHandler();
$chat_handler->handleInput();

$commandHandler = new TelegramCommandHandler();
$commandHandler->handleInput();

// $chat_handler->logInput();