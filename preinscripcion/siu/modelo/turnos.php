<?php

namespace siu\modelo;

use SIU\Chulupi\kernel;
use siu\modelo\opciones;
use siu\modelo\lib\abm;
use siu\errores\error_preinscripcion;
use siu\modelo\lib\catalogo;
use siu\modelo\g3\g3;

class turnos
{
	protected static $instancia;
	protected $datos_turnos;
	
	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\turnos");
		}
		return self::$instancia;
	}

	function existe_configuracion()
	{
		$sql = "SELECT	COUNT(1) AS cant
				FROM	trn_configuracion";
		$res = kernel::db()->consultar_fila($sql);
		return $res['cant'] > 0;
	}

	function get_cant_periodos_turnos_configurados()
	{
		$sql = "SELECT	COUNT(1) AS cant
				FROM	trn_configuracion";
		$res = kernel::db()->consultar_fila($sql);
		return $res['cant'];
	}

	function get_datos_turnos()
	{
		if (!isset($this->datos_turnos)) {
			$sql = "SELECT 
						trn_configuracion.configuracion,
						trn_configuracion.fecha_inicio,
						trn_configuracion.fecha_fin
					FROM 
						trn_configuracion 
					ORDER BY
						fecha_inicio DESC
					";

			$datos = kernel::db()->consultar($sql);
			$nuevo = array();

			foreach ($datos as $key => $dato) {
				$dato['fecha_inicio'] = convertir_fecha_formato_frontend($dato['fecha_inicio']);
				$dato['fecha_fin'] = convertir_fecha_formato_frontend($dato['fecha_fin']);
				$dato['ras'] = $this->get_nombres_ras_configuracion($dato['configuracion']);

				//---- ID encriptado -------------------------------------------
				$id = util::catalogo()->generar_id($dato['configuracion']);
				$nuevo[$id] = $dato;
				$nuevo[$id][catalogo::id] = $id;
			}
			$this->datos_turnos = $nuevo;
		}
		return $this->datos_turnos;
	}
	
	protected function get_nombres_ras_configuracion($configuracion)
	{
		$configuracion = kernel::db()->quote($configuracion);
		
		$sql = "SELECT	responsable_academica
				FROM	trn_configuracion_ra
				WHERE	configuracion = $configuracion";
		$ras = kernel::db()->consultar($sql);
		
		$salida = '';
		foreach ($ras as $ra) {
			if (!empty($salida)) {
				$salida .= ' / ';
			}
			$salida .= g3::entidad('responsables_academicas')->get_nombre_abreviado($ra['responsable_academica']);
		}
		
		return $salida;
	}

	function get_datos_configuracion($seleccion)
	{
		$sql = "SELECT 
					configuracion,
					fecha_inicio,
					fecha_fin,
					minutos_intervalo,
					cant_operadores,
					atiende_feriados,
					atiende_lunes,
					atiende_martes,
					atiende_miercoles,
					atiende_jueves,
					atiende_viernes,
					atiende_sabado,
					atiende_domingo
				FROM 
					trn_configuracion";

		$datos = kernel::db()->consultar($sql);

		foreach ($datos as $key => $dato) {
			if (util::catalogo()->generar_id($dato['configuracion']) == $seleccion) {
				$res = $dato;
				break;
			}
		}

		if (!empty($res)) {
			//---- Intervalos --------------------------------------------------
			$res['fecha_inicio'] = \convertir_fecha_formato_frontend($res['fecha_inicio']);
			$res['fecha_fin'] = \convertir_fecha_formato_frontend($res['fecha_fin']);

			$configuracion = kernel::db()->quote($res['configuracion']);
			$sql_int = "SELECT 
							intervalo,
							hora_inicio,
							hora_fin
						FROM 
							trn_configuracion_intervalos
						WHERE
							configuracion = $configuracion
						ORDER BY 
							hora_inicio";
			$intervalos = kernel::db()->consultar($sql_int);

			$res['hora_inicio'] = \convertir_hora_formato_frontend($intervalos[0]['hora_inicio']);
			$res['hora_fin'] = \convertir_hora_formato_frontend($intervalos[0]['hora_fin']);

			if (!empty($intervalos[1])) {
				$res['hora_inicio_2'] = \convertir_hora_formato_frontend($intervalos[1]['hora_inicio']);
				$res['hora_fin_2'] = \convertir_hora_formato_frontend($intervalos[1]['hora_fin']);
			}
			
			//---- Responsables Académicas -------------------------------------
			$sql = "SELECT	responsable_academica
					FROM	trn_configuracion_ra
					WHERE	configuracion = $configuracion";
			$ras = kernel::db()->consultar($sql);

			if (!empty($ras)) {
				foreach ($ras as $ra) {
					$clave = 'ra_' . $ra['responsable_academica'];
					$res[$clave] = 1;
				}
			}
		} else {
			$res = array();
		}

		return $res;
	}

	function get_fecha_minima()
	{
		$sql = "SELECT 	MIN(fecha) as fecha
				FROM 	trn_turnos
				WHERE	id_preinscripcion IS NULL
						AND fecha >= CURRENT_DATE;
				";
		$res = kernel::db()->consultar_fila($sql);
		if (!empty($res['fecha'])) {
			$salida = convertir_fecha_formato_frontend($res['fecha']);
		} else {
			$salida = '';
		}
		return $salida;
	}

	function get_fecha_maxima()
	{
		$sql = "SELECT 	MAX(fecha) as fecha
				FROM 	trn_turnos
				WHERE	id_preinscripcion IS NULL;
				";
		$res = kernel::db()->consultar_fila($sql);
		if (!empty($res['fecha'])) {
			$salida = convertir_fecha_formato_frontend($res['fecha']);
		} else {
			$salida = '';
		}
		return $salida;
	}

	function get_fechas_turnos_disponibles()
	{
		$sql = "SELECT 		distinct(fecha) AS fecha
				FROM 		trn_turnos
				WHERE		id_preinscripcion IS NULL
							AND (
								fecha > CURRENT_DATE
								OR
								(
									fecha = CURRENT_DATE
									AND hora >= CURRENT_TIME
								)
							)
				ORDER BY 	fecha
				";
		$res = kernel::db()->consultar($sql);
		$salida = array();
		foreach ($res as $fecha) {
			$salida[] = convertir_fecha_formato_frontend($fecha['fecha']);
		}
		return $salida;
	}

	function get_franjas_horarias_disponibles($fecha)
	{
		$fecha = kernel::db()->quote(convertir_fecha_formato_base($fecha));

		$sql = "SELECT 	DISTINCT EXTRACT(HOUR FROM hora) AS hora
				FROM 	trn_turnos
				WHERE 	fecha = $fecha
						AND id_preinscripcion IS NULL
						AND (
							fecha > CURRENT_DATE
							OR
							(
								fecha = CURRENT_DATE
								AND hora >= CURRENT_TIME
							)
						)
				ORDER BY hora
				";

		$datos = kernel::db()->consultar($sql);
		foreach ($datos as $key => $dato) {
			$hora = $dato['hora'];
			$hora_sig = $hora + 1;

			$hora_str = ($hora < 10) ? '0' . $hora : $hora;
			$hora_sig_str = ($hora_sig < 10) ? '0' . $hora_sig : $hora_sig;

			$datos[$key]['descr'] = "De $hora_str:00 a $hora_sig_str:00";
		}
		return opciones::armar_combo_opciones($datos, 'hora', 'descr', true, false);
	}

	function reservar_turno($id_preinscripcion, $datos_turno)
	{
		$clave = array('turno' => $this->asignar_turno($datos_turno));
		$datos = array('id_preinscripcion' => $id_preinscripcion);
		abm::modificacion('trn_turnos', $datos, $clave);
	}

	static protected function asignar_turno($datos_turno)
	{
		$fecha = kernel::db()->quote(convertir_fecha_formato_base($datos_turno['fecha']));
		$franja = kernel::db()->quote($datos_turno['franja']);

		$sql = "SELECT 		turno
				FROM		trn_turnos
				WHERE 		id_preinscripcion IS NULL
							AND fecha = $fecha
							AND EXTRACT(HOUR FROM hora) = $franja
							AND (
								fecha > CURRENT_DATE
								OR
								(
									fecha = CURRENT_DATE
									AND hora >= CURRENT_TIME
								)
							)
				ORDER BY 	hora,
							operador
				LIMIT 		1
				";

		$res = kernel::db()->consultar_fila($sql);
		if (empty($res)) {
			$mje = kernel::traductor()->trans('sel_turno.error_sin_turnos');
			throw new error_preinscripcion($mje);
		}
		return $res['turno'];
	}

	function devolver_turno($id_preinscripcion)
	{
		$id_preinscripcion = kernel::db()->quote($id_preinscripcion);

		$sql = "UPDATE 	trn_turnos  
				SET 	id_preinscripcion = NULL 
				WHERE 	id_preinscripcion = $id_preinscripcion;
				";

		kernel::db()->ejecutar($sql);
	}

	function get_turno_presentacion($id_preinscripcion)
	{
		$sql = "SELECT 
					turno,
					operador,
					fecha,
					hora
				FROM 
					trn_turnos
				WHERE
					id_preinscripcion = $id_preinscripcion
				";

		return kernel::db()->consultar_fila($sql);
	}

	//--------------------------------------------------------------------------

	function get_cantidad_turnos_generados()
	{
		$sql = "SELECT 	COUNT(1) AS cant
				FROM 	trn_turnos
				";

		$res = kernel::db()->consultar_fila($sql);
		return $res['cant'];
	}

	function get_cantidad_turnos_disponibles()
	{
		$sql = "SELECT 	COUNT(1) AS cant
				FROM 	trn_turnos
				WHERE	id_preinscripcion IS NULL
						AND (
							fecha > CURRENT_DATE
							OR
							(
								fecha = CURRENT_DATE
								AND hora >= CURRENT_TIME
							)
						)
				";

		$res = kernel::db()->consultar_fila($sql);
		return $res['cant'];
	}

}
?>