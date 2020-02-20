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

namespace App\UI\Cli;

use App\Infrastructure\Shared\AmqpAwareWsServer;
use App\Infrastructure\Shared\Server;
use Psr\Log\LoggerInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Server as Reactor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ServerCommand extends Command
{
    private Server $server;
    private LoggerInterface $log;

    public function __construct(Server $server, LoggerInterface $log, string $name = null)
    {
        parent::__construct($name);
        $this->server = $server;
        $this->log = $log;
    }

    protected function configure()
    {
        $this
            ->setName('app:serve')
            ->setDescription('Will run the websocket server')
            ->addArgument('host', null, 'Set listening host.', '127.0.0.1')
            ->addArgument('port', null, 'Set listening port.', '1337');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $input->getArgument('host');
        $port = $input->getArgument('port');
        $output->writeln(sprintf('Starting server on %s:%s', $host, $port), OutputInterface::VERBOSITY_VERBOSE);

        // AMQP
        $output->writeln('Waiting AMQP to come online...');
        $conn = new \AMQPConnection([
            'host'     => 'amqp',
            'vhost'    => '/',
            'port'     => '5672',
            'login'    => 'guest',
            'password' => 'guest',
        ]);
        $conn->connect();
        $channel = new \AMQPChannel($conn);
        $exchange = new \AMQPExchange($channel);
        $exchange->setType(AMQP_EX_TYPE_FANOUT);
        $exchange->setFlags(AMQP_DURABLE);
        $exchange->setName('messages');
        $exchange->declareExchange();
        $queue = new \AMQPQueue($channel);
        $queue->setName('messages');
        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();
        $queue->bind('messages');

        $output->writeln('Server starting...');
        $loop = LoopFactory::create();
        $socket = new Reactor("$host:$port", $loop);
        $server = new AmqpAwareWsServer($this->server, $loop, $queue, $exchange, $this->log);
        $server->enableKeepAlive($loop, 30);
        $server = new IoServer(new HttpServer($server), $socket, $loop);
        $output->writeln('Server up!');
        $server->run();
    }
}
