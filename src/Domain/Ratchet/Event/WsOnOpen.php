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

use App\Domain\Ratchet\ConnectionInterface;
use Ratchet\ConnectionInterface as VendorConnectionInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class WsOnOpen extends Event
{
    public VendorConnectionInterface $conn;

    /**
     * WsOnOpen constructor.
     *
     * @param $conn ConnectionInterface|VendorConnectionInterface
     */
    public function __construct(VendorConnectionInterface $conn)
    {
        $this->conn = $conn;
    }
}
