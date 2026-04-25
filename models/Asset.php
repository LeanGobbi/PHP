<?php

class Asset {
    public static function getAll($args)
    {
        $db = DB::getConnection();
        $sql = "SELECT a.name, a.current_price FROM assets a WHERE 1=1";
        $params = [];

        if (isset($args['type'])) {
            $sql .= " AND a.name = :type";
            $params[':type'] = $args['type'];
        }

        if (isset($args['min_price'])) {
            $sql .= " AND a.current_price >= :min_price";
            $params[':min_price'] = $args['min_price'];
        }

        if (isset($args['max_price'])) {
            $sql .= " AND a.current_price <= :max_price";
            $params[':max_price'] = $args['max_price'];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getPrices (){
      $db = DB::getConnection();
      $stmt = $db->query ("SELECT a.id, a.current_price, a.last_update FROM assets a");
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateAssetM ($newPrice, $id){
        $db = DB::getConnection();
        $stmt = $db->prepare("UPDATE assets SET current_price = :current_price, last_update =  NOW() WHERE id = :id");
        $stmt->execute([
            ':id' => $id,
            ":current_price" => $newPrice ]);
    }

    
    public static function getHistory ($args) {
      $db = DB::getConnection();
      $limit = (int) min($args['quantity'], 5);
      $stmt = $db->prepare 
      ("SELECT t.price_per_unit, t.transaction_date 
      FROM transactions t 
      WHERE t.asset_id = :asset_id
      ORDER BY t.transaction_date DESC
      LIMIT $limit");
      $stmt->execute([
        ':asset_id' => $args['asset_id'] ]);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

      public static function getAsset (array $data) {
      $db = DB::getConnection();
      $stmt = $db->prepare ("SELECT a.current_price FROM assets a WHERE a.id = :id");
      $stmt->execute ([
      ':id' => $data['asset_id']
      ]);
      return $stmt->fetch(PDO::FETCH_ASSOC);
      }

}