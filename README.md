# Data Feed Documentation

**Database Connection Handling (DatabaseConnection)**

Description:

This class handles the connection to a database using PDO (PHP Data Objects).
Constructor:

    Parameters:
        $dbType: The type of database (e.g., 'sqlite').
        $dbPath: The path to the database.
        $userName (optional): The username for authentication (not applicable for SQLite).
        $password (optional): The password for authentication (not applicable for SQLite).

Methods:

    __construct($dbType, $dbPath, $userName = NULL, $password = NULL):
        Initializes a new SQLiteConnection object and establishes a connection to the SQLite database.
        Handles authentication if provided (not applicable for SQLite).
        Sets PDO error mode to exception.

    getPdo(): PDO:
        Returns the PDO object representing the database connection.

    close():
        Closes the database connection.

**Data Parsing (DataParser)**
Description:

This class parses data from various file formats (XML, JSON, CSV).
Constructor:

    Parameters:
        $filePath: The path to the data file.

Methods:

    __construct($filePath):
        Initializes a new DataParser object and parses data from the specified file.
        Determines the file type based on the file extension and parses data accordingly.

    parseItems(): array:
        Parses items from the data file and returns an array of parsed items.

**Creating Insert Statements and Entering Data to Database (DataInsert)**
Description:

This class creates insert statements and enters data into the database table.
Constructor:

    Parameters:
        $pdoConnection: An instance of the SQLiteConnection class.

Methods:

    __construct(SQLiteConnection $pdoConnection):
        Initializes a new DataInsert object with a PDO connection to the database.

    insert($table, $data): int:
        Inserts data into the specified table in the database.
        Creates the table if it doesn't exist and defines column names based on the data.
        Executes the insert statement and returns the last inserted ID.
