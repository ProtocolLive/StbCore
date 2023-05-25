<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbInterfaces;

/**
 * @version 2023.05.24.00
 */
interface StbModuleInterface{
  public static function Command():void;
  public static function Install():void;
  public static function Listener():void;
  public static function Uninstall():void;
}