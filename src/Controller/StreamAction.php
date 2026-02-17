<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Controller;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use S3\Log\Viewer\ActionHandler;
use S3\Log\Viewer\LogService;
use S3\Log\Viewer\Sse\FrankenPhpSseConnection;

readonly class StreamAction implements ActionHandler
{
    public function __construct(private LogService $logService)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getHeaderLine('Last-Event-ID');

        // Criar conexão SSE
        $connection = new FrankenPhpSseConnection($id);
        $this->logService->createChannelStream($connection, $id);

        // Iniciar o stream
        $this->startStreamLoop($connection);

        // Retornar resposta SSE
        return new Response(
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }

    private function startStreamLoop(FrankenPhpSseConnection $connection): void
    {
        // Enviar headers imediatamente
        if (! headers_sent()) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
        }

        // Timeout de 30 segundos por conexão
        $timeout = 30;
        $startTime = time();

        while ($connection->isActive() && (time() - $startTime) < $timeout) {
            // Envia keep-alive a cada 15 segundos
            if ((time() - $startTime) % 15 === 0 && (time() - $startTime) > 0) {
                $connection->send(":keep-alive\n\n");
            }

            // Verifica se conexão ainda está ativa
            if (connection_aborted()) {
                $connection->close();
                break;
            }

            // Pequena pausa para não consumir CPU
            usleep(100000); // 100ms
        }

        $connection->close();
    }
}
