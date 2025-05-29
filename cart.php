<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';
session_start();
include 'ShoppingCart.php'; // Include the ShoppingCart class

// Получение идентификатора пользователя
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('location:login.php');
    exit();
}

// Создание объекта корзины
// Ensure $conn is available. It should be from config.php
if (!isset($conn)) {
    // This should ideally not happen if config.php is included correctly
    // and $conn is initialized there.
    die("Database connection not found in cart.php. Check config.php."); 
}
$cart = new ShoppingCart($conn, $user_id);

// Обработка действий
$message = []; // Initialize message array

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        $message[] = $cart->updateCart(
            $_POST['cart_id'],
            $_POST['cart_quantity']
        );
    }
    // Add other POST actions if necessary, e.g., applying a coupon
}

if (isset($_GET['remove'])) {
    if ($cart->removeFromCart($_GET['remove'])) {
        $message[] = "Item removed successfully."; // This message will be lost due to redirect
    } else {
        $message[] = "Error removing item."; // This message will be lost due to redirect
    }
    // Redirect to cart.php without GET parameters to prevent re-execution on refresh
    header('location:cart.php');
    exit();
}

if (isset($_GET['delete_all'])) {
    if ($cart->deleteAll()) {
        $message[] = "All items removed successfully."; // This message will be lost due to redirect
    } else {
        $message[] = "Error removing all items."; // This message will be lost due to redirect
    }
    // Redirect to cart.php without GET parameters
    header('location:cart.php');
    exit();
}

// Fetch cart items for display
$cart_items = $cart->getCartItems();
$grand_total = $cart->getGrandTotal();

?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shopping Cart</title>
   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <h1><a href="index.php" style="text-decoration:none; color:inherit;">Dolcetta</a></h1>
    <nav>
        <a href="index.php">Каталог</a>
        <a href="cart.php">Корзина</a>
        <a href="#">О нас</a>
        <a href="#">Контакты</a>
    </nav>
</header>

<div class="container">

<?php
if(!empty($message)){
   foreach($message as $msg){
      echo '<div class="message" onclick="this.remove();">'.htmlspecialchars($msg).'</div>';
   }
}
?>

<div class="shopping-cart">

   <h1 class="heading">Корзина</h1>

   <table>
      <thead>
         <th>фото</th>
         <th>название</th>
         <th>цена</th>
         <th>количество</th>
         <th>общая сумма</th>
         <th>действие</th>
      </thead>
      <tbody>
      <?php
         if(count($cart_items) > 0){
            foreach($cart_items as $fetch_cart){
      ?>
         <tr>
            <td><img src="images/<?php echo htmlspecialchars($fetch_cart['image']); ?>" height="100" alt=""></td>
            <td><?php echo htmlspecialchars($fetch_cart['name']); ?></td>
            <td>$<?php echo htmlspecialchars($fetch_cart['price']); ?>/-</td>
            <td>
               <form action="cart.php" method="post">
                  <input type="hidden" name="cart_id" value="<?php echo $fetch_cart['id']; ?>">
                  <input type="number" min="1" name="cart_quantity" value="<?php echo $fetch_cart['quantity']; ?>" class="qty">
                  <input type="submit" name="update_cart" value="update" class="option-btn">
               </form>
            </td>
            <td>$<?php echo number_format($fetch_cart['price'] * $fetch_cart['quantity'], 2); ?>/-</td>
            <td><a href="cart.php?remove=<?php echo $fetch_cart['id']; ?>" class="delete-btn" onclick="return confirm('Убрать товар из корзины?');">убрать</a></td>
         </tr>
      <?php
            } // end foreach
         }else{
            echo '<tr><td style="padding:20px; text-transform:capitalize;" colspan="6">В вашей корзине пусто</td></tr>';
         }
      ?>
      <tr class="table-bottom">
         <td colspan="4">Общая сумма :</td>
         <td>$<?php echo number_format($grand_total, 2); ?>/-</td>
         <td><a href="cart.php?delete_all" onclick="return confirm('Удалить все товары из корзины?');" class="delete-btn <?php echo ($grand_total > 0)?'':'disabled'; ?>">Убрать все</a></td>
      </tr>
   </tbody>
   </table>

   <div class="cart-btn">  
      <a href="#" class="btn <?php echo ($grand_total > 0)?'':'disabled'; ?>">Перейти к оформлению</a>
   </div>
   <div style="margin-top: 20px; text-align: center;">
      <a href="index.php" class="btn">Продолжить покупки</a>
   </div>

</div> <!-- .shopping-cart -->
</div> <!-- .container -->

</body>
</html>
