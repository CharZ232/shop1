<?php
include 'config.php';
session_start();
include 'ShoppingCart.php'; // Include the ShoppingCart class

class UserSession
{
    public static function logout()
    {
        session_unset();
        session_destroy();
        header('location:login.php');
        exit();
    }
}

// Получение идентификатора пользователя
$user_id = $_SESSION['user_id'] ?? null;

// Initialize message array
$message = [];

// Создание объекта корзины
// Ensure $conn is available from config.php and $user_id is set for logged-in actions
if (isset($conn) && $user_id) {
    $cart = new ShoppingCart($conn, $user_id);
} else if ($user_id) { // User is logged in, but $conn might be missing
    die("Database connection not found in index.php. Check config.php.");
}
// If $user_id is not set, $cart object is not created here.
// addToCart attempts below will only proceed if $cart is set.

// Обработка действий
if (isset($_GET['logout'])) {
    if ($user_id) { // Only attempt logout if user_id was set
        UserSession::logout();
    } else {
        // If no user_id, perhaps redirect to login or just end script
        header('location:login.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        if (!$user_id) {
            // User must be logged in to add items to cart
            $message[] = "Пожалуйста, войдите в систему, чтобы добавить товары в корзину.";
            // Optionally redirect to login page:
            // header('location:login.php');
            // exit();
        } elseif (isset($cart)) { // Check if $cart object was successfully created
            $message[] = $cart->addToCart(
                $_POST['product_name'],
                $_POST['product_price'],
                $_POST['product_image'],
                $_POST['product_quantity']
            );
        } else {
            // This case should ideally not be reached if $user_id is set and $conn is available.
            $message[] = "Ошибка: Корзина недоступна. Попробуйте еще раз.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Dolcetta</title>

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<header>
    <h1>Dolcetta</h1>
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

<div class="user-profile">

   <?php
      if (isset($conn) && $user_id) {
         $select_user_query = "SELECT * FROM `user_form` WHERE id = ?";
         $stmt_user = mysqli_prepare($conn, $select_user_query);
         mysqli_stmt_bind_param($stmt_user, "i", $user_id);
         mysqli_stmt_execute($stmt_user);
         $result_user = mysqli_stmt_get_result($stmt_user);
         if(mysqli_num_rows($result_user) > 0){
            $fetch_user = mysqli_fetch_assoc($result_user);
         } else {
            // This case implies user_id from session does not exist in db, which is unusual
            $fetch_user = ['name' => 'Пользователь не найден', 'email' => 'N/A']; 
         }
      } else {
         // User is not logged in or $conn is not set
         $fetch_user = ['name' => 'Гость', 'email' => 'Войдите или зарегистрируйтесь'];
      }
   ?>

   <p> Имя пользователя : <span><?php echo htmlspecialchars($fetch_user['name']); ?></span> </p>
   <p> Email : <span><?php echo htmlspecialchars($fetch_user['email']); ?></span> </p>
   <div class="flex">
      <?php if ($user_id): ?>
         <a href="index.php?logout=<?php echo $user_id; ?>" onclick="return confirm('Вы уверены, что хотите выйти?');" class="delete-btn">Выйти</a>
      <?php else: ?>
         <a href="login.php" class="btn">Логин</a>
         <a href="register.php" class="option-btn">Регистрация</a>
      <?php endif; ?>
   </div>

</div>

<div class="products">

   <h1 class="heading">Наша выпечка</h1>

   <div class="box-container">

   <?php
      if (isset($conn)) {
         $select_product_query = "SELECT * FROM `products`";
         // Using mysqli_query for simplicity as no user input in this specific query
         $result_product = mysqli_query($conn, $select_product_query);
         
         if($result_product && mysqli_num_rows($result_product) > 0){
            while($fetch_product = mysqli_fetch_assoc($result_product)){
   ?>
      <form method="post" class="box" action="index.php"> <!-- Action to index.php -->
         <img src="images/<?php echo htmlspecialchars($fetch_product['image']); ?>" alt="<?php echo htmlspecialchars($fetch_product['name']); ?>">
         <div class="name"><?php echo htmlspecialchars($fetch_product['name']); ?></div>
         <div class="price">$<?php echo htmlspecialchars(number_format($fetch_product['price'], 2)); ?>/-</div>
         <input type="number" min="1" name="product_quantity" value="1" class="qty">
         <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($fetch_product['image']); ?>">
         <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($fetch_product['name']); ?>">
         <input type="hidden" name="product_price" value="<?php echo htmlspecialchars($fetch_product['price']); ?>">
         <input type="submit" value="add to cart" name="add_to_cart" class="btn">
      </form>
   <?php
            } // end while
         } else {
            echo '<p class="empty">Товары еще не добавлены!</p>';
         }
      } else {
         echo '<p class="empty">Ошибка подключения к базе данных.</p>';
      }
   ?>

   </div>

</div>

</div> <!-- .container -->

<footer class="footer">
    <div class="waves">
      <div class="wave" id="wave1"></div>
      <div class="wave" id="wave2"></div>
      <div class="wave" id="wave3"></div>
      <div class="wave" id="wave4"></div>
    </div>
    <ul class="social-icon">
      <li class="social-icon__item"><a class="social-icon__link" href="#">
          <ion-icon name="logo-facebook"></ion-icon>
        </a></li>
      <li class="social-icon__item"><a class="social-icon__link" href="#">
          <ion-icon name="logo-twitter"></ion-icon>
        </a></li>
      <li class="social-icon__item"><a class="social-icon__link" href="#">
          <ion-icon name="logo-linkedin"></ion-icon>
        </a></li>
      <li class="social-icon__item"><a class="social-icon__link" href="#">
          <ion-icon name="logo-instagram"></ion-icon>
        </a></li>
    </ul>
    <ul class="menu">
      <li class="menu__item"><a class="menu__link" href="https://vk.com/vinokurovsss">Home</a></li>
      <li class="menu__item"><a class="menu__link" href="https://vk.com/vinokurovsss">О нас</a></li>
      <li class="menu__item"><a class="menu__link" href="https://vk.com/vinokurovsss">Сервис</a></li>
      <li class="menu__item"><a class="menu__link" href="https://vk.com/vinokurovsss">Наша команда</a></li>
      <li class="menu__item"><a class="menu__link" href="https://vk.com/vinokurovsss">Контакты</a></li>

    </ul>
    <p>&copy;2024 Maximka Company | All Rights Reserved</p>
  </footer>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>

</html>
</body>
</html>
