<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2023.02.04.01

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use PDO;
use ProtocolLive\TelegramBotLibrary\{
  TelegramBotLibrary,
  TgObjects\TgCallback
};

abstract class StbModuleHelper{
  /**
   * Run this after the 'create table' block
   */
  protected static function InstallHelper(
    PDO $Pdo,
    array $Commands,
    bool $Commit = true
  ):void{
    /**
     * @var StbDatabase $Db
     * @var TgCallback $Webhook
     * @var TelegramBotLibrary $Bot
     * @var StbLanguageSys $Lang
     */
    global $Db, $Webhook, $Bot, $Lang;
    $Pdo->beginTransaction();

    if($Db->ModuleInstall(self::ModName()) === false):
      self::MsgError($Pdo);
      error_log('Fail to install module ' . self::ModName());
      return;
    endif;

    $cmds = $Bot->MyCmdGet();
    foreach($Commands as $cmd):
      $cmds->Add($cmd[0], $cmd[1]);
      if($Db->CommandAdd($cmd[0], self::ModName()) === false):
        self::MsgError($Pdo);
        error_log('Fail to add the command ' . $cmd[0]);
        return;
      endif;
    endforeach;
    try{
      $Bot->MyCmdSet($cmds);
    }catch(TblException){
      self::MsgError($Pdo);
      error_log('Fail to add the commands');
      return;
    }

    $Bot->CallbackAnswer(
      $Webhook->Id,
      sprintf($Lang->Get('InstallOk', Group: 'Module'))
    );
    if($Commit):
      $Pdo->commit();
      StbAdminModules::Callback_Modules();
    endif;
  }

  protected static function InstallHelper2(
    PDO $Pdo
  ){
    $Pdo->Commit();
    StbAdminModules::Callback_Modules();
  }

  private static function ModName():string{
    $temp = debug_backtrace();
    return $temp[2]['class'];
  }

  protected static function ModTable(
    string $Table
  ):string{
    return 'module_' . self::ModName() . '_' . $Table;
  }

  protected static function MsgError(
    PDO $Pdo
  ):void{
    global $Bot, $Webhook, $Lang;
    DebugTrace();
    $Pdo->rollBack();
    $Bot->CallbackAnswer(
      $Webhook->Id,
      sprintf($Lang->Get('Fail', Group: 'Module'))
    );
    StbAdminModules::Callback_Modules();
  }

  /**
   * Run this before the 'drop table' block
   */
  protected static function UninstallHelper(
    PDO $Pdo,
    array $Commands,
    bool $Commit = true
  ):void{
    /**
     * @var StbDatabase $Db
     * @var TgCallback $Webhook
     * @var TelegramBotLibrary $Bot
     * @var StbLanguageSys $Lang
     */
    global $Db, $Bot, $Webhook, $Lang;
    DebugTrace();
    $Pdo->beginTransaction();

    $Db->ModuleUninstall(self::ModName());

    $cmds = $Bot->MyCmdGet();
    foreach($Commands as $cmd):
      $cmds->Del($cmd[0]);
    endforeach;
    try{
      $Bot->MyCmdSet($cmds);
    }catch(TblException){
      self::MsgError($Pdo);
      return;
    }

    if($Commit):
      $Pdo->commit();
    endif;
  }
}