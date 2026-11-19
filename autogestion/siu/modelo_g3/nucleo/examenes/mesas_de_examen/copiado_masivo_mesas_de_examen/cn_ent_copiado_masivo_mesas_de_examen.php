<?php 
/**
 *
 *
 * @package Examenes
 * @subpackage Copiado Masivo de Mesas de Examen
 */

class cn_ent_copiado_masivo_mesas_de_examen extends cn_procesos_bk
{	
	protected $s__actividades;
	protected $hay_errores = false;
	protected $errores;
    
	//-----------------------------------------------------------------------------------
	//---- Funciones de manejo del proceso background -----------------------------------
	//-----------------------------------------------------------------------------------
	
	function ejecutar_proceso($datos)
	{
		$datos_proceso = array_merge($datos, array('actividades' => $this->s__actividades));
		$this->s__proceso = guarani::proceso_bk(pro_copiado_masivo_mesas_de_examen::nombre);
		$this->s__proceso->set_datos($datos_proceso);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Validaciones sobre los datos ingresados para la Copia de Mesas ---------------
	//-----------------------------------------------------------------------------------
	
	function validar_datos_origen($ubicacion, $turno_examen_origen)
	{
		//Verifica que la existan Mesas en el Turno y Ubicación origen seleccionados para la Copia.
		$this->validar_existencia_mesas($ubicacion, $turno_examen_origen);
		
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_existencia_mesas($ubicacion, $turno_examen)
	{
		$hay_mesas = toba::consulta_php('co_mesas_de_examen')->existen_mesas_turno_ubicacion($ubicacion, $turno_examen);
		if (!$hay_mesas) {
			//Datos a mostrar
			$datos_turno = toba::consulta_php('co_turnos_de_examen')->get_datos_turno($turno_examen);
			$datos_ubicacion = toba::consulta_php('co_ubicaciones')->get_ubicacion_info($ubicacion);
			$this->set_error('800EXA_copiar_ubic_sin_mesa', array($datos_turno['nombre'], $datos_ubicacion['ubicacion_nombre']));
		}
	}

	function validar_datos_destino($turno_origen, $turno_destino, $ras, $ubicacion, $planes)
	{
		//Se chequea si la cantidad de Llamados en el Turno Origen es igual a la cantidad de Llamados en el Turno Destino.
		$this->validar_cant_llamados($turno_origen, $turno_destino);
		//Se chequea que existan Actividades con Mesas en el Origen según los filtros ingresados por el usuario.
		$this->validar_existencia_actividades($ras, $planes, $turno_origen, $ubicacion);
		
		if ($this->hay_errores()) {
			unset($this->s__actividades);
			throw new guarani_error($this->get_mensaje_error());
		} 
	}
	
	/**
	 * Se determina si la cantidad de Llamados del Turno Origen es igual a la cantidad de Llamados del Turno Destino,
	 * ya que de otra forma no se puede realizar la copia de Mesas de Origen a Destino.
	 */
	function validar_cant_llamados($turno_origen, $turno_destino)
	{
		$llamados_origen = toba::consulta_php('co_turnos_de_examen')->get_llamados_turno($turno_origen);
		$llamados_destino = toba::consulta_php('co_turnos_de_examen')->get_llamados_turno($turno_destino);
		if (guarani_arreglos::count($llamados_origen) != guarani_arreglos::count($llamados_destino)) {
			$this->set_error('800EXA_cantidad_llamados');
		}
	}
	
	/**
	 * Se chequea que existan actividades, segun los filtros ingresados, para generarle Mesas en el Turno Destino.
	 * Se recuperan las Actividades correspondientes a las Resonsables Académicas y Planes selecionados
	 * que tengan Mesas de Examen definidas para del Turno Origen, con la Ubicación seleccionada.
	 */
	function validar_existencia_actividades($ras, $planes, $turno_origen, $ubicacion)
	{
		$actividades = toba::consulta_php('co_mesas_de_examen')->get_actividades_planes_con_mesas_turno_ubicacion($ras, $planes, $turno_origen, $ubicacion);
		if (empty($actividades)) {
			$this->set_error('800EXA_copiar_sin_mesas_actividades');
		} else {
			//Se guardan las Actividades cuyas Mesas serán consideradas en el proceso de copiado.
			$this->s__actividades = $actividades;
		}
	}
    
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