<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbInterfaces;

/**
 * @version 2023.05.23.00
 */
interface StbModuleInterface{
  public static function Install():void;

  public static function Listener():void;

  public static function Uninstall():void;
}