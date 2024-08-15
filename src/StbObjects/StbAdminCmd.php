<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\SimpleTelegramBot\StbEnums\{
  StbDbAdminPerm,
  StbDbVariables
};
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
 * @version 2024.08.15.00
 */
abstract class StbAdminCmd{
  public static function Callback_Cmd(
    TelegramBotLibrary $Bot,
    TgCallback|TgText $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Cmd
  ):void{
    DebugTrace();
    if($Webhook::class === TgText::class):
      $temp = $Db->ChatGet($Webhook->Data->User->Id);
    else:
      $temp = $Db->ChatGet($Webhook->Data->User->Id);
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
        sprintf(
          $Lang->Get('Command', Group: 'Admin'),
          $Cmd,
          $description
        ),
        $Webhook->Message->Data->Chat->Id,
        $Webhook->Message->Data->Id,
        Markup: $mk
      );
    else:
      $Bot->TextSend(
        sprintf(
          $Lang->Get('Command', Group: 'Admin'),
          $Cmd,
          $description
        ),
        $Webhook->Data->Chat->Id,
        Markup: $mk
      );
    endif;
  }

  public static function Callback_CmdDel(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Cmd
  ):void{
    DebugTrace();
    $temp = $Db->ChatGet($Webhook->Data->User->Id);
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
      $Webhook->Message->Data->Chat->Id,
      $Webhook->Message->Data->Id,
      Markup: $mk
    );
  }

  public static function Callback_CmdDelOk(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Cmd
  ){
    $temp = $Db->ChatGet($Webhook->Data->User->Id);
    if($temp->Permission & StbDbAdminPerm::Cmds == false):
      return;
    endif;
    $cmds = $Bot->MyCmdGet();
    $cmds->Del($Cmd);
    $Bot->MyCmdSet($cmds);
    $Db->CommandDel($Cmd);
    self::Callback_Commands($Bot, $Webhook, $Db, $Lang);
  }

  public static function Callback_CmdDown(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Cmd
  ):void{
    DebugTrace();
    $temp = $Db->ChatGet($Webhook->Data->User->Id);
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
    self::Callback_Commands($Bot, $Webhook, $Db, $Lang);
  }

  public static function Callback_CmdEdit(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Cmd
  ){
    DebugTrace();
    $temp = $Db->ChatGet($Webhook->Data->User->Id);
    if($temp->Permission & StbDbAdminPerm::Cmds == false):
      return;
    endif;
    $Db->ListenerAdd(
      TgText::class,
      StbAdmin::class,
      $Webhook->Data->User->Id
    );
    $Db->VariableSet(
      StbDbVariables::Action->name,
      StbDbVariables::CmdEdit->name,
      StbAdmin::class,
      $Webhook->Data->User->Id
    );
    $Db->VariableSet(
      StbDbVariables::CmdName->name,
      $Cmd,
      StbAdmin::class,
      $Webhook->Data->User->Id
    );
    $mk = new TblMarkupInline;
    $mk->ButtonCallback(
      0,
      0,
      '🔙',
      $Db->CallBackHashSet(self::Callback_CmdEditCancel(...), $Cmd)
    );
    $Bot->TextEdit(
      $Lang->Get('CommandDescription', Group: 'Admin'),
      $Webhook->Message->Data->Chat->Id,
      $Webhook->Message->Data->Id,
      Markup: $mk
    );
  }

  public static function Callback_CmdEditCancel(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Cmd
  ):void{
    DebugTrace();
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
    self::Callback_Cmd($Bot, $Webhook, $Db, $Lang, $Cmd);
  }

  public static function Callback_CmdNew(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    $temp = $Db->ChatGet($Webhook->Data->User->Id);
    if($temp->Permission & StbDbAdminPerm::Cmds == false):
      return;
    endif;
    $Db->ListenerAdd(
      TgText::class,
      StbAdmin::class,
      $Webhook->Data->User->Id
    );
    $Db->VariableSet(
      StbDbVariables::Action,
      StbDbVariables::CmdAddName->name,
      StbAdmin::class,
      $Webhook->Data->User->Id
    );
    $Bot->TextEdit(
      $Lang->Get('CommandName', Group: 'Admin'),
      $Webhook->Message->Data->Chat->Id,
      $Webhook->Message->Data->Id
    );
  }

  public static function Callback_CmdUp(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Cmd
  ):void{
    DebugTrace();
    $temp = $Db->ChatGet($Webhook->Data->User->Id);
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
    self::Callback_Commands($Bot, $Webhook, $Db, $Lang);
  }

  public static function Callback_Commands(
    TelegramBotLibrary $Bot,
    TgCallback|TgText $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    if($Webhook instanceof TgCallback):
      $temp = $Webhook->Data->User->Id;
    else:
      $temp = $Webhook->Data->User->Id;
    endif;
    if($Webhook::class === TgText::class):
      $temp = $Db->ChatGet($Webhook->Data->User->Id);
    else:
      $temp = $Db->ChatGet($Webhook->Data->User->Id);
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
        $Lang->Get('CommandsButton', Group: 'Admin'),
        $Webhook->Message->Data->Chat->Id,
        $Webhook->Message->Data->Id,
        Markup: $mk
      );
    else:
      $Bot->TextSend(
        $Lang->Get('CommandsButton', Group: 'Admin'),
        $Webhook->Data->Chat->Id,
        Markup: $mk
      );
    endif;
  }

  public static function CmdAddDescription(
    TelegramBotLibrary $Bot,
    TgText $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
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
    StbAdminCmd::Callback_Commands($Bot, $Webhook, $Db, $Lang);
  }

  public static function CmdAddName(
    TelegramBotLibrary $Bot,
    TgText $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
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
      $Lang->Get('CommandDescription', Group: 'Admin'),
      $Webhook->Data->User->Id
    );
  }

  public static function CmdEdit(
    TelegramBotLibrary $Bot,
    TgText $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
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
    StbAdminCmd::Callback_Cmd($Bot, $Webhook, $Db, $Lang, $temp);
  }
}