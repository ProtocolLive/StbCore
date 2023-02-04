<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/FuncoesComuns
//2023.02.04.00

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\SimpleTelegramBot\StbObjects\{
  StbDatabase,
  StbDbAdminData,
  StbDbAdminPerm,
  StbLog
};
use ProtocolLive\TelegramBotLibrary\TblObjects\{
  TblCmd,
  TblData,
  TblException,
  TblWebhook
};
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgObjects\{
  TgCallback,
  TgChat,
  TgChatTitle,
  TgGroupStatusMy,
  TgInlineQuery,
  TgInvoiceCheckout,
  TgInvoiceShipping,
  TgParseMode,
  TgPhoto,
  TgText,
  TgUpdateType,
  TgUser
};

abstract class StbBotTools{
  public static function Action_():void{
    /**
     * @var TelegramBotLibrary $Bot
     */
    global $Bot, $Webhook;
    DebugTrace();
    $Webhook = $Bot->WebhookGet();
    if($Webhook === null):
      return;
    endif;
  
    if(get_class($Webhook) === TblCmd::class)://prevent TblCmdEdited
      self::Update_Cmd();
    elseif($Webhook instanceof TgCallback):
      self::Update_Callback();
    elseif(get_class($Webhook) === TgText::class)://prevent TgTextEdited
      self::Update_Text();
    elseif($Webhook instanceof TgPhoto):
      self::Update_ListenerDual(StbDbListeners::Photo);
    elseif($Webhook instanceof TgInvoiceCheckout):
      self::Update_ListenerSimple(StbDbListeners::InvoiceCheckout);
    elseif($Webhook instanceof TgInvoiceShipping):
      self::Update_ListenerSimple(StbDbListeners::InvoiceShipping);
    elseif($Webhook instanceof TgInlineQuery):
      self::Update_ListenerSimple(StbDbListeners::InlineQuery);
    elseif($Webhook instanceof TgGroupStatusMy):
      self::Update_ListenerSimple(StbDbListeners::ChatMy);
    elseif($Webhook instanceof TgChatTitle):
      self::Update_ListenerSimple(StbDbListeners::Chat);
    endif;
  }

  public static function Action_WebhookDel():void{
    /**
    * @var TblData $BotData
    */
    global $BotData;
    $Webhook = new TblWebhook($BotData);
    try{
      $Webhook->Del();
    }catch(TblException $e){
      echo $e->getMessage();
    }
  }

  public static function Action_WebhookGet():void{
    /**
     * @var TblData $BotData
     */
    global $BotData;
    $Webhook = new TblWebhook($BotData);
    $temp = $Webhook->Get();
    echo 'URL: ' . $temp['url'] . '<br>';
    echo 'Certificate: ' . ($temp['has_custom_certificate'] ? 'Yes' : 'No') . '<br>';
    echo 'Pending updates: ' . $temp['pending_update_count'] . '<br>';
    echo 'Max connections: ' . ($temp['max_connections'] ?? 0) . '<br>';
    echo 'Server: ' . ($temp['ip_address'] ?? 'None') . '<br>';
    echo 'Updates: ';
    if(isset($temp['allowed_updates'])):
      foreach($temp['allowed_updates'] as $update):
        echo $update . ', ';
      endforeach;
    else:
      echo 'None';
    endif;
    echo '<br>Last sync error: ';
    if(isset($temp['last_synchronization_error_date'])):
      echo date('Y-m-d H:i:s', $temp['last_synchronization_error_date']);
    else:
      echo 'Never';
    endif;
    echo '<br>Last error: ';
    if(isset($temp['last_error_date'])):
      echo date('Y-m-d H:i:s', $temp['last_error_date']) . ' - ';
      echo $temp['last_error_message'];
    else:
      echo 'None';
    endif;
  }

  public static function Action_WebhookSet():void{
    /**
     * @var TblData $BotData
     */
    global $BotData;
    $Webhook = new TblWebhook($BotData);
    try{
      $Webhook->Set(
        $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'],
        Updates: TgUpdateType::cases(),
        TokenWebhook: $BotData->TokenWebhook
      );
      echo '<p>Webhook set</p>';
      echo '<p><a href="index.php?a=WebhookGet">Click here see details</a></p>';
    }catch(TblException $e){
      echo '<p>Webhook fails</p>';
      echo '<p>' . $e->getMessage() . '</p>';
    }
  }

  /**
   * Check if the user permission match and return the user if true
   */
  public static function AdminCheck(
    int $Id,
    StbDbAdminPerm $Level = StbDbAdminPerm::All
  ):StbDbAdminData|null{
    /**
     * @var StbDatabase $Db
     */
    global $Db;
    $user = $Db->Admin($Id);
    if($user === false
    or ($user->Perms & $Level->value) === false):
      return null;
    else:
      return $user;
    endif;
  }

  public static function Cron():void{
    StbModuleTools::Load($_SERVER['Cron']);
    call_user_func($_SERVER['Cron'] . '::Cron');
    StbBotTools::Log(
      StbLog::Cron,
      'Time: ' . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'])
    );
  }

  public static function Log(
    int $Type,
    string $Msg,
    bool $NewLine = true
  ):void{
    /**
     * @var TblData TblBotData
     */
    global $BotData;
    DebugTrace();
    if(($BotData->Log & $Type) === false):
      return;
    endif;
    $Msg = date('Y-m-d H:i:s') . PHP_EOL . $Msg . PHP_EOL;
    if($NewLine):
      $Msg .= PHP_EOL;
    endif;
    if($Type === StbLog::Cron):
      $file = 'cron';
    endif;
    file_put_contents(DirLogs . '/' . $file . '.log', $Msg, FILE_APPEND);
  }

  public static function SendUserCmd(
    string $Command,
    string $EventAdditional = null
  ):bool{
    /**
     * @var TelegramBotLibrary $Bot
     * @var TblCmd $Webhook
     * @var StbDatabase $Db
     */
    global $Bot, $Webhook, $Db;
    DebugTrace();
    $Photo = false;
    $Text = false;
    $data = $Db->UserGet($Webhook->Data->User->Id);
    $lang = $data->Language ?? DefaultLanguage;
    if(is_dir(DirUserCmds . '/' . $lang) === false):
      $lang = DefaultLanguage;
    endif;
    $File = DirUserCmds . '/' . $lang . '/' . $Command;
  
    foreach(['jpg', 'png', 'gif'] as $ext):
      $temp = $File . '.' . $ext;
      if(is_file($temp)):
        $Bot->PhotoSend(
          $Webhook->Data->Chat->Id,
          $temp
        );
        $Photo = true;
        break;
      endif;
    endforeach;
  
    $File .= '.txt';
    if(is_file($File)):
      $text = file_get_contents($File);
      $text = str_replace('##NAME##', $Webhook->Data->User->Name, $text);
      $text = explode('##BREAK##', $text);
      foreach($text as $txt):
        $Bot->TextSend(
          $Webhook->Data->Chat->Id,
          $txt,
          ParseMode: TgParseMode::Html
        );
      endforeach;
      $Text = true;
    endif;
  
    if($Photo or $Text):
      $Db->UsageLog($Webhook->Data->Chat->Id, $Command, $EventAdditional);
      return true;
    else:
      return false;
    endif;
  }

  /**
   * @throws TypeError
   */
  public static function Tgchat2Tguser(
    TgChat $Chat
  ):TgUser{
    return new TgUser([
      'id' => $Chat->Id,
      'first_name' => $Chat->Name,
      'last_name' => null,
      'username' => $Chat->Nick
    ]);
  }

  private static function Update_Callback():void{
    /**
     * @var TgCallback $Webhook
     * @var StbDatabase $Db
     * @var TelegramBotLibrary $bot
     * @var StbLanguageSys $Lang
     */
    global $Webhook, $Db, $Bot, $Lang;
    $Db->UserSeen($Webhook->User);
    if($Db->CallBackHashRun($Webhook->Callback) === false):
      $Bot->CallbackAnswer(
        $Webhook->Id,
        $Lang->Get('ButtonWithoutAction', Group: 'Errors')
      );
    endif;
  }

  private static function Update_Cmd():void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var StbDatabase $Db
     * @var TblCmd $Webhook
     */
    global $Bot, $Db, $Webhook;
    $Db->UserSeen($Webhook->Data->User);
  
    //In a group, with many bots, the commands have the target bot.
    //This block check the target and caches the bot name
    if($Webhook->Data->Chat instanceof TgChat):
      $user = $Bot->MyGet();
      $user = $user->Nick;
      if($Webhook->Target !== null
      and $Webhook->Target !== $user):
        return;
      endif;
    endif;
  
    //Module command
    $module = $Db->Commands($Webhook->Command);
    if($module !== []):
      StbModuleTools::Load($module[0]['module']);
      call_user_func($module[0]['module'] . '::Command_' . $Webhook->Command);
      return;
    endif;
  
    if(StbBotTools::SendUserCmd($Webhook->Command) === false):
      StbBotTools::SendUserCmd('unknown');
    endif;
  }

  private static function Update_ListenerDual(
    StbDbListeners $Listener
  ):void{
    /**
     * @var TgPhoto $Webhook
     * @var StbDatabase $Db
     */
    global $Db, $Webhook;
    foreach($Db->ListenerGet($Listener) as $listener):
      StbModuleTools::Load($listener['module']);
      if(call_user_func($listener['module'] . '::Listener_' . $Listener->name) === false):
        return;
      endif;
    endforeach;
    foreach($Db->ListenerGet($Listener, $Webhook->Data->Chat->Id) as $listener):
      StbModuleTools::Load($listener['module']);
      if(call_user_func($listener['module'] . '::Listener_' . $Listener->name) === false):
        return;
      endif;
    endforeach;
  }

  private static function Update_ListenerSimple(
    StbDbListeners $Listener
  ):void{
    /**
     * @var StbDatabase $Db
     */
    global $Db;
    foreach($Db->ListenerGet($Listener) as $listener):
      StbModuleTools::Load($listener['module']);
      if(call_user_func($listener['module'] . '::Listener_' . $Listener->name) === false):
        return;
      endif;
    endforeach;
  }

  private static function Update_Text():void{
    /**
     * @var TgText $Webhook
     * @var StbDatabase $Db
     */
    global $Db, $Webhook;
    $Db->UserSeen($Webhook->Data->User);
    $Run = false;
    foreach($Db->ListenerGet(StbDbListeners::Text) as $listener):
      $Run = true;
      StbModuleTools::Load($listener['module']);
      if(call_user_func($listener['module'] . '::Listener_Text') === false):
        return;
      endif;
    endforeach;
    foreach($Db->ListenerGet(StbDbListeners::Text, $Webhook->Data->Chat->Id) as $listener):
      $Run = true;
      StbModuleTools::Load($listener['module']);
      if(call_user_func($listener['module'] . '::Listener_Text') === false):
        return;
      endif;
    endforeach;
    if($Run === false
    and $Webhook->Data->Chat instanceof TgUser):
      StbBotTools::SendUserCmd('dontknow', $Webhook->Text);
    endif;
    return;
  }
}