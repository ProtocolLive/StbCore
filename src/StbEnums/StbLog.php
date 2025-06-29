<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbEnums;
use ProtocolLive\TelegramBotLibrary\TblInterfaces\TblLogInterface;

/**
 * @version 2025.06.29.00
 */
enum StbLog:int
implements TblLogInterface{
  case Cron;
  case Trace;
}