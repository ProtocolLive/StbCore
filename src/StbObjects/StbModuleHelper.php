<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use PDO;
use ProtocolLive\TelegramBotLibrary\TblObjects\{
  TblCmd,
  TblData,
  TblException,
  TblMarkupInline
};
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgInterfaces\TgEventInterface;
use ProtocolLive\TelegramBotLibrary\TgObjects\TgCallback;

/**
 * @version 2024.11.26.00
 */
abstract class StbModuleHelper{
  private static array $InstallCommands = [];

  public abstract static function Command(
    TelegramBotLibrary $Bot,
    TblCmd $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void;

  public abstract static function Cron(
    TelegramBotLibrary $Bot,
    StbDatabase $Db,
    TblData $BotData
  ):void;

  /**
   * Run this function before InstallHelper
   * @param string $Module If null, the command is an txt 
   */
  protected static function InstallCmd(
    string $Name,
    string|null $Description = null,
    string|null $Module = null,
    bool $Public = true
  ):void{
    self::$InstallCommands[] = (object)[
      'Name' => $Name,
      'Description' => $Description,
      'Module' => $Module,
      'Public' => $Public
    ];
  }

  /**
   * Execute this after the 'create table' block (The create query has a transaction of its own).
   * If the module has commands, run InstallCmd first
   * @param string $Module Use \_\_CLASS__ constant
   * @param array $Commands Command to be added to Telegram menu
   * @param bool $Commit Use false if need to add listeners
   */
  protected static function InstallHelper(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Module,
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
    foreach(self::$InstallCommands as $cmd):
      if($cmd->Public):
        $cmds->Add($cmd->Name, $cmd->Description);
      endif;
      if($cmd->Module !== null):
        if($Db->CommandAdd($cmd->Name, $Module) === false):
          self::MsgError($Bot, $Webhook, $Db, $Lang);
          error_log('Fail to add the command ' . $cmd->Name);
          return;
        endif;
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
        $Webhook->Data->Id,
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
  ):void{
    DebugTrace();
    $Db->GetCustom()->commit();
    $Bot->CallbackAnswer(
      $Webhook->Data->Id,
      sprintf($Lang->Get('InstallOk', Group: 'Module'))
    );
    StbAdminModules::Callback_Modules($Bot, $Webhook, $Db, $Lang);
  }

  /**
   * @return bool If the listener are satisfied
   */
  public abstract static function Listener(
    TelegramBotLibrary $Bot,
    TgEventInterface $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):bool;

  protected static function MsgError(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    $Db->GetCustom()->rollBack();
    $Bot->CallbackAnswer(
      $Webhook->Data->Id,
      sprintf($Lang->Get('Fail', Group: 'Module'))
    );
    StbAdminModules::Callback_Modules($Bot, $Webhook, $Db, $Lang);
  }

  public abstract static function PluginButton_Module(
    StbDatabase $Db,
    TblMarkupInline $Markup
  ):void;

  /**
   * Run this before the 'drop table' block to begin the transaction. For commands and listeners, don't need the UninstallHelper2 because the cascade foreign key
   * @param bool $Commit Use false when you need to remove module tables or/and listeners. Don't forget the commit!
   * @return PDO|null Return the PDO object if $Commit is false
   */
  protected static function UninstallHelper(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Module,
    bool $Commit = true
  ):null{
    DebugTrace();
    $Db->GetCustom()->beginTransaction();

    $Db->ModuleUninstall($Module);

    $cmds = $Bot->MyCmdGet();
    foreach(self::$InstallCommands as $cmd):
      if($cmd->Public):
        $cmds->Del($cmd->Name);
      endif;
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
        $Webhook->Data->Id,
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
      $Webhook->Data->Id,
      sprintf($Lang->Get('UninstallOk', Group: 'Module'))
    );
    StbAdminModules::Callback_Modules($Bot, $Webhook, $Db, $Lang);
  }
}