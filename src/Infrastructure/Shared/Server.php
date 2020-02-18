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

final class Server implements MessageComponentInterface
{
    private EventDispatcherInterface $dispatcher;
    private OutputInterface $output;

    public function __construct(EventDispatcherInterface $dispatcher, OutputInterface $output)
    {
        $this->dispatcher = $dispatcher;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     *
     * @param $conn LocalConnectionInterface
     */
    public function onOpen($conn)
    {
        $this->log("New connection with id of $conn->resourceId from $conn->remoteAddress.");
        $this->dispatcher->dispatch(new WsOnOpen($conn));
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn)
    {
        return;
        $this->dispatcher->dispatch(new WsOnClose());
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->dispatcher->dispatch(new WsOnError());
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->dispatcher->dispatch(new WsOnMessage());
    }

    private function log(string $msg, $level = OutputInterface::VERBOSITY_NORMAL): void
    {
        $date = (new \DateTimeImmutable())->format(DATE_ATOM);
        $this->output->writeln("[$date] INFO: $msg", $level);
    }
}
