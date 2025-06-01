<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbEnums;

/**
 * @version 2025.05.31.00
 */
enum StbError{
  case CallBackReturn;
  case ChatNotFound;
  case LanguageNotFound;
  case ListenerInvalid;
  case ModuleNotFound;
}