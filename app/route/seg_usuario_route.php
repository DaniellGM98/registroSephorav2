<?php
	use App\Lib\Response,
		App\Lib\MiddlewareToken,
		PHPMailer\PHPMailer\PHPMailer,
		PHPMailer\PHPMailer\Exception,
		Slim\Http\UploadedFile;
		require_once './core/defines.php';

	$app->group('/seg_usuario/', function() use ($app) {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Ruta de seg_usuario');
		});

        // Obtener seg_usuario por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->seg_usuario->get($arguments['id']));
		});

        // Agregar seg_usuario
        $this->post('add/', function ($req, $res, $args) {
			$parsedBody = $req->getParsedBody();
			$idUsuario = $this->model->seg_usuario->add($parsedBody);
			return $res->withHeader('Content-type', 'application/json')->write(json_encode($idUsuario));
    	});

        // // Obtener todos los usuarios
		// $this->get('getAll/', function ($req, $res, $args) {
		// 	$usuarios = $this->model->seg_usuario->getAll();
		// 	return $res->withHeader('Content-type', 'application/json')->write(json_encode($usuarios));
    	// });

		// // Obtener todos los usuarios
		$this->get('getAll/', function ($req, $res, $args) {
			$usuarios = $this->model->seg_usuario->getAll();
			return $res
				->withHeader('Access-Control-Allow-Origin', '*')
				->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
				->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
				->withHeader('Content-type', 'application/json')
				->write(json_encode($usuarios));
		});		

		// Editar seg_usuario
		$this->post('edit/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$id = $arguments['id'];
			$seg_usuario = $this->model->seg_usuario->edit($parsedBody, $id);
			if($seg_usuario->response) {
				$seg_usuario->state = $this->model->transaction->confirmaTransaccion();
				return $response->withJson($seg_usuario);
			}else{
				$seg_usuario->state = $this->model->transaction->regresaTransaccion();
				return $response->withJson($seg_usuario); 
			}
		});

		// Eliminar seg_usuario
		$this->put('del/{id}', function ($req, $res, $args) {
			$this->response = new Response();
			$this->response->state = $this->model->transaction->iniciaTransaccion();
			$resultado = $this->model->seg_usuario->del($args['id']);
			if(!$resultado){
				$this->model->transaction->regresaTransaccion();
				return $this->response->withJson($add);
			}
			$this->response->state = $this->model->transaction->confirmaTransaccion();
	        return $res->withHeader('Content-type', 'application/json')
	                   ->write(json_encode($resultado));
	    });
		
		// Inicio de sesion app
		$this->post('app/login/', function ($req, $res, $args) {
			$parsedBody= $req->getParsedBody();
			$email= $parsedBody['email'];
			$pass = $parsedBody['password'];
			$resultado = $this->model->seg_usuario->login($email, $pass);
			if(is_object($resultado->result)){
				//$resultado->result = 0;
				//$resultado->SetResponse(true,'Acceso correcto');
			}else{
				$resultado->result = (object)[];
				$resultado->SetResponse(false,'Verifique sus datos');
			}
			return json_encode($resultado);
   		});

	});	
?>