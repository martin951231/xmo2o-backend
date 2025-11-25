<?php

namespace emoji;

use think\Exception;

class Emoji
{
  public function emojiDB()
  {
    $emoji_file = root_path() . '../static/emojiJSON/emoji.json';
    if (file_exists($emoji_file)) {
      $json = file_get_contents($emoji_file);
      $list = json_decode($json, true);
    } else {
      $list = [];
    }
    return $list;
  }
}
