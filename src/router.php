<?

namespace IsraelNogueira\fastRouter;
use RuntimeException;
use Closure;

/**
 * -------------------------------------------------------------------------
 * 
 *		@author Israel Nogueira <israel@feats.com>
 *		@package library
 *		@license GPL-3.0-or-later
 *		@copyright 2023 Israel Nogueira
 *		@link https://github.com/israel-nogueira/fast-router
 *
 * -------------------------------------------------------------------------
 */
	class router{
		
		public static $group_routers	=	[];
		public static $middleware		=	[];
		public static $handler 			=	null;
    	private $currentGroupStack		=	[];
		public $paths_instances			=	[];
		public $middlewareInstance		=	[];
		public $handlerInstance			=	null;
		public $group_routersInstance	=	[]; 

		/**-------------------------------------------------
		 * 	MODO INSTANCIADO
		 * -------------------------------------------------
		 *  PRIMEIRO INSTANCIA TUDO PARA DEPOIS PROCESSAR
		 * $_ROTA = new router();
		 * $_ROTA()->group([],'');
		 * $_ROTA()->post([],'');
		 * $_ROTA()->get([],'');
		 * $_ROTA()->any([],'');
		 * $_ROTA()->processa(); <---- apenas aqui processa
		 * -------------------------------------------------*/
		public function __call($name, $arguments){
			if ($name === 'group') { 
				$name = 'groupInstance'; 
				return $this->groupInstance(...$arguments);
			}
			$callback = end($arguments);
			$options = $arguments[0] ?? [];
			$route = [
				'type' => 'route',
				'method' => $name,
				'options' => $options,
				'callback' => $callback
			];
			if (!empty($this->currentGroupStack)) {
				$parent = &$this->currentGroupStack[count($this->currentGroupStack) - 1];
				$parent['children'][] = $route;
			} else {
				$this->paths_instances[] = $route;
			}
			return $this;
			
		}

public function groupInstance($config, $callback = null) {
    $prefix = $config['prefix'] ?? null;

    $group = [
        'type' => 'group',
        'method' => 'GROUP',
        'options' => [
            'prefix' => $prefix,
            'middleware' => $config['middleware'] ?? [],
            'callback' => $callback
        ],
        'children' => []
    ];

    // CORREÇÃO 1: Adiciona ao paths_instances ANTES de empilhar
    if (empty($this->currentGroupStack)) {
        $this->paths_instances[] = &$group;
    }

    // Empilha o grupo atual
    $this->currentGroupStack[] = &$group;

    // CORREÇÃO 2: Adiciona como filho se houver pai
    if (count($this->currentGroupStack) > 1) {
        $parent = &$this->currentGroupStack[count($this->currentGroupStack) - 2];
        $parent['children'][] = &$group;
    }

    // Executa o callback passando a própria instância de ROTA
    if (is_callable($callback)) {
        $callback($this);
    }

    array_pop($this->currentGroupStack);

    return $group;
}

		private function collectPath(array $path, $prefix = '') {
			$currentPrefix = $path['options']['prefix'] ?? '';
			$fullPrefix = $prefix ? trim($prefix, '/') . '/' . trim($currentPrefix, '/') : trim($currentPrefix, '/');

			if ($path['type'] === 'route') {
				return [[
					'method' => $path['method'],
					'options' => $path['options'],
					'callback' => $path['callback'],
					'full_prefix' => $fullPrefix
				]];
			}

			if ($path['type'] === 'group') {
				$routes = [];
				$children = $path['children'];

				if ($children instanceof \Closure) {
					$subRouter = new self();
					$boundClosure = $children->bindTo($subRouter, $subRouter);
					$boundClosure();
					$children = $subRouter->paths_instances ?? [];
				}

				foreach ($children as $child) {
					$routes = array_merge($routes, $this->collectPath($child, $fullPrefix));
				}

				return $routes;
			}

			return [];
		}

		public function collectPathWithMiddleware($node, $parentMiddlewares = [], $parentPrefix = '') {
			$result = [];
			
			// Acumula middlewares
			$currentMiddlewares = $parentMiddlewares;
			if (!empty($node['options']['middleware'])) {
				$currentMiddlewares = array_merge($currentMiddlewares, $node['options']['middleware']);
			}
			
			// CORREÇÃO: Monta o prefix corretamente
			$nodePrefix = $node['options']['prefix'] ?? '';
			if ($nodePrefix !== '' && $nodePrefix !== null) {
				$currentPrefix = $parentPrefix 
					? rtrim($parentPrefix, '/') . '/' . trim($nodePrefix, '/')
					: trim($nodePrefix, '/');
			} else {
				$currentPrefix = $parentPrefix;
			}
			
			// Se for rota, adiciona ao resultado
			if ($node['type'] === 'route') {
				$node['all_middlewares'] = $currentMiddlewares;
				$node['full_prefix'] = $currentPrefix;
				$result[] = $node;
			}
			
			// Processa filhos recursivamente
			if (!empty($node['children'])) {
				foreach ($node['children'] as $child) {
					$result = array_merge(
						$result, 
						$this->collectPathWithMiddleware($child, $currentMiddlewares, $currentPrefix)
					);
				}
			}
			
			return $result;
		}

		public function sendInstanceConsolidado(array $route) {
			$middlewares = $route['all_middlewares'] ?? [];
			$params = $route['params'] ?? [];
			$callback = $route['callback'] ?? null;

			if (!empty($middlewares)) {
				$this->callMiddlewareInstance($middlewares, function($retornos) use ($callback, $params) {
					$this->middlewareInstance = $retornos;
					if ($callback) {
						$this->execFnInstance($callback, ...array_values($params));
					}
				});
			} elseif ($callback) {
				$this->execFnInstance($callback, ...array_values($params));
			}
		}


		public function processa() {
			foreach ($this->paths_instances as $value) {
				$routes = $this->collectPathWithMiddleware($value, [], '');
				
				// echo "<br><br>ROTAS COLETADAS:<br>";
				// foreach ($routes as $r) {
				// 	echo "- {$r['method']} => {$r['full_prefix']}<br>";
				// }
				
				foreach ($routes as $route) {
					if (!isset($route['callback'])) continue;
					
					$parametros = $this->parametrosRotaInstance($route['full_prefix'], null);
					
					// echo "<br>TESTANDO: {$route['full_prefix']}<br>";
					// echo "URL: " . self::urlPath() . "<br>";
					// echo "REGEX: {$parametros['regex']}<br>";
					// echo "MATCH: " . ($parametros['status'] ? 'SIM' : 'NÃO') . "<br>";
					
					if (empty($parametros['status'])) continue;
					
					// echo "<br>EXECUTANDO!<br>";
					
					$consolidado = [
						'status' => $parametros['status'],
						'setada' => $parametros['setada'],
						'params' => $parametros['params'],
						'regex' => $parametros['regex'],
						'method' => $route['method'],
						'full_prefix' => $route['full_prefix'],
						'all_middlewares' => $route['all_middlewares'],
						'callback' => $route['callback']
					];
					
					$this->sendInstanceConsolidado($consolidado);
					
					if(empty($route['options']['continue']) || $route['options']['continue']==false){
						break 2;
					}
				}
			}
		}
		/**-----------------------------------------------------------
		 *	MODO ESTATICO
		 * -----------------------------------------------------------
		 * 	 AQUI JÁ PROCESSA DIRETO NA EXECUÇÃO.
		 * 
		 *   router::group([],function(){});
		 *   router::get([],function(){});
		 *   router::post([],function(){});
		 *   router::any([],function(){});
		 * 
		 * 
		 * ----------------------------------------------------------- */
		/*
		|------------------------------------------------------------------
		|    __CALLSTATIC
		|------------------------------------------------------------------
		*/
			public static function __callStatic($name, $arguments){
				if($name=='group'){
					return self::groupStatic(...$arguments);
				}

				if (in_array(strtoupper($name), ['ANY','MATH','GET', 'REDIRECT','POST','RMDIR','MKDIR','INDEX','MOVE','TRACE','DELETE','TRACK','PUT','HEAD','OPTIONS','CONNECT'])) {
					if(strtoupper($name)=='MATH' && is_array($arguments[0])){
						$name = $arguments[0];
						array_shift($arguments);
					}
					self::send($name,...$arguments);
				} else {
					self::$name(...$arguments);
				}
			}



			

		/*
		|------------------------------------------------------------------
		|    RETORNA A URL
		|------------------------------------------------------------------
		*/

			static function urlPath($node = null, $debug = true) {
				if (substr($_SERVER['REQUEST_URI'], 0, 1) == '/'){
					$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 1, strlen($_SERVER['REQUEST_URI']));
				}
				
				$REQUEST_URL = explode('?', $_SERVER['REQUEST_URI']);
				$url = $REQUEST_URL[0];
				
				if (substr($url, -1) == '/'){
					$url = substr($url, 0, -1);
				}
				
				$GET = explode('/', $url);
				
				if ($node === null)
					return $url;
				
				if (is_int($node)) {
					if ($node > count($GET)) {
						if ($debug) {
							throw new RuntimeException("Erro: Não existe path nesta posição ->    self::urlPath(" . $node . ")");
						} else {
							return false;
						}
					} else {
						return $GET[($node - 1)];
					}
				}
				
				if (is_array($node)) {
					if (count($node) === 1) {
						$start = $node[0] - 1;
						if ($start < 0) {
							$start = count($GET) + $start;
						}
						$result = array_slice($GET, $start);
						if (count($result) == 0) {
							if ($debug) {
								throw new RuntimeException("Erro: O intervalo especificado não existe na URL.");
							} else {
								return false;
							}
						} else {
							return implode('/', $result);
						}
					} elseif (count($node) === 2) {
						$start = $node[0] - 1;
						$end = $node[1];
						if ($end < 0) {
							$end = count($GET) + $end + 1; // Adiciona 1 para incluir o último elemento
						}
						$result = array_slice($GET, $start, $end - $start);
						if (count($result) == 0) {
							if ($debug) {
								throw new RuntimeException("Erro: O intervalo especificado não existe na URL.");
							} else {
								return false;
							}
						} else {
							return implode('/', $result);
						}
					} else {
						throw new RuntimeException("Erro: O array passado para \$node deve conter um ou dois números.");
					}
				}
				
				throw new RuntimeException("Erro: O parâmetro \$node deve ser null, um número ou um array.");
			}



		/*
		|------------------------------------------------------------------
		|	EXECUTA FUNÇÕES 
		|------------------------------------------------------------------
		|	Aqui, qualquer função, classe, método passado será executado
		|------------------------------------------------------------------
		*/
			public static function execFn($function, ...$parameters){	
				if (is_callable($function)) {
					// Verifica se é uma função ou método estático
					if (is_string($function)) {
						// Verifica se é uma função global
						if (function_exists($function)) {
							return call_user_func_array($function, $parameters);
						} else {
							// Verifica se é um método estático de classe
							if (strpos($function, '::') !== false) {
								list($class, $method) = explode('::', $function);
								if (class_exists($class) && method_exists($class, $method)) {
									return call_user_func_array($function, $parameters);
								}
							}
						}
					} elseif (is_array($function) && count($function) == 2) {
						// Verifica se é um método de objeto
						list($object, $method) = $function;
						if (is_object($object) && method_exists($object, $method)) {
							return call_user_func_array([$object, $method], $parameters);
						}
					} else {
						$function($parameters);
					}
				} elseif (is_string($function) && strpos($function, '@') !== false) {
					// Verifica se é uma string com "@" para chamar uma função de classe
					list($class, $method) = explode('@', $function);
					if (class_exists($class) && method_exists($class, $method)) {
						$object = new $class();

						return call_user_func_array([$object, $method], $parameters);						
					} else {
						// Verifica se a classe foi declarada antes de utilizar o autoload
						if (!class_exists($class)) {

							$filePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR;
							$pattern = $class . '.*.php';
							$fileList = glob($filePath . $pattern);
							if (empty($fileList)) {
								$pattern = $class . '.php';
								$fileList = glob($filePath . $pattern);
							}
							spl_autoload_register(function ($className)use($fileList) {
								if(count($fileList)>0){
									foreach($fileList as $file){
										require_once $file;
									}
								}
							});

							if (class_exists($class) && method_exists($class, $method)) {
								$object = new $class();
								return call_user_func_array([$object, $method], $parameters);
							}
						}
					}
				} elseif (is_string($function) && strpos($function, '\\') !== false) {
					// Verifica se é uma string com "\\" para chamar uma função de namespace
					if (function_exists($function)) {
						return call_user_func_array($function, $parameters);
					}
				}

				// throw new Exception('Function or method not found');
			}

			private function execFnInstance($callback, ...$params) {
				// 1) Se for Closure, só executa
				if ($callback instanceof \Closure) {
					return $callback(...$params);
				}

				// 2) Se for string no formato "Classe@metodo"
				if (is_string($callback) && strpos($callback, '@') !== false) {
					list($class, $method) = explode('@', $callback);
					if (class_exists($class)) {
						$instance = new $class();
						if (method_exists($instance, $method)) {
							return $instance->$method(...$params);
						}
					}
					throw new \Exception("Callback inválido: {$callback}");
				}

				// 3) Se for array no formato [callable, param1, param2...]
				if (is_array($callback)) {
					$fn = array_shift($callback); // pega a função/método
					$args = array_merge($callback, $params); // junta params do array + params da rota
					if (is_string($fn) && strpos($fn, '@') !== false) {
						list($class, $method) = explode('@', $fn);
						if (class_exists($class)) {
							$instance = new $class();
							if (method_exists($instance, $method)) {
								return $instance->$method(...$args);
							}
						}
						throw new \Exception("Callback inválido: {$fn}");
					}
					if (is_callable($fn)) {
						return $fn(...$args);
					}
					throw new \Exception("Callback não executável");
				}

				// fallback
				if (is_callable($callback)) {
					return $callback(...$params);
				}

				throw new \Exception("Tipo de callback não suportado");
			}



		/*
		|------------------------------------------------------------------
		|	CRIA O REGEX 
		|------------------------------------------------------------------
		|	Criamos o regex que será validado na sequencia 
		|------------------------------------------------------------------
		*/
			public static function gerarRegex_OLD( $rota ){
				$rota             = str_replace( ["{","}"], ["｛", "｝"], $rota );
				$regex_parametros = "/｛(?'chamada'((((((?'parametro'([a-z0-9\_,]+))\:)?(?'valor'([^｛｝]+))))|(?R))*))｝/";
				$regex_final      = '';
				$regex_final      = preg_replace_callback( $regex_parametros,function ($match) {
					$novo = $match[0];
					$novo = str_replace(["｛", "｝"], ["(", ")"], $novo);
					if (isset($match['parametro']) && !empty($match['parametro'])) {
						$novo = str_replace($match['chamada'], "(?'" . str_replace(",", "___", $match['parametro']) . "'(" . $match['valor'] . "))", $novo);
					} else {
						$novo = str_replace($match['chamada'], "(?'" . str_replace(",", "___", $match['valor']) . "'_closure_+)", $novo);
					}
					return $novo;
				}, $rota );
				while( preg_match( "/\[\/(.*)\/\]/", $regex_final, $match ) ){
					$novo        = preg_replace( ["/^\[\//","/\/\]$/"], ["(\/",")?"], $match[0] );
					$regex_final = str_replace( $match[0], $novo, $regex_final );
				}
				$regex_final =  str_replace(	"_closure_", "[^\/]",	$regex_final );
				$regex_final = preg_replace(	"/^\//"           , "\/"   ,	$regex_final );
				$regex_final = preg_replace(	"/([^\\\])\//"    , "$1\/" ,	$regex_final );
				$regex_final = '/^' . $regex_final . '(\/)?$/';
				return $regex_final;
			} 


			public static function gerarRegex($rota) {
				// 1. Limpa e protege chaves
				$rota = trim($rota, '/');
				
				// 2. PROTEGE colchetes opcionais [/path/] temporariamente
				$rota = str_replace('[/', '___BRACKET_OPEN___', $rota);
				$rota = str_replace('/]', '___BRACKET_CLOSE___', $rota);
				
				// 3. Protege parâmetros {id}
				$rota = str_replace(["{", "}"], ["｛", "｝"], $rota);

				// 4. Processa parâmetros
				$regex_parametros = "/｛(?'chamada'((((((?'parametro'([a-z0-9\_,]+))\:)?(?'valor'([^｛｝]+))))|(?R))*))｝/";
				$regex_final = preg_replace_callback($regex_parametros, function ($match) {
					if (isset($match['parametro']) && !empty($match['parametro'])) {
						// Parâmetro com padrão: {id:[0-9]+}
						return "(?'" . str_replace(",", "___", $match['parametro']) . "'" . $match['valor'] . ")";
					} else {
						// Parâmetro simples: {id} → usa marcador temporário
						return "(?'" . str_replace(",", "___", $match['valor']) . "'___PARAM_PATTERN___)";
					}
				}, $rota);

				// 5. Suporte ao coringa *
				$regex_final = str_replace("*", "___WILDCARD___", $regex_final);

				// 6. Escapa barras PRIMEIRO
				$regex_final = str_replace('/', '\/', $regex_final);

				// 7. DEPOIS substitui os marcadores temporários
				$regex_final = str_replace('___PARAM_PATTERN___', '[^\/]+', $regex_final);
				$regex_final = str_replace('___WILDCARD___', '.*', $regex_final);

				// 8. Restaura colchetes opcionais
				$regex_final = str_replace('___BRACKET_OPEN___', '(\/', $regex_final);
				$regex_final = str_replace('___BRACKET_CLOSE___', ')?', $regex_final);

				// 9. Monta regex final
				return '/^' . $regex_final . '(\/)?$/i';
			}





		/*
		|------------------------------------------------------------------
		|	FORMATA ROTA
		|-------------------------------------------------------------------
		|	Tratamos os parâmetros da rota 
		|-------------------------------------------------------------------
		*/
			public function formatParamsRoute( $match ){
				$novo = $match[0];
				$novo = str_replace( ["｛","｝"], ["(",")"], $novo );
				if( isset( $match['parametro'] ) && !empty( $match['parametro'] ) ){
					$novo = str_replace( $match['chamada'], "(?'" . str_replace( ",", "___", $match['parametro'] ) . "'(" . $match['valor'] . "))", $novo );
				} else {
					$novo = str_replace( $match['chamada'], "(?'" . str_replace( ",", "___", $match['valor'] ) . "'_closure_+)", $novo );
				}
				return $novo;
			}

		/*
		|------------------------------------------------------------------
		|    RETORNA OS PARÂMETROS DA ROTA
		|-------------------------------------------------------------------
		|    Agora processamos o regex criado e retornamos
		|    caso a URL esteja correta e dentro do que espera-se
		|-------------------------------------------------------------------
		*/
			static public function parametrosRota($_ROTA,$FAKE_ROUTE=NULL){
				$_REGEX = self::gerarRegex(trim($_ROTA,'/'));
				if (preg_match($_REGEX, ($FAKE_ROUTE??self::urlPath()), $resultado)) {
					foreach ($resultado as $k => $_VALOR) {
						if (is_numeric($k)) {
							unset($resultado[$k]);
						} else {
							if (preg_match("/___/", $k)) {
								$parametro = explode("___", $k);
								unset($resultado[$k]);
								$_CHAVE				= $parametro[0];
								$_TRATAMENTO		= $parametro[1];
								$resultado[$_CHAVE] = $_TRATAMENTO((is_string($_VALOR)) ? urldecode($_VALOR) : $_VALOR);
							}
						}
					}
					return [
						'status'=>true,
						'regex'=>$_REGEX,
						'setada'=>($FAKE_ROUTE??self::urlPath()),
						'rota'=>trim($_ROTA,'/'),
						'params'=>$resultado
					];
				} else {
					return [
						'status'=>false,
						'regex'=>$_REGEX,
						'setada'=>($FAKE_ROUTE??self::urlPath()),
						'rota'=>trim($_ROTA,'/'),
						'params'=>[]
					];
				}
			}

			public function parametrosRotaInstance_OLD($_ROTA,$FAKE_ROUTE=NULL){
				$_REGEX = self::gerarRegex(trim($_ROTA,'/'));
				if (preg_match($_REGEX, ($FAKE_ROUTE??self::urlPath()), $resultado)) {
					foreach ($resultado as $k => $_VALOR) {
						if (is_numeric($k)) {
							unset($resultado[$k]);
						} else {
							if (preg_match("/___/", $k)) {
								$parametro = explode("___", $k);
								unset($resultado[$k]);
								$_CHAVE				= $parametro[0];
								$_TRATAMENTO		= $parametro[1];
								$resultado[$_CHAVE] = $_TRATAMENTO((is_string($_VALOR)) ? urldecode($_VALOR) : $_VALOR);
							}
						}
					}
					return [
						'status'=>true,
						'regex'=>$_REGEX,
						'setada'=>($FAKE_ROUTE??self::urlPath()),
						'rota'=>trim($_ROTA,'/'),
						'params'=>$resultado
					];
				} else {
					return [
						'status'=>false,
						'regex'=>$_REGEX,
						'setada'=>($FAKE_ROUTE??self::urlPath()),
						'rota'=>trim($_ROTA,'/'),
						'params'=>[]
					];
				}
			}

			public function parametrosRotaInstance($_ROTA,$FAKE_ROUTE=NULL){
				$_REGEX = self::gerarRegex(trim($_ROTA,'/'));
				
				try {
					$url = $FAKE_ROUTE ?? self::urlPath();
					
					// Tenta fazer o match
					$result = @preg_match($_REGEX, $url, $resultado);
					
					// Se deu erro no regex
					if ($result === false) {
						// echo "<pre>";
						// echo "ERRO NO PREG_MATCH!\n";
						// echo "ROTA: " . $_ROTA . "\n";
						// echo "URL: " . $url . "\n";
						// echo "REGEX: " . $_REGEX . "\n";
						// echo "ERRO: " . preg_last_error_msg() . "\n";
						// echo "</pre>";
						
						return [
							'status'=>false,
							'regex'=>$_REGEX,
							'setada'=>$url,
							'rota'=>trim($_ROTA,'/'),
							'params'=>[]
						];
					}
					
					if ($result > 0) {
						foreach ($resultado as $k => $_VALOR) {
							if (is_numeric($k)) {
								unset($resultado[$k]);
							} else {
								if (preg_match("/___/", $k)) {
									$parametro = explode("___", $k);
									unset($resultado[$k]);
									$_CHAVE = $parametro[0];
									$_TRATAMENTO = $parametro[1];
									$resultado[$_CHAVE] = $_TRATAMENTO((is_string($_VALOR)) ? urldecode($_VALOR) : $_VALOR);
								}
							}
						}
						return [
							'status'=>true,
							'regex'=>$_REGEX,
							'setada'=>$url,
							'rota'=>trim($_ROTA,'/'),
							'params'=>$resultado
						];
					} else {
						return [
							'status'=>false,
							'regex'=>$_REGEX,
							'setada'=>$url,
							'rota'=>trim($_ROTA,'/'),
							'params'=>[]
						];
					}
				} catch (\Exception $e) {
					echo "<pre>";
					echo "EXCEÇÃO CAPTURADA!\n";
					echo "ROTA: " . $_ROTA . "\n";
					echo "REGEX: " . $_REGEX . "\n";
					echo "ERRO: " . $e->getMessage() . "\n";
					echo "</pre>";
					die();
				}
			}
		/*
		|------------------------------------------------------------------
		|	FILTRANDO OS PARÂMETROS
		|-------------------------------------------------------------------
		|
		|	Aqui apenas retiramos os parâmetros enviados 
		|	que não estão autorizados a passar
		|
		|
		*/

			public function filterParameters($_PARAMS){
				if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
					parse_str(file_get_contents("php://input"), $_PUT);
					foreach ($_PUT as $key => $value) {
						unset($_PUT[$key]);
						$_PUT[str_replace('amp;', '', $key)] = $value;
					}
				}
				$_GET       = ($_SERVER['REQUEST_METHOD']=='GET')   ? array_intersect_key($_GET,array_flip($_PARAMS)) : $_GET;
				$_POST      = ($_SERVER['REQUEST_METHOD']=='POST')  ? array_intersect_key($_POST,array_flip($_PARAMS)) : $_POST;
				$_PUT   	= array_intersect_key(($_PUT??[]),array_flip($_PARAMS));
				$_REQUEST   = array_intersect_key($_REQUEST,array_flip($_PARAMS));
				return $this;
			}

		/*
		|------------------------------------------------------------------
		|	EXIGINDO PARÂMTROS
		|-------------------------------------------------------------------
		|
		|	Aqui verificamos se os parâmetros enviados existem ou estão faltando
		|	Caso estejam incorretos, sobrando ou faltando gera o erro.
		|	Parâmetros: (string|array, function)
		|
		|
		*/
			public function requireParameters($_PARAMS,$_ERROR=null){
				if(is_array($_PARAMS) && count($_PARAMS)>0){
					if ($_SERVER['REQUEST_METHOD'] == 'GET')	{$_PARAMETROS = array_intersect_key($_GET,array_flip($_PARAMS));}
					if ($_SERVER['REQUEST_METHOD'] == 'POST')	{$_PARAMETROS = array_intersect_key($_POST,array_flip($_PARAMS));}
					if ($_SERVER['REQUEST_METHOD'] == 'PUT')	{
						parse_str(file_get_contents("php://input"), $_PUT);
						foreach ($_PUT as $key => $value) {
							unset($_PUT[$key]);
							$_PUT[str_replace('amp;', '', $key)] = $value;
						}
						$_PARAMETROS = array_intersect_key(($_PUT??[]),array_flip($_PARAMS));
					}
					if(array_keys($_PARAMETROS)!=$_PARAMS){
						self::execFn($_ERROR,'PARÂMETROS INVÁLIDOS');
					} 
				}
				return $this;
			}

		/*
		|------------------------------------------------------------------
		|	FUNÇÕES LIBERADAS PARA EXECUÇÃO
		|-------------------------------------------------------------------
		|
		|	Aqui é exclusivo para meu Framework, pois toda requisição é feita via WS
		|	Então, bloqueamos qualquer função não autorizada
		|	Parâmetros: (string|array , function)
		|
		|
		*/

			public function function($_FUNCTIONS=null,$_ERRO=null){
				$_FUNCTIONS= (is_string($_FUNCTIONS) && !is_numeric($_FUNCTIONS) ) ? [$_FUNCTIONS] : ((is_array($_FUNCTIONS))? $_FUNCTIONS : null);
				if (count($_FUNCTIONS)>0) {
					if (isset($_REQUEST['function']) && !in_array($_REQUEST['function'], $_FUNCTIONS)) {
						if(is_callable($_ERRO)){
							$_ERRO();
						}else{
							http_response_code(403);
							die('ILEGAL REQUEST_METHOD');
						}
					}
				}
				return $this;
			}

		/*
		|------------------------------------------------------------------
		|	INSERE A ROTA NA CLASSE
		|-------------------------------------------------------------------
		|
		|
		*/
			public static function route($_ROTA,$FAKE_ROUTE=NULL){
					$full_route = "";
					foreach (self::$group_routers as $group) {
						$full_route .= $group . '/';
					}
					$full_route .= $_ROTA;
				self::$handler = self::parametrosRota($full_route, $FAKE_ROUTE);
				return new static;
			}

			public function routeInstance($_ROTA, $FAKE_ROUTE = null) {
				$full_route = "";
				foreach ($this->group_routersInstance as $group) {
					$full_route .= $group . '/';
				}
				$full_route .= $_ROTA;

				$this->handlerInstance = $this->parametrosRota($full_route, $FAKE_ROUTE);
				return $this;
			}

		/*
		|------------------------------------------------------------------
		|	MIDDLEWARES
		|-------------------------------------------------------------------
		*/
			private static function callMiddleware($middlewares, $callback, $return = []) {
				// Garantir que `$middlewares` seja um array
				$middlewares = is_array($middlewares) ? $middlewares : [$middlewares];

				// Garantir que `$next` seja um callback válido
				$next = is_callable($callback) ? $callback : fn() => null;

				// Iterar sobre os middlewares em ordem inversa
				foreach (array_reverse($middlewares) as $middleware) {
					if (is_callable($middleware)) {
						// Middleware é uma função anônima ou callable
						$next = fn($return) => $middleware($return, $next);
					} else {
						// Resolver middlewares no formato `Class@method`
						[$middleware_class, $middleware_method] = explode('@', $middleware) + [1 => 'handle'];

						if (class_exists($middleware_class)) {
							// Resolver método da classe
							$middleware_object = new $middleware_class();
							if (method_exists($middleware_object, $middleware_method)) {
								$next = fn($return) => $middleware_object->$middleware_method($return, $next);
							}
						} elseif (self::loadMiddlewareFile($middleware_class)) {
							// Middleware encontrado em arquivo externo
							$middleware_object = new $middleware_class();
							if (method_exists($middleware_object, $middleware_method)) {
								$next = fn($return) => $middleware_object->$middleware_method($return, $next);
							}
						}
					}
				}

				// Executar o primeiro middleware da cadeia
				return $next($return);
			}

			public function callMiddlewareInstance($middlewares, $callback, $return = []) {
				// Garantir que `$middlewares` seja um array
				$middlewares = is_array($middlewares) ? $middlewares : [$middlewares];

				// Garantir que `$next` seja um callback válido
				$next = is_callable($callback) ? $callback : fn() => null;

				// Iterar sobre os middlewares em ordem inversa
				foreach (array_reverse($middlewares) as $middleware) {
					if (is_callable($middleware)) {
						// Middleware é uma função anônima ou callable
						$next = fn($return) => $middleware($return, $next);
					} else {
						// Resolver middlewares no formato `Class@method`
						[$middleware_class, $middleware_method] = explode('@', $middleware) + [1 => 'handle'];

						if (class_exists($middleware_class)) {
							// Resolver método da classe
							$middleware_object = new $middleware_class();
							if (method_exists($middleware_object, $middleware_method)) {
								$next = fn($return) => $middleware_object->$middleware_method($return, $next);
							}
						} elseif ($this->loadMiddlewareFileInstance($middleware_class)) {
							// Middleware encontrado em arquivo externo
							$middleware_object = new $middleware_class();
							if (method_exists($middleware_object, $middleware_method)) {
								$next = fn($return) => $middleware_object->$middleware_method($return, $next);
							}
						}
					}
				}

				// Executar o primeiro middleware da cadeia
				return $next($return);
			}

			private static function loadMiddlewareFile($middleware_class) {
				$filePath	= realpath(__DIR__ . '/../../../../');
				$pattern	= $filePath . DIRECTORY_SEPARATOR . $middleware_class . '*.php';
				$files		= glob($pattern);
				if (empty($files)) {
					$files = glob($filePath . $middleware_class . '.php');
				}
				foreach ($files as $file) {
					require_once $file;
				}
				return class_exists($middleware_class);
			}

			public function loadMiddlewareFileInstance($middleware_class) {
				$filePath = realpath(__DIR__ . '/../../../../');
				$pattern  = $filePath . DIRECTORY_SEPARATOR . $middleware_class . '*.php';
				$files    = glob($pattern);
				if (empty($files)) {
					$files = glob($filePath . DIRECTORY_SEPARATOR . $middleware_class . '.php');
				}
				foreach ($files as $file) {
					require_once $file;
				}
				return class_exists($middleware_class);
			}

		/*
		|------------------------------------------------------------------
		|	GROUPS
		|-------------------------------------------------------------------
		*/
			public static function verifyGroup($_GRUPO){
				$MODELO 		=	trim($_GRUPO, '/');
				$MODEL_VALIDO 	=	preg_match('/^[a-zA-Z0-9\/\-]+$/', $MODELO);
				$GRUPO_STRING	=	implode('/',self::$group_routers);
				$GRUPO_ARRAY	=	explode('/',$GRUPO_STRING);
				$URL_BROWSER	=	explode('/',trim(self::urlPath(), '/'));
				$COUNT			=	count($GRUPO_ARRAY);
				$RANGE1 		=	array_slice($GRUPO_ARRAY,0,$COUNT);
				$RANGE2 		=	array_slice($URL_BROWSER,0,$COUNT);
				return ($MODEL_VALIDO && $RANGE1==$RANGE2);
			}

			public static function groupStatic($config, $callback=null,$group=null){

				if(is_array($config) || !is_null($group)){
					if(isset($config['prefix']) || !is_null($group)){

						array_push(self::$group_routers, ($group??$config['prefix']));
						if(self::verifyGroup(($group??$config['prefix']))){
							if(isset($config['middleware'])){

								self::callMiddleware($config['middleware'], function($retornos)use($callback){
									if (is_callable($callback)) {
										$callback($retornos);
										array_pop(self::$group_routers);
										return new static;
									}
								});
							}else{
								if (is_callable($callback)) {
									$callback();
									array_pop(self::$group_routers);
									return new static;
								}
							}
						}else{
							self::$group_routers = [];
							return new static;
						}
					}
				}else{
					array_push(self::$group_routers, $_GRUPO);
					if(self::verifyGroup($_GRUPO)){
						if (is_callable($_ROUTERS)) {
							$_ROUTERS();
							array_pop(self::$group_routers);
							return new static;
						}
					}else{
						self::$group_routers = [];
						return new static;
					}

				}
				
			}

		/*
		|------------------------------------------------------------------
		|	VERIFICAÇÃO AO SEU GOSTO 
		|-------------------------------------------------------------------
		|	Poderá ser colocado uma função no $_VAR ou um parametro boleano 
		*/
			public function verify($_VAR,$_RETORNO){
				if($_VAR==false){
					if (is_callable($_RETORNO)) {
						$_RETORNO($_VAR);
					} else {
						http_response_code(403);
						die('Error 403');
					}
				}
				return $this;
			}

		/*
		|------------------------------------------------------------------
		|	CALL STATIC MANDA OS DADOS
		|-------------------------------------------------------------------
		|
		|   Verificamos se o Método está liberado e retornamos
		|   Caso seja de outra natureza, retorna o erro.
		|   Parametros: (string, function, function)  
		|
		|
		*/
			public static function send($_REQUEST_METHOD="GET",$_PATH=null,$_SUCESS=null, $_ERROR=null){
				if(is_array($_PATH)){
					if(isset($_PATH['middleware'])){
						self::callMiddleware($_PATH['middleware'], function($retornos)use($_PATH,$_REQUEST_METHOD, $_SUCESS,$_ERROR){
							self::route($_PATH['prefix']);
							self::$middleware =$retornos;
							return self::request($_REQUEST_METHOD,$_SUCESS,$_ERROR);
						});
					}

					if(isset($_PATH['prefix'])){
						$_PATH = $_PATH['prefix'];
					}
				}

				self::route($_PATH);
				return self::request($_REQUEST_METHOD,$_SUCESS,$_ERROR);

			}

		/*
		|------------------------------------------------------------------
		|	REALIZA A REQUISIÇÃO
		|-------------------------------------------------------------------
		|
		|   Verificamos se o Método está liberado e retornamos
		|   Caso seja de outra natureza, retorna o erro.
		|   Parametros: (string, function, function)  
		|
		*/

		public static function request2($_REQUEST_METHOD=null,$_SUCESS=null,$_ERROR=null){
			static $executed = false;
			if(self::$handler['status']==true && !$executed){
				$executed = true;
				$PARAMS_URL = array_values(self::$handler['params']);
				$REQ1 = (!is_array($_REQUEST_METHOD)) ? [strtoupper(trim($_REQUEST_METHOD))] : $_REQUEST_METHOD;
				$REQ2 = strtoupper(trim($_SERVER['REQUEST_METHOD']));
				if(in_array($REQ2, $REQ1) || $REQ1[0]=='ANY'){
					if(is_array($_SUCESS)){
						foreach($_SUCESS as $callback){
							self::execFn($callback, ...$PARAMS_URL);
						}
					}else{
						self::execFn($_SUCESS, ...$PARAMS_URL);
					}
				}else{
					if(is_callable($_ERROR)){
						self::execFn($_ERROR, 'ILEGAL REQUEST_METHOD: '.trim($REQ2));
					}else{
						http_response_code(403);
						die('ILEGAL REQUEST_METHOD '.trim($REQ2));
					}
				}
			}
		}

		public static function request($_REQUEST_METHOD=null, $_SUCESS=null, $_ERROR=null){
			static $executed = false;
			if(self::$handler['status']==true && !$executed){
				$executed = true;
				$PARAMS_URL = array_values(self::$handler['params']);
				$REQ1 = (!is_array($_REQUEST_METHOD)) ? [strtoupper(trim($_REQUEST_METHOD))] : $_REQUEST_METHOD;
				$REQ2 = strtoupper(trim($_SERVER['REQUEST_METHOD']));
				if(in_array($REQ2, $REQ1) || $REQ1[0]=='ANY'){
					$callbacks = is_array($_SUCESS) ? $_SUCESS : [$_SUCESS];
					foreach($callbacks as $callback){
						if(is_string($callback) && strpos($callback, '@') !== false){
							list($class, $method) = explode('@', $callback);
							$instance = new $class;
							call_user_func_array([$instance, $method], $PARAMS_URL);
						}else if(is_array($callback) && count($callback) == 2){
							if(is_string($callback[0])){
								$callback[0] = new $callback[0];
							}
							call_user_func_array($callback, $PARAMS_URL);
						}else if(is_callable($callback)){
							call_user_func_array($callback, $PARAMS_URL);
						}
					}
				}else{
					if(is_callable($_ERROR)){
						self::execFn($_ERROR, 'ILEGAL REQUEST_METHOD: '.trim($REQ2));
					}else{
						http_response_code(403);
						die('ILEGAL REQUEST_METHOD '.trim($REQ2));
					}
				}
			}
		}

		public function requestInstance($_REQUEST_METHOD=null, $_SUCESS=null, $_ERROR=null) {
			static $executed = false;
			if (!$this->handlerInstance || $this->handlerInstance['status'] !== true || $executed) return;
			$executed = true;

			$PARAMS_URL = array_values($this->handlerInstance['params'] ?? []);
			$REQ1 = (!is_array($_REQUEST_METHOD)) ? [strtoupper(trim($_REQUEST_METHOD ?? 'ANY'))] : $_REQUEST_METHOD;
			$REQ2 = strtoupper(trim($_SERVER['REQUEST_METHOD'] ?? 'GET'));

			if (in_array($REQ2, $REQ1) || $REQ1[0] === 'ANY') {
				$callbacks = is_array($_SUCESS) ? $_SUCESS : [$_SUCESS];
				foreach ($callbacks as $callback) {
					if (is_string($callback) && strpos($callback, '@') !== false) {
						list($class, $method) = explode('@', $callback);
						$instance = new $class;
						call_user_func_array([$instance, $method], $PARAMS_URL);
					} elseif (is_array($callback) && count($callback) == 2) {
						if (is_string($callback[0])) $callback[0] = new $callback[0];
						call_user_func_array($callback, $PARAMS_URL);
					} elseif (is_callable($callback)) {
						call_user_func_array($callback, $PARAMS_URL);
					}
				}
			} else {
				if (is_callable($_ERROR)) {
					$this->execFnInstance($_ERROR, 'ILEGAL REQUEST_METHOD: '.trim($REQ2));
				} else {
					http_response_code(403);
					die('ILEGAL REQUEST_METHOD '.trim($REQ2));
				}
			}
		}


	}




