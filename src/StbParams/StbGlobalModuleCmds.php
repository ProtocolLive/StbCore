<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbParams;

/**
 * @version 2024.02.14.00
 */
final class StbGlobalModuleCmds{
  private array $Commands = [];

  public function __construct(
    string $Name = null,
    string $Description = null,
    bool $Public = true
  ){
    DebugTrace();
    if($Name === null):
      return;
    endif;
    $this->Add($Name, $Description, $Public);
  }

  public function Add(
    string $Name,
    string $Description,
    bool $Public = true
  ):self{
    DebugTrace();
    $this->Commands[] = (object)[
      'Name' => $Name,
      'Description' => $Description,
      'Public' => $Public
    ];
    return $this;
  }

  public function Get():array{
    DebugTrace();
    return $this->Commands;
  }
}