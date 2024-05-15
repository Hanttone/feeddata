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

try {
    $pdoConnection = new DatabaseConnection(DB_TYPE, DB_HOST);
    $parser = new DataParser(DOWNLOAD_DIR);
    $items = $parser->parseItems();
    $insert = new DataInsert($pdoConnection);
    
    //Insert each array into database
    foreach($items as $item) {
        $insertId = $insert->insert(DB_TABLENAME, $item);
    }

    echo "completed";
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}
