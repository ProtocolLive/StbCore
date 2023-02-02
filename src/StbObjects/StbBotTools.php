<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/FuncoesComuns
//2023.02.02.03

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\SimpleTelegramBot\StbObjects\{
  StbDatabase,
  StbDbAdminData,
  StbDbAdminPerm,
  StbLog
};
use ProtocolLive\TelegramBotLibrary\TblObjects\TblCmd;
use ProtocolLive\TelegramBotLibrary\TelegramBotLibrary;
use ProtocolLive\TelegramBotLibrary\TgObjects\{
  TgChat,
  TgParseMode,
  TgUser
};

abstract class StbBotTools{
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
}