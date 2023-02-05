<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2023.02.05.00

namespace ProtocolLive\SimpleTelegramBot\StbObjects;

final class StbLanguageSys
extends StbLanguageMaster{
  public function __construct(
    string $Default
  ){
    DebugTrace();
    $this->Default = $Default;
    foreach(glob(dirname(__DIR__) . '/language/*', GLOB_ONLYDIR) as $dir):
      foreach(glob($dir . '/*.json') as $file):
        $temp = file_get_contents($file);
        $temp = json_decode($temp, true);
        $index = basename(dirname($file));
        $this->Translate[$index] = array_merge_recursive(
          $this->Translate[$index] ?? [],
          $temp
        );
      endforeach;
    endforeach;
  }
}