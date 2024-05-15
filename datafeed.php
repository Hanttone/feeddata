<?php 

//Config file
include 'config.php';

//Error logging and PHP INI setup and activation
error_reporting(E_ALL);
ini_set('ignore_repeated_errors', TRUE);
ini_set('display_errors', TRUE);
ini_set('log_errors', TRUE);
ini_set('error_log', ERROR_LOG_PATH);

//Database connection handling
class DatabaseConnection {
    private $pdo;

    public function __construct($dbType, $dbPath, $userName = NULL, $password = NULL) {
        try {
            $dsn = $dbType . ':' . $dbPath;

            if ($userName !== NULL && $password !== NULL) {
                $dsn .= '?user=' . $username . '&password=' . $password;
            }

            $this->pdo = new PDO($dsn);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
            die();
        }
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function close() {
        $this->pdo = null;
    }
}

//Data Parsing
class DataParser {
    private $xml;
    private $fileExtension;

    // Add a test if it is an XML file or not
    public function __construct($filePath) {
        if (file_exists($filePath)) {
            $this->fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

            switch ($this->fileExtension) {
                case 'xml':
                    $this->xml = simplexml_load_file($filePath);
                    break;
                case 'json':
                    $json = file_get_contents($filePath);
                    $this->data = json_decode($json, true);
                    break;
                case 'csv':
                    // Implement CSV parsing logic here
                    break;
                // Add cases for other file types as needed
                default:
                    throw new Exception("Unsupported file type: $this->fileExtension");
            }
        } else {
            throw new Exception("File not found: $filePath");
        }
    }

    public function parseItems() {
        $items = [];

        switch ($this->fileExtension) {
            case 'xml':
                foreach ($this->xml->item as $item) {
                    $parsedItem = [];
                    foreach ($item->children() as $child) {
                        $parsedItem[$child->getName()] = (string) $child;
                    }
                    $items[] = $parsedItem;
                }
                break;
            case 'json':
                foreach ($this->data['items'] as $item) {
                    $items[] = $item;
                }
                break;
            case 'csv':
                 // Implement CSV parsing logic here
                break;
            // Add cases for other file types as needed
            default:
                throw new Exception("Unsupported file type: {$this->fileExtension}");
        }

        return $items;
    }
}

//Creating insert statements and entering data to database
class DataInsert {
    private $pdoConnection;

    public function __construct(DatabaseConnection $pdoConnection) {
        $this->pdoConnection = $pdoConnection;
    }

    public function insert($table, $data) {
        $pdo = $this->pdoConnection->getPdo();

        //test for valid data
        if (!is_array($data) || empty($data)) {
            throw new InvalidArgumentException("Data must be a non-empty array.");
        }

         // Create the table if it doesn't exist and enter column names according to data
        $pdo->exec("CREATE TABLE IF NOT EXISTS $table (
            id INTEGER PRIMARY KEY,
            " . implode(' TEXT, ', array_keys($data)) . " TEXT
        )");
        
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO $table ($columns) VALUES ($values)";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute(array_values($data));
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
        }

        return $pdo->lastInsertId();
    }
}

// Command line interface
class CommandLineInterface {
    private $filePath;
    private $table;
    private $dbType;
    private $dbPath;
    private $username;
    private $password;

    public function __construct($argv) {
        
        //Initializing variables with config information in case nothing is sent from command line
        $filePath = DONWLOAD_DIR;
        $table = DATABASE_TABLENAME;
        $dbType = DB_TYPE;
        $dbPath = DB_HOST;
        $userName = DB_USER;
        $password = DB_PASS;
        
        // Parsing command line arguments
        $this->parseArguments($argv);

        // Database connection
        $dbConnection = new DatabaseConnection($this->dbType, $this->dbPath,$this->username, $this->password);

        // Data parsing
        $dataParser = new DataParser($this->filePath);
        $items = $dataParser->parseItems();

        // Data insertion
        $dataInsert = new DataInsert($dbConnection);
        foreach ($items as $item) {
            $dataInsert->insert($this->table, $item);
        }
        
        echo "completed";
    }

    private function parseArguments($argv) {
        // Skip the first argument which is the script name
        array_shift($argv);

        // Iterate through each argument
        foreach ($argv as $arg) {
            $parts = explode('=', $arg);
            $key = $parts[0];
            $value = $parts[1];

            switch ($key) {
                case '--file':
                    $this->filePath = $value;
                    break;
                case '--table':
                    $this->table = $value;
                    break;
                case '--db-type':
                    $this->dbType = $value;
                    break;
                case '--db-path':
                    $this->dbPath = $value;
                    break;
                case '--username':
                    $this->username = $value;
                    break;
                case '--password':
                    $this->password = $value;
                    break;
                default:
                    // Invalid argument, do nothing or handle accordingly
                    break;
            }
        }
    }
}

// Run the command line interface
new CommandLineInterface($argv);
