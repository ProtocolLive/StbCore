<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\SimpleTelegramBot\Datas\ChatData;
use ProtocolLive\TelegramBotLibrary\TgObjects\TgUser;

abstract class StbBotTools{
  /**
   * Return the user full name with nick and ID as string in HTML
   */
  public static function FormatName(
    TgUser|ChatData $User,
    bool $Nick = true,
    bool $Link = true,
    bool $Id = true
  ):string{
    if($Link):
      $nome = '<a href="tg://user?id=' . $User->Id . '">';
    endif;
    $nome .= $User->Name;
    if($User->NameLast !== null) :
      $nome .= ' ' . $User->NameLast;
    endif;
    if($Link):
      $nome .= '</a>';
    endif;
    if($Nick
    and $User->Nick !== null) :
      $nome .= ' @' . $User->Nick;
    endif;
    if($Id):
      $nome .= ' (' . $User->Id . ')';
    endif;
    return $nome;
  }
}