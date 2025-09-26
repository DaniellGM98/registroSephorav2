<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;
	use Slim\Http\UploadedFile;
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	
	class RegistroModel {
		private $db;
		private $table = 'registro';
		private $tableH = 'registro_historial';
		private $tableC = 'codigo';
		private $tableP = 'premio';
		private $tableU = 'seg_usuario';
		private $response;	

		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}
		

		// Obtener los datos de registro por medio del ID
		public function get($fk_codigo, $fk_seg_usuario) {
			$this->response->result = $this->db
				->from($this->table)
				->where("$this->table.fk_codigo", $fk_codigo)
				->where("$this->table.fk_seg_usuario", $fk_seg_usuario)
				->where("$this->table.status", 1)
				->fetch();
			if($this->response->result) {
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// Obtener los datos de registro por medio del codigo
		public function getByCodigo($codigo) {
			$this->response->result = $this->db
				->from($this->tableC)
				->where("$this->tableC.codigo", $codigo)
				->where("$this->tableC.status", 1)
				->fetch();
			if($this->response->result) {
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// Obtener los datos de registro por medio del fk_seg_usuario
		public function getByFkSegUsuario($fk_seg_usuario, $fechaInicio, $fechaFin) {
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id, $this->table.fk_codigo, $this->table.fk_seg_usuario, $this->table.fecha_entrada, $this->table.fecha_salida, $this->table.tiempo_evento, $this->table.status, $this->tableC.codigo")
				->innerJoin($this->tableC." ON $this->table.fk_codigo = $this->tableC.id")
				->where("$this->table.fk_seg_usuario", $fk_seg_usuario)
				->where("$this->table.fecha_salida IS NOT NULL")
				->where("DATE($this->table.fecha_entrada) BETWEEN ? AND ?", [$fechaInicio, $fechaFin])
				->where("$this->table.status", 1)
				->orderBy('fecha_entrada DESC')
				->fetchAll();
			if($this->response->result) {
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// Obtener los codigos utilizados en registro
		public function getCodigosUsados() {
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id")
				->where("$this->table.fecha_salida IS NULL")
				->where("$this->table.status", 1)
				->fetchAll();
			if($this->response->result) {
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// Obtener los datos de registro por medio del ID
		public function getCountVisitas($fk_codigo) {
		    $sql = "SELECT COUNT(DISTINCT zona) AS total
                    FROM {$this->table}
                    WHERE fk_codigo = :fk
                      AND fecha_salida IS NOT NULL
                      AND status = 1";
            $stmt = $this->db->getPdo()->prepare($sql);
            $stmt->execute([':fk' => $fk_codigo]);
            $this->response->result = (int) $stmt->fetchColumn();
			if($this->response->result) {
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// Obtener los datos de registro por medio del ID
		public function getStandsVisitados($fk_codigo) {
		    $sql = "SELECT DISTINCT zona
					FROM {$this->table}
					WHERE fk_codigo = :fk
					AND fecha_salida IS NOT NULL
					AND status = 1";

			$stmt = $this->db->getPdo()->prepare($sql);
			$stmt->execute([':fk' => $fk_codigo]);
			// array de zonas
			$zonas = $stmt->fetchAll(\PDO::FETCH_COLUMN);
			$this->response->result = $zonas;
			if($this->response->result) {
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// Obtener total de los registros
		public function getVisitasGeneral($seg_usuario, $fechaInicio, $fechaFin) {
			$this->response->result = $this->db
				->from($this->table)
				->where("$this->table.fk_seg_usuario", $seg_usuario)
				//->where("DATE($this->table.fecha_entrada)", $fecha)
				->where("DATE($this->table.fecha_entrada) BETWEEN ? AND ?", [$fechaInicio, $fechaFin])
				->where("$this->table.fecha_salida IS NULL")
				->where("$this->table.status", 1)
				->count();
			if($this->response->result) {
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// Obtener total de los registros
		public function getVisitasDemo($seg_usuario, $fechaInicio, $fechaFin) {
			$this->response->result = $this->db
				->from($this->table)
				->where("$this->table.fk_seg_usuario", $seg_usuario)
				// ->where("DATE($this->table.fecha_entrada)", $fecha)
				->where("DATE($this->table.fecha_entrada) BETWEEN ? AND ?", [$fechaInicio, $fechaFin])
				->where("$this->table.fecha_salida IS NOT NULL")
				->where("$this->table.status", 1)
				->count();
			if($this->response->result) {
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// Obtener visitas por ID
		public function getEstadisticaByID($id) {
			$result = $this->db
				->from($this->table)
				->where("$this->table.fk_seg_usuario", $id)
				->where("$this->table.fecha_salida IS NULL")
				->where("$this->table.status", 1)
				->count();
		
			$result2 = $this->db
				->from($this->table)
				->where("$this->table.fk_seg_usuario", $id)
				->where("$this->table.fecha_salida IS NOT NULL")
				->where("$this->table.status", 1)
				->count();
		
			if ($result >= 0) {
				$this->response->result = [
					"general" => $result,
					"demo" => $result2,
					"total" => ($result + $result2)
				];
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}		

		// Obtener los datos de cuantos tienen premio
		// public function getCountPremios($fechaInicio, $fechaFin) {
		// 	$sql = "
		// 		SELECT COUNT(*) AS total
		// 		FROM (
		// 			SELECT fk_codigo
		// 			FROM {$this->table}
		// 			WHERE fecha_salida IS NOT NULL
		// 			AND DATE(fecha_entrada) BETWEEN :fechaInicio AND :fechaFin
		// 			AND status = 1
		// 			GROUP BY fk_codigo
		// 			HAVING COUNT(*) >= 7
		// 		) AS sub
		// 	";
		
		// 	$stmt = $this->db->getPdo()->prepare($sql);
		// 	$stmt->execute([
		// 		':fechaInicio' => $fechaInicio,
		// 		':fechaFin' => $fechaFin,
		// 	]);
		
		// 	return $stmt->fetch();
		// }
		public function getCountPremios($fechaInicio, $fechaFin) {
			$rows = $this->db
			->from($this->tableP)
			->select('fk_codigo') // solo queremos el campo a agrupar
			->where("DATE($this->tableP.fecha_entrega) BETWEEN ? AND ?", [$fechaInicio, $fechaFin])
			->where("{$this->tableP}.status", 1)
			->groupBy('fk_codigo')
			->fetchAll();
			$this->response->result = count($rows);
			
			if($this->response->result) {
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// contar numero de brazaletes
		public function getCountBrazaletes($fechaInicio, $fechaFin) {
			$rows = $this->db
			->from($this->table)
			->select('fk_codigo') 
			->where("DATE($this->table.fecha_entrada) BETWEEN ? AND ?", [$fechaInicio, $fechaFin])
			->where("{$this->table}.status", 1)
			->groupBy('fk_codigo')
			->fetchAll();
			$this->response->result = count($rows);
			
			if($this->response->result) {
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// obtener id a quien se le ha entregado premio
		public function getPremioEntregado($fk_codigo) {
			$this->response->result = $this->db
				->from($this->tableP)
				->where("$this->tableP.fk_codigo", $fk_codigo)
				->where("$this->tableP.status", 1)
				->fetch();
			if($this->response->result) {
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// Obtener los datos de los registro
		public function getAll() {
			$this->response->result = $this->db
				->from($this->table)
				->where('status', 1)
				->orderBy('fecha_entrada ASC')
				->fetchAll();
			return $this->response->SetResponse(true);
		}

		// Obtener todos los stand visitados por codigo
		public function getAllByCodigo($fk_codigo) {
			$registros = $this->db
				->from($this->table)
				->select(null)->select("$this->tableU.nombre, $this->table.fecha_entrada, $this->table.fecha_salida, $this->table.tiempo_evento, $this->table.zona")
				->innerJoin($this->tableC." ON $this->table.fk_codigo = $this->tableC.id")
				->innerJoin($this->tableU." ON $this->table.fk_seg_usuario = $this->tableU.id")
				->where("$this->table.fk_codigo", $fk_codigo)
				->where("$this->table.status", 1)
				->orderBy("$this->table.fecha_entrada Desc")
				->fetchAll();
			if($registros) {
				$this->response->result = $registros;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, "No existe el registro en ".$this->table);
			}
		}

		// Agregar registro
		public function add($data){
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
				return $this->response->SetResponse(true, 'Id del registro: '.$resultado);    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, 'No se inserto el registro');
			}	
		}

		//	agregar a historial
		public function addHistorial($data) {
			try {
				$this->response->result = $this->db
					->insertInto($this->tableH, $data)
					->execute();
				if($this->response->result != 0){
					$this->response->SetResponse(true, 'id del registro: '.$this->response->result);
				}else { 
					$this->response->SetResponse(false, 'No se inserto el registro'); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Add model $this->table");
			}
			return $this->response;
		}
		
		//	agregar premio
		public function addPremio($data) {
			try {
				$this->response->result = $this->db
					->insertInto($this->tableP, $data)
					->execute();
				if($this->response->result != 0){
					$this->response->SetResponse(true, 'id del registro: '.$this->response->result);
				}else { 
					$this->response->SetResponse(false, 'No se inserto el registro'); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Add model $this->tableP");
			}
			return $this->response;
		}

		// Modificar un registro
		public function edit($data, $id) {
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id', $id)
					->execute();
				if($this->response->result!=0) { 
					$this->response->SetResponse(true, "id actualizado: $id"); 
				}else { 
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