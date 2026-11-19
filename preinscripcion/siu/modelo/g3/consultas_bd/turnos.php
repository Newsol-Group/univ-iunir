<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\interface_turnos;
use siu\modelo\util;
use siu\errores\error_preinscripcion;

class turnos extends _consultas_bd implements interface_turnos
{
	protected static $instancia;
	protected $tiempo_cache = 1;

	static function instancia()
	{
		$clase = substr(strrchr(get_class(), "\\"), 1);
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\g3\\consultas_bd\\$clase");
		}
		return self::$instancia;
	}

	//--------------------------------------------------------------------------
	//---- Implementación interface_turnos -------------------------------------
	//--------------------------------------------------------------------------

	/**
	 * Determina si existen turnos disponibles (no ocupados, vigentes) para una
	 * Responsable Académica (RA) dada
	 * 
	 * @param int $ra
	 * @return boolean
	 */
	function hay_turnos_disponibles($ra)
	{
		$fechas = $this->get_fechas_turnos_disponibles($ra);
		return count($fechas) > 0;
	}

	/**
	 * Obtiene las fechas en las que exiten turnos disponibles
	 * 
	 * @param int $ra
	 * @return array
	 */
	function get_fechas_turnos_disponibles($ra)
	{
		$where_ra = $this->get_where_ra($ra);

		$sql = "SELECT
					distinct(fecha) AS fecha
				FROM 
					pre_turnos
				WHERE		
					id_preinscripcion IS NULL 
					AND persona IS NULL
					AND (
						fecha > CURRENT_DATE
						OR	(
							fecha = CURRENT_DATE
							AND hora >= CURRENT_TIME
						)
					)
					$where_ra
				ORDER BY 
					fecha
				";
		$res = $this->consultar($sql);
		$salida = array();
		foreach ($res as $fecha) {
			$salida[] = convertir_fecha_formato_frontend($fecha['fecha']);
		}
		return $salida;
	}

	/**
	 * Obtiene la primera fecha en la que hay un turno disponible, o el string 
	 * vacío en caso de no existir turnos disponibles
	 * 
	 * @param int $ra
	 * @return string
	 */
	function get_fecha_minima($ra)
	{
		$where_ra = $this->get_where_ra($ra);

		$sql = "SELECT 	
					MIN(fecha) as fecha
				FROM 	
					pre_turnos
				WHERE	
					id_preinscripcion IS NULL
					AND persona IS NULL
					AND fecha >= CURRENT_DATE
					$where_ra
				";
		$res = $this->consultar_fila($sql);
		if (!empty($res['fecha'])) {
			$salida = convertir_fecha_formato_frontend($res['fecha']);
		} else {
			$salida = '';
		}
		return $salida;
	}

	/**
	 * Obtiene la última fecha en la que hay un turno disponible, o el string 
	 * vacío en caso de no existir turnos disponibles
	 * 
	 * @param int $ra
	 * @return string
	 */
	function get_fecha_maxima($ra)
	{
		$where_ra = $this->get_where_ra($ra);

		$sql = "SELECT 	
					MAX(fecha) as fecha
				FROM 	
					pre_turnos
				WHERE 
					id_preinscripcion IS NULL
					AND persona IS NULL
					$where_ra
				";
		$res = $this->consultar_fila($sql);
		if (!empty($res['fecha'])) {
			$salida = convertir_fecha_formato_frontend($res['fecha']);
		} else {
			$salida = '';
		}
		return $salida;
	}

	/**
	 * Obtiene las franjas horarias en las que hay turnos disponibles para una 
	 * RA dada en una determinada fecha
	 * 
	 * @param string $fecha
	 * @param int $ra
	 * @return array
	 */
	function get_franjas_horarias_disponibles($fecha, $ra)
	{
		$fecha = kernel::db()->quote(convertir_fecha_formato_base($fecha));
		$where_ra = $this->get_where_ra($ra);

		$sql = "SELECT 	
					DISTINCT EXTRACT(HOUR FROM hora) AS hora
				FROM 	
					pre_turnos
				WHERE 	
					fecha = $fecha
					AND id_preinscripcion IS NULL
					AND persona IS NULL
					AND (
						fecha > CURRENT_DATE
						OR (
							fecha = CURRENT_DATE
							AND hora >= CURRENT_TIME
						)
					)
					$where_ra
				ORDER BY hora
				";

		$datos = $this->consultar($sql);
		foreach ($datos as $key => $dato) {
			$hora = $dato['hora'];
			$hora_sig = $hora + 1;

			$hora_str = ($hora < 10) ? '0' . $hora : $hora;
			$hora_sig_str = ($hora_sig < 10) ? '0' . $hora_sig : $hora_sig;

			$datos[$key]['descr'] = "De $hora_str:00 a $hora_sig_str:00";
		}

		return util::opciones()->armar_combo_opciones($datos, 'hora', 'descr', true, false);
	}

	/**
	 * Asigna un turno según la solicitud (fecha, rango horario, RA)
	 * 
	 * @param int $id_preinscripcion
	 * @param int $ra
	 * @param array $datos_turno
	 * @throws error_preinscripcion
	 */
	function reservar_turno($id_preinscripcion, $ra, $datos_turno)
	{
		$pre_turno = $this->asignar_turno($datos_turno, $ra);
		$ra = kernel::db()->quote($ra);

		if (empty($pre_turno)) {
			$mensaje = kernel::traductor()->trans('turnos.error_asignacion_turno');
			throw new error_preinscripcion($mensaje);
		}

		$sql = "UPDATE 	pre_turnos  
				SET 	id_preinscripcion = $id_preinscripcion,
						responsable_academica = $ra
				WHERE 	pre_turno = $pre_turno;
				";

		$this->ejecutar($sql);
	}

	/**
	 * Deja nuevamente disponible un turno solicitado por un aspirante, para una
	 * RA dada.
	 * 
	 * @param int $id_preinscripcion
	 * @param int $ra
	 */
	function devolver_turno($id_preinscripcion, $ra)
	{
		$sql = "UPDATE 	pre_turnos  
                        SET 	id_preinscripcion = NULL,
                                persona = NULL,
                                responsable_academica = NULL
                        WHERE 	id_preinscripcion = $id_preinscripcion
                        AND responsable_academica = $ra;
			";

		$this->ejecutar($sql);
	}

	/**
	 * Determina qué turno de presentación(en caso que haya sido solicitado) de
	 * un aspirante en una RA dada.
	 * 
	 * @param int $id_preinscripcion
	 * @param int $ra
	 * @return array
	 */
	function get_turno_presentacion($id_preinscripcion, $ra)
	{
		$sql = "SELECT 
					pre_turnos.pre_turno,
					pre_turnos.turno_config,
					pre_turnos.id_preinscripcion,
					pre_turnos.persona,
					pre_turnos.responsable_academica,
					pre_turnos.operador,
					pre_turnos.fecha,
					pre_turnos.hora,
					pre_turnos.recordatorio_enviado
				FROM 
					pre_turnos
				WHERE
					pre_turnos.id_preinscripcion = $id_preinscripcion 
					AND pre_turnos.responsable_academica = $ra
					AND pre_turnos.fecha >= CURRENT_DATE

				";

		return $this->consultar_fila($sql, "turno_presentacion_{$id_preinscripcion}_{$ra}");
	}

        /**
	 * Invalida cache de turno de presentación(en caso que haya sido solicitado) de
	 * un aspirante en una RA dada.
	 *
	 * @param int $id_preinscripcion
	 * @param int $ra
	 * @return void
	 */
        function invalidar_cache_turno_presentacion($id_preinscripcion, $ra)
        {
            util::catalogo()->invalidar_por_id("turno_presentacion_{$id_preinscripcion}_{$ra}");
        }

	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @param $id = array('ra' => x, 'fecha' => y)
	 * @return array
	 */
	function test($id)
	{
		// Se testean las funciones particualres
		try {
			$datos = array();
			$datos['fechas_turnos_disponibles'] = $this->get_fechas_turnos_disponibles($id['ra']);
			$datos['fecha_minima'] = $this->get_fecha_minima($id['ra']);
			$datos['fecha_maxima'] = $this->get_fecha_maxima($id['ra']);
			$datos['franjas_horarias'] = $this->get_franjas_horarias_disponibles($id['fecha'], $id['ra']);
			$salida = array(
				'resultado' => true,
				'datos' => $datos,
				'msj' => 'OK',
			);
		} catch (\Exception $ex) {
			$salida = array(
				'resultado' => false,
				'msj' => $ex->getMessage(),
			);
		}
		return $salida;
	}

        /**
	 * Determina si existen turnos vigentes para una preinscripción dada
	 *
	 * @param int $responsable_academica
	 * @param int $ubicacion
	 * @param string $modalidad
	 * @return boolean
	 */
	function hay_turnos_preinscripcion_propuesta($responsable_academica, $ubicacion, $modalidad)
	{
		$modalidad = kernel::db()->quote($modalidad);

		$sql = "SELECT COUNT(pre_turnos.pre_turno) AS cant_turnos
                        FROM pre_turnos_config
                        JOIN pre_turnos_config_ra           ON (pre_turnos_config.turno_config = pre_turnos_config_ra.turno_config)
                        JOIN pre_turnos_config_ubicacion    ON (pre_turnos_config.turno_config = pre_turnos_config_ubicacion.turno_config)
                        JOIN pre_turnos_config_modalidad    ON (pre_turnos_config.turno_config = pre_turnos_config_modalidad.turno_config)
                        JOIN pre_turnos                     ON (pre_turnos_config.turno_config = pre_turnos.turno_config)
                        WHERE pre_turnos_config_ra.responsable_academica = {$responsable_academica}
                        AND pre_turnos_config_ubicacion.ubicacion = {$ubicacion}
                        AND pre_turnos_config_modalidad.modalidad = {$modalidad}
                        -- Turnos de presentación de documentación vigentes
                        AND ((pre_turnos.fecha > CURRENT_DATE) OR (pre_turnos.fecha = CURRENT_DATE AND pre_turnos.hora >= CURRENT_TIME))
                        -- Turnos no tomados desde Preinscripción
                        AND pre_turnos.id_preinscripcion IS NULL
                        -- Turnos no tomados desde Autogestión
                        AND pre_turnos.persona IS NULL
                        ";

		$fila = $this->consultar_fila($sql);
                return $fila['cant_turnos'] > 0;
	}
        
	//--------------------------------------------------------------------------
	//---- Auxiliares ----------------------------------------------------------
	//--------------------------------------------------------------------------

	protected function get_where_ra($ra)
	{
		$ra = kernel::db()->quote($ra);
		return " AND EXISTS (
						SELECT	1
						FROM	pre_turnos_config_ra 
						WHERE	pre_turnos_config_ra.turno_config = pre_turnos.turno_config
								AND pre_turnos_config_ra.responsable_academica = $ra
					)";
	}

	protected function asignar_turno($datos_turno, $ra)
	{
		$fecha = kernel::db()->quote(convertir_fecha_formato_base($datos_turno['fecha']));
		$franja = kernel::db()->quote($datos_turno['franja']);

		$where_ra = $this->get_where_ra($ra);

		$sql = "SELECT 	
					pre_turno
				FROM
					pre_turnos
				WHERE 		
					id_preinscripcion IS NULL
					AND persona IS NULL
					AND fecha = $fecha
					AND EXTRACT(HOUR FROM hora) = $franja
					AND (
						fecha > CURRENT_DATE
						OR (
							fecha = CURRENT_DATE
							AND hora >= CURRENT_TIME
						)
					)
					$where_ra
				ORDER BY 	
					hora,
					operador
				LIMIT 1
				";

		$res = $this->consultar_fila($sql);
		if (empty($res)) {
			$mje = kernel::traductor()->trans('sel_turno.error_sin_turnos');
			throw new error_preinscripcion($mje);
		}
		return $res['pre_turno'];
	}

}