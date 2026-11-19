<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 * 
 */
class ci_nav_licencias extends ci_nav_con_preseleccion
{
	protected $s__persona;
	protected $s__datos_persona;

	// Componente Visual de Información.
	protected $ev_info;

	function ini()
	{
		parent::set_concepto_preseleccion('Alumno');
	}

	function set_preseleccion($seleccion)
	{
		$this->s__persona = $seleccion['persona'];
	}

	function get_preseleccion()
	{
		return $this->s__persona;
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial_preseleccion();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Preselección --------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__cuadro_preseleccion__seleccion($seleccion)
	{
		if (!$this->cn()->es_alumno_activo($seleccion['persona'])) {
			$nombre = guarani::co('co_personas')->get_nombre_persona($seleccion['persona']);
			$mensaje = toba::mensajes()->get('800MAT_alumno_no_propuesta', array($nombre));
			throw guarani::error($mensaje);
		}
		parent::evt__cuadro_preseleccion__seleccion($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$datos_persona = toba::consulta_php('co_personas')->get_datos_personas($this->s__persona);
		$this->s__datos_persona['nombre'] = $datos_persona['nombre_completo'];
		$this->s__datos_persona['identificación'] = $datos_persona['documento'];

		$propuestas = toba::consulta_php('co_propuestas')->get_propuestas_alumno($this->s__persona);
		$this->s__datos_persona['propuestas'] = implode(' - ', aplanar_matriz($propuestas, 'descr'));

		$this->ev_info()->set_datos($this->s__datos_persona);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_licencias');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->get_listado_licencias($this->get_preseleccion());
		$cuadro->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Edición -------------------------------------------------------------
	//-----------------------------------------------------------------------------------    

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__datos_persona);
		parent::conf__edicion($pantalla);
	}

	///----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'identificación', 'propuestas'));
			
			$this->ev_info->set_columnas_fila(array('nombre', 'identificación'), 1);
			$this->ev_info->set_columnas_fila(array('propuestas'), 2);
			
			$this->ev_info->set_max_columnas_por_fila(2);
			
			$this->ev_info->set_etiqueta('nombre', 'Alumno');
			$this->ev_info->set_etiqueta('identificación', 'Identificación');
			$this->ev_info->set_etiqueta('propuestas', 'Propuesta');
			
			$this->ev_info->set_td_etiqueta_width('100px');
		}
		return $this->ev_info;
	}
}
?>