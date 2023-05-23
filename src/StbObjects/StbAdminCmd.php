<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2023.05.23.00

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\TelegramBotLibrary\TblObjects\{
  TblCommands,
  TblMarkupInline
};
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgObjects\{
  TgCallback,
  TgText
};

abstract class StbAdminCmd{
  public static function Callback_Cmd(
    string $Cmd
  ):void{
    /**
     * @var TgCallback|TgText $Webhook
     * @var TelegramBotLibrary $Bot
     * @var StbLanguageSys $Lang
     * @var StbDatabase $Db
     */
    global $Webhook, $Bot, $Lang, $Db;
    DebugTrace();
    if($Webhook instanceof TgCallback):
      $id = $Webhook->User->Id;
    else:
      $id = $Webhook->Data->User->Id;
    endif;
    if(StbBotTools::AdminCheck($id, StbDbAdminPerm::Cmds) === null):
      return;
    endif;
    $description = $Bot->MyCmdGet()->Get($Cmd);
    $mk = new TblMarkupInline;
    $mk->ButtonCallback(
      0,
      0,
      '🔙',
      $Db->CallBackHashSet(self::Callback_Commands(...))
    );
    $mk->ButtonCallback(
      0,
      1,
      '📝',
      $Db->CallBackHashSet(self::Callback_CmdEdit(...), $Cmd)
    );
    $mk->ButtonCallback(
      0,
      2,
      '❌',
      $Db->CallBackHashSet(self::Callback_CmdDel(...), $Cmd)
    );
    if($Webhook instanceof TgCallback):
      $Bot->TextEdit(
        $Webhook->Data->Data->Chat->Id,
        $Webhook->Data->Data->Id,
        sprintf(
          $Lang->Get('Command', Group: 'Admin'),
          $Cmd,
          $description
        ),
        Markup: $mk
      );
    else:
      $Bot->TextSend(
        $Webhook->Data->Chat->Id,
        sprintf(
          $Lang->Get('Command', Group: 'Admin'),
          $Cmd,
          $description
        ),
        Markup: $mk
      );
    endif;
  }

  public static function Callback_CmdDel(
    string $Cmd
  ):void{
    /**
     * @var TgCallback $Webhook
     * @var StbDatabase $Db
     * @var TelegramBotLibrary $Bot
     */
    global $Webhook, $Db, $Bot;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->User->Id, StbDbAdminPerm::Cmds) === null):
      return;
    endif;
    $mk = new TblMarkupInline;
    $mk->ButtonCallback(
      0,
      0,
      '🔙',
      $Db->CallBackHashSet(self::Callback_Commands(...))
    );
    $mk->ButtonCallback(
      0,
      1,
      '📝',
      $Db->CallBackHashSet(self::Callback_CmdEdit(...), $Cmd)
    );
    $mk->ButtonCallback(
      0,
      2,
      '✅',
      $Db->CallBackHashSet(self::Callback_CmdDelOk(...), $Cmd)
    );
    $Bot->MarkupEdit(
      $Webhook->Data->Data->Chat->Id,
      $Webhook->Data->Data->Id,
      Markup: $mk
    );
  }

  public static function Callback_CmdDelOk(
    string $Cmd
  ){
    /**
     * @var TgCallback $Webhook
     * @var TelegramBotLibrary $Bot
     * @var StbDatabase $Db
     */
    global $Webhook, $Bot, $Db;
    if(StbBotTools::AdminCheck($Webhook->User->Id, StbDbAdminPerm::Cmds) === null):
      return;
    endif;
    $cmds = $Bot->MyCmdGet();
    $cmds->Del($Cmd);
    $Bot->MyCmdSet($cmds);
    $Db->CommandDel($Cmd);
    self::Callback_Commands();
  }

  public static function Callback_CmdDown(
    string $Cmd
  ):void{
    /**
     * @var TgCallback $Webhook
     * @var TelegramBotLibrary $Bot
     */
    global $Webhook, $Bot;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->User->Id, StbDbAdminPerm::Cmds) === null):
      return;
    endif;
    $CmdsNew = new TblCommands;
    $CmdsOld = $Bot->MyCmdGet()->Get();
    $DescrBackup = null;
    foreach($CmdsOld as $cmd => $descr):
      if($cmd === $Cmd):
        $DescrBackup = $descr;
        continue;
      else:
        $CmdsNew->Add($cmd, $descr);
      endif;
      if($DescrBackup !== null):
        $CmdsNew->Add($Cmd, $DescrBackup);
        $DescrBackup = null;
      endif;
    endforeach;
    $Bot->MyCmdSet($CmdsNew);
    self::Callback_Commands();
  }

  public static function Callback_CmdEdit(
    string $Cmd
  ){
    /**
     * @var TgCallback $Webhook
     * @var StbDatabase $Db
     * @var TelegramBotLibrary $Bot
     * @var StbLanguageSys $Lang
     */
    global $Webhook, $Db, $Bot, $Lang;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->User->Id, StbDbAdminPerm::Cmds) === null):
      return;
    endif;
    $Db->ListenerAdd(
      StbDbListeners::Text,
      __CLASS__,
      $Webhook->User->Id
    );
    $Db->VariableSet(
      StbDbVariables::Action->name,
      StbDbVariables::CmdEdit->name,
      __CLASS__,
      $Webhook->User->Id
    );
    $Db->VariableSet(
      StbDbVariables::CmdName->name,
      $Cmd,
      __CLASS__,
      $Webhook->User->Id
    );
    $mk = new TblMarkupInline;
    $mk->ButtonCallback(
      0,
      0,
      '🔙',
      $Db->CallBackHashSet(self::Callback_CmdEditCancel(...), $Cmd)
    );
    $Bot->TextEdit(
      $Webhook->Data->Data->Chat->Id,
      $Webhook->Data->Data->Id,
      $Lang->Get('CommandDescription', Group: 'Admin'),
      Markup: $mk
    );
  }

  public static function Callback_CmdEditCancel(
    string $Cmd
  ):void{
    /**
     * @var StbDatabase $Db
     * @var TgCallback $Webhook
     */
    global $Db, $Webhook;
    DebugTrace();
    $Db->VariableDel(
      StbDbVariables::Action->name,
      null,
      __CLASS__,
      $Webhook->User->Id
    );
    $Db->VariableDel(
      StbDbVariables::CmdName->name,
      null,
      __CLASS__,
      $Webhook->User->Id
    );
    $Db->ListenerDel(
      StbDbListeners::Text,
      $Webhook->User->Id
    );
    self::Callback_Cmd($Cmd);
  }

  public static function Callback_CmdNew():void{
    /**
     * @var StbDatabase $Db
     * @var TgCallback $Webhook
     * @var TelegramBotLibrary $Bot
     * @var StbLanguageSys $Lang
     */
    global $Db, $Webhook, $Bot, $Lang;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->User->Id, StbDbAdminPerm::Cmds) === null):
      return;
    endif;
    $Bot->TextEdit(
      $Webhook->Data->Data->Chat->Id,
      $Webhook->Data->Data->Id,
      $Lang->Get('CommandName', Group: 'Admin')
    );
    $Db->ListenerAdd(
      StbDbListeners::Text,
      __CLASS__,
      $Webhook->User->Id
    );
    $Db->VariableSet(
      StbDbVariables::Action->name,
      StbDbVariables::CmdAddName->name,
      __CLASS__,
      $Webhook->User->Id
    );
  }

  public static function Callback_CmdUp(
    string $Cmd
  ):void{
    /**
     * @var TgCallback $Webhook
     * @var TelegramBotLibrary $Bot
     */
    global $Webhook, $Bot;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->User->Id, StbDbAdminPerm::Cmds) === null):
      return;
    endif;
    $CmdsNew = new TblCommands;
    $CmdsOld = $Bot->MyCmdGet()->Get();
    $BackupCmd = null;
    $BackupDescr = null;
    $first = true;
    $moved = false;
    foreach($CmdsOld as $cmd => $descr):
      if($first):
        $BackupCmd = $cmd;
        $BackupDescr = $descr;
      elseif($cmd == $Cmd):
        $CmdsNew->Add($cmd, $descr);
        $CmdsNew->Add($BackupCmd, $BackupDescr);
        $moved = true;
      elseif($moved):
        $CmdsNew->Add($cmd, $descr);
      else:
        $CmdsNew->Add($BackupCmd, $BackupDescr);
        $BackupCmd = $cmd;
        $BackupDescr = $descr;
      endif;
      $first = false;
    endforeach;
    $Bot->MyCmdSet($CmdsNew);
    self::Callback_Commands();
  }

  public static function Callback_Commands():void{
    /**
     * @var StbDatabase $Db
     * @var TgCallback|TgText $Webhook
     * @var TelegramBotLibrary $Bot
     * @var StbLanguageSys $Lang
     */
    global $Db, $Webhook, $Bot, $Lang;
    DebugTrace();
    if($Webhook instanceof TgCallback):
      $temp = $Webhook->User->Id;
    else:
      $temp = $Webhook->Data->User->Id;
    endif;
    if(StbBotTools::AdminCheck($temp, StbDbAdminPerm::Cmds) === null):
      return;
    endif;
    $mk = new TblMarkupInline;
    $line = 0;
    $col = 0;
    $i = 0;
    $mk->ButtonCallback(
      $line,
      0,
      '🔙',
      $Db->CallBackHashSet(StbAdmin::Command_admin(...))
    );
    $mk->ButtonCallback(
      $line++,
      1,
      '🆕',
      $Db->CallBackHashSet(self::Callback_CmdNew(...))
    );
    $cmds = $Bot->MyCmdGet()->Get();
    $last = count($cmds) - 1;
    foreach($cmds as $cmd => $descr):
      $mk->ButtonCallback(
        $line,
        $col++,
        $cmd,
        $Db->CallBackHashSet(self::Callback_Cmd(...), $cmd)
      );
      if($i < $last):
        $mk->ButtonCallback(
          $line,
          $col++,
          '🔽',
          $Db->CallBackHashSet(self::Callback_CmdDown(...), $cmd)
        );
      endif;
      if($i > 0):
        $mk->ButtonCallback(
          $line,
          $col++,
          '🔼',
          $Db->CallBackHashSet(self::Callback_CmdUp(...), $cmd)
        );
      endif;
      $i++;
      $line++;
      $col = 0;
    endforeach;
    if($Webhook instanceof TgCallback):
      $Bot->TextEdit(
        $Webhook->Data->Data->Chat->Id,
        $Webhook->Data->Data->Id,
        $Lang->Get('CommandsButton', Group: 'Admin'),
        Markup: $mk
      );
    else:
      $Bot->TextSend(
        $Webhook->Data->Chat->Id,
        $Lang->Get('CommandsButton', Group: 'Admin'),
        Markup: $mk
      );
    endif;
  }
}