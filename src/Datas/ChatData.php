<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\Datas;
use ProtocolLive\SimpleTelegramBot\NoStr\Fields\Chats;

/**
 * @version 2024.07.24.00
 */
final readonly class ChatData{
  public int $Id;
  public string $Name;
  public string|null $NameLast;
  public string|null $Nick;
  public string|null $Language;
  public int $Permission;
  public int $Created;
  public int $LastSeen;

  public function __construct(
    array $Data
  ){
    foreach(Chats::cases() as $field):
      $this->{$field->name} = $Data[$field->value];
    endforeach;
  }
}