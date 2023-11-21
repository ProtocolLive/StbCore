<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\TelegramBotLibrary\TblObjects\TblException;
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgObjects\TgCallback;

/**
 * @version 2023.11.21.00
 */
abstract class StbModuleHelper{
  /**
   * Run this after the 'create table' block to begin the transaction
   */
  protected static function InstallHelper(
    string $Module,
    array $Commands = [],
    bool $Commit = true
  ):void{
    /**
     * @var StbDatabase $Db
     * @var TgCallback $Webhook
     * @var TelegramBotLibrary $Bot
     * @var StbLanguageSys $Lang
     */
    global $Db, $Webhook, $Bot, $Lang;
    $pdo = $Db->GetCustom();
    $pdo->beginTransaction();

    if($Db->ModuleInstall($Module) === false):
      self::MsgError();
      error_log('Fail to install module ' . $Module);
      return;
    endif;

    $cmds = $Bot->MyCmdGet();
    foreach($Commands as $cmd):
      $cmds->Add($cmd[0], $cmd[1]);
      if($Db->CommandAdd($cmd[0], $Module) === false):
        self::MsgError();
        error_log('Fail to add the command ' . $cmd[0]);
        return;
      endif;
    endforeach;
    try{
      $Bot->MyCmdSet($cmds);
    }catch(TblException){
      self::MsgError();
      error_log('Fail to add the commands');
      return;
    }

    $Bot->CallbackAnswer(
      $Webhook->Id,
      sprintf($Lang->Get('InstallOk', Group: 'Module'))
    );
    if($Commit):
      $pdo->commit();
      StbAdminModules::Callback_Modules();
    endif;
  }

  /**
   * Run this in the end to commit
   */
  protected static function InstallHelper2(){
    global $Db;
    $Db->GetCustom()->Commit();
    StbAdminModules::Callback_Modules();
  }

  protected static function MsgError():void{
    global $Bot, $Webhook, $Lang, $Db;
    DebugTrace();
    $Db->GetCustom()->rollBack();
    $Bot->CallbackAnswer(
      $Webhook->Id,
      sprintf($Lang->Get('Fail', Group: 'Module'))
    );
    StbAdminModules::Callback_Modules();
  }

  /**
   * Run this before the 'drop table' block to begin the transaction
   */
  protected static function UninstallHelper(
    string $Module,
    array $Commands = [],
    bool $Commit = true
  ):void{
    /**
     * @var StbDatabase $Db
     * @var TelegramBotLibrary $Bot
     */
    global $Db, $Bot;
    DebugTrace();
    $pdo = $Db->GetCustom();
    $pdo->beginTransaction();

    $Db->ModuleUninstall($Module);

    $cmds = $Bot->MyCmdGet();
    foreach($Commands as $cmd):
      $cmds->Del($cmd[0]);
    endforeach;
    try{
      $Bot->MyCmdSet($cmds);
    }catch(TblException){
      self::MsgError();
      return;
    }

    if($Commit):
      $pdo->commit();
    endif;
  }
}