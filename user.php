<?php
$url = "mysql:host=localhost;dbname=web_assignment2";
$user = "root";
$password = "";
$db = new PDO($url, $user, $password);
?>

<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'web_assignment2');
?>

<?php
class Factory
{
    private $conn;

    function __construct($db)
    {
        $this->conn = $db; 
    }


    function getAllUsers()
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
  
            echo "Error: " . $e->getMessage();
            return array("error" => $e->getMessage());
        }
    }

  
public function createUser($data)
{
    $errors = array();

    if (empty($data['email'])) {
        $errors[] = "Email ID must not be empty";
    }
    if (empty($data['password'])) {
        $errors[] = "Password must not be empty";
    }
    if (empty($data['username'])) {
        $errors[] = "Username must not be empty";
    }
    if (empty($data['shipping_address'])) {
        $errors[] = "Shipping address must not be empty";
    }
    
    if (!empty($errors)) {
        return array("error" => implode(', ', $errors));
    }

    try {
        $stmt = $this->conn->prepare("INSERT INTO users (email, password, username, purchase_history, shipping_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['email'], $data['password'], $data['username'], $data['purchase_history'], $data['shipping_address']]);
        
        return $this->conn->lastInsertId();
    } catch (PDOException $e) {
        return array("error" => $e->getMessage());
    }
}

public function updateUser($id, $data)
{
    try {
        if (!isset($id)) {
            return array("error" => "User ID is required for updating the user.");
        }
        $user = $this->getUserById($id);
        if (!$user) {
            return array("error" => "Invalid user ID. User does not exist.");
        }
        $requiredFields = ['email', 'password'];
        $emptyFields = array();
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $emptyFields[] = $field;
            }
        }
        if (!empty($emptyFields)) {
            $emptyFieldsString = implode(", ", $emptyFields);
            return array("error" => "Required fields are empty: $emptyFieldsString");
        }
        if (count(array_filter($data)) === 0) {
            return array("error" => "At least one field must be provided for updating the user.");
        }
        $stmt = $this->conn->prepare("UPDATE users SET email = ?, password = ?, username = ?, purchase_history = ?, shipping_address = ? WHERE id = ?");
        $stmt->execute([$data['email'], $data['password'], $data['username'], $data['purchase_history'], $data['shipping_address'], $id]);
        
        return "User updated successfully";
    } catch (PDOException $e) {
        return array("error" => $e->getMessage());
    }
}    
    public function deleteUser($id)
{
    try {
        if (empty($id)) {
            return array("error" => "User ID is required for deleting the user.");
        }
        $user = $this->getUserById($id);
        if (!$user) {
            return array("error" => "Invalid user ID. User does not exist.");
        }
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
   
        return "User deleted successfully";
    } catch (PDOException $e) {
        return array("error" => $e->getMessage());
    }
}
    private function getUserById($id)
{
    $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user;
}

}


$factory = new Factory($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode($factory->getAllUsers());
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);

    $result = $factory->createUser($data);

  
    if (isset($result['error'])) {
        
        http_response_code(500); 
        echo json_encode(['error' => $result['error']]);
    } else {

        http_response_code(201);
        echo json_encode(['message' => 'User created successfully', 'user_id' => $result]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {

    $data = json_decode(file_get_contents('php://input'), true);

    $id = $_GET['id']; 

    $result = $factory->updateUser($id, $data);


    if (isset($result['error'])) {

        http_response_code(500); 
        echo json_encode(['error' => $result['error']]);
    } else {
  
        http_response_code(200); // OK
        echo json_encode(['message' => $result]);
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;

    if (!$id) {
        http_response_code(400); 
        echo json_encode(['error' => 'User ID is required for deleting the product.']);
        exit;
    }
    $result = $factory->deleteUser($id);
    if (isset($result['error'])) {
        http_response_code(500); 
        echo json_encode(['error' => $result['error']]);
    } else {
        http_response_code(200); 
        echo json_encode(['message' => 'User deleted successfully']);
    }
}

?>
