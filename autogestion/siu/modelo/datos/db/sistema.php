<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use \siu\modelo\datos\catalogo;

class sistema
{
	/**
	 * parametros: _ua, operacion, evento, _interfaz, punto_control
	 * cache: memoria
	 * cache_expiracion: 120
	 * filas: n
	 */
	function controles_activos_punto($parametros) 
	{
		$sql = "execute procedure ctr_ctrlsADisparar({$parametros['_ua']}, ".
														"{$parametros['operacion']},".
														"{$parametros['evento']},".
														"{$parametros['_interfaz']},".
														"{$parametros['punto_control']})";
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		return $datos;				
	}	
	
	/**
	 * parametros: parametro
	 * cache: memoria
	 * filas: n
	 */
	function parametro($parametros) 
	{
		$sql = "execute procedure sp_param_sistema({$parametros['parametro']})";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos;				
	}	
	
	/**
	 * cache: memoria
	 * filas: n
	 */
	function ubicaciones($parametros) 
	{
		$sql = "SELECT ubicacion, nombre FROM sga_ubicaciones";
		// WHERE ubicacion_tipo in (3,4) -- Sede/Subsede";
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		return $datos;				
	}	
	
	
	/**
	 * cache: memoria
	 * cache_expiracion: 60
	 * filas: 1
	 */
	function fecha_actual($parametros) 
	{
		$parametros['formato'] = 'DD-MM-YYYY';
		$datos = catalogo::consultar('sistema', 'fecha_actual_formato', $parametros);
		return $datos;
	}	

	/**
	 * parametros: formato
	 * cache: memoria
	 * cache_expiracion: 60
	 * filas: 1
	 */
	function fecha_actual_formato($parametros) 
	{
		// DD-MM-YYYY
		$sql = "SELECT to_char( CURRENT_DATE, {$parametros['formato']}) ";		
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos;
	}	
	
	/**
	 * parametros: mensaje
	 * cache: memoria
	 * filas: 1
	 */
	function mensaje($parametros) 
	{
		$sql = "EXECUTE PROCEDURE mme_muestramensaje({$parametros['mensaje']})";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_NUM);
		return $datos;				
	}

	/**
	 * parametros: escala_notas
	 * cache: memoria
	 * filas: n
	 */
	function escala_notas($parametros)
	{
		$sql = "SELECT		sga_escalas_notas_det.nota,
							sga_escalas_notas_det.descripcion,
							sga_escalas_notas_resultado.descripcion as resultado,
							sga_escalas_notas_resultado.resultado as resultado_codigo,
							sga_escalas_notas_det.valor_numerico,
							sga_escalas_notas_concepto.descripcion as concepto
				FROM		sga_escalas_notas_det
							LEFT JOIN sga_escalas_notas_concepto ON sga_escalas_notas_det.concepto = sga_escalas_notas_concepto.concepto,
					 		sga_escalas_notas_resultado
				WHERE		sga_escalas_notas_det.escala_nota = {$parametros['escala_notas']} AND
							sga_escalas_notas_resultado.resultado = sga_escalas_notas_det.resultado
				ORDER BY	sga_escalas_notas_det.valor_numerico ASC
		";
		$datos = kernel::db()->consultar($sql);
		return $datos;	 
	}	
	
	
	/**
	 * 
	 * cache: memoria
	 * filas: n
	 */
	function get_escalas_notas_activas()
	{
        $sql = "SELECT escala_nota,
					   nombre
                FROM	sga_escalas_notas
                WHERE	estado = '".\comunes::estado_activo."'
                ORDER BY sga_escalas_notas.nombre
                ";
                
      	$datos = kernel::db()->consultar($sql);
		return $datos;	 
		
	}

	/**
	 * cache: memoria
	 * filas: n
	 */
	function get_escalas_notas_resultados()
	{
       return \toba::consulta_php('co_escalas_notas')->get_escalas_notas_resultados();
	}

	/**
	 * parametros: escala_nota
	 * no_quote: escala_nota
	 * cache: memoria
	 * filas: n
	 */
	function get_resultados_posibles_escala_nota($parametros)
	{
       return \toba::consulta_php('co_escalas_notas')->get_resultados_posibles_escala_nota($parametros['escala_nota']);
	}
	
	
	//------------------------------------------------------------------------
	// CACHE
	//------------------------------------------------------------------------
	
	/**
	 * parametros: 
	 * cache: no
	 * filas: n
	 */
	function cache_lista_carreras() 
	{
		// Listado de propuestas con planes vigentes
		$sql = "SELECT DISTINCT sga_propuestas.propuesta, 
								sga_propuestas.nombre, 
								sga_propuestas.nombre_reducido
			   FROM sga_propuestas, 
					sga_planes
			   WHERE sga_propuestas.propuesta = sga_planes.propuesta
				 AND sga_planes.estado IN ('V','A')
			";
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		return $datos;				
	}

	/**
	 * parametros: propuesta
	 * cache: no
	 * filas: n
	 */
	function cache_lista_alumnos_carrera($parametros) 
	{
		$sql = "SELECT 
					sga_alumnos.persona,
					sga_alumnos.propuesta,
					sga_alumnos.legajo,
					sga_planes_versiones.plan,
					sga_alumnos.plan_version as version
				FROM sga_alumnos, 
					sga_planes_versiones,
					sga_planes
				WHERE sga_alumnos.propuesta = {$parametros['propuesta']}
				AND sga_planes_versiones.plan_version = sga_alumnos.plan_version
				AND sga_planes.plan = sga_planes_versiones.plan
				AND sga_alumnos.calidad IN ('A','P')
		";
		return kernel::db()->consultar($sql, db::FETCH_ASSOC);
	}

	/**
	 * parametros: 
	 * cache: no
	 * filas: n
	 */
	function cache_lista_alumnos_ua($parametros) 
	{
		$sql = "SELECT 
					sga_alumnos.persona,
					sga_alumnos.propuesta,
					sga_alumnos.legajo,
					sga_planes_versiones.plan,
					sga_alumnos.plan_version as version
				FROM sga_alumnos, 
					sga_planes_versiones,
					sga_planes
				WHERE sga_planes_versiones.plan_version = sga_alumnos.plan_version
				AND sga_planes.plan = sga_planes_versiones.plan
				AND sga_alumnos.calidad IN ('A','P')
			";
		return kernel::db()->consultar($sql, db::FETCH_ASSOC);
	}

	/**
	 * parametros: 
	 * cache: no
	 * filas: n
	 */
	function cache_lista_alumnos_solicitud_actualizacion() 
	{
		$sql = "SELECT sga_alumnos.persona,
				sga_alumnos.carrera,
				sga_alumnos.legajo
        FROM x";
		return kernel::db()->consultar($sql, db::FETCH_ASSOC);
	}
	
	/**
	 * parametros: 
	 * cache: memoria
	 * filas: 1
	 */
	function version_base_g3() 
	{
            return \toba::consulta_php('co_parametros')->get_version_app_base();
	}
	
	/**
	 * parametros:
	 * no_quote:
	 * cache: memoria
	 */
	public function get_dias_semana() 
	{
		return \toba::consulta_php('co_comunes')->get_dias_semana();
	}

	/**
	 * parametros:
	 * no_quote:
	 * cache: memoria
	 */
	public function get_turnos_cursadas() 
	{
		return \toba::consulta_php('co_comisiones')->get_listado_turnos_cursadas();
	}

	/**
	 * parametros:
	 * no_quote:
	 * cache: memoria
	 */
	public function get_ubicaciones() 
	{
		return \toba::consulta_php('co_ubicaciones')->get_ubicaciones();
	}

        /**
	 * parametros: persona, operacion, tiempo
	 * no_quote: persona, operacion, tiempo
         * cache: no
	 * filas: 1
	 */
	function bloquear_simultaneidad_operaciones($parametros)
	{
                $sql = "SELECT resultado FROM f_bloquear_simultaneidad_operaciones({$parametros['persona']}, {$parametros['operacion']}, {$parametros['tiempo']}) AS resultado";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos['resultado'];
	}

        /**
	 * parametros: persona, operacion, tiempo
	 * no_quote: persona, operacion, tiempo
         * cache: no
	 * filas: 0
	 */
	function desbloquear_simultaneidad_operaciones($parametros)
	{
                $sql = "SELECT resultado FROM f_desbloquear_simultaneidad_operaciones({$parametros['persona']}, {$parametros['operacion']}, {$parametros['tiempo']}) AS resultado";
		kernel::db()->consultar_fila($sql);
	}
}