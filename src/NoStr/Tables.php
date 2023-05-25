<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\NoStr;

/**
 * @version 2023.05.25.00
 */
enum Tables:string{
  case CallbackHash = 'callbackshash';
  case Chats = 'chats';
  case Commands = 'commands';
  case Listeners = 'listeners';
  case LogTexts = 'log_texts';
  case LogUpdates = 'log_updates';
  case Modules = 'modules';
  case Params = 'sys_params';
  case Variables = 'variables';
}