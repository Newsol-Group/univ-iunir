<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class ci_edi_matriz_equivalencia extends guarani_pers_ci
{
	//Generales
	protected $indx_msg_eof;					//Se recupera el índice del mensaje eof para cuadros del ci padre.

	//Datos temporales del formulario
	protected $datos_generales_temp;

	function ini()
	{
		$this->indx_msg_eof = $this->controlador()->get_indx_msg_eof();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- datos_generales --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_datos_matriz(toba_ei_formulario $form)
	{
		$form->ef('aplicacion_automatica')->set_estado_defecto(comunes::no);
	
		$datos = $this->cn()->entidad()->get_datos_generales();
		if (!$this->cn()->entidad_es_nueva()) {
			// Datos para los Combos Plan y Versión origen con las opciones 'Todos' y 'Todas' con campos nulos.
			if ($datos['alcance'] == equivalencia::matriz_alcance_entre_planes_misma_prop && !isset($datos['plan_origen'])) {
				$datos['plan_origen'] = 'T';
			} elseif ($datos['alcance'] == equivalencia::matriz_alcance_entre_versiones && !isset($datos['plan_version_origen'])) {
				$datos['plan_version_origen'] = 'T';
			}
				
			// Si la matriz no es nueva, es decir se está editando y tiene grupos asociados, debe restringirse la edición.
			$id_matriz = $this->cn()->entidad()->get_id_matriz();
			$grupos_matriz = toba::consulta_php('co_matrices_de_equivalencia')->get_grupos_matriz_equivalencia($id_matriz);
			if (!empty($grupos_matriz)) {
				$form->set_solo_lectura(array('nro_resolucion', 'alcance', 'propuesta_origen', 'plan_origen', 'plan_version_origen'));
			}
		} 
		if (isset($this->datos_generales_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_generales_temp);
		} else { 
			$form->set_datos($datos);			
		}
	}

	function evt__form_datos_matriz__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_generales($datos);
		} catch (guarani_error $e) {
			$this->datos_generales_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

 	//-----------------------------------------------------------------------------------
	//---- COMBOS -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_propuestas_origen($alcance)
	{
		$seleccione = false;
		$propuesta = $this->controlador()->get_propuesta_destino();
		if ($alcance == equivalencia::matriz_alcance_entre_propuestas || $alcance == equivalencia::matriz_alcance_entre_planes_otra_prop) {
			$filtro = "sga_propuestas.propuesta <> $propuesta";
			$seleccione = true;
		} elseif ($alcance <> equivalencia::matriz_alcance_general) { // Entre Planes de la misma Propuesta o Entre Versiones.
			$filtro = "sga_propuestas.propuesta = $propuesta";
		} else {
			return array();
		}
		$datos = toba::consulta_php('co_propuestas')->get_listado_nombres_propuestas($filtro);
		if ($seleccione) {
			$no_seleccion = array(array('valor' => apex_ef_no_seteado, 'propuesta_descr' => '-- Seleccione --'));
			$datos = array_merge($no_seleccion, $datos);
		}
		return $datos;
	}

	function get_planes_origen($alcance, $propuesta)
	{
		$filtro = '';
		$todos = false;
		$seleccione = false;
		if ($alcance == equivalencia::matriz_alcance_entre_versiones) {
			$filtro = "sga_planes.plan = " . $this->controlador()->get_plan_destino();
		} elseif ($alcance == equivalencia::matriz_alcance_entre_planes_misma_prop) {
			$filtro = "sga_planes.plan <> " . $this->controlador()->get_plan_destino();
			$todos = true;
			$seleccione = true;
		} elseif ($alcance == equivalencia::matriz_alcance_entre_planes_otra_prop) {
			$seleccione = true;
		} else {
			return array();
		}	
		$datos = toba::consulta_php('co_planes')->get_planes_propuesta($propuesta, $filtro);
		if ($todos) {
			$todos = array(array('valor' => 'T', 'descr' => 'Todos'));
			$datos = array_merge($todos, $datos);
		}
		if ($seleccione) {
			$no_seleccion = array(array('valor' => apex_ef_no_seteado, 'descr' => '-- Seleccione --'));
			$datos = array_merge($no_seleccion, $datos);
		}
		return $datos;
	}

	function get_versiones_origen($alcance, $plan)
	{
		if ($plan == 'T') {
			// Para que funcione la carga de la cascada en el caso Todos los Planes, aunque la versión no se muestre.
			return array();
		} else {
			$filtro = '';
			$todas = false;
			if ($alcance == equivalencia::matriz_alcance_entre_versiones) {
				$filtro = "sga_planes_versiones.plan_version <> " . $this->controlador()->get_plan_version_destino();
				$todas = true;
			} 
			$datos = toba::consulta_php('co_planes')->get_versiones_por_plan($plan, $filtro);	
			if ($todas) {
				$todos = array(array('valor' => 'T', 'descr' => 'Todas'));
				$datos = array_merge($todos, $datos);
			}
			return $datos;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Consultas AJAX ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Cuando el alcance es distinto de General y el usuario selecciona una propuesta origen, se verifica si la matriz
	 * no tiene asignado nombre, para setearle por defecto el nombre de la misma.
	 */
	function ajax__nombre_matriz_defecto($params, toba_ajax_respuesta $respuesta) 
	{
		$prop_origen = $params[0];
		$datos_propuesta = toba::consulta_php('co_propuestas')->get_datos_propuesta($prop_origen);
		$salida = array('nombre'=>$datos_propuesta['nombre']);
		$respuesta->set($salida);
	}
}
?>