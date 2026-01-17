<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Storage;

use JsonException;
use PDO;
use PDOException;
use S3\Log\Viewer\Dto\LogEntry;
use S3\Log\Viewer\Dto\LogEntryView;

use function is_array;
use function is_int;
use function is_float;

final readonly class LogStorageSQLite implements LogStorage
{
    public function __construct(private PDO $storage)
    {
        $this->storage->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->storage->exec('PRAGMA foreign_keys = ON');
        $this->storage->exec(<<<SQL
            CREATE VIRTUAL TABLE IF NOT EXISTS logs USING fts5(
                datetime,
                channel,
                level,
                message,
                context,
                extra,
                tokenize='unicode61 remove_diacritics 2'
            );
            SQL
        );
    }

    public function add(LogEntry $log): void
    {
        $stmt = $this->storage->prepare(<<<QUERY
            INSERT INTO logs (datetime, channel, level, message, context, extra)
            VALUES (:datetime, :channel, :level, :message, :context, :extra)
            QUERY
        );
        $stmt->bindValue(':datetime', $log->datetime);
        $stmt->bindValue(':channel', $log->channel);
        $stmt->bindValue(':level', $log->level);
        $stmt->bindValue(':message', $log->message);
        $stmt->bindValue(':context', json_encode($this->normalizeValueAsText($log->context), JSON_UNESCAPED_UNICODE));
        $stmt->bindValue(':extra', json_encode($this->normalizeValueAsText($log->extra), JSON_UNESCAPED_UNICODE));
        $stmt->execute();
    }

    /** @return LogEntryView[] */
    public function search(string $filter): array
    {
        if ($filter) {
            $stmt = $this->storage->prepare(<<<SQL
                SELECT
                    highlight(logs, 0, '⟦', '⟧') AS datetime,
                    highlight(logs, 1, '⟦', '⟧') AS channel,
                    highlight(logs, 2, '⟦', '⟧') AS level,
                    highlight(logs, 3, '⟦', '⟧') AS message,
                    highlight(logs, 4, '⟦', '⟧') AS context,
                    highlight(logs, 5, '⟦', '⟧') AS extra
                FROM logs
                WHERE logs MATCH :q
                ORDER BY bm25(logs), logs.datetime DESC
                LIMIT 100
                SQL
            );
            $stmt->bindValue(':q', $filter, PDO::PARAM_STR);
        } else {
            $stmt = $this->storage->prepare(<<<SQL
                SELECT datetime, channel, level, message, context, extra
                FROM logs
                ORDER BY logs.datetime DESC
                LIMIT 100
                SQL
            );
        }

        try {
            $stmt->execute();
            $result = $stmt->fetchAll(
                PDO::FETCH_FUNC,
                fn (string $datetime, string $channel, string $level, string $message, string $context, string $extra): LogEntryView => new LogEntryView(
                    $datetime,
                    $channel,
                    $level,
                    $message,
                    $this->jsonDecode($context),
                    $this->jsonDecode($extra)
                )
            );
            /** @var LogEntryView[] $result */
            return $result;
        } catch (PDOException) {
            return [];
        }
    }

    public function clear(): void
    {
        $this->storage->exec('DELETE FROM logs');
    }

    /** @return array<int|string, mixed> */
    private function jsonDecode(string $json): array
    {
        try {
            /** @var array<int|string, mixed> $data */
            $data = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
        return $data;
    }

    private function normalizeValueAsText(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map($this->normalizeValueAsText(...), $data);
        }

        return match (true) {
            $data === null => 'null',
            $data === true => 'true',
            $data === false => 'false',
            is_int($data), is_float($data) => (string) $data,
            is_string($data) => $data,
            default => $data,
        };
    }
}
