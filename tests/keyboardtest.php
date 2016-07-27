<?php

include "../autoload.php";

class BotTest extends PHPUnit_Framework_TestCase
{
    public function testInlineKeyboard() {
        $keyboard = new Inline_keyboard();
        $keyboard->addLevelButtons(['text' => 'First', 'callback_data' => '1'], ['text' => 'Second', 'callback_data' => '2']);
        $keyboard->getKeyboard();
    }
}