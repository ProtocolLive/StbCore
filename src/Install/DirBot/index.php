<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2023.05.22.01

use ProtocolLive\SimpleTelegramBot\StbObjects\StbBotTools;

const DirBot = __DIR__;
require(dirname(__DIR__, 1) . '/system/system.php');

StbBotTools::Entry();