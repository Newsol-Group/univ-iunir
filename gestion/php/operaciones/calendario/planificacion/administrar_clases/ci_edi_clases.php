<?php
/**
 *
 *
 * @package Calendario
 * @subpackage Planificacion
 */
class ci_edi_clases extends guarani_pers_ci
{
	protected $datos_temp;
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function get_datos()
	{
		return $this->datos_temp;
	}
	
	function get_datos_combo_bh()
	{
		// Seteo de parámetros para el popup de banda horaria.
		$subcomision = $this->controlador()->get_seleccion_subcomision();
		if (!is_null($subcomision)) {
			$filtro = "sga_subcomisiones_bh.subcomision = $subcomision";
			return toba::consulta_php('co_bandas_horarias')->get_listado_bh_propias_subcomisiones($filtro);	
		} else {
			$comision = $this->controlador()->get_preseleccion();
			$filtro = "sga_comisiones_bh.comision = $comision";
			return toba::consulta_php('co_bandas_horarias')->get_listado_bh_propias($filtro);
		}
	}

	function get_datos_combo_tramos()
	{
		$datos_comision = $this->controlador()->get_datos_comision();
		$periodo_lectivo = $datos_comision['periodo_lectivo'];
        return toba::consulta_php('co_periodos_lectivos')->get_tramos_por_periodos_lectivos($periodo_lectivo);
	}
        
	//-----------------------------------------------------------------------------------
	//---- Pantalla Inicial -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- formulario -------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		$datos_comision = $this->controlador()->get_datos_comision();
		
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);		
		} elseif ($this->controlador()->hay_clase_seleccionada()) {
			$datos_clase = $this->controlador()->get_datos_clase();
			$form->set_datos($datos_clase);
			// Si se está editando, se setean de sólo lectura los campos correspondientes.
			$form->set_solo_lectura(array('utilizar_bh_definida', 'banda_horaria', 'fecha', 'hora_inicio', 'hora_finalizacion', 'cantidad_horas_dictadas', 'tipo_clase', 'tema_planificado', 'id_tramo'));
		}
	}
	
	function evt__formulario__modificacion($datos)
	{
		$this->datos_temp = $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Consultas AJAX ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * El usuario selecciona un BH ya existente para asociar a la clase, por lo cual se recuperan los datos de 
	 * la misma para setearlos por defecto.
	 */
	function ajax__datos_banda_horaria_existente($params, toba_ajax_respuesta $respuesta) 
	{
		$bh = $params['bh'];
		$datos_bh = toba::consulta_php('co_bandas_horarias')->get_datos_banda_horaria($bh);
		if (!empty($datos_bh)) {
			$salida = current($datos_bh);
			$respuesta->set($salida);
		} else {
			$respuesta->set(false);
		}
	}
}
?>
