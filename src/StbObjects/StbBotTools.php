<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\SimpleTelegramBot\Datas\ChatData;
use ProtocolLive\TelegramBotLibrary\TgObjects\TgUser;

/**
 * @version 2024.11.13.00
 */
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
    $name = '';
    if($Link):
      $name = '<a href="tg://user?id=' . $User->Id . '">';
    endif;
    $name .= $User->Name;
    if($User->NameLast !== null) :
      $name .= ' ' . $User->NameLast;
    endif;
    if($Link):
      $name .= '</a>';
    endif;
    if($Nick
    and $User->Nick !== null) :
      $name .= ' @' . $User->Nick;
    endif;
    if($Id):
      $name .= ' (<code>' . $User->Id . '</code>)';
    endif;
    return $name;
  }
}