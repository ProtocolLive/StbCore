<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\TelegramBotLibrary\TblObjects\TblMarkupInline;
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgObjects\TgCallback;

/**
 * @version 2024.02.11.00
 */
abstract class StbAdminModules{
  private static function Access(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):bool{
    DebugTrace();
    $chat = $Db->ChatGet($Webhook->Data->User->Id);
    if(($chat->Permission & StbDbAdminPerm::Modules->value) == false):
      $Bot->CallbackAnswer(
        $Webhook->Data->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return false;
    endif;
    return true;
  }

  public static function Callback_Modules(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    if(self::Access($Bot, $Webhook, $Db, $Lang) === false):
      return;
    endif;
    $mods = $Db->Modules();
    $mk = new TblMarkupInline;
    $line = 0;
    $col = 0;
    $mk->ButtonCallback(
      $line,
      $col++,
      $Lang->Get('Back'),
      $Db->CallBackHashSet(StbAdmin::Callback_AdminMenu(...))
    );
    $mk->ButtonCallback(
      $line,
      $col++,
      $Lang->Get('Add'),
      $Db->CallBackHashSet(self::Callback_ModuleAdd(...))
    );
    $line = 1;
    $col = 0;
    foreach($mods as $mod):
      if($Db->ModuleRestricted($mod['module'])):
        continue;
      endif;
      $mk->ButtonCallback(
        $line,
        $col++,
        str_replace('ProtocolLive\StbModules\\', '', $mod['module']),
        $Db->CallBackHashSet(self::Callback_Mod(...), $mod['module'])
      );
      if($col === 4):
        $col = 0;
        $line++;
      endif;
    endforeach;

    $Bot->TextEdit(
      $Webhook->Message->Data->Chat->Id,
      $Webhook->Message->Data->Id,
      $Lang->Get('Modules', Group: 'Module'),
      Markup: $mk
    );
  }

  public static function Callback_ModuleAdd(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    if(self::Access($Bot, $Webhook, $Db, $Lang) === false):
      return;
    endif;

    $modules = [];
    foreach(glob(DirModules . '/*', GLOB_ONLYDIR) as $module):
      $module = $module . '/index.php';
      if(is_file($module)):
        $module = file_get_contents($module);
        preg_match('/class (.*)[{ \r\n]/', $module, $class);
        $modules[] = 'ProtocolLive\StbModules\\' . trim($class[1]);
      endif;
    endforeach;
    $modules = array_diff($modules, array_column($Db->Modules(), 'module'));

    $line = 1;
    $col = 0;
    $mk = new TblMarkupInline;
    $mk->ButtonCallback(
      0,
      0,
      $Lang->Get('Back'),
      $Db->CallBackHashSet(self::Callback_Modules(...))
    );
    foreach($modules as $mod):
      $mk->ButtonCallback(
        $line,
        $col++,
        str_replace('ProtocolLive\StbModules\\', '', $mod),
        $Db->CallBackHashSet(self::Callback_InsModPic(...), $mod)
      );
      if($col === 4):
        $line++;
        $col = 0;
      endif;
    endforeach;

    $Bot->TextEdit(
      $Webhook->Message->Data->Chat->Id,
      $Webhook->Message->Data->Id,
      $Lang->Get('InstallPick', Group: 'Module'),
      Markup: $mk
    );
  }

  public static function Callback_InsModPic(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Module
  ):void{
    DebugTrace();
    if(self::Access($Bot, $Webhook, $Db, $Lang) === false):
      return;
    endif;

    $line = 0;
    $col = 0;
    $mk = new TblMarkupInline;
    if(is_callable($Module . '::Install') === false):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('Back'),
        $Db->CallBackHashSet(self::Callback_ModuleAdd(...))
      );
      $Bot->TextEdit(
        $Webhook->Message->Data->Chat->Id,
        $Webhook->Message->Data->Id,
        $Lang->Get('InstallNotFound', null, 'Module'),
        Markup: $mk
      );
      return;
    endif;
    if(is_callable($Module . '::Uninstall') === false):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('Back'),
        $Db->CallBackHashSet(self::Callback_ModuleAdd(...))
      );
      $Bot->TextEdit(
        $Webhook->Message->Data->Chat->Id,
        $Webhook->Message->Data->Id,
        $Lang->Get('UninstallNotFound', null, 'Module'),
        Markup: $mk
      );
      return;
    endif;
    call_user_func($Module . '::Install', $Bot, $Webhook, $Db, $Lang);
  }

  public static function Callback_Mod(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Module
  ):void{
    DebugTrace();
    if(self::Access($Bot, $Webhook, $Db, $Lang) === false):
      return;
    endif;

    $mk = new TblMarkupInline;
    $line = 0;
    $col = 0;
    $mk->ButtonCallback(
      $line,
      $col++,
      $Lang->Get('Back'),
      $Db->CallBackHashSet(self::Callback_Modules(...))
    );
    $mk->ButtonCallback(
      $line,
      $col++,
      $Lang->Get('UninstallButton', Group: 'Module'),
      $Db->CallBackHashSet(self::Callback_UniModPic1(...), $Module)
    );
    if(is_callable($Module . '::Plugin_Buttons')):
      call_user_func($Module . '::Plugin_Buttons', $Db, $mk);
    endif;
    $date = $Db->Modules($Module);
    $Bot->TextEdit(
      Admin,
      $Webhook->Message->Data->Id,
      sprintf(
        $Lang->Get('Module', Group: 'Module'),
        str_replace('ProtocolLive\StbModules\\', '', $Module),
        date(
          $Lang->Get('DateTime'),
          $date[0]['created']
        )
      ),
      Markup: $mk
    );
  }

  public static function Callback_UniModPic1(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Module
  ):void{
    DebugTrace();
    if(self::Access($Bot, $Webhook, $Db, $Lang) === false):
      return;
    endif;

    $mk = new TblMarkupInline;
    $mk->ButtonCallback(
      0,
      0,
      $Lang->Get('Back'),
      $Db->CallBackHashSet(self::Callback_Mod(...), $Module)
    );
    $mk->ButtonCallback(
      0,
      1,
      $Lang->Get('Yes'),
      $Db->CallBackHashSet(self::Callback_UniModPic2(...), $Module)
    );
    $Bot->MarkupEdit(
      Admin,
      $Webhook->Message->Data->Id,
      Markup: $mk
    );
  }

  public static function Callback_UniModPic2(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    string $Module
  ):void{
    DebugTrace();
    if(self::Access($Bot, $Webhook, $Db, $Lang) === false):
      return;
    endif;
    call_user_func($Module . '::Uninstall', $Bot, $Webhook, $Db, $Lang);
  }
}