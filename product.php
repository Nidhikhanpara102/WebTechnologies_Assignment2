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

    function getAllProducts()
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM products");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {

            echo "Error: " . $e->getMessage();
            return array("error" => $e->getMessage());
        }
    }
    public function createProduct($data)
    {
        if (!isset($data['description']) || !isset($data['image']) || !isset($data['price']) || !isset($data['shipping_cost'])) {
            return array("error" => "There might be some missing fields.All required fields (description, image, price, shipping_cost) must be provided.");
        }
    
        try {
            $stmt = $this->conn->prepare("INSERT INTO products (description, image, price, shipping_cost) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['description'], $data['image'], $data['price'], $data['shipping_cost']]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            return array("error" => $e->getMessage());
        }
    }
    
    public function updateProduct($id, $data)
{
    try {
        if (empty($id)) {
            return array("error" => "Product ID is required for updating the product.");
        }
        
        $product = $this->getProductId($id);
        if (!$product) {
            return array("error" => "Invalid product ID. Product does not exist.");
        }

        $requiredFields = ['description', 'image', 'price', 'shipping_cost'];
        $providedFields = array_intersect($requiredFields, array_keys($data));
        if (empty($providedFields)) {
            return array("error" => "At least one field must be provided for updating the product. Required Fields are: " . implode(', ', $requiredFields));
        }
        
        $updateFields = [];
        $values = [];
        foreach ($providedFields as $field) {
            $updateFields[] = "$field = ?";
            $values[] = $data[$field];
        }
        $values[] = $id;
        
        $sql = "UPDATE products SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($values);

        return "Product has been updated successfully";
    } catch (PDOException $e) {
        return array("error" => $e->getMessage());
    }
}


    public function deleteProduct($id)
{
    try {
        if (empty($id)) {
            return array("error" => "Product ID is required for deleting the product.");
        }

        $product = $this->getProductId($id);
        if (!$product) {
            return array("error" => "Invalid product ID. Product does not exist.");
        }

        $stmt = $this->conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        return "Product deleted successfully";
    } catch (PDOException $e) {
        return array("error" => $e->getMessage());
    }
}
    private function getProductId($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        return $product;
    }
}

//End Points


$factory = new Factory($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode($factory->getAllProducts());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);


    $result = $factory->createProduct($data);


    if (isset($result['error'])) {

        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
    } else {

        http_response_code(201); 
        echo json_encode(['message' => 'Product created successfully', 'product_id' => $result]);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {

    $data = json_decode(file_get_contents('php://input'), true);

    $id = $_GET['id'];


    $result = $factory->updateProduct($id, $data);

    if (isset($result['error'])) {

        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
    } else {

        http_response_code(200); 
        echo json_encode(['message' => $result]);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;

    if (!$id) {
        http_response_code(400); 
        echo json_encode(['error' => 'Product ID is required for deleting the product.']);
        exit;
    }
    $result = $factory->deleteProduct($id);
    if (isset($result['error'])) {
        http_response_code(500); 
        echo json_encode(['error' => $result['error']]);
    } else {
        http_response_code(200); 
        echo json_encode(['message' => 'Product deleted successfully']);
    }
}

?>
