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

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class      => ['all' => true],
    Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle::class => ['all' => true],
    Gos\Bundle\WebSocketBundle\GosWebSocketBundle::class       => ['all' => true],
];
