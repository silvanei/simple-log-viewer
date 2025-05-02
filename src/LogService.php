<?php

declare(strict_types=1);

namespace S3\Log\Viewer;

use Clue\React\Sse\BufferedChannel;
use PDO;
use PDOException;
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

        try {
            $stmt->execute();
            /** @var array{'datetime': string, 'channel': string, 'level': string, 'message': string, 'context': string}[] $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $html = '';
            foreach ($rows as $line) {
                $html .= $this->renderLog($line);
            }
            return $html;
        } catch (PDOException) {
            return '';
        }
    }

    /** @param array{'datetime': string, 'channel': string, 'level': string, 'message': string, 'context': string} $line */
    private function renderLog(array $line): string
    {
        $level = strtolower($line['level']);
        /** @var array<string|int, mixed> $context */
        $context = json_decode($line['context'], true);
        $jsonEncoded = base64_encode($line['context']);
        $jsonContent = htmlspecialchars($jsonEncoded);

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
                <span class="channel">[{$line['channel']}]</span>
                <span class="level $level">$level</span>
                <span class="message">{$line['message']}</span>
            </div>
            <div class="log-content collapsed" data-json="$jsonContent">
                <pre>{$this->formatContent($context)}</pre>
                <div class="log-actions">
                    <button class="toggle-highlight-btn" _="on click toggleHighlight(event)">
                        <svg class="toggle-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                            <path 
                                fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" 
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span>Expand All</span>
                    </button>
                    <button class="copy-json-btn" _="on click copyJSON(event)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                        </svg>
                        Copy JSON
                    </button>
                </div>
            </div>
        </div>
        HTML;
    }

    /** @param array<string|int, mixed> $context */
    private function formatContent(array $context, int $deep = 1, bool $isList = false): string
    {
        $button = <<<HTML
        <button _="on click toggle .highlight-toggle-display on next .highlight-toggle then toggle .rotate-180 on first in me">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 icon-toggle" viewBox="0 0 20 20" fill="currentColor" width="16">
                <path 
                    fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" 
                    clip-rule="evenodd"
                />
            </svg>
        </button>

        HTML;

        $tab = str_repeat(' ', $deep * 2);
        $html = '';
        foreach ($context as $key => $value) {
            $html .= $isList ? $tab : $tab . '<span class="highlight-key">' . $key . '</span>: ';
            $html .= match (true) {
                is_array($value) && array_is_list($value)
                    => $button . '<span class="highlight-toggle-display highlight-toggle">' . $this->formatContent($value, $deep + 1, true) . '</span>',
                is_array($value) => $button . '<span class="highlight-toggle-display highlight-toggle">' . $this->formatContent($value, $deep + 1) . '</span>',
                is_string($value) => '<span class="highlight-string">"' . htmlspecialchars(str_replace("\n", "\n  $tab", $value)) . '"</span>'  . "\n",
                is_numeric($value) => '<span class="highlight-number">' . $value . '</span>' . "\n",
                is_null($value) => '<span class="highlight-null">null</span>' . "\n",
                is_bool($value) => '<span class="highlight-boolean">' . ($value ? 'true' : 'false') . '</span>' . "\n",
                default => '<span class="highlight-string">"Type not mapped"</span>' . "\n",
            };
        }
        return $html;
    }
}
