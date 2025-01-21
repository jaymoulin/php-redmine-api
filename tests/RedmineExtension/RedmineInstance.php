<?php

declare(strict_types=1);

namespace Redmine\Tests\RedmineExtension;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use SQLite3;

final class RedmineInstance
{
    /**
     * @param InstanceRegistration $tracer Required to ensure that RedmineInstance is created while Test Runner is running
     */
    public static function create(InstanceRegistration $tracer, RedmineVersion $version, string $rootPath): void
    {
        $tracer->registerInstance(new self($tracer, $version, $rootPath));
    }

    private InstanceRegistration $tracer;

    private RedmineVersion $version;

    private string $dataPath;

    private string $workingDB;

    private string $migratedDB;

    private string $backupDB;

    private string $workingFiles;

    private string $migratedFiles;

    private string $backupFiles;

    private string $redmineUrl;

    private string $apiKey;

    private function __construct(InstanceRegistration $tracer, RedmineVersion $version, string $rootPath)
    {
        $this->tracer = $tracer;
        $this->version = $version;

        $versionId = strval($version->asId());

        // Default to .docker folder
        if ($rootPath === '') {
            $rootPath = dirname(__FILE__, 3) . '/.docker';
        }

        $this->dataPath = $rootPath . '/redmine-' . $versionId . '_data/';

        $this->workingDB = 'sqlite/redmine.db';
        $this->migratedDB = 'sqlite/redmine-migrated.db';
        $this->backupDB = 'sqlite/redmine.db.bak';

        $this->workingFiles = 'files/';
        $this->migratedFiles = 'files-migrated/';
        $this->backupFiles = 'files-bak/';

        $this->redmineUrl = 'http://redmine-' . $versionId . ':3000';
        $this->apiKey = sha1($versionId . (string) time());

        $this->runHealthChecks($version);

        $this->createDatabaseBackup();
        $this->createFilesBackup();
        $this->runDatabaseMigration();
        $this->saveMigratedDatabase();
        $this->saveMigratedFiles();
    }

    public function getVersionId(): int
    {
        return $this->version->asId();
    }

    public function getVersionString(): string
    {
        return $this->version->asString();
    }

    public function getRedmineUrl(): string
    {
        return $this->redmineUrl;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    private function runHealthChecks(RedmineVersion $version): void
    {
        $ch = curl_init($this->redmineUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($data === false || $statusCode !== 200) {
            throw new InvalidArgumentException(sprintf(
                'Could not connect to Redmine server at %s, please make sure that Redmine %s has a docker service in /docker-composer.yml and is correctly configured in /tests/Behat/behat.yml.',
                $this->redmineUrl,
                $version->asString(),
            ));
        }

        if (! file_exists($this->dataPath . $this->workingDB)) {
            throw new InvalidArgumentException(sprintf(
                'Could not find database file in %s, please make sure that Redmine %s has a docker service in /docker-composer.yml and is correctly configured in /tests/Behat/behat.yml.',
                $this->dataPath . $this->workingDB,
                $version->asString(),
            ));
        }
    }

    public function reset(InstanceRegistration $tracer): void
    {
        if ($tracer !== $this->tracer) {
            throw new InvalidArgumentException();
        }

        $this->restoreFromMigratedDatabase();
        $this->restoreFromMigratedFiles();
    }

    public function shutdown(InstanceRegistration $tracer): void
    {
        if ($tracer !== $this->tracer) {
            throw new InvalidArgumentException();
        }

        $this->restoreDatabaseFromBackup();
        $this->restoreFilesFromBackup();
        $this->removeDatabaseBackups();
        $this->removeFilesBackups();

        $tracer->deregisterInstance($this);
    }

    /**
     * Allows tests to prepare the database
     */
    public function excecuteDatabaseQuery(string $query, array $options = [], ?array $params = null): void
    {
        $pdo = new PDO('sqlite:' . $this->dataPath . $this->workingDB);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare($query, $options);
        $stmt->execute($params);
    }

    private function runDatabaseMigration()
    {
        $now = new DateTimeImmutable();
        $pdo = new PDO('sqlite:' . $this->dataPath . $this->workingDB);

        // Get admin user to check sqlite connection
        $stmt = $pdo->prepare('SELECT * FROM users WHERE login = :login;');
        $stmt->execute([':login' => 'admin']);
        $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

        // Update admin user
        $stmt = $pdo->prepare('UPDATE users SET must_change_passwd = :must_change_passwd WHERE id = :id;');
        $stmt->execute([':id' => $adminUser['id'], ':must_change_passwd' => 0]);

        // Enable rest api
        $stmt = $pdo->prepare('INSERT INTO settings(name, value, updated_on) VALUES(:name, :value, :updated_on);');
        $stmt->execute([
            ':name' => 'rest_api_enabled',
            ':value' => 1,
            ':updated_on' => $now->format('Y-m-d H:i:s.u'),
        ]);

        // Create api token for admin user
        $stmt = $pdo->prepare('INSERT INTO tokens(user_id, action, value, created_on, updated_on) VALUES(:user_id, :action, :value, :created_on, :updated_on);');
        $stmt->execute([
            ':user_id' => $adminUser['id'],
            ':action' => 'api',
            ':value' => $this->apiKey,
            ':created_on' => $now->format('Y-m-d H:i:s.u'),
            ':updated_on' => $now->format('Y-m-d H:i:s.u'),
        ]);
    }

    /**
     * Create backup of working database
     */
    private function createDatabaseBackup()
    {
        $workingDB = new SQLite3($this->dataPath . $this->workingDB);

        $backupDB = new SQLite3($this->dataPath . $this->backupDB);

        $workingDB->backup($backupDB);

        $workingDB->close();
        $backupDB->close();
    }

    /**
     * Create backup of migrated database
     */
    private function saveMigratedDatabase()
    {
        $workingDB = new SQLite3($this->dataPath . $this->workingDB);

        $migratedDB = new SQLite3($this->dataPath . $this->migratedDB);

        $workingDB->backup($migratedDB);

        $workingDB->close();
        $migratedDB->close();
    }

    private function restoreFromMigratedDatabase(): void
    {
        $workingDB = new SQLite3($this->dataPath . $this->workingDB);

        $migratedDB = new SQLite3($this->dataPath . $this->migratedDB);

        $migratedDB->backup($workingDB);

        $workingDB->close();
        $migratedDB->close();
    }

    private function restoreDatabaseFromBackup(): void
    {
        $workingDB = new SQLite3($this->dataPath . $this->workingDB);

        $backupDB = new SQLite3($this->dataPath . $this->backupDB);

        $backupDB->backup($workingDB);

        $workingDB->close();
        $backupDB->close();
    }

    private function removeDatabaseBackups(): void
    {
        unlink($this->dataPath . $this->migratedDB);
        unlink($this->dataPath . $this->backupDB);
    }

    private function createFilesBackup()
    {
        // Add an empty file to avoid warnings about copying and removing content from an empty folder
        touch($this->dataPath . $this->workingFiles . 'empty');
        exec(sprintf(
            'cp -r %s %s',
            $this->dataPath . $this->workingFiles,
            $this->dataPath . rtrim($this->backupFiles, '/'),
        ));
    }

    private function saveMigratedFiles()
    {
        exec(sprintf(
            'cp -r %s %s',
            $this->dataPath . $this->workingFiles,
            $this->dataPath . rtrim($this->migratedFiles, '/'),
        ));
    }

    private function restoreFromMigratedFiles(): void
    {
        exec(sprintf(
            'rm -r %s',
            $this->dataPath . $this->workingFiles . '*',
        ));

        exec(sprintf(
            'cp -r %s %s',
            $this->dataPath . $this->migratedFiles . '*',
            $this->dataPath . rtrim($this->workingFiles, '/'),
        ));
    }

    private function restoreFilesFromBackup(): void
    {
        exec(sprintf(
            'rm -r %s',
            $this->dataPath . $this->workingFiles . '*',
        ));

        exec(sprintf(
            'cp -r %s %s',
            $this->dataPath . $this->backupFiles . '*',
            $this->dataPath . rtrim($this->workingFiles, '/'),
        ));
    }

    private function removeFilesBackups(): void
    {
        exec(sprintf(
            'rm -r %s %s',
            $this->dataPath . $this->migratedFiles,
            $this->dataPath . $this->backupFiles,
        ));

        unlink($this->dataPath . $this->workingFiles . 'empty');
    }
}
