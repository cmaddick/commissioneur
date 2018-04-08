<?php
/**
 * Created by PhpStorm.
 * User: ZHIQIN
 * Date: 04/04/2018
 * Time: 22:39
 */
require_once ("Database.php");
require_once ("Functions.php");

session_start();
$_SESSION['message'] = '';
$hashed_password = '';
$username = $_POST['uname'];
$password = $_POST['pword'];

$function = new Functions();
$database = new Database();
//validation of making sure we had the right data passed
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $status = false;
    $conn = $database->createConnection();

    $sql = "SELECT USERNAME, PASSWORD FROM USERLOGIN WHERE USERNAME = '$username'";
    $result = $conn->query($sql);
    //if username does exist in the database rows will be returned
    $rows = $result->num_rows;

    if ($rows >= 1){
        //bind the return result to a variable
        $catch = $result->fetch_row();
        $hashed_password = $catch[1];
        $check = password_verify($password, $hashed_password);

        if ($check) {
            $_SESSION['message'] = 'Login Successful!! Welcome user $username';
            //save username to the session
            $_SESSION['username'] = $username;
            $status = true;
            //TO DO: Redirect to the somewhere...
        } else {
            $_SESSION['message'] = 'Password incorrect please try again';
        }
    } else {
        $_SESSION['message'] = 'Username does not exist.';
    }
}
$conn->close();
$message = $_SESSION['message'];
if ($status == false){
    $function->DirectTo("LoginForm.html", $message);
}
?>
