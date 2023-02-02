<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2023.02.02.00

use ProtocolLive\SimpleTelegramBot\StbObjects\{
  StbAdmin,
  StbAdminModules
};

abstract class StbModuleTools{
  public static function StbModuleLoad(
    string $Module
  ):void{
    if(in_array($Module, self::StbModuleSystem()) === false):
      require(DirModules . '/' . basename($Module) . '/index.php');
    endif;
  }

  private static function StbModuleSystem():array{
    return [StbAdmin::class, StbAdminModules::class];
  }
}