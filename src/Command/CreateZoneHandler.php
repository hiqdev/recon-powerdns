<?php

namespace hiqdev\recon\powerdns\Command;

use hiqdev\recon\dns\Command\CreateZoneCommand;

class CreateZoneHandler
{
    public function handle(CreateZoneCommand $command): string
    {
        /** @noinspection ForgottenDebugOutputInspection */
        error_log('Imagine we have used PowerDNS to update zone accordingly'); // TODO: implement

        return 'Successes at ' . time();
    }
}
