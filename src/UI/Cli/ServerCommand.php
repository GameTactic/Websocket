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

use App\Infrastructure\Shared\Server;
use Ratchet\Server\IoServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ServerCommand extends Command
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher, string $name = null)
    {
        parent::__construct($name);
        $this->dispatcher = $dispatcher;
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

        $server = IoServer::factory(new Server($this->dispatcher, $output), $port, $host);
        $server->run();
    }
}
