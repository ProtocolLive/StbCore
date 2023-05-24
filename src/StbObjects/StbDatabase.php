<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use PDO;
use PDOException;
use ProtocolLive\PhpLiveDb\{
  AndOr,
  Operators,
  Parenthesis,
  PhpLiveDb,
  Types
};
use ProtocolLive\TelegramBotLibrary\TgObjects\{
  TgChat,
  TgGroupStatusMy,
  TgInlineQuery,
  TgObject,
  TgUser
};

/**
 * @version 2023.05.24.00
 */
final class StbDatabase{

  public function __construct(
    private PhpLiveDb $Db
  ){
    DebugTrace();
  }

  public function Admin(
    int $User
  ):StbDbAdminData|false{
    DebugTrace();
    $result = $this->Db->Select('chats')
    ->WhereAdd('chat_id', $User, Types::Int)
    ->Run();
    if($result === []):
      return false;
    endif;
    return new StbDbAdminData($result[0]);
  }

  public function AdminAdd(
    int $User,
    int $Perms
  ):bool{
    DebugTrace();
    $result = $this->Db->Select('chats')
    ->WhereAdd('chat_id', $User, Types::Int)
    ->Run();
    if($result !== []):
      return false;
    endif;
    $consult = $this->Db->Insert('chats')
    ->FieldAdd('chat_id', $User, Types::Int)
    ->FieldAdd('perms', $Perms, Types::Int)
    ->FieldAdd('created', time(), Types::Int);
    try{
      $consult->Run();
      return true;
    }catch(PDOException){
      return false;
    }
  }

  public function AdminDel(
    int $User
  ):bool{
    DebugTrace();
    if($User === Admin):
      return false;
    endif;
    $this->Db->Update('chats')
    ->FieldAdd('perms', StbDbAdminPerm::None->value, Types::Int)
    ->WhereAdd('chat_id', $User, Types::Int)
    ->Run();
    return true;
  }

  public function AdminEdit(
    int $User,
    int $Perms
  ):bool{
    DebugTrace();
    if($User === Admin):
      return false;
    endif;
    $consult = $this->Db->Update('chats')
    ->FieldAdd('perms', $Perms, Types::Int)
    ->WhereAdd('chat_id', $User, Types::Int);
    try{
      $consult->Run();
      return true;
    }catch(PDOException){
      return false;
    }
  }

  /**
   * @return StbDbAdminData[]
   */
  public function Admins():array{
    DebugTrace();
    $result = $this->Db->Select('chats')
    ->WhereAdd(
      'perms',
      StbDbAdminPerm::None->value,
      Types::Int,
      Operators::Bigger
    )
    ->Run();
    foreach($result as &$admin):
      $admin = new StbDbAdminData($admin);
    endforeach;
    return $result;
  }

  public function CallBackHashRun(
    string $Hash
  ):bool{
    DebugTrace();
    $result = $this->Db->Select('callbackshash')
    ->WhereAdd('hash', $Hash, Types::Str)
    ->Run();
    if($result === []):
      return false;
    endif;
    $function = json_decode($result[0]['method'], true);
    if(is_callable($function[0])):
      call_user_func_array(array_shift($function), $function);
      return true;
    else:
      return false;
    endif;
  }

  /**
   * The callback data are limited to 64 bytes. This function hash the function to be called
   */
  public function CallBackHashSet(
    callable $Method,
    ...$Args
  ):string{
    DebugTrace();
    $Args = func_get_args();
    $Args[0] = F2s($Args[0]);
    $Args = json_encode($Args);
    $hash = sha1($Args);
    $this->Db->InsertUpdate('callbackshash')
    ->FieldAdd('hash', $hash, Types::Str, Update: true)
    ->FieldAdd('method', $Args, Types::Str, Update: true)
    ->Run(HtmlSafe: false);
    return $hash;
  }

  public function ChatAdd(
    TgChat|TgUser $Chat
  ):bool{
    DebugTrace();
    $consult = $this->Db->Insert('chats')
    ->FieldAdd('chat_id', $Chat->Id, Types::Int)
    ->FieldAdd('name', $Chat->Name, Types::Str)
    ->FieldAdd('created', time(), Types::Int);
    try{
      $consult->Run();
      return true;
    }catch(PDOException){
      return false;
    }
  }

  public function CommandAdd(
    string $Command,
    string $Module
  ):bool{
    DebugTrace();
    $consult = $this->Db->Insert('commands')
    ->FieldAdd('command', $Command, Types::Str)
    ->FieldAdd('module', $Module, Types::Str);
    try{
      $consult->Run();
      return true;
    }catch(PDOException $e){
      error_log($e);
      return false;
    }
  }

  public function CommandDel(
    string|array $Command
  ):void{
    DebugTrace();
    if(is_string($Command)):
      $Command = [$Command];
    endif;
    $consult = $this->Db->Delete('commands')
    ->WhereAdd(1, Parenthesis: Parenthesis::Open);
    foreach($Command as $id => $cmd):
      $consult->WhereAdd(
        'command',
        $cmd,
        Types::Str,
        AndOr: AndOr::Or,
        CustomPlaceholder: 'cmd' . $id
      );
    endforeach;
    $consult->WhereAdd(2, Parenthesis: Parenthesis::Close)
    ->Run();
  }

  /**
   * List all commands or check if a commands exists
   * @param string $Command
   * @return array|string|null{ Return all commands or the respective module
   */
  public function Commands(
    string $Command = null
  ):array|string|null{
    DebugTrace();
    $consult = $this->Db->Select('commands');
    if($Command !== null):
      $consult->WhereAdd('command', $Command, Types::Str);
    endif;
    $return = $consult->Run();
    if($return === []):
      return null;
    elseif($Command !== null):
      return $return[0]['module'];
    endif;
    return $return;
  }

  public function GetCustom():PDO{
    return $this->Db->GetCustom();
  }

  /**
   * @param int $User User ID to associate the listener. Not allowed in checkout and InlineQuery listeners
   */
  public function ListenerAdd(
    TgObject|string $Listener,
    string $Class,
    int $Chat = null
  ):bool{
    DebugTrace();
    if($Chat === 0):
      return false;
    endif;
    if($this->NoUserListener($Listener)):
      $Chat = null;
    endif;
    if($Listener instanceof TgObject):
      $Listener = get_class($Listener);
    endif;
    $consult = $this->Db->InsertUpdate('listeners')
    ->FieldAdd('listener', $Listener, Types::Str)
    ->FieldAdd('chat_id', $Chat, Types::Str, Update: true)
    ->FieldAdd('module', $Class, Types::Str, Update: true);
    try{
      $consult->Run();
      return true;
    }catch(PDOException $e){
      error_log($e);
      return false;
    }
  }

  public function ListenerDel(
    TgObject|string $Listener,
    int $User = null
  ):void{
    DebugTrace();
    if($this->NoUserListener($Listener)):
      $User = null;
    endif;
    if($Listener instanceof TgObject):
      $Listener = get_class($Listener);
    endif;
    $this->Db->Delete('listeners')
    ->WhereAdd('listener', $Listener, Types::Str)
    ->WhereAdd('chat_id', $User, Types::Int)
    ->Run();
  }

  public function ListenerGet(
    TgObject|string $Listener,
    int $User = null
  ):string|null{
    DebugTrace();
    if($this->NoUserListener($Listener)):
      $User = null;
    endif;
    if($Listener instanceof TgObject):
      $Listener = get_class($Listener);
    endif;
    $return = $this->Db->Select('listeners')
    ->WhereAdd('listener', $Listener, Types::Str)
    ->WhereAdd('chat_id', $User, Types::Int)
    ->Run();
    return $return[0]['module'] ?? null;
  }

  public function ModuleInstall(
    string $Module
  ):bool{
    DebugTrace();
    if($this->ModuleRestricted($Module)):
      return false;
    endif;
    $consult = $this->Db->Insert('modules')
    ->FieldAdd('module', $Module, Types::Str)
    ->FieldAdd('created', time(), Types::Int);
    try{
      $consult->Run();
      return true;
    }catch(PDOException){
      return false;
    }
  }

  public function ModuleRestricted(
    string $Module
  ):bool{
    DebugTrace();
    return (
      str_contains($Module, '\Stb')
      or str_contains($Module, '\Tbl')
      or str_contains($Module, '\Tg')
    );
  }

  /**
   * List all installed modules or get the module installation timestamp
   * @param string $Module
   * @return array
   */
  public function Modules(
    string $Module = null
  ):array{
    DebugTrace();
    $consult = $this->Db->Select('modules');
    if($Module !== null):
      $consult->WhereAdd('module', $Module, Types::Str);
    endif;
    $consult->Order('module');
    return $consult->Run();
  }

  /**
   * Removes listeners, callback hashes and module data before uninstall
   */
  public function ModuleUninstall(
    string $Module
  ):void{
    DebugTrace();
    $this->Db->Delete('modules')
    ->WhereAdd('module', $Module, Types::Str)
    ->Run();
    $this->Db->Delete('callbackshash')
    ->WhereAdd(
      'method',
      '%' . $Module . '%',
      Types::Str,
      Operators::Like
    )
    ->Run();
  }

  private function NoUserListener(
    TgObject|string $Listener
  ):bool{
    DebugTrace();
    if($Listener instanceof TgObject):
      $Listener = get_class($Listener);
    endif;
    if($Listener === TgInlineQuery::class
    or $Listener === TgGroupStatusMy::class):
      return true;
    else:
      return false;
    endif;
  }

  public function UsageLog(
    int $Id,
    string $Event,
    string $Additional = null
  ):void{
    DebugTrace();
    $this->Db->Insert('sys_logs')
    ->FieldAdd('chat_id', $Id, Types::Int)
    ->FieldAdd('time', time(), Types::Int)
    ->FieldAdd('event', $Event, Types::Str)
    ->FieldAdd('msg', $Additional, Types::Str)
    ->Run();
  }

  public function UserEdit(
    TgUser $User
  ):bool{
    DebugTrace();
    $consult = $this->Db->InsertUpdate('chats')
    ->FieldAdd(':chat_id', $User->Id, Types::Int)
    ->FieldAdd(':name', $User->Name, Types::Str, Update: true)
    ->FieldAdd(':name2', $User->NameLast, Types::Str, Update: true)
    ->FieldAdd(':nick', $User->Nick, Types::Str, Update: true)
    ->FieldAdd(':lang', $User->Language, Types::Str, Update: true);
    try{
      $consult->Run();
      return true;
    }catch(PDOException){
      return false;
    }
  }

  public function UserGet(
    int $Id
  ):TgUser|null{
    DebugTrace();
    $result = $this->Db->Select('chats')
    ->WhereAdd('chat_id', $Id, Types::Int)
    ->Run();
    if($result === []):
      return null;
    endif;
    $return = [
      'id' => $result[0]['chat_id'],
      'first_name' => $result[0]['name'],
      'last_name' => $result[0]['name2'],
      'username' => $result[0]['nick'],
      'language_code' => $result[0]['lang']
    ];
    return new TgUser($return);
  }

  public function UserSeen(
    TgUser|TgChat $User
  ):void{
    DebugTrace();
    $this->Db->InsertUpdate('chats')
    ->FieldAdd('chat_id', $User->Id, Types::Int)
    ->FieldAdd('created', time(), Types::Int)
    ->FieldAdd('name', $User->Name, Types::Str, Update: true)
    ->FieldAdd('name2', $User->NameLast ?? null, Types::Str, Update: true)
    ->FieldAdd('nick', $User->Nick, Types::Str, Update: true)
    ->FieldAdd('lastseen', time(), Types::Int, Update: true)
    ->FieldAdd('lang', $User->Language ?? null, Types::Str, Update: true)
    ->Run();
  }

  public function VariableDel(
    string $Name,
    string $Value = null,
    string $Module = null,
    int $User = null
  ):void{
    DebugTrace();
    $consult = $this->Db->Delete('variables')
    ->WhereAdd('name', $Name, Types::Str);
    if($Value !== null):
      $consult->WhereAdd('value', $Value, Types::Str);
    endif;
    $consult->WhereAdd('chat_id', $User, Types::Int)
    ->WhereAdd('module', $Module, Types::Str)
    ->Run();
  }

  public function VariableGet(
    string $Name,
    string $Module = null,
    int $User = null
  ):string|null{
    DebugTrace();
    $result = $this->Db->Select('variables')
    ->WhereAdd('name', $Name, Types::Str)
    ->WhereAdd('module', $Module, Types::Str)
    ->WhereAdd('chat_id', $User, Types::Int)
    ->Run();
    if($result === []):
      return null;
    else:
      return $result[0]['value'];
    endif;
  }

  public function VariableGet2(
    string $Value,
    string $Module = null,
    int $User = null
  ):string|null{
    DebugTrace();
    $result = $this->Db->Select('variables')
    ->WhereAdd('value', $Value, Types::Str)
    ->WhereAdd('module', $Module, Types::Str)
    ->WhereAdd('chat_id', $User, Types::Int)
    ->Run();
    if($result === []):
      return null;
    else:
      return $result[0]['name'];
    endif;
  }

  public function VariableSet(
    string $Name,
    string|int|float $Value,
    string $Module = null,
    int $User = null,
    bool $AllowDuplicatedName = false
  ):void{
    DebugTrace();
    //InsertUpdate don't work because null values
    $result = $this->Db->Select('variables')
    ->WhereAdd('name', $Name, Types::Str)
    ->WhereAdd('module', $Module, Types::Str)
    ->WhereAdd('chat_id', $User, Types::Int)
    ->Run();
    if($result === []
    or $AllowDuplicatedName):
      $consult = $this->Db->Insert('variables')
      ->FieldAdd('name', $Name, Types::Str)
      ->FieldAdd('chat_id', $User, Types::Int)
      ->FieldAdd('module', $Module, Types::Str);
    else:
      $consult = $this->Db->Update('variables')
      ->WhereAdd('name', $Name, Types::Str)
      ->WhereAdd('module', $Module, Types::Str)
      ->WhereAdd('chat_id', $User, Types::Int);
    endif;
    $consult->FieldAdd('value', $Value, Types::Str)
    ->Run();
  }
}