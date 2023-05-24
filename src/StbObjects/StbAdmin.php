<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\PhpLiveDb\PhpLiveDb;
use ProtocolLive\TelegramBotLibrary\TblObjects\{
  TblCmd,
  TblMarkupInline,
  TblMarkupKeyboard,
  TblMarkupRemove
};
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgObjects\{
  TgCallback,
  TgParseMode,
  TgText,
  TgUserShared
};

/**
 * @version 2023.05.23.00
 */
abstract class StbAdmin{
  public static function Callback_Admin(
    int $Admin,
    bool $ListenerDel = false
  ):void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var TgCallback|TgUserShared $Webhook
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     */
    global $Bot, $Webhook, $Db, $Lang;
    DebugTrace();
    if($Webhook instanceof TgCallback):
      $id = $Webhook->User->Id;
    else:
      $id = $Webhook->Data->User->Id;
    endif;
    if(StbBotTools::AdminCheck($id, StbDbAdminPerm::Admins) === null):
      $Bot->CallbackAnswer(
        $Webhook->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return;
    endif;
    $line = 0;
    $col = 0;
    $mk = new TblMarkupInline();
    $mk->ButtonCallback(
      $line,
      $col++,
      '🔙',
      $Db->CallBackHashSet(self::Callback_Admins(...))
    );
    self::JumpLineCheck($line, $col, 2);
    if($Admin !== Admin):
      $mk->ButtonCallback(
        $line,
        $col++,
        '🗑️',
        $Db->CallBackHashSet(
          self::Callback_AdminDel(...),
          $Admin
        )
      );
      self::JumpLineCheck($line, $col, 2);
    endif;
    $admin = $Db->Admin($Admin);
    foreach(StbDbAdminPerm::cases() as $perm):
      if($perm === StbDbAdminPerm::All
      or $perm === StbDbAdminPerm::None):
        continue;
      endif;
      $value = $admin->Perms & $perm->value;
      $mk->ButtonCallback(
        $line,
        $col++,
        ($value ? '✅' : '') . $Lang->Get('Perm' . $perm->name, Group: 'Admin'),
        $Db->CallBackHashSet(
          self::Callback_AdminPerm(...),
          $Admin,
          $perm->value,
          !$value
        )
      );
      self::JumpLineCheck($line, $col, 2);
    endforeach;
    $AdminName = $admin->Name;
    if($admin->NameLast !== null):
      $AdminName .= ' ' . $admin->NameLast;
    endif;
    if($admin->Nick !== null):
      $AdminName .= ' (' . $admin->Nick . ')';
    endif;
    if($Webhook instanceof TgCallback):
      $Bot->TextEdit(
        $Webhook->Data->Data->Chat->Id,
        $Webhook->Data->Data->Id,
        sprintf(
          $Lang->Get('Admin', Group: 'Admin'),
          $AdminName,
          date($Lang->Get('DateTime'), $admin->Creation)
        ),
        Markup: $mk
      );
    else:
      $Bot->TextSend(
        $Webhook->Data->Chat->Id,
        sprintf(
          $Lang->Get('Admin', Group: 'Admin'),
          $AdminName,
          date($Lang->Get('DateTime'), $admin->Creation)
        ),
        Markup: $mk
      );
    endif;
    if($ListenerDel):
      /**
       * @var TgUserShared $Webhook
       */
      $Db->ListenerDel(
        TgRequestUser::class,
        $Webhook->Data->User->Id
      );
    endif;
  }

  public static function Callback_AdminAdd():void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var TgCallback $Webhook
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     */
    global $Bot, $Webhook, $Db, $Lang;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->User->Id, StbDbAdminPerm::Admins) === null):
      $Bot->CallbackAnswer(
        $Webhook->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return;
    endif;
    $mk = new TblMarkupKeyboard(Resize: true, OneTime: true);
    $mk->ButtonRequestUser(
      0,
      0,
      $Lang->Get('AdminAddButton', Group: 'Admin'),
      StbResquestChatId::AdminAdd->value,
      false
    );
    $Bot->TextSend(
      $Webhook->User->Id,
      $Lang->Get('AdminAdd', Group: 'Admin'),
      Markup: $mk
    );
    $Bot->MessageDelete(
      $Webhook->Data->Data->Chat->Id,
      $Webhook->Data->Data->Id
    );
    $Db->ListenerAdd(
      TgUserShared::class,
      __CLASS__,
      $Webhook->User->Id
    );
  }

  public static function Callback_AdminDel(
    int $Id
  ):void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var TgCallback $Webhook
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     */
    global $Bot, $Webhook, $Db, $Lang;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->User->Id, StbDbAdminPerm::Admins) === null
    or $Id === Admin):
      $Bot->CallbackAnswer(
        $Webhook->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return;
    endif;
    $mk = new TblMarkupInline;
    $mk->ButtonCallback(
      0,
      0,
      $Lang->Get('Back'),
      $Db->CallBackHashSet(
        self::Callback_Admin(...),
        $Id
      )
    );
    $mk->ButtonCallback(
      0,
      1,
      $Lang->Get('Yes'),
      $Db->CallBackHashSet(
        self::Callback_AdminDel2(...),
        $Id
      )
    );
    $Bot->TextEdit(
      $Webhook->Data->Data->Chat->Id,
      $Webhook->Data->Data->Id,
      $Lang->Get('AdminDel', Group: 'Admin'),
      Markup: $mk
    );
  }

  public static function Callback_AdminDel2(
    int $Id
  ):void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var TgCallback $Webhook
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     */
    global $Bot, $Webhook, $Db, $Lang;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->User->Id, StbDbAdminPerm::Admins) === null
    or $Id === Admin):
      $Bot->CallbackAnswer(
        $Webhook->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return;
    endif;
    $Db->AdminEdit($Id, StbDbAdminPerm::None->value);
    self::Callback_Admins();
  }

  public static function Callback_AdminMenu():void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var TblCmd|TgCallback $Webhook
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     */
    global $Bot, $Webhook, $Db, $Lang;
    DebugTrace();
    if($Webhook instanceof TblCmd):
      $id = $Webhook->Data->User->Id;
    else:
      $id = $Webhook->User->Id;
    endif;
    $user = StbBotTools::AdminCheck($id);
    if($user === null):
      return;
    endif;
    $mk = new TblMarkupInline;
    $line = 0;
    $col = 0;
    if($user->Perms & StbDbAdminPerm::Admins->value):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('AdminsButton', Group: 'Admin'),
        $Db->CallBackHashSet(self::Callback_Admins(...))
      );
      self::JumpLineCheck($line, $col);
    endif;
    if($user->Perms & StbDbAdminPerm::Modules->value):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('ModulesButton', Group: 'Admin'),
        $Db->CallBackHashSet(StbAdminModules::Callback_Modules(...))
      );
      self::JumpLineCheck($line, $col);
    endif;
    //Updates
    if($id === Admin):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('UpdatesButton', Group: 'Admin'),
        $Db->CallBackHashSet(self::Callback_Updates(...))
      );
      self::JumpLineCheck($line, $col);
    endif;
    //Commands
    if($user->Perms & StbDbAdminPerm::Cmds->value):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('CommandsButton', Group: 'Admin'),
        $Db->CallBackHashSet(StbAdminCmd::Callback_Commands(...))
      );
      self::JumpLineCheck($line, $col);
    endif;
    //Stats
    if($user->Perms & StbDbAdminPerm::Stats->value):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('StatsButton', Group: 'Admin'),
        $Db->CallBackHashSet(self::Callback_Stats(...))
      );
    endif;
    if($Webhook instanceof TblCmd):
      $Bot->TextSend(
        $Webhook->Data->User->Id,
        $Lang->Get('AdminMenu', Group: 'Admin'),
        Markup: $mk
      );
    else:
      $Bot->TextEdit(
        $Webhook->Data->Data->Chat->Id,
        $Webhook->Data->Data->Id,
        $Lang->Get('AdminMenu', Group: 'Admin'),
        Markup: $mk
      );
    endif;
  }

  public static function Callback_AdminPerm(
    int $Admin,
    int $Perm,
    bool $Grant = false
  ):void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var TgCallback $Webhook
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     */
    global $Bot, $Webhook, $Db, $Lang;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->User->Id, StbDbAdminPerm::Admins) === null
    or $Admin === Admin):
      $Bot->CallbackAnswer(
        $Webhook->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return;
    endif;
    $admin = $Db->Admin($Admin);
    if($Grant):
      $Perm = $admin->Perms | $Perm;
    else:
      $Perm = $admin->Perms & ~$Perm;
    endif;
    $Db->AdminEdit($Admin, $Perm);
    self::Callback_Admin($Admin);
  }

  public static function Callback_Admins():void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var TgCallback $Webhook
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     */
    global $Bot, $Webhook, $Db, $Lang;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->User->Id, StbDbAdminPerm::Admins) === null):
      $Bot->CallbackAnswer(
        $Webhook->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return;
    endif;
    $mk = new TblMarkupInline();
    $mk->ButtonCallback(
      0,
      0,
      '🔙',
      $Db->CallBackHashSet(self::Callback_AdminMenu(...))
    );
    $mk->ButtonCallback(
      0,
      1,
      '➕',
      $Db->CallBackHashSet(self::Callback_AdminAdd(...))
    );
    $line = 1;
    $col = 0;
    $Admins = $Db->Admins();
    foreach($Admins as $admin):
      if($admin->Name === null):
        $detail = $admin;
      else:
        $detail = $admin->Name;
      endif;
      $mk->ButtonCallback(
        $line,
        $col++,
        $detail,
        $Db->CallBackHashSet(
          self::Callback_Admin(...),
          $admin->Id
        )
      );
      self::JumpLineCheck($line, $col);
    endforeach;
    $Bot->TextEdit(
      $Webhook->Data->Data->Chat->Id,
      $Webhook->Data->Data->Id,
      $Lang->Get('Admins', Group: 'Admin'),
      Markup: $mk
    );
  }

  public static function Callback_Cancel():void{
    /**
     * @var TgText $Webhook
     * @var StbDatabase $Db
     */
    global $Webhook, $Db;
    DebugTrace();
    $Db->VariableDel(
      StbDbVariables::Action->name,
      null,
      __CLASS__,
      $Webhook->Data->User->Id
    );
    $Db->ListenerDel(
      TgText::class,
      $Webhook->Data->User->Id
    );
    self::Callback_Admins();
  }

  public static function Callback_Stats():void{
    /**
     * @var PhpLiveDb $PlDb
     * @var TelegramBotLibrary $Bot
     * @var TgCallback $Webhook
     * @var StbLanguageSys $Lang
     */
    global $PlDb, $Bot, $Webhook, $Lang;
    $consult = $PlDb->Select('chats');
    $consult->Fields('count(*) as count');
    $chats = $consult->Run();
    $msg = sprintf(
      '<b>' . $Lang->Get('Used', Group: 'Stats') . '</b>' . PHP_EOL,
      $chats[0]['count']
    );
    $msg .= PHP_EOL;
    $msg .= '<b>' . $Lang->Get('Commands', Group: 'Stats') . '</b>' . PHP_EOL;
    $consult = $PlDb->Select('sys_logs');
    $consult->Fields('event,count(event) as count');
    $consult->Group('event');
    $consult->Order('count desc');
    $consult->Limit(10);
    $consult->Run(Fetch: true);
    while(($event = $consult->Fetch()) !== false):
      $msg .= $event['count'] . ' - ' . $event['event'] . PHP_EOL;
    endwhile;
    $msg .= PHP_EOL;
    $msg .= '<b>' . $Lang->Get('Logs', Group: 'Stats') . '</b>' . PHP_EOL;
    $consult = $PlDb->Select('sys_logs');
    $consult->JoinAdd('chats', 'chat_id');
    $consult->Order('time desc');
    $consult->Limit(10);
    $consult->Run(Fetch: true);
    while(($log = $consult->Fetch()) !== false):
      $msg .= date('Y/m/d H:i:s', $log['time']) . ' - ';
      $msg .= $log['event'] . ' ';
      if($log['additional'] !== null):
        $msg .= $log['additional'];
      endif;
      $msg .= PHP_EOL;
      $msg .= $log['chat_id'] . ', ';
      if($log['nick'] !== null):
        $msg .= '@' . $log['nick'] . ', ';
      endif;
      $msg .= '<a href="tg://user?id=' . $log['chat_id'] . '">' . $log['name'] . ' ';
      if($log['name2'] !== null):
        $msg .= $log['name2'];
      endif;
      $msg .= '</a>' . PHP_EOL;
      $msg .= '-----------------------------' . PHP_EOL;
    endwhile;
    $Bot->TextSend(
      $Webhook->User->Id,
      $msg,
      ParseMode: TgParseMode::Html
    );
  }

  public static function Callback_Updates():void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var TgCallback $Webhook
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     */
    global $Bot, $Webhook, $Db, $Lang;
    DebugTrace();
    if($Webhook->User->Id !== Admin):
      $Bot->TextSend(
        $Webhook->User->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return;
    endif;
    $mk = new TblMarkupInline();
    $line = 0;
    $col = 0;
    $mk->ButtonCallback(
      $line,
      $col++,
      '🔙',
      $Db->CallBackHashSet(self::Callback_AdminMenu(...))
    );
    $stb = file_get_contents('https://raw.githubusercontent.com/ProtocolLive/SimpleTelegramBot/main/sha1sum.txt');
    $stb = str_replace("\n", "\r\n", $stb);
    $stb = file_get_contents(DirSystem . '/sha1sum.txt') === $stb;
    if($stb):
      $stb = $Lang->Get('Yes');
    else:
      $stb = $Lang->Get('No');
    endif;
    $tbl = file_get_contents('https://raw.githubusercontent.com/ProtocolLive/TelegramBotLibrary/main/src/sha1sum.txt');
    $tbl = str_replace("\n", "\r\n", $tbl);
    $tbl = file_get_contents(DirSystem . '/vendor/protocollive/telegrambotlibrary/src/sha1sum.txt') === $tbl;
    if($tbl):
      $tbl = $Lang->Get('Yes');
    else:
      $tbl = $Lang->Get('No');
    endif;
    $Bot->TextEdit(
      $Webhook->Data->Data->Chat->Id,
      $Webhook->Data->Data->Id,
      sprintf(
        $Lang->Get('Updates', Group: 'Admin'),
        $stb,
        $tbl
      ),
      Markup: $mk
    );
  }

  private static function CmdAddDescription():void{
    /**
     * @var TgText $Webhook
     * @var StbDatabase $Db
     * @var TelegramBotLibrary $Bot
     */
    global $Webhook, $Db, $Bot;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->Data->User->Id, StbDbAdminPerm::Cmds) === null):
      return;
    endif;
    $temp = $Db->VariableGet(
      StbDbVariables::CmdName->name,
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
    StbAdminCmd::Callback_Commands();
  }

  private static function CmdAddName():void{
    /**
     * @var TgText $Webhook
     * @var StbDatabase $Db
     * @var TelegramBotLibrary $Bot
     * @var StbLanguageSys $Lang
     */
    global $Webhook, $Db, $Bot, $Lang;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->Data->User->Id, StbDbAdminPerm::Cmds) === null):
      return;
    endif;
    $Db->VariableSet(
      StbDbVariables::CmdName->name,
      trim($Webhook->Text),
      __CLASS__,
      $Webhook->Data->User->Id
    );
    $Db->VariableSet(
      StbDbVariables::Action->name,
      StbDbVariables::CmdAddDescription->name,
      __CLASS__,
      $Webhook->Data->User->Id
    );
    $Bot->TextSend(
      $Webhook->Data->User->Id,
      $Lang->Get('CommandDescription', Group: 'Admin')
    );
  }

  private static function CmdEdit():void{
    /**
     * @var TgText $Webhook
     * @var StbDatabase $Db
     * @var TelegramBotLibrary $Bot
     */
    global $Webhook, $Db, $Bot;
    DebugTrace();
    if(StbBotTools::AdminCheck($Webhook->Data->User->Id, StbDbAdminPerm::Cmds) === null):
      return;
    endif;
    $temp = $Db->VariableGet(
      StbDbVariables::CmdName->name,
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

  public static function Command_admin():void{
    DebugTrace();
    self::Callback_AdminMenu();
  }

  public static function Command_id():void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var TblCmd $Webhook
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     */
    global $Bot, $Webhook, $Db, $Lang;
    DebugTrace();
    $msg = sprintf(
      $Lang->Get('MyId'),
      $Webhook->Data->User->Id,
    );
    if($Webhook->Data->Chat->Id !== $Webhook->Data->User->Id):
      $msg .= sprintf(
        $Lang->Get('MyIdChat'),
        $Webhook->Data->Chat->Id,
      );
    endif;
    $Bot->TextSend(
      $Webhook->Data->User->Id,
      $msg
    );
    $Db->UsageLog($Webhook->Data->User->Id, 'id');
  }

  private static function JumpLineCheck(
    int &$Line,
    int &$Col,
    int $PerLine = 3
  ):void{
    DebugTrace();
    if($Col === $PerLine):
      $Col = 0;
      $Line++;
    endif;
  }

  public static function Listener_TgRequestUser():bool{
    /**
     * @var TgUserShared $Webhook
     * @var TelegramBotLibrary $Bot
     */
    global $Webhook, $Bot, $Db, $Lang;
    $Db->ChatAdd($Bot->ChatGet($Webhook->UserId));
    self::Callback_Admin($Webhook->UserId, true);
    $Bot->TextSend(
      $Webhook->Data->User->Id,
      $Lang->Get('AdminAddPerms', Group: 'Admin'),
      Markup: new TblMarkupRemove
    );
    return false;
  }

  public static function Listener_Text():void{
    /**
     * @var TgText $Webhook
     * @var StbDatabase $Db
     */
    global $Webhook, $Db;
    DebugTrace();
    $temp = $Db->VariableGet(
      StbDbVariables::Action->name,
      __CLASS__,
      $Webhook->Data->User->Id
    );
    if($temp === null):
      return;
    elseif($temp === StbDbVariables::CmdAddName->name):
      self::CmdAddName();
    elseif($temp === StbDbVariables::CmdAddDescription->name):
      self::CmdAddDescription();
    elseif($temp === StbDbVariables::CmdEdit->name):
      self::CmdEdit();
    endif;
  }
}