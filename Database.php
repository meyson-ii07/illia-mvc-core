<?php


namespace app\core;

use app\migrations\m0001_initial;

class Database
{
    private \PDO $pdo;

    public function __construct($config)
    {
        $dsn = $config['dsn'] ?? '';
        $user = $config['user'] ?? '';
        $password = $config['password'] ?? '';
        try {
            $this->pdo = new \PDO($dsn, $user, $password);
        } catch (\Exception $e) {
            die("Could not connect to the database: " . $e->getMessage().PHP_EOL);
        }
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     *  Applies migrations
     */
    public function applyMigrations()
    {
        $this->createMigrationsTable();
        $appliedMigrations = $this->getAppliedMigrations();

        $newMigrations = [];
        $files = scandir(Application::$ROOT_DIR.'/migrations');
        $migrationsToApply = array_diff($files, $appliedMigrations);
        foreach ($migrationsToApply as $migration) {
            if ($migration === '.'  || $migration === '..') {
                continue;
            }

            require Application::$ROOT_DIR.'/migrations/'.$migration;

            $className = pathinfo($migration, PATHINFO_FILENAME);
            $instance = new $className();
            echo "Applying migration {$migration}".PHP_EOL;
            $this->pdo->exec($instance->up());
            echo "Migration {$migration} applied".PHP_EOL;
            $newMigrations[] = $migration;
        }
        if (!empty($newMigrations)) {
            $this->saveMigrations($newMigrations);
        } else {
            echo "All migrations are applied".PHP_EOL;
        }

    }

    /**
     *  Creates migration table
     */
    public function createMigrationsTable()
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255),
        created_at TIMESTAMP  DEFAULT CURRENT_TIMESTAMP 
        ) ENGINE=INNODB;");
    }

    /**
     * Returns array of applied migrations
     * @return array
     */
    public function getAppliedMigrations()
    {
        $statement = $this->pdo->prepare("SELECT migration FROM migrations");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Saves migrations that have being applied
     * @param $migrations
     */
    public function saveMigrations($migrations)
    {
        $values = implode(array_map(fn($m) => "('$m')", $migrations));
        $statement = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES {$values}");
        $statement->execute();
    }

    /**
     * Prepers SQL query
     * @param $quarry
     * @return false|\PDOStatement
     */
    public function prepare($quarry)
    {
        return $this->pdo->prepare($quarry);
    }

    /**
     * Prepers SQL query
     * @param $quarry
     * @return false|\PDOStatement
     */
    public function prepareQuarry($quarry)
    {
        return $this->pdo->query($quarry);
    }

    /**
     * Executes givven quarry
     * @param $quarry
     */
    public function executeQuarry($quarry)
    {
        $this->pdo->exec($quarry);
    }

}