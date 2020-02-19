<?php

/**
 *
 * GameTactic Websocket 2020 — NOTICE OF LICENSE
 * This source file is released under GPLv3 license by copyright holders.
 * Please see LICENSE file for more specific licensing terms.
 * @copyright 2019-2020 (c) GameTactic
 * @author Niko Granö <niko@granö.fi>
 *
 */

namespace App\Domain\Ratchet\Event;

use Ratchet\ConnectionInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class WsOnMessage extends Event
{
    /** @var ConnectionInterface|\App\Domain\Ratchet\ConnectionInterface */
    public ConnectionInterface $from;
    public string $message;

    public function __construct(ConnectionInterface $from, string $message)
    {
        $this->from = $from;
        $this->message = $message;
    }
}
