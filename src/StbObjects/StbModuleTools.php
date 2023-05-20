<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2023.05.19.00

namespace ProtocolLive\SimpleTelegramBot\StbObjects;

abstract class StbModuleTools{
  public static function Load(
    string $Module
  ):void{
    if(strpos($Module, 'ProtocolLive\SimpleTelegramBot') === false):
      require(DirModules . '/' . basename($Module) . '/index.php');
    endif;
  }
}