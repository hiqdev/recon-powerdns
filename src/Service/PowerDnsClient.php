<?php

namespace hiqdev\recon\powerdns\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use hiqdev\recon\dns\Model\Zone;

/**
 * Class PowerDnsClient
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class PowerDnsClient
{
    /**
     * @var ClientInterface
     */
    private $client;
    /**
     * @var ZonePayloadBuilder
     */
    private $payloadBuilder;

    public function __construct(ClientInterface $client, ZonePayloadBuilder $payloadBuilder)
    {
        $this->client = $client;
        $this->payloadBuilder = $payloadBuilder;
    }

    /**
     * Creates or updates the passed $zone on the PowerDNS service
     *
     * @param Zone $zone
     * @throws PowerDnsResponseException when command failes
     */
    public function upsert(Zone $zone): void
    {
        try {
            $remoteZone = $this->readRemoteZone($zone);
            if ($remoteZone === null) {
                $this->createZone($zone);
            }

            $response = $this->client->request('PATCH', "servers/localhost/zones/{$zone->fqdn}", [
                'json' => $this->payloadBuilder->buildPayloadToUpdate($zone, $remoteZone),
            ]);
            if ($response->getStatusCode() !== 204) {
                throw PowerDnsResponseException::fromResponse($response, 'Failed to update zone');
            }
        } catch (GuzzleException $e) {
            throw PowerDnsResponseException::fromException($e, 'Failed to update zone');
        }
    }

    /**
     * @param Zone $zone
     * @throws PowerDnsResponseException
     */
    public function delete(Zone $zone): void
    {
        $remoteZone = $this->readRemoteZone($zone);
        if ($remoteZone === null) {
            return;
        }

        try {
            $response = $this->client->request('DELETE', "servers/localhost/zones/{$zone->fqdn}");
            if ($response->getStatusCode() !== 204) {
                throw PowerDnsResponseException::fromResponse($response, 'Failed to remove zone');
            }
        } catch (GuzzleException $e) {
            throw PowerDnsResponseException::fromException($e, 'Failed to remove zone');
        }
    }

    private function createZone(Zone $zone): void
    {
        $response = $this->client->request('POST', 'servers/localhost/zones', [
            'json' => $this->payloadBuilder->buildPayloadToCreate($zone),
        ]);

        if ($response->getStatusCode() !== 201) {
            throw PowerDnsResponseException::fromResponse($response, 'Failed to create zone');
        }
    }

    private function readRemoteZone(Zone $zone): ?array
    {
        $response = $this->client->request('GET', "servers/localhost/zones/{$zone->fqdn}", [
            'http_errors' => false,
        ]);

        if ($response->getStatusCode() === 422) {
            return null;
        }
        if ($response->getStatusCode() !== 200) {
            throw PowerDnsResponseException::fromResponse($response, 'Failed to get zone info');
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
