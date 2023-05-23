<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use Exception;

/**
 * @version 2023.05.23.00
 */
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

  public function LanguagesGet():array{
    DebugTrace();
    return array_keys($this->Translate);
  }
}