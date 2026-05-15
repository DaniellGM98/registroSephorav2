<?php
	use App\Lib\Response,
		App\Lib\MiddlewareToken,
		//App\Lib\CorsMiddleware,
		PHPMailer\PHPMailer\PHPMailer,
		PHPMailer\PHPMailer\Exception,
		Slim\Http\UploadedFile;
	require_once './core/defines.php';

	error_reporting(0);

	// $mwApiKey = function($request, $response, $next){
	// 	$apiKey = $request->getHeaderLine('apiKey');
	// 	$response = $next($request, $response);
	// 	return $response;
	// };

	$app->group('/registro/', function () {
		$this->get('', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'text/html')->write('Soy ruta de registro de V2 '.SITE_NAME);
		});
		
		// Ruta para obtener los datos de registro por medio del ID
		$this->get('get/{codigo}/{fk_seg_usuario}', function ($req, $res, $args) {
			$resultado = $this->model->registro->get($args['codigo'], $args['fk_seg_usuario']);
			return $res->withJson($resultado);
		});

		// Ruta para obtener los datos de los registro
		$this->get('getAll/', function ($req, $res, $args) {
			$resultado = $this->model->registro->getAll();
			return $res->withJson($resultado);
			// return $res
			// 	->withHeader('Access-Control-Allow-Origin', '*')
			// 	->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
			// 	->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
			// 	->withHeader('Content-type', 'application/json')
			// 	->write(json_encode($res));
		});

		// Obtener todos los stand visitados por codigo
		$this->get('getAllByCodigo/{fk_codigo}', function ($req, $res, $args) {
			$this->model->transaction->iniciaTransaccion();			
			$checkCode = $this->model->registro->getByCodigo($args['fk_codigo']);
			if($checkCode->response){
				$idCode = $checkCode->result->id;
				$registros = $this->model->registro->getAllByCodigo($idCode);
				if($registros->response){
					$registros->state = $this->model->transaction->confirmaTransaccion(); 
					return $res->withJson($registros);
				}else{
					$registros->state = $this->model->transaction->regresaTransaccion();
					return $res->withJson($registros->setResponse(false, 'No hay registros'));
				}
			}else{
				$checkCode->result = 0;
				$checkCode->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($checkCode->setResponse(false, 'QR incorrecto'));
			}	
    	});

		// ruta para contar las visitas de diferentes zonas y con salida marcada
    	$this->get('getCountVisitas/{idcodigo}', function ($req, $res, $args) {
			$resultado = $this->model->registro->getCountVisitas($args['idcodigo']);
			return $res->withJson($resultado);
		});

		// Ruta para obtener total de cafés entregados
		$this->get('getCountCafe/', function ($req, $res, $args) {
			$resultado = $this->model->registro->getCountCafe();
			return $res->withJson($resultado);
		});

		// Ruta para obtener los datos de registro por medio del ID que cuentan con premio CON ZONAS
		/*$this->get('getPremio/{codigo}', function ($req, $res, $args) {
			$this->model->transaction->iniciaTransaccion();			
			$checkCode = $this->model->registro->getByCodigo($args['codigo']);
			if($checkCode->response){
				$idCode = $checkCode->result->id;
				$checkPremio = $this->model->registro->getPremioEntregado($idCode);
				if($checkPremio->response){
					$checkPremio->result = 0;
					$checkPremio->state = $this->model->transaction->regresaTransaccion();
					return $res->withJson($checkCode->setResponse(false, 'Premio ya fue entregado'));
				}else{
					$resultado = $this->model->registro->getCountVisitas($idCode);
					if($resultado->response){
						$visitas = $resultado->result;
						if($visitas >= 6) {
							$data = array(
								'fk_codigo' => $idCode,
							);
							$resultado2 = $this->model->registro->addPremio($data);
							if(!$resultado2->response){
								$resultado2->result = 0;
								$resultado2->state = $this->model->transaction->regresaTransaccion();
								return $res->withJson($resultado2->setResponse(false, 'Ocurrio algo extraño. Vuelve a intentar'));
							}
	
							$resultado->result = $visitas;
							$resultado->state = $this->model->transaction->confirmaTransaccion(); 
							return $res->withJson($resultado->setResponse(true, "Felicidades"));
						}else{
							// mostrar cuales faltan
							$resultado3 = $this->model->registro->getStandsVisitados($idCode);
							$zonasTexto = implode(', ', $resultado3->result);

							$resultado->state = $this->model->transaction->regresaTransaccion();
							return $res->withJson($resultado->setResponse(false, "No has alcanzado las condiciones necesarias para el premio \n\nZonas visitadas:\n\n".$zonasTexto) );
						}
					}else{
						$resultado->result = 0;
						$resultado->state = $this->model->transaction->regresaTransaccion();
						return $res->withJson($resultado->setResponse(false, 'No has visitado ningun stand'));
					}
				}
			}else{
				$checkCode->result = 0;
				$checkCode->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($checkCode->setResponse(false, 'QR incorrecto'));
			}	
		});*/

		// Ruta para obtener los datos de registro por medio del ID que cuentan con premio SIN ZONAS
		$this->get('getPremio/{codigo}', function ($req, $res, $args) {
			$this->model->transaction->iniciaTransaccion();			
			$checkCode = $this->model->registro->getByCodigo($args['codigo']);
			if($checkCode->response){
				$idCode = $checkCode->result->id;
				$checkPremio = $this->model->registro->getPremioEntregado($idCode);
				if($checkPremio->response){
					$checkPremio->result = 0;
					$checkPremio->state = $this->model->transaction->regresaTransaccion();
					return $res->withJson($checkCode->setResponse(false, 'Premio ya fue entregado'));
				}else{
					$resultado = $this->model->registro->getCountVisitas($idCode);
					if($resultado->response){
						$visitas = $resultado->result;
						$numPremios = $this->model->registro->getVariable('premio');
						if($numPremios->response){
							$numPremios = $numPremios->result->valor;
							if(intval($visitas) >= intval($numPremios)) {
								$data = array(
									'fk_codigo' => $idCode,
								);
								$resultado2 = $this->model->registro->addPremio($data);
								if(!$resultado2->response){
									$resultado2->result = 0;
									$resultado2->state = $this->model->transaction->regresaTransaccion();
									return $res->withJson($resultado2->setResponse(false, 'Ocurrio algo extraño. Vuelve a intentar'));
								}
		
								$resultado->result = intval($visitas);
								$resultado->state = $this->model->transaction->confirmaTransaccion(); 
								return $res->withJson($resultado->setResponse(true, "Felicidades"));
							}else{
								// mostrar cuales faltan
								$faltanStands = intval($numPremios) - intval($visitas);
								$resultado->result = 0;
								$resultado->state = $this->model->transaction->regresaTransaccion();
								return $res->withJson($resultado->setResponse(false, "No has alcanzado las condiciones necesarias para el premio. Aún te faltan ".$faltanStands." stand") );
							}
						}else{
							$numPremios->result = 0;
							$numPremios->state = $this->model->transaction->regresaTransaccion();
							return $res->withJson($numPremios->setResponse(false, 'No has visitado ningun stand'));
						}
					}else{
						$resultado->result = 0;
						$resultado->state = $this->model->transaction->regresaTransaccion();
						return $res->withJson($resultado->setResponse(false, 'No has visitado ningun stand'));
					}
				}
			}else{
				$checkCode->result = 0;
				$checkCode->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($checkCode->setResponse(false, 'QR incorrecto'));
			}	
		});

		// Ruta para obtener cafe
		$this->get('getCafe/{codigo}', function ($req, $res, $args) {
			$this->model->transaction->iniciaTransaccion();
			$checkCode = $this->model->registro->getByCodigo($args['codigo']);
			if($checkCode->response){
				$idCode = $checkCode->result->id;
				$checkCafe = $this->model->registro->getCafeEntregado($idCode);
				if($checkCafe->response){
					$checkCafe->result = 0;
					$checkCafe->state = $this->model->transaction->regresaTransaccion();
					return $res->withJson($checkCode->setResponse(false, 'Café ya fue entregado'));
				}else{
					$data = array(
						'fk_codigo' => $idCode,
					);
					$resultado2 = $this->model->registro->addCafe($data);
					if(!$resultado2->response){
						$resultado2->result = 0;
						$resultado2->state = $this->model->transaction->regresaTransaccion();
						return $res->withJson($resultado2->setResponse(false, 'Ocurrio algo extraño. Vuelve a intentar'));
					}
					$resultado2->state = $this->model->transaction->confirmaTransaccion(); 
					return $res->withJson($resultado2->setResponse(true, "Felicidades"));
				}
			}else{
				$checkCode->result = 0;
				$checkCode->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($checkCode->setResponse(false, 'QR incorrecto'));
			}	
		});

		// Ruta para obtener estadisticas de los registros
		$this->get('estadisticas/{tipo}/{fechaInicio}/{fechaFin}', function ($req, $res, $args) {
			$resultado = $this->model->seg_usuario->getAllByTipo($args['tipo']);
			if($resultado->response){
				foreach ($resultado->result as $item) {
					$resultado2 = $this->model->registro->getVisitasGeneral(intval($item->id), $args['fechaInicio'], $args['fechaFin']);
					$item->visita_general = $resultado2->result;
					$resultado3 = $this->model->registro->getVisitasDemo(intval($item->id), $args['fechaInicio'], $args['fechaFin']);
					$item->visita_demo = $resultado3->result;
				}
				// Ordenar por visita_general descendente
				usort($resultado->result, function($a, $b) {
					return ($b->visita_general + $b->visita_demo) <=> ($a->visita_general + $a->visita_demo);
				});				
				$resultado->message = $this->model->registro->getCountPremios($args['fechaInicio'], $args['fechaFin'])->result;
				$resultado->totalBrazaletes = $this->model->registro->getCountBrazaletes($args['fechaInicio'], $args['fechaFin'])->result;
				return $res->withJson($resultado);
			}else{
				return $res->withJson($resultado->setResponse(false, 'No hay registros'));
			}
			// $resultado = $this->model->registro->getCountPremios()->result;
			// return $res->withJson($resultado);
		});

		// Ruta para obtener estadisticas por ID
		$this->get('estadisticaByID/{id}', function ($req, $res, $args) {
			$resultado = $this->model->registro->getEstadisticaByID($args['id']);
			return $res->withJson($resultado);
		});

		//ruta para obtener valor de variable
		$this->get('getVariable/{nombre}', function ($req, $res, $args) {
			$resultado = $this->model->registro->getVariable($args['nombre']);
			if($resultado->response){
				$resultado->result = $resultado->result->valor;
			}
			return $res->withJson($resultado);
		});

		$this->post('editPremio/{valor}', function ($req, $res, $args) {
			$this->model->transaction->iniciaTransaccion();
			$data = array(
				'valor' => $args['valor'],
			);
			$resultado = $this->model->registro->editPremio($data);
			if($resultado->response){
				$resultado->state = $this->model->transaction->confirmaTransaccion(); 
				return $res->withJson($resultado);
			}else{
				$resultado->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($resultado);
			}
		});

		// Ruta para agregar registro de checkIn-checkOut
		$this->post('addCheck/{codigo}[/{fk_seg_usuario}]', function ($req, $res, $args) {
			date_default_timezone_set('America/Mexico_City');
			$this->model->transaction->iniciaTransaccion();
			// verificar que existe usuario
			$info2 = $this->model->seg_usuario->get($args['fk_seg_usuario']);
			$nombre = '';
			if($info2->response){
				$nombre = $info2->result->nombre;
				$zona = $info2->result->zona;
				// verificar que existe codigo 
				$checkCode = $this->model->registro->getByCodigo($args['codigo']);
				if($checkCode->response){
					$idCode = $checkCode->result->id;
					// verificar que existe registro
					$info = $this->model->registro->get($idCode, $args['fk_seg_usuario']);
					if(!$info->response){
						// checkIn
						$data = array(
							'fk_codigo'			=> $idCode,
							'zona'				=> $zona,
							'fk_seg_usuario'	=> $args['fk_seg_usuario'],
							'fecha_entrada'		=> date('Y-m-d H:i:s'),
							'tiempo_evento' 	=> '00:00:00',
						);
						$resultado = $this->model->registro->add($data);
						if($resultado->response){
							// agregar entrada en historial
							$data2 = array(
								'fk_codigo' 		=> $idCode,
								'zona'				=> $zona,
								'fk_seg_usuario'	=> $args['fk_seg_usuario'],
								'fecha_entrada'		=> date('Y-m-d H:i:s'),
							);
							$add = $this->model->registro->addHistorial($data2);
							if(!$add->response){
								$resultado->state = $this->model->transaction->regresaTransaccion();
								return $res->withJson($resultado->setResponse(false, 'Ocurrio algo extraño.. Vuelve a intentar'));
								// return $res
								// 	->withHeader('Access-Control-Allow-Origin', '*')
								// 	->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
								// 	->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
								// 	->withJson($resultado->setResponse(false, 'Ocurrio algo extraño.. Vuelve a intentar'));
							}
							$resultado->checkin = date('Y-m-d H:i:s');
							$resultado->state = $this->model->transaction->confirmaTransaccion(); 
							return $res->withJson($resultado->setResponse(true, 'Bienvenido(a) a, '.$nombre));
							// return $res
							// 	->withHeader('Access-Control-Allow-Origin', '*')
							// 	->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
							// 	->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
							// 	->withJson($resultado->setResponse(true, 'Bienvenido(a) a, '.$nombre));
						}else{
							$resultado->state = $this->model->transaction->regresaTransaccion();
							return $res->withJson($resultado->setResponse(false, 'Ocurrio algo extraño. Vuelve a intentar'));
							// return $res
							// 	->withHeader('Access-Control-Allow-Origin', '*')
							// 	->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
							// 	->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
							// 	->withJson($resultado->setResponse(false, 'Ocurrio algo extraño.. Vuelve a intentar'));
						}
					}else{
						// checkOut
						// verificar que existe fecha_salida
						if($info->result->fecha_salida != Null){
							$info->result = "";
							$info->state = $this->model->transaction->regresaTransaccion();
							return $res->withJson($info->setResponse(false,'Ya fue registrada la Salida'));
							// return $res
							// 	->withHeader('Access-Control-Allow-Origin', '*')
							// 	->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
							// 	->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
							// 	->withJson($info->setResponse(false, 'Ya fue registrada la Salida'));
						}else{
							// agregar salida
							$id = $info->result->id;
							$fecha1 = new DateTime($info->result->fecha_entrada);
							$fecha2 = new DateTime(date("Y-m-d H:i:s"));
							$intervalo = $fecha1->diff($fecha2);
							$tiempoEvento = new DateTime($info->result->tiempo_evento);
							$tiempoEvento->add($intervalo);
							$format = $tiempoEvento->format('H:i:s');
							$data = array(
								'fecha_salida' => date('Y-m-d H:i:s'),
								'tiempo_evento'=> $format,
							);
							$resultado = $this->model->registro->edit($data, $id);
							if($resultado->response){
								// agregar salida en historial
								$data2 = array(
									'fk_codigo'			=> $idCode,
									'zona'				=> $zona,
									'fk_seg_usuario'	=> $args['fk_seg_usuario'],
									'fecha_salida'		=> date('Y-m-d H:i:s'),
								);
								$add = $this->model->registro->addHistorial($data2);
								if(!$add->response){
									$resultado->state = $this->model->transaction->regresaTransaccion();
									return $res->withJson($resultado->setResponse(false, 'Ocurrio algo extraño.. Vuelve a intentar'));
									// return $res
									// 	->withHeader('Access-Control-Allow-Origin', '*')
									// 	->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
									// 	->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
									// 	->withJson($resultado->setResponse(false, 'Ocurrio algo extraño.. Vuelve a intentar'));
								}
								$resultado->checkout = date('Y-m-d H:i:s');
								$resultado->state = $this->model->transaction->confirmaTransaccion(); 
								return $res->withJson($resultado->setResponse(true, 'Gracias por visitar, '.$nombre));
								// return $res
								// 		->withHeader('Access-Control-Allow-Origin', '*')
								// 		->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
								// 		->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
								// 		->withJson($resultado->setResponse(true, 'Gracias por visitar, '.$nombre));
							}else{
								$resultado->state = $this->model->transaction->regresaTransaccion();
								return $res->withJson($resultado->setResponse(false,'Ocurrio algo extraño.. Vuelve a intentar'));
								// return $res
								// 		->withHeader('Access-Control-Allow-Origin', '*')
								// 		->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
								// 		->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
								// 		->withJson($resultado->setResponse(false, 'Ocurrio algo extraño.. Vuelve a intentar'));
							}
						}
					}
				}else{
					$checkCode->result = "";
					$checkCode->state = $this->model->transaction->regresaTransaccion();
					return $res->withJson($checkCode->setResponse(false, 'QR incorrecto'));
					// return $res
					// 	->withHeader('Access-Control-Allow-Origin', '*')
					// 	->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
					// 	->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
					// 	->withJson($checkCode->setResponse(false, 'QR incorrecto'));
				}
			}else{
				$info2->result = "";
				$info2->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($info2->setResponse(false, 'Usuario fue dado de baja'));
				// return $res
				// 		->withHeader('Access-Control-Allow-Origin', '*')
				// 		->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
				// 		->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
				// 		->withJson($info2->setResponse(false, 'Usuario fue dado de baja'));
			}
		});

		// Ruta para agregar registro de checkIn-checkOut
		$this->post('deleteRegistrosSinSalida/', function ($req, $res, $args) {
			date_default_timezone_set('America/Mexico_City');
			$this->model->transaction->iniciaTransaccion();
			$info = $this->model->registro->getCodigosUsados();
			if($info->response){
				foreach($info->result as $item){
					$id = intval($item->id);
					$data = array(
						'status' => "0",
					);
					$info2 = $this->model->registro->edit($data, $id);
					if(!$info2->response){
						$info2->result = "";
						$info2->state = $this->model->transaction->regresaTransaccion();
						return $res->withJson($info2->setResponse(false, 'No hay registros'));
					}
				}
				$res->state = $this->model->transaction->confirmaTransaccion(); 
				return $res->withJson($info->setResponse(true, 'Registros eliminados correctamente'));
			}else{
				$info->result = "";
				$info->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($info->setResponse(false, 'No hay registros'));
			}
		});

		//CheckIn registros app
		/*
		$this->post('checkin/{codigo}[/{fk_seg_usuario}]', function ($req, $res, $args) {
			date_default_timezone_set('America/Mexico_City');
			$this->model->transaction->iniciaTransaccion();
			$info2 = $this->model->seg_usuario->get($args['fk_seg_usuario']);
			$nombre = '';
			if($info2->response){
				$nombre = $info2->result->nombre.' '.$info2->result->nombre_encargado;
			}else{
				$info2->result = "";
				$info2->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($info2->setResponse(false, 'Usuario fue dado de baja'));
			}
			$checkCode = $this->model->registro->getByCodigo($args['codigo']);
			if($checkCode->response){
				$idCode = $checkCode->result->id;
				$info = $this->model->registro->get($idCode, $args['fk_seg_usuario']);
				if(!$info->response){
						$id = $info->result->id;
						$data = array(
							'fk_codigo'			=> $idCode,
							'fk_seg_usuario'	=> $args['fk_seg_usuario'],
							'tiempo_evento' 	=> '00:00:01',
						);
						$resultado = $this->model->registro->add($data, $id);
						if($resultado->response){
							$data2 = array(
								'fk_codigo' 		=> $idCode,
								'fk_seg_usuario'	=> $args['fk_seg_usuario'],
								'fecha_entrada'		=> date('Y-m-d H:i:s'),
							);
							$add = $this->model->registro->addHistorial($data2);
							if(!$add->response){
								$resultado->state = $this->model->transaction->regresaTransaccion();
								return $res->withJson($resultado->setResponse(false, 'Ocurrio algo extraño.. Vuelve a intentar'));
							}
							$resultado->checkin = date('Y-m-d H:i:s');
							$resultado->state = $this->model->transaction->confirmaTransaccion(); 
							return $res->withJson($resultado->setResponse(true, 'Bienvenido(a) a, '.$nombre));
						}else{
							$resultado->state = $this->model->transaction->regresaTransaccion();
							return $res->withJson($resultado->setResponse(false, 'Ocurrio algo extraño. Vuelve a intentar'));
						}
				}else{
					$info->result = "";
					$info->state = $this->model->transaction->regresaTransaccion();
					return $res->withJson($info->setResponse(false, 'Ya existe el registro CheckIn'));
				}
			}else{
				$checkCode->result = "";
				$checkCode->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($checkCode->setResponse(false, 'QR incorrecto'));
			}
		});
		*/

		/*
		//checkout registros app
		$this->post('checkout/{codigo}[/{fk_seg_usuario}]', function ($req, $res, $args) {
			date_default_timezone_set('America/Mexico_City');
			$this->model->transaction->iniciaTransaccion();
			$info2 = $this->model->seg_usuario->get($args['fk_seg_usuario']);
			$nombre = '';
			if($info2->response){
				$nombre = $info2->result->nombre.' '.$info2->result->nombre_encargado;
			}else{
				$info2->result = "";
				$info2->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($info2->setResponse(false, 'Usuario fue dado de baja'));
			}
			$checkCode = $this->model->registro->getByCodigo($args['codigo']);
			if($checkCode->response){
				$idCode = $checkCode->result->id;
				$info = $this->model->registro->get($idCode, $args['fk_seg_usuario']);
				if(!$info->response){
					$info->result = "";
					$info->state = $this->model->transaction->regresaTransaccion();
					return $res->withJson($info->setResponse(false, 'Primero debe realizar CheckIn'));
				}else{
					if($info->result->fecha_salida != Null){
						$info->result = "";
						$info->state = $this->model->transaction->regresaTransaccion();
						return $res->withJson($info->setResponse(false,'CheckOut realizado anteriormente'));
					}else{
						$id = $info->result->id;

						$fecha1 = new DateTime($info->result->fecha_entrada);
						$fecha2 = new DateTime(date("Y-m-d H:i:s"));
						$intervalo = $fecha1->diff($fecha2);
						$tiempoEvento = new DateTime($info->result->tiempo_evento);
						$tiempoEvento->add($intervalo);
						$format = $tiempoEvento->format('H:i:s');
						$data = array(
							'fecha_salida' => date('Y-m-d H:i:s'),
							'tiempo_evento'=> $format,
						);
						$resultado = $this->model->registro->edit($data, $id);
						if($resultado->response){
							$data2 = array(
								'fk_codigo'			=> $idCode,
								'fk_seg_usuario'	=> $args['fk_seg_usuario'],
								'fecha_salida'		=> date('Y-m-d H:i:s'),
							);
							$add = $this->model->registro->addHistorial($data2);
							if(!$add->response){
								$resultado->state = $this->model->transaction->regresaTransaccion();
								return $res->withJson($resultado->setResponse(false, 'Ocurrio algo extraño.. Vuelve a intentar'));
							}
							$resultado->checkout = date('Y-m-d H:i:s');
							$resultado->state = $this->model->transaction->confirmaTransaccion(); 
							return $res->withJson($resultado->setResponse(true, 'Gracias por visitar, '.$nombre));
						}else{
							$resultado->state = $this->model->transaction->regresaTransaccion();
							return $res->withJson($resultado->setResponse(false,'Ocurrio algo extraño.. Vuelve a intentar'));
						}
					}
				}
			}else{
				$checkCode->result = "";
				$checkCode->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($checkCode->setResponse(false, 'QR incorrecto'));
			}
		});
		*/

		// Obtener CSV de todos los registros
		$this->get('getExcel/{fechaInicio}/{fechaFin}', function($req, $response, $args){
			$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();
		
			$titulo = "Estadísticas Sephora 2025";
			$sheet->setCellValue("A1", $titulo);

			$titulo2 = $this->model->registro->getCountPremios($args['fechaInicio'], $args['fechaFin'])->result;
			$sheet->setCellValue("A3", "Premios entregados:");
			$sheet->setCellValue("C3", $titulo2);
		
			$sheet->setCellValue("A6", 'Nombre');
			$sheet->setCellValue("B6", 'Nombre Encargado');
			$sheet->setCellValue("C6", 'Visitas');
			$sheet->setCellValue("D6", 'Actividad Finalizada');
			$sheet->setCellValue("E6", 'Total Participantes');
			$sheet->setCellValue("F6", 'Zona');
		
			$registros = $this->model->seg_usuario->getAllByTipo('2');
			if($registros->response){
				foreach ($registros->result as $item) {
					$resultado2 = $this->model->registro->getVisitasGeneral(intval($item->id), $args['fechaInicio'], $args['fechaFin']);
					$item->visita_general = $resultado2->result;
					$resultado3 = $this->model->registro->getVisitasDemo(intval($item->id), $args['fechaInicio'], $args['fechaFin']);
					$item->visita_demo = $resultado3->result;
				}
				// Aquí ordenas por visita_general descendente
				usort($registros->result, function($a, $b) {
					return ($b->visita_general + $b->visita_demo) <=> ($a->visita_general + $a->visita_demo);
				});				
			} else {
				$writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
				$writer->setUseBOM(true);
			
				header('Content-Type: text/csv');
				header("Content-Disposition: attachment; filename=\"Reporte Visitas Sephora {$args['fechaInicio']} al {$args['fechaFin']}.csv\"");
				$writer->save('php://output');
				exit();
			}

			$fila = 7;
			foreach($registros->result as $item){
				$totalVisitas = intval($item->visita_general) + intval($item->visita_demo);

				$sheet->setCellValue("A".$fila, $item->nombre);
				$sheet->setCellValue("B".$fila, $item->nombre_encargado);
				$sheet->setCellValue("C".$fila, $item->visita_general);
				$sheet->setCellValue("D".$fila, $item->visita_demo);
				$sheet->setCellValue("E".$fila, $totalVisitas);
				$sheet->setCellValue("F".$fila, $item->zona);

				$fila++;
			}
		
			$writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
			$writer->setUseBOM(true);
		
			header('Content-Type: text/csv');
			header("Content-Disposition: attachment; filename=\"Reporte Visitas Sephora {$args['fechaInicio']} al {$args['fechaFin']}.csv\"");
			$writer->save('php://output');
			exit();
		});

		// Obtener CSV de todos los registros con tiempos por usuario
		$this->get('getExcel2/{fechaInicio}/{fechaFin}', function($req, $response, $args){
			ini_set('memory_limit', '128M'); // Aumentar límite de memoria
            ini_set('max_execution_time', 300); // Aumentar tiempo de ejecución opcionalmente
			$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();
		
			$titulo = "Registros Sephora 2025";
			$sheet->setCellValue("A1", $titulo);
		
			$registros = $this->model->seg_usuario->getAllByTipo('2');
			if($registros->response){
				foreach ($registros->result as $item) {
					$resultado2 = $this->model->registro->getByFkSegUsuario(intval($item->id), $args['fechaInicio'], $args['fechaFin']);
					$item->registros = $resultado2->result;
					//print_r($item); exit;
				}
			} else {
				$writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
				$writer->setUseBOM(true);
			
				header('Content-Type: text/csv');
				header("Content-Disposition: attachment; filename=\"Reporte Tiempos Sephora {$args['fechaInicio']} al {$args['fechaFin']}.csv\"");
				$writer->save('php://output');
				exit();
			}

			$fila = 3;
			foreach($registros->result as $item){

				$sheet->setCellValue("A".$fila, $item->nombre);

				$count = count($item->registros);
				if($count == 1){
					$sheet->setCellValue("B".$fila, $count." Actividad");
				}else{
					$sheet->setCellValue("B".$fila, $count." Actividades");
				}

				$fila++;
				$sheet->setCellValue("A".$fila, "Stand");
				$sheet->setCellValue("B".$fila, "Código");
				$sheet->setCellValue("C".$fila, "CheckIn");
				$sheet->setCellValue("D".$fila, "CheckOut");
				$sheet->setCellValue("E".$fila, "Tiempo en evento");
				$sheet->setCellValue("F".$fila, "Zona");

				$totalSegundos = 0;

				foreach($item->registros as $item2){
					$fila++;
					$sheet->setCellValue("A".$fila, $item->nombre);
					$sheet->setCellValue("B".$fila, $item2->codigo);
					$sheet->setCellValue("C".$fila, $item2->fecha_entrada);
					$sheet->setCellValue("D".$fila, $item2->fecha_salida);
					$sheet->setCellValue("E".$fila, $item2->tiempo_evento);
					$sheet->setCellValue("F".$fila, $item->zona);

					list($horas, $minutos, $segundos) = explode(":", $item2->tiempo_evento);
    				$totalSegundos += ($horas * 3600) + ($minutos * 60) + $segundos;
				}
				$horas = floor($totalSegundos / 3600);
				$minutos = floor(($totalSegundos % 3600) / 60);
				$segundos = $totalSegundos % 60;
				$tiempoTotal = sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);

				$fila++;
				$sheet->setCellValue("B".$fila, "Total tiempo");
				$sheet->setCellValue("C".$fila, $tiempoTotal);
				$fila++;
				$fila++;
			}
		
			$writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
			$writer->setUseBOM(true);
		
			header('Content-Type: text/csv');
			header("Content-Disposition: attachment; filename=\"Reporte Tiempos Sephora {$args['fechaInicio']} al {$args['fechaFin']}.csv\"");
			$writer->save('php://output');
			exit();
		});

	})
	// ->add(new \Eko3alpha\Slim\Middleware\CorsMiddleware([
	// 	'https://sephorawebapp.clase.digital/' => ['GET', 'POST', 'OPTIONS'],
	// ], 'Content-Type, Authorization, apiKey'))->$mwApiKey
	;

	// $app->options('/{routes:.+}', function ($request, $response, $args) {
    //     return $response
    //         ->withHeader('Access-Control-Allow-Origin', '*')
    //         ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, apiKey')
    //         ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
    //         ->withStatus(204);
    // });
?>