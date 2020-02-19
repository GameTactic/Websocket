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
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Server as Reactor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class ServerCommand extends Command
{
    private EventDispatcherInterface $dispatcher;
    private SerializerInterface $serializer;

    public function __construct(EventDispatcherInterface $dispatcher, SerializerInterface $serializer, string $name = null)
    {
        parent::__construct($name);
        $this->dispatcher = $dispatcher;
        $this->serializer = $serializer;
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
        $output->writeln('Waiting 10 seconds AMQP to become online...');
        sleep(10); // Wait AMQP to come online.
        $conn = new \AMQPConnection([
            'host'     => 'amqp',
            'vhost'    => '/',
            'port'     => '5672',
            'login'    => 'guest',
            'password' => 'guest',
        ]);
        $conn->connect();
        $channel = new \AMQPChannel($conn);
        $queue = new \AMQPQueue($channel);
        $queue->setName('messages');
        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();
        //$exchange = new \AMQPExchange($channel);
        //$exchange->setName('GT_WS');
        //$exchange->declare();

        $output->writeln('Server starting...');
        $loop = LoopFactory::create();
        $socket = new Reactor("$host:$port", $loop);
        $server = new AmqpAwareWsServer(new Server($this->dispatcher, $output, $this->serializer), $loop, $queue);
        $server->enableKeepAlive($loop, 30);
        $server = new IoServer(new HttpServer($server), $socket, $loop);
        $output->writeln('Server up!');
        $server->run();
    }
}
