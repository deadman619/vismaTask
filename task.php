<?php

$host = "localhost";
$username = "root";
$password = "";
$dbname = "test";
$dsn = "mysql:host=$host;dbname=$dbname";

try {
    $conn = new PDO($dsn, $username, $password);
} catch (Exception $e) {
    echo $e;
}

try {
    $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $createCompaniesTable = "CREATE TABLE IF NOT EXISTS companies (id int(11) AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        registration_code varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(255) NOT NULL,
        comment varchar(1024) NULL,
        PRIMARY KEY (id))";
    $conn->exec($createCompaniesTable);
} catch (PDOException $e) {
    print $e->getMessage();
}

if (!isset($argv[1])) {
    print "You must specify a command you wish to execute, i.e. 'php task.php help' to get a list of commands\n";
    return;
}

switch ($argv[1]) {
    case 'help':
        help();
        break;
    case 'addCompany':
        try {
            addCompany($conn, $argv[2] ?? null, $argv[3] ?? null, $argv[4] ?? null, $argv[5] ?? null, $argv[6] ?? null);
        } catch (Exception $e){
            print 'Caught exception: '. $e->getMessage();
        }
        break;
    case 'editCompany':
        try {
            editCompany($conn, $argv[2] ?? null, $argv[3] ?? null, $argv[4] ?? null, $argv[5] ?? null, $argv[6] ?? null, $argv[7] ?? null);
        } catch (Exception $e){
            print 'Caught exception: '. $e->getMessage();
        }
        break;
    case 'deleteCompany':
        try {
            deleteCompany($conn, $argv[2] ?? null);
        } catch (Exception $e) {
            print 'Caught exception: '. $e->getMessage();
        }
        break;
    case 'importCsv':
        try {
            importCsv($conn, $argv[2] ?? null);
        } catch (Exception $e){
            print 'Caught exception: '. $e->getMessage();
        }
        break;
    default:
        print "Command not found";
}

function help() {
    print "The commands for this application are as follows:\n";
    print "addCompany (you must provide a name, registration_code, email, phone and an optional comment. Values must be space seperated.)\n";
    print "editCompany (you must provide an ID and all the same fields as the addCompany function)\n";
    print "deleteCompany (you must provide an ID)\n";
    print "importCsv (you must provide the path to the CSV file)\n";
}

/**
 * @param $conn
 * @param $name
 * @param $registration_code
 * @param $email
 * @param $phone
 * @param string $comment
 * @return int
 * @throws Exception
 */
function addCompany($conn, $name, $registration_code, $email, $phone, $comment = '') {
    dataValidation($name, $registration_code, $email, $phone);
    if (!phoneValidation($phone)) {
        throw new Exception("Invalid phone number");
    }
    $emailExistsQuery = "SELECT email FROM companies WHERE email = :email ";
    $emailExists = $conn->prepare($emailExistsQuery);
    $emailExists->bindParam(':email', $email, PDO::PARAM_STR);
    $emailExists->execute();
    if ($emailExists->rowCount() > 0) {
        return print "Someone else has already used this email address, could not create new company entry";
    }
    $newCompany = "INSERT INTO companies (name, registration_code, email, phone, comment) VALUES (:name, :registration_code, :email, :phone, :comment)";
    $newCompanyPrepare = $conn->prepare($newCompany);
    $newCompanyPrepare->bindParam(':name', $name, PDO::PARAM_STR);
    $newCompanyPrepare->bindParam(':registration_code', $registration_code, PDO::PARAM_STR);
    $newCompanyPrepare->bindParam(':email', $email, PDO::PARAM_STR);
    $newCompanyPrepare->bindParam(':phone', $phone, PDO::PARAM_STR);
    $newCompanyPrepare->bindParam(':comment', $comment, PDO::PARAM_STR);
    $newCompanyPrepare->execute();

    return print "Company successfully saved to DB";
}

/**
 * @param $conn
 * @param $id
 * @param $name
 * @param $registration_code
 * @param $email
 * @param $phone
 * @param string $comment
 * @return int
 * @throws Exception
 */
function editCompany($conn, $id, $name, $registration_code, $email, $phone, $comment = '') {
    dataValidation($name, $registration_code, $email, $phone);
    if (!isset($id)) {
        throw new Exception("Missing ID");
    }
    if (!phoneValidation($phone)) {
        throw new Exception("Invalid phone number");
    }
    $emailExistsQuery = "SELECT email FROM companies WHERE email = :email AND id != :id ";
    $emailExists = $conn->prepare($emailExistsQuery);
    $emailExists->bindParam(':email', $email, PDO::PARAM_STR);
    $emailExists->bindParam(':id', $id, PDO::PARAM_STR);
    $emailExists->execute();
    if ($emailExists->rowCount() > 0) {
        return print "Someone else has already used this email address, could not update company entry";
    }
    $idExistsQuery = "SELECT * FROM companies WHERE id = :id";
    $idExistsPrepare = $conn->prepare($idExistsQuery);
    $idExistsPrepare->bindParam(':id', $id, PDO::PARAM_STR);
    $idExistsPrepare->execute();
    if ($idExistsPrepare->rowCount() > 0) {
        $editCompany = "UPDATE companies SET name= :name, registration_code=:registration_code, email=:email, phone=:phone, comment=:comment WHERE id = :id";
        $editCompanyPrepare = $conn->prepare($editCompany);
        $editCompanyPrepare->bindParam(':name', $name, PDO::PARAM_STR);
        $editCompanyPrepare->bindParam(':registration_code', $registration_code, PDO::PARAM_STR);
        $editCompanyPrepare->bindParam(':email', $email, PDO::PARAM_STR);
        $editCompanyPrepare->bindParam(':phone', $phone, PDO::PARAM_STR);
        $editCompanyPrepare->bindParam(':comment', $comment, PDO::PARAM_STR);
        $editCompanyPrepare->bindParam(':id', $id, PDO::PARAM_STR);
        $editCompanyPrepare->execute();
        return print "Company successfully updated in DB";
    }

    return print "ID does not exist in DB. Nothing to update";

}

/**
 * @param $conn
 * @param $id
 * @return int
 * @throws Exception
 */
function deleteCompany($conn, $id) {
    if (!isset($id) || !intval($id)) {
        throw new Exception ("Missing ID");
    }
    if (!intval($id)) {
        throw new Exception ("ID must be numeric");
    }
    $idExistsQuery = "SELECT * FROM companies WHERE id = :id";
    $idExistsPrepare = $conn->prepare($idExistsQuery);
    $idExistsPrepare->bindParam(':id', $id, PDO::PARAM_STR);
    $idExistsPrepare->execute();
    if ($idExistsPrepare->rowCount() > 0) {
        $rowToDelete = "DELETE FROM companies WHERE id = :id";
        $rowToDeletePrepare = $conn->prepare($rowToDelete);
        $rowToDeletePrepare->bindParam(':id', $id, PDO::PARAM_STR);
        $rowToDeletePrepare->execute();
        return print "Row deleted";
    }

    return print "Row with that ID does not exist";
}

/**
 * @param $conn
 * @param $filePath
 */
function importCsv($conn, $filePath) {
    csvValidation($filePath);
    $csv = fopen($filePath, 'r');
    $content = fread($csv, filesize($filePath));
    $fileByRows = explode(PHP_EOL, $content);
    $parsedData = parseRows($fileByRows);
    $columnNames = array_shift($parsedData);
    foreach($parsedData as $dbEntry) {
        addCompany($conn, $dbEntry[0] ?? null, $dbEntry[1] ?? null, $dbEntry[2] ?? null, $dbEntry[3] ?? null, $dbEntry[4] ?? null);
    }
}

/**
 * @param array $data
 * @return array
 */
function parseRows(array $data) {
    $parsedData = [];
    foreach ($data as $index => $line) {
        $trimmedLine = trim($line, "\n");
        $lineToArray = $trimmedLine ? explode(';', $trimmedLine) : null;
        if (!empty($lineToArray)) {
            $parsedData[] = $lineToArray;
        }
    }

    return $parsedData;
}

/**
 * @param $name
 * @param $registration_code
 * @param $email
 * @param $phone
 * @return bool
 * @throws Exception
 */
function dataValidation($name, $registration_code, $email, $phone) {
    if (!isset($name)) {
        throw new Exception("Missing name");
    }
    if (!isset($registration_code)) {
        throw new Exception("Missing registration code");
    }
    if (!isset($email)) {
        throw new Exception("Missing email");
    }
    if (!isset($phone)) {
        throw new Exception("Missing phone number");
    }

    return true;
}

/**
 * @param $phoneNumber
 * @return bool
 */
function phoneValidation($phoneNumber) {
    $cleanedUpNumber = str_replace('-', '', $phoneNumber);
    $cleanedUpNumber = str_replace('+', '', $cleanedUpNumber);
    if (strlen($cleanedUpNumber) < 9 || strlen($cleanedUpNumber) > 11 || !intval($cleanedUpNumber)) {
        print $cleanedUpNumber;
        return false;
    }

    return true;
}

/**
 * @param $filePath
 * @return bool
 * @throws Exception
 */
function csvValidation($filePath) {
    if (!isset($filePath)) {
        throw new Exception ("Missing file path");
    }
    if (!file_exists($filePath)) {
        throw new Exception ("File not found");
    }
    $pathInfo = pathinfo($filePath);
    if ($pathInfo['extension'] !== 'csv') {
        throw new Exception ("Wrong file type. File must be CSV");
    }
    if (filesize($filePath) === 0) {
        throw new Exception ("File is empty");
    }

    return true;
}
