<?php

namespace App\Services\Hr;

use Carbon\Carbon;
use RuntimeException;

/**
 * Minimal ZKTeco UDP protocol client.
 *
 * Implements the ZK binary protocol (UDP, port 4370) documented by open-source
 * reverse-engineering (pyzk, roflsunrise/zkteco). Supports CMD_CONNECT,
 * CMD_GET_ATTLOG with large-data chunking, and CMD_EXIT.
 *
 * Attendance record format (40 bytes, little-endian):
 *   Bytes  0-3  : user_id   (uint32)
 *   Bytes  4-5  : reserved
 *   Bytes  6-9  : timestamp (ZK-encoded uint32, see decodeZkTime)
 *   Byte  10-11 : reserved
 *   Byte  12    : status    (0=in, 1=out, 4=ot_in, 5=ot_out)
 *   Byte  13    : punch     (0=finger, 15=password, 255=auto)
 *   Bytes 14-39 : reserved
 */
class ZkSocketService
{
    private const CMD_CONNECT      = 1000;
    private const CMD_EXIT         = 1001;
    private const CMD_GET_ATTLOG   = 13;
    private const CMD_ACK_OK       = 2000;
    private const CMD_PREPARE_DATA = 1500;
    private const CMD_DATA         = 1501;
    private const CMD_FREE_DATA    = 1502;
    private const RECORD_SIZE      = 40;
    private const UDP_BUFFER       = 65536;

    private mixed $socket = null;
    private int $sessionId = 0;
    private int $replyId   = 65534;

    public function __construct(
        private readonly string $ip,
        private readonly int $port = 4370,
        private readonly int $timeout = 10,
    ) {}

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Open UDP socket and authenticate with device.
     */
    public function connect(): void
    {
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if (! $this->socket) {
            throw new RuntimeException('Cannot create UDP socket: ' . socket_strerror(socket_last_error()));
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $this->timeout, 'usec' => 0]);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $this->timeout, 'usec' => 0]);

        $response = $this->sendCommand(self::CMD_CONNECT);
        $header   = $this->parseHeader($response);

        if ($header['cmd'] !== self::CMD_ACK_OK) {
            throw new RuntimeException("ZK device at {$this->ip} rejected connection (response cmd={$header['cmd']}).");
        }

        $this->sessionId = $header['session_id'];
    }

    /**
     * Fetch all attendance logs from the device.
     *
     * @return array<array{user_pin: string, punched_at: Carbon, status: int, punch_type: int}>
     */
    public function getAttendanceLogs(): array
    {
        $raw    = $this->fetchAllRecords(self::CMD_GET_ATTLOG);
        return $this->parseAttendanceLogs($raw);
    }

    /**
     * Send CMD_EXIT and close socket.
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            try {
                $this->sendCommand(self::CMD_EXIT);
            } catch (\Throwable) {
            }
        }
        $this->close();
    }

    // ─── Protocol internals ──────────────────────────────────────────────────

    private function fetchAllRecords(int $cmd): string
    {
        $response = $this->sendCommand($cmd);
        $header   = $this->parseHeader($response);

        if ($header['cmd'] === self::CMD_PREPARE_DATA) {
            // Large dataset: device announces total size first, then sends CMD_DATA chunks
            $totalSize = unpack('V', substr($response, 8, 4))[1];
            return $this->receiveChunked($totalSize);
        }

        if ($header['cmd'] === self::CMD_DATA) {
            return substr($response, 8);
        }

        // CMD_ACK_OK with inline data (small response) or empty
        return strlen($response) > 8 ? substr($response, 8) : '';
    }

    private function receiveChunked(int $expectedSize): string
    {
        $buffer = '';

        while (strlen($buffer) < $expectedSize) {
            $raw    = $this->socketReceive();
            $header = $this->parseHeader($raw);

            if ($header['cmd'] === self::CMD_DATA) {
                $buffer .= substr($raw, 8);
            } elseif ($header['cmd'] === self::CMD_ACK_OK) {
                break;
            }
        }

        // Release device data buffer
        try {
            $this->sendCommand(self::CMD_FREE_DATA);
        } catch (\Throwable) {
        }

        return $buffer;
    }

    private function parseAttendanceLogs(string $raw): array
    {
        $logs  = [];
        $count = intdiv(strlen($raw), self::RECORD_SIZE);

        for ($i = 0; $i < $count; $i++) {
            $offset = $i * self::RECORD_SIZE;

            $uid       = unpack('V', substr($raw, $offset,      4))[1];
            $timestamp = unpack('V', substr($raw, $offset + 6,  4))[1];
            $status    = ord($raw[$offset + 12]);
            $punch     = ord($raw[$offset + 13]);

            if ($uid === 0 || $timestamp === 0) {
                continue;
            }

            $logs[] = [
                'user_pin'   => (string) $uid,
                'punched_at' => $this->decodeZkTime($timestamp),
                'status'     => $status,
                'punch_type' => $punch,
            ];
        }

        return $logs;
    }

    /**
     * ZKTeco timestamp encoding (documented in pyzk):
     *   t = ((year-2000)*12*31 + (month-1)*31 + (day-1)) * 86400 + hour*3600 + min*60 + sec
     */
    private function decodeZkTime(int $t): Carbon
    {
        $second = $t % 60;     $t = intdiv($t, 60);
        $minute = $t % 60;     $t = intdiv($t, 60);
        $hour   = $t % 24;     $t = intdiv($t, 24);
        $day    = $t % 31 + 1; $t = intdiv($t, 31);
        $month  = $t % 12 + 1; $t = intdiv($t, 12);
        $year   = $t + 2000;

        return Carbon::create($year, $month, $day, $hour, $minute, $second);
    }

    // ─── Socket I/O ──────────────────────────────────────────────────────────

    private function sendCommand(int $cmd, string $data = ''): string
    {
        $this->replyId = ($this->replyId + 1) & 0xFFFF;

        // Packet: [cmd 2B][checksum 2B][session_id 2B][reply_id 2B][data...]
        $packet    = pack('vvvv', $cmd, 0, $this->sessionId, $this->replyId) . $data;
        $checksum  = $this->checksum($packet);
        $packet[2] = chr($checksum & 0xFF);
        $packet[3] = chr(($checksum >> 8) & 0xFF);

        $sent = socket_sendto($this->socket, $packet, strlen($packet), 0, $this->ip, $this->port);

        if ($sent === false) {
            throw new RuntimeException('ZK socket send error: ' . socket_strerror(socket_last_error($this->socket)));
        }

        return $this->socketReceive();
    }

    private function socketReceive(): string
    {
        $buf  = '';
        $from = '';
        $port = 0;
        $n    = socket_recvfrom($this->socket, $buf, self::UDP_BUFFER, 0, $from, $port);

        if ($n === false || $n === 0) {
            throw new RuntimeException("ZK device did not respond (ip={$this->ip}, timeout={$this->timeout}s). Check IP, port, and network.");
        }

        return $buf;
    }

    private function parseHeader(string $buf): array
    {
        if (strlen($buf) < 8) {
            return ['cmd' => 0, 'checksum' => 0, 'session_id' => 0, 'reply_id' => 0];
        }

        $parts = unpack('vcmd/vchecksum/vsession_id/vreply_id', substr($buf, 0, 8));

        return [
            'cmd'        => $parts['cmd'],
            'checksum'   => $parts['checksum'],
            'session_id' => $parts['session_id'],
            'reply_id'   => $parts['reply_id'],
        ];
    }

    /**
     * ZK one's-complement Internet checksum over uint16 words.
     */
    private function checksum(string $buf): int
    {
        if (strlen($buf) % 2 !== 0) {
            $buf .= "\x00";
        }

        $sum = 0;
        for ($i = 0; $i < strlen($buf); $i += 2) {
            $sum += unpack('v', $buf[$i] . $buf[$i + 1])[1];
        }

        while ($sum >> 16) {
            $sum = ($sum & 0xFFFF) + ($sum >> 16);
        }

        return (~$sum) & 0xFFFF;
    }

    private function close(): void
    {
        if ($this->socket) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }
}
