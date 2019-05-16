<?php

namespace hiqdev\recon\powerdns\Service;

use hiqdev\recon\dns\Helper\NsHelper;
use hiqdev\recon\dns\Model\Record;
use hiqdev\recon\dns\Model\Zone;

/**
 * Class ZonePayloadBuilder builds PowerDNS API payloads to work with
 * domain zones.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class ZonePayloadBuilder
{
    /**
     * @param Zone $zone zone that should be published
     * @return array
     */
    public function buildPayloadToCreate(Zone $zone): array
    {
        $payload = $this->buildPayload($zone);
        unset($payload['rrsets']);

        return $payload;
    }

    /**
     * @param Zone $zone zone that should be published
     * @param array|null $remoteZone current zone fetch from the PowerDNS servers. Null when the zone is new
     * @return array
     */
    public function buildPayloadToUpdate(Zone $zone, ?array $remoteZone): array
    {
        return $this->markOldRecordSetsRemoved(
            $this->buildPayload($zone),
            $remoteZone ?? [],
        );
    }

    private function buildPayload(Zone $zone): array
    {
        $payload = [
            'name' => NsHelper::canonical($zone->fqdn),
            'type' => 'zone',
            'kind' => 'native',
            'nameservers' => $this->buildNameServersList($zone),
            'rrsets' => $this->buildRecordSets($zone),
        ];

        return $payload;
    }

    /**
     * @param Zone $zone
     * @return string[]
     */
    private function buildNameServersList(Zone $zone): array
    {
        $nameServers = [];

        foreach ($zone->records as $record) {
            if ($record->typeIs(Record::NS)) {
                $nameServers[] = NsHelper::canonical($record->value);
            }
        }

        return $nameServers;
    }

    private function buildRecordSets(Zone $zone): array
    {
        $rrsets = [$this->buildSOARecord($zone)];

        foreach ($this->groupRecords($zone) as $key => $records) {
            /** @var Record[] $records */
            /** @var Record $firstRecord */
            $firstRecord = reset($records);
            $recordSet = [
                'changetype' => 'REPLACE',
                'name' => $firstRecord->canonicalFqdn(),
                'type' => $firstRecord->type,
                'ttl' => $firstRecord->ttl,
                'records' => [],
            ];

            foreach ($records as $record) {
                $recordSet['records'][] = [
                    'content' => $record->canonicalValue(),
                    'disabled' => false,
                ];
            }

            $rrsets[] = $recordSet;
        }

        return $rrsets;
    }

    /**
     * @param Zone $zone
     * @return array records grouped by FQDN, type and TTL
     */
    private function groupRecords(Zone $zone): array
    {
        $recordsByFqdnAndType = [];
        foreach ($zone->records as $record) {
            $key = implode(':', [$record->canonicalFqdn(), $record->type, $record->ttl]);
            $recordsByFqdnAndType[$key][] = $record;
        }

        return $recordsByFqdnAndType;
    }

    private function buildSOARecord(Zone $zone): array
    {
        $soa = $zone->soa;

        $record = [
            'changetype' => 'REPLACE',
            'name' => NsHelper::canonical($zone->fqdn),
            'ttl' => (int)$soa->ttl,
            'type' => 'SOA',
            'records' => [
                [
                    'content' => implode(' ', [
                        NsHelper::canonical($zone->fqdn),
                        NsHelper::canonical('hostmaster.' . $zone->fqdn),
                        date('Ymd') . sprintf('%02d', random_int(0, 99)),
                        $soa->refresh,
                        $soa->retry,
                        $soa->expire,
                        $soa->ttl,
                    ]),
                    'disabled' => false,
                ],
            ],
        ];

        return $record;
    }

    private function markOldRecordSetsRemoved(array $zoneConfig, array $oldZoneConfig): array
    {
        $rrsetsToBeRemoved = [];

        foreach ($oldZoneConfig['rrsets'] ?? [] as $rrset) {
            $rrsetsToBeRemoved[] = [
                'changetype' => 'DELETE',
                'name' => $rrset['name'],
                'ttl' => $rrset['ttl'],
                'type' => $rrset['type'],
            ];
        }

        $zoneConfig['rrsets'] = array_merge($rrsetsToBeRemoved, $zoneConfig['rrsets'] ?? []);

        return $zoneConfig;
    }
}
