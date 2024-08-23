<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ConsoleColorText;
use Exception;
use HttpCode;
use ProtocolLive\PhpLiveDb\Enums\Types;
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
  TgChatType,
  TgParseMode,
  TgUpdateType
};
use ProtocolLive\TelegramBotLibrary\TgInterfaces\TgEventInterface;
use ProtocolLive\TelegramBotLibrary\TgObjects\{
  TgBusinessConnection,
  TgCallback,
  TgChat,
  TgGroupStatus,
  TgGroupStatusMy,
  TgInvoiceCheckout,
  TgInvoiceDone,
  TgLimits,
  TgMessageDeleted,
  TgPoll,
  TgReactionUpdate,
  TgUser
};
use ReflectionClass;
use TypeError;

/**
 * @version 2024.08.23.00
 */
abstract class StbCore{
  public static function Action_(
    TelegramBotLibrary $Bot,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    try{
      $Webhook = $Bot->WebhookGet();
    }catch(TblException $e){
      error_log($e->getMessage());
      http_response_code(HttpCode::BadRequest->value);
      exit(1);
    }
    if($Webhook === null):
      return;
    endif;
    if(empty($Webhook->Data->User) === false //anon reaction
    or ($Webhook->Data->Chat->Type ?? null) === TgChatType::Private):
      $Db->ChatEdit($Webhook->Data->User);
    endif;
    if($Webhook::class === TblCmd::class)://prevent TblCmdEdited
      self::Update_Cmd($Bot, $Db, $Webhook, $Lang);
      return;
    endif;
    if($Webhook instanceof TgCallback):
      self::Update_Callback($Bot, $Webhook, $Db, $Lang);
      return;
    endif;
    $module = $Db->ListenerGet($Webhook, $Webhook->Data->User->Id ?? null) ?? $Db->ListenerGet($Webhook);
    if($module !== null
    and call_user_func($module . '::Listener', $Bot, $Webhook, $Db, $Lang)):
      return;
    endif;
    if($Webhook instanceof TgReactionUpdate
    or $Webhook instanceof TgGroupStatus
    or $Webhook instanceof TgGroupStatusMy
    or $Webhook instanceof TgInvoiceCheckout
    or $Webhook instanceof TgInvoiceDone
    or $Webhook instanceof TgBusinessConnection
    or $Webhook instanceof TgMessageDeleted
    or $Webhook instanceof TgPoll
    or $Webhook->Data->Chat instanceof TgChat
    or $Webhook->Data->BusinessConnection !== null):
      return;
    endif;
    self::SendUserCmd($Bot, $Webhook, $Db, 'dontknow', $Webhook->Text ?? $Webhook::class);
    if(ForwardDontknow !== null
    and $Webhook->Data->User->Id !== ForwardDontknow):
      $temp = $Bot->MessageForward(
        $Webhook->Data->Chat->Id,
        $Webhook->Data->Id,
        ForwardDontknow
      );
      if($temp->Data->Forward->Id === null):
        $Bot->TextSend(
          ForwardDontknow,
          StbBotTools::FormatName($Webhook->Data->Chat),
          ParseMode: TgParseMode::Html,
          DisableNotification: true
        );
      endif;
    endif;
  }

  public static function Action_WebhookDel(
    TblData $BotData
  ):void{
    DebugTrace();
    $Webhook = new TblWebhook($BotData);
    try{
      $Webhook->Del();
    }catch(TblException $e){
      echo $e->getMessage();
    }
  }

  public static function Action_WebhookGet(
    TblData $BotData
  ):void{
    DebugTrace();
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

  public static function Action_WebhookSet(
    TblData $BotData
  ):void{
    DebugTrace();
    $Webhook = new TblWebhook($BotData);
    try{
      $Webhook->Set(
        ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_GET['server'] ?? $_SERVER['SERVER_NAME']) . $_SERVER['SCRIPT_NAME'],
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
   * @param TblData $BotData Used with cron and webhook
   */
  public static function Entry(
    TelegramBotLibrary $Bot,
    StbDatabase $Db,
    StbLanguageSys $Lang,
    TblData $BotData = null
  ):void{
    DebugTrace();
    self::ModuleAutoload();
    $_GET['a'] ??= '';
    if(PHP_SAPI === 'cli'):
      self::Terminal($Bot, $Db, $BotData);
    elseif(str_contains($_GET['a'], 'Webhook')):
      call_user_func(__CLASS__ . '::Action_' . $_GET['a'], $BotData);
    elseif(is_callable(__CLASS__ . '::Action_' . $_GET['a'])):
      call_user_func(__CLASS__ . '::Action_' . $_GET['a'], $Bot, $Db, $Lang);
    endif;
  }

  public static function Log(
    TblData $BotData,
    int $Type,
    string $Msg,
    bool $NewLine = true
  ):void{
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
    DebugTrace();
    spl_autoload_register(function(string $Class){
      DebugTrace();
      $temp = explode('\\', $Class);
      unset($temp[0], $temp[1]);
      require(DirModules . '/'. implode('/', $temp) . '/index.php');
    });
  }

  /**
   * Method to be user in set_error_handler and set_exception_handler. This method warns bot admin about the error
   */
  public static function OnError(
    mixed ...$Args
  ):void{
    global $Bot;
    ob_start();
    var_dump($Args);
    $log = ob_get_contents();
    ob_end_clean();
    $msg = 'An error occurred';
    if(strlen($log) < TgLimits::Text):
      $msg .= ':' . PHP_EOL;
      $msg .= '<pre><code>' . $log . '</code></pre>';
    else:
      $msg .= '!';
    endif;
    $Bot->TextSend(
      Admin,
      $msg,
      ParseMode: TgParseMode::Html
    );
    Handler(...$Args);
  }

  public static function SendUserCmd(
    TelegramBotLibrary $Bot,
    TgEventInterface $Webhook,
    StbDatabase $Db,
    string $Command,
    string $EventAdditional = null
  ):bool{
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

  private static function Terminal(
    TelegramBotLibrary $Bot,
    StbDatabase $Db,
    TblData $BotData
  ):void{
    DebugTrace();
    if(in_array('-h', $_SERVER['argv'])
    or in_array('--help', $_SERVER['argv'])):
      echo ConsoleColor('SimpleTelegramBot by Protocol Corp.', ConsoleColorText::Green) . PHP_EOL;
      echo ConsoleColor('Terminal parameters help:', ConsoleColorText::Yellow) . PHP_EOL;
      echo "-m, --module\tCall a module" . PHP_EOL;
      return;
    endif;

    if(in_array('-m', $_SERVER['argv'])
    or in_array('--module', $_SERVER['argv'])):
      $temp = array_search('-m', $_SERVER['argv']);
      if($temp === false):
        $temp = array_search('--module', $_SERVER['argv']);
      endif;
      call_user_func($_SERVER['argv'][$temp + 1] . '::Cron', $Bot, $Db, $BotData);
      self::Log(
        $BotData,
        StbLog::Cron,
        'Time: ' . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'])
      );
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
    DebugTrace();
    return new TgUser([
      'id' => $Chat->Id,
      'first_name' => $Chat->Name,
      'last_name' => null,
      'username' => $Chat->Nick
    ]);
  }

  private static function Update_Callback(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
    $Lang->LanguageSet($Webhook->Data->User->Language);
    $return = $Db->CallBackHashRun($Bot, $Webhook, $Db, $Lang);
    if($return === false):
      $Bot->CallbackAnswer(
        $Webhook->Data->Id,
        $Lang->Get('ButtonWithoutAction', Group: 'Errors')
      );
    endif;
  }

  private static function Update_Cmd(
    TelegramBotLibrary $Bot,
    StbDatabase $Db,
    TblCmd $Webhook,
    StbLanguageSys $Lang
  ):void{
    DebugTrace();
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
      call_user_func($module . '::Command', $Bot, $Webhook, $Db, $Lang);
      return;
    endif;
  
    if(self::SendUserCmd($Bot, $Webhook, $Db,$Webhook->Command) === false):
      self::SendUserCmd($Bot, $Webhook, $Db, 'unknown');
    endif;
  }
}