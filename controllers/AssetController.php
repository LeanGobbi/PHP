<?php

require_once __DIR__ . '/../models/Asset.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AssetController {

    public static function getAssets (Request $request, Response $response) {
      try {
            $args = $request->getQueryParams();
            $assets = Asset::getAll($args);
            $resultado = [];
            foreach ($assets as $fila){
              $resultado[] = [
              "Nombre del activo" => $fila['name'],
              "Valor actual del activo" => $fila['current_price']
              ];
            }
            $response->getBody()->write(json_encode($resultado));
      } catch (PDOException $e) {
             $response = $response->withStatus(500);
             $response->getBody()->write(json_encode(['Error' => $e->getMessage()]));
      }

      return $response;
    }

    public static function variarPrecioPorTiempo($precioActual, $timestampUltimaVez, $volatilidadPorSegundo) {  
        // 1. Calcular cuántos segundos han pasado  
        $tiempoPasado = time() - strtotime($timestampUltimaVez); // Si no ha pasado tiempo, el precio no cambia  
        if ($tiempoPasado <= 0) return $precioActual;  
        // 2. Generar un cambio aleatorio (puede ser positivo o negativo)  
        // mt_rand(-100, 100) / 100 nos da un número entre -1.0 y 1.0  
        $direccion = mt_rand(-100, 100) / 100;  
        // 3. El cambio total depende del tiempo que pasó  
        $delta = $direccion * $volatilidadPorSegundo * $tiempoPasado;  
        
        $precio = $precioActual + $delta;
        //evito valores negativos
        $nuevoPrecio = max(10, $precio);

      return $nuevoPrecio;
    }

    public static function updateAssets (Request $request, Response $response){

    try {
          $userId = $request->getAttribute('idUsuario');
          $user = User::getUser($userId);
          if ($user['is_admin'] == 1) {
              $prices = Asset::GetPrices();
              $volatilidadPorSegundo = 0.05;
              foreach ($prices as $fila){
                $newPrice = self::variarPrecioPorTiempo ($fila['current_price'], $fila['last_update'], $volatilidadPorSegundo);
                Asset::updateAssetM ($newPrice, $fila['id']);
              }
              $response->getBody()->write(json_encode(["Confirmación" => 'Precios actualizados con éxito']));
          } else {
            $response = $response->withStatus(401);
            $response->getBody()->write(json_encode(["Error" => 'Solo el administrador está autorizado para realizar esta acción']));
          }
        
      } catch (PDOException $e) {
          $response = $response->withStatus(500);
          $response->getBody()->write(json_encode(['Error' => $e->getMessage()]));
      }
      return $response;

    }

    public static function getAssetsHistory (Request $request, Response $response, $args){
    try {

       $assets = Asset::getHistory($args);
       $resultado = [];
       if ($assets) {
            foreach ($assets as $fila){
              $resultado[] = [
              "Precio histórico del activo" => $fila['price_per_unit'],
              "Fecha histórica" => $fila['transaction_date']
              ];
            }
            $response->getBody()->write(json_encode($resultado));
       } else {
         $response->getBody()->write(json_encode('El activo no posee historial de precios hasta la fecha'));
       }
    } catch (PDOException $e) {
        $response = $response->withStatus(500);
        $response->getBody()->write(json_encode(['Error' => $e->getMessage()]));
    }
      return $response;
    }
}