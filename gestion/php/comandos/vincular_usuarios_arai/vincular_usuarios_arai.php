<?php

const PAGINADOR_ARAI = 100;

try {

	inicia_proceso();
	
	exit(0);

} catch (Exception $e) {
	
	$mensaje = $e->getMessage();
	// Elimino saltos de líneas para que el error quede solo en una línea
	$mensaje = comunes::reemplazar_saltos_lineas_por_espacios($mensaje);
	echo comunes::consola_tipo_msg_error."Ocurrió el siguiente error: {$mensaje}".PHP_EOL;
	exit(1);

}

function inicia_proceso()
{
	try {
		
        // Valido la conexión con Araí-Usuarios
        if (!arai_usuarios::getDriver()->isConexionOk()) {
			echo comunes::consola_tipo_msg_error."No pudo establecerse conexión con Araí-Usuarios, revise el archivo 'instalacion/i__desarrollo/p__guarani/rest/arai_usuarios/cliente.ini'.".PHP_EOL;
			exit(1);
		}
        
        // Valido que este configurado 'appUniqueIdGestion' en 'instalacion/instalacion.ini'
        if (!toba::config()->existe_valor('instalacion', null, arai_usuarios::APP_UNIQUE_ID_GESTION)) {
			echo comunes::consola_tipo_msg_error."Debe especificar el parámetro '" . arai_usuarios::APP_UNIQUE_ID_GESTION . "' en 'instalacion/instalacion.ini'.".PHP_EOL;
			exit(1);
		}

        // Valido que este configurado 'appUniqueId3w' en 'instalacion/instalacion.ini'
		if (!toba::config()->existe_valor('instalacion', null, arai_usuarios::APP_UNIQUE_ID_3W)) {
			echo comunes::consola_tipo_msg_error."Debe especificar el parámetro '" . arai_usuarios::APP_UNIQUE_ID_3W . "' en 'instalacion/instalacion.ini'.".PHP_EOL;
			exit(1);
		}
        
        $version_api = arai_usuarios::getDriver()->getVersionApi();
        switch ($version_api) {
            case 1 :
                // Si usa API V1 de Araí Usuarios
                procesar_usuarios_api_v1();
                break;
            case 2 :
                // Si usa API V2 de Araí Usuarios
                procesar_usuarios_api_v2();
                break;
        }
	
	} catch (Exception $e) {
		$mensaje = $e->getMessage();
		// Elimino saltos de líneas para que el error quede solo en una línea
		$mensaje = comunes::reemplazar_saltos_lineas_por_espacios($mensaje);
		echo comunes::consola_tipo_msg_error."Ocurrió el siguiente error procesando usuarios: {$mensaje}".PHP_EOL;
	}
}

function procesar_usuarios_api_v1()
{
    try{
        
		//GESTION
		echo comunes::consola_tipo_msg_subtitulo."Inicia proceso aplicacion gestion...".PHP_EOL;		
        $app_gestion = toba::config()->get_parametro('instalacion', null, arai_usuarios::APP_UNIQUE_ID_GESTION);
		list($cantidad_usu_arai_v1, $sumo_los_vinculados) = procesar_por_proyecto_api_v1($app_gestion);
		echo comunes::consola_tipo_msg_subtitulo."Se procesaron ". $cantidad_usu_arai_v1 ." cuentas de Araí asociadas a Guaraní.".PHP_EOL;
		echo comunes::consola_tipo_msg_subtitulo."Se vincularon ". $sumo_los_vinculados ." cuentas de Araí con Guaraní.".PHP_EOL;
		echo comunes::consola_tipo_msg_subtitulo."Finaliza proceso aplicacion gestión...".PHP_EOL.PHP_EOL.PHP_EOL;

		//AUTOGESTION
		echo comunes::consola_tipo_msg_subtitulo."Inicia proceso aplicacion 3W...".PHP_EOL;
		$app_autogestion = toba::config()->get_parametro('instalacion', null, arai_usuarios::APP_UNIQUE_ID_3W);
		list($cantidad_usu_arai_v1, $sumo_los_vinculados) = procesar_por_proyecto_api_v1($app_autogestion);
		echo comunes::consola_tipo_msg_subtitulo."Se procesaron ". $cantidad_usu_arai_v1 ." cuentas de Araí.".PHP_EOL;
		echo comunes::consola_tipo_msg_subtitulo."Se vincularon ". $sumo_los_vinculados ." cuentas de Araí con Guaraní.".PHP_EOL;
		echo comunes::consola_tipo_msg_subtitulo."Finaliza proceso aplicacion 3W...".PHP_EOL;

		} catch (Exception $e) {
			
			$mensaje = $e->getMessage();
			// Elimino saltos de líneas para que el error quede solo en una línea
			$mensaje = comunes::reemplazar_saltos_lineas_por_espacios($mensaje);
			echo comunes::consola_tipo_msg_error."Ocurrió el siguiente error procesando usuarios: {$mensaje}".PHP_EOL;
			
	}
}

function procesar_por_proyecto_api_v1($seccion)
{
	//En usuarios_arai_v1 vendrán todos los usuarios de arai que tienen vinculada la cuenta de gestión	y se inicializa los limit y page
	$limit = PAGINADOR_ARAI;
	$page = 1;	
	
	//En usuario_no_vinculados, vendrán los usuarios de gestion que no tienen asignado un usuario de arai
	$usuario_no_vinculados = toba::consulta_php('co_personas')->get_usuarios_arai_no_asociados();
	
    // Si ya estan todas las personas de Guaraní vinculadas no voy a buscar usuarios a Araí
    if (empty($usuario_no_vinculados)) {
        return [0, 0];
    }
    
	$cantidad_usu_arai_v1	= 0;
	$sumo_los_vinculados = 0;
	
	do {
		$usuarios_arai_v1 = arai_usuarios::getDriver()->getCuentasByAplicacion($seccion, $limit, $page);
		$page++;		
		$cantidad_usu_arai_v1	+= count($usuarios_arai_v1);
		foreach ($usuarios_arai_v1 as $i => $u_arai) {
			foreach($usuario_no_vinculados as $j => $u_guarani){
				if($u_guarani['usuario'] == $u_arai['cuenta']){
					echo comunes::consola_tipo_msg_exito."Se vinculó la cuenta de araí con identificador_usuario ". $u_arai['identificador_usuario'] ." con el usuario Guaraní ".$u_guarani['usuario']." ".PHP_EOL;
					guarani::act('act_personas')->actualizar_arai_identificador_sso($u_guarani['usuario'], $u_arai['identificador_usuario']);	
					$sumo_los_vinculados ++;
					break;
				}				
			}			
		}

	} while (count($usuarios_arai_v1) == $limit);

	return array($cantidad_usu_arai_v1, $sumo_los_vinculados);
}

///////ARAI API V2/////////
function procesar_usuarios_api_v2()
{
	try{
        
		//GESTION
		echo comunes::consola_tipo_msg_subtitulo."Inicia proceso aplicacion gestion...".PHP_EOL;		
        $app_gestion = toba::config()->get_parametro('instalacion', null, arai_usuarios::APP_UNIQUE_ID_GESTION);
		list($cantidad_usu_arai_v1, $sumo_los_vinculados) = procesar_por_proyecto_api_v2($app_gestion);
		echo comunes::consola_tipo_msg_subtitulo."Se procesaron ". $cantidad_usu_arai_v1 ." cuentas de Araí asociadas a Guaraní.".PHP_EOL;
		echo comunes::consola_tipo_msg_subtitulo."Se vincularon ". $sumo_los_vinculados ." cuentas de Araí con Guaraní.".PHP_EOL;
		echo comunes::consola_tipo_msg_subtitulo."Finaliza proceso aplicacion gestión...".PHP_EOL.PHP_EOL.PHP_EOL;

		//AUTOGESTION
		echo comunes::consola_tipo_msg_subtitulo."Inicia proceso aplicacion 3W...".PHP_EOL;
		$app_autogestion = toba::config()->get_parametro('instalacion', null, arai_usuarios::APP_UNIQUE_ID_3W);
		list($cantidad_usu_arai_v1, $sumo_los_vinculados) = procesar_por_proyecto_api_v2($app_autogestion);
		echo comunes::consola_tipo_msg_subtitulo."Se procesaron ". $cantidad_usu_arai_v1 ." cuentas de Araí.".PHP_EOL;
		echo comunes::consola_tipo_msg_subtitulo."Se vincularon ". $sumo_los_vinculados ." cuentas de Araí con Guaraní.".PHP_EOL;
		echo comunes::consola_tipo_msg_subtitulo."Finaliza proceso aplicacion 3W...".PHP_EOL;

		} catch (Exception $e) {
			
			$mensaje = $e->getMessage();
			// Elimino saltos de líneas para que el error quede solo en una línea
			$mensaje = comunes::reemplazar_saltos_lineas_por_espacios($mensaje);
			echo comunes::consola_tipo_msg_error."Ocurrió el siguiente error procesando usuarios: {$mensaje}".PHP_EOL;
			
	}
}

function procesar_por_proyecto_api_v2($seccion)
{
	$limit = PAGINADOR_ARAI;
	$page = 1;
	
	$usuario_no_vinculados = toba::consulta_php('co_personas')->get_usuarios_arai_no_asociados();
    
    // Si ya estan todas las personas de Guaraní vinculadas no voy a buscar usuarios a Araí
    if (empty($usuario_no_vinculados)) {
        return [0, 0];
    }
    
	$cantidad_usu_arai_v2	= 0;
	$sumo_los_vinculados = 0;
	
	do {
		$usuarios_arai_v2 = arai_usuarios::getDriver()->getCuentasByAplicacion($seccion, $limit, $page);
		$page++;		
		$cantidad_usu_arai_v2	+= count($usuarios_arai_v2);
		foreach ($usuarios_arai_v2 as $i => $u_arai) {
			foreach($usuario_no_vinculados as $j => $u_guarani){
				if($u_guarani['usuario'] == $u_arai['cuenta']){
					$usuario = arai_usuarios::getDriver()->getUsuario($u_arai['identificador_usuario']);
					echo comunes::consola_tipo_msg_exito."Se vinculó la cuenta de araí con identificador_usuario ". $u_arai['identificador_usuario'] ." con el usuario Guaraní ".$u_guarani['usuario']." ".PHP_EOL;
					guarani::act('act_personas')->actualizar_arai_uuid_y_arai_identificador_sso($u_arai['identificador_usuario'], $usuario['identificador'], $u_arai['cuenta']);	
					$sumo_los_vinculados ++;
					break;
				}
			}			
		}

	} while (count($usuarios_arai_v2) == $limit);
	
	return array($cantidad_usu_arai_v2, $sumo_los_vinculados);
}
