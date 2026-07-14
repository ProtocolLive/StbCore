<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\NoStr\Fields;

/**
 * @version 2026.07.14.00
 */
enum Commands:string{
  case Name = 'command';
  case Module = Modules::Name->value;
  case Ephemeral = 'ephemeral';
}