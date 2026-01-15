<?php
namespace src\Middleware;

class LoggingMiddleware implements MiddlewareInterface
{
    private array $config;
    private bool $enabled;
    private array $ignorePaths;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'ignore_paths' => ['/health', '/ping', '/favicon.ico'],
            'log_ips' => true,
            'log_user_agents' => false,
            'log_performance' => true,
        ], $config);

        $this->enabled = $this->config['enabled'];
        $this->ignorePaths = $this->config['ignore_paths'];
    }

    public function handle(array $request, callable $next)
    {
        if (!$this->enabled) {
            return $next($request);
        }

        // Проверяем, не игнорируется ли путь
        $uri = $request['uri'] ?? '/';
        foreach ($this->ignorePaths as $ignorePath) {
            if (strpos($uri, $ignorePath) === 0) {
                return $next($request);
            }
        }

        $startTime = microtime(true);

        // Логируем начало запроса
        $this->logRequest($request);

        // Пропускаем запрос дальше
        $response = $next($request);

        // Логируем результат
        $duration = microtime(true) - $startTime;
        if ($this->config['log_performance']) {
            $this->logPerformance($request, $duration);
        }

        return $response;
    }

    private function logRequest(array $request): void
    {
        $logEntry = sprintf(
            "[%s] %s %s",
            date('Y-m-d H:i:s'),
            $request['method'],
            $request['uri']
        );

        if ($this->config['log_ips']) {
            $logEntry .= " IP: " . ($request['ip'] ?? 'unknown');
        }

        if ($this->config['log_user_agents'] && isset($request['user_agent'])) {
            $logEntry .= " User-Agent: " . $request['user_agent'];
        }

        $logEntry .= "\n";

        $this->writeLog('access.log', $logEntry);
    }

    private function logPerformance(array $request, float $duration): void
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