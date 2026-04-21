<?php

class FreeAtHomeDiscovery extends IPSModule
{
    // GUID der Bridge-Instanz die angelegt werden soll
    const mBridgeModuleId = '{9AFFB383-D756-8422-BCA0-EFD3BB1E3E29}';

    // Timeout pro Host in Millisekunden
    const SCAN_TIMEOUT_MS = 400;

    // Maximale parallele cURL-Handles
    const SCAN_PARALLEL = 16;

    public function Create()
    {
        parent::Create();
    }

    public function Destroy()
    {
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    }

    // ====================================================================
    //  Discovery-Einsprungpunkt - wird von IPS aufgerufen
    // ====================================================================

    public function GetConfigurationForm()
    {
        $lForm   = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $lValues = [];

        $lFound = $this->scanSubnet();

        foreach ($lFound as $lHost)
        {
            $lInstanceId = $this->findExistingBridgeInstance($lHost['ip']);

            $lEntry = [
                'Name'       => $lHost['name'],
                'IPAddress'  => $lHost['ip'],
                'Firmware'   => $lHost['firmware'],
                'instanceID' => $lInstanceId,
            ];

            // Nur anbieten wenn noch keine Bridge-Instanz mit dieser IP existiert
            if ($lInstanceId === 0)
            {
                $lEntry['create'] = [
                    'moduleID'      => self::mBridgeModuleId,
                    'configuration' => [
                        'Host'   => $lHost['ip'],
                        'UseTLS' => $lHost['tls'],
                    ],
                    'name' => $lHost['name'],
                ];
            }

            $lValues[] = $lEntry;
        }

        $lForm['actions'][0]['values'] = $lValues;
        return json_encode($lForm);
    }

    // ====================================================================
    //  Subnetz-Scan
    // ====================================================================

    private function scanSubnet(): array
    {
        $lLocalIp = $this->getLocalIp();
        if ($lLocalIp === '')
        {
            $this->SendDebug('Discovery', 'Could not determine local IP', 0);
            return [];
        }

        $lParts  = explode('.', $lLocalIp);
        $lPrefix = $lParts[0] . '.' . $lParts[1] . '.' . $lParts[2] . '.';

        $this->SendDebug('Discovery', "Scanning subnet {$lPrefix}0/24", 0);

        $lHosts   = [];
        $lResults = [];

        for ($i = 1; $i <= 254; $i++)
        {
            $lHosts[] = $lPrefix . $i;
        }

        foreach (array_chunk($lHosts, self::SCAN_PARALLEL) as $lBatch)
        {
            $lBatchResults = $this->scanBatch($lBatch);
            $lResults      = array_merge($lResults, $lBatchResults);
        }

        $this->SendDebug('Discovery', 'Found ' . count($lResults) . ' SysAP(s)', 0);
        return $lResults;
    }

    private function scanBatch(array $a_Hosts): array
    {
        $lMulti   = curl_multi_init();
        $lHandles = [];

        // Je Host zwei Handles: http (Port 80) und https (Port 443)
        foreach ($a_Hosts as $lIp)
        {
            foreach (['http', 'https'] as $lScheme)
            {
                $lCh = curl_init();
                curl_setopt_array($lCh, [
                    CURLOPT_URL               => "{$lScheme}://{$lIp}/fhapi/v1/api/rest/sysap",
                    CURLOPT_RETURNTRANSFER    => true,
                    CURLOPT_TIMEOUT_MS        => self::SCAN_TIMEOUT_MS,
                    CURLOPT_CONNECTTIMEOUT_MS => self::SCAN_TIMEOUT_MS,
                    CURLOPT_FOLLOWLOCATION    => false,
                    CURLOPT_SSL_VERIFYPEER    => false,
                    CURLOPT_SSL_VERIFYHOST    => 0,
                ]);
                curl_multi_add_handle($lMulti, $lCh);
                $lHandles[] = ['ch' => $lCh, 'ip' => $lIp, 'scheme' => $lScheme];
            }
        }

        // Alle Requests parallel ausfuhren
        $lRunning = null;
        do
        {
            curl_multi_exec($lMulti, $lRunning);
            curl_multi_select($lMulti);
        }
        while ($lRunning > 0);

        // Ergebnisse auswerten - pro IP nur einen Treffer ubernehmen
        $lFound    = [];
        $lFoundIps = [];

        foreach ($lHandles as $lEntry)
        {
            $lCh     = $lEntry['ch'];
            $lIp     = $lEntry['ip'];
            $lScheme = $lEntry['scheme'];

            $lHttpCode = curl_getinfo($lCh, CURLINFO_HTTP_CODE);
            $lBody     = curl_multi_getcontent($lCh);

            if (!in_array($lIp, $lFoundIps) &&
                $lHttpCode === 200 &&
                $lBody !== false &&
                $lBody !== '')
            {
                $lSysap = json_decode($lBody, true);
                if (is_array($lSysap) && isset($lSysap['sysapName']))
                {
                    $lFound[]    = [
                        'ip'       => $lIp,
                        'name'     => $lSysap['sysapName'],
                        'firmware' => $lSysap['version'] ?? '',
                        'tls'      => ($lScheme === 'https'),
                    ];
                    $lFoundIps[] = $lIp;
                    $this->SendDebug('Discovery', "Found SysAP at {$lScheme}://{$lIp}: {$lSysap['sysapName']}", 0);
                }
            }

            curl_multi_remove_handle($lMulti, $lCh);
            curl_close($lCh);
        }

        curl_multi_close($lMulti);
        return $lFound;
    }

    // ====================================================================
    //  Hilfsfunktionen
    // ====================================================================

    private function getLocalIp(): string
    {
        // Methode 1: Hostname auflosen
        $lHostname = gethostname();
        if ($lHostname !== false)
        {
            $lIp = gethostbyname($lHostname);
            if ($lIp !== $lHostname && filter_var($lIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
            {
                return $lIp;
            }
        }

        // Methode 2: UDP-Socket-Trick (kein echtes Paket wird gesendet)
        $lSocket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($lSocket !== false)
        {
            @socket_connect($lSocket, '8.8.8.8', 53);
            $lIp = '';
            @socket_getsockname($lSocket, $lIp);
            @socket_close($lSocket);
            if (filter_var($lIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
            {
                return $lIp;
            }
        }

        return '';
    }

    private function findExistingBridgeInstance(string $a_Ip): int
    {
        $lInstances = IPS_GetInstanceListByModuleID(self::mBridgeModuleId);
        foreach ($lInstances as $lId)
        {
            if (IPS_GetProperty($lId, 'Host') === $a_Ip)
            {
                return $lId;
            }
        }
        return 0;
    }
}
