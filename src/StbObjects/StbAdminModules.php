<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\SimpleTelegramBot\Datas\ChatData;
use ProtocolLive\TelegramBotLibrary\TblObjects\TblMarkupInline;
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgObjects\TgCallback;

/**
 * @version 2024.01.08.00
 */
abstract class StbAdminModules{
  private static function Acesso():bool{
    global $Db, $Webhook, $Bot, $Lang;
    DebugTrace();
    $chat = $Db->ChatGet($Webhook->User->Id);
    if(($chat->Permission & StbDbAdminPerm::Modules->value) == false):
      $Bot->CallbackAnswer(
        $Webhook->Id,
        $Lang->Get('Denied', Group: 'Errors')
      );
      return false;
    endif;
    return true;
  }

  public static function Callback_Modules():void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     * @var TgCallback $Webhook
     */
    global $Bot, $Db, $Lang, $Webhook;
    DebugTrace();
    if(self::Acesso() === false):
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
      $Webhook->Data->Data->Chat->Id,
      $Webhook->Data->Data->Id,
      $Lang->Get('Modules', Group: 'Module'),
      Markup: $mk
    );
  }

  public static function Callback_ModuleAdd():void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     * @var TgCallback $Webhook
     */
    global $Bot, $Db, $Lang, $Webhook;
    DebugTrace();
    if(self::Acesso() === false):
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
      $Webhook->Data->Data->Chat->Id,
      $Webhook->Data->Data->Id,
      $Lang->Get('InstallPick', Group: 'Module'),
      Markup: $mk
    );
  }

  public static function Callback_InsModPic(
    string $Module
  ):void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var TgCallback $Webhook
     * @var StbLanguageSys $Lang
     * @var StbDatabase $Db
     * @var ChatData $chat
     */
    global $Bot, $Webhook, $Lang, $Db;
    DebugTrace();
    if(self::Acesso() === false):
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
        $Webhook->Data->Data->Chat->Id,
        $Webhook->Data->Data->Id,
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
        $Webhook->Data->Data->Chat->Id,
        $Webhook->Data->Data->Id,
        $Lang->Get('UninstallNotFound', null, 'Module'),
        Markup: $mk
      );
      return;
    endif;
    call_user_func($Module . '::Install', $Bot, $Webhook, $Db, $Lang);
  }

  public static function Callback_Mod(
    string $Module
  ):void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     * @var TgCallback $Webhook
     */
    global $Bot, $Db, $Lang, $Webhook;
    DebugTrace();
    if(self::Acesso() === false):
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
      call_user_func($Module . '::Plugin_Buttons', $mk);
    endif;
    $date = $Db->Modules($Module);
    $Bot->TextEdit(
      Admin,
      $Webhook->Data->Data->Id,
      sprintf(
        $Lang->Get('Module', Group: 'Module'),
        $Module,
        date(
          $Lang->Get('DateTime'),
          $date[0]['created']
        )
      ),
      Markup: $mk
    );
  }

  public static function Callback_UniModPic1(
    string $Module
  ):void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var StbLanguageSys $Lang
     * @var TgCallback $Webhook
     * @var StbDatabase $Db
     */
    global $Bot, $Lang, $Webhook, $Db;
    DebugTrace();
    if(self::Acesso() === false):
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
      $Webhook->Data->Data->Id,
      Markup: $mk
    );
  }

  public static function Callback_UniModPic2(
    string $Module
  ):void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var TgCallback $Webhook
     * @var StbDatabase $Db
     * @var StbLanguageSys $Lang
     */
    global $Bot, $Webhook, $Db, $Lang;
    DebugTrace();
    if(self::Acesso() === false):
      return;
    endif;
    require(DirModules . '/' . $Module . '/index.php');
    call_user_func($Module . '::Uninstall', $Bot, $Webhook, $Db, $Lang);
  }
}