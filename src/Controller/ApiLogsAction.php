<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use Respect\Validation\Validator as v;
use S3\Log\Viewer\ActionHandler;
use S3\Log\Viewer\LogService;
use Throwable;

readonly class ApiLogsAction implements ActionHandler
{
    public function __construct(private LogService $logService)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $contentType = $request->getHeaderLine('Content-Type');
            if ($contentType !== 'application/json') {
                return new Response(415, ['Content-Type' => 'text/html'], 'Unsupported Media Type');
            }

            /** @var array{'datetime':? string, 'channel':? string, 'level':? string, 'message':? string, 'context':? array<mixed>} $data */
            $data = json_decode((string)$request->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);

            $errors = [];

            if (! v::dateTime('Y-m-d\TH:i:sP')->validate($data['datetime'] ?? null)) {
                $errors['datetime'] = 'Invalid or missing datetime';
            }

            if (! v::stringType()->length(3, 255)->validate($data['channel'] ?? null)) {
                $errors['channel'] = 'Invalid or missing channel';
            }

            $validLevels = ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];
            if (! v::stringType()->in($validLevels)->validate(strtoupper($data['level'] ?? ''))) {
                $errors['level'] = 'Invalid or missing level';
            }

            if (! v::stringType()->length(3, 255)->validate($data['message'] ?? null)) {
                $errors['message'] = 'Invalid or missing message';
            }

            if (! v::arrayType()->validate($data['context'] ?? null)) {
                $errors['context'] = 'Invalid or missing context';
            }

            if (! empty($errors)) {
                return new Response(400, ['Content-Type' => 'application/json'], json_encode(['errors' => $errors]) ?: '');
            }

            $this->logService->add($data);

            return new Response(201, body: 'Received log');
        } catch (Throwable $e) {
            return new Response(400, ['Content-Type' => 'text/html'], $e->getMessage());
        }
    }
}
