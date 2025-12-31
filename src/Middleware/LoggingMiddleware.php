<?php
namespace src\Middleware;

class LoggingMiddleware implements MiddlewareInterface
{
    public function handle(array $request, callable $next)
    {
        $startTime = microtime(true);

        // Логируем начало запроса
        $this->logRequest($request);

        // Пропускаем запрос дальше
        $response = $next($request);

        // Логируем результат
        $duration = microtime(true) - $startTime;
        $this->logResponse($request, $duration);

        return $response;
    }

    private function logRequest(array $request): void
    {
        $logEntry = sprintf(
            "[%s] %s %s IP: %s User-Agent: %s\n",
            date('Y-m-d H:i:s'),
            $request['method'],
            $request['uri'],
            $request['ip'],
            $request['user_agent'] ?? 'Unknown'
        );

        $this->writeLog('access.log', $logEntry);
    }

    private function logResponse(array $request, float $duration): void
    {
        $logEntry = sprintf(
            "[%s] %s %s Duration: %.3fs Memory: %s\n",
            date('Y-m-d H:i:s'),
            $request['method'],
            $request['uri'],
            $duration,
            $this->formatMemory(memory_get_peak_usage(true))
        );

        $this->writeLog('performance.log', $logEntry);
    }

    private function writeLog(string $filename, string $content): void
    {
        $logDir = __DIR__ . '/../../logs/';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logDir . $filename, $content, FILE_APPEND);
    }

    private function formatMemory(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}