<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\Install;
use DateTimeZone;
use ProtocolLive\PhpLiveDb\Enums\{
  Drivers,
  Formats,
  PhpLiveDb,
  RefTypes,
  Types
};
use ProtocolLive\SimpleTelegramBot\NoStr\Fields\{
  CallbackHash,
  Chats,
  Commands,
  Listeners,
  LogTexts,
  LogUpdates,
  Modules,
  Params,
  Variables
};
use ProtocolLive\SimpleTelegramBot\NoStr\Tables;
use ProtocolLive\SimpleTelegramBot\StbObjects\{
  StbAdmin,
  StbDbAdminPerm
};

/**
 * @version 2024.02.23.00
 */
abstract class Install{
  private static function CopyRecursive(
    string $From,
    string $To
  ):void{
    DebugTrace();
    foreach(glob($From . '/*') as $file):
      if(is_dir($file)):
        mkdir($To . '/' . basename($file), 0755, true);
        self::CopyRecursive($file, $To . '/' . basename($file));
      else:
        copy($file, $To . '/' . basename($file));
      endif;
    endforeach;
  }

  private static function CreateCallbackhash():void{
    global $PlDb;
    DebugTrace();
    $PlDb->Create(Tables::CallbackHash)
    ->Add(
      CallbackHash::Hash,
      Formats::Varchar,
      40,
      NotNull: true,
      Primary: true
    )
    ->Add(
      CallbackHash::Method,
      Formats::Varchar,
      255,
      NotNull: true
    )
    ->Run();
  }

  private static function CreateChats():void{
    global $PlDb;
    DebugTrace();
    $PlDb->Create(Tables::Chats)
    ->Add(
      Chats::Id,
      Formats::IntBig,
      NotNull: true,
      Primary: true
    )
    ->Add(
      Chats::Name,
      Formats::Varchar,
      50,
      Default: '-',
      NotNull: true
    )
    ->Add(
      Chats::NameLast,
      Formats::Varchar,
      50
    )
    ->Add(
      Chats::Nick,
      Formats::Varchar,
      50
    )
    ->Add(
      Chats::Language,
      Formats::Varchar,
      5
    )
    ->Add(
      Chats::Permission,
      Formats::IntTiny,
      Unsigned: true,
      Default: 0,
      NotNull: true
    )
    ->Add(
      Chats::Created,
      Formats::Int,
      Unsigned: true,
      NotNull: true
    )
    ->Add(
      Chats::LastSeen,
      Formats::Int,
      Unsigned: true
    )
    ->Run();
  }

  private static function CreateCommands():void{
    global $PlDb;
    DebugTrace();
    $PlDb->Create(Tables::Commands)
    ->Add(
      Commands::Name,
      Formats::Varchar,
      50,
      NotNull: true,
      Primary: true
    )
    ->Add(
      Commands::Module,
      Formats::Varchar,
      255,
      NotNull: true,
      RefTable: 'modules',
      RefField: 'module',
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    )
    ->Run();
    $consult = $PlDb->Insert(Tables::Commands)
    ->FieldAdd(Commands::Module, StbAdmin::class, Types::Str)
    ->FieldAdd(Commands::Name, 'admin', Types::Str);
    $consult->Run();
    $consult->FieldAdd(Commands::Name, 'id', Types::Str)
    ->Run();
  }

  private static function CreateListeners():void{
    global $PlDb;
    DebugTrace();
    $PlDb->Create(Tables::Listeners)
    ->Add(
      Listeners::Id,
      Formats::Int,
      Unsigned: true,
      NotNull: true,
      Primary: true,
      AutoIncrement: true
    )
    ->Add(
      Listeners::Name,
      Formats::Varchar,
      255,
      NotNull: true
    )
    ->Add(
      Listeners::Chat,
      Formats::IntBig,
      RefTable: Tables::Chats,
      RefField: Chats::Id,
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    )
    ->Add(
      Listeners::Module,
      Formats::Varchar,
      255,
      NotNull: true,
      RefTable: Tables::Modules,
      RefField: Modules::Name,
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    )
    ->Unique([Listeners::Name, Listeners::Chat])
    ->Run();
  }

  private static function CreateLogTexts():void{
    global $PlDb;
    DebugTrace();
    $PlDb->Create(Tables::LogTexts)
    ->Add(
      LogTexts::Id,
      Formats::Int,
      Unsigned: true,
      NotNull: true,
      Primary: true,
      AutoIncrement: true
    )
    ->Add(
      LogTexts::Time,
      Formats::Int,
      Unsigned: true,
      NotNull: true
    )
    ->Add(
      LogTexts::Chat,
      Formats::IntBig,
      NotNull: true,
      RefTable: Tables::Chats,
      RefField: Chats::Id,
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    )
    ->Add(
      LogTexts::Event,
      Formats::Varchar,
      50,
      NotNull: true
    )
    ->Add(
      LogTexts::Msg,
      Formats::Text
    )
    ->Run();
  }

  private static function CreateLogUpdates():void{
    global $PlDb;
    DebugTrace();
    $PlDb->Create(Tables::LogUpdates)
    ->Add(
      LogUpdates::Id,
      Formats::Int,
      Unsigned: true,
      NotNull: true,
      Primary: true,
      AutoIncrement: true
    )
    ->Add(
      LogUpdates::Time,
      Formats::Int,
      Unsigned: true,
      NotNull: true,
    )
    ->Add(
      LogUpdates::Type,
      Formats::Varchar,
      50,
      NotNull: true,
    )
    ->Add(
      LogUpdates::Update,
      Formats::Text,
      NotNull: true,
    )
    ->Run();
  }

  private static function CreateModules():void{
    global $PlDb;
    DebugTrace();
    $PlDb->Create(Tables::Modules)
    ->Add(
      Modules::Name,
      Formats::Varchar,
      255,
      NotNull: true,
      Primary: true
    )
    ->Add(
      Modules::Created,
      Formats::Int,
      Unsigned: true,
      NotNull: true
    )
    ->Run();
    $PlDb->Insert(Tables::Modules)
    ->FieldAdd(Modules::Name, StbAdmin::class, Types::Str)
    ->FieldAdd(Modules::Created, time(), Types::Int)
    ->Run();
  }

  private static function CreateParams():void{
    global $PlDb;
    DebugTrace();
    $PlDb->Create(Tables::Params)
    ->Add(
      Params::Name,
      Formats::Varchar,
      50,
      NotNull: true,
      Primary: true
    )
    ->Add(
      Params::Value,
      Formats::Varchar,
      50,
      NotNull: true
    )
    ->Run();
    $PlDb->Insert(Tables::Params)
    ->FieldAdd(Params::Name, 'DbVersion', Types::Str)
    ->FieldAdd(Params::Value, '1.0.0', Types::Str)
    ->Run();
  }

  private static function CreateVariables():void{
    global $PlDb;
    DebugTrace();
    $PlDb->Create(Tables::Variables)
    ->Add(
      Variables::Id,
      Formats::Int,
      Unsigned: true,
      Primary: true,
      AutoIncrement: true
    )
    ->Add(
      Variables::Name,
      Formats::Varchar,
      50,
      NotNull: true
    )
    ->Add(
      Variables::Value,
      Formats::Varchar,
      255,
      NotNull: true
    )
    ->Add(
      Variables::Module,
      Formats::Varchar,
      255,
      RefTable: Tables::Modules,
      RefField: Modules::Name,
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    )
    ->Add(
      Variables::Chat,
      Formats::IntBig,
      RefTable: Tables::Chats,
      RefField: Chats::Id,
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    )
    ->Run();
  }

  public static function Step1():void{
    DebugTrace();?>
    <!DOCTYPE html>
      <html lang="en">
      <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SimpleTelegramBot Install</title>
      </head>
      <body>
        <h1>SimpleTelegramBot Install</h1>
        <form method="post" action="index.php?step=2">
          <table>
            <tr>
              <td>Name:</td>
              <td>
                <input type="text" name="name">
              </td>
            </tr>
            <tr>
              <td>Token:</td>
              <td>
                <input type="text" name="token">
              </td>
            </tr>
            <tr>
              <td>Admin ID:</td>
              <td>
                <input type="text" name="admin"><br>
                To know your ID, send the command <b>/myid</b> to <a href="https://t.me/ProtocolSimpleTelegramBot">ProtocolSimpleTelegramBot</a>
              </td>
            </tr>
            <tr>
              <td>Timezone:</td>
              <td>
                <select name="timezone"><?php
                  foreach(DateTimeZone::listIdentifiers(DateTimeZone::ALL) as $name):?>
                    <option value="<?=$name?>"><?=$name?></option><?php
                  endforeach;?>
                </select>
              </td>
            </tr>
            <tr>
              <td style="vertical-align:top">Default language:</td>
              <td>
                <select name="language">
                  <option value="en">English</option>
                  <option value="pt-br">Portuguese Brazil</option>
                </select><br>
                <span style="font-size:11">Another language? Add later in bot config</span>
              </td>
            </tr>
            <tr>
              <td>Test server:</td>
              <td>
                <select name="testserver">
                  <option value="false">No</option>
                  <option value="true">Yes</option>
                </select>
              </td>
            </tr>
          </table><br>
          <table>
            <tr>
              <td>Database:</td>
              <td>
                <select name="dbtype">
                  <option value="<?=Drivers::SqLite->value?>"><?=Drivers::SqLite->name?></option>
                  <option value="<?=Drivers::MySql->value?>"><?=Drivers::MySql->name?></option>
                </select>
              </td>
            </tr>
            <tr>
              <td>Host:</td>
              <td><input type="text" name="host"></td>
            </tr>
            <tr>
              <td>User:</td>
              <td><input type="text" name="user"></td>
            </tr>
            <tr>
              <td>Password:</td>
              <td><input type="text" name="pwd"></td>
            </tr>
            <tr>
              <td>Database name:</td>
              <td><input type="text" name="db"></td>
            </tr>
          </table>
          <p>
            <input type="submit" value="Install">
          </p>
        </form>
      </body>
    </html><?php
  }

  public static function Step2():void{
    global $PlDb;
    DebugTrace();?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>SimpleTelegramBot Install</title>
    </head>
    <body>
      <h1>SimpleTelegramBot Install</h1><?php
      $_POST = filter_input_array(INPUT_POST);

      $DirSystem = dirname(__DIR__, 5);
      $DirBot = 'Bot-' . trim($_POST['name']) . '-' . md5(uniqid());

      mkdir($DirSystem . '/DirBot', 0755, true);
      self::CopyRecursive(__DIR__ . '/DirBot', $DirSystem . '/DirBot');

      $config = file_get_contents($DirSystem . '/DirBot/config.txt');
      $config = str_replace('##DATE##', date('Y-m-d H:i:s'), $config);
      $config = str_replace('##TIMEZONE##', $_POST['timezone'], $config);
      $config = str_replace('##TOKEN##', trim($_POST['token']), $config);
      $config = str_replace('##TESTSERVER##', $_POST['testserver'], $config);
      $config = str_replace('##LANGUAGE##', $_POST['language'], $config);
      $config = str_replace('##ADMIN##', trim($_POST['admin']), $config);
      $config = str_replace('##TOKENWEBHOOK##', hash('sha256', uniqid()), $config);

      if($_POST['dbtype'] === 'mysql'):
        $config = str_replace('##DBTYPE##', 'Drivers::MySql', $config);
      else:
        $config = str_replace('##DBTYPE##', 'Drivers::SqLite', $config);
      endif;
      $config = str_replace('##DBHOST##', trim($_POST['host']), $config);
      $config = str_replace('##DBUSER##', trim($_POST['user']), $config);
      $config = str_replace('##DBPWD##', trim($_POST['pwd']), $config);
      $config = str_replace('##DBNAME##', trim($_POST['db']), $config);

      $temp = md5(uniqid());
      $config = str_replace('##DIRLOGS##', $temp, $config);
      file_put_contents($DirSystem . '/DirBot/config.php', $config);
      unlink($DirSystem . '/DirBot/config.txt');

      rename($DirSystem . '/DirBot/logs', $DirSystem . '/DirBot/logs-' . $temp);
      rename($DirSystem . '/DirBot', $DirSystem . '/' . $DirBot);

      if($_POST['dbtype'] === Drivers::MySql->value):
        $PlDb = new PhpLiveDb(
          $_POST['host'],
          $_POST['user'],
          $_POST['pwd'],
          $_POST['db']
        );
      else:
        $PlDb = new PhpLiveDb(
          $DirSystem . '/' . $DirBot . '/db.db',
          Driver: Drivers::SqLite
        );
      endif;
      
      self::CreateCallbackhash();
      self::CreateChats();
      self::CreateModules();
      self::CreateCommands();
      self::CreateListeners();
      self::CreateLogUpdates();
      self::CreateLogTexts();
      self::CreateParams();
      self::CreateVariables();

      $PlDb->Insert(Tables::Chats)
      ->FieldAdd(Chats::Id, $_POST['admin'], Types::Int)
      ->FieldAdd(Chats::Created, time(), Types::Int)
      ->FieldAdd(Chats::Permission, StbDbAdminPerm::All->value, Types::Int)
      ->Run();

      rename($DirSystem . '/index.php', $DirSystem . '/index_' . uniqid() . '.php');

      echo '✅ Install complete!';
      $url = dirname($_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME']);
      $url .= '/' . $DirBot . '/index.php?a=WebhookSet';?>
      <p><a href="https://<?=$url?>">Click here to set the webhook</a></p>
      <p>Note: When a unknown message are received, the bot auto forward to main admin. If you have privacy activated for voice messages, you need to put the bot in exception to receive them.</p>
    </body>
    </html><?php
  }
}