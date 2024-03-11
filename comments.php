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

    function getAllComments()
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM comments");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return array("error" => $e->getMessage());
        }
    }

    function createComment($data)
{
    try {
        if (empty($data['user_id']) || empty($data['product_id'])) {
            return array("error" => "User ID and Product ID must not be empty.");
        }
        $userExists = $this->isUserExists($data['user_id']);
        $productExists = $this->isProductExists($data['product_id']);

        if (!$userExists && !$productExists) {
            return array("error" => "Both User ID and Product ID are invalid.");
        } elseif (!$userExists) {
            return array("error" => "User ID does not exist.");
        } elseif (!$productExists) {
            return array("error" => "Product ID does not exist.");
        }
        $stmt = $this->conn->prepare("INSERT INTO comments (user_id, product_id, rating, images, text) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['user_id'], $data['product_id'], $data['rating'], $data['images'], $data['text']]);
        return $this->conn->lastInsertId();
    } catch (PDOException $e) {
        return array("error" => $e->getMessage());
    }
}
public function updateComment($id, $data)
{
    try {
         if (!isset($id)) {
            return "Comment ID is required for updating the comment.";
        }
    
        $commentExists = $this->isCommentExists($id);
        if (!$commentExists) {
            return "Comment ID does not exist.";
        }
        if (empty($data['user_id']) || empty($data['product_id'])) {
            return "User ID and Product ID must not be empty.";
        }

        $userExists = $this->isUserExists($data['user_id']);
        $productExists = $this->isProductExists($data['product_id']);

        if (!$userExists && !$productExists) {
            return "Both User ID and Product ID are invalid.";
        } elseif (!$userExists) {
            return "User ID does not exist.";
        } elseif (!$productExists) {
            return "Product ID does not exist.";
        }

        $stmt = $this->conn->prepare("UPDATE comments SET user_id = ?, product_id = ?, rating = ?, images = ?, text = ? WHERE id = ?");
        $stmt->execute([$data['user_id'], $data['product_id'], $data['rating'], $data['images'], $data['text'], $id]);
        return "Comment updated successfully";
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}


public function deleteComment($id)
{
    try {
        if (!isset($id)) {
            return array("error" => "Comment ID is required for deleting the comment.");
        }
        $commentExists = $this->isCommentExists($id);
        if (!$commentExists) {
            return array("error" => "Invalid comment ID. Comment does not exist.");
        }
        $stmt = $this->conn->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$id]);

        return "Comment deleted successfully";
    } catch (PDOException $e) {
        return array("error" => $e->getMessage());
    }
}

private function isUserExists($userId)
{
    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();
    return $count > 0;
}

private function isProductExists($productId)
{
    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $count = $stmt->fetchColumn();
    return $count > 0;
}

public function isCommentExists($id)
{
    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();
    return $count > 0;
}

}

//End Points 

$factory = new Factory($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode($factory->getAllComments());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $result = $factory->createComment($data);
    if (isset($result['error'])) {
        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
    } else {
        http_response_code(201);
        echo json_encode(['message' => 'Comment posted successfully', 'comment_id' => $result]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Comment ID is required.']);
        exit;
    }
    $id = $_GET['id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $result = $factory->updateComment($id, $data);
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
    $result = $factory->deleteComment($id);
    if (isset($result['error'])) {
        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
    } else {
        http_response_code(200);
        echo json_encode(['message' => $result]);
    }
}
?>
