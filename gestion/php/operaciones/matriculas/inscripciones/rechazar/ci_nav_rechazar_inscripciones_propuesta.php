<?php
/**
 *
 * 
 * @package		Matrícula
 * @subpackage	Inscripciones
 * 
 */
class ci_nav_rechazar_inscripciones_propuesta extends ci_nav_sin_listado_inicial
{
	protected $limpiar_seleccion = false;
	protected $s__seleccion_aplanada;
	protected $s__motivo_rechazo;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_seleccion(toba_ei_pantalla $pantalla)
	{
		if ($this->limpiar_seleccion) {
			unset($this->s__seleccion);
			unset($this->s__seleccion_aplanada);
		}
		$this->cn()->resetear_atributos_parametros();
		// Si no hay datos, se elimina el botón 'Continuar'
		$datos = $this->dep('cuadro')->get_datos();
		if (empty($datos)) {
			$pantalla->eliminar_evento('continuar');
		}
	}

	function conf__pant_rechazo(toba_ei_pantalla $pantalla)
	{
		$this->evento('rechazar')->set_msg_confirmacion(guarani_mensajes::get('800MAT_rechazar_inscripciones'));
		$seleccion = current($this->s__seleccion);
		$datos = array(
			'anio_academico' => $seleccion['anio_academico'],
			'propuesta_descr' => $seleccion['propuesta_descripcion']
		);
		$this->ev_info()->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		$this->limpiar_seleccion = true;
		parent::evt__filtro__filtrar($datos);
		
	}

	function evt__filtro__cancelar()
	{
		$this->limpiar_seleccion = true;
		parent::evt__filtro__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($datos)
	{
		$this->s__seleccion = $datos;
		if (!empty($datos)) {
			$this->s__seleccion_aplanada = aplanar_matriz($datos, 'propuesta_aspira');
		}
		// Carga de atributos
		$propuestas = array($propuesta = $this->s__datos['propuesta']['valor']);
		$this->cn()->cargar_atributos_parametros($propuestas);
	}

	//-----------------------------------------------------------------------------------
	//---- form_motivo_rechazo ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_motivo_rechazo__modificacion($datos)
	{
		$this->s__motivo_rechazo = $datos;
	}

	function conf__form_motivo_rechazo(toba_ei_formulario $form)
	{
		if (isset($this->s__motivo_rechazo)) {
			$form->set_datos($this->s__motivo_rechazo);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_rechazo ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_rechazo(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'propuesta_descripcion'));
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_td_etiqueta_width('70px');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('propuesta_descripcion', 'Propuesta');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__continuar()
	{
		if (empty($this->s__seleccion)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una inscripción'));
			throw guarani::error($mje);
		}
		$this->set_pantalla('pant_rechazo');
	}

	function evt__volver()
	{
		$this->set_pantalla('pant_seleccion');
	}

	function evt__rechazar()
	{
		try {
			$propuesta = $this->s__datos['propuesta']['valor'];
			$rechazo_con_ha = $this->cn()->rechazar_inscripciones($propuesta, $this->s__seleccion, $this->s__motivo_rechazo['situacion_asp'], $this->s__motivo_rechazo['observaciones']);
			if (!$rechazo_con_ha) {
				$mensaje = guarani_mensajes::get('800MAT_rechazar_inscripciones_con_HA_manualmente');
			} else {
				$mensaje = guarani_mensajes::get('800_rechazar_inscripciones_ok');
			}
			toba::notificacion()->info($mensaje);
		} catch (toba_error $e) {
			$mensaje = guarani_mensajes::get('800_rechazar_inscripciones_error');
			toba::notificacion()->agregar($mensaje);
		}
		unset($this->s__seleccion);
		unset($this->s__seleccion_aplanada);
		unset($this->s__motivo_rechazo);
		$this->set_pantalla('pant_seleccion');
	}

}
?>