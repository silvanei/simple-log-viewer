<?php

declare(strict_types=1);

namespace S3\Log\Viewer;

use Clue\React\Sse\BufferedChannel;
use JsonException;
use PDO;
use React\EventLoop\Loop;
use React\Stream\ThroughStream;

readonly class LogService
{
    public function __construct(
        private PDO $storage,
        private BufferedChannel $channel = new BufferedChannel()
    ) {
        $this->storage->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->storage->exec('PRAGMA foreign_keys = ON');
        $this->storage->exec(<<<SQL
            CREATE VIRTUAL TABLE IF NOT EXISTS logs USING fts5(
                datetime,
                channel,
                level,
                message,
                context
            );
            SQL
        );
    }

    public function channel(ThroughStream $stream, string $id): void
    {
        Loop::get()->futureTick(fn() => $this->channel->connect($stream, $id));
        $stream->on('close', fn() => $this->channel->disconnect($stream));
    }

    /** @param array{'datetime': string, 'channel': string, 'level': string, 'message': string, 'context': string} $log */
    public function add(array $log): void
    {
        $stmt = $this->storage->prepare(<<<QUERY
            INSERT INTO logs (datetime, channel, level, message, context) 
            VALUES (:datetime, :channel, :level, :message, :context)
            QUERY
        );
        $stmt->bindValue(':datetime', $log['datetime']);
        $stmt->bindValue(':channel', $log['channel']);
        $stmt->bindValue(':level', $log['level']);
        $stmt->bindValue(':message', $log['message']);
        $stmt->bindValue(':context', json_encode($log['context'], JSON_UNESCAPED_UNICODE));
        $stmt->execute();

        $this->channel->writeMessage('Received new log');
    }

    public function search(string $filter): string
    {
        if ($filter) {
            $stmt = $this->storage->prepare(<<<SQL
                SELECT datetime, channel, level, message, context
                FROM logs
                WHERE logs MATCH :q
                ORDER BY bm25(logs), logs.datetime DESC
                LIMIT 100
                SQL
            );
            $stmt->bindValue(':q', $filter, PDO::PARAM_STR);
        } else {
            $stmt = $this->storage->prepare(<<<SQL
                SELECT datetime, channel, level, message, context
                FROM logs
                ORDER BY logs.datetime DESC
                LIMIT 100
                SQL
            );
        }

        $stmt->execute();
        /** @var array{'datetime': string, 'channel': string, 'level': string, 'message': string, 'context': string}[] $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '';
        foreach ($rows as $line) {
            $html .= $this->renderLog($line);
        }
        return $html;
    }

    /** @param array{'datetime': string, 'channel': string, 'level': string, 'message': string, 'context': string} $line */
    private function renderLog(array $line): string
    {
        $level = strtolower($line['level']);
        /** @var array<string|int, mixed> $context */
        $context = json_decode($line['context'], true);

        return <<<HTML
        <div class="log-entry">
            <div class="log-header" _="on click toggle .collapsed on next .log-content then toggle .rotate-180 on first in me">
                <button>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" width="16">
                        <path 
                            fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" 
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
                <span class="datetime">{$line['datetime']}</span>
                <span class="channel">{$line['channel']}</span>
                <span class="level $level">$level</span>
                <span class="message">{$line['message']}</span>
            </div>
            <pre class="log-content collapsed">{$this->highlightJson($context)}</pre>
        </div>
        HTML;
    }

    /** @param array<string|int, mixed> $decoded */
    private function highlightJson(array $decoded): string
    {
        try {
            $formattedJson = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return '<span class="json-error">Error encoding JSON: ' . htmlspecialchars($e->getMessage()) . '</span>';
        }

        $patterns = [
            '/^( *)"(.*?)":/m' => fn(array $m) => $m[1] . '<span class="json-key">"' . htmlspecialchars($m[2]) . '"</span>:',
            '/: "((.*?|[^"\\\\]).*)"/m' => fn(array $m) => ': <span class="json-string">"' . htmlspecialchars($m[1]) . '"</span>',
            '/(:\s*)(-?\d+(\.\d+)?([eE][+-]?\d+)?)/m' => fn(array $m) => $m[1] . '<span class="json-number">' . $m[2] . '</span>',
            '/(:\s*)(true|false)/m' => fn(array $m) => $m[1] . '<span class="json-boolean">' . $m[2] . '</span>',
            '/(:\s*)null/m' => fn(array $m) => $m[1] . '<span class="json-null">null</span>',
        ];

        foreach ($patterns as $pattern => $callback) {
            $formattedJson = preg_replace_callback($pattern, $callback, $formattedJson ?? '');
        }

        return $formattedJson ?? '';
    }
}
