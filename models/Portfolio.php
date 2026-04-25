<?php

class Portfolio {
     
      public static function getPortfolio ($idUsuario, $idAsset) {
      $db = DB::getConnection();
      $stmt = $db->prepare ("SELECT p.id, p.quantity FROM portfolio p WHERE user_id = :user_id AND asset_id = :asset_id");
      $stmt->execute ([
      ':user_id' => $idUsuario,
      ':asset_id' => $idAsset
      ]);
      return $stmt->fetch(PDO::FETCH_ASSOC);
      }

      public static function getPortfolios ($idUsuario) {
        $db = DB::getConnection();
        $stmt = $db->prepare ("SELECT a.name, COALESCE(SUM(p.quantity * a.current_price), 0) as TOTAL 
        FROM portfolio p
        INNER JOIN assets a ON p.asset_id = a.id 
        WHERE user_id = :user_id
        GROUP BY a.id");
        $stmt->execute ([
        ':user_id' => $idUsuario,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

    public static function insertPortfolio ($idUsuario, array $data) {

         $db = DB::getConnection();
         $stmt = $db->prepare("INSERT INTO portfolio (user_id, asset_id, quantity) VALUES (:user_id, :asset_id, :quantity)");
         $stmt->execute([
            ':user_id' => $idUsuario,
            ':asset_id' => $data['asset_id'],
            ':quantity' => $data['quantity']
       ]);
    }

public static function updatePortfolio ($idPortfolio, $cantidad) {
        $db = DB::getConnection();
        $stmt = $db->prepare("UPDATE portfolio SET quantity = quantity + :quantity  WHERE id = :id");
        $stmt->execute([
            ':id' => $idPortfolio,
            ':quantity' => $cantidad
        ]);
      }


      public static function deletePortfolio ($idPortfolio) {
        $db = DB::getConnection();
        $stmt = $db->prepare("DELETE FROM portfolio WHERE id = :id");
        $stmt->execute([':id' => $idPortfolio]);
      }
}


