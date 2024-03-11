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

    function addToCart($data)
    {
        try {
            $productExists = $this->isProductExists($data['product_id']);
            $userExists = $this->isUserExists($data['user_id']);
    
            if (!$productExists || !$userExists) {
                return array("error" => "Product ID or User ID does not exist.");
            }
            $stmt = $this->conn->prepare("INSERT INTO cart (product_id, quantity, user_id) VALUES (?, ?, ?)");
            $stmt->execute([$data['product_id'], $data['quantity'], $data['user_id']]);
            
            return "Item added to cart successfully";
        } catch (PDOException $e) {
            return array("error" => $e->getMessage());
        }
    }

    function getCartDetails()
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM cart");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return array("error" => $e->getMessage());
        }
    }

    function updateCart($id, $data)
{
    try {
        if (!isset($id) || !isset($data['product_id']) || !isset($data['quantity']) || !isset($data['user_id'])) {
            return array("error" => "Cart ID, product ID, quantity, and user ID are required.");
        }
        $cartExists = $this->isCartExists($id);
        if (!$cartExists) {
            return array("error" => "Cart ID does not exist.");
        }
        $productExists = $this->isProductExists($data['product_id']);
        $userExists = $this->isUserExists($data['user_id']);

        if (!$productExists || !$userExists) {
            return array("error" => "Product ID or User ID does not exist.");
        }

        $stmt = $this->conn->prepare("UPDATE cart SET product_id = ?, quantity = ?, user_id = ? WHERE id = ?");
        $stmt->execute([$data['product_id'], $data['quantity'], $data['user_id'], $id]);
        
        return "Cart updated successfully";
    } catch (PDOException $e) {
        return array("error" => $e->getMessage());
    }
}

    function removeFromCart($id)
{
    try {
        if (empty($id)) {
            return array("error" => "Cart ID is required to remove an item from the cart.");
        }
        if (!$this->isCartExists($id)) {
            return array("error" => "Cart ID does not exist.");
        }

        $stmt = $this->conn->prepare("DELETE FROM cart WHERE id = ?");
        $stmt->execute([$id]);
        return "Item removed from cart successfully";
    } catch (PDOException $e) {
        return array("error" => $e->getMessage());
    }
}

public function isProductExists($product_id)
{
    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $count = $stmt->fetchColumn();
    return $count > 0;
}
public function isUserExists($user_id)
{
    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $count = $stmt->fetchColumn();
    return $count > 0;
}public function isCartExists($cart_id)
{
    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM cart WHERE id = ?");
    $stmt->execute([$cart_id]);
    $count = $stmt->fetchColumn();
    return $count > 0;
}

//End Points

}

$factory = new Factory($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode($factory->getCartDetails());
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $result = $factory->addToCart($data);
    if (isset($result['error'])) {
        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
    } else {
        http_response_code(201);
        echo json_encode(['message' => $result]);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Cart ID must not be empty']);
        exit;
    }

    $result = $factory->updateCart($id, $data);
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
        echo json_encode(array("error" => "Missing cart item ID"));
    } else {
        $result = $factory->removeFromCart($id);
        if (isset($result['error'])) {
            http_response_code(500); 
            echo json_encode(['error' => $result['error']]);
        } else {
            http_response_code(200);
            echo json_encode(['message' => $result]);
        }
    }
} 

?>
