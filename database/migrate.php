<?php
/**
 * SwiftChat Database Migration Runner
 */
// require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

class DatabaseMigration {
    private $db;
    private $migrations_path;
    private $migrations_table = 'migrations';
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->migrations_path = __DIR__ . '/migrations/';
    }
    
    public function run() {
        echo "🔄 Starting database migrations...\n\n";
        
        // Create migrations table if not exists
        $this->createMigrationsTable();
        
        // Get all migration files
        $files = glob($this->migrations_path . '*.sql');
        sort($files);
        
        // Get completed migrations
        $completed = $this->getCompletedMigrations();
        
        $count = 0;
        foreach ($files as $file) {
            $filename = basename($file);
            
            if (!in_array($filename, $completed)) {
                echo "⏳ Running: {$filename}\n";
                
                $sql = file_get_contents($file);
                
                // Execute migration
                if ($this->executeSQL($sql)) {
                    $this->markAsCompleted($filename);
                    echo "✅ Completed: {$filename}\n\n";
                    $count++;
                } else {
                    echo "❌ Failed: {$filename}\n";
                    echo "Error: " . $this->db->error . "\n\n";
                    return false;
                }
            }
        }
        
        echo "🎉 Migrations complete! {$count} migration(s) executed.\n";
        return true;
    }
    
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrations_table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) UNIQUE NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->query($sql);
    }
    
    private function getCompletedMigrations() {
        $result = $this->db->query("SELECT migration FROM {$this->migrations_table}");
        $migrations = [];
        while ($row = $result->fetch_assoc()) {
            $migrations[] = $row['migration'];
        }
        return $migrations;
    }
    
    private function executeSQL($sql) {
        // Split by semicolon to execute multiple statements
        $statements = array_filter(
            array_map('trim', 
                explode(';', $sql)
            )
        );
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                if (!$this->db->query($statement)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    private function markAsCompleted($filename) {
        $stmt = $this->db->prepare("INSERT INTO {$this->migrations_table} (migration) VALUES (?)");
        $stmt->bind_param('s', $filename);
        $stmt->execute();
    }
    
    public function rollback() {
        echo "🔄 Rolling back last migration...\n";
        // Implementation for rollback
    }
    
    public function status() {
        echo "📊 Migration Status:\n\n";
        $files = glob($this->migrations_path . '*.sql');
        $completed = $this->getCompletedMigrations();
        
        foreach ($files as $file) {
            $filename = basename($file);
            $status = in_array($filename, $completed) ? '✅' : '❌';
            echo "{$status} {$filename}\n";
        }
    }
}

// Run migrations from command line
if (php_sapi_name() === 'cli') {
    $migration = new DatabaseMigration();
    
    $command = $argv[1] ?? 'run';
    
    switch ($command) {
        case 'run':
            $migration->run();
            break;
        case 'rollback':
            $migration->rollback();
            break;
        case 'status':
            $migration->status();
            break;
        default:
            echo "Usage: php migrate.php [run|rollback|status]\n";
    }
}
?>