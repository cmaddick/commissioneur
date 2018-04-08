<?php
/**
 * Created by PhpStorm.
 * User: ZHIQIN
 * Date: 04/04/2018
 * Time: 08:07
 */
require_once ("Database.php");
require_once ("Functions.php");

session_start();
$_SESSION['message'] = '';

$function = new Functions();
$database = new Database();
//validation of making sure we had the right data passed
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['uname'];
    $password = $_POST['pword'];
    $status = false;
    //check for both password field input are the same
    if ($_POST["pword"] == $_POST["cpword"]) {

        $conn = $database->createConnection();
        //before insert we need to check if username already exist or not
        $duplicate="SELECT * FROM USERLOGIN WHERE USERNAME = '$username'";
        $result = $conn->query($duplicate);
        //if username already exist rows will be return
        $rows = $result->num_rows;
        if ($rows >= 1){
            //in this case data will not be insert
            $_SESSION['message'] = 'Username already exist.';
        } else {
            //so now we set variables accordingly
            $username = $conn->real_escape_string($username);
            $password = password_hash($password, PASSWORD_BCRYPT); //bcrypt has password for security

            $sql = "INSERT INTO USERLOGIN (USERNAME, PASSWORD)
                VALUES ('$username', '$password')";

            //check if the query is success or not
            $check = $conn->query($sql);
            if ($check) {
                $_SESSION['message'] = "Registration successful! Redirecting to Login Page in 3 seconds...";
                $status = true;
            } else {
                $_SESSION['message'] = 'User could not be added to the database!';
            }
        }
    } else {
        $_SESSION['message'] = 'both password are not the same!!';
    }
    $conn->close();
    //set message to be passed as a parameters
    $message = $_SESSION['message'];
    //if sign up process is bad then do not proceed to login page
    if ($status){
        $function->DirectTo("LoginForm.html", $message);
    } else{
        $function->DirectTo("SignUpForm.html", $message);
    }
}
?>

