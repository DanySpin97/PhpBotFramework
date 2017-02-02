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

namespace PhpBotFramework\Entities;

/**
 * \addtogroup Entities Entities
 * @{
 */

/** \class CallbackQuery
 * \brief This object represents an incoming inline query.
 * \details When the user sends an empty query, your bot could return some default or trending results.
 */
class InlineQuery implements \ArrayAccess
{

    /** @} */

    use EntityAccess;

    public function getQuery() : string
    {

        // Get text of the message if any
        return isset($this->container['query']) ? $this->container['query'] : null;
    }

    public function getChatID()
    {

        // Return the chat id
        return $this->container['from']['id'];
    }

    public function getBotParameter() : array
    {

        return ['var' => '_inline_query_id', 'id' => $this->container['id']];
    }
}
