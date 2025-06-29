<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\NoStr\Fields;

/**
 * @version 2025.06.29.00
 */
enum LogUpdates:string{
  case Id = 'log_id';
  case Time = 'time';
  case Type = 'type';
  case User = 'user';
  case Chat = 'chat';
  case MsgId = 'msg_id';
  case Update = 'update';
}