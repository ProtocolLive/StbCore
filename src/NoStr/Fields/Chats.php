<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\NoStr\Fields;

/**
 * @version 2023.05.25.00
 */
enum Chats:string{
  case Created = 'created';
  case Id = 'chat_id';
  case Language = 'lang';
  case LastSeen = 'lastseen';
  case Name = 'name';
  case NameLast = 'name2';
  case Nick = 'nick';
  case Permission = 'perms';
}