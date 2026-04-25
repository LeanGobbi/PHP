<?php

require_once __DIR__ . '/../models/User.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;

class UserController {

    public static function getUsers (Request $request, Response $response) {
        try {
            $users = User::getAll();
            if ($users) {
                $resultado = [];
                foreach ($users as $fila){
                  $resultado[] = [
                  "Nombre del usuario" => $fila['name'],
                  "Valor total del portfolio" => $fila['TOTAL']
                  ];
                }
                $response->getBody()->write(json_encode($resultado));
            } else {
            $response->getBody()->write(json_encode(["No hay usuarios registrados hasta el momento"]));
            }
        } catch (PDOException $e) {
             $response = $response->withStatus(500);
             $response->getBody()->write(json_encode(['Error' => $e->getMessage()]));
        }

      return $response;
    }

    public static function getUser (Request $request, Response $response, array $args) {
        try {
            $user = User::getUserInfo ($args);
            if (empty($user)) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(["Error" => "Usuario no encontrado"]));
            } else {
              $resultado = [
              "Nombre del usuario" => $user['name'],
              "Email del usuario" => $user['email'],
              "Saldo actual del usuario" => $user['balance'],
              "Valor total del portfolio" => $user['TOTAL']
              ];
            $response->getBody()->write(json_encode($resultado));
          }
        } catch (PDOException $e) {
             $response = $response->withStatus(500);
             $response->getBody()->write(json_encode(['Error' => $e->getMessage()]));
        }
      return $response;
    }

    public static function validateInfo (array $data) {
         $errores = [];

         if ((isset ($data['email'])) && (!str_contains(($data['email']), '@'))) {
          $errores[] = "El campo 'email' debe contener el caracter '@'";
        }
        
        if ((isset ($data['password'])) && (!preg_match('/[a-z]/', ($data['password'])))) {
          $errores[] = "El campo 'contraseña' debe tener al menos una letra minúscula.";
        }

        if ((isset ($data['password'])) && (!preg_match('/[A-Z]/', ($data['password'])))) {
          $errores[] = "El campo 'contraseña' debe tener al menos una letra mayúscula";
        }
            
        if ((isset ($data['password'])) && (!preg_match('/\d/', ($data['password'])))) {
          $errores[] = "El campo 'contraseña' debe tener al menos un número.";
        }

        if ((isset ($data['password'])) && (!preg_match('/[\W_]/', ($data['password'])))) {
          $errores[] = "El campo 'contraseña' debe tener al menos un caracter especial.";
        }

        if ((isset ($data['password'])) && (strlen($data['password']) < 8)) {
           $errores[] = "El campo 'contraseña' debe tener al menos 8 caracteres.";
        }

        if ((isset ($data['name'])) && (empty(trim($data['name'])))) {
          $errores[] = "El campo 'nombre' es obligatorio. No puede estar vacío.";
        } 

        if ((isset ($data['name'])) && ((preg_match('/\d/', ($data['name']))) || (preg_match('/[\W_]/', ($data['name']))))) {
           $errores[] = "El campo 'nombre' solo debe tener letras. No se permiten espacios";
        }

        return $errores;

    }

    public static function insertUser (Request $request, Response $response) {

        $data = $request->getParsedBody();
        try {
            if ((isset ($data['name'])) && (isset($data['email'])) && (isset($data['password']))) {
                $errores = self::ValidateInfo($data);
                if (empty ($errores)){
                      User::insertUserM ($data);
                      $response = $response->withStatus(200);
                      $response->getBody()->write(json_encode(['Confirmación' => 'Usuario creado con éxito']));
                } else {
                      $response = $response->withStatus(400);
                      $response->getBody()->write(json_encode(["Error" => $errores]));
                }
            } else {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(["Error" => 'Los campos nombre, email y contraseña deben estar completados. Intente nuevamente']));
            }
         } catch (PDOException $e) {
             $response = $response->withStatus(500);
             $response->getBody()->write(json_encode(['Error' => $e->getMessage()]));
         }
        
      return $response;
    }
    
    public static function updateUser (Request $request, Response $response, array $args) {

          $data = $request->getParsedBody();
          try {
            if ((isset ($data['name'])) || (isset($data['email'])) || (isset($data['password']))) {
                $userId = $request->getAttribute('idUsuario');
                if ($args ['user_id'] == $userId) {
                    $errores = self::ValidateInfo($data);
                    if (empty ($errores)){
                        $stmt = User::updateUserM ($data, $userId);
                        if ($stmt->rowCount() > 0) {
                            $response->getBody()->write(json_encode(['Confirmación' => 'Usuario actualizado con éxito']));
                        } else {
                            $response = $response->withStatus(404);
                            $response->getBody()->write(json_encode(['Error' => 'No se realizaron cambios']));
                        }
                    } else {
                          $response = $response->withStatus(400);
                          $response->getBody()->write(json_encode(["Error" => $errores]));
                    }
                } else {
                          $response = $response->withStatus(401);
                          $response->getBody()->write(json_encode(["Error" => 'El usuario logueado no puede realizar esta acción']));
                }
            } else {
                $response = $response->withStatus(400);
                $response->getBody()->write(json_encode(["Error" => 'Tiene que haber al menos un campo completado para poder realizar la modificación. Intente nuevamente']));
            }
      } catch (PDOException $e) {
          $response = $response->withStatus(500);
          $response->getBody()->write(json_encode(['Error' => $e->getMessage()]));
      }
        return $response;
    }

    	public static function login (Request $request, Response $response) {
 
          //Verificar en la BD si el usuario con la contraseña existe
          $data = $request->getParsedBody();
          if ((isset($data['email'])) && (isset($data['password']))) {
              $usuario = User::UserExist ($data);
              if ($usuario) {
                  date_default_timezone_set('America/Argentina/Buenos_Aires');
                  //agrego 5 minutos más a su token original
                  $expire = time() + (5 * 60);
                  //El token se arma con el id de usuario y la fecha de expiración
                  $token = JWT::encode([
                    "idUsuario" => $usuario['id'],
                    "exp" => $expire
                ], IsLoggedMiddleware::$secret, 'HS256');
                  $expireString = date("Y-m-d H:i:s", $expire);
                  $success = User::addToken($expireString, $token, $usuario['id']);
                  $response = $response->withHeader('Authorization', 'Bearer ' . $token);
                  if ($success) {
                  $response->getBody()->write(json_encode(['Confirmación' => 'El usuario ha sido logueado exitosamente']));
                  $response->withStatus(200);
                  }
              } else {
              $response->getBody()->write(json_encode(['Error' => 'Email o contraseña incorrectos. Intente nuevamente']));
              $response->withStatus(404);
              }
          } else {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(["Error" => 'Los campos email y contraseña deben estar completados. Intente nuevamente']));
          }
          
          return $response;
    }

    public static function logout (Request $request, Response $response) {

      $userId = $request->getAttribute('idUsuario');
      $success = User::deleteToken($userId);
      
        if ($success) {
          $response->getBody()->write(json_encode(['Confirmación' => 'El usuario ha sido deslogueado exitosamente']));
          $response->withStatus(200);
          } else {
                  $response->getBody()->write(json_encode(['Error' => 'No pudo desloguearse el usuario.']));
                  $response->withStatus(404);
          }

     return $response;

    }
}
