<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_nav_anular_tramite_certificacion extends ci_nav_sin_listado_inicial
{
	// Datos del trámite seleccionado.
	protected $s__datos_tramite;

	// Componente Visual de Información.
	protected $ev_info;

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->cn()->cargar(array('nro_solicitud' => $seleccion['nro_solicitud']));
		$this->set_pantalla('edicion');
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pant)
	{
		$pant->set_descripcion('Sólo se listarán trámites no anulados que no estén en un estado final del circuito.');
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$filtro = "sga_certificados_otorg.nro_solicitud = {$this->s__seleccion['nro_solicitud']}";
		$this->s__datos_tramite = current(toba::consulta_php('co_tramites_certificacion')->get_listado($filtro));
		$this->s__datos_tramite['fecha_cambio_estado'] = guarani_fecha::convertir_fecha_formato_frontend($this->s__datos_tramite['fecha_cambio_estado']);
		$this->ev_info()->set_datos($this->s__datos_tramite);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nro_solicitud', 'apellido_y_nombres', 'documento_principal', 'certificado_nombre', 'propuesta', 'estado_nombre', 'fecha_cambio_estado'));
			$this->ev_info->set_max_columnas_por_fila(2);
			
			$this->ev_info->set_columnas_fila(array('nro_solicitud'), 1);			
			$this->ev_info->set_columnas_fila(array('apellido_y_nombres', 'documento_principal'), 2);
			$this->ev_info->set_columnas_fila(array('certificado_nombre', 'estado_nombre'), 3);
			$this->ev_info->set_columnas_fila(array('propuesta', 'fecha_cambio_estado'), 4);
			
			$this->ev_info->set_etiqueta('nro_solicitud', 'Nro. Trámite');
			$this->ev_info->set_etiqueta('apellido_y_nombres', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('certificado_nombre', 'Certificado');
			$this->ev_info->set_etiqueta('propuesta', 'Propuesta');
			$this->ev_info->set_etiqueta('estado_nombre', 'Estado');
			$this->ev_info->set_etiqueta('fecha_cambio_estado', 'Último cambio');
		}
		return $this->ev_info;
	}
}
?>