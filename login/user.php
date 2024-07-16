<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__.'/../vendor/autoload.php';

class User {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function authenticate($username, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE login = :login LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':login', $username);
        
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        } else {
            return false;
        }
    }

    public function userExists($username) {
        $query = "SELECT id, level FROM " . $this->table_name . " WHERE login = :login LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':login', $username);
        
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        return $user ? $user['level'] : false;
    }  

    public function userId($username) { // Same function as above except it returns id not levelx
        $query = "SELECT id, level FROM " . $this->table_name . " WHERE login = :login LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':login', $username);
        
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        return $user ? $user['id'] : false;
    }    

    public function findUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ? $user : false;
    }

    public function updateUserToken($email, $token) {
        $query = "UPDATE " . $this->table_name . " SET password_reset_token = :token WHERE email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':email', $email);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function sendPasswordResetLink($email, $token) {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        try {
            //Server settings
            $mail->isSMTP();                                           
            $mail->Host       = 'smtp.gmail.com';                    
            $mail->SMTPAuth   = true;                                  
            $mail->Username   = 'zado1984@gmail.com';              
            $mail->Password   = 'gvmn kxff nquz ivar';                       
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;        
            $mail->Port       = 587;                                   

            //Recipients
            $mail->setFrom('info@hydro-dyna.pl', 'Mailer');
            $mail->addAddress($email);     

            //Content
            $mail->isHTML(true);                                  
            $mail->Subject = 'Resetowanie hasła w HYDRO-DYNA APP';
            $mail->Body    = '<strong>HYDRO-DYNA APP</strong><br><br>Dostałeś ten email bo wysłałeś zapytanie o zresetowanie hasła do swojego konta<br>Jeżeli nie pytałeś o reset hasła, zignoruj ten email i powiadom administratora<br><br>👉 <strong><a href="https://hydro-dyna.pl/app/login/index.php?token=' . $token . '">ZRESETUJ HASŁO</a></strong>';

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function resetPassword($token, $newPassword) {
        $query = "UPDATE " . $this->table_name . " SET password = :newPassword, password_reset_token = NULL WHERE password_reset_token = :token";
        
        $stmt = $this->conn->prepare($query);
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt->bindParam(':newPassword', $newPasswordHash);
        $stmt->bindParam(':token', $token);

        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function getUserByToken($token) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE password_reset_token = :token LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ? $user : false;
    }
}
?>
