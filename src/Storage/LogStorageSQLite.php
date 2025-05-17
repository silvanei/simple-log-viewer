<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Storage;

use PDO;
use PDOException;

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
                extra
            );
            SQL
        );
    }

    /** @param array{datetime: string, channel: string, level: string, message: string, context: array<string|int, mixed>, extra?: array<string|int, mixed>} $log */
    public function add(array $log): void
    {
        $stmt = $this->storage->prepare(<<<QUERY
            INSERT INTO logs (datetime, channel, level, message, context, extra) 
            VALUES (:datetime, :channel, :level, :message, :context, :extra)
            QUERY
        );
        $stmt->bindValue(':datetime', $log['datetime']);
        $stmt->bindValue(':channel', $log['channel']);
        $stmt->bindValue(':level', $log['level']);
        $stmt->bindValue(':message', $log['message']);
        $stmt->bindValue(':context', json_encode($log['context'], JSON_UNESCAPED_UNICODE));
        $stmt->bindValue(':extra', json_encode($log['extra'] ?? [], JSON_UNESCAPED_UNICODE));
        $stmt->execute();
    }

    public function search(string $filter): array
    {
        if ($filter) {
            $stmt = $this->storage->prepare(<<<SQL
                SELECT datetime, channel, level, message, context, extra
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
            /** @var array{'datetime': string, 'channel': string, 'level': string, 'message': string, 'context': string, 'extra': string}[] $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rows;
        } catch (PDOException) {
            return [];
        }
    }

    public function clear(): void
    {
        $this->storage->exec('DELETE FROM logs');
    }
}
