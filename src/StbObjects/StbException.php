<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/TelegramBotLibrary

namespace ProtocolLive\SimpleTelegramBot\StbObjects;
use Exception;
use ProtocolLive\SimpleTelegramBot\StbEnums\StbError;
use Throwable;

/**
 * @version 2024.02.14.00
 */
class StbException
extends Exception{
  /**
   * @param StbError $code The Exception code.
   * @param string $message [optional] The Exception message to throw.
   * @param Throwable|null $previous [optional] The previous throwable used for the exception chaining.
   */
  public function __construct(
    protected $code,
    protected $message = null,
    protected Throwable|null $previous = null
  ){}
}