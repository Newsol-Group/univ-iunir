<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_edi_caminos extends guarani_pers_ci
{
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';

	protected $datos_camino;
	protected $datos_forms;
	protected $datos_campos;

	function ini()
	{
		$this->dep('ml_forms')->set_columna_ordenamiento('formulario_nombre');
		$this->dep('ml_campos')->set_columna_ordenamiento('etiqueta');
	}

	//---------------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN CAMINO
	//---------------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- cuadro_caminos ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_caminos(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_caminos();
		foreach ($datos as $clave => $valor) {
			// Se limpia el contenido de la columna externa 'nombre_estado_inicial' cuando el estado es vacío.
			if (empty($valor['estado_inicial'])) {
				$datos[$clave]['estado_inicial_nombre'] = '';
			}
		}
		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array('Caminos de Egreso')));
	}

	function evt__cuadro_caminos__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_camino($seleccion);
		$this->set_pantalla('edicion_camino');
	}

	function evt__cuadro_caminos__agregar()
	{
		$this->set_pantalla('edicion_camino');
	}

	//---------------------------------------------------------------------------------------
	//---- PANTALLA EDICIÓN CAMINO
	//---------------------------------------------------------------------------------------

	function conf__edicion_camino(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		if ($this->controlador()->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
			$this->controlador()->controlador()->evento('eliminar')->desactivar();
		}
		$this->controlador()->controlador()->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_camino_seleccionado()) {
			$this->evento('alta')->ocultar();
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array('Camino de Egreso')));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_camino ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_camino(form_camino $form)
	{
		if (isset($this->datos_camino)) {
			$form->set_datos($this->datos_camino);
		} else {
			$datos = $this->cn()->entidad()->get_datos_camino_seleccionado();
			$form->set_datos($datos);
		}		
	}

	function evt__form_camino__modificacion($datos)
	{
		$this->datos_camino = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- ml_forms ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__ml_forms(form_ml_requerido $form_ml)
	{
		if (isset($this->datos_forms)) {
			$datos = $this->datos_forms;
		} else {
			$datos = $this->cn()->entidad()->get_datos_forms();
		}
		
		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'mce_formularios', 'formulario', 'formulario', true);
		$datos_posibles = toba::consulta_php('co_circuitos_tramite')->get_listado_formularios_egreso($filtro);
		
		foreach ($datos_posibles as $key => $dato) {
			$datos_posibles[$key]['cant_copias'] = 1;
		}

		$form_ml->set_datos($datos);
		$form_ml->set_datos_posibles($datos_posibles);		
	}

	function evt__ml_forms__modificacion($datos)
	{
		$this->datos_forms = $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- ml_campos ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__ml_campos(form_ml_requerido $form_ml)
	{
		if (isset($this->datos_campos)) {
			$datos = $this->datos_campos;
		} else {
			$datos = $this->cn()->entidad()->get_datos_campos();
		}

		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'mce_campos', 'campo', 'campo', true);
		$datos_posibles = toba::consulta_php('co_circuitos_tramite')->get_listado_campos_egreso($filtro);

		$form_ml->set_datos($datos);
		$form_ml->set_datos_posibles($datos_posibles);		
	}

	function evt__ml_campos__modificacion($datos)
	{
		$this->datos_campos = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$this->cn()->entidad()->nuevo_camino($this->datos_camino);
		$this->cn()->entidad()->set_datos_forms($this->datos_forms);
		$this->cn()->entidad()->set_datos_campos($this->datos_campos);
		$this->evt__cancelar();
	}

	function evt__modificacion()
	{
		$this->cn()->entidad()->set_datos_camino($this->datos_camino);
		$this->cn()->entidad()->set_datos_forms($this->datos_forms);
		$this->cn()->entidad()->set_datos_campos($this->datos_campos);
		$this->evt__cancelar();
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_camino_seleccionado();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		unset($this->datos_camino);
		unset($this->datos_campos);
		unset($this->datos_forms);
		
		$this->cn()->entidad()->deseleccionar_camino();
		$this->set_pantalla('seleccion_camino');
	}
}
?>