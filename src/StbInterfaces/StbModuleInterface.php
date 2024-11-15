<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbInterfaces;
use ProtocolLive\SimpleTelegramBot\StbObjects\{
  StbDatabase,
  StbLanguageSys
};
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgObjects\TgCallback;

/**
 * @version 2024.11.14.00
 */
interface StbModuleInterface{
  public static function Install(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void;

  public static function Uninstall(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void;
}