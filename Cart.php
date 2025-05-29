<?php

class Cart
{
    private $conn;
    private $userId;

    public function __construct($dbConnection, $userId)
    {
        $this->conn = $dbConnection;
        $this->userId = $userId;
        // It's important that $userId is already validated (e.g., session started, user is logged in)
        // before this class is instantiated. The checkUserSession was a bit problematic
        // for a class constructor if it always tries to redirect.
        // Let's assume userId is valid if passed.
        if (!isset($this->userId)) {
             // Or throw an exception
            error_log("Cart class instantiated without a userId.");
            // Potentially redirect or handle error, but constructor shouldn't output headers directly.
            // For now, let's rely on calling code to ensure userId is valid.
        }
    }

    // This was originally in constructor, but a constructor should not redirect.
    // Calling code should ensure user is logged in before instantiating.
    // private function checkUserSession()
    // {
    //     if (!isset($this->userId)) {
    //         header('location:login.php');
    //         exit();
    //     }
    // }

    public function addToCart($productName, $productPrice, $productImage, $productQuantity)
    {
        // Basic validation
        if (empty($productName) || !is_numeric($productPrice) || $productPrice < 0 || !is_numeric($productQuantity) || $productQuantity < 1) {
            return 'Invalid product data.';
        }

        $query = "SELECT * FROM `cart` WHERE name = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) { return "Error preparing statement (select): " . $this->conn->error; }
        $stmt->bind_param("si", $productName, $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return 'Product already added to cart!';
        } else {
            $query = "INSERT INTO `cart` (user_id, name, price, image, quantity) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) { return "Error preparing statement (insert): " . $this->conn->error; }
            $stmt->bind_param("isdsi", $this->userId, $productName, $productPrice, $productImage, $productQuantity); // price is likely decimal
            if ($stmt->execute()) {
                return 'Product added to cart!';
            } else {
                return "Error adding product to cart: " . $stmt->error;
            }
        }
    }

    public function updateCart($cartId, $cartQuantity)
    {
        if (!is_numeric($cartId) || !is_numeric($cartQuantity) || $cartQuantity < 1) {
            return 'Invalid input for cart update.';
        }
        $query = "UPDATE `cart` SET quantity = ? WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) { return "Error preparing statement: " . $this->conn->error; }
        $stmt->bind_param("iii", $cartQuantity, $cartId, $this->userId);
        if ($stmt->execute()) {
            return 'Cart quantity updated successfully!';
        } else {
            return "Error updating cart quantity: " . $stmt->error;
        }
    }

    public function removeFromCart($cartId)
    {
        if (!is_numeric($cartId)) {
            return false; // Or an error message
        }
        $query = "DELETE FROM `cart` WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) { return false; /* Or log error */ }
        $stmt->bind_param("ii", $cartId, $this->userId);
        return $stmt->execute();
    }

    public function deleteAll()
    {
        $query = "DELETE FROM `cart` WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) { return false; /* Or log error */ }
        $stmt->bind_param("i", $this->userId);
        return $stmt->execute();
    }

    public function getCartItems() {
        $cart_items = [];
        if (!isset($this->userId)) return $cart_items; // No user, no items

        $query = "SELECT id, name, price, image, quantity FROM `cart` WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) { /* log error */ return $cart_items; }
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $cart_items[] = $row;
        }
        return $cart_items;
    }

    public function getGrandTotal() {
        $grand_total = 0;
        $items = $this->getCartItems();
        foreach ($items as $item) {
            $grand_total += $item['price'] * $item['quantity'];
        }
        return $grand_total;
    }
}
?>
