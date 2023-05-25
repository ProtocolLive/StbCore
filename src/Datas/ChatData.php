<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\Datas;
use ProtocolLive\SimpleTelegramBot\NoStr\Fields\Chats;

/**
 * @version 2023.05.25.00
 */
final class ChatData{
  public readonly int $Id;
  public readonly string $Name;
  public readonly string|null $NameLast;
  public readonly string|null $Nick;
  public readonly string|null $Language;
  public readonly int $Permission;
  public readonly int $Created;
  public readonly int $LastSeen;

  public function __construct(array $Data){
    foreach(Chats::cases() as $field):
      $this->{$field->name} = $Data[$field->value];
    endforeach;
    /*
    $this->Id = $Data[Chats::Id->value];
    $this->Created = $Data[Chats::Created->value];
    $this->Perms = $Data[Chats::Permission->value];
    $this->Name = $Data[Chats::Name->value];
    $this->NameLast = $Data[Chats::NameLast->value];
    $this->Nick = $Data[Chats::Nick->value];
    $this->Language = $Data[Chats::Language->value];
    $this->LastSeen = $Data[Chats::LastSeen->value];
    */
  }
}