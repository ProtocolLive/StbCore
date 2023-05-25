<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\NoStr\Fields;

/**
 * @version 2023.05.25.01
 */
enum Commands:string{
  case Name = 'command';
  case Module = Modules::Name->value;
}