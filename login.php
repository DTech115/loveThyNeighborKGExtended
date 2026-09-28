<?php
    // Comment for assignment -Madi
    // Template for new VMS pages. Base your new page on this one

    // Make session information accessible, allowing us to associate
    // data with the logged-in user.
    session_cache_expire(30);
    session_start();
    
    ini_set("display_errors",1);
    error_reporting(E_ALL);

    // redirect to index if already logged in
    if (isset($_SESSION['_id'])) {
        header('Location: index.php');
        die();
    }
    $badLogin = false;
    $archivedAccount = false;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once('include/input-validation.php');
        $ignoreList = array('password');
        $args = sanitize($_POST, $ignoreList);
        $required = array('username', 'password');
        if (wereRequiredFieldsSubmitted($args, $required)) {
            require_once('domain/Person.php');
            require_once('database/dbPersons.php');
            /*@require_once('database/dbMessages.php');*/
            /*@dateChecker();*/
            $username = trim($args['username']);
            $password = $args['password'];
            $user = retrieve_person($username);
            if (!$user) {
                $badLogin = true;
            } /*else if ($user->get_status() === "Inactive") {
                // If the user is archived, block login
                $archivedAccount = true;
            }*/ else if (password_verify($password, $user->get_password())) {
                $_SESSION['logged_in'] = true;

                $_SESSION['access_level'] = $user->get_access_level();
                $_SESSION['f_name'] = $user->get_first_name();
                $_SESSION['l_name'] = $user->get_last_name();

                $type = $user->get_access_level();
                if($type == 2){
                  $_SESSION['type'] = 'admin';
                }
                else if($type == 1){
                  $_SESSION['type'] = 'volunteer';
                }
                unset($type);
                $_SESSION['_id'] = $user->get_id();
                
                 //hard code root privileges
                 if ($user->get_id() == 'vmsroot') {
                    $_SESSION['access_level'] = 3;
		                $_SESSION['locked'] = false;
                    header('Location: index.php');
               }
               //hard code kiosk privileges
               elseif($user->get_id() =='vmskiosk')
                {
                  $_SESSION['access_level'] = 4;
                  $_SESSION['locked'] = false;
                  header('Location: KioskviewOverallEventsKG.php');
                }
            
                //if ($changePassword) {
                //    $_SESSION['access_level'] = 0;
                //    $_SESSION['change-password'] = true;
                //    header('Location: changePassword.php');
                //    die();
                //} 
                else {
                    header('Location: index.php');
                    die();
                }
                die();
            } else {
                $badLogin = true;
            }
        }
    }
    //<p>Or <a href="register.php">register as a new volunteer</a>!</p>
    //Had this line under login button, took user to register page
?>
<!DOCTYPE html>
<html>
    <head>
	<script src="https://cdn.tailwindcss.com"></script>
    	<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;700&display=swap" rel="stylesheet">
	<style>

* { font-family: Quicksand, sans-serif; }
	</style>
        <title>Log-In | Love Thy Neighbor Community Food Pantry</title>
    </head>
  <body>
    <div class="min-h-screen relative">

      <!-- Left: Image Section (Hidden on small screens) -->
      <!-- Background Image Now -->
      <div class="absolute inset-0">
          <img src="images/LoveThyNeighbor_foodLightBlue.jpg"
                alt="Barrels"
                style="height: 100%;"
                class="w-full h-full object-cover">

      </div>

      <!-- dark overlay -->
      <div class="absolute inset-0 bg-black/50"></div>

        <!-- Right: Form Section -->
      <!-- now the middle form section -->
      <div class="relative min-h-screen flex items-center justify-center ">

        <div class="w-full max-w-xl bg-white backdrop-blur-md p-7 rounded-3xl shadow-2xl ">
          

          <!-- Logo Placeholder (Now the same width as inputs and centered) -->
          <div class="w-full flex justify-center mb-6">
            <img src="images\LoveThyNeighbor_logo1_NoBackground.png"
                alt="Logo"
                class="w-full max-w-xs">
          </div>

          <form class="w-full" method="post">
                    <?php
                        if ($badLogin) {
                            echo '<span class="text-white bg-red-700 text-center block p-2 rounded-lg mb-2">No login with that username and password combination currently exists.</span>';
                        }
                        if ($archivedAccount) {
                            echo '<span class="text-white bg-red-700 block p-2 rounded-lg mb-2">This account has either been archived or not yet approved by managment. For help, notify <a href="mailto:volunteer@fredspca.org">volunteer@fredspca.org</a>.</span>';
                        }
            if (isset($_GET['registerSuccess'])) {
                            echo '<span class="text-white text-center bg-green-700 block p-2 rounded-lg mb-2">Registration Successful! Please login below.</span>';
            } 
                    ?>
            <div class="mb-4">
              <label class="block text-[rgb(0,74,173)] font-medium mb-2" for="username">Login</label>
              <input class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400" type="text" name="username" placeholder="Enter your username" required>
            </div>
            <div class="mb-4">
              <label class="block text-[rgb(0,74,173)] font-medium mb-2" for="password">Password</label>
              <input class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400" type="password" name="password" placeholder="Enter your password" required>
            </div>
            <div class="flex justify-between items-center mb-4">
              <a href="forgotPassword.php" class="text-[rgb(0,74,173)] text-sm hover:underline">Forgot password?</a>
              <a href="https://www.kgfood.org/" class="text-[rgb(0,74,173)] text-sm hover:underline">Love Thy Neighbor Website</a>
            </div>
            <button class="cursor-pointer w-full bg-[rgb(0,74,173)] hover:bg-[rgb(203,37,26)] text-white font-semibold py-3 rounded-lg transition duration-300">Login</button>
          </form>

          <!-- Divider -->
          <div class="flex items-center my-6 w-full">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="mx-4 text-gray-500">or</span>
            <div class="flex-grow border-t border-gray-300"></div>
          </div>

          <!-- Sign Up Section -->
          <p class="text-center text-gray-700">
            Don’t have an account?
            <a href="VolunteerRegister.php" class="text-[rgb(203,37,26)] font-semibold hover:underline">Sign Up Now</a>
          </p>

        </div>
      </div>

    </div>

    </body>
</html>
