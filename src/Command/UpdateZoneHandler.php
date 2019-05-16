<?php

namespace hiqdev\recon\powerdns\Command;

use hiqdev\recon\dns\Command\UpdateZoneCommand;
use hiqdev\recon\powerdns\Service\PowerDnsClient;

/**
 * Class UpdateZoneHandler
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class UpdateZoneHandler
{
    /**
     * @var PowerDnsClient
     */
    private $client;

    public function __construct(PowerDnsClient $client)
    {
        $this->client = $client;
    }

    public function handle(UpdateZoneCommand $command): string
    {
        $this->client->upsert($command->zone);

        return 'Successes at ' . time();
    }
}
