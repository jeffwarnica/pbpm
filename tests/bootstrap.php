<?php
chdir(__DIR__);

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Logging\SQLLogger;

// include_once('AutoLoader.php');
include_once ('../vendor/autoload.php');
require_once ('../vendor/apache/log4php/src/main/php/Logger.php');

Logger::configure(__DIR__ . '/log4php_config.xml');
/**
 * @var Logger
 */
$logger = Logger::getLogger("main");

// You can now use your logger
$logger->info('My logger is now ready');

// the connection configuration
$conn = array('driver' => 'pdo_sqlite', 'path' => __DIR__ . '/tmp_db/db.sqlite')
//     'memory' => 'true',
;
class azSqlLogger implements SQLLogger {

    function __construct() {
        $this->logger = Logger::getLogger("sql");
    }

    /**
     * Logs a SQL statement somewhere.
     *
     * @param string     $sql    The SQL to be executed.
     * @param array|null $params The SQL parameters.
     * @param array|null $types  The SQL parameter types.
     *
     * @return void
     */
    public function startQuery($sql, array $params = null, array $types = null) {
        $this->logger->debug($sql);
    }

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * @return void
     */
    public function stopQuery() {
    }
}


$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../src"), true /*dev mode*/);
// $config->setSQLLogger(new azSqlLogger());

$entityManager = EntityManager::create($conn, $config);