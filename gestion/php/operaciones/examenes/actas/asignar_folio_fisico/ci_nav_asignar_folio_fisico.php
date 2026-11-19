<?php
/**
 *
 * 
 * @package 	Exámenes
 * @subpackage 	Actas
 * 
 */
class ci_nav_asignar_folio_fisico extends ci_nav_actas
{
	function ini()
	{
		$volvio_edit = toba::memoria()->get_parametro('volvio_edit');
		if (isset($volvio_edit)) {
			$this->set_pantalla('edicion');
		}
	}

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial();
		$this->s__pantalla = $this->get_id_pantalla();
		if ($this->s__pantalla == 'seleccion_acta') {
			$this->set_titulo('Listado de Actas');
			$this->pantalla()->set_descripcion('Sólo pueden seleccionarse Actas Cerradas');
		} else {
			$this->set_titulo('Asignar Folio');
		}
	}

	function get_acta_seleccionada()
	{
		return $this->s__acta_seleccionada['id_acta'];
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('codigo')->set_condicion_fija('contiene');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_actas -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Se define la configuración del evento de selección para el cuadro de actas.
	 */
	function conf_evt__cuadro_actas__seleccion_acta(toba_evento_usuario $evento, $fila)
	{
		// La selección sólo estará disponible cuando el acta esté CERRADA
		$datos = $this->dep('cuadro_actas')->get_datos();
		$estado = $datos[$fila]['estado'];
		if ($estado != acta::estado_cerrada) {
			$evento->ocultar();
		} else {
			$evento->mostrar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION 
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		$this->ev_info()->ocultar_columnas(array('fecha_formato_visual', 'tipo_acta_descr', 'libro_nombre'));
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		$id_acta = $this->get_acta_seleccionada();
		$libro_tomo = $this->dep('editor')->get_libro_tomo();
		$folios = $this->dep('editor')->get_folios();

		$this->cn()->actualizar_asignaciones_ff($id_acta, $libro_tomo, $folios);
		$this->evt__cancelar();
		toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
	}

}