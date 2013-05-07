<?php

	$opSelecionada = $_REQUEST['op'];
	$idioma = $_REQUEST['idioma'];
	$act = $_REQUEST['activo'];	
	$idConteudo = $_REQUEST['id'];
	
	$activo = 'true';	
	if( $act == "n" )
		$activo = 'false';
	
	if($opSelecionada == "POI" || $opSelecionada == "Eventos")
	{
		$latitude = $_REQUEST['lat'];
		$longitude = $_REQUEST['lon'];
		$dataInicio = $_REQUEST['dataInicio'];
		$dataFim = $_REQUEST['dataFim'];
		$imagem = $_REQUEST['imagem'];
		$morada = $_REQUEST['morada'];
		$contacto = $_REQUEST['contacto'];
		$codPostal = $_REQUEST['codPostal'];
		$localidade = $_REQUEST['localidade'];
		$nrPorta = $_REQUEST['nrPorta']; 
		$email = $_REQUEST['email'];
		$url = $_REQUEST['url'];
		$idCategoria = $_REQUEST['categoria'];		
		
		if( $opSelecionada == "Eventos")
		{
			$dataInicioEvento = $_REQUEST['dInicioEvento'];
			$dataFimEvento = $_REQUEST['dFimEvento'];
		}
	} 
	else if ( $opSelecionada == "Categoria") 
	{
		$icon = $_REQUEST['icon'];
		if ( $_REQUEST['tipoCat'] == "POI" )
			$tipoCategoria = 1;
		else if ( $_REQUEST['tipoCat'] == "Evento" )
			$tipoCategoria = 2;
	} 
	else if ( $opSelecionada == "Percursos")
	{
		$dataInicio = $_REQUEST['dataInicio'];
		$dataFim = $_REQUEST['dataFim'];
		$modoDeslocacao = 0;
		if( $_REQUEST['mDesloc'] == "pe" )
			$modoDeslocacao = 1;
		else if ( $_REQUEST['mDesloc'] == "carro" )
			$modoDeslocacao = 2;
		$imagem = $_REQUEST['imagem'];
		
	}
	
	$conString = include 'connVariaveis.php';
	$conn = pg_connect($conString);
	
	if( $opSelecionada == "POI" || $opSelecionada == "Eventos" )
	{
//		$queryCategoria = "SELECT conteudo_cat.id_categoria as id FROM conteudo_cat, idioma WHERE idioma.codigo = '$idioma' AND conteudo_cat.nome = '$categoria'";
//		$resCategoria = pg_query($conn, $queryCategoria);
//		$row = pg_fetch_array($resCategoria);
//		$idCategoria = $row['id'];
	}
	
	if( $opSelecionada == "POI" )
	{
		$query = "UPDATE poi 
				SET lat = $latitude, lon = $longitude, \"imagem\" = '$imagem', ativo = $activo, id_categoria = $idCategoria,
				\"data_ini\" = '$dataInicio', \"data_fim\" = '$dataFim', \"web_url\" = '$url', \"contact\" = '$contacto', \"direcao\" = '$morada', 
				\"codigo_postal\" = '$codPostal', \"localidade\" = '$localidade', \"email\" = '$email', \"num_porta\" = '$nrPorta'
				WHERE poi.id = $idConteudo RETURNING id";
                
	}
	else if( $opSelecionada == "Eventos" )
	{
		$query = "UPDATE evento 
				SET lat = $latitude, lon = $longitude, \"imagem\" = '$imagem', ativo = $activo, id_categoria = $idCategoria,
				\"data_ini\" = '$dataInicio', \"data_fim\" = '$dataFim', \"web_url\" = '$url', \"contact\" = '$contacto', \"direcao\" = '$morada', 
				\"codigo_postal\" = '$codPostal', \"localidade\" = '$localidade', \"email\" = '$email', \"num_porta\" = '$nrPorta',
				\"data_evento\" = '$dataInicioEvento', \"data_eventof\" = '$dataFimEvento'
				WHERE evento.id = $idConteudo RETURNING id";
	} 
	else if( $opSelecionada == "Categorias" )
	{
		$query = "UPDATE categoria
					SET \"icon\" = '$icon', tipo = $tipoCategoria, ativo = $activo 
					WHERE categoria.id = $idConteudo ";
	}
	else if( $opSelecionada == "Percursos" )
	{
		$query = "UPDATE percurso
					SET \"data_ini\" = '$dataInicio', \"data_fim\" = '$dataFim', \"imagem\" = '$imagem', ativo = $activo, modo_desloc = $modoDeslocacao
					WHERE percurso.id = $idConteudo";
					
	}
	
	
	
	$result = pg_query($conn,$query);
	//$row = pg_fetch_array($result);
	//echo $row['id'];
	
	// CONTEUDOS IDIOMATICOS
	
	$nomesOrig = explode(',', $_REQUEST['nomeOrig']);
	$nomesEditado = explode(',', $_REQUEST['nomeEditado']);
	$descOrig = explode('|*|', $_REQUEST['descOrig']);
	$descEditado = explode('|*|', $_REQUEST['descEditado']);
	$idiomasOrig = explode(',', $_REQUEST['idiomasOrig']);
	$idiomasEditado = explode(',', $_REQUEST['idiomasEditado']);
	$ativoOrig = explode(',', $_REQUEST['ativoOrig']);
	$ativoEditado = explode(',', $_REQUEST['ativoEditado']);
	
	$conString = include 'connVariaveis.php';
	$conn = pg_connect($conString);


		//ALTERAR OS QUE SAO COMUNS AO ORIGINAL E EDITADO
		for($i=0; $i<sizeof($idiomasEditado); $i++)
		{
			$queryIdioma = "SELECT id FROM idioma WHERE codigo = '$idiomasEditado[$i]'";
			$resIdioma = pg_query($conn, $queryIdioma);
			$rowIdioma = pg_fetch_array($resIdioma);
			$idIdioma = $rowIdioma['id'];
			
			$activo = 'true';	
			if( $ativoEditado[$i] == "f" )
				$activo = 'false';
				
			$queryUpdate = getQueryUpdate($opSelecionada, $nomesEditado[$i], $descEditado[$i], $activo, $idConteudo, $idIdioma);
			
			$resUpdate = pg_query($conn, $queryUpdate);
		}

		//DESATIVAR E INSERIR OS QUE FORAM REMOVIDOS/ADICIONADOS DURANTE A EDICAO
		//DESATIVAR
		for( $i=0; $i<sizeof($idiomasOrig); $i++)
		{
			$flag = elementoPertence($idiomasOrig[$i], $idiomasEditado, $i);
			if( $flag == false )
			{
				
				$queryIdioma = "SELECT id FROM idioma WHERE codigo = '$idiomasOrig[$i]'";
				$resIdioma = pg_query($conn, $queryIdioma);
				$rowIdioma = pg_fetch_array($resIdioma);
				$idIdioma = $rowIdioma['id'];
				
				$query = getQueryDesativa($opSelecionada,$idIdioma,$idConteudo);
				$resQuery = pg_query($conn, $query);
			}
		}
		//INSERIR
		for($i=0; $i<sizeof($idiomasEditado); $i++)
		{
			if($idiomasEditado[$i] != 'undefined')
			{
				$flag = elementoPertence($idiomasEditado[$i], $idiomasOrig, $i);
				if( $flag == false)
				{					
					$queryIdioma = "SELECT id FROM idioma WHERE codigo = '$idiomasEditado[$i]'";
					$resIdioma = pg_query($conn, $queryIdioma);
					$rowIdioma = pg_fetch_array($resIdioma);
					$idIdioma = $rowIdioma['id'];
					
					$activo = 'true';	
					if( $ativoEditado[$i] == "f" )
						$activo = 'false';
	
					$query = getQueryInsere($opSelecionada, $nomesEditado[$i], $descEditado[$i], $activo, $idConteudo, $idIdioma);
					$resQuery = pg_query($conn, $query);
					
					if( $opSelecionada == "POI" )
					{
						try 
						{
							$queryElimina = "DELETE FROM alertas WHERE id_idioma = $idIdioma AND id_ponto = $idConteudo";
							echo $queryElimina;
							$resElimina = pg_query($conn,$queryElimina);
						} catch(Exception $e) {
						  echo "";
						}
					} 
					else if ( $opSelecionada == "Percursos" )
					{
						//id conteudo = id_percurso
						$queryPontos = "select id_ponto, nome from pontos_percurso, conteudo_poi where conteudo_poi.id_poi = pontos_percurso.id_ponto AND id_percurso = $idConteudo AND conteudo_poi.id_idioma = 1";
						$resultadoPontos = pg_query($conn, $queryPontos);
						$arrayPontosPercurso = array(); $aux = 0;
						$arrayNomesPecurso = array();
						while ( $row = pg_fetch_array($resultadoPontos) )
						{
							$arrayPontosPercurso[$aux] = $row['id_ponto'];
							$arrayNomesPercurso[$aux] = $row['nome'];
							$aux++;
						}
						
						echo "tamanho do array: "; echo count($arrayPontosPercurso);
						for($j=0; $j<count($arrayPontosPercurso); $j++)
						{
							$queryIdiomaExiste = "select count(*) as nrregistos from conteudo_poi where id_poi = $arrayPontosPercurso[$j] AND id_idioma = $idIdioma";
							$resultadoIdiomaExiste = pg_query($conn, $queryIdiomaExiste);
							$rowIdiomaExiste = pg_fetch_array($resultadoIdiomaExiste);
							echo "nr de idiomas de codigo $idIdioma: "; echo $rowIdiomaExiste['nrregistos'];
							if( $rowIdiomaExiste['nrregistos'] == 0 )
							{
								$queryAlerta = "insert into alertas(id_percurso, id_ponto, \"nome_ponto\", id_utilizador, id_idioma)
												values ($idConteudo, $arrayPontosPercurso[$j], '$arrayNomesPercurso[$j]', 1, $idIdioma)";
								$resultadoAlerta = pg_query($conn, $queryAlerta);
							}
						}
					}
				}
			}
		}
	 

	function elementoPertence($elem, $arr, $pos)
	{
		for($i=0; $i<sizeof($arr); $i++)
		{
			if($arr[$i] == $elem)
				return true;
		}
		return false;
	}
	
	function getQueryUpdate($op, $nome, $desc, $act, $idC, $idi)
	{
		if( $op == "POI" )
		{
			return "UPDATE conteudo_poi 
							SET \"nome\" = '$nome', \"descr\" = '$desc', ativo = $act
							WHERE id_idioma = $idi AND id_poi = $idC";
		} 
		else if ( $op == "Eventos" )
		{
			return "UPDATE conteudo_evento
							SET \"nome\" = '$nome', \"descr\" = '$desc', ativo = $act
							WHERE id_idioma = $idi AND id_evento = $idC";
		}
		else if ( $op == "Categorias" )
		{
			return "UPDATE conteudo_cat 
							SET \"nome\" = '$nome', \"descr\" = '$desc', ativo = $act
							WHERE id_idioma = $idi AND id_categoria = $idC";
		}
		else if ( $op == "Percursos" )
		{
			return "UPDATE conteudo_percurso 
							SET \"nome\" = '$nome', \"descr\" = '$desc', ativo = $act
							WHERE id_idioma = $idi AND id_percurso = $idC";
		}		
	}
	
	function getQueryDesativa($op, $idi, $idC)
	{
		if( $op == "POI")
			return "UPDATE conteudo_poi
							SET ativo = 'false'
							WHERE id_poi = $idConteudo AND id_idioma = $idIdioma";
		else if ( $op == "Eventos" )
			return "UPDATE conteudo_evento
							SET ativo = 'false'
							WHERE id_evento = $idConteudo AND id_idioma = $idIdioma";
		else if ( $op == "Categorias" )
			return "UPDATE conteudo_cat
							SET ativo = 'false'
							WHERE id_categoria = $idConteudo AND id_idioma = $idIdioma";
		else if ( $op == "Percursos" )
			return "UPDATE conteudo_percurso
							SET ativo = 'false'
							WHERE id_percurso = $idConteudo AND id_idioma = $idIdioma";
	}
	
	function getQueryInsere($op, $nome, $desc, $act, $idC, $idI)
	{
		if( $op == "POI" )
			return "INSERT INTO conteudo_poi (\"nome\", \"descr\",ativo,id_poi,id_idioma)
									VALUES ('$nome', '$ddesc', $act, $idC, $idI )";
		else if ( $op == "Eventos" )
			return "INSERT INTO conteudo_evento (\"nome\", \"descr\",ativo,id_evento,id_idioma)
									VALUES ('$nome', '$ddesc', $act, $idC, $idI )";
		else if ( $op == "Categorias" )
			return "INSERT INTO conteudo_cat (\"nome\", \"descr\",ativo,id_categoria,id_idioma)
									VALUES ('$nome', '$ddesc', $act, $idC, $idI )";
		else if ( $op == "Percursos" )
			return "INSERT INTO conteudo_percurso (\"nome\", \"descr\",ativo,id_percurso,id_idioma)
									VALUES ('$nome', '$desc', $act, $idC, $idI )";
	}
?>