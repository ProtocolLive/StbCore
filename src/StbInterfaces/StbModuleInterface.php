<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbInterfaces;
use ProtocolLive\SimpleTelegramBot\StbObjects\{
  StbDatabase,
  StbLanguageSys
};
use ProtocolLive\TelegramBotLibrary\TblObjects\{
  TblCmd,
  TblData,
  TblMarkupInline
};
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgInterfaces\TgEventInterface;
use ProtocolLive\TelegramBotLibrary\TgObjects\TgCallback;

/**
 * @version 2024.02.14.00
 */
interface StbModuleInterface{
  public static function Command(
    TelegramBotLibrary $Bot,
    TblCmd $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void;

  public static function Cron(
    TelegramBotLibrary $Bot,
    StbDatabase $Db,
    TblData $BotData
  ):void;

  public static function Install(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void;

  /**
   * @return bool If the listener are satisfied
   */
  public static function Listener(
    TelegramBotLibrary $Bot,
    TgEventInterface $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):bool;

  public static function Plugin_Buttons(
    StbDatabase $Db,
    TblMarkupInline $Markup
  ):void;

  public static function Uninstall(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void;
}