<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/SimpleTelegramBot
//2023.05.20.00

namespace ProtocolLive\SimpleTelegramBot\StbObjects;

enum StbDbListeners{
  case Animation;
  case Chat;
  case ChatMy;
  case ChatPhotoNew;
  case Contact;
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
  case Sticker;
  case Video;
  case Voice;
}