<?

namespace IsraelNogueira\fastRouter;
/**
 * -------------------------------------------------------------------------
 *		@author Israel Nogueira <israel@feats.com>
 *		@package library
 *		@license GPL-3.0-or-later
 *		@copyright 2023 Israel Nogueira
 *		@link https://github.com/israel-nogueira/fast-router
 * -------------------------------------------------------------------------
 * 
 * 		Classe fastRouter para rotas: 
 * 		Uma classe extremamnte simples para gerenciamento de rotas
 * 		com controle de request, verificações de parametros
 * 		e aninhamento de rotas
 * 
 * -------------------------------------------------------------------------
 */

/*
	function([],function($ERRO){}) 				//	['app:fn1','app:fn2','app:fn3'] se nao existe a function gera erro
	->filterParameters([])						//	['a','b','c'] Filtra o param e deixa passar apenas esses
	->requireParameters([],function(){})		//	['a','b','c'] exige o param, se não tiver barra
	->verify(function(){}, function(){})		//	@param1 é uma FN q retorna true|false, @param2 function de erro 
	
	->any('/path1/{{ID}}/{{NOME}}',function($SUCCESS){},function($ERROR){})
	->get('/path1/{{ID}}/{{NOME}}',function($SUCCESS){},function($ERROR){})
	->post('/path1/{{ID}}/{{NOME}}',function($SUCCESS){},function($ERROR){})
	->put('/path1/{{ID}}/{{NOME}}',function($SUCCESS){},function($ERROR){})
	
	
	router::group('/path1',function($SUCCESS){

	})
	router::group(['prefix'=>'/path1','middleware'=>[fn,fn,fn]],function(){

	})
	

*/


	class router{


		public static $group_routers 			= [];
		public static $paramsHandler 			= null;

			
		public static function __callStatic($name, $arguments){

			if (in_array(strtoupper($name), ['ANY','MATH','GET', 'REDIRECT','POST','RMDIR','MKDIR','INDEX','MOVE','TRACE','DELETE','TRACK','PUT','HEAD','OPTIONS','CONNECT'])) {
				
				if(strtoupper($name)=='MATH' && is_array($arguments[0])){
					$name = $arguments[0];
					array_shift($arguments);
				}

				self::send($name,...$arguments);

			} else {
				self::$name(...$arguments);
			}
			exit;
		}
		public function __construct(){}
		
		/*
		|------------------------------------------------------------------
		|    RETORNA A URL
		|------------------------------------------------------------------
		*/

			static function urlPath($node = null, $debug = true) {
				if (substr($_SERVER['REQUEST_URI'], 0, 1) == '/')
					$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 1, strlen($_SERVER['REQUEST_URI']));
				if (is_string($node)) {
					throw new RuntimeException("Erro: Isso não é um número ->    self::urlPath('" . $node . "')");
				} elseif ($node == null && $node == 0) {
					$REQUEST_URL = explode('?', $_SERVER['REQUEST_URI']);
					$url         = $REQUEST_URL[0];
					return $url;
				} else {
					$REQUEST_URL = explode('?', $_SERVER['REQUEST_URI']);
					$url         = $REQUEST_URL[0];
					if (substr($url, -1) == '/') {
						$url = substr($url, 0, -1);
					}
					$GET = explode('/', $url);
					if ($node > count($GET)) {
						if ($debug == true) {
							throw new RuntimeException("Erro: Não existe path nesta posição ->    self::urlPath(" . $node . ")");
						} else {
							return false;
						}
					} else {
						return $GET[($node - 1)];
					}
				}
			}
		
		/*
		|------------------------------------------------------------------
		|	CRIA O REGEX 
		|------------------------------------------------------------------
		|	Criamos o regex que será validado na sequencia 
		|------------------------------------------------------------------
		*/
			public function generate($routeName, array $params = [])
				{

					// Check if named route exists
					if (!isset($this->namedRoutes[$routeName])) {
						throw new RuntimeException("Route '{$routeName}' does not exist.");
					}

					// Replace named parameters
					$route = $this->namedRoutes[$routeName];

					// prepend base path to route url again
					$url = $this->basePath . $route;

					if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
						foreach ($matches as $index => $match) {
							list($block, $pre, $type, $param, $optional) = $match;

							if ($pre) {
								$block = substr($block, 1);
							}

							if (isset($params[$param])) {
								// Part is found, replace for param value
								$url = str_replace($block, $params[$param], $url);
							} elseif ($optional && $index !== 0) {
								// Only strip preceding slash if it's not at the base
								$url = str_replace($pre . $block, '', $url);
							} else {
								// Strip match block
								$url = str_replace($block, '', $url);
							}
						}
					}

					return $url;
				}
			public static function gerarRegex( $rota ){
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
						if (is_callable($_ERROR)) {
							$_ERROR('PARÂMETROS INVÁLIDOS');
						} 
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
						$full_route .= $group['route'] . '/';
					}
					$full_route .= $_ROTA;
				self::$paramsHandler = self::parametrosRota($full_route, $FAKE_ROUTE);

				return new static;
			}

		/*
		|------------------------------------------------------------------
		|	MIDDLEWARES
		|-------------------------------------------------------------------
		|
		|
		*/

			private static function callMiddleware($middlewares, $callback) {
				$middlewares = (!is_array($middlewares)) ? [$middlewares] : $middlewares;
				$next = $callback;
				foreach (array_reverse($middlewares) as $middleware) {
					if (is_callable($middleware)) {
						$next = fn() => call_user_func($middleware, $next);
					} else {
						[$middleware_class, $middleware_method] = explode('@', $middleware) + [1 => 'handle'];
						if (is_callable([$middleware_class, $middleware_method])) {
							$next = fn() => call_user_func([$middleware_class, $middleware_method], $next);
						} elseif (is_subclass_of($middleware_class, self::class)) {
							$middleware_instance = new $middleware_class;
							$next = fn() => call_user_func([$middleware_instance, $middleware_method], $next);
						} elseif (method_exists(static::class, $middleware_method)) {
							$next = fn() => call_user_func([static::class, $middleware_method], $next);
						} elseif (method_exists(get_called_class(), $middleware_method)) {
							$next = fn() => call_user_func([get_called_class(), $middleware_method], $next);
						}
					}
				}
				$next();
			}

		/*
		|------------------------------------------------------------------
		|	GROUPS
		|-------------------------------------------------------------------
		|
		|
		*/
			public static function group($_ROTA, $_ROUTERS=NULL){
				if(is_array($_ROTA)){
					if(isset($_ROTA['middleware'])){
						self::callMiddleware($_ROTA['middleware'], function()use($_ROUTERS){
							if(isset($_ROTA['prefix'])){
								array_push(self::$group_routers, array('route' => $_ROTA['prefix'], 'routers' => array()));
								if (is_callable($_ROUTERS)) {
									$_ROUTERS();
									array_pop(self::$group_routers);
									return new static;
								}
							}else{
								if (is_callable($_ROUTERS)) {
									$_ROUTERS();
									return new static;
								}
							}
						});
					}
				}else{
					array_push(self::$group_routers, array('route' => $_ROTA, 'routers' => array()));
					if (is_callable($_ROUTERS)) {
						$_ROUTERS();
						array_pop(self::$group_routers);
						return new static;
					}
				}
			}

		/*
		|------------------------------------------------------------------
		|	VERIFICAÇÃO AO SEU GOSTO 
		|-------------------------------------------------------------------
		|	Poderá ser colocado uma função no $_VAR ou um parametro boleano 
		|
		|
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
			public static function request($_REQUEST_METHOD=null,$_SUCESS=null,$_ERROR=null){
				if(self::$paramsHandler['status']==true){
					



					$REQ1 = (!is_array($_REQUEST_METHOD))?[strtoupper(trim($_REQUEST_METHOD))]:$_REQUEST_METHOD;
					$REQ2 = strtoupper(trim($_SERVER['REQUEST_METHOD']));
					
					if(  in_array($REQ2,$REQ1 ) ||  $REQ1[0]=='ANY'	){
						
						$_PARAMS = array_values(self::$paramsHandler['params']);
						if (is_callable($_SUCESS)) { 
							$_SUCESS(...$_PARAMS);
						}
					}else{
						if (is_callable($_ERROR)) {
							$_ERROR('ILEGAL REQUEST_METHOD: '.trim($REQ2));
						}else{
							http_response_code(403);
							die('ILEGAL REQUEST_METHOD '.trim($REQ2));
						}
					}
				}
			}
	}




