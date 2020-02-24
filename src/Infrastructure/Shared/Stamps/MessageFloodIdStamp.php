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

namespace App\Infrastructure\Shared\Stamps;

use Symfony\Component\Messenger\Stamp\StampInterface;

final class MessageFloodIdStamp implements StampInterface
{
    public string $id;

    public function __construct()
    {
        $this->id = md5(microtime());
    }
}
