<?php

class User
{
    private $id;
    private $email;
    private $displayName;

    public function __construct($id, $email, $displayName) {
        $this->id = $id;
        $this->email = $email;
        $this->displayName = $displayName;
    }

    public static function get_user_by_id($pdo, $userID) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE UserID = ?');
        $stmt->execute([$userID]);
        $row = $stmt->fetch();

        if ($row) {
            $user = new User($row['UserID'], $row['Email'], $row['displayName']);
            return $user;
        } else {
            return null;
        }
    }

    public static function get_user_by_email($pdo, $userEmail) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE Email = ?');
        $stmt->execute([$userEmail]);
        $row = $stmt->fetch();

        if ($row) {
            $user = new User($row['UserID'], $row['Email'], $row['displayName']);
            return $user;
        } else {
            return null;
        }
    }

    public static function login_user($pdo, $email, $password) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE EMAIL = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if($row) {

            $dbPasswordHash = $row['Password'];

            if(password_verify($password, $dbPasswordHash)) {
                $user = new User($row['id'], $row['Email'], $row['DisplayName']);
                $userID = $row['UserID'];
                $displayName = $row['DisplayName'];
                $_SESSION['IsLoggedIn'] = 'true';
                $_SESSION['UserID'] = $userID;
                $_SESSION['DisplayName'] = $displayName;

                return $user;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    public static function register_user($pdo, $email, $displayName, $password, $rePassword) {
        // Creates a new user in the database

        $userID = mt_rand(100000000, 999999999);

        if (!self::get_user_by_email($pdo, $email)) {
            if ($password === $rePassword) {
                $password = password_hash($password, PASSWORD_DEFAULT);
                if (!self::get_user_by_id($pdo, $userID)) {
                    $stmt = $pdo->prepare('INSERT INTO `users` (`UserID`, `Email`, `Password`, `DisplayName`) VALUES (:userid, :email, :password, :displayName)');
                    $stmt->bindParam(':userid', $userID);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $password);
                    $stmt->bindParam(':displayName', $displayName);
                    $stmt->execute();

                    return self::get_user_by_id($pdo, $userID);
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}