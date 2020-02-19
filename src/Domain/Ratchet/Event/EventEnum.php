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

final class EventEnum
{
    private const CLASSES = [
        WsOnClose::class,
        WsOnError::class,
        WsOnMessage::class,
        WsOnOpen::class,
    ];

    private object $event;

    public function __construct(object $event)
    {
        if (!\in_array(\get_class($event), self::CLASSES, true)) {
            throw new \LogicException('Must be instance of accepted event classes.');
        }
        $this->event = $event;
    }

    /**
     * @return WsOnClose|WsOnError|WsOnMessage|WsOnOpen
     */
    public function read(): object
    {
        return $this->event;
    }
}
