<?php

class User {
    public static function getAll() //obtiene todos los usuarios con nombre y valor total portfolio
    {
        $db = DB::getConnection();
        $stmt = $db->query
          ("SELECT u.name, COALESCE(SUM(p.quantity * a.current_price), 0) as TOTAL
            FROM users u
            LEFT JOIN portfolio p ON u.id = p.user_id 
            LEFT JOIN assets a ON p.asset_id = a.id
            GROUP BY u.id"
          );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getUserInfo (array $args)  //obtiene el usuario con nombre, mail, saldo y valor total portfolio
    {
        $db = DB::getConnection();
        $id = $args['user_id'];
        $stmt = $db->prepare
          ("SELECT u.name, u.email, u.balance, COALESCE(SUM(p.quantity * a.current_price), 0) as TOTAL
            FROM users u 
            LEFT JOIN portfolio p ON u.id = p.user_id 
            LEFT JOIN assets a ON p.asset_id = a.id
            WHERE u.id = :id
            GROUP BY u.id"
            );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function insertUserM (array $data) //agrega un usuario con los parametros name, email, y password
    {
       $db = DB::getConnection();
       $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
       $stmt->execute([
            ':name' => $data['name'] ?? '',
            ':email' => $data['email'] ?? '',
            ':password' => $data['password'] ?? ''
       ]);
    }

    public static function updateUserM(array $data, $idUser) //edita un usuario con cualquiera de los 3 parametros enviados
    {
        $db = DB::getConnection();
        $fields = [];
        $params = [':id' => $idUser];

        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = $data['email'];
        }

        if (isset($data['password'])) {
            $fields[] = "password = :password";
            $params[':password'] = $data['password'];
        }
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

	  public static function UserExist (array $data) { //valida la existencia de un usuario mediante el email y password
      $db = DB::getConnection();
      $stmt = $db->prepare ("SELECT u.id FROM users u WHERE u.email = :email AND u.password = :password");
      $stmt->execute ([
      ':email' => $data['email'],
      ':password' => $data['password']
      ]);
      return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function addToken ($expireDateTime, $token, $idUser) //agrega el token al usuario
    {
       $db = DB::getConnection();
       $stmt = $db->prepare("UPDATE users SET token = :token, token_expired_at = :token_expired_at WHERE id = :id");
       return $stmt->execute([
            ':id' => $idUser,
            ':token' => $token,
            ':token_expired_at' => $expireDateTime
        ]);
    }

    public static function deleteToken ($idUser) { //elimina el token del usuario
       
       $db = DB::getConnection();
       $stmt = $db->prepare("UPDATE users SET token = :token, token_expired_at = :token_expired_at WHERE id = :id");
       return $stmt->execute([
            ':id' => $idUser,
            ':token' => NULL,
            ':token_expired_at' => NULL
        ]);
    }

      public static function getUser ($idUser) { //devuelve el usuario con tal id
      $db = DB::getConnection();
      $stmt = $db->prepare ("SELECT u.balance, u.is_admin FROM users u WHERE u.id = :id");
      $stmt->execute ([
      ':id' => $idUser
      ]);
      return $stmt->fetch(PDO::FETCH_ASSOC);
      }

      public static function updateBalance ($idUser, $balance) { //actualiza el saldo del usuario
       $db = DB::getConnection();
       $stmt = $db->prepare("UPDATE users SET balance = :balance WHERE id = :id");
       $stmt->execute([
            ':id' => $idUser,
            ':balance' => $balance
        ]);
      }

  

}

