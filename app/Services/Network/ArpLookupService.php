<?php

declare(strict_types=1);

namespace App\Services\Network;

use Symfony\Component\Process\Process;

class ArpLookupService
{
    /**
     * Look up MAC address for a given IPv4 target from the local OS ARP cache table.
     * Returns sanitized uppercase format (e.g. 00:1A:2B:3C:4D:5E) or null if not found.
     */
    public function lookupMac(string $ip): ?string
    {
        $ip = trim($ip);

        // Strictly validate IPv4
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return null;
        }

        $isWindows = PHP_OS_FAMILY === 'Windows';

        if ($isWindows) {
            // Windows ARP lookup: arp -a <ip>
            $command = ['arp', '-a', $ip];
        } else {
            // Linux: ip neigh show <ip> or arp -n <ip>
            $command = ['ip', 'neigh', 'show', $ip];
        }

        try {
            $process = new Process($command);
            $process->setTimeout(1.0);
            $process->run();

            if (! $process->isSuccessful() && ! $isWindows) {
                // Fallback for minimal Linux without iproute2
                $process = new Process(['arp', '-n', $ip]);
                $process->setTimeout(1.0);
                $process->run();
            }

            $output = $process->getOutput();
            return $this->parseMacFromOutput($output, $ip);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Parse MAC address from ARP output.
     */
    public function parseMacFromOutput(string $output, string $ip): ?string
    {
        if (empty($output)) {
            return null;
        }

        // Standard MAC address regex pattern (colon or hyphen separated hex pairs)
        if (preg_match('/([0-9a-fA-F]{2}[:-][0-9a-fA-F]{2}[:-][0-9a-fA-F]{2}[:-][0-9a-fA-F]{2}[:-][0-9a-fA-F]{2}[:-][0-9a-fA-F]{2})/', $output, $matches)) {
            $rawMac = $matches[1];
            // Normalize to colon-delimited uppercase
            $cleanMac = strtoupper(str_replace('-', ':', $rawMac));

            // Discard broadcast or null dummy MACs
            if ($cleanMac === 'FF:FF:FF:FF:FF:FF' || $cleanMac === '00:00:00:00:00:00') {
                return null;
            }

            return $cleanMac;
        }

        return null;
    }
}

