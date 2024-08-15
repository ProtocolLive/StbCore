<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\PhpLiveDb\PhpLiveDb;
use ProtocolLive\SimpleTelegramBot\NoStr\Fields\{
  Chats,
  LogTexts
};
use ProtocolLive\SimpleTelegramBot\NoStr\Tables;
use ProtocolLive\SimpleTelegramBot\StbEnums\{
  StbDbAdminPerm,
  StbDbVariables,
  StbResquestChatId
};
use ProtocolLive\TelegramBotLibrary\TblObjects\{
  TblCmd,
  TblException,
  TblMarkupInline,
  TblMarkupKeyboard,
  TblMarkupRemove
};
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgEnums\TgParseMode;
use ProtocolLive\TelegramBotLibrary\TgObjects\{
  TgCallback,
  TgText,
  TgUsersShared
};

/**
 * @version 2024.08.15.01
 */
abstract class StbAdmin{
  public static function Callback_Admin(
    TelegramBotLibrary $Bot,
    TgCallback|TgUsersShared $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    int $Admin,
    bool $ListenerDel = false
  ):true{
    DebugTrace();
    $chat = $Db->ChatGet($Webhook->Data->User->Id);
    if(($chat->Permission & StbDbAdminPerm::Admins->value) == false):
      $Bot->CallbackAnswer(
        $Webhook->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return true;
    endif;

    $Admin = $Db->ChatGet($Admin);
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
    if($Admin->Id !== Admin):
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
    foreach(StbDbAdminPerm::cases() as $perm):
      if($perm === StbDbAdminPerm::All
      or $perm === StbDbAdminPerm::None):
        continue;
      endif;
      $value = $Admin->Permission & $perm->value;
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
    $AdminName = $Admin->Name;
    if($Admin->NameLast !== null):
      $AdminName .= ' ' . $Admin->NameLast;
    endif;
    if($Admin->Nick !== null):
      $AdminName .= ' (' . $Admin->Nick . ')';
    endif;

    $msg = sprintf(
      $Lang->Get('Admin', Group: 'Admin'),
      $AdminName,
      date($Lang->Get('DateTime'), $Admin->Created)
    );

    if($Webhook instanceof TgCallback):
      $Bot->TextEdit(
        $msg,
        $Webhook->Message->Data->Chat->Id,
        $Webhook->Message->Data->Id,
        Markup: $mk
      );
    else:
      $Bot->TextSend(
        $Webhook->Data->Chat->Id,
        $msg,
        Markup: $mk
      );
    endif;

    if($ListenerDel):
      /**
       * @var TgUserShared $Webhook
       */
      $Db->ListenerDel(
        TgUsersShared::class,
        $Webhook->Data->User->Id
      );
    endif;
    return true;
  }

  public static function Callback_AdminAdd(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    $chat = $Db->ChatGet($Webhook->Data->User->Id);
    if(($chat->Permission & StbDbAdminPerm::Admins->value) == false):
      $Bot->CallbackAnswer(
        $Webhook->Data->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return;
    endif;
    $mk = new TblMarkupKeyboard(Resize: true, OneTime: true);
    $mk->ButtonRequestUser(
      0,
      0,
      $Lang->Get('AdminAddButton', Group: 'Admin'),
      Id: StbResquestChatId::AdminAdd->value,
      Bot: false
    );
    $Bot->TextSend(
      $Webhook->Data->User->Id,
      $Lang->Get('AdminAdd', Group: 'Admin'),
      Markup: $mk
    );
    try{
      //Somes times, got the error "Bad Request: message can't be deleted for everyone". I don't know why
      $Bot->MessageDelete(
        $Webhook->Message->Data->Chat->Id,
        $Webhook->Message->Data->Id
      );
    }catch(TblException){}
    $Db->ListenerAdd(
      TgUsersShared::class,
      __CLASS__,
      $Webhook->Data->User->Id
    );
  }

  public static function Callback_AdminDel(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    int $Id
  ):void{
    DebugTrace();
    $chat = $Db->ChatGet($Webhook->Data->User->Id);
    if(($chat->Permission & StbDbAdminPerm::Admins->value) == false):
      $Bot->CallbackAnswer(
        $Webhook->Data->Id,
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
      $Lang->Get('AdminDel', Group: 'Admin'),
      $Webhook->Message->Data->Chat->Id,
      $Webhook->Message->Data->Id,
      Markup: $mk
    );
  }

  public static function Callback_AdminDel2(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    int $Id
  ):void{
    DebugTrace();
    $chat = $Db->ChatGet($Webhook->Data->User->Id);
    if(($chat->Permission & StbDbAdminPerm::Admins->value) == false):
      $Bot->CallbackAnswer(
        $Webhook->Data->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return;
    endif;
    $Db->ChatEdit($Id, StbDbAdminPerm::None->value);
    self::Callback_Admins($Bot, $Webhook, $Db, $Lang);
  }

  public static function Callback_AdminMenu(
    TelegramBotLibrary $Bot,
    TblCmd|TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):true{
    DebugTrace();
    $chat = $Db->ChatGet($Webhook->Data->User->Id);
    if($chat === null):
      return true;
    endif;
    $mk = new TblMarkupInline;
    $line = 0;
    $col = 0;
    if($chat->Permission & StbDbAdminPerm::Admins->value):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('AdminsButton', Group: 'Admin'),
        $Db->CallBackHashSet(self::Callback_Admins(...))
      );
      self::JumpLineCheck($line, $col);
    endif;
    if($chat->Permission & StbDbAdminPerm::Modules->value):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('ModulesButton', Group: 'Admin'),
        $Db->CallBackHashSet(StbAdminModules::Callback_Modules(...))
      );
      self::JumpLineCheck($line, $col);
    endif;
    //Updates
    if($chat->Id === Admin):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('UpdatesButton', Group: 'Admin'),
        $Db->CallBackHashSet(self::Callback_Updates(...))
      );
      self::JumpLineCheck($line, $col);
    endif;
    //Commands
    if($chat->Permission & StbDbAdminPerm::Cmds->value):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('CommandsButton', Group: 'Admin'),
        $Db->CallBackHashSet(StbAdminCmd::Callback_Commands(...))
      );
      self::JumpLineCheck($line, $col);
    endif;
    //Stats
    if($chat->Permission & StbDbAdminPerm::Stats->value):
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
        $Lang->Get('AdminMenu', Group: 'Admin'),
        $Webhook->Message->Data->Chat->Id,
        $Webhook->Message->Data->Id,
        Markup: $mk
      );
    endif;
    return true;
  }

  public static function Callback_AdminPerm(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    int $Admin,
    int $Perm,
    bool $Grant = false
  ):void{
    DebugTrace();
    $chat = $Db->ChatGet($Webhook->Data->User->Id);
    if(($chat->Permission & StbDbAdminPerm::Admins->value) == false):
      $Bot->CallbackAnswer(
        $Webhook->Data->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return;
    endif;

    $Admin = $Db->ChatGet($Admin);
    if($Grant):
      $Perm = $Admin->Permission | $Perm;
    else:
      $Perm = $Admin->Permission & ~$Perm;
    endif;
    $Db->ChatEdit($Admin->Id, $Perm);
    self::Callback_Admin($Bot, $Webhook, $Db, $Lang, $Admin->Id);
  }

  public static function Callback_Admins(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    $chat = $Db->ChatGet($Webhook->Data->User->Id);
    if(($chat->Permission & StbDbAdminPerm::Admins->value) == false):
      $Bot->CallbackAnswer(
        $Webhook->Data->Id,
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
      $Lang->Get('Admins', Group: 'Admin'),
      $Webhook->Message->Data->Chat->Id,
      $Webhook->Message->Data->Id,
      Markup: $mk
    );
  }

  public static function Callback_Cancel(
    TelegramBotLibrary $Bot,
    TgText|TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
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
    self::Callback_Admins($Bot, $Webhook, $Db, $Lang);
  }

  public static function Callback_Stats(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    /**
     * @var PhpLiveDb $PlDb
     */
    global $PlDb;
    DebugTrace();
    $chats = $PlDb->Select(Tables::Chats)
    ->Fields('count(*) as count')
    ->Run();
    $msg = sprintf(
      $Lang->Get('Used', Group: 'Stats'),
      $chats[0]['count']
    );
    $Bot->TextSend(
      $Webhook->Data->User->Id,
      $msg,
      ParseMode: TgParseMode::Html,
      DisableNotification: true
    );

    $msg = '<b>' . $Lang->Get('Commands', Group: 'Stats') . '</b>' . PHP_EOL;
    $consult = $PlDb->Select(Tables::LogTexts)
    ->Fields(LogTexts::Event->value . ',count(' . LogTexts::Event->value . ') as count')
    ->Group(LogTexts::Event->value)
    ->Order('count desc')
    ->Limit(20);
    $consult->Run(Fetch: true);
    while(($event = $consult->Fetch()) !== false):
      $msg .= $event['count'] . ' - ' . $event[LogTexts::Event->value] . PHP_EOL;
    endwhile;
    $Bot->TextSend(
      $Webhook->Data->User->Id,
      $msg,
      ParseMode: TgParseMode::Html,
      DisableNotification: true
    );

    $msg = '<b>' . $Lang->Get('Logs', Group: 'Stats') . '</b>' . PHP_EOL;
    $consult = $PlDb->Select(Tables::LogTexts)
    ->JoinAdd(Tables::Chats, Chats::Id)
    ->Order(LogTexts::Time->value . ' desc')
    ->Limit(20);
    $consult->Run(Fetch: true);
    while(($log = $consult->Fetch()) !== false):
      $msg .= date('Y/m/d H:i:s', $log['time']) . ' - ';
      $msg .= $log[LogTexts::Event->value] . ' ';
      if($log[LogTexts::Msg->value] !== null):
        $msg .= $log[LogTexts::Msg->value];
      endif;
      $msg .= PHP_EOL;
      $msg .= $log[LogTexts::Chat->value] . ', ';
      $msg .= '<a href="tg://user?id=' . $log[LogTexts::Chat->value] . '">' . $log[Chats::Name->value];
      if($log[Chats::NameLast->value] !== null):
        $msg .= ' ' . $log[Chats::NameLast->value];
      endif;
      $msg .= '</a>';
      if($log[Chats::Nick->value] !== null):
        $msg .= ' @' . $log[Chats::Nick->value];
      endif;
      $msg .= PHP_EOL;
      $msg .= '-----------------------------' . PHP_EOL;
    endwhile;
    $Bot->TextSend(
      $Webhook->Data->User->Id,
      $msg,
      ParseMode: TgParseMode::Html
    );
  }

  public static function Callback_Updates(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    if($Webhook->Data->User->Id !== Admin):
      $Bot->TextSend(
        $Webhook->Data->User->Id,
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
    $stb = file_get_contents(DirSystem . '/sha1sum.txt') === $stb;
    if($stb):
      $stb = $Lang->Get('Yes');
    else:
      $stb = $Lang->Get('No');
    endif;
    $tbl = file_get_contents('https://raw.githubusercontent.com/ProtocolLive/TelegramBotLibrary/main/src/sha1sum.txt');
    $tbl = file_get_contents(DirSystem . '/vendor/protocollive/telegrambotlibrary/src/sha1sum.txt') === $tbl;
    if($tbl):
      $tbl = $Lang->Get('Yes');
    else:
      $tbl = $Lang->Get('No');
    endif;
    $Bot->TextEdit(
      sprintf(
        $Lang->Get('Updates', Group: 'Admin'),
        $stb,
        $tbl
      ),
      $Webhook->Message->Data->Chat->Id,
      $Webhook->Message->Data->Id,
      Markup: $mk
    );
  }

  public static function CmdId(
    TelegramBotLibrary $Bot,
    TblCmd $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
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
      $msg,
      ParseMode: TgParseMode::Html
    );
    $Db->UsageLog($Webhook->Data->User->Id, 'id');
  }

  public static function Command(
    TelegramBotLibrary $Bot,
    TblCmd $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    match($Webhook->Command){
      'admin' => self::Callback_AdminMenu($Bot, $Webhook, $Db, $Lang),
      'id' => self::CmdId($Bot, $Webhook, $Db, $Lang)
    };
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

  public static function Listener(
    TelegramBotLibrary $Bot,
    TgUsersShared|TgText $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):bool{
    DebugTrace();
    if($Webhook::class === TgUsersShared::class):
      return self::Listener_UserShared($Bot, $Webhook, $Db, $Lang);
    elseif($Webhook::class === TgText::class):
      return self::Listener_Text($Bot, $Webhook, $Db, $Lang);
    endif;
  }

  private static function Listener_UserShared(
    TelegramBotLibrary $Bot,
    TgUsersShared $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):true{
    DebugTrace();
    try{
      $Db->ChatEdit($Bot->ChatGet($Webhook->Users[0]));
      self::Callback_Admin($Bot, $Webhook, $Db, $Lang, $Webhook->Users[0], true);
      $Bot->TextSend(
        $Webhook->Data->User->Id,
        $Lang->Get('AdminAddPerms', Group: 'Admin'),
        Markup: new TblMarkupRemove
      );
    }catch(TblException){
      $Bot->TextSend(
        $Webhook->Data->User->Id,
        $Lang->Get('AdminNeedStart', Group: 'Admin'),
        Markup: new TblMarkupRemove
      );
    }
    return true;
  }

  private static function Listener_Text(
    TelegramBotLibrary $Bot,
    TgText $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):bool{
    DebugTrace();
    $temp = $Db->VariableGetValue(
      StbDbVariables::Action,
      __CLASS__,
      $Webhook->Data->User->Id
    );
    if($temp === null):
      return false;
    elseif($temp === StbDbVariables::CmdAddName->name):
      StbAdminCmd::CmdAddName($Bot, $Webhook, $Db, $Lang);
    elseif($temp === StbDbVariables::CmdAddDescription->name):
      StbAdminCmd::CmdAddDescription($Bot, $Webhook, $Db, $Lang);
    elseif($temp === StbDbVariables::CmdEdit->name):
      StbAdminCmd::CmdEdit($Bot, $Webhook, $Db, $Lang);
    endif;
    return true;
  }
}