<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use Exception;

/**
 * @version 2023.11.21.01
 */
abstract class StbLanguageMaster{
  protected string $Default;
  protected array $Translate;

  public function CommandsGet(
    string $Language
  ):array{
    DebugTrace();
    return $this->Translate[$Language]['Commands'];
  }

  public function Get(
    string $Text,
    string $Language = null,
    string $Group = null
  ):string|null{
    DebugTrace();
    $lang = $Language ?? $this->Default;
    if($Group === null):
      return $this->Translate[$lang][$Text];
    else:
      return $this->Translate[$lang][$Group][$Text];
    endif;
  }

  public function LanguagesGet():array{
    DebugTrace();
    return array_keys($this->Translate);
  }

  /**
   * @throws Exception
   */
  public function LanguageSet(
    string $Language
  ):void{
    if(isset($this->Translate[$Language]) === false):
      throw new Exception('Language not found');
    endif;
    $this->Default = $Language;
  }
}