<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/FuncoesComuns

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use Exception;
use ProtocolLive\PhpLiveDb\Types;
use ProtocolLive\SimpleTelegramBot\NoStr\Fields\LogUpdates;
use ProtocolLive\SimpleTelegramBot\NoStr\Tables;
use ProtocolLive\SimpleTelegramBot\StbObjects\{
  StbDatabase,
  StbLog
};
use ProtocolLive\TelegramBotLibrary\TblObjects\{
  TblCmd,
  TblData,
  TblException,
  TblLog,
  TblWebhook
};
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgEnums\{
  TgParseMode,
  TgUpdateType
};
use ProtocolLive\TelegramBotLibrary\TgObjects\{
  TgCallback,
  TgChat,
  TgText,
  TgUser
};
use ReflectionClass;
use TypeError;

/**
 * @version 2024.01.03.05
 */
abstract class StbBotTools{
  public static function Action_():void{
    /**
     * @var TelegramBotLibrary $Bot
     * @var StbDatabase $Db
     */
    global $Bot, $Webhook, $Db;
    DebugTrace();
    $Webhook = $Bot->WebhookGet();
    if($Webhook === null):
      return;
    endif;
    if(get_class($Webhook) === TblCmd::class)://prevent TblCmdEdited
      self::Update_Cmd();
      return;
    endif;
    if($Webhook instanceof TgCallback):
      self::Update_Callback();
      return;
    endif;
    if(get_class($Webhook) === TgText::class)://prevent TgTextEdited
      self::Update_Text();
      return;
    endif;
    //Some updates don't have TgMessageData
    $id = $Webhook->Data->User->Id ?? $Webhook->User->Id ?? null;
    $module = $Db->ListenerGet($Webhook, $id) ?? $Db->ListenerGet($Webhook);
    if($module === null):
      return;
    endif;
    call_user_func($module . '::Listener');
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
        ($_GET['server'] ?? $_SERVER['SERVER_NAME']) . $_SERVER['SCRIPT_NAME'],
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

  public static function Cron():void{
    call_user_func($_SERVER['Cron'] . '::Cron');
    StbBotTools::Log(
      StbLog::Cron,
      'Time: ' . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'])
    );
  }

  public static function Entry():void{
    self::ModuleAutoload();
    ArgV();
    $_GET['a'] ??= '';
    if(isset($_SERVER['Cron'])):
      self::Cron();
    elseif(is_callable(__CLASS__ . '::Action_' . $_GET['a'])):
      call_user_func(__CLASS__ . '::Action_' . $_GET['a']);
    endif;
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

  private static function ModuleAutoload():void{
    spl_autoload_register(function(string $Class){
      $temp = explode('\\', $Class);
      unset($temp[0], $temp[1]);
      require(DirModules . '/'. implode('/', $temp) . '/index.php');
    });
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
    $lang = $Webhook->Data->User->Language ?? DefaultLanguage;
    if(is_dir(DirTextCmds . '/' . $lang) === false):
      $lang = DefaultLanguage;
    endif;
    $File = DirTextCmds . '/' . $lang . '/' . $Command;
  
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

  public static function TblLog(
    int $Type,
    string $Msg
  ):void{
    global $PlDb;
    DebugTrace();
    $constants = new ReflectionClass(TblLog::class);
    $constants = $constants->getConstants();
    $constants = array_flip($constants);
    $Type = $constants[$Type];
    $PlDb->Insert(Tables::LogUpdates)
    ->FieldAdd(LogUpdates::Time, time(), Types::Int)
    ->FieldAdd(LogUpdates::Type, $Type, Types::Str)
    ->FieldAdd(LogUpdates::Update, $Msg, Types::Str)
    ->Run(HtmlSafe: false);
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
    $Db->ChatEdit($Webhook->User);
    $Lang->LanguageSet($Webhook->User->Language);
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
    global $Bot, $Db, $Webhook, $Lang;
    $Db->ChatEdit($Webhook->Data->User);
    try{
      $Lang->LanguageSet($Webhook->Data->User->Language);
    }catch(Exception){}
  
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
    if(is_callable($module . '::Command')):
      call_user_func($module . '::Command');
      return;
    endif;
  
    if(StbBotTools::SendUserCmd($Webhook->Command) === false):
      StbBotTools::SendUserCmd('unknown');
    endif;
  }

  private static function Update_Text():void{
    /**
     * @var TgText $Webhook
     * @var StbDatabase $Db
     */
    global $Db, $Webhook;
    if($Webhook->Data->User instanceof TgUser):
      $Db->ChatEdit($Webhook->Data->User);
    endif;
    $listener = $Db->ListenerGet(TgText::class, $Webhook->Data->Chat->Id);
    if($listener !== null):
      call_user_func($listener . '::Listener');
      return;
    endif;
    $listener = $Db->ListenerGet(TgText::class);
    if($listener !== null):
      call_user_func($listener . '::Listener');
      return;
    endif;
    if($Webhook->Data->Chat instanceof TgUser):
      StbBotTools::SendUserCmd('dontknow', $Webhook->Text);
    endif;
  }
}