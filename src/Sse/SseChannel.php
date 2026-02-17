<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Sse;

/**
 * SSE Channel for broadcasting messages to multiple connections
 */
class SseChannel
{
    /** @var array<string, SseConnectionInterface> */
    private array $connections = [];

    /** @var array<int, string> */
    private array $buffer = [];

    private int $bufferSize = 100;

    public function connect(SseConnectionInterface $connection): void
    {
        $this->connections[$connection->getId()] = $connection;
    }

    public function disconnect(SseConnectionInterface $connection): void
    {
        unset($this->connections[$connection->getId()]);
        $connection->close();
    }

    public function writeMessage(string $message, ?string $event = null, ?string $id = null): void
    {
        $sseMessage = $this->formatMessage($message, $event, $id);

        // Store in buffer for new connections
        $this->buffer[] = $sseMessage;
        if (count($this->buffer) > $this->bufferSize) {
            array_shift($this->buffer);
        }

        // Send to all active connections
        foreach ($this->connections as $key => $connection) {
            if (! $connection->isActive()) {
                $this->disconnect($connection);
                continue;
            }

            try {
                $connection->send($sseMessage);
            } catch (\Throwable $e) {
                $this->disconnect($connection);
            }
        }
    }

    /**
     * Send buffered messages to a new connection
     */
    public function replayBuffer(SseConnectionInterface $connection): void
    {
        foreach ($this->buffer as $message) {
            if (! $connection->isActive()) {
                break;
            }

            try {
                $connection->send($message);
            } catch (\Throwable $e) {
                $connection->close();
                break;
            }
        }
    }

    /**
     * @return array<string, SseConnectionInterface>
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    private function formatMessage(string $data, ?string $event = null, ?string $id = null): string
    {
        $lines = [];

        if ($event !== null) {
            $lines[] = "event: {$event}";
        }

        if ($id !== null) {
            $lines[] = "id: {$id}";
        }

        // Split data by newlines and prefix each line
        foreach (explode("\n", $data) as $line) {
            $lines[] = "data: {$line}";
        }

        $lines[] = ''; // Empty line to end the event
        $lines[] = ''; // Extra empty line

        return implode("\n", $lines);
    }
}
