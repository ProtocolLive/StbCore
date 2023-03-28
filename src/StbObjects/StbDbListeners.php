<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2023.03.28.00

namespace ProtocolLive\SimpleTelegramBot\StbObjects;

enum StbDbListeners{
  case Chat;
  case ChatMy;
  case ChatPhotoNew;
  case Document;
  case Text;
  case InlineQuery;
  case Invoice;
  case InvoiceCheckout;
  case InvoiceShipping;
  case Photo;
  case PinnedMsg;
  case RequestChat;
  case RequestUser;
  case Video;
  case Voice;
}