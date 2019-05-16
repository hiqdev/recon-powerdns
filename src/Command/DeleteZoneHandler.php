<?php

namespace hiqdev\recon\powerdns\Command;

use hiqdev\recon\dns\Command\DeleteZoneCommand;
use hiqdev\recon\powerdns\Service\PowerDnsClient;

/**
 * Class DeleteZoneHandler
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class DeleteZoneHandler
{
    /**
     * @var PowerDnsClient
     */
    private $client;

    public function __construct(PowerDnsClient $client)
    {
        $this->client = $client;
    }

    public function handle(DeleteZoneCommand $command): string
    {
        $this->client->delete($command->zone);

        return 'Successes at ' . time();
    }
}
