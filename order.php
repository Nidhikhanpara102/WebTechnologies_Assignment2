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

    function placeOrder($data)
    {
    try {
        $productExists = $this->isProductExists($data['product_id']);
        $userExists = $this->isUserExists($data['user_id']);

        if (!$productExists || !$userExists) {
            return array("error" => "Product ID or User ID does not exist.");
        }

        $stmt = $this->conn->prepare("INSERT INTO orders (product_id, quantity, user_id, total_amount) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['product_id'], $data['quantity'], $data['user_id'], $data['total_amount']]);
        return $this->conn->lastInsertId();
    } catch (PDOException $e) {
        return array("error" => $e->getMessage());
    }
}

    function getOrderDetails()
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM orders");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {

            echo "Error: " . $e->getMessage();
            return array("error" => $e->getMessage());
        }
    }

    function updateOrder($id, $data)
{
    try {
        if (!isset($id)) {
            return array("error" => "Order ID is required.");
        }

        $orderExists = $this->isOrderExists($id);
        if (!$orderExists) {
            return array("error" => "Order ID does not exist.");
        }

        if (!isset($data['product_id']) || !isset($data['quantity']) || !isset($data['user_id']) || !isset($data['total_amount'])) {
            return array("error" => "Product ID, quantity, user ID, and total amount are required.");
        }

        $productExists = $this->isProductExists($data['product_id']);
        $userExists = $this->isUserExists($data['user_id']);

        if (!$productExists || !$userExists) {
            return array("error" => "Product ID or User ID does not exist.");
        }

        $stmt = $this->conn->prepare("UPDATE orders SET product_id = ?, quantity = ?, user_id = ?, total_amount = ? WHERE id = ?");
        $stmt->execute([$data['product_id'], $data['quantity'], $data['user_id'], $data['total_amount'], $id]);

        return "Order updated successfully";
    } catch (PDOException $e) {
        return array("error" => $e->getMessage());
    }
}

function cancelOrder($id)
{
    try {
        if (empty($id)) {
            return array("error" => "Order ID is required.");
        }
        if (!$this->isOrderExists($id)) {
            return array("error" => "Invalid order ID. Order does not exist.");
        }
        $stmt = $this->conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$id]);

        return "Order canceled successfully";
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
    }
    public function isOrderExists($order_id)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $count = $stmt->fetchColumn();
        return $count > 0;
    }
    
}

//End Points
$factory = new Factory($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode($factory->getOrderDetails());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);

    $result = $factory->placeOrder($data);

    if (isset($result['error'])) {

        http_response_code(500); 
        echo json_encode(['error' => $result['error']]);
    } else {

        http_response_code(201); 
        echo json_encode(['message' => 'Order Placed successfully', 'order_id' => $result]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID is missing.']);
        exit;
    }
    $id = $_GET['id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $result = $factory->updateOrder($id, $data);

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
    $result = $factory->cancelOrder($id);
    if (isset($result['error'])) {
        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
    } else {
        http_response_code(200);
        echo json_encode(['message' => $result]);
    }
}

?>
