<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

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

/**
 * @version 2024.01.01.00
 */
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
      $temp = $Webhook->User->Id;
    else:
      $temp = $Webhook->Data->User->Id;
    endif;
    if(get_class($Webhook) === TgText::class):
      $temp = $Db->ChatGet($Webhook->Data->User->Id);
    else:
      $temp = $Db->ChatGet($Webhook->User->Id);
    endif;
    if($temp->Permission & StbDbAdminPerm::Cmds == false):
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
    $temp = $Db->ChatGet($Webhook->User->Id);
    if($temp->Permission & StbDbAdminPerm::Cmds == false):
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
    $temp = $Db->ChatGet($Webhook->User->Id);
    if($temp->Permission & StbDbAdminPerm::Cmds == false):
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
    global $Webhook, $Bot, $Db;
    DebugTrace();
    $temp = $Db->ChatGet($Webhook->User->Id);
    if($temp->Permission & StbDbAdminPerm::Cmds == false):
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
    $temp = $Db->ChatGet($Webhook->User->Id);
    if($temp->Permission & StbDbAdminPerm::Cmds == false):
      return;
    endif;
    $Db->ListenerAdd(
      TgText::class,
      StbAdmin::class,
      $Webhook->User->Id
    );
    $Db->VariableSet(
      StbDbVariables::Action->name,
      StbDbVariables::CmdEdit->name,
      StbAdmin::class,
      $Webhook->User->Id
    );
    $Db->VariableSet(
      StbDbVariables::CmdName->name,
      $Cmd,
      StbAdmin::class,
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
      StbAdmin::class,
      $Webhook->User->Id
    );
    $Db->VariableDel(
      StbDbVariables::CmdName->name,
      null,
      StbAdmin::class,
      $Webhook->User->Id
    );
    $Db->ListenerDel(
      TgText::class,
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
    $temp = $Db->ChatGet($Webhook->User->Id);
    if($temp->Permission & StbDbAdminPerm::Cmds == false):
      return;
    endif;
    $Bot->TextEdit(
      $Webhook->Data->Data->Chat->Id,
      $Webhook->Data->Data->Id,
      $Lang->Get('CommandName', Group: 'Admin')
    );
    $Db->ListenerAdd(
      TgText::class,
      StbAdmin::class,
      $Webhook->User->Id
    );
    $Db->VariableSet(
      StbDbVariables::Action,
      StbDbVariables::CmdAddName->name,
      StbAdmin::class,
      $Webhook->User->Id
    );
  }

  public static function Callback_CmdUp(
    string $Cmd
  ):void{
    /**
     * @var TgCallback $Webhook
     * @var TelegramBotLibrary $Bot
     * @var StbDatabase $Db
     */
    global $Webhook, $Bot, $Db;
    DebugTrace();
    $temp = $Db->ChatGet($Webhook->User->Id);
    if($temp->Permission & StbDbAdminPerm::Cmds == false):
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
    if(get_class($Webhook) === TgText::class):
      $temp = $Db->ChatGet($Webhook->Data->User->Id);
    else:
      $temp = $Db->ChatGet($Webhook->User->Id);
    endif;
    if($temp->Permission & StbDbAdminPerm::Cmds == false):
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
      $Db->CallBackHashSet(StbAdmin::Callback_AdminMenu(...))
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

  public static function CmdAddDescription():void{
    /**
     * @var TgText $Webhook
     * @var StbDatabase $Db
     * @var TelegramBotLibrary $Bot
     */
    global $Webhook, $Db, $Bot;
    DebugTrace();
    $chat = $Db->ChatGet($Webhook->Data->User->Id);
    if(($chat->Permission & StbDbAdminPerm::Cmds->value) == false):
      return;
    endif;
    $temp = $Db->VariableGetValue(
      StbDbVariables::CmdName,
      StbAdmin::class,
      $Webhook->Data->User->Id
    );
    $cmds = $Bot->MyCmdGet();
    $cmds->Add($temp, trim($Webhook->Text));
    $Bot->MyCmdSet($cmds);
    $Db->VariableDel(
      StbDbVariables::Action->name,
      null,
      StbAdmin::class,
      $Webhook->Data->User->Id
    );
    $Db->VariableDel(
      StbDbVariables::CmdName->name,
      null,
      StbAdmin::class,
      $Webhook->Data->User->Id
    );
    $Db->ListenerDel(
      TgText::class,
      $Webhook->Data->User->Id
    );
    StbAdminCmd::Callback_Commands();
  }

  public static function CmdAddName():void{
    /**
     * @var TgText $Webhook
     * @var StbDatabase $Db
     * @var TelegramBotLibrary $Bot
     * @var StbLanguageSys $Lang
     */
    global $Webhook, $Db, $Bot, $Lang;
    DebugTrace();
    $chat = $Db->ChatGet($Webhook->Data->User->Id);
    if(($chat->Permission & StbDbAdminPerm::Cmds->value) == false):
      return;
    endif;
    $Db->VariableSet(
      StbDbVariables::CmdName,
      trim($Webhook->Text),
      StbAdmin::class,
      $Webhook->Data->User->Id
    );
    $Db->VariableSet(
      StbDbVariables::Action,
      StbDbVariables::CmdAddDescription->name,
      StbAdmin::class,
      $Webhook->Data->User->Id
    );
    $Bot->TextSend(
      $Webhook->Data->User->Id,
      $Lang->Get('CommandDescription', Group: 'Admin')
    );
  }

  public static function CmdEdit():void{
    /**
     * @var TgText $Webhook
     * @var StbDatabase $Db
     * @var TelegramBotLibrary $Bot
     */
    global $Webhook, $Db, $Bot;
    DebugTrace();
    $chat = $Db->ChatGet($Webhook->Data->User->Id);
    if(($chat->Permission & StbDbAdminPerm::Cmds->value) == false):
      return;
    endif;
    $temp = $Db->VariableGetValue(
      StbDbVariables::CmdName,
      __CLASS__,
      $Webhook->Data->User->Id
    );
    $cmds = $Bot->MyCmdGet();
    $cmds->Add($temp, trim($Webhook->Text));
    $Bot->MyCmdSet($cmds);
    $Db->VariableDel(
      StbDbVariables::Action->name,
      null,
      __CLASS__,
      $Webhook->Data->User->Id
    );
    $Db->VariableDel(
      StbDbVariables::CmdName->name,
      null,
      __CLASS__,
      $Webhook->Data->User->Id
    );
    $Db->ListenerDel(
      TgText::class,
      $Webhook->Data->User->Id
    );
    StbAdminCmd::Callback_Cmd($temp);
  }
}