<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;
	use Slim\Http\UploadedFile;
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	require '../vendor/autoload.php';

	class SegUsuarioModel {
		private $db;
		private $table = 'seg_usuario';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
			require_once './core/defines.php';
		}

		
        // Obtener usuario por id
		public function get($id) {
			$usuario = $this->db
				->from($this->table)
				->where('id', $id)
				->where('status', 1)
				->fetch();
			if($usuario) {
				unset($usuario->password);
				$this->response->result = $usuario;
				$this->response->SetResponse(true);
			}
			else{
                $this->response->SetResponse(false, 'No existe el registro');
            }
			return $this->response;
		}

        // Obtener todos los usuarios
		public function getAll() {
			$usuario = $this->db
			->from($this->table)
			->where("status", 1)
			->orderBy("id ASC")
			->fetchAll();
			if($usuario) {
				unset($usuario->password);
				$this->response->result = $usuario;
				$this->response->SetResponse(true); 
			}else { 
				$this->response->SetResponse(false, 'No hay usuarios registrados'); 
			}
			return $this->response;
		}

		// Obtener todos los usuarios
		public function getAllByTipo($tipo) {
			$usuario = $this->db
			->from($this->table)
			->select(null)->select("id, nombre, nombre_encargado, zona")
			->where("tipo", intval($tipo))
			->where("status", 1)
			//->orderBy("nombre ASC, nombre_encargado ASC")
			->fetchAll();
			if($usuario) {
				unset($usuario->password);
				$this->response->result = $usuario;
				$this->response->SetResponse(true); 
			}else { 
				$this->response->SetResponse(false, 'No hay usuarios registrados'); 
			}
			return $this->response;
		}

        // Agregar usuario
		public function add($data){
			date_default_timezone_set('America/Mexico_City');
			$data['password'] = strrev(md5(sha1($data['password'])));
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$resultado = $this->db
                ->insertInto($this->table, $data)
                ->execute();
			}catch(\PDOException $ex){
				$this->response->result = $resultado;
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, 'catch: Add model usuario');	
			}
			if($resultado!=0){
				$this->response->result = $resultado;
				return $this->response->SetResponse(true, 'Agregado exitosamente');    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, 'No se inserto el registro');
			}	
		}

		//	Editar usuario
		public function edit($data, $id) {
			date_default_timezone_set('America/Mexico_City');
			if(isset($data['password'])) $data['password'] = strrev(md5(sha1($data['password'])));
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			$query = $this->db
				->update('seg_usuario')
				->set($data)
				->where('id', $id);
			$response = $query->execute();
			return (object)[
				'result' => $response,
				'response' => $response == 1 ? true : false,
				'message' => $response ? 'Editado exitosamente' : 'Error al actualizar',
			];
		}
		

		// Eliminar usuario
		public function del($id){
			$set = array('status' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('id', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		// inicio de sesión
		public function login($email, $password) {
			$password = strrev(md5(sha1($password)));
			$usuario = $this->db
				->from($this->table)
				->where('email', $email)
				->where('password', $password)
				->where('status', 1)
				->fetch();
			if(is_object($usuario)) {
				unset($usuario->password);
				$this->ultimoAcceso($usuario->id);
				$this->response->SetResponse(true, 'Acceso correcto,'+$usuario->tipo+','+$usuario->nombre+','+$usuario->nombre_encargado);
			} else {
				$this->response->SetResponse(false, 'Verifique sus datos');
			}
			$this->response->result = $usuario;
			return $this->response;
		}

		// Modificar ultimo acceso
		public function ultimoAcceso($id) {
			date_default_timezone_set('America/Mexico_City');
			$data['ultimo_acceso'] = date("Y-m-d H:i:s");
			try {
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id', $id)
					->execute();
				if($this->response->result!=0) { 
					$this->response->SetResponse(true, 'Id actualizado: '.$id);
				} else { 
					$this->response->SetResponse(false, 'No se edito el registro');
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model $this->table");
			}
			return $this->response;
		}

	}
?>