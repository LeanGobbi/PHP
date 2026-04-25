<?php

require_once __DIR__ . '/../models/Transaction.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TransactionController {

   public static function buyAsset (Request $request, Response $response) {
    try {

      $data = $request->getParsedBody();
      if ((isset ($data['asset_id'])) && (isset($data['quantity']))) {
         if ((filter_var($data['quantity'], FILTER_VALIDATE_INT)) !== false && ($data['quantity'] > 0)) {
            $asset = Asset::getAsset ($data);
            if ($asset) {
                $idUser = $request->getAttribute('idUsuario');
                $user = User::getUser ($idUser);
                if ($user['balance'] >= ($asset['current_price'] * $data['quantity'])) {

                    $newBalance = $user['balance'] - ($asset['current_price'] * $data['quantity']);
                    User::updateBalance ($idUser, $newBalance);
                    $portfolio = Portfolio::getPortfolio($idUser, $data['asset_id']);

                    if ($portfolio) {
                    Portfolio::updatePortfolio ($portfolio['id'], $data['quantity']);
                    } else {
                    Portfolio::insertPortfolio ($idUser, $data);
                    }

                    $typeTransaction = 'buy';
                    Transaction::insertTransaction($idUser, $data, $typeTransaction, $asset['current_price']);
                    $response = $response->withStatus(200);
                    $response->getBody()->write(json_encode(['Confirmación' => 'El activo ha sido comprado con éxito']));
            
                } else {
                $response = $response->withStatus(409);
                $response->getBody()->write(json_encode(["Error" => 'El usuario no posee suficiente saldo para realizar la compra']));
                }

            } else {
                    $response = $response->withStatus(400);
                    $response->getBody()->write(json_encode(["Error" => 'El id del activo ingresado no existe. Intente nuevamente']));
            }

         } else {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(["Error" => 'La cantidad ingresada a comprar debe ser mayor a 0. Intente nuevamente']));
         }
       
    } else {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(["Error" => 'Los campos id y cantidad del activo deben estar completados. Intente nuevamente']));
    }

      } catch (PDOException $e) {
        $response = $response->withStatus(500);
        $response->getBody()->write(json_encode(['Error' => $e->getMessage()]));
      }
      return $response;
   }

   
   public static function sellAsset (Request $request, Response $response) {
    try {
      $data = $request->getParsedBody();
      if ((isset ($data['asset_id'])) && (isset($data['quantity']))) {
         if ((filter_var($data['quantity'], FILTER_VALIDATE_INT)) !== false && ($data['quantity'] > 0)) {
            $asset = Asset::getAsset ($data);
            if ($asset) {
                 $idUser = $request->getAttribute('idUsuario');
                 $user = User::getUser ($idUser);
                 $portfolio = Portfolio::getPortfolio($idUser, $data['asset_id']);
                if ($portfolio && ($portfolio['quantity'] >= $data['quantity'])) {
                    
                    $newBalance = $user['balance'] + ($asset['current_price'] * $data['quantity']);
                    User::updateBalance ($idUser, $newBalance);
                    Portfolio::updatePortfolio ($portfolio['id'], -$data['quantity']);
                    $typeTransaction = 'sell';
                    Transaction::insertTransaction($idUser, $data, $typeTransaction, $asset['current_price']);
                    $response = $response->withStatus(200);
                    $response->getBody()->write(json_encode(['Confirmación' => 'El activo ha sido vendido con éxito']));
            
                } else {
                $response = $response->withStatus(409);
                $response->getBody()->write(json_encode(["Error" => 'El usuario no posee suficientes unidades del activo para realizar la venta']));
                }

            } else {
                    $response = $response->withStatus(400);
                    $response->getBody()->write(json_encode(["Error" => 'El id del activo ingresado no existe. Intente nuevamente']));
            }

         } else {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(["Error" => 'La cantidad ingresada a vender debe ser mayor a 0. Intente nuevamente']));
         }
       
    } else {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(["Error" => 'Los campos id y cantidad del activo deben estar completados. Intente nuevamente']));
    }

      } catch (PDOException $e) {
        $response = $response->withStatus(500);
        $response->getBody()->write(json_encode(['Error' => $e->getMessage()]));
      }
      return $response;
   }



 public static function getTransactions (Request $request, Response $response) {

    $idUser = $request->getAttribute('idUsuario');
    $args = $request->getQueryParams();
    $transactions = Transaction::getTransaction ($idUser, $args);
    if (empty($transactions)) {
         $response->getBody()->write(json_encode('El usuario no posee historial de movimientos a la fecha'));
    } else {
        foreach ($transactions as $fila){
           $resultado = [
          "Id del activo" => $fila['asset_id'],
          "Tipo de transacción" => $fila['transaction_type'],
          "Cantidad total" => $fila['quantity'],
          "Precio por unidad" => $fila['price_per_unit'],
          "Precio total" => $fila['total_amount'],
          "Fecha del movimiento" => $fila['transaction_date'],
          ];
         $response->getBody()->write(json_encode($resultado));
        }
      }
   return $response;


 }
}