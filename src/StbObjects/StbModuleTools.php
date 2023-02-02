<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2023.02.02.02

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\SimpleTelegramBot\StbObjects\{
  StbAdmin,
  StbAdminModules
};

abstract class StbModuleTools{
  public static function Load(
    string $Module
  ):void{
    if(in_array($Module, self::System()) === false):
      require(DirModules . '/' . basename($Module) . '/index.php');
    endif;
  }

  private static function System():array{
    return [StbAdmin::class, StbAdminModules::class];
  }
}