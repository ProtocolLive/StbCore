<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use PDO;
use ProtocolLive\TelegramBotLibrary\TblObjects\TblException;
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgObjects\TgCallback;

/**
 * @version 2024.01.12.00
 */
abstract class StbModuleHelper{
  /**
   * Run this after the 'create table' block to begin the transaction
   * @param string $Module Use the complete name, with namespace. Better use \_\_CLASS__ constant
   */
  protected static function InstallHelper(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Module,
    array $Commands = [],
    bool $Commit = true
  ):void{
    DebugTrace();
    $Db->GetCustom()->beginTransaction();

    if($Db->ModuleInstall($Module) === false):
      self::MsgError($Bot, $Webhook, $Db, $Lang);
      error_log('Fail to install module ' . $Module);
      return;
    endif;

    $cmds = $Bot->MyCmdGet();
    foreach($Commands as $cmd):
      $cmds->Add($cmd[0], $cmd[1]);
      if($Db->CommandAdd($cmd[0], $Module) === false):
        self::MsgError($Bot, $Webhook, $Db, $Lang);
        error_log('Fail to add the command ' . $cmd[0]);
        return;
      endif;
    endforeach;
    try{
      $Bot->MyCmdSet($cmds);
    }catch(TblException){
      self::MsgError($Bot, $Webhook, $Db, $Lang);
      error_log('Fail to add the commands');
      return;
    }

    if($Commit):
      $Db->GetCustom()->commit();
      $Bot->CallbackAnswer(
        $Webhook->Id,
        sprintf($Lang->Get('InstallOk', Group: 'Module'))
      );
      StbAdminModules::Callback_Modules($Bot, $Webhook, $Db, $Lang);
    endif;
  }

  /**
   * Run this in the end to commit
   */
  protected static function InstallHelper2(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ){
    DebugTrace();
    $Db->GetCustom()->commit();
    $Bot->CallbackAnswer(
      $Webhook->Id,
      sprintf($Lang->Get('InstallOk', Group: 'Module'))
    );
    StbAdminModules::Callback_Modules($Bot, $Webhook, $Db, $Lang);
  }

  protected static function MsgError(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    $Db->GetCustom()->rollBack();
    $Bot->CallbackAnswer(
      $Webhook->Id,
      sprintf($Lang->Get('Fail', Group: 'Module'))
    );
    StbAdminModules::Callback_Modules($Bot, $Webhook, $Db, $Lang);
  }

  /**
   * Run this before the 'drop table' block to begin the transaction
   * @param bool $Commit Use false when you need to remove module tables or/and listeners. Don't forget the commit!
   * @return PDO|null Return the PDO object if $Commit is false
   */
  protected static function UninstallHelper(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Module,
    array $Commands = [],
    bool $Commit = true
  ):null{
    DebugTrace();
    $Db->GetCustom()->beginTransaction();

    $Db->ModuleUninstall($Module);

    $cmds = $Bot->MyCmdGet();
    foreach($Commands as $cmd):
      $cmds->Del($cmd[0]);
    endforeach;
    try{
      $Bot->MyCmdSet($cmds);
    }catch(TblException){
      self::MsgError($Bot, $Webhook, $Db, $Lang);
      return null;
    }

    if($Commit):
      $Db->GetCustom()->commit();
      $Bot->CallbackAnswer(
        $Webhook->Id,
        sprintf($Lang->Get('UninstallOk', Group: 'Module'))
      );
      StbAdminModules::Callback_Modules($Bot, $Webhook, $Db, $Lang);
      return null;
    endif;
  }

  protected static function UninstallHelper2(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    $Db->GetCustom()->Commit();
    $Bot->CallbackAnswer(
      $Webhook->Id,
      sprintf($Lang->Get('UninstallOk', Group: 'Module'))
    );
    StbAdminModules::Callback_Modules($Bot, $Webhook, $Db, $Lang);
  }
}