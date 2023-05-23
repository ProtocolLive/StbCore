<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2023.05.22.00

use ProtocolLive\SimpleTelegramBot\StbObjects\StbBotTools;

const DirBot = __DIR__;
require(dirname(__DIR__, 1) . '/system/system.php');

ArgV();
$_GET['a'] ??= '';
if(isset($_SERVER['Cron'])):
  StbBotTools::Cron();
elseif(is_callable(StbBotTools::class . '::Action_' . $_GET['a'])):
  call_user_func(StbBotTools::class . '::Action_' . $_GET['a']);
endif;