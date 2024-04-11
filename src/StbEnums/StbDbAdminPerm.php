<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbEnums;

/**
 * @version 2023.04.11.00
 */
enum StbDbAdminPerm:int{
  case All = 31;
  case None = 0;
  case Admins = 1;
  case Modules = 2;
  case UserCmds = 4;
  case Stats = 8;
  case Cmds = 16;
}