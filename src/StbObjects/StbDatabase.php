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
use ProtocolLive\SimpleTelegramBot\NoStr\Fields\{
  CallbackHash,
  Chats,
  Commands,
  Listeners,
  LogTexts,
  Modules,
  Variables
};
use ProtocolLive\SimpleTelegramBot\NoStr\Tables;
use ProtocolLive\TelegramBotLibrary\TgObjects\{
  TgChat,
  TgGroupStatusMy,
  TgInlineQuery,
  TgObject,
  TgUser
};

/**
 * @version 2023.05.25.02
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
    $result = $this->Db->Select(Tables::Chats)
    ->WhereAdd(Chats::Id, $User, Types::Int)
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
    $result = $this->Db->Select(Tables::Chats)
    ->WhereAdd(Chats::Id, $User, Types::Int)
    ->Run();
    if($result !== []):
      return false;
    endif;
    $consult = $this->Db->Insert(Tables::Chats)
    ->FieldAdd(Chats::Id, $User, Types::Int)
    ->FieldAdd(Chats::Permission, $Perms, Types::Int)
    ->FieldAdd(Chats::Created, time(), Types::Int);
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
    $this->Db->Update(Tables::Chats)
    ->FieldAdd(Chats::Permission, StbDbAdminPerm::None->value, Types::Int)
    ->WhereAdd(Chats::Id, $User, Types::Int)
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
    $consult = $this->Db->Update(Tables::Chats)
    ->FieldAdd(Chats::Permission, $Perms, Types::Int)
    ->WhereAdd(Chats::Id, $User, Types::Int);
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
    $result = $this->Db->Select(Tables::Chats)
    ->WhereAdd(
      Chats::Permission,
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
    $result = $this->Db->Select(Tables::CallbackHash)
    ->WhereAdd(CallbackHash::Hash, $Hash, Types::Str)
    ->Run();
    if($result === []):
      return false;
    endif;
    $function = json_decode($result[0][CallbackHash::Method->value], true);
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
    $this->Db->InsertUpdate(Tables::CallbackHash)
    ->FieldAdd(CallbackHash::Hash, $hash, Types::Str, Update: true)
    ->FieldAdd(CallbackHash::Method, $Args, Types::Str, Update: true)
    ->Run(HtmlSafe: false);
    return $hash;
  }

  public function ChatAdd(
    TgChat|TgUser $Chat
  ):bool{
    DebugTrace();
    $consult = $this->Db->Insert(Tables::Chats)
    ->FieldAdd(Chats::Id, $Chat->Id, Types::Int)
    ->FieldAdd(Chats::Name, $Chat->Name, Types::Str)
    ->FieldAdd(Chats::Created, time(), Types::Int);
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
    $consult = $this->Db->Insert(Tables::Commands)
    ->FieldAdd(Commands::Name, $Command, Types::Str)
    ->FieldAdd(Commands::Module, $Module, Types::Str);
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
    $consult = $this->Db->Delete(Tables::Commands)
    ->WhereAdd(1, Parenthesis: Parenthesis::Open);
    foreach($Command as $id => $cmd):
      $consult->WhereAdd(
        Commands::Name,
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
    $consult = $this->Db->Select(Tables::Commands);
    if($Command !== null):
      $consult->WhereAdd(Commands::Name, $Command, Types::Str);
    endif;
    $return = $consult->Run();
    if($return === []):
      return null;
    elseif($Command !== null):
      return $return[0][Commands::Module];
    endif;
    return $return;
  }

  public function GetCustom():PDO{
    DebugTrace();
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
    $consult = $this->Db->InsertUpdate(Tables::Listeners)
    ->FieldAdd(Listeners::Name, $Listener, Types::Str)
    ->FieldAdd(Listeners::Chat, $Chat, Types::Str, Update: true)
    ->FieldAdd(Listeners::Module, $Class, Types::Str, Update: true);
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
    $this->Db->Delete(Tables::Listeners)
    ->WhereAdd(Listeners::Name, $Listener, Types::Str)
    ->WhereAdd(Listeners::Chat, $User, Types::Int)
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
    $return = $this->Db->Select(Tables::Listeners)
    ->WhereAdd(
      Listeners::Name,
      $Listener,
      Types::Str,
      Parenthesis: Parenthesis::Open
    )
    ->WhereAdd(
      Listeners::Name,
      TgObject::class,
      Types::Str,
      AndOr: AndOr::Or,
      Parenthesis: Parenthesis::Close,
      CustomPlaceholder: 'l2'
    )
    ->WhereAdd(Listeners::Chat, $User, Types::Int)
    ->Run();
    return $return[0][Listeners::Module->value] ?? null;
  }

  public function ModuleInstall(
    string $Module
  ):bool{
    DebugTrace();
    if($this->ModuleRestricted($Module)):
      return false;
    endif;
    $consult = $this->Db->Insert(Tables::Modules)
    ->FieldAdd(Modules::Name, $Module, Types::Str)
    ->FieldAdd(Modules::Created, time(), Types::Int);
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
    return str_starts_with($Module, 'ProtocolLive\SimpleTelegramBot')
      or str_starts_with($Module, 'ProtocolLive\ProtocolBotLibrary');
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
    $consult = $this->Db->Select(Tables::Modules);
    if($Module !== null):
      $consult->WhereAdd('module', $Module, Types::Str);
    endif;
    $consult->Order(Modules::Name);
    return $consult->Run();
  }

  /**
   * Removes listeners, callback hashes and module data before uninstall
   */
  public function ModuleUninstall(
    string $Module
  ):void{
    DebugTrace();
    $this->Db->Delete(Tables::Modules)
    ->WhereAdd(Modules::Name, $Module, Types::Str)
    ->Run();
    $this->Db->Delete(Tables::CallbackHash)
    ->WhereAdd(
      CallbackHash::Method,
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
    $this->Db->Insert(Tables::LogTexts->value)
    ->FieldAdd(LogTexts::Chat, $Id, Types::Int)
    ->FieldAdd(LogTexts::Time, time(), Types::Int)
    ->FieldAdd(LogTexts::Event, $Event, Types::Str)
    ->FieldAdd(LogTexts::Msg, $Additional, Types::Str)
    ->Run();
  }

  public function UserEdit(
    TgUser $User
  ):bool{
    DebugTrace();
    $consult = $this->Db->InsertUpdate(Tables::Chats)
    ->FieldAdd(Chats::Id, $User->Id, Types::Int)
    ->FieldAdd(Chats::Name, $User->Name, Types::Str, Update: true)
    ->FieldAdd(Chats::NameLast, $User->NameLast, Types::Str, Update: true)
    ->FieldAdd(Chats::Nick, $User->Nick, Types::Str, Update: true)
    ->FieldAdd(Chats::Language, $User->Language, Types::Str, Update: true);
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
    $result = $this->Db->Select(Tables::Chats)
    ->WhereAdd(Chats::Id, $Id, Types::Int)
    ->Run();
    if($result === []):
      return null;
    endif;
    $return = [
      'id' => $result[0][Chats::Id->value],
      'first_name' => $result[0][Chats::Name->value],
      'last_name' => $result[0][Chats::NameLast->value],
      'username' => $result[0][Chats::Nick->value],
      'language_code' => $result[0][Chats::Language->value]
    ];
    return new TgUser($return);
  }

  public function UserSeen(
    TgUser|TgChat $User
  ):void{
    DebugTrace();
    $this->Db->InsertUpdate(Tables::Chats)
    ->FieldAdd('chat_id', $User->Id, Types::Int)
    ->FieldAdd(Chats::Created, time(), Types::Int)
    ->FieldAdd(Chats::Name, $User->Name, Types::Str, Update: true)
    ->FieldAdd(Chats::NameLast, $User->NameLast ?? null, Types::Str, Update: true)
    ->FieldAdd(Chats::Nick, $User->Nick, Types::Str, Update: true)
    ->FieldAdd(Chats::LastSeen, time(), Types::Int, Update: true)
    ->FieldAdd(Chats::Language, $User->Language ?? null, Types::Str, Update: true)
    ->Run();
  }

  public function VariableDel(
    string $Name,
    string $Value = null,
    string $Module = null,
    int $User = null
  ):void{
    DebugTrace();
    $consult = $this->Db->Delete(Tables::Variables)
    ->WhereAdd(Variables::Name, $Name, Types::Str);
    if($Value !== null):
      $consult->WhereAdd(Variables::Value, $Value, Types::Str);
    endif;
    $consult->WhereAdd(Variables::Chat, $User, Types::Int)
    ->WhereAdd(Variables::Module, $Module, Types::Str)
    ->Run();
  }

  public function VariableGet(
    string $Name,
    string $Module = null,
    int $User = null
  ):string|null{
    DebugTrace();
    $result = $this->Db->Select(Tables::Variables)
    ->WhereAdd(Variables::Name, $Name, Types::Str)
    ->WhereAdd(Variables::Module, $Module, Types::Str)
    ->WhereAdd(Variables::Chat, $User, Types::Int)
    ->Run();
    if($result === []):
      return null;
    else:
      return $result[0][Variables::Value->value];
    endif;
  }

  public function VariableGet2(
    string $Value,
    string $Module = null,
    int $User = null
  ):string|null{
    DebugTrace();
    $result = $this->Db->Select(Tables::Variables)
    ->WhereAdd(Variables::Value, $Value, Types::Str)
    ->WhereAdd(Variables::Module, $Module, Types::Str)
    ->WhereAdd(Variables::Chat, $User, Types::Int)
    ->Run();
    if($result === []):
      return null;
    else:
      return $result[0][Variables::Name->value];
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
    $result = $this->Db->Select(Tables::Variables)
    ->WhereAdd(Variables::Name, $Name, Types::Str)
    ->WhereAdd(Variables::Module, $Module, Types::Str)
    ->WhereAdd(Variables::Chat, $User, Types::Int)
    ->Run();
    if($result === []
    or $AllowDuplicatedName):
      $consult = $this->Db->Insert(Tables::Variables)
      ->FieldAdd(Variables::Name, $Name, Types::Str)
      ->FieldAdd(Variables::Chat, $User, Types::Int)
      ->FieldAdd(Variables::Module, $Module, Types::Str);
    else:
      $consult = $this->Db->Update(Tables::Variables)
      ->WhereAdd(Variables::Name, $Name, Types::Str)
      ->WhereAdd(Variables::Module, $Module, Types::Str)
      ->WhereAdd(Variables::Chat, $User, Types::Int);
    endif;
    $consult->FieldAdd(Variables::Value, $Value, Types::Str)
    ->Run();
  }
}