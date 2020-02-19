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

abstract class On
{
    protected string $resourceId;
    protected string $id;
    protected string $type;

    public function __construct(string $resourceId, string $id, string $type)
    {
        $this->resourceId = $resourceId;
        $this->id = $id;
        $this->type = $type;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
