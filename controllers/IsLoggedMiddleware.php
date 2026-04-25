<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseFactoryInterface;

class IsLoggedMiddleware implements Middleware
{
    private ResponseFactoryInterface $responseFactory;
    
    public static $secret = 'mi_clave_super_secreta_larga_123456789';

    public function __construct()
    {
        $this->responseFactory = new \Slim\Psr7\Factory\ResponseFactory();
    }
    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
	//Este Middleware realiza chequeos del token
	try {
	    //Busco que el header tenga la clave "Authorization" que es donde viaja el token
	    //Si este no existe, la ejecución se detiene y envía el mensaje correspondiente
            if ($request->hasHeader("Authorization")){
                $token = $request->getHeaderLine("Authorization");

                if (!empty($token)){
		    //Creo una Key, usando una palabra secreta y un algoritmo
			        $token = str_replace("Bearer ", "", $token);
                    $key = new Key(self::$secret, "HS256");
		    //Con la key puedo abrir/decodificar el token recibido y extraer los datos de usuarioId y la fecha de expiración
                    $dataToken = JWT::decode($token, $key);
					$request = $request->withAttribute('idUsuario', $dataToken->idUsuario);
                    date_default_timezone_set('America/Argentina/Buenos_Aires');
                    //agrego 5 minutos mas a su token original
					$newExpire = time() + (5 * 60);
		            
                    $newToken = JWT::encode([
                        "idUsuario" => $dataToken->idUsuario,
                        "exp" => $newExpire
                    ], self::$secret, 'HS256');
                    
                    $expireString = date("Y-m-d H:i:s", $newExpire);

                    User::addToken($expireString, $newToken, $dataToken->idUsuario);

					$response = $handler->handle($request);
                    return $response->withHeader('Authorization', 'Bearer ' . $newToken);
		    }
		}
	    //Si el token no existe, indico que la acción que quiere realizar requiere login
	    $response = $this->responseFactory->createResponse();
	    $response->getBody()->write(json_encode(["error"=>'Acción requiere login']));
	    $response->withHeader("Content-Type", "application/json");
	    return $response->withStatus(401);

        } catch (\Firebase\JWT\ExpiredException $e) {

            $response = $this->responseFactory->createResponse();
            $response->getBody()->write(json_encode(["error"=>'El token está vencido']));
            return $response->withHeader("Content-Type", "application/json")->withStatus(401);

        } catch (\Exception $e) {

            $response = $this->responseFactory->createResponse();
            $response->getBody()->write(json_encode(["error"=>'El token es inválido']));
            return $response->withHeader("Content-Type", "application/json")->withStatus(401);
        }
    }
}

