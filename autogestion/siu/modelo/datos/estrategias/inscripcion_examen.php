<?php

namespace siu\modelo\datos\estrategias;

use siu\guarani;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;
use SIU\Chulupi\kernel;

/**
 * Integra la caché de archivo y sesión, el estado de inscripciones del alumno y
 * el display de cupos, y arma las listas de la operación
 */
class inscripcion_examen
{
	function info__lista_materias($propuesta, $alumno, $persona, $plan, $plan_version, $ubicacion)
	{
		$param_exa_filtro_actividades_con_mesas = parametro::get_valor('exa_filtro_actividades_con_mesas', array($propuesta));
		$param_exa_rinde_en_cualquier_ubicacion = parametro::get_valor('exa_rinde_en_cualquier_ubicacion', array($plan));
		$param_exa_rendir_regularizada_plan_anterior = parametro::get_valor('exa_rendir_regularizada_plan_anterior', array($propuesta) ,false);
		$param_exa_multiples_actas_examen = parametro::get_valor('exa_multiples_actas_examen', array($propuesta), false);
		
		//si no es curso tomo el plan activo 
		if (!kernel::persona()->es_perfil_cursos()) {
			$plan = guarani::persona()->get_id_plan_activo();
			$plan_version = guarani::persona()->get_id_plan_version_activo();
		}

		//-[1]- Elige entre el caché DURO y la base
		$lista_actividades = catalogo::consultar('inscripcion_examen', 'lista_materias', array(
					'propuesta' => $propuesta,
					'alumno' => $alumno,
					'persona' => $persona,
					'plan' => $plan,
					'tipo_plan' => '',
					'plan_version' => $plan_version,
					'ubicacion' => $ubicacion,
					'param_exa_filtro_actividades_con_mesas' => $param_exa_filtro_actividades_con_mesas,
					'param_exa_rinde_en_cualquier_ubicacion' => $param_exa_rinde_en_cualquier_ubicacion,
					'param_exa_rendir_regularizada_plan_anterior' => $param_exa_rendir_regularizada_plan_anterior,
					'param_exa_multiples_actas_examen' => $param_exa_multiples_actas_examen
						)
		);

		//-[2]- Suma información de estado: inscripciones realizadas
		$actividades_inscripto = guarani::persona()->examenes()->get_ids_materias_inscripto();
		kernel::log()->add_debug('actividades inscripto', $actividades_inscripto);
		foreach ($lista_actividades as $id_act => $actividad) {
			$lista_actividades[$id_act]['E_INSCRIPCION'] = 0;
			if (in_array($actividad['id'], $actividades_inscripto)) {
				$lista_actividades[$id_act]['E_INSCRIPCION'] = 1;
			}
		}
		return $lista_actividades;
	}

	function info__lista_mesas($propuesta, $alumno, $plan, $plan_version, $actividad, $ubicacion, $modalidad, $division, $tipo_plan)
	{
		$param_exa_rinde_en_cualquier_ubicacion = parametro::get_valor('exa_rinde_en_cualquier_ubicacion', array($plan));
		$param_exa_mostrar_mesas_todas_modalidades = parametro::get_valor('exa_mostrar_mesas_todas_modalidades', array($propuesta));
		$param_exa_mostrar_mesas_con_cupo_completo = parametro::get_valor('exa_mostrar_mesas_con_cupo_completo', array($propuesta));
		$param_cur_division_estricta = parametro::get_valor('cur_division_estricta', array($plan));
		$param_exa_multiples_actas_examen = parametro::get_valor('exa_multiples_actas_examen', array($propuesta));

		$llamados_mesas_disponibles = catalogo::consultar('inscripcion_examen', 'get_mesas_disponibles_para_inscripcion', array(
					'propuesta' => $propuesta,
					'alumno' => $alumno,
					'plan' => $plan,
					'plan_version' => $plan_version,
					'actividad' => $actividad,
					'ubicacion' => $ubicacion,
					'modalidad' => $modalidad,
					'division' => $division,
					'fuera_de_termino' => null,
					'param_exa_rinde_en_cualquier_ubicacion' => $param_exa_rinde_en_cualquier_ubicacion,
					'param_exa_mostrar_mesas_todas_modalidades' => $param_exa_mostrar_mesas_todas_modalidades,
					'param_cur_division_estricta' => $param_cur_division_estricta,
					'tipo_plan' => $tipo_plan,
					'param_exa_multiples_actas_examen' => $param_exa_multiples_actas_examen,
					'param_exa_mostrar_mesas_con_cupo_completo' => $param_exa_mostrar_mesas_con_cupo_completo
					)
		);

		$mesas_aplanado = array();
		foreach ($llamados_mesas_disponibles as $key => $arreglo) {
			$mesas_aplanado[$arreglo['mesa_examen']] = $arreglo['mesa_examen'];
		}

		// Se buscan las instancias de las mesas disponibles
		$instancias_mesas = catalogo::consultar('inscripcion_examen', 'get_instancias_mesas', array('tipo_plan' => $tipo_plan, 'mesas' => implode(',', $mesas_aplanado)));

		$mesas_inscripto = guarani::persona()->examenes()->info__inscripciones();

		foreach ($llamados_mesas_disponibles as $key => $mesa_disp) {
			$inscripto = false;
			foreach ($mesas_inscripto as $mesa_inscripto) {
				$llamados_mesas_disponibles[$key]['INSCRIPTO'] = false;
				if ($mesa_disp['llamado_mesa'] == $mesa_inscripto['llamado_mesa']) {
					$llamados_mesas_disponibles[$key]['INSCRIPTO'] = true;
					$llamados_mesas_disponibles[$key]['INSCRIPTO_COMO'] = $mesa_inscripto['instancia_nombre'];
					$llamados_mesas_disponibles[$key]['NRO_TRANSACCION'] = $mesa_inscripto['nro_transaccion'];
					$llamados_mesas_disponibles[$key]['ID_INSCRIPCION'] = $mesa_inscripto[catalogo::id];
					$llamados_mesas_disponibles[$key]['puede_desinscribirse'] = $mesa_inscripto['puede_desinscribirse'];
					$inscripto = true;
					break;
				}
			}

			// Sólo se agrega info de instancias a las mesas que no está inscripto.
			if (!$inscripto) {
				$llamados_mesas_disponibles[$key]['instancias'] = $instancias_mesas[$mesa_disp['mesa_examen']];
			}
		}

		return $llamados_mesas_disponibles;
	}

	function info__mesa($id)
	{
		$datos = catalogo::consultar('inscripcion_examen', 'info_mesa', array('mesa' => $id));
		return $datos;
	}

	function info__inscripcion($inscripcion)
	{
		$datos = catalogo::consultar('inscripcion_examen', 'info_inscripcion', array('inscripcion' => $inscripcion));
		return $datos;
	}

}
?>