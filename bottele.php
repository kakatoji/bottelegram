<?php

require_once "vendor/autoload.php";


use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Tools;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\RPCErrorException;


class MyEventHandler extends EventHandler
{

    const ADMIN = "kakatoji";
    const BOT_USERNAME = "hkearn_usdt_bot";


    public function getReportPeers()
    {
        return [self::ADMIN];
    }

    public function _Send($text){
     return $this->messages->sendMessage([
          "peer" => self::BOT_USERNAME,
          "message" => $text
      ]);
    }

    public function onStart()
    {
      yield $this->_Send("/earn");
    }

    public function onUpdateNewChannelMessage(array $update): \Generator
    {
        return $this->onUpdateNewMessage($update);
    }

    public function onUpdateNewMessage(array $update): \Generator
    {
        if ($update["message"]["_"] === "messageEmpty" || $update["message"]["out"] ?? false) {
            return;
        }
        $inf = yield $this->getFullInfo(yield $this->getId($update));
        if(array_key_exists("User", $inf) && $inf["User"]["username"] === self::BOT_USERNAME){

          if (isset($update["message"]["reply_markup"]["rows"])) {
              foreach ($update["message"]["reply_markup"]["rows"] as $row) {
                  foreach ($row["buttons"] as $button) {
                      $btext = $button["text"];
                      if(strpos($btext, "Visit sites") !== false){
                        yield $button->click();
                      }elseif(strpos($btext, "View Posts") !== false){
                        yield $button->click();
                        break;
                      }elseif(strpos($btext, "Go to website") !== false){
                        yield $button->click();
                        $ch = curl_init($button["url"]);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_HTTPAGENT, "Mozilla/5.0");
                        curl_exec($ch);
                      }
                  }
              }
          }

          $msg = $update["message"]["message"];

          if(strpos($msg, "😟 Sorry, there are no new ads available") !== false){
            echo "[!] ADS HABIS";
            yield $this->_Send('/balance');
          }elseif(strpos($msg, "🛡 You will need to resolve the captcha to continue") !== false){
            $media = $update["message"]["media"];
            yield $this->downloadToCallable($media, function($x){
              $this->messages->sendMessage("89768");
            });
            echo "[!] MENDETEKSI CHAPTA, silahkan buka telegram anda";
          }elseif(strpos($msg, "You are currently in an active operation; to use another command or function, you must first cancel this operation.") !== false){
            yield $this->_Send("/cancel");
            return;
          }elseif(strpos($msg, "balance") !== false){
            if(preg_match("/(?<=\sbalance\:)(?:[\s.\d]+)/", $msg, $balance)){
              echo "[$] BALANCE: " . trim($balance[0]) . " USDT";
            }
          }else{
            if(preg_match('/(welcome|balance|success)/im', $msg, $__)){
              yield $this->_Send('/earn');
            }
            return;
          }
          echo PHP_EOL . "\x1b[92m" . str_repeat("-", explode(" ", exec("stty size"))[1]) . "\x1b[0m".PHP_EOL;
       }
    }
}

$settings = new Settings;
$settings->getLogger()
    ->setType(Logger::LOGGER_FILE)
    ->setLevel(Logger::LEVEL_FATAL)
    ->setExtra("bacot")
    ->setMaxSize(0);

MyEventHandler::startAndLoop("session.madeline", $settings);
