<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use PDO;
use PDOException;
use ProtocolLive\PhpLiveDb\Enums\{
  AndOr,
  Operators,
  Parenthesis,
  Types
};
use ProtocolLive\PhpLiveDb\PhpLiveDb;
use ProtocolLive\SimpleTelegramBot\Datas\ChatData;
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
use ProtocolLive\SimpleTelegramBot\StbEnums\StbError;
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgInterfaces\TgEventInterface;
use ProtocolLive\TelegramBotLibrary\TgObjects\{
  TgCallback,
  TgChat,
  TgGroupStatusMy,
  TgInlineQuery,
  TgUser
};
use UnitEnum;

/**
 * @version 2024.03.11.00
 */
final readonly class StbDatabase{
  public function __construct(
    private PhpLiveDb $Db
  ){
    DebugTrace();
  }

  /**
   * @return ChatData[]
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
      $admin = new ChatData($admin);
    endforeach;
    return $result;
  }

  public function CallBackHashRun(
    TelegramBotLibrary $Bot,
    TgCallback $Webhook,
    StbDatabase $Db,
    StbLanguageSys $Lang
  ):bool{
    DebugTrace();
    $result = $this->Db->Select(Tables::CallbackHash)
    ->WhereAdd(CallbackHash::Hash, $Webhook->Callback, Types::Str)
    ->Run();
    if($result === []):
      return false;
    endif;
    $function = json_decode($result[0][CallbackHash::Method->value], true);
    if(is_callable($function[0])):
      call_user_func_array(
        array_shift($function),
        array_merge([$Bot, $Webhook, $Db, $Lang], $function)
      );
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

  /**
   * @param int|TgUser|TgChat $Chat Pass integer to only edit permissions
   */
  public function ChatEdit(
    int|TgUser|TgChat $Chat,
    int $Permissions = null
  ):bool{
    DebugTrace();
    $consult = $this->Db->InsertUpdate(Tables::Chats)
    ->FieldAdd(Chats::Created, time(), Types::Int)
    ->FieldAdd(Chats::LastSeen, time(), Types::Int, Update: true);
    if($Chat instanceof TgUser):
      $consult->FieldAdd(Chats::Id, $Chat->Id, Types::Int)
      ->FieldAdd(Chats::Name, $Chat->Name, Types::Str, Update: true)
      ->FieldAdd(Chats::NameLast, $Chat->NameLast, Types::Str, Update: true)
      ->FieldAdd(Chats::Nick, $Chat->Nick, Types::Str, Update: true)
      ->FieldAdd(Chats::Language, $Chat->Language, Types::Str, Update: true);
    elseif($Chat instanceof TgChat):
      $consult->FieldAdd(Chats::Id, $Chat->Id, Types::Int)
      ->FieldAdd(Chats::Name, $Chat->Name, Types::Str, Update: true)
      ->FieldAdd(Chats::Nick, $Chat->Nick, Types::Int, Update: true);
    endif;
    if($Permissions !== null):
      if($Permissions === 0):
        $Permissions = null;
      endif;
      $consult->FieldAdd(Chats::Permission, $Permissions, Types::Int, Update: true);
    endif;
    try{
      $consult->Run();
      return true;
    }catch(PDOException){
      return false;
    }
  }

  public function ChatGet(
    int|TgUser|TgChat $Chat
  ):ChatData|null{
    DebugTrace();
    $return = $this->Db->Select(Tables::Chats)
    ->WhereAdd(Chats::Id, $Chat->Id ?? $Chat, Types::Int)
    ->Run();
    return $return === [] ? null : new ChatData($return[0]);
  }

  public function CommandAdd(
    string $Command,
    string $Module
  ):void{
    DebugTrace();
    $consult = $this->Db->Insert(Tables::Commands)
    ->FieldAdd(Commands::Name, $Command, Types::Str)
    ->FieldAdd(Commands::Module, $Module, Types::Str);
    try{
      $consult->Run();
    }catch(PDOException $e){
      if(str_contains($e->getMessage(), 'REFERENCES `modules` (`module`)')):
        throw new StbException(StbError::ModuleNotFound, 'Module not found');
      else:
        throw $e;
      endif;
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
      return $return[0][Commands::Module->value];
    endif;
    return $return;
  }

  public function GetCustom():PDO{
    DebugTrace();
    return $this->Db->GetCustom();
  }

  /**
   * @param int $User User ID to associate the listener. Not allowed in checkout and InlineQuery listeners
   * @throws StbException|PDOException
   */
  public function ListenerAdd(
    TgEventInterface|string $Listener,
    string $Class,
    int $Chat = null
  ):void{
    DebugTrace();
    if($this->NoUserListener($Listener)):
      $Chat = null;
    endif;
    if(in_array(TgEventInterface::class, class_implements($Listener)) === false):
      throw new StbException(StbError::ListenerInvalid, 'Informed listener ' . $Listener . ' not implement TgEventInterface');
    endif;
    if(is_object($Listener)):
      $Listener = $Listener::class;
    endif;
    $consult = $this->Db->InsertUpdate(Tables::Listeners)
    ->FieldAdd(Listeners::Name, $Listener, Types::Str)
    ->FieldAdd(Listeners::Chat, $Chat, Types::Str, Update: true)
    ->FieldAdd(Listeners::Module, $Class, Types::Str, Update: true);
    try{
      $consult->Run();
    }catch(PDOException $e){
      $temp = $e->getMessage();
      if(str_contains($temp, 'REFERENCES `modules` (`module`)')):
        throw new StbException(StbError::ModuleNotFound, 'Module not found');
      elseif(str_contains($temp, 'REFERENCES `chats` (`chat_id`)')):
        throw new StbException(StbError::ChatNotFound, 'Chat not found');
      else:
        throw $e;
      endif;
    }
  }

  public function ListenerDel(
    TgEventInterface|string $Listener,
    int $User = null
  ):void{
    DebugTrace();
    if($this->NoUserListener($Listener)):
      $User = null;
    endif;
    if(is_object($Listener)):
      $Listener = $Listener::class;
    endif;
    $this->Db->Delete(Tables::Listeners)
    ->WhereAdd(Listeners::Name, $Listener, Types::Str)
    ->WhereAdd(Listeners::Chat, $User, Types::Int)
    ->Run();
  }

  /**
   * Get the module linked to the specified listener
   */
  public function ListenerGet(
    TgEventInterface $Listener,
    int $User = null
  ):string|null{
    DebugTrace();
    if($this->NoUserListener($Listener)):
      $User = null;
    endif;
    $listeners = $this->Db->Select(Tables::Listeners)
    ->WhereAdd(Listeners::Chat, $User, Types::Int)
    ->Run();
    //Test every listener because of interfaces
    foreach($listeners as $listener):
      if($Listener instanceof $listener['listener']):
        return $listener['module'];
      endif;
    endforeach;
    return null;
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
    TgEventInterface|string $Listener
  ):bool{
    DebugTrace();
    if($Listener instanceof TgEventInterface):
      $Listener = $Listener::class;
    endif;
    if($Listener === TgInlineQuery::class
    or $Listener === TgGroupStatusMy::class):
      return true;
    else:
      return false;
    endif;
  }

  public function UsageLog(
    int $ChatId,
    string $Event,
    string $Additional = null
  ):void{
    DebugTrace();
    $this->Db->Insert(Tables::LogTexts->value)
    ->FieldAdd(LogTexts::Chat, $ChatId, Types::Int)
    ->FieldAdd(LogTexts::Time, time(), Types::Int)
    ->FieldAdd(LogTexts::Event, $Event, Types::Str)
    ->FieldAdd(LogTexts::Msg, $Additional, Types::Str)
    ->Run();
  }

  public function VariableDel(
    string|UnitEnum $Name,
    string $Value = null,
    string $Module = null,
    int $User = null
  ):void{
    DebugTrace();
    if($Name instanceof UnitEnum):
      $Name = $Name->value ?? $Name->name;
    endif;
    $consult = $this->Db->Delete(Tables::Variables)
    ->WhereAdd(Variables::Name, $Name, Types::Str);
    if($Value !== null):
      $consult->WhereAdd(Variables::Value, $Value, Types::Str);
    endif;
    $consult->WhereAdd(Variables::Chat, $User, Types::Int)
    ->WhereAdd(Variables::Module, $Module, Types::Str)
    ->Run();
  }

  public function VariableGetName(
    string|UnitEnum $Value,
    string $Module = null,
    int $User = null
  ):string|null{
    DebugTrace();
    if($Value instanceof UnitEnum):
      $Value = $Value->value ?? $Value->name;
    endif;
    $result = $this->Db->Select(Tables::Variables)
    ->Fields(Variables::Name->value)
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

  public function VariableGetChat(
    string|UnitEnum $Name,
    string $Module = null
  ):string|array|null{
    DebugTrace();
    if($Name instanceof UnitEnum):
      $Name = $Name->value ?? $Name->name;
    endif;
    $result = $this->Db->Select(Tables::Variables)
    ->Fields(Variables::Chat->value)
    ->WhereAdd(Variables::Name, $Name, Types::Str)
    ->WhereAdd(Variables::Module, $Module, Types::Str)
    ->Run();
    if($result === []):
      return null;
    endif;
    return isset($result[1]) ? array_column($result, Variables::Chat->value) : $result[0][Variables::Chat->value];
  }

  public function VariableGetValue(
    string|UnitEnum $Name,
    string $Module = null,
    int $User = null
  ):string|array|null{
    DebugTrace();
    if($Name instanceof UnitEnum):
      $Name = $Name->value ?? $Name->name;
    endif;
    $result = $this->Db->Select(Tables::Variables)
    ->Fields(Variables::Value->value)
    ->WhereAdd(Variables::Name, $Name, Types::Str)
    ->WhereAdd(Variables::Module, $Module, Types::Str)
    ->WhereAdd(Variables::Chat, $User, Types::Int)
    ->Run();
    if($result === []):
      return null;
    endif;
    return isset($result[1]) ? array_column($result, Variables::Value->value) : $result[0][Variables::Value->value];
  }

  public function VariableSet(
    string|UnitEnum $Name,
    string|int|float $Value,
    string $Module = null,
    int $User = null,
    bool $AllowDuplicatedName = false
  ):void{
    DebugTrace();
    if($Name instanceof UnitEnum):
      $Name = $Name->value ?? $Name->name;
    endif;
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