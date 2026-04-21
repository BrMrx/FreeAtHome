<?php

class FreeAtHomeDiscovery extends IPSModule
{
    const mBridgeModuleId = '{9AFFB383-D756-8422-BCA0-EFD3BB1E3E29}';
    const SCAN_TIMEOUT_MS = 400;
    const SCAN_PARALLEL   = 16;

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
    //  Discovery-Einsprungpunkt
    // ====================================================================

    public function GetConfigurationForm()
    {
        $lForm   = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $lValues = [];

        $lFound = $this->scanSubnet();

        foreach ($lFound as $lHost)
        {
            $lInstanceId = $this->findExistingBridgeInstance($lHost['ip']);

            $lName     = 'free@home SysAP (' . $lHost['ip'] . ')';
            $lFirmware = '';

            // Name und Firmware aus bestehender Instanz übernehmen
            if ($lInstanceId !== 0)
            {
                $lName     = IPS_GetProperty($lInstanceId, 'SysAPName') ?: $lName;
                $lFirmware = IPS_GetProperty($lInstanceId, 'SysAPFirmware');
            }

            // create immer setzen - IPS markiert die Zeile sonst rot.
            // Bei vorhandener instanceID wird create ignoriert und die
            // bestehende Instanz verknüpft.
            $lValues[] = [
                'Name'       => $lName,
                'IPAddress'  => $lHost['ip'],
                'Firmware'   => $lFirmware,
                'instanceID' => $lInstanceId,
                'create'     => [
                    'moduleID'      => self::mBridgeModuleId,
                    'configuration' => [
                        'Host'   => $lHost['ip'],
                        'UseTLS' => $lHost['tls'],
                    ],
                    'name' => $lName,
                ],
            ];
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

        $lHosts = [];
        for ($i = 1; $i <= 254; $i++)
        {
            $lHosts[] = $lPrefix . $i;
        }

        $lResults = [];
        foreach (array_chunk($lHosts, self::SCAN_PARALLEL) as $lBatch)
        {
            $lResults = array_merge($lResults, $this->scanBatch($lBatch));
        }

        $this->SendDebug('Discovery', 'Found ' . count($lResults) . ' SysAP(s)', 0);
        return $lResults;
    }

    private function scanBatch(array $a_Hosts): array
    {
        $lMulti   = curl_multi_init();
        $lHandles = [];

        // Je Host zwei Handles: http + https
        foreach ($a_Hosts as $lIp)
        {
            foreach (['https', 'http'] as $lScheme)
            {
                $lCh = curl_init();
                curl_setopt_array($lCh, [
                    CURLOPT_URL               => "{$lScheme}://{$lIp}/",
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

        $lRunning = null;
        do
        {
            curl_multi_exec($lMulti, $lRunning);
            curl_multi_select($lMulti);
        }
        while ($lRunning > 0);

        $lFound    = [];
        $lFoundIps = [];

        foreach ($lHandles as $lEntry)
        {
            $lCh     = $lEntry['ch'];
            $lIp     = $lEntry['ip'];
            $lScheme = $lEntry['scheme'];

            $lHttpCode = curl_getinfo($lCh, CURLINFO_HTTP_CODE);
            $lBody     = curl_multi_getcontent($lCh);

            // SysAP erkennbar an HTTP 200 + "free@home" im Body
            if (!in_array($lIp, $lFoundIps) &&
                $lHttpCode === 200 &&
                $lBody !== false &&
                strpos($lBody, 'free@home') !== false)
            {
                $lFound[]    = [
                    'ip'  => $lIp,
                    'tls' => ($lScheme === 'https'),
                ];
                $lFoundIps[] = $lIp;
                $this->SendDebug('Discovery', "Found SysAP at {$lScheme}://{$lIp}", 0);
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
        // Methode 1: Hostname auflösen
        $lHostname = gethostname();
        if ($lHostname !== false)
        {
            $lIp = gethostbyname($lHostname);
            if ($lIp !== $lHostname && filter_var($lIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
            {
                return $lIp;
            }
        }

        // Methode 2: UDP-Socket-Trick
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
