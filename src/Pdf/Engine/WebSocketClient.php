<?php

declare(strict_types=1);

namespace LifeAstroApi\Pdf\Engine;

/**
 * A minimal blocking WebSocket client (RFC 6455) for the Chrome DevTools protocol: text messages,
 * fragmentation, ping/pong and close. Plain `ws://` only (DevTools listens on localhost).
 *
 * @internal
 */
final class WebSocketClient
{
    /** @var resource */
    private $stream;

    private bool $open = true;

    /** @param resource $stream */
    private function __construct($stream)
    {
        $this->stream = $stream;
    }

    /** @throws PdfException */
    public static function connect(string $url, float $timeout = 10.0): self
    {
        $parts = parse_url($url);
        if (!\is_array($parts) || ($parts['scheme'] ?? '') !== 'ws' || !isset($parts['host'])) {
            throw new PdfException("Not a ws:// URL: {$url}");
        }
        $host = $parts['host'];
        $port = $parts['port'] ?? 80;
        $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');

        $stream = @stream_socket_client("tcp://{$host}:{$port}", $errno, $error, $timeout);
        if ($stream === false) {
            throw new PdfException("Cannot connect to {$url}: {$error}");
        }
        $client = new self($stream);
        $key = base64_encode(random_bytes(16));
        $client->write(
            "GET {$path} HTTP/1.1\r\nHost: {$host}:{$port}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n"
            . "Sec-WebSocket-Key: {$key}\r\nSec-WebSocket-Version: 13\r\n\r\n",
        );
        $deadline = microtime(true) + $timeout;
        $head = '';
        while (!str_contains($head, "\r\n\r\n")) {
            $head .= $client->read(1, $deadline);
            if (\strlen($head) > 16384) {
                throw new PdfException('WebSocket handshake: response too long');
            }
        }
        if (preg_match('#^HTTP/1\.[01] 101#', $head) !== 1) {
            throw new PdfException('WebSocket handshake failed: ' . strtok($head, "\r\n"));
        }

        return $client;
    }

    /** Send one text message. */
    public function send(string $text): void
    {
        $length = \strlen($text);
        $header = \chr(0x81);
        if ($length < 126) {
            $header .= \chr(0x80 | $length);
        } elseif ($length < 65536) {
            $header .= \chr(0x80 | 126) . pack('n', $length);
        } else {
            $header .= \chr(0x80 | 127) . pack('J', $length);
        }
        $mask = random_bytes(4);
        $masked = $text ^ substr(str_repeat($mask, intdiv($length, 4) + 1), 0, $length);
        $this->write($header . $mask . $masked);
    }

    /**
     * The next text message. Pings are answered; a close frame throws.
     *
     * @param float $deadline A `microtime(true)` value after which reading gives up.
     *
     * @throws PdfException
     */
    public function receive(float $deadline): string
    {
        $message = '';
        while (true) {
            $head = $this->read(2, $deadline);
            $fin = (\ord($head[0]) & 0x80) !== 0;
            $opcode = \ord($head[0]) & 0x0F;
            $masked = (\ord($head[1]) & 0x80) !== 0;
            $length = \ord($head[1]) & 0x7F;
            if ($length === 126) {
                $length = self::unpackInt('n', $this->read(2, $deadline));
            } elseif ($length === 127) {
                $length = self::unpackInt('J', $this->read(8, $deadline));
            }
            $mask = $masked ? $this->read(4, $deadline) : '';
            $payload = $length > 0 ? $this->read($length, $deadline) : '';
            if ($masked) {
                $payload ^= substr(str_repeat($mask, intdiv($length, 4) + 1), 0, $length);
            }

            if ($opcode === 0x8) {
                $this->open = false;
                throw new PdfException('The browser closed the DevTools connection');
            }
            if ($opcode === 0x9) {
                $this->sendControl(0xA, $payload);
                continue;
            }
            if ($opcode === 0xA) {
                continue;
            }
            $message .= $payload;
            if ($fin) {
                return $message;
            }
        }
    }

    public function close(): void
    {
        if (!$this->open) {
            return;
        }
        $this->open = false;
        try {
            $this->sendControl(0x8, pack('n', 1000));
        } catch (PdfException) {
            // Already gone.
        }
        fclose($this->stream);
    }

    private function sendControl(int $opcode, string $payload): void
    {
        $mask = random_bytes(4);
        $length = \strlen($payload);
        $this->write(\chr(0x80 | $opcode) . \chr(0x80 | $length) . $mask
            . ($payload ^ substr(str_repeat($mask, 32), 0, $length)));
    }

    private function write(string $bytes): void
    {
        $offset = 0;
        $total = \strlen($bytes);
        while ($offset < $total) {
            $n = @fwrite($this->stream, substr($bytes, $offset, 1 << 20));
            if ($n === false || $n === 0) {
                $this->open = false;
                throw new PdfException('DevTools connection lost while sending');
            }
            $offset += $n;
        }
    }

    private function read(int $length, float $deadline): string
    {
        $out = '';
        while (\strlen($out) < $length) {
            $left = $deadline - microtime(true);
            if ($left <= 0) {
                throw new PdfException('Timed out waiting for the browser');
            }
            $read = [$this->stream];
            $write = null;
            $except = null;
            $seconds = (int) floor($left);
            $ready = @stream_select($read, $write, $except, $seconds, (int) (($left - $seconds) * 1_000_000));
            if ($ready === false) {
                throw new PdfException('DevTools connection error');
            }
            if ($ready === 0) {
                continue;
            }
            $chunk = fread($this->stream, max(1, min($length - \strlen($out), 1 << 20)));
            if ($chunk === false || ($chunk === '' && feof($this->stream))) {
                $this->open = false;
                throw new PdfException('DevTools connection closed');
            }
            $out .= $chunk;
        }

        return $out;
    }

    private static function unpackInt(string $format, string $bytes): int
    {
        $v = unpack($format, $bytes);
        $n = \is_array($v) ? ($v[1] ?? 0) : 0;

        return \is_int($n) ? $n : 0;
    }
}
