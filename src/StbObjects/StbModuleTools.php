<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;

/**
 * @version 2023.05.23.00
 */
abstract class StbModuleTools{
  public static function Load(
    string $Module
  ):void{
    require(DirModules . '/' . basename($Module) . '/index.php');
  }
}