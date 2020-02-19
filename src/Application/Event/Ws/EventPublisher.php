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

namespace App\Application\Event\Ws;

use App\Domain\Ratchet\Event\EventEnum;
use App\Domain\Ratchet\Event\WsOnClose;
use App\Domain\Ratchet\Event\WsOnError;
use App\Domain\Ratchet\Event\WsOnMessage;
use App\Domain\Ratchet\Event\WsOnOpen;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class EventPublisher implements EventSubscriberInterface
{
    private MessageBusInterface $eventBus;

    public function __construct(MessageBusInterface $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WsOnClose::class   => 'publishEvent',
            WsOnOpen::class    => 'publishEvent',
            WsOnError::class   => 'publishEvent',
            WsOnMessage::class => 'publishEvent',
        ];
    }

    public function publishEvent(EventEnum $enum): void
    {
    }
}
