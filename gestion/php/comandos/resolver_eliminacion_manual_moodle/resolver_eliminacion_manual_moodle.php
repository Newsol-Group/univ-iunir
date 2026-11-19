<?php

const CANT_CURSOS = 200;

try {

	global $argv;
	$plataforma = isset($argv[7])? $argv[7] : null;
	
	// Si no se pasa --moodle (-m)
	if (is_null($plataforma)) {
		
		echo comunes::consola_tipo_msg_info."Se procesaran todas las plataformas activas.".PHP_EOL;
		
	} else {// Si se pasa --moodle (-m)
		
		// Se valida que sea integer
		if (filter_var($plataforma, FILTER_VALIDATE_INT)) {
			echo comunes::consola_tipo_msg_info."Se procesará la plataforma '{$plataforma}'.".PHP_EOL;
		} else {
			echo comunes::consola_tipo_msg_error."La opción --moodle (-m) debe ser un número entero, se ha pasado '{$plataforma}'.".PHP_EOL;
			exit(1);
		}
		
	}
	
	$where_plataformas = "int_pv_plataformas.activa = TRUE";
	// Si se paso la opción --moodle (-m) traigo la seleccionada
	if (!is_null($plataforma)) {
		$where_plataformas .= " AND int_pv_plataformas.plataforma = $plataforma";
	}
	
	// Obtengo las las plataformas de la tabla 'int_pv_plataformas'
	$plataformas = toba::consulta_php('co_configuracion')->get_plataformas_educacion_virtual($where_plataformas);
	
	// Si no hay plataformas
	if (empty($plataformas)) {
		echo comunes::consola_tipo_msg_info."No se encontraron plataformas activas.".PHP_EOL;
		exit(0);
	}
	
	echo comunes::consola_tipo_msg_subtitulo."Procesando usuarios.......".PHP_EOL;
	procesar_usuarios($plataformas);
	echo comunes::consola_tipo_msg_subtitulo."Procesando cursos.......".PHP_EOL;
	procesar_cursos($plataformas);
	
	exit(0);

} catch (Exception $e) {
	
	$mensaje = $e->getMessage();
	// Elimino saltos de líneas para que el error quede solo en una línea
	$mensaje = comunes::reemplazar_saltos_lineas_por_espacios($mensaje);
	echo comunes::consola_tipo_msg_error."Ocurrió el siguiente error: {$mensaje}".PHP_EOL;
	exit(1);

}

function procesar_usuarios($plataformas)
{
	try {
		
		$alfabeto = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','Ñ','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

		// Recorro las plataformas
		foreach ($plataformas as $datos_plataforma) {

			// Obtengo los usuarios de la tabla 'int_pv_usuarios' para la plataforma dada
			$usuarios_guarani = toba::consulta_php('co_configuracion')->get_usuarios_by_plataforma($datos_plataforma['plataforma']);

			echo comunes::consola_tipo_msg_info."Se obtuvieron ". count($usuarios_guarani)." usuarios de la tabla 'int_pv_usuarios' para la plataforma '{$datos_plataforma['plataforma']}'.".PHP_EOL;

			// Si la plataforma no tiene usuarios en la tabla 'int_pv_usuarios' salto a la siguiente 
			if (empty($usuarios_guarani)) {
				echo comunes::consola_tipo_msg_info."No hay usuarios a procesar para la plataforma '{$datos_plataforma['plataforma']}'.".PHP_EOL;
				continue;
			}

			// Ids de los usuarios en la tabla 'int_pv_usuarios'
			$usuarios_guarani_ids = array_column($usuarios_guarani, 'id_usuario_externo');

			// Armo la conexión con Moodle
			$moodle = new moodle($datos_plataforma['host'], $datos_plataforma['url'], $datos_plataforma['token']);

			$usuarios_moodle_ids = [];
			// Busco usuarios en Moodle según la letra con la que comienza el apellido
			foreach ($alfabeto as $letra) {

				$letra = utf8_encode($letra);
				$criteria = [['key' => 'lastname', 'value' => "{$letra}%"]];
				$usuarios_moodle = $moodle->getUsuarios($criteria);

				// Si falla la obtención de usuarios de Moodle muestro error y paso a la siguiente plataforma.
				if ($usuarios_moodle == -1) {
					echo comunes::consola_tipo_msg_error."Falló la obtención de usuarios de Moodle cuyo apellido comienza con '{$letra}' para la plataforma '{$datos_plataforma['plataforma']}'.".PHP_EOL;
					continue 2;
				}

				// Si no hay usuarios para una letra dada salto a la siguiente letra 
				if (empty($usuarios_moodle)) {
					continue;
				}

				// Ids de los usuarios en Moodle
				$usuarios_moodle_ids_temp = array_column($usuarios_moodle, 'id');
				$usuarios_moodle_ids = array_merge($usuarios_moodle_ids, $usuarios_moodle_ids_temp);
			}

			echo comunes::consola_tipo_msg_info."Se obtuvieron ". count($usuarios_moodle_ids)." usuarios de Moodle para la plataforma '{$datos_plataforma['plataforma']}'.".PHP_EOL;

			// Usuarios vinculados que no existen mas en Moodle
			$usuarios_eliminar_ids = array_diff($usuarios_guarani_ids, $usuarios_moodle_ids);

			// Si no hay usuarios para desvincular
			if (empty($usuarios_eliminar_ids)) {
				echo comunes::consola_tipo_msg_info."No hay usuarios a desvincular para la plataforma '{$datos_plataforma['plataforma']}'.".PHP_EOL;
			} else {// Si hay usuarios para desvincular
				// Elimino los usuarios vinculados de la tabla 'int_pv_usuarios'
				guarani::act('act_plataformas_virtuales')->borrar_usuarios_pv_masivo($datos_plataforma['plataforma'], $usuarios_eliminar_ids);
				echo comunes::consola_tipo_msg_exito."Se desvincularon ". count($usuarios_eliminar_ids)." usuario/s para la plataforma '{$datos_plataforma['plataforma']}' (IDs: ".implode(", ", $usuarios_eliminar_ids).").".PHP_EOL;
			}


		}
	
	} catch (Exception $e) {
		
		$mensaje = $e->getMessage();
		// Elimino saltos de líneas para que el error quede solo en una línea
		$mensaje = comunes::reemplazar_saltos_lineas_por_espacios($mensaje);
		echo comunes::consola_tipo_msg_error."Ocurrió el siguiente error procesando usuarios: {$mensaje}".PHP_EOL;
		
	}
}


function procesar_cursos($plataformas)
{
	try {
		
		// Recorro las plataformas
		foreach ($plataformas as $datos_plataforma) {

			// Obtengo los cursos de la tabla 'int_pv_cursos' para la plataforma dada
			$cursos_guarani = toba::consulta_php('co_configuracion')->get_cursos_por_plataforma($datos_plataforma['plataforma']);

			echo comunes::consola_tipo_msg_info."Se obtuvieron ". count($cursos_guarani)." cursos de la tabla 'int_pv_cursos' para la plataforma '{$datos_plataforma['plataforma']}'.".PHP_EOL;

			// Si la plataforma no tiene cursos en la tabla 'int_pv_cursos' salto a la siguiente 
			if (empty($cursos_guarani)) {
				echo comunes::consola_tipo_msg_info."No hay cursos a procesar para la plataforma '{$datos_plataforma['plataforma']}'.".PHP_EOL;
				continue;
			}

			// Ids de los cursos en la tabla 'int_pv_cursos'
			$cursos_guarani_ids = array_column($cursos_guarani, 'id_curso_externo');
				
			// Armo la conexión con Moodle
			$moodle = new moodle($datos_plataforma['host'], $datos_plataforma['url'], $datos_plataforma['token']);
			
			$bloques_cursos_guarani = array_chunk($cursos_guarani_ids, CANT_CURSOS);
			
			$cursos_moodle_ids = [];
			foreach ($bloques_cursos_guarani as $bloque_cursos_guarani) {
				
				$cursos_moodle = $moodle->getCursos($bloque_cursos_guarani, false);
				
				// Si falla la obtención de cursos de Moodle muestro error y paso a la siguiente plataforma.
				if ($cursos_moodle == -1) {
					echo comunes::consola_tipo_msg_error."Falló la obtención de cursos de Moodle para la plataforma '{$datos_plataforma['plataforma']}'.".PHP_EOL;
					continue 2;
				}
				
				// Si no hay cursos salto a la siguiente iteración 
				if (empty($cursos_moodle)) {
					continue;
				}
				
				// Ids de los cursos en Moodle
				$cursos_moodle_ids_temp = array_column($cursos_moodle, 'id');
				$cursos_moodle_ids = array_merge($cursos_moodle_ids, $cursos_moodle_ids_temp);
			}
			
			echo comunes::consola_tipo_msg_info."Se obtuvieron ". count($cursos_moodle_ids)." cursos de Moodle para la plataforma '{$datos_plataforma['plataforma']}'.".PHP_EOL;

			// Cursos vinculados que no existen mas en Moodle
			$cursos_eliminar_ids = array_diff($cursos_guarani_ids, $cursos_moodle_ids);

			// Si no hay cursos para desvincular
			if (empty($cursos_eliminar_ids)) {
				echo comunes::consola_tipo_msg_info."No hay cursos a desvincular para la plataforma '{$datos_plataforma['plataforma']}'.".PHP_EOL;
			} else {// Si hay cursos para desvincular
				// Elimino los cursos vinculados de la tabla 'int_pv_cursos'
				guarani::act('act_plataformas_virtuales')->borrar_cursos_pv_masivo($datos_plataforma['plataforma'], $cursos_eliminar_ids);
				echo comunes::consola_tipo_msg_exito."Se desvincularon ". count($cursos_eliminar_ids)." curso/s para la plataforma '{$datos_plataforma['plataforma']} (IDs: ".implode(", ", $cursos_eliminar_ids).").".PHP_EOL;
			}

		}
	
	} catch (Exception $e) {
		
		$mensaje = $e->getMessage();
		// Elimino saltos de líneas para que el error quede solo en una línea
		$mensaje = comunes::reemplazar_saltos_lineas_por_espacios($mensaje);
		echo comunes::consola_tipo_msg_error."Ocurrió el siguiente error procesando cursos: {$mensaje}".PHP_EOL;
		
	}
}