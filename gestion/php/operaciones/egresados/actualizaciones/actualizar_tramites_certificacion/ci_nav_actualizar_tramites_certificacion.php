<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_nav_actualizar_tramites_certificacion extends ci_nav_sin_listado_inicial
{
	// Datos del trámite seleccionado.
	protected $s__datos_tramite;

	// Componente Visual de Información.
	protected $ev_info;
	
	function ini__operacion()
	{
		$nro_solicitud = toba::memoria()->get_parametro('nro_solicitud');		
		if (! empty($nro_solicitud)) {
			$this->s__seleccion = array('nro_solicitud' => $nro_solicitud);
			$this->cn()->cargar($this->s__seleccion);
			$datos_tramite = $this->cn()->entidad()->get_datos_tramite();			
			$this->s__seleccion['propuesta'] = guarani::co('co_alumnos')->get_alumno_propuesta($datos_tramite['alumno']);
			$this->set_pantalla('edicion');
		}
	}

	function get_datos_tramite()
	{
		return $this->s__datos_tramite;
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->cn()->validar_seleccion_tramite($seleccion['nro_solicitud']);
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
		$this->s__datos_tramite = current(toba::consulta_php('co_tramites_certificacion')->get_listado($filtro));		
		$this->ev_info()->set_datos($this->s__datos_tramite);
	}

	function conf__resumen()
	{
		$this->ev_info()->set_datos($this->s__datos_tramite);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nro_solicitud', 'apellido_y_nombres', 'documento_principal', 'certificado_nombre', 'plan_nombre'));
			$this->ev_info->set_max_columnas_por_fila(2);
			
			$this->ev_info->set_columnas_fila(array('nro_solicitud'), 1);			
			$this->ev_info->set_columnas_fila(array('apellido_y_nombres', 'documento_principal'), 2);
			$this->ev_info->set_columnas_fila(array('certificado_nombre', 'plan_nombre'), 3);
			
			$this->ev_info->set_etiqueta('nro_solicitud', 'Nro. Trámite');
			$this->ev_info->set_etiqueta('apellido_y_nombres', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('certificado_nombre', 'Certificado');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		try {
			$this->cn()->guardar();
			$datos_tramite = $this->cn()->entidad()->get_datos_tramite();

			if ($this->cn()->entidad()->registrar_historia_academica()) {
				guarani::act('act_tramites_certificacion')->eliminar_historia_academica($datos_tramite['nro_solicitud']);
				guarani::act('act_tramites_certificacion')->registrar_historia_academica($datos_tramite['alumno'], $datos_tramite['nro_solicitud']);
			}

			// Invoco al notificador para que envíe mensaje/email según corresponda.
			$parametros = ['nro_tramite' => $datos_tramite['nro_solicitud']];
			guarani::notificador()->enviar(notificador::actualizar_tramite_certificacion, $parametros);

			$this->set_pantalla('resumen');
		} catch (guarani_error $e) {
			throw $e;
		}
	}
}
?>