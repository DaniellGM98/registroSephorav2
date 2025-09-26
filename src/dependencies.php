<?php
	//use Slim\App;

	//return function (App $app) {
		$container = $app->getContainer();

		// view renderer
		$container['renderer'] = function ($c) {
			$settings = $c->get('settings')['renderer'];
			return new \Slim\Views\PhpRenderer($settings['template_path']);
		};

		// rpt renderer
		$container['rpt_renderer'] = function ($c) {
			$settings = $c->get('settings')['rpt_renderer'];
			return new \Slim\Views\PhpRenderer($settings['template_path']);
		};

		// monolog
		$container['logger'] = function ($c) {
			$settings = $c->get('settings')['logger'];
			$logger = new \Monolog\Logger($settings['name']);
			$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
			$logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
			return $logger;
		};

		// Database
			$container['db'] = function($c) {
				$connectionString = $c->get('settings')['connectionString'];
				
				$pdo = new PDO($connectionString['dns'], $connectionString['user'], $connectionString['pass']);

				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

				return new \Envms\FluentPDO\Query($pdo);
				
			};
			
			// Register component view 
			$container['view'] = function ($container) {
				return new \Slim\Views\PhpRenderer('../templates/');
			};

			// Models
			$container['model'] = function($c) {
				return (object)[
					'transaction' => new App\Lib\Transaction($c->db),
					'seg_usuario' => new App\Model\SegUsuarioModel($c->db),
					'registro' => new App\Model\RegistroModel($c->db),
				];
			};
	//};
?>