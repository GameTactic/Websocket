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
use App\Domain\Ratchet\Event\WsOnError;
use App\Domain\Ratchet\Event\WsOnMessage;
use App\Domain\Ratchet\Event\WsOnOpen;
use App\Domain\Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Serializer\SerializerInterface;

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
    private OutputInterface $output;
    private SerializerInterface $serializer;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        OutputInterface $output,
        SerializerInterface $serializer
    )
    {
        $this->dispatcher = $dispatcher;
        $this->output = $output;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     *
     * @param $conn LocalConnectionInterface
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->log("New connection with id of $conn->resourceId from $conn->remoteAddress");
        $this->dispatcher->dispatch(new WsOnOpen($conn));
        $this->log("New connection with id of $conn->resourceId from $conn->remoteAddress was handled!", self::LOG_DEBUG);
    }

    /**
     * {@inheritdoc}
     *
     * @param $conn LocalConnectionInterface
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->log("Connection closed for client $conn->resourceId from $conn->remoteAddress", );
        $this->dispatcher->dispatch(new WsOnClose($conn));
        $this->log("Connection closed for client $conn->resourceId from $conn->remoteAddress was handled!", self::LOG_DEBUG);
    }

    /**
     * {@inheritdoc}
     *
     * @param $conn LocalConnectionInterface
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $msg = $e->getMessage();
        $this->log("Error for client $conn->resourceId from $conn->remoteAddress with message of $msg", self::LOG_ERROR);
        //$this->dispatcher->dispatch(new WsOnError($conn, $e));
        $this->log("Error for client $conn->resourceId from $conn->remoteAddress was handled!", self::LOG_DEBUG);
    }

    /**
     * {@inheritdoc}
     *
     * @param $from LocalConnectionInterface
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->log("Client $from->resourceId from $from->remoteAddress sent a message of $msg", self::LOG_INFO, OutputInterface::VERBOSITY_VERBOSE);
        $this->dispatcher->dispatch(new WsOnMessage($from, $msg));
        $this->log("Client $from->resourceId message from $from->remoteAddress was handled", self::LOG_DEBUG);
    }

    private function log(string $msg, $type = self::LOG_INFO, $level = OutputInterface::VERBOSITY_NORMAL): void
    {
        $type = self::LOG_STRINGS[$type];
        $date = (new \DateTimeImmutable())->format(DATE_ATOM);
        $this->output->writeln("$date [$type] $msg", self::LOG_DEBUG === $type ? OutputInterface::VERBOSITY_DEBUG : $level);
    }

    public function onConsume(\AMQPEnvelope $envelope, \AMQPQueue $queue): void
    {
        var_dump($envelope->getBody());
        //var_dump($this->serializer->deserialize($envelope->getBody(), 'class', 'json'));
    }
}
