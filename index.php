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
<style>
  @font-face {
  font-family: Moderne Sans;

}
* {
  box-sizing: border-box;
}
body {
  margin: 0;
  background: #000;
  background-size: cover;
}
video { 
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}
div#fashion {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
header {
  margin-top:20px
  position: fixed;
  width: 100%;
  text-align: center;
  color: black;
  transition: .4s;
}
header:hover {
  background: rgba(255,255,255,0.8);
  color: #000;
}
h1 {
  font-family: Moderne Sans, sans-serif;
  text-align: center;
  font-size: 2rem;
  width: 100%;
  letter-spacing: .5rem;
}
nav a {
  text-decoration: none;
  color: inherit;
  padding: 1rem;
}
h2 { 
  font-family: Century Schoolbook, Century Schoolbook L, Georgia, serif;
  font-size: 8vmin;
  text-align: center;
  margin: 2rem 3rem 0;
  mix-blend-mode: overlay;
  color: #000000;
  font-weight: 100;
}
   *, *:before, *:after {
  box-sizing: border-box;
}

html {
  font-size: 100%;
}

body {
  font-family: acumin-pro, system-ui, sans-serif;
  margin: 0;
  display: grid;
  grid-template-rows: auto 1fr auto;
  font-size: 14px;
  background-color: #f4f4f4;
  align-items: start;
  min-height: 100vh;
}

.footer {
  margin-top:-100px
  display: flex;
  flex-flow: row wrap;
  padding: 30px 30px 20px 30px;
  color: #2f2f2f;
  background-color: #fff;
  border-top: 1px solid #e5e5e5;
}

.footer > * {
  flex:  1 100%;
}

.footer__addr {
  margin-right: 1.25em;
  margin-bottom: 2em;
}

.footer__logo {
  font-family: 'Pacifico', cursive;
  font-weight: 400;
  text-transform: lowercase;
  font-size: 1.5rem;
}

.footer__addr h2 {
  margin-top: 1.3em;
  font-size: 15px;
  font-weight: 400;
}

.nav__title {
  font-weight: 400;
  font-size: 15px;
}

.footer address {
  font-style: normal;
  color: #999;
}

.footer__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 36px;
  max-width: max-content;
  background-color: rgb(0, 0, 0, 0.07);
  border-radius: 100px;
  color: #2f2f2f;
  line-height: 0;
  margin: 0.6em 0;
  font-size: 1rem;
  padding: 0 1.3em;
}

.footer ul {
  list-style: none;
  padding-left: 0;
}

.footer li {
  line-height: 2em;
}

.footer a {
  text-decoration: none;
}

.footer__nav {
  display: flex;
  flex-flow: row wrap;
}

.footer__nav > * {
  flex: 1 50%;
  margin-right: 1.25em;
}

.nav__ul a {
  color: #999;
}

.nav__ul--extra {
  column-count: 2;
  column-gap: 1.25em;
}

.legal {
  display: flex;
  flex-wrap: wrap;
  color: #999;
}
  
.legal__links {
  display: flex;
  align-items: center;
}

.heart {
  color: #2f2f2f;
}

@media screen and (min-width: 24.375em) {
  .legal .legal__links {
    margin-left: auto;
  }
}

@media screen and (min-width: 40.375em) {
  .footer__nav > * {
    flex: 1;
  }
  
  .nav__item--extra {
    flex-grow: 2;
  }
  
  .footer__addr {
    flex: 1 0px;
  }
  
  .footer__nav {
    flex: 2 0px;
  }
}
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700;800;900&display=swap");

* {
  margin-top:-10px:
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Poppins", sans-serif;
}



.footer {
  position: relative;
  width: 100%;
  background: #3586ff;
  min-height: 100px;
  padding: 20px 50px;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
}

.social-icon,
.menu {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 10px 0;
  flex-wrap: wrap;
}

.social-icon__item,
.menu__item {
  list-style: none;
}

.social-icon__link {
  font-size: 2rem;
  color: #fff;
  margin: 0 10px;
  display: inline-block;
  transition: 0.5s;
}
.social-icon__link:hover {
  transform: translateY(-10px);
}

.menu__link {
  font-size: 1.2rem;
  color: #fff;
  margin: 0 10px;
  display: inline-block;
  transition: 0.5s;
  text-decoration: none;
  opacity: 0.75;
  font-weight: 300;
}

.menu__link:hover {
  opacity: 1;
}

.footer p {
  color: #fff;
  margin: 15px 0 10px 0;
  font-size: 1rem;
  font-weight: 300;
}

.wave {
  position: absolute;
  top: -100px;
  left: 0;
  width: 100%;
  height: 100px;
  background: url("https://i.ibb.co/wQZVxxk/wave.png");
  background-size: 1000px 100px;
}

.wave#wave1 {
  z-index: 1000;
  opacity: 1;
  bottom: 0;
  animation: animateWaves 4s linear infinite;
}

.wave#wave2 {
  z-index: 999;
  opacity: 0.5;
  bottom: 10px;
  animation: animate 4s linear infinite !important;
}

.wave#wave3 {
  z-index: 1000;
  opacity: 0.2;
  bottom: 15px;
  animation: animateWaves 3s linear infinite;
}

.wave#wave4 {
  z-index: 999;
  opacity: 0.7;
  bottom: 20px;
  animation: animate 3s linear infinite;
}

@keyframes animateWaves {
  0% {
    background-position-x: 1000px;
  }
  100% {
    background-positon-x: 0px;
  }
}

@keyframes animate {
  0% {
    background-position-x: -1000px;
  }
  100% {
    background-positon-x: 0px;
  }
}

</style>
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
