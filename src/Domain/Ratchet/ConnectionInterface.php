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

namespace App\Domain\Ratchet;

use const Ratchet\VERSION;

/**
 * Interface ConnectionInterface.
 *
 * @property int $resourceId
 * @property string $remoteAddress
 */
interface ConnectionInterface
{
    public const VERSION = VERSION;

    /**
     * Send data to the connection.
     */
    public function send(string $data): self;

    /**
     * Close the connection.
     */
    public function close(): void;
}
