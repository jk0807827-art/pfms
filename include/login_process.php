<?php
   
class login_process{

    var $db;

    function __construct($conn){
        $this->db = $conn;
    }


    public function user_login($email)
    {
        try{

            $sql = "
                SELECT
                    user_id,
                    full_name,
                    email,
                    phone,
                    password_hash,
                    is_active,
                    last_login_at,
                    created_at,
                    updated_at
                FROM users
                WHERE email = :email
                LIMIT 1
            ";

            $statement = $this->db->prepare($sql);

            $statement->execute([
                ":email" => $email
            ]);

            return $statement->fetch(PDO::FETCH_ASSOC);

        }catch(\Throwable $th){

            throw $th;

        }
    }

}


?>