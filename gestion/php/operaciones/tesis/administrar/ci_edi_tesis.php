<?php
/**
 *
 *
 * @package 	Tesis
 * @subpackage	-
 * 
 */
class ci_edi_tesis extends guarani_pers_ci
{
	// Estado al que pasa la tesis producto de la acción realizada
	protected $s__accion_tesis;
	protected $s__datos_imp;

	//Fecha de alta de la tesis, usada para validar
	protected $fecha_alta = '';
	// Temporales
	protected $datos_temp;

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DATOS PRINCIPALES 
	//-----------------------------------------------------------------------------------

	function conf__datos_principales(toba_ei_pantalla $pantalla)
	{
		// Se limpian los valores en memoria que filtran las actividades del popup de selección.
		toba::memoria()->eliminar_dato('lista_actividades');
		toba::memoria()->eliminar_dato('plan_version');
		toba::memoria()->eliminar_dato('alumno');
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos();
		}

		if (isset($this->s__accion_tesis)) {
			// Como aún no se debe guardar el cambio de estado se lee de sesión para setear
			$datos = array_merge($datos, $this->s__accion_tesis);
		} else {
			// Si no, se establece el valor correspondiente al estado actual
			$datos = $this->get_observaciones_estado_actual() != '' ? array_merge($datos, array('observaciones' => $this->get_observaciones_estado_actual())) : $datos;
		}

		$solo_lectura = array('estado_inicial_nombre', 'estado_final_nombre', 'formularios');

		if ($this->cn()->entidad()->es_nueva()) {
			// Al crear una tesis se setea el estado actual en vacío para poder 
			// cargar el combo acción a partir del circuito seleccionado y el estado actual
			$datos['estado_actual'] = '';
		} else {
			// Si se edita una tesis no se le puede modificar el circuito seleccionado
			array_push($solo_lectura, 'circuito');
		}
		
		$this->fecha_alta = isset($datos['fecha_alta']) ? $datos['fecha_alta']: '';
		$form->set_solo_lectura($solo_lectura);		
		$form->set_datos($datos);
	}

	function evt__formulario__modificacion($datos)
	{
		if (!is_null($datos['accion'])) {
			$this->s__accion_tesis['observaciones'] = $datos['observaciones'];
			$datos = array_merge($datos, $this->s__accion_tesis);
			$this->s__datos_imp = $datos;
		} else {
			unset($this->s__accion_tesis);
		}

		try {
			$this->cn()->entidad()->set_datos($datos);
		} catch (guarani_error $e) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA HISTORIAL MOVIMIENTOS 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- cuadro_movimientos -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_movimientos(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_cambios_estados();
		$cuadro->set_datos($datos);
		
		if (empty($datos)) {
			$etiquetas = guarani_etiquetas::get_tabla('sga_tesis_cmb');
			$etiqueta = $etiquetas->get_enf('plural');
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta)));
		}
	}

	function get_observaciones_estado_actual()
	{
		$datos = $this->cn()->entidad()->get_datos_cambios_estados();
		
		if ($datos) {
			$ultimo_cambio_estado = end($datos);
			//Quita los saltos de linea que generan error.
			$observaciones = preg_replace(tesis_nucleo::expr_regular, " ", $ultimo_cambio_estado['observaciones']);
			return $observaciones;
		}

		return '';
	}

	function get_datos_cambio_estado()
	{				
		if (isset($this->s__accion_tesis)) {
			$datos = $this->cn()->entidad()->get_datos();
			if (isset($this->s__accion_tesis['observaciones'])){
				//Quita los saltos de linea que generan error.
				$this->s__accion_tesis['observaciones'] = preg_replace(tesis_nucleo::expr_regular, " ", $this->s__accion_tesis['observaciones']);
			}else{
				$this->s__accion_tesis['observaciones'] = '';
			}

			return array(
				'accion' => $this->s__accion_tesis['accion'],
				'estado_anterior' => $datos['estado_actual'],
				'estado_actual' => $this->s__accion_tesis['estado_final'],
				'estado_nuevo' => $this->s__accion_tesis['estado_final'],
				'observaciones' => $this->s__accion_tesis['observaciones']
			);
		}
	}

	function get_datos_cambio_estado_mail()
	{
		return $this->s__datos_imp;
	}

	function reset_vars()
	{
		unset($this->s__datos_imp);
		unset($this->s__accion_tesis);
		$this->dep('seleccion_alumnos')->filtro_limpiar('filtro');
		$this->dep('seleccion_tutores')->filtro_limpiar('filtro');
		$this->dep('seleccion_evaluadores')->filtro_limpiar('filtro');
	}

	//-----------------------------------------------------------------------------------
	//---- Consultas AJAX ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * El usuario selecciona una acción a seguir para la tesis, por lo cual se recuperan los datos de 
	 * la misma para setearlos.
	 */
	function ajax__datos_accion_tesis($params, toba_ajax_respuesta $respuesta)
	{
		$circuito = $params['circuito'];
		$accion = $params['accion'];
		$estado_actual = $this->cn()->entidad()->get_estado_actual();
		
		$datos_estado_final = current(toba::consulta_php('co_circuitos_tramite')->get_estado_final_por_circuito_estado_accion($circuito, $estado_actual, $accion));
		$formularios = toba::consulta_php('co_circuitos_tramite')->get_formularios_por_circuito_estado_accion($circuito, $estado_actual, $accion);
		$lista_forms = guarani_texto::armar_lista(guarani_arreglos::aplanar_matriz_sin_nulos($formularios, 'nombre'), ' - ');
		$habilitar_fecha_aprob = $datos_estado_final['estado_final'] == tesis::estado_tesis_aprobada;

		$this->s__accion_tesis['accion'] = $accion;
		$this->s__accion_tesis['estado_final'] = $datos_estado_final['estado_final'];
		$this->s__accion_tesis['estado_final_nombre'] = $datos_estado_final['nombre'];
		$this->s__accion_tesis['formularios'] = $lista_forms;
		$salida = array_merge($datos_estado_final, array('formularios' => $lista_forms), array('habilitar_fecha_aprob' => $habilitar_fecha_aprob), ['fecha_alta' => $this->fecha_alta]);
		$respuesta->set($salida);
	}
    
    //-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
    
	function get_acciones($circuito)
	{
		$datos_tesis = $this->cn()->entidad()->get_datos();
		return toba::consulta_php('co_circuitos_tramite')->get_acciones_por_circuito_estado($circuito, $datos_tesis['estado_actual'] ?? null);
	}
    
    /**
     * El metodo devuelve True si la tesis es un Alta, es decir no presenta estado, False caso contrario
     * Ticket #32454
     * @return boolean
     */
    function get_estado_incial() {
        $estado_tesis = $this->cn()->entidad()->get_estado_actual();
        if (isset($estado_tesis)) {
            return $estado_tesis;
        } else {
			$estado_tesis = -1;
            return $estado_tesis;
        }
    }
}
