<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\NoStr\Fields;

/**
 * @version 2023.05.29.00
 */
enum LogUpdates:string{
  case Id = 'log_id';
  case Time = 'time';
  case Type = 'type';
  case Update = 'update';
}