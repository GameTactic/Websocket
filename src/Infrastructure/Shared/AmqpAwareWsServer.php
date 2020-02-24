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

use AMQPEnvelope;
use AMQPExchange;
use AMQPQueue;
use Gos\Component\ReactAMQP\Consumer;
use Psr\Log\LoggerInterface;
use Ratchet\ComponentInterface;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

final class AmqpAwareWsServer extends WsServer
{
    private const QUORUM_PING = 0x1;
    private const QUORUM_VOTE_INIT = 0x2;
    private const QUORUM_VOTE = 0x3;
    private const QUORUM_ANNOUNCE_MASTER = 0x4;
    private const QUORUM_ANNOUNCE_SLAVE = 0x5;

    private bool $master = false;
    private bool $elections = false;
    private bool $voting = false;
    private int $masterUpdated = 0;
    private ComponentInterface $component;
    private LoggerInterface $log;
    private string $nodeId;
    private int $electionRetry = 0;
    private AMQPExchange $exchange;
    private AMQPQueue $queue;
    private array $flood = [];
    private array $votes = [];
    private array $usedTickets = [];
    private ?TimerInterface $votingTimer = null;

    public function __construct(
        ComponentInterface $component,
        LoopInterface $loop,
        AMQPQueue $queue,
        AMQPExchange $exchange,
        LoggerInterface $log
    ) {
        $this->exchange = $exchange;
        $this->queue = $queue;
        $this->log = $log;
        $this->component = $component;
        $this->nodeId = substr(md5(microtime(false)), 0, 7);
        $this->log->info("New node $this->nodeId at your service!");
        $consumer = new Consumer($queue, $loop, 0.5, 10);
        $consumer->on('consume', [$this, 'onEnvelope']);
        $this->votingTimer = $loop->addPeriodicTimer(15, [$this, 'countVotes']);
        $this->election();
        parent::__construct($this->component);
    }

    public function election(): void
    {
        if (!$this->master && $this->masterUpdated > (time() - 10)) {
            $this->log->debug('No elections required');

            return;
        }
        $this->log->notice('Node is not master or slave. Starting elections');
        $this->elections = true;

        $this->log->debug('Checking if Master node is elected');
        $this->publish('master');
    }

    private function iWantBeSlave(array $data, AMQPEnvelope $envelope): void
    {
        $this->log->info("I am a slave of $data[from]");
        $this->master = false;
        $this->elections = false;
        $this->masterUpdated = time();
        $this->usedTickets[] = $data['flood'];
        $this->publish('master', self::QUORUM_ANNOUNCE_SLAVE);
    }

    private function resolveMaster(array $data, AMQPEnvelope $envelope): void
    {
        if ($this->electionRetry >= 5) {
            $this->log->notice('Master is not available! Starting election for new.');
            $this->queue->ack($envelope->getDeliveryTag());

            $this->log->info('Voting for myself as new master.');
            $this->votes = [$this->nodeId => 1];

            $this->log->info('Waiting to other nodes vote');
            $this->publish('all', self::QUORUM_VOTE_INIT);
            $this->voting = true;

            return;
        }

        ++$this->electionRetry;
        $this->log->debug('Master did not response. Retry #'.$this->electionRetry);
        sleep(1);
        $this->queue->reject($envelope->getDeliveryTag(), AMQP_REQUEUE);
    }

    private function registerVote(array $data, AMQPEnvelope $envelope): void
    {
        if (isset($this->votes[$data['payload']])) {
            $this->log->debug("Adding vote to existing node $data[payload]");
            ++$this->votes[$data['payload']];
        } else {
            $this->log->debug("Adding vote to new node $data[payload]");
            $this->votes[$data['payload']] = 1;
        }

        $this->queue->ack($envelope->getDeliveryTag());
    }

    public function countVotes(): void
    {
        if (!$this->voting) {
            return;
        }

        $this->voting = false;
        asort($this->votes);
        $masterId = array_key_last($this->votes);
        $this->log->info("Electing $masterId as new Master!");
        $this->publish('all', self::QUORUM_ANNOUNCE_MASTER, $masterId);
    }

    private function handleQuorum(array $data, AMQPEnvelope $envelope): void
    {
        if (isset($this->usedTickets[$data['flood']])) {
            $this->flood($envelope, $data);

            return;
        }

        if ('all' === $data['to'] && $data['from'] !== $this->nodeId) {
            if (self::QUORUM_VOTE_INIT === $data['type']) {
                $this->log->notice('Received election voting initializer. Cleaning...');
                $this->elections = true;
                $this->master = false;
                $this->log->info("Voting for $data[from] as new master");
                $this->publish($data['from'], self::QUORUM_VOTE, $data['from']);

                return;
            }

            return;
        }

        if ($this->elections && self::QUORUM_PING === $data['type'] && 'master' === $data['alias']) {
            $this->iWantBeSlave($data, $envelope);

            return;
        }

        if (self::QUORUM_ANNOUNCE_SLAVE === $data['type'] && $this->master) {
            $this->log->info("New Slave $data[from] joined the cluster");

            return;
        }

        if ('all' === $data['to'] && self::QUORUM_ANNOUNCE_MASTER === $data['type']) {
            if ($data['payload'] === $this->nodeId) {
                $this->log->info('I was elected as master of all nodes');
                $this->master = true;
                $this->elections = false;
            } else {
                $this->iWantBeSlave($data, $envelope);
            }

            return;
        }

        if ('master' === $data['to'] && $this->master && $data['from'] !== $this->nodeId && self::QUORUM_PING === $data['type']) {
            $this->log->debug("Got ping from $data[from]");
            $this->publish($data['from']);

            return;
        }

        if ($data['to'] === $this->nodeId) {
            if (self::QUORUM_VOTE === $data['type']) {
                $this->registerVote($data, $envelope);

                return;
            }

            if (self::QUORUM_PING === $data['type'] && 'master' === $data['alias']) {
                $this->iWantBeSlave($data, $envelope);

                return;
            }
        }

        if ($data['from'] === $this->nodeId && self::QUORUM_PING === $data['type'] && $this->elections) {
            // When trying to find master, but it was redirected here...
            $this->resolveMaster($data, $envelope);

            return;
        }

        $this->flood($envelope, $data);
    }

    private function flood(AMQPEnvelope $envelope, array $data): void
    {
        // Flood blocking.
        if (isset($this->flood[$data['flood']]) && $this->flood[$data['flood']] >= 15) {
            $this->log->debug("Rejected message from $data[from] to prevent flooding.");
            $this->queue->reject($envelope->getDeliveryTag());

            return;
        }
        $this->flood[$data['flood']] = isset($this->flood[$data['flood']]) ? $this->flood[$data['flood']] + 1 : 1;
        $this->queue->reject($envelope->getDeliveryTag(), AMQP_REQUEUE);
    }

    public function onEnvelope(AMQPEnvelope $envelope, AMQPQueue $queue): void
    {
        if (!('Quorum:' === substr($envelope->getBody(), 0, 7))) {
            \call_user_func([$this->component, 'onConsume'], $envelope, $queue);

            return;
        }
        $this->handleQuorum(json_decode(substr($envelope->getBody(), 7), true), $envelope);
    }

    private function publish(string $to, int $type = self::QUORUM_PING, ?string $payload = null): void
    {
        // @noinspection PhpUnhandledExceptionInspection
        $this->exchange->publish('Quorum:'.json_encode(
            [
                    'flood'   => crc32(microtime()),
                    'from'    => $this->nodeId,
                    'alias'   => $this->master ? 'master' : 'slave',
                    'to'      => $to,
                    'type'    => $type,
                    'payload' => $payload,
                ]
        ), 'messages');
    }
}
