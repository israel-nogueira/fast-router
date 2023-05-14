<p align="center">
    <img src="https://github.com/israel-nogueira/fast-router/blob/main/src/topo_README.png" width="650"/>
</p>
<p align="center">
    <a href="#instalação" target="_Self">Instalação</a> |
    <a href="#primeiros-passos" target="_Self">Primeiros passos</a> |
    <a href="#agrupamentos" target="_Self">Agrupamentos</a> |
    <a href="#middlewares" target="_Self">Middlewares</a> |
    <a href="#regex" target="_Self">Regex</a> |
</p>
<p align="center">
    <a href="https://packagist.org/packages/israel-nogueira/galaxy-db"><img src="https://poser.pugx.org/israel-nogueira/galaxy-db/v/stable.svg"></a>
    <a href="https://packagist.org/packages/israel-nogueira/galaxy-db"><img src="https://poser.pugx.org/israel-nogueira/galaxy-db/downloads"></a>
    <a href="https://packagist.org/packages/israel-nogueira/galaxy-db"><img src="https://poser.pugx.org/israel-nogueira/galaxy-db/license.svg"></a>
  
</p>
<p align="center">
Classe para gerenciar as suas rotas no PHP com facilidade e segurança.<br/>
</p>

## INSTALAÇÃO

Instale via composer.

```plaintext
    composer require israel-nogueira/fast-router
```


## PRIMEIROS PASSOS

Basta importar o autoload e inserir o namespace

```php
<?php
    include "vendor\autoload.php";
    use IsraelNogueira\fastRouter\router;
?>
```

Aqui segue um exemplo de uma aplicação simples da classe

```php
<?php

	namespace IsraelNogueira\Models;
	use IsraelNogueira\fastRouter\router;

	//-------------------------------------------------------------------------------
	// No modo estático a requisição é direto no método
	//-------------------------------------------------------------------------------

		router::get('admin/path1/path2', function () {});
		router::post('admin/path1/path2', function () {});
		router::put('admin/path1/path2', function () {});
		router::delete('admin/path1/path2', function () {});
	
	//-------------------------------------------------------------------------------
	// O método "any" aceita qualquer tipo de requisição
	//-------------------------------------------------------------------------------
	
		router::any('admin/path1/path2', function () {});    		
	
	//-------------------------------------------------------------------------------
	// Ele aceita o seguinte grupo de requests:
	//-------------------------------------------------------------------------------
	// 'ANY','MATH','GET', 'REDIRECT','POST','RMDIR','MKDIR',
	// 'INDEX','MOVE','TRACE','DELETE','TRACK','PUT','HEAD','OPTIONS','CONNECT'
	//
	//
	//  math aceitará os métodos listados na array 
	//-------------------------------------------------------------------------------
	
	router::math(['POST','GET'],'admin/path1/path2', function () {});
	
	
?>
```

# AGRUPAMENTOS

```php

<?php
	namespace IsraelNogueira\Models;
	use IsraelNogueira\fastRouter\router;

	/*
	
	Rotas:
	/admin
	/admin/usuarios
	/admin/fotos
	/admin/produtos/detalhes
	/admin/produtos/fotos
	
	*/


	router::group('admin',function(){
		router::get('usuarios', function () {});
		router::get('fotos', function () {});
		router::group('produtos',function(){
			router::get('detalhes', function () {});
			router::get('fotos', function () {});
		})
	})

?>
```

# MIDDLEWARES

As middlewares são aplicáveis de  uma forma muito simples:


```php

<?php
	namespace IsraelNogueira\Models;
	use IsraelNogueira\fastRouter\router;

	router::group([
			'prefix'=>'/admin',
			'middleware'=>['App/Middlewares/auth@middl_1','App/Middlewares/auth@middl_2','App/Middlewares/auth@middl_3']],
			function(){
				callback();
			});
	
	
?>
```

Cada função é definida de maneira que a próxima é executada apenas se a atual finalizou com sucesso;
É passado como parametro único uma função _closure_ da próxima:

```php
<?php
	namespace IsraelNogueira\Models;
	use IsraelNogueira\fastRouter\router;
	
	function middl_1($next){
		// faz o que tiver que fazer
		// ... ... ... 
		// executa a próxima
		$next();
	}
	
	function middl_2($next){
		// faz o que tiver que fazer
		// ... ... ... 
		// executa a próxima
		$next();
	}
	
	function middl_3($next){
		// faz o que tiver que fazer
		// ... ... ... 
		// executa a próxima
		$next();
	}


	router::group([
		'prefix'=>'/admin',
		'middleware'=>['middl_1','middl_2','middl_3']],
		function(){callback();}
	);



```

# REGEX

```php
<?php

	namespace IsraelNogueira\Models;
	use IsraelNogueira\fastRouter\router;

	//-------------------------------------------------------
	// Variáveis ID e NOME podem ser passadas como parâmetros
	//-------------------------------------------------------

		router::get('admin/{ID}/{NOME}', function ($ID,$NOME) {});
	
	//--------------------------------------------------------------------------------------
	// No regex, a expressão {idade[0-9]+} é uma expressão regular que define 
	// um padrão de correspondência de texto que procura por uma string que 
	// começa com a sequência de caracteres idade, seguida por um ou mais dígitos de 0 a 9.
	// eo O \d representa qualquer dígito numérico e o sinal de + significa 
	// que o padrão anterior deve aparecer uma ou mais vezes na string correspondente. 
	//--------------------------------------------------------------------------------------
	
		router::get('admin/{idade:[0-9]+}/{id:\d+}', function ($iddade,$id) {});
	
	/*
	|-----------------------------------------------------------------------------------
	| NÃO OBRIGATORIEDADE DO PARAMETRO
	|-----------------------------------------------------------------------------------
	|
	|  Caso não queira um parametro obrigatório basta colocar ele nesse formato: [/{param}/] exemplo:
	|
	|-----------------------------------------------------------------------------------
	|*/
	
		router::get('admin/{id:\d+}[/{{title}}/]', function ($id,$title) {});
	
	/*
	|-----------------------------------------------------------------------------------
	| Para parametros não obrigatórios enclosurados
	|-----------------------------------------------------------------------------------
	| 
	| Isso quer dizer que apenas o admin, id e nome são obrigatórios. 
	| Quanto ao enclousuramento ficará com o formato [/{param}/] com barra no inicio e no fim;  
	| Enclosurando fica: [/{{nome}}[/{{sobrenome}}/]/]
	| Para facilitar a visualização será algo mais ou menos assim:
	| [/ param1 
	|	[/ param1 
	|		[/ param1 
	|			[/ param1 /] 
	|		/]
	|	/]
	| /]
	-------------------------------------------------------------------------------------
	*/

	router::post('admin/{id:\d+}[/{{title}}[/{{length}}[/{{last}}/]/]/]', function ($id,$title,$length,$last) {});
	
?>
```















