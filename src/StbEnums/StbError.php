<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbEnums;

/**
 * @version 2024.11.08.00
 */
enum StbError{
  case CallBackReturn;
  case ChatNotFound;
  case ListenerInvalid;
  case ModuleNotFound;
}