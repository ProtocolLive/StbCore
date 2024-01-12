<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2024.01.12.00

use ProtocolLive\SimpleTelegramBot\StbObjects\StbBotTools;

const DirBot = __DIR__;
require(dirname(__DIR__, 1) . '/system/system.php');

StbBotTools::Entry($Bot, $Db, $Lang);