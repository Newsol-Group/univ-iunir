<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_nav_suplementos_titulo extends ci_nav_sin_listado_inicial
{
	// Datos del trámite seleccionado.
	protected $s__datos_tramite;

	// Componente Visual de Información.
	protected $ev_info;

	function get_datos_tramite()
	{
		return $this->s__datos_tramite;
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos_cuadro = $this->dependencia('cuadro')->get_datos();
		
		if ($datos_cuadro[$fila]['cant_anexos'] == 0) {
			$evento->ocultar();  
		} else {
			$evento->mostrar();  
		}
	}

	function conf_evt__cuadro__generar(toba_evento_usuario $evento, $fila)
	{
		$datos_cuadro = $this->dependencia('cuadro')->get_datos();

		if ($datos_cuadro[$fila]['cant_anexos'] > 0) {
			$evento->ocultar();  
		} else {
			$evento->mostrar();  
		}
	}

	function evt__cuadro__generar($seleccion)
	{
		//GENERAR SUPLEMENTOS
		alumno::generar_suplementos($seleccion);
		
		$this->s__seleccion = $seleccion;
		$this->cn()->cargar(array('nro_solicitud' => $seleccion['nro_solicitud']));
		$this->set_pantalla('edicion');
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$filtro = "sga_certificados_otorg.nro_solicitud = {$this->s__seleccion['nro_solicitud']}";
		$this->s__datos_tramite = current(toba::consulta_php('co_tramites_certificacion')->get_tramites_egreso_activos($filtro));
		$this->ev_info()->set_datos($this->s__datos_tramite);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function evt__procesar()
	{
		try {
			$this->cn()->guardar();
			if ($this->volver_al_guardar) {
				$this->cn()->resetear();
				$this->set_pantalla('seleccion');
			}
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e) {
			throw $e;
		} 
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_td_etiqueta_width('100px');
			$this->ev_info->set_table_width('65%');
			$this->ev_info->set_columnas_a_mostrar(array('documento_principal', 'apellido_y_nombres', 'certificado_nombre', 'propuesta_nombre', 'plan_nombre'));
			$this->ev_info->set_max_columnas_por_fila(2);
			
			$this->ev_info->set_columnas_fila(array('documento_principal', 'apellido_y_nombres'), 1);			
			$this->ev_info->set_columnas_fila(array('certificado_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre', 'plan_nombre'), 3);
			
			$this->ev_info->set_etiqueta('apellido_y_nombres', 'Apellido y Nombre');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('certificado_nombre', 'Certificado');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
		}
		return $this->ev_info;
	}
}
?>