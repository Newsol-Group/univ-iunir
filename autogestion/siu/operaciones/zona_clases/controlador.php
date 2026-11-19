<?php
namespace siu\operaciones\zona_clases;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\operaciones\_comun\zonas\zona_clase;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;
use siu\modelo\_comun\comunes;
use siu\operaciones\zona_comisiones\controlador as controlador_zona_comisiones;

class controlador extends controlador_zona_comisiones
{
	/**
	 * @return \siu\modelo\transacciones\zona_clases
	 */
	function modelo()
	{
		return guarani::zona_clases();
	}

	function get_info_zona()
	{
		if (isset($this->params_comision['comision'])) {
			return array('zona' => 'clase', 'seleccion' => $this->params_comision);
		}
		return null;
	}

	protected function get_clave_sesion_periodo()
	{
		return zona_clase::$clave_sesion_periodo;
	}

	protected function instanciar_form_builder()
	{
		return kernel::localizador()->instanciar('operaciones\zona_clases\comisiones\builder_filtro_comisiones');
	}

	//--------------------------------------------------------------------------

	protected function get_comisiones($anio_academico = null, $periodo_lectivo = null, $actividad = null /* , $propuesta = null */)
	{
		$comisiones = $this->codificador_modelo()->info__comisiones_docente($anio_academico, $periodo_lectivo, $actividad);
		$comisiones->link('LINK', kernel::ruteador()->get_id_operacion() /* 'zona_comisiones' */, 'home');
		$columnas = array('comision_nombre', 'subcomision_nombre', 'ubicacion_nombre', 'responsabilidad_nombre', 'turno_nombre', 'cant_inscriptos_comision', 'resp_docente');
		$datos = array();
		foreach ($comisiones as $fila) {
			$periodo_key = $fila['periodo_lectivo_nombre_full'];
			$actividad_key = $fila['actividad_orden'];
			foreach ($columnas as $campo) {
				$nuevo[$campo] = isset($fila[$campo]) ? $fila[$campo] : null;
			}	
			$nuevo['cant_inscriptos'] = $fila['es_subcomision'] ? $fila['cant_inscriptos_subcomision'] : $fila['cant_inscriptos_comision'];
			// Se determina si se registra asistencia
			$planes = aplanar_matriz(\toba::consulta_php('co_comisiones')->get_planes_comision($fila['comision']), 'plan');
			$registra_asistencia = parametro::get_valor('asistencia_registrar_asistencia', $planes);
			$nuevo['link'] = ($registra_asistencia == comunes::si || $registra_asistencia == 'O') ? $fila['LINK'] : '';
			
			$datos[$periodo_key][$actividad_key][] = $nuevo;
		}
		return $datos;
	}

}