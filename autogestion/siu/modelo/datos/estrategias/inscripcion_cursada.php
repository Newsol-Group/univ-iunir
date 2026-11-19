<?php
namespace siu\modelo\datos\estrategias;

use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;
use SIU\Chulupi\kernel;

/**
 * Integra la caché a archivo y sesión, el estado de inscripciones del alumno
 * y el display de cupos y arma las listas de la operación
 */
class inscripcion_cursada
{
	function info__lista_materias($propuesta, $alumno, $persona, $plan, $plan_version, $ubicacion)
	{
		//-[1]- Elige entre el cache DURO y la base
		$param_cur_recursar_regularizadas = parametro::get_valor('cur_recursar_regularizadas', array($plan));
		$param_cur_filtro_actividades_con_comision = parametro::get_valor('cur_filtro_actividades_con_comision', array($propuesta));
		$param_cur_cursa_en_cualquier_ubicacion = parametro::get_valor('cur_cursa_en_cualquier_ubicacion', array($plan));
		$param_cur_ingresantes_oferta_actividades = parametro::get_valor('cur_ingresantes_oferta_actividades', array($propuesta));
		
		$parametros = array(
			'alumno' => $alumno,
			'persona' => $persona,
			'propuesta' => $propuesta,
			'plan' => $plan,
			'plan_version' => $plan_version,
			'ubicacion' => $ubicacion,
			'param_cur_ingresantes_oferta_actividades' => $param_cur_ingresantes_oferta_actividades,
			'param_cur_filtro_actividades_con_comision' => $param_cur_filtro_actividades_con_comision,
			'param_cur_cursa_en_cualquier_ubicacion' => $param_cur_cursa_en_cualquier_ubicacion,
			'param_cur_recursar_regularizadas' => $param_cur_recursar_regularizadas
		);
		$lista_actividades = catalogo::consultar('inscripcion_cursada', 'lista_materias', $parametros);

		//-[2]- Suma informacion de estado: inscripcines realizadas
		$parametros_cursada = array(
			'persona' => $persona,
			'estado' => 'AP',
			'vigentes' => 'S',
			'cursos' => false
		);

		$actividades_inscripto = $this->ids_materias_inscripto($parametros_cursada);

		foreach ($lista_actividades as $id_act => $actividad) {
			$lista_actividades[$id_act]['E_INSCRIPCION'] = 0;
			if (in_array($actividad['id'], $actividades_inscripto)) {
				$lista_actividades[$id_act]['E_INSCRIPCION'] = 1;
			}
		}
		kernel::log()->add_debug('LISTA ACTIVIDADES', $lista_actividades);
		return $lista_actividades;
	}

	function ids_materias_inscripto($parametros_cursada)
	{
		$actividades = array();
		$inscripciones = catalogo::consultar('alumno', 'inscripciones_cursadas', $parametros_cursada);
		foreach ($inscripciones as $inscripcion) {
			$actividades[] = $inscripcion['actividad'];
		}
		return $actividades;
	}

	function info__lista_comisiones($alumno, $actividad, $propuesta, $plan, $plan_version, $ubicacion, $inicial_apellido, $division)
	{
		// Busca info de comisiones (DB o sesion)
		// suma informacion de inscripciones
		// suma informacion de cupos
		
		$comisiones = catalogo::consultar('inscripcion_cursada', 'lista_comisiones', array(
					'alumno' => $alumno,
					'actividad' => $actividad,
					'propuesta' => $propuesta,
					'plan' => $plan,
					'plan_version' => $plan_version,
					'ubicacion' => $ubicacion,
					'inicial_apellido' => $inicial_apellido,
					'division' => $division,
					'param_cur_usa_subcomisiones' => parametro::get_valor('cur_usa_subcomisiones', array($propuesta)),
					'param_cur_cursa_en_cualquier_ubicacion' => parametro::get_valor('cur_cursa_en_cualquier_ubicacion', array($plan)),
					'param_cur_filtra_rango_apellido_comision' => parametro::get_valor('cur_filtra_rango_apellido_comision', array($propuesta)),
					'param_cur_filtra_rango_apellido_subco'    => parametro::get_valor('cur_filtra_rango_apellido_subco', array($propuesta)),
					'param_cur_controlar_cupo_por'             => parametro::get_valor('cur_controlar_cupo_por', array($propuesta)),
					'param_cur_division_estricta'              => parametro::get_valor('cur_division_estricta', array($plan))
					)
				);

		// Si hay comisiones prosigo
		if (!empty($comisiones)) {

			$comisiones_ids = array_column($comisiones, 'comision');
			$cupo_comisiones = catalogo::consultar('inscripcion_cursada', 'cupo_comisiones', array(
						'comisiones' => $comisiones_ids,
						'param_cur_controlar_cupo_por' => parametro::get_valor('cur_controlar_cupo_por', array($propuesta)),
						'propuesta' => $propuesta
			));

			foreach ($comisiones as $comision_id => $comision) {
				// Si la comisión tiene cupo le agrego la información 
				if (isset($cupo_comisiones[$comision_id])) {
					$comisiones[$comision_id]['tiene_cupo'] = $cupo_comisiones[$comision_id]['tiene_cupo'];
					$comisiones[$comision_id]['cupo'] = $cupo_comisiones[$comision_id]['cupo'];
					$comisiones[$comision_id]['cant_inscriptos'] = $cupo_comisiones[$comision_id]['cant_inscriptos'];
				}

				// Si la comisión tiene subcomisiones
				if (isset($comision['subcomisiones'])) {

					$subcomisiones_ids = array();
					array_walk_recursive($comision['subcomisiones'], function($val, $key) use(&$subcomisiones_ids) {
						if ($key == 'subcomision') {
							$subcomisiones_ids[] = $val;
						}
					});

					$cupo_subcomisiones = catalogo::consultar('inscripcion_cursada', 'cupo_subcomisiones', array(
								'subcomisiones' => $subcomisiones_ids,
								'param_cur_controlar_cupo_por' => parametro::get_valor('cur_controlar_cupo_por', array($propuesta))
					));

					foreach ($comision['subcomisiones'] as $tipo_clase_id => $tipo_clase) {

						foreach ($tipo_clase as $subcomision_id => $subcomision) {

							// Si la subcomision tiene cupo le agrego la información 
							if (isset($cupo_subcomisiones[$subcomision['subcomision']])) {
								$comisiones[$comision_id]['subcomisiones'][$tipo_clase_id][$subcomision_id]['tiene_cupo'] = $cupo_subcomisiones[$subcomision['subcomision']]['tiene_cupo'];
								$comisiones[$comision_id]['subcomisiones'][$tipo_clase_id][$subcomision_id]['cupo'] = $cupo_subcomisiones[$subcomision['subcomision']]['cupo'];
								$comisiones[$comision_id]['subcomisiones'][$tipo_clase_id][$subcomision_id]['cant_inscriptos'] = $cupo_subcomisiones[$subcomision['subcomision']]['cant_inscriptos'];
							}
						}
					}
				}
			}
		}
		
		kernel::log()->add_debug('====== Estrategia inscripcion_cursada/info__lista_comisiones ======', $comisiones);
		return $comisiones;
	}

	function info__lista_comisiones_baja()
	{
		$parametros_cursada = array();
		$parametros_cursada['persona'] = kernel::persona()->get_id();
		$parametros_cursada['estado'] = "T";
		$parametros_cursada['vigentes'] = "S";
		$parametros_cursada['cursos'] = false;

		return catalogo::consultar('alumno', 'inscripciones_cursadas', $parametros_cursada);
	}

	function info__comision($id, $propuesta, $plan, $plan_version)
	{
		//carrera plan version
		$datos = catalogo::consultar('inscripcion_cursada', 'info_comision', array(
					'comision' => $id,
					'propuesta' => $propuesta,
					'plan' => $plan,
					'plan_version' => $plan_version
		));
		return $datos;
	}

	function info__inscripcion($inscripcion)
	{
		$datos = catalogo::consultar('inscripcion_cursada', 'info_inscripcion', array('inscripcion' => $inscripcion));
		return $datos;
	}

}
?>