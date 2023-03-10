<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/FuncoesComuns
//2023.01.23.00

enum WeekDay:int{
  case Sunday = 7;
  case Monday = 1;
  case Tuesday = 2;
  case Wednesday = 3;
  case Thursday = 4;
  case Friday = 5;
  case Saturday = 6;
}

function AccentInsensitive(string $Text):string{
  return iconv('utf-8', 'ascii//TRANSLIT', $Text);
}

function ArgV():void{
  if($_SERVER['argc'] > 0):
    unset($_SERVER['argv'][0]);
    $temp = '';
    foreach($_SERVER['argv'] as $param):
      $temp .= $param . '&';
    endforeach;
    parse_str($temp, $_temp);
    $_SERVER = array_merge($_SERVER, $_temp);
  endif;
}

function ArrayDefrag(array &$Array):void{
  $Array = array_values($Array);
}

/**
 * date and strtotime union
 */
function Dates(string $Format, string|int $Date = null):string{
  if(is_string($Date)):
    $Date = strtotime($Date);
  endif;
  return date($Format, $Date);
}

function DirCreate(
  string $Dir,
  int $Perm = 0755,
  bool $Recursive = true
):bool{
  if(is_dir($Dir)):
    return false;
  else:
    return mkdir($Dir, $Perm, $Recursive);
  endif;
}

function Equals(string $Text1, string $Text2):bool{
  $Text1 = AccentInsensitive($Text1);
  $Text2 = AccentInsensitive($Text2);
  return strcasecmp($Text1, $Text2) === 0;
}

function F2s(\Closure $Function):string{
  $Function = new \ReflectionFunction($Function);
  $return = '';
  $temp = $Function->getNamespaceName();
  if($temp !== ''):
    $return = $temp . '\\';
  endif;
  $temp = $Function->getClosureScopeClass();
  if($temp !== null):
    $temp = $temp->getName();
    if($temp !== ''):
      $return .= $temp . '::';
    endif;
  endif;
  $return .= $Function->getName();
  return $return;
}

function FloatInt(string $Val):int{
  $Val = str_replace(',', '.', $Val);
  $Val = number_format($Val, 2, '.', '');
  return str_replace('.', '', $Val);
}

function GlobRecursive(string $Dir, int $Flags = 0){
  $files = [];
  foreach(glob($Dir . '/*', $Flags) as $file):
    if(is_dir($file)):
      $files = array_merge($files, GlobRecursive($file, $Flags));
    else:
      $files[] = $file;
    endif;
  endforeach;
  return $files;
}

function HashDir(string $Algo, string $Dir):array{
  $hash = [];
  foreach(glob($Dir . '/*') as $file):
    if(is_dir($file)):
      $hash += HashDir($Algo, $file);
    else:
      $hash[$file] = hash_file($Algo, $file);
    endif;
  endforeach;
  return $hash;
}

function Money(int $Val = 0):string{
  $Val /= 100;
  $obj = numfmt_create('pt-br', NumberFormatter::CURRENCY);
  return numfmt_format_currency($obj, $Val, 'BRL');
}

function Number(int $N, int $Precision):string{
  $temp = new NumberFormatter('pt-br', NumberFormatter::DECIMAL);
  $temp->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $Precision);
  return $temp->format($N);
}

function PrintIfSet(mixed &$Var, string $Content = null, string $Else = null):void{
  if(isset($Var) and $Var !== null):
    if($Content === null):
      print $Var;
    else:
      print $Content;
    endif;
  else:
    print $Else;
  endif;
}