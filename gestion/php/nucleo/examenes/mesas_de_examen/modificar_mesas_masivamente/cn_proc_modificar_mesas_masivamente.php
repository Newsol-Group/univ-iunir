<?php 
/**
 *
 *
 * @package Examenes
 * @subpackage Modificar Mesas Masivamente
 */

class cn_proc_modificar_mesas_masivamente extends cn_procesos_bk
{	
	protected $hay_errores = false;
	protected $errores;
    
	//-----------------------------------------------------------------------------------
	//---- Funciones de manejo del proceso background -----------------------------------
	//-----------------------------------------------------------------------------------
	
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_modificar_mesas_masivamente::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Validaciones sobre los datos ingresados para la Copia de Mesas ---------------
	//-----------------------------------------------------------------------------------
	
	function validar_datos_tipo_modificacion($tipo, $fecha=null, $hora_inicio=null, $hora_finalizacion=null)
	{
		//Verifica que la existan Mesas en el Turno y Ubicación origen seleccionados para la Copia.
		if ($tipo == mesa_de_examen::cambio_fecha) {
			$this->validar_datos_cambio_fecha($fecha);
		} elseif ($tipo == mesa_de_examen::cambio_horario) {
			$this->validar_datos_cambio_horario($hora_inicio, $hora_finalizacion);
		} 
		
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_datos_cambio_fecha($fecha=null)
	{
		if (!isset($fecha)) {
			$this->set_error('800_dato_dep_obligatorio', array('Fecha', 'Tipo de Modificación'));
		} else if (guarani_fecha::es_menor_a_fecha_actual($fecha)) {
			//Fecha mayor o igual a hoy.			
			$this->set_error('800_control_fechas', array('nueva del LLamado Mesa', 'menor', 'actual'));
		}
	}
	
	function validar_datos_cambio_horario($hora_inicio=null, $hora_finalizacion=null)
	{
		if (!isset($hora_inicio)) {
			$this->set_error('800_dato_dep_obligatorio', array('Hora Inicio', 'Tipo de Modificación'));
		} elseif (isset($hora_finalizacion)) {
			if (!guarani_hora::es_mayor($hora_finalizacion, $hora_inicio)) {
				$this->set_error('800_error_rango_horas');
			}
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Validaciones sobre los datos ingresados para la Copia de Mesas ---------------
	//-----------------------------------------------------------------------------------
	
	function validar_mesas_a_modificar()
	{
		// No tiene sentido validar que no tengan acta abierta/cerrada dado que esas mesas no se muestran!
		// Alguna otra validación?
	}
	
	//-----------------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
    /**
	 * Acumula mensajes a enviar a la pantalla
	 */	
	function set_error($descripcion_corta, $parms=null, $tipo=null)
	{
		$mensaje = guarani_mensajes::get_msg_info($descripcion_corta, $parms);
		//Si no está el mensaje definido en la base
		if (!$mensaje) {
			$mensaje = array('mensaje' => $descripcion_corta, 'tipo' => comunes::error_critico);
		}
		//Si me definió un tratamiento para el mensaje
		if ($tipo) {
			$mensaje['tipo'] = $tipo;
		}
		$this->hay_errores = true;
		$this->errores[] = $mensaje;
	}
    
    function get_mensaje_error($separador="\n")
	{
		return implode($separador, aplanar_matriz($this->errores, 'mensaje'));
	}

	function get_errores()
	{
		return $this->errores;
	}

	function hay_errores()
	{
		return $this->hay_errores;	
	}
}
?>