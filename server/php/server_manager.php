<?php
/**
 * SwiftChat C Server Manager
 * Controls the C WebSocket server process
 */

class ServerManager {
    private $binaryPath;
    private $pidFile;
    
    public function __construct() {
        $this->binaryPath = __DIR__ . '/../c/compiled/chat_server';
        $this->pidFile = '/tmp/swiftchat_server.pid';
    }
    
    public function isRunning(): bool {
        if (!file_exists($this->pidFile)) return false;
        $pid = (int)file_get_contents($this->pidFile);
        return posix_kill($pid, 0);
    }
    
    public function start(): bool {
        if ($this->isRunning()) return true;
        $cmd = sprintf('%s > /dev/null 2>&1 & echo $! > %s', $this->binaryPath, $this->pidFile);
        exec($cmd);
        sleep(1);
        return $this->isRunning();
    }
    
    public function stop(): bool {
        if (!$this->isRunning()) return true;
        $pid = (int)file_get_contents($this->pidFile);
        posix_kill($pid, SIGTERM);
        sleep(1);
        if ($this->isRunning()) {
            posix_kill($pid, SIGKILL);
        }
        unlink($this->pidFile);
        return !$this->isRunning();
    }
    
    public function restart(): bool {
        $this->stop();
        return $this->start();
    }
    
    public function getStatus(): array {
        return [
            'running' => $this->isRunning(),
            'pid' => $this->isRunning() ? file_get_contents($this->pidFile) : null
        ];
    }
}
?>