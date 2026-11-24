<?php

declare(strict_types=1);

namespace App\Services\Network;

use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\Subnet;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class PingService
{
    /**
     * Perform a secure, single ICMP echo check against a target IP address.
     */
    public function ping(string $ipAddress, ?User $user = null, bool $bypassRateLimit = false): PingResult
    {
        $ip = trim($ipAddress);

        // 1. Strict IP format validation (IPv4 or IPv6)
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return PingResult::error("Invalid IP address syntax: '{$ip}'.");
        }

        // 2. Validate target IP belongs to an authorized, managed subnet
        try {
            $ipVO = IpAddressVO::parse($ip);
        } catch (\Throwable $e) {
            return PingResult::error("Failed to parse IP address: {$e->getMessage()}");
        }

        $isAuthorized = Subnet::where('subnet_type', 'assignable')
            ->get()
            ->contains(fn (Subnet $s) => $s->toIpPrefix()->containsIp($ipVO));

        if (! $isAuthorized) {
            return PingResult::error("Access denied: {$ip} is outside authorized, managed subnets.");
        }

        // 3. User authentication & authorization check
        if ($user && ! $user->canOperate()) {
            return PingResult::error("Unauthorized: insufficient permissions to ping IP addresses.");
        }

        // 4. Rate limiting per user / IP session (30 pings per minute, skippable by authorized background scan engine)
        if (! $bypassRateLimit) {
            $rateLimitKey = 'ipam_ping:' . ($user?->id ?? request()->ip() ?? 'console');
            if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
                $seconds = RateLimiter::availableIn($rateLimitKey);
                return PingResult::error("Rate limit reached. Please wait {$seconds}s before pinging again.");
            }
            RateLimiter::hit($rateLimitKey, 60);
        }

        // 5. Construct secure, parameterized OS-specific command
        $isWindows = PHP_OS_FAMILY === 'Windows';
        $isIpv6 = str_contains($ip, ':');

        if ($isWindows) {
            // Windows ping: -n 1 (1 echo request), -w 1000 (1000ms timeout)
            $command = ['ping', '-n', '1', '-w', '1000', $ip];
        } else {
            // Linux / macOS ping: -c 1 (count 1), -W 1 (1 sec timeout)
            $binary = $isIpv6 ? 'ping6' : 'ping';
            $command = [$binary, '-c', '1', '-W', '1', $ip];
        }

        $process = new Process($command);
        $process->setTimeout(2.5);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return PingResult::noReply('Timed out after 2.5s');
        } catch (\Throwable $e) {
            return PingResult::error("Execution failed: {$e->getMessage()}");
        }

        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();
        $fullOutput = trim($output . ' ' . $errorOutput);

        return $this->parsePingOutput($fullOutput, $process->getExitCode(), $isWindows);
    }

    /**
     * Parse OS-specific ping output to determine Reachable, No Reply, or Error.
     */
    public function parsePingOutput(string $output, ?int $exitCode, bool $isWindows): PingResult
    {
        // Check for ICMP execution / driver failure on server
        if (
            str_contains($output, 'Unable to contact IP driver') ||
            str_contains($output, 'General failure') ||
            str_contains($output, 'Operation not permitted') ||
            str_contains($output, 'Permission denied') ||
            str_contains($output, 'command not found')
        ) {
            return PingResult::error('ICMP execution is unavailable on server environment.');
        }

        if ($isWindows) {
            // Unreachable or timed out on Windows
            if (
                str_contains($output, 'Request timed out') ||
                str_contains($output, 'Destination host unreachable') ||
                str_contains($output, '100% loss') ||
                str_contains($output, 'Lost = 1')
            ) {
                return PingResult::noReply();
            }

            // Reachable reply
            if (preg_match('/time[=<]\s*([0-9]+(?:\.[0-9]+)?)\s*ms/i', $output, $matches)) {
                $rawMatch = $matches[0];
                $latency = str_contains($rawMatch, '<') ? '<1ms' : $matches[1] . 'ms';
                return PingResult::reachable($latency);
            }

            if ($exitCode === 0 && (str_contains($output, 'Reply from') || str_contains($output, 'bytes='))) {
                return PingResult::reachable('<1ms');
            }

            return PingResult::noReply();
        }

        // Linux / macOS ping output parsing
        if (
            str_contains($output, '100% packet loss') ||
            str_contains($output, '0 packets received') ||
            str_contains($output, 'Destination Host Unreachable') ||
            str_contains($output, '0 received')
        ) {
            return PingResult::noReply();
        }

        if (preg_match('/time=([0-9]+(?:\.[0-9]+)?)\s*ms/i', $output, $matches)) {
            return PingResult::reachable($matches[1] . 'ms');
        }

        if ($exitCode === 0 && (str_contains($output, '1 received') || str_contains($output, '0% packet loss'))) {
            return PingResult::reachable('<1ms');
        }

        return $exitCode === 0 ? PingResult::reachable('<1ms') : PingResult::noReply();
    }
}
