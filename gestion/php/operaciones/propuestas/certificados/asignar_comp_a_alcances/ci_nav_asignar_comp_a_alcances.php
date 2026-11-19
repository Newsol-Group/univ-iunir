<?php
/**
 *
 *
 * @package     Propuestas Formativas
 * @subpackage  Certificados
 * 
 */
class ci_nav_asignar_comp_a_alcances extends ci_nav_con_preseleccion
{
	protected $s__preseleccion;
	protected $s__datos_ev_info = array();
	
	// Componente Visual de Información.
	protected $ev_info;

	function ini()
	{
		parent::set_concepto_preseleccion('Certificado');
	}

	function set_preseleccion($seleccion)
	{
		$this->s__preseleccion = $seleccion['certificado'];
		$this->s__datos_ev_info['certificado'] = $seleccion['nombre'];
	}

	function get_preseleccion()
	{
		return $this->s__preseleccion;
	}
	
	function get_seleccion()
	{
		return $this->s__seleccion['alcance'];
	}
	
	function get_datos_ev_info()
	{
		return $this->s__datos_ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Preselección 
	//-----------------------------------------------------------------------------------    
	
	function conf__filtro_preseleccion(toba_ei_filtro $filtro)
	{
		parent::conf__filtro_preseleccion($filtro);
		$filtro->columna('responsable_academica')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro_preseleccion__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_preseleccion = $datos;
			$filtro = '';
			if ((isset($datos['responsable_academica']))) {
				$ra = toba::db()->quote($datos['responsable_academica']['valor']);
				$filtro = " AND EXISTS(	SELECT 	1 
										FROM 	sga_certificados_ra
										WHERE	sga_certificados_ra.certificado = sga_certificados.certificado
												AND sga_certificados_ra.responsable_academica = $ra)";
				$this->dep('filtro_preseleccion')->columna('responsable_academica')->resetear_estado();
			}
			$this->s__filtro_preseleccion = "{$this->dep('filtro_preseleccion')->get_sql_where()} $filtro";
		} else {
			unset($this->s__datos_preseleccion);
			$this->s__filtro_preseleccion = '';
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección 
	//-----------------------------------------------------------------------------------    

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------    
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('certificado'));
			$this->ev_info->set_columnas_fila(array('certificado'), 1);
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_etiqueta('certificado', 'Certificado');
			$this->ev_info->set_td_etiqueta_width('100px');
		}
		return $this->ev_info;
	}
	
	function conf__seleccion()
	{
		$this->ev_info()->set_datos($this->get_datos_ev_info());
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------    
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_certificados_alcances');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->get_listado_asignacion_comp_a_alcances($this->get_preseleccion(), $this->get_filtro_condicion());
		$cuadro->set_datos($datos);
	}
	
	function evt__cuadro__seleccion($seleccion)
	{		
		$this->s__datos_ev_info['alcance'] = $seleccion['alcance_descr'];
		unset($seleccion['alcance_descr']);
		parent::evt__cuadro__seleccion($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar_seleccion()
	{
		parent::evt__cancelar();
		$this->set_pantalla('preseleccion');
	}

	function evt__guardar()
	{
		parent::evt__guardar();
		$this->set_pantalla('preseleccion');
	}

}
?>