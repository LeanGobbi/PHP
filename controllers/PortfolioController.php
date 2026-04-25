<?php

require_once __DIR__ . '/../models/Portfolio.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PortfolioController {

   public static function getPortfolioUser (Request $request, Response $response) {
     try {
        $idUser = $request->getAttribute('idUsuario');
        $assets = Portfolio::getPortfolios ($idUser);
        if (empty($assets)) {
            $response->getBody()->write(json_encode('El usuario no posee activos actualmente en su portfolio'));
        } else {
                foreach ($assets as $fila){
                    $resultado[] = [
                    "Nombre del activo" => $fila['name'],
                    "Valor total del portfolio" => $fila['TOTAL']
                    ];
                }
                $response->getBody()->write(json_encode($resultado));
        }
      } catch (PDOException $e) {
        $response = $response->withStatus(500);
        $response->getBody()->write(json_encode(['Error' => $e->getMessage()]));
      }
     return $response;
     }

     public static function deletePortfolioUser (Request $request, Response $response, array $args) {
       
       try {
          $idUser = $request->getAttribute('idUsuario');
          $portfolio = Portfolio::getPortfolio ($idUser, $args['asset_id']);
          if (empty($portfolio)) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(["Error" => 'El activo especificado no existe en el portfolio del usuario']));
          } else {
              
            if ($portfolio['quantity'] == 0){
                Portfolio::deletePortfolio ($portfolio['id']);
                $response = $response->withStatus(200);
                $response->getBody()->write(json_encode(['Confirmación' => 'Activo eliminado con éxito del portfolio del usuario']));
              } else {
                $response = $response->withStatus(409);
                $response->getBody()->write(json_encode(["Error" => 'No puedes quitar un activo de tu portfolio si aún tienes unidades. Debes venderlas primero']));

              }

          }
        } catch (PDOException $e) {
        $response = $response->withStatus(500);
        $response->getBody()->write(json_encode(['Error' => $e->getMessage()]));
        }
       return $response;
     }

}