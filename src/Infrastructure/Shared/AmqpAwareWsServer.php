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

namespace App\Infrastructure\Shared;

use Gos\Component\ReactAMQP\Consumer;
use Ratchet\ComponentInterface;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;

final class AmqpAwareWsServer extends WsServer
{
    public function __construct(ComponentInterface $component, LoopInterface $loop, \AMQPQueue $queue)
    {
        $consumer = new Consumer($queue, $loop, 0.5, 10);
        $consumer->on('consume', [$component, 'onConsume']);
        parent::__construct($component);
    }
}
