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

use App\Domain\Ratchet\ConnectionInterface as LocalConnectionInterface;
use App\Domain\Ratchet\Event\WsOnClose;
use App\Domain\Ratchet\Event\WsOnMessage;
use App\Domain\Ratchet\Event\WsOnOpen;
use App\Domain\Ratchet\MessageComponentInterface;
use App\Infrastructure\Shared\Stamps\MessageDeliveryTagStamp;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\RejectRedeliveredMessageException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final class Server implements MessageComponentInterface
{
    private const LOG_DEBUG = 0x1;
    private const LOG_INFO = 0x2;
    private const LOG_WARN = 0x3;
    private const LOG_ERROR = 0x4;
    private const LOG_STRINGS = [
        self::LOG_DEBUG => 'debug',
        self::LOG_INFO  => 'info',
        self::LOG_WARN  => 'warning',
        self::LOG_ERROR => 'error',
    ];

    private EventDispatcherInterface $dispatcher;
    private LoggerInterface $log;
    private SerializerInterface $serializer;
    private MessageBusInterface $eventBus;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        SerializerInterface $serializer,
        MessageBusInterface $eventBus,
        LoggerInterface $logger
    ) {
        $this->dispatcher = $dispatcher;
        $this->log = $logger;
        $this->serializer = $serializer;
        $this->eventBus = $eventBus;
    }

    /**
     * {@inheritdoc}
     *
     * @param $conn LocalConnectionInterface
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->log->info("New connection with id of $conn->resourceId from $conn->remoteAddress");
        $this->dispatcher->dispatch(new WsOnOpen($conn));
        $this->log->debug("New connection with id of $conn->resourceId from $conn->remoteAddress was handled!");
    }

    /**
     * {@inheritdoc}
     *
     * @param $conn LocalConnectionInterface
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->log->info("Connection closed for client $conn->resourceId from $conn->remoteAddress");
        $this->dispatcher->dispatch(new WsOnClose($conn));
        $this->log->debug("Connection closed for client $conn->resourceId from $conn->remoteAddress was handled!");
    }

    /**
     * {@inheritdoc}
     *
     * @param $conn LocalConnectionInterface
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $msg = $e->getMessage();
        $this->log->warning("Error for client $conn->resourceId from $conn->remoteAddress with message of $msg");
        //$this->dispatcher->dispatch(new WsOnError($conn, $e));
        $this->log->debug("Error for client $conn->resourceId from $conn->remoteAddress was handled!");
    }

    /**
     * {@inheritdoc}
     *
     * @param $from LocalConnectionInterface
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->log->info("Client $from->resourceId from $from->remoteAddress sent a message of $msg");
        $this->dispatcher->dispatch(new WsOnMessage($from, $msg));
        $this->log->debug("Client $from->resourceId message from $from->remoteAddress was handled");
    }

    public function onConsume(\AMQPEnvelope $envelope, \AMQPQueue $queue): void
    {
        /** @var Envelope $message */
        $message = $this->serializer->decode(['headers' => $envelope->getHeaders(), 'body' => $envelope->getBody()]);
        $message = $message->with(new MessageDeliveryTagStamp($envelope->getDeliveryTag()));
        $event = new WorkerMessageReceivedEvent($message, 'public');
        $this->dispatcher->dispatch($event);

        if (!$event->shouldHandle()) {
            return;
        }

        try {
            $this->eventBus->dispatch($message->with(new ReceivedStamp('public'), new ConsumedByWorkerStamp()));
        } catch (\Throwable $e) {
            $rejectFirst = $e instanceof RejectRedeliveredMessageException;
            if ($rejectFirst) {
                $queue->reject($envelope->getDeliveryTag());
            }

            if ($e instanceof HandlerFailedException) {
                $message = $e->getEnvelope();
            }

            $this->dispatcher->dispatch(new WorkerMessageFailedEvent($message, 'public', $e));

            if (!$rejectFirst) {
                $queue->reject($message->last(MessageDeliveryTagStamp::class)->id);
            }

            return;
        }

        $this->dispatcher->dispatch(new WorkerMessageHandledEvent($message, 'public'));
        $queue->ack($message->last(MessageDeliveryTagStamp::class)->id);
    }
}
