<?php
/**
 *
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_rep_ha_inv_conv extends ci_rep_sin_listado_inicial
{
	protected $s__alumno;
	protected $ev_info;

	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_listado($filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__alumno = $seleccion['alumno'];
		$this->set_pantalla('listado');
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__listado()
	{
		$datos = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($this->s__alumno);
		$this->ev_info()->set_datos($datos);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'legajo', 'propuesta_nombre_abreviado', 'plan_nombre', 'ubicacion_nombre'));

			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'legajo'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre'), 3);

			$this->ev_info->set_max_columnas_por_fila(2);

			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');

			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');

			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		unset($this->s__alumno);
		$this->set_pantalla('alumnos');
	}

}
?>