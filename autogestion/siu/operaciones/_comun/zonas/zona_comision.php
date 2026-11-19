<?php

namespace siu\operaciones\_comun\zonas;

use SIU\Chulupi\kernel;
use siu\guarani;


class zona_comision extends zona
{

	public static $clave_sesion_periodo = 'filtro_comisiones_periodos_lectivos';

	protected function get_operaciones_zona()
	{
		return array(
			//por defecto viene a la primera si no tiene derechos
			'zona_comisiones' => array('accion' => 'home', 'descripcion' => 'Inicio'),
			'notas_cursada_comision' => array('accion' => 'edicion', 'descripcion' => 'Cargar Notas'),
			'inscriptos_cursadas' => array('accion' => 'info_comision', 'descripcion' => 'Alumnos'),
			'evaluaciones' => array('accion' => 'ver_comision', 'descripcion' => 'Evaluaciones'),
			'cierre_cursadas' => array('accion' => 'index', 'descripcion' => 'Actas'),
			'administrar_cursos_virtuales' => array('accion' => 'index', 'descripcion' => 'Moodle'),
		);
	}

	public function get_link_indice()
	{
		return kernel::vinculador()->crear('zona_comisiones');
	}

	public function get_template()
	{
		return 'comision';
	}

	protected function get_transaccion()
	{
		return guarani::zona_comisiones();
	}


	/**
	 * Ejectua la consulta indice de la zona sobre la transaccion creada en get_transaccion()
	 * @param $transaccion
	 * @return mixed
	 */
	protected function get_datos_navegacion($transaccion)
	{
		return $transaccion->info__comisiones(kernel::sesion()->get('filtro_comisiones_periodos_lectivos'));
	}

	public function get_datos_detalle($transaccion, $seleccion)
	{
		$datos = $transaccion->info__comision_zona($seleccion);
		return $datos;
	}


	protected function get_descripcion_fila($fila)
	{
		$descripcion = $fila['actividad_nombre'] . ': ' .$fila['comision_nombre'];
		$subcomision = $fila['subcomision_nombre'];
		if (!!$subcomision) {
			$descripcion .= ' - Subcomision ' . $subcomision;
		}
		$tipo_clase = $fila['tipo_clase'];
		if (!!$tipo_clase) {
			$descripcion .= ' (' . $fila['tipo_clase'] . ')';
		}
		return $descripcion;
	}

	/**
	 * retorna el id de la fila
	 * @param $seleccion
	 * @return mixed
	 */
	protected function get_id_fila($seleccion)
	{
		{
			$id_seleccionado = $seleccion['id'];
			return $id_seleccionado;
		}
	}
}

?>
