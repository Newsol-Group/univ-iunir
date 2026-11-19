<?php
/**
 *
 * 
 * @package		Exámenes
 * @subpackage	Inscripciones
 * 
 */
class ci_nav_baja_insc_examen extends ci_nav_sin_listado_inicial
{
	// Componente visual de información.
	protected $ev_info;

	function get_seleccion()
	{
		return $this->s__seleccion['alumno'];
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$alumno = toba::db()->quote($this->controlador()->get_seleccion());
		$filtro = "sga_alumnos.alumno = $alumno";
		$datos = current($this->cn()->get_listado($filtro));
		$this->ev_info()->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->set_pantalla('edicion');
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();

			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'documento_principal', 'legajo',
				'propuesta_nombre_abreviado', 'plan_nombre', 'plan_version_nombre', 'ubicacion_nombre'));

			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'documento_principal', 'legajo'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre', 'plan_version_nombre', 'ubicacion_nombre'), 2);

			$this->ev_info->set_max_columnas_por_fila(4);

			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');

			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('plan_version_nombre', 'Versión');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}

}
?>