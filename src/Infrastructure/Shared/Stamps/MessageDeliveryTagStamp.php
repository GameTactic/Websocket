<?php


namespace App\Infrastructure\Shared\Stamps;


use Symfony\Component\Messenger\Stamp\StampInterface;

final class MessageDeliveryTagStamp implements StampInterface
{
    public string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}
