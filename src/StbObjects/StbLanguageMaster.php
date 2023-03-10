<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2023.02.02.00

namespace ProtocolLive\SimpleTelegramBot\StbObjects;

abstract class StbLanguageMaster{
  protected string $Default;
  protected array $Translate;

  public function Get(
    string $Text,
    string $Language = null,
    string $Group = null
  ):string|null{
    DebugTrace();
    if($Language === null):
      $lang = $this->Default;
    else:
      $lang = $Language;
    endif;
    if($Group === null):
      return $this->Translate[$lang][$Text];
    else:
      return $this->Translate[$lang][$Group][$Text];
    endif;
  }

  public function CommandsGet(
    string $Language
  ):array{
    DebugTrace();
    return $this->Translate[$Language]['Commands'];
  }

  public function LanguagesGet():array{
    DebugTrace();
    return array_keys($this->Translate);
  }
}