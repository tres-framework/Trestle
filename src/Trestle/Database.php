<?php
/*                      ______                   __   __
                       /_  __/_____ ___   _____ / /_ / /___
                        / /  / ___// _ \ / ___// __// // _ \
                       / /  / /   /  __/(__  )/ /_ / //  __/
                      /_/  /_/    \___//____/ \__//_/ \___/

                            PHP PDO database wrapper
                  Supporting multiple connections and drivers.
                   https://github.com/tres-framework/Trestle
         ______________________________________________________________
        |_  _  ______  _  ______  _   _____  _  ______  _  ______  _  _|
         / / \ \    / / \ \    / / \ \    / / \ \    / / \ \    / / \ \
        / /   \ \  / /   \ \  / /   \ \  / /   \ \  / /   \ \  / /   \ \
       / /     \ \/ /     \ \/ /     \ \/ /     \ \/ /     \ \/ /     \ \
      / /       |  |       |  |       |  |       |  |       |  |       \ \
     / /       / /\ \     / /\ \     / /\ \     / /\ \     / /\ \       \ \
    / /       / /  \ \   / /  \ \   / /  \ \   / /  \ \   / /  \ \       \ \
 __/ /_______/ /____\ \_/ /____\ \_/ /____\ \_/ /____\ \_/ /____\ \_______\ \__
|______________________________________________________________________________|
*/
namespace Trestle {

    use Exception;
    use PDO;
    use PDOException;
    use ReflectionMethod;
    use Trestle\Build;
    use Trestle\Config;
    use Trestle\TrestleException;
    use Trestle\DatabaseException;
    use Trestle\Log;
    use Trestle\Process;

    /**
     *-------------------------------------------------------------------------
     * Database
     *-------------------------------------------------------------------------
     *
     * This is where you start a new database connection. It's where the
     * method chain begins.
     *
     */
    class Database {

        /**
         * Holds database configuration.
         *
         * @var array
         */
        protected $_config = [];

        /**
         * Holds database connection.
         *
         * @var protected
         */
        protected $_connection = []; // TODO: ??; Improve DocBlock.

        /**
         * Establishes the link to the database.
         *
         * @param  string $connection The connection name from the config.
         */
        public function __construct($connection = null) {
            try {
                $this->_config = Config::get();

                if(empty($this->_config)){
                    throw new DatabaseException('Database configuration not set.');
                }

                if(!isset($connection)) {
                    $connection = $this->_config['default'];
                }

                if(isset($this->_config['connections'][$connection])) {
                    $this->_config = $this->_config['connections'][$connection];
                } else {
                    throw new DatabaseException('Unable to locate "' . $connection . '" config.');
                }
                
                Log::init(Config::get('logs'));
                
                Log::register('database');
                Log::register('request');
                Log::register('query');
                
                $this->_process = new Process();
                $this->_process->connection($this->_config);
            } catch(Exception $e) {
                throw new TrestleException($e->getMessage());
            }
        }

        /**
         * __call()
         *
         * @param  string $method The method name.
         * @param  mixed  $args   The arguments.
         * @return object
         */
        public function __call($method, $args) { // TODO: Improve DocBlock.
            try {
                $method = strtolower($method);
                if(in_array($method, ['query', 'create', 'read', 'update', 'delete', 'raw'])) {

                    Log::start('total');

                    $driver = "Trestle\blueprints\\{$this->_config['driver']}";

                    $reflection = new ReflectionMethod($driver, $method);

                    return $reflection->invokeArgs(new $driver($this->_process), $args);

                } else {
                    throw new DatabaseException('Trestle was unable to recognize your database call.');
                }
            } catch(Exception $e) {
                throw new TrestleException($e);
            }
        }

    }

}
