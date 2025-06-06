<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use Exception;
use ProtocolLive\PhpLiveDb\PhpLiveDb;
use ProtocolLive\SimpleTelegramBot\StbEnums\{
  StbDbAdminPerm,
  StbError
};
use ProtocolLive\SimpleTelegramBot\StbParams\StbGlobalModuleCmds;
use ProtocolLive\TelegramBotLibrary\TblObjects\TblMarkupInline;
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgInterfaces\TgEventInterface;
use ProtocolLive\TelegramBotLibrary\TgObjects\TgCallback;

/**
 * @version 2024.12.22.00
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
  ):true{
    DebugTrace();
    if(self::Access($Bot, $Webhook, $Db, $Lang) === false):
      return true;
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
      $temp = explode('\\',  $mod['module']);
      $temp = array_pop($temp);
      $mk->ButtonCallback(
        $line,
        $col++,
        $temp,
        $Db->CallBackHashSet(self::Callback_Mod(...), $mod['module'])
      );
      if($col === 4):
        $col = 0;
        $line++;
      endif;
    endforeach;

    $Bot->TextEdit(
      $Lang->Get('Modules', Group: 'Module'),
      $Webhook->Message->Data->Chat->Id,
      $Webhook->Message->Data->Id,
      Markup: $mk
    );
    return true;
  }

  public static function Callback_ModuleAdd(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):true{
    DebugTrace();
    if(self::Access($Bot, $Webhook, $Db, $Lang) === false):
      return false;
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
      $Lang->Get('InstallPick', Group: 'Module'),
      $Webhook->Message->Data->Chat->Id,
      $Webhook->Message->Data->Id,
      Markup: $mk
    );
    return true;
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
    if(method_exists($Module, 'Install') === false):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('Back'),
        $Db->CallBackHashSet(self::Callback_ModuleAdd(...))
      );
      $Bot->TextEdit(
        $Lang->Get('InstallNotFound', null, 'Module'),
        $Webhook->Message->Data->Chat->Id,
        $Webhook->Message->Data->Id,
        Markup: $mk
      );
      return;
    endif;
    if(method_exists($Module, 'Uninstall') === false):
      $mk->ButtonCallback(
        $line,
        $col++,
        $Lang->Get('Back'),
        $Db->CallBackHashSet(self::Callback_ModuleAdd(...))
      );
      $Bot->TextEdit(
        $Lang->Get('UninstallNotFound', null, 'Module'),
        $Webhook->Message->Data->Chat->Id,
        $Webhook->Message->Data->Id,
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
  ):true{
    DebugTrace();
    if(self::Access($Bot, $Webhook, $Db, $Lang) === false):
      return true;
    endif;

    $mk = new TblMarkupInline;
    if(method_exists($Module, 'PluginButton_Module')):
      call_user_func($Module . '::PluginButton_Module', $Db, $mk);
    endif;
    $mk->ButtonCallback(
      0,
      0,
      $Lang->Get('Back'),
      $Db->CallBackHashSet(self::Callback_Modules(...))
    );
    $mk->ButtonCallback(
      0,
      1,
      $Lang->Get('UninstallButton', Group: 'Module'),
      $Db->CallBackHashSet(self::Callback_UniModPic1(...), $Module)
    );
    $date = $Db->Modules($Module);
    $temp = explode('\\', $Module);
    $temp = array_pop($temp);
    $Bot->TextEdit(
      sprintf(
        $Lang->Get('Module', Group: 'Module'),
        $temp,
        date(
          $Lang->Get('DateTime'),
          $date[0]['created']
        )
      ),
      Admin,
      $Webhook->Message->Data->Id,
      Markup: $mk
    );
    return true;
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

  /**
   * @param TgEventInterface[] $Listeners
   */
  public static function GlobalModuleInstall(
    string $Module,
    StbGlobalModuleCmds|null $Commands = null,
    array|null $Listeners = null
  ):bool{
    DebugTrace();
    $Bots = glob(dirname(__DIR__, 5) . '/Bot-*', GLOB_ONLYDIR);
    foreach($Bots as $bot):
      $config = $bot . '/config.php';
      $config = file_get_contents($config);
      preg_match('/const DbHost {0,}= {0,}["\'](.*)["\'];/', $config, $DbHost);
      preg_match('/const DbUser {0,}= {0,}["\'](.*)["\'];/', $config, $DbUser);
      preg_match('/const DbPwd {0,}= {0,}["\'](.*)["\'];/', $config, $DbPwd);
      preg_match('/const DbName {0,}= {0,}["\'](.*)["\'];/', $config, $DbName);
      try{
        $db = new StbDatabase(new PhpLiveDb($DbHost[1], $DbUser[1], $DbPwd[1], $DbName[1]));
        $db->GetCustom()->beginTransaction();
        $db->ModuleInstall($Module);
        foreach($Commands->Get() as $cmd):
          $db->CommandAdd($cmd->Name, $Module);
        endforeach;
        foreach($Listeners as $listener):
          if(in_array(TgEventInterface::class, class_implements($listener)) === false):
            throw new StbException(StbError::ListenerInvalid, 'Informed listener ' . $listener . ' not implement TgEventInterface');
          endif;
          $db->ListenerAdd($listener, $Module);
        endforeach;
        $db->GetCustom()->commit();
        return true;
      }catch(Exception){
        $db->GetCustom()->rollBack();
        return false;
      }
    endforeach;
    return false;
  }

  public static function GlobalModuleUninstall(
    string $Module
  ):bool{
    DebugTrace();
    $Bots = glob(dirname(__DIR__, 5) . '/Bot-*', GLOB_ONLYDIR);
    foreach($Bots as $bot):
      $config = $bot . '/config.php';
      $config = file_get_contents($config);
      preg_match('/const DbHost = \'(.*)\';/', $config, $DbHost);
      preg_match('/const DbUser = \'(.*)\';/', $config, $DbUser);
      preg_match('/const DbPwd = \'(.*)\';/', $config, $DbPwd);
      preg_match('/const DbName = \'(.*)\';/', $config, $DbName);
      try{
        $db = new StbDatabase(new PhpLiveDb($DbHost[1], $DbUser[1], $DbPwd[1], $DbName[1]));
        $db->ModuleUninstall($Module);
      }catch(Exception){
        return false;
      }
    endforeach;
    return true;
  }
}