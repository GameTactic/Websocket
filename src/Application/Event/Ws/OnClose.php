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

namespace App\Application\Event\Ws;

use App\Application\Event\PublicEvent;
use App\Domain\Ratchet\Event\WsOnClose;

final class OnClose extends On implements PublicEvent
{
    public function __construct(string $resourceId, string $id)
    {
        parent::__construct($resourceId, $id, WsOnClose::class);
    }
}
