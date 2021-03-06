<?php

/*
 * This file is part of the PhpBotFramework.
 *
 * PhpBotFramework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, version 3.
 *
 * PhpBotFramework is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace PhpBotFramework\Core;

use PhpBotFramework\Exceptions\BotException;
use PhpBotFramework\Logging\Logging;
use PhpBotFramework\Commands\CommandHandler;
use PhpBotFramework\Entities\Message;
use PhpBotFramework\Entities\CallbackQuery;
use PhpBotFramework\Entities\ChosenInlineResult;
use PhpBotFramework\Entities\InlineQuery;

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use PhpBotFramework\Logging\TelegramHandler;

/**
 * \class Bot Bot
 * \brief Bot class to handle updates and commands.
 * \details Class Bot to handle task like API request, or more specific API method like sendMessage, editMessageText, etc..
 * An example of its usage is available in webhook.php
 *
 */
class BasicBot extends CoreBot
{
    use CommandHandler,
        Run,
        Logging;

    /** @internal
      * \brief True if the bot is using webhook? */
    protected $_is_webhook = false;

    public $answerUpdate;

    public static $update_types = [
            'message' => 'Message',
            'callback_query' => 'CallbackQuery',
            'inline_query' => 'InlineQuery',
            'channel_post' => 'ChannelPost',
            'edited_message' => 'EditedMessage',
            'edited_channel_post' => 'EditedChannelPost',
            'chosen_inline_result' => 'ChosenInlineResult',
            'pre_checkout_query' => 'PreCheckoutQuery',
            'shipping_query' => 'ShippingQuery'
        ];

    /**
     * \brief Construct an empty base bot.
     * \details Construct a base bot that can handle updates.
     */
    public function __construct(string $token)
    {
        parent::__construct($token);

        $this->answerUpdate = [];

        // Init all default fallback for updates
        foreach (BasicBot::$update_types as $type => $classes) {
            $this->answerUpdate[$type] = function ($bot, $message) {
            };
        }

        // Add alias for entity classes
        class_alias('PhpBotFramework\Entities\Message', 'PhpBotFramework\Entities\EditedMessage');
        class_alias('PhpBotFramework\Entities\Message', 'PhpBotFramework\Entities\ChannelPost');
        class_alias('PhpBotFramework\Entities\Message', 'PhpBotFramework\Entities\EditedChannelPost');
    }

    /** @} */

    /**
     * @internal
     * \brief Dispatch each update to the right method (processMessage, processCallbackQuery, etc).
     * \details Set $chat_id for each update, $text, $data and $query are set for each update that contains them.
     * @param array $update Reference to the update received.
     * @return int The id of the update processed.
     */
    protected function processUpdate(array $update) : int
    {
        if ($this->processCommands($update)) {
            return $update['update_id'];
        }

        // For each update type
        foreach (BasicBot::$update_types as $offset => $class) {
            // Did we receive this type of the update?
            if (isset($update[$offset])) {
                $object_class = "PhpBotFramework\Entities\\$class";
                $object = new $object_class($update[$offset]);

                $this->chat_id = $object->getChatID();

                $this->setAdditionalData($object);

                $this->answerUpdate[$offset]($this, $object);

                return $update['update_id'];
            }
        }
    }

    protected function setAdditionalData($entity)
    {
        if (method_exists($entity, 'getBotParameter')) {
            $var = $entity->getBotParameter();
            $this->{$var['var']} = $var['id'];
        }
    }

    /**
     * \brief Set compatibilityu mode for old processes method.
     * \details If your bot uses `processMessage` or another deprecated function, call this method to make the old version works.
     */
    public function oldDispatch()
    {
        // For each update type
        foreach (BasicBot::$update_types as $offset => $class) {
            // Check if the bot has an inherited method
            if (method_exists($this, 'process' . $class)) {
                // Wrap it in a closure to make it works with the 3.0 version
                $this->answerUpdate[$offset] = function ($bot, $entity) use ($class) {
                    $bot->{"process$class"}($entity);
                };
            }
        }
    }

    public function init()
    {
        $this->initCommands();
        if ($this->_is_webhook) {
            $this->logger->pushHandler(new StreamHandler('/var/log/' . $this->bot_name . '.log', Logger::WARNING));
        } else {
            if ($this->getBotId() === 0) {
                throw new BotException("The bot could not be started");
            }
            $logger_path = $this->getScriptPath() . '/' . $this->bot_name . '.log';
            $this->logger->pushHandler(new StreamHandler($logger_path, Logger::WARNING));
            print("The bot has been started successfully.\nA log file has been created at " . $logger_path .
                "\nTo stop it press <C-c> (Control-C).");
            $this->logger->warning("START LOGGING");
        }

        if ($this->getChatLog() !== "") {
            $this->logger->pushHandler(new TelegramHandler($this, Logger::WARNING));
        }
    }

    /** @} */
}
