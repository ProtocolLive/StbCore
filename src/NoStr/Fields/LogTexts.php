<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\NoStr\Fields;

/**
 * @version 2023.05.25.00
 */
enum LogTexts:string{
  case Chat = Chats::Id->value;
  case Event = 'event';
  case Id = 'log_id';
  case Msg = 'msg';
  case Time = 'time';
}