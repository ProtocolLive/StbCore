<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\Install;
use DateTimeZone;
use ProtocolLive\PhpLiveDb\{
  Drivers,
  Formats,
  PhpLiveDb,
  RefTypes,
  Types
};
use ProtocolLive\SimpleTelegramBot\StbObjects\{
  StbAdmin,
  StbDbAdminPerm
};

/**
 * @version 2023.05.25.00
 */
abstract class Install{
  private static function CopyRecursive(
    string $From,
    string $To
  ):void{
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
    $consult = $PlDb->Create('callbackshash');
    $consult->Add(
      'hash',
      Formats::Varchar,
      40,
      NotNull: true,
      Primary: true
    );
    $consult->Add(
      'method',
      Formats::Varchar,
      255,
      NotNull: true
    );
    $consult->Run();
  }

  private static function CreateChats():void{
    global $PlDb;
    $consult = $PlDb->Create('chats');
    $consult->Add(
      'chat_id',
      Formats::IntBig,
      NotNull: true,
      Primary: true
    );
    $consult->Add(
      'name',
      Formats::Varchar,
      50,
      Default: '-',
      NotNull: true
    );
    $consult->Add(
      'name2',
      Formats::Varchar,
      50
    );
    $consult->Add(
      'nick',
      Formats::Varchar,
      50
    );
    $consult->Add(
      'lang',
      Formats::Varchar,
      5
    );
    $consult->Add(
      'perms',
      Formats::IntTiny,
      Unsigned: true,
      Default: 0,
      NotNull: true
    );
    $consult->Add(
      'created',
      Formats::Int,
      Unsigned: true,
      NotNull: true
    );
    $consult->Add(
      'lastseen',
      Formats::Int,
      Unsigned: true
    );
    $consult->Run();
  }

  private static function CreateCommands():void{
    global $PlDb;
    $consult = $PlDb->Create('commands');
    $consult->Add(
      'command',
      Formats::Varchar,
      50,
      NotNull: true,
      Primary: true
    );
    $consult->Add(
      'module',
      Formats::Varchar,
      255,
      NotNull: true,
      RefTable: 'modules',
      RefField: 'module',
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    );
    $consult->Run();
    $consult = $PlDb->Insert('commands');
    $consult->FieldAdd('module', StbAdmin::class, Types::Str);
    $consult->FieldAdd('command', 'admin', Types::Str);
    $consult->Run();
    $consult->FieldAdd('command', 'id', Types::Str);
    $consult->Run();
  }

  private static function CreateEventslogs():void{
    global $PlDb;
    $consult = $PlDb->Create('sys_eventslog');
    $consult->Add(
      'log_id',
      Formats::Int,
      Unsigned: true,
      NotNull: true,
      Primary: true,
      AutoIncrement: true
    );
    $consult->Add(
      'time',
      Formats::Int,
      Unsigned: true,
      NotNull: true,
    );
    $consult->Add(
      'chat_id',
      Formats::IntBig,
      NotNull: true,
      RefTable: 'chats',
      RefField: 'chat_id',
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    );
    $consult->Add(
      'event',
      Formats::Varchar,
      50,
      NotNull: true,
    );
    $consult->Add(
      'additional',
      Formats::Varchar,
      50
    );
    $consult->Unique(['time', 'chat_id']);
    $consult->Run();
  }

  private static function CreateListeners():void{
    global $PlDb;
    $consult = $PlDb->Create('listeners');
    $consult->Add(
      'listener_id',
      Formats::Int,
      Unsigned: true,
      NotNull: true,
      Primary: true,
      AutoIncrement: true
    );
    $consult->Add(
      'listener',
      Formats::Varchar,
      255,
      NotNull: true
    );
    $consult->Add(
      'chat_id',
      Formats::IntBig,
      RefTable: 'chats',
      RefField: 'chat_id',
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    );
    $consult->Add(
      'module',
      Formats::Varchar,
      255,
      NotNull: true,
      RefTable: 'modules',
      RefField: 'module',
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    );
    $consult->Unique(['listener', 'chat_id']);
    $consult->Run();
  }

  private static function CreateLogs():void{
    global $PlDb;
    $consult = $PlDb->Create('text_logs');
    $consult->Add(
      'log_id',
      Formats::Int,
      Unsigned: true,
      NotNull: true,
      Primary: true,
      AutoIncrement: true
    );
    $consult->Add(
      'time',
      Formats::Int,
      Unsigned: true,
      NotNull: true
    );
    $consult->Add(
      'chat_id',
      Formats::IntBig,
      NotNull: true,
      RefTable: 'chats',
      RefField: 'chat_id',
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    );
    $consult->Add(
      'event',
      Formats::Varchar,
      50,
      NotNull: true
    );
    $consult->Add(
      'msg',
      Formats::Text
    );
    $consult->Run();
  }

  private static function CreateModules():void{
    global $PlDb;
    $consult = $PlDb->Create('modules');
    $consult->Add(
      'module',
      Formats::Varchar,
      255,
      NotNull: true,
      Primary: true
    );
    $consult->Add(
      'created',
      Formats::Int,
      Unsigned: true,
      NotNull: true
    );
    $consult->Run();
    $consult = $PlDb->Insert('modules');
    $consult->FieldAdd('module', StbAdmin::class, Types::Str);
    $consult->FieldAdd('created', time(), Types::Int);
    $consult->Run();
  }

  private static function CreateParams():void{
    global $PlDb;
    $consult = $PlDb->Create('sys_params');
    $consult->Add(
      'name',
      Formats::Varchar,
      50,
      NotNull: true,
      Primary: true
    );
    $consult->Add(
      'value',
      Formats::Varchar,
      50,
      NotNull: true
    );
    $consult->Run();
    $consult = $PlDb->Insert('sys_params');
    $consult->FieldAdd('name', 'DbVersion', Types::Str);
    $consult->FieldAdd('value', '1.0.0', Types::Str);
    $consult->Run();
  }

  private static function CreateVariables():void{
    global $PlDb;
    $consult = $PlDb->Create('variables');
    $consult->Add(
      'var_id',
      Formats::Int,
      Unsigned: true,
      Primary: true,
      AutoIncrement: true
    );
    $consult->Add(
      'name',
      Formats::Varchar,
      50,
      NotNull: true
    );
    $consult->Add(
      'value',
      Formats::Varchar,
      255,
      NotNull: true
    );
    $consult->Add(
      'module',
      Formats::Varchar,
      255,
      RefTable: 'modules',
      RefField: 'module',
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    );
    $consult->Add(
      'chat_id',
      Formats::IntBig,
      RefTable: 'chats',
      RefField: 'chat_id',
      RefDelete: RefTypes::Cascade,
      RefUpdate: RefTypes::Cascade
    );
    $consult->Run();
  }

  public static function Step1():void{?>
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
    global $PlDb;?>
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
      $DirBot = 'Bot-' . $_POST['name'] . '-' . md5(uniqid());

      mkdir($DirSystem . '/DirBot', 0755, true);
      self::CopyRecursive(__DIR__ . '/DirBot', $DirSystem . '/DirBot');

      $config = file_get_contents($DirSystem . '/DirBot/config.txt');
      $config = str_replace('##DATE##', date('Y-m-d H:i:s'), $config);
      $config = str_replace('##TIMEZONE##', $_POST['timezone'], $config);
      $config = str_replace('##TOKEN##', $_POST['token'], $config);
      $config = str_replace('##TESTSERVER##', $_POST['testserver'], $config);
      $config = str_replace('##LANGUAGE##', $_POST['language'], $config);
      $config = str_replace('##ADMIN##', $_POST['admin'], $config);
      $config = str_replace('##TOKENWEBHOOK##', hash('sha256', uniqid()), $config);

      if($_POST['dbtype'] === 'mysql'):
        $config = str_replace('##DBTYPE##', 'Drivers::MySql', $config);
      else:
        $config = str_replace('##DBTYPE##', 'Drivers::SqLite', $config);
      endif;
      $config = str_replace('##DBHOST##', $_POST['host'], $config);
      $config = str_replace('##DBUSER##', $_POST['user'], $config);
      $config = str_replace('##DBPWD##', $_POST['pwd'], $config);
      $config = str_replace('##DBNAME##', $_POST['db'], $config);

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
      self::CreateEventslogs();
      self::CreateLogs();
      self::CreateParams();
      self::CreateVariables();

      $consult = $PlDb->Insert('chats');
      $consult->FieldAdd('chat_id', $_POST['admin'], Types::Int);
      $consult->FieldAdd('created', time(), Types::Int);
      $consult->FieldAdd('perms', StbDbAdminPerm::All->value, Types::Int);
      $consult->Run();

      rename($DirSystem . '/index.php', $DirSystem . '/index_' . uniqid() . '.php');

      echo '✅ Install complete!';
      $url = dirname($_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME']);
      $url .= '/' . $DirBot . '/index.php?a=WebhookSet';
      echo '<p><a href="https://' . $url . '">Click here to set the webhook</a></p>';?>
    </body>
    </html><?php
  }
}