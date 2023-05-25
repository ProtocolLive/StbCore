<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\NoStr\Fields;

/**
 * @version 2023.05.25.00
 */
enum Variables:string{
  case Chat = Chats::Id->value;
  case Id = 'var_id';
  case Module = Modules::Name->value;
  case Name = 'name';
  case Value = 'value';
}