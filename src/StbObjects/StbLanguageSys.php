<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use ProtocolLive\SimpleTelegramBot\StbEnums\StbError;

/**
 * @version 2025.05.31.00
 */
final class StbLanguageSys{
  private string $Default;
  private array $Translate;

  public function CommandsGet(
    string $Language
  ):array{
    DebugTrace();
    return $this->Translate[$Language]['Commands'];
  }

  public function Get(
    string $Text,
    string|null $Language = null,
    string|null $Group = null
  ):string|null{
    DebugTrace();
    $lang = $Language ?? $this->Default;
    if(isset($this->Translate[$lang]) === false):
      $this->Load($lang);
    endif;
    if($Group === null):
      return $this->Translate[$lang][$Text];
    else:
      return $this->Translate[$lang][$Group][$Text];
    endif;
  }

  /**
   * @throws StbException
   */
  public function LanguageSet(
    string $Language
  ):void{
    DebugTrace();
    if(is_dir(dirname(__DIR__) . '/language/' . $Language) === false):
      throw new StbException(StbError::LanguageNotFound, 'Language ' . $Language . ' not found');
    endif;
    $this->Default = $Language;
  }

  public function LanguagesGet():array{
    DebugTrace();
    $folders = scandir((dirname(__DIR__) . '/language/'));
    unset($folders[0], $folders[1]);
    return ArrayDefrag($folders);
  }

  private function Load(
    string $Language
  ):void{
    DebugTrace();
    $this->Translate[$Language] = json_load(dirname(__DIR__) . '/language/' . $Language . '/system.json', true);
  }
}