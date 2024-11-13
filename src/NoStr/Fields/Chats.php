<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\NoStr\Fields;

/**
 * @version 2023.11.13.00
 */
enum Chats:string{
  case Created = 'created';
  case Id = 'chat_id';
  case Language = 'language_code';
  case LastSeen = 'lastseen';
  case Name = 'first_name';
  case NameLast = 'last_name';
  case Nick = 'username';
  case Permission = 'perms';
}