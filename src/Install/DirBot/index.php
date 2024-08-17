<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2024.08.17.00

use ProtocolLive\SimpleTelegramBot\StbObjects\StbCore;

const DirBot = __DIR__;
require(dirname(__DIR__, 1) . '/system/system.php');

StbCore::Entry($Bot, $Db, $Lang, $BotData);