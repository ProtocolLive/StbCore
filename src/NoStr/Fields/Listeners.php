<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\NoStr\Fields;

/**
 * @version 2023.05.25.01
 */
enum Listeners:string{
  case Chat = Chats::Id->value;
  case Name = 'listener';
  case Id = 'listener_id';
  case Module = Modules::Name->value;
}