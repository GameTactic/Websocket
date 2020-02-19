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

namespace App\Application\Event\Subscriber;

use App\Application\Event\Ws\OnClose;
use App\Application\Event\Ws\OnMessage;
use App\Application\Event\Ws\OnOpen;
use App\Domain\Ratchet\Event\WsOnClose;
use App\Domain\Ratchet\Event\WsOnMessage;
use App\Domain\Ratchet\Event\WsOnOpen;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class GeneralEventPublisher implements EventSubscriberInterface
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
            WsOnClose::class   => 'onClose',
            WsOnOpen::class    => 'onOpen',
            WsOnMessage::class => 'onMessage',
        ];
    }

    public function onClose(WsOnClose $event): void
    {
        $this->eventBus->dispatch(new OnClose($event->connection->resourceId, $event->connection->resourceId));
    }

    public function onOpen(WsOnOpen $event): void
    {
        $this->eventBus->dispatch(new OnOpen($event->connection->resourceId, $event->connection->resourceId));
    }

    public function onMessage(WsOnMessage $event): void
    {
        $this->eventBus->dispatch(new OnMessage($event->from->resourceId, $event->from->resourceId, $event->message));
    }
}
