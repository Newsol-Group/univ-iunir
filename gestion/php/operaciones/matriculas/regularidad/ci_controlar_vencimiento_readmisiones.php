<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Regularidad
 */
class ci_controlar_vencimiento_readmisiones extends guarani_pers_ci
{
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__controlar()
	{
		// Obtengo los alumnos cuyas readmisiones actuales están vencidas.
		$alumnos = toba::consulta_php('co_alumnos')->get_alumnos_con_readmisiones_vencidas();

		foreach ($alumnos as $alumno) {
			try {
				toba::db()->abrir_transaccion();

				guarani::act('act_vencimientos_regularidad')->actualizar_estado_readmision($alumno['readmision'], vencimiento_regularidad::readmision_controlada);

				$filtro = "sga_propuestas_regularidad.para_controlar IN ('".vencimiento_regularidad::para_controlar_regularidad_readmisiones."', '".vencimiento_regularidad::para_controlar_readmisiones."')";
				$causas_perdida_regularidad = toba::consulta_php('co_readmisiones')->get_causas_perdida_regularidad_propuesta_ubicacion($alumno['propuesta'], $alumno['ubicacion'], $filtro);
				$requisitos_no_cumplidos = vencimiento_regularidad::get_requisitos_no_cumplidos($alumno, $causas_perdida_regularidad, $alumno['anio_academico'], $alumno['fecha_control_desde'], $alumno['fecha_vencimiento_readmision']);
				if (! empty($requisitos_no_cumplidos)) {
					$cant_perdidas_reg = toba::consulta_php('co_alumnos')->get_cant_perdidas_regularidad($alumno['alumno'], $alumno['anio_academico']) + 1;
					$perdida_regularidad = guarani::act('act_vencimientos_regularidad')->insertar_perdida_regularidad($alumno['alumno'], $alumno['anio_academico'], guarani_fecha::get_hoy(), null, $cant_perdidas_reg, $alumno['fecha_control_desde'], $alumno['fecha_vencimiento_readmision']);
					foreach ($requisitos_no_cumplidos as $requisito) {
						guarani::act('act_vencimientos_regularidad')->insertar_perdida_regularidad_det($perdida_regularidad, $requisito['causa_perdida_reg'], null);
					}
					guarani::act('act_alumnos')->actualizar_regularidad($alumno['alumno'], comunes::no);
				}

				toba::db()->cerrar_transaccion();
			} catch (guarani_error $e) {
				toba::db()->abortar_transaccion();
				throw new guarani_error($e->getMessage());
			}
		}
	}
}
?>