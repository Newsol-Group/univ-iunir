<?php

namespace siu\operaciones\_comun\zonas;

use \SIU\Chulupi\kernel;
use siu\guarani;
use siu\modelo\entidades\fuente_rs_guarani;


class zona_examen extends zona
{


	protected function get_operaciones_zona()
	{
		return array(
			//por defecto viene a la primera si no tiene derechos
			'zona_examenes' => array('accion' => 'home', 'descripcion' => 'Inicio'),
			'notas_mesa_examen' => array('accion' => 'edicion', 'descripcion' => 'Cargar Notas'),
			//'inscriptos_examenes' => array('accion' => 'info_acta', 'descripcion' => 'Alumnos'),
			'inscriptos_examen' => array('accion' => 'info', 'descripcion' => 'Alumnos'),
			'impresion_actas_examen' => array('accion' => 'index', 'descripcion' => 'Actas'),


		);
	}

	public function get_link_indice()
	{
		return kernel::vinculador()->crear('zona_examenes');
	}

	protected function get_transaccion()
	{
		return guarani::zona_examenes();
	}

	/**
	 * Ejectua la consulta indice de la zona sobre la transaccion creada en get_transaccion()
	 * @param $transaccion
	 * @return mixed
	 */
	protected function get_datos_navegacion($transaccion)
	{
		return $transaccion->info__examenes();
	}


	protected function get_descripcion_fila($fila)
	{
		return $fila['actividad_nombre'] . ': ' . $fila['mesa_examen_nombre'];
	}

	/**
	 * @param $transaccion get_transaccion() plano--> no se debe devolver un modelo cofificado/derechos/hasheado
	 * @param $seleccion
	 * @return array Los datos para mostrar en el detalle (en el twig customizado de la zona)
	 */
	protected function get_datos_detalle($transaccion, $seleccion)
	{
		return $transaccion->info__mesa_zona($seleccion);
	}

	public function get_template()
	{
		return 'examen';
	}


	/**
	 * retorna el id de la fila
	 * @param $seleccion
	 * @return mixed
	 */
	protected function get_id_fila($seleccion)
	{
		$id_seleccionado = $seleccion['llamado_mesa'];
		return $id_seleccionado;

	}
}

?>
