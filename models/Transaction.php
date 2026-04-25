<?php

class Transaction {
 
       public static function insertTransaction ($idUser, array $data, $type, $price)
    {
       $db = DB::getConnection();
       $stmt = $db->prepare("INSERT INTO transactions (user_id, asset_id, transaction_type, quantity, price_per_unit, total_amount) VALUES (:user_id, :asset_id, :transaction_type, :quantity, :price_per_unit, :price_per_unit * :quantity)");
       $stmt->execute([
            ':user_id' => $idUser,
            ':asset_id' => $data['asset_id'],
            ':transaction_type' => $type,
            ':quantity' => $data['quantity'],
            ':price_per_unit' => $price
       ]);
    }

    public static function getTransaction ($idUsuario, array $args) {


        $db = DB::getConnection();
        $sql = "SELECT t.asset_id, t.transaction_type, t.quantity, t.price_per_unit, t.total_amount, t.transaction_date FROM transactions t WHERE user_id = :user_id";
        $params = [];
        $params[':user_id'] = $idUsuario;

        if (isset($args['type'])) {
            $sql .= " AND t.transaction_type = :type";
            $params[':type'] = $args['type'];

        }
        if (isset($args['asset_id'])) {
            $sql .= " AND t.asset_id = :asset_id";
            $params[':asset_id'] = $args['asset_id'];
        }
        $sql .= " ORDER BY t.transaction_date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


}


            