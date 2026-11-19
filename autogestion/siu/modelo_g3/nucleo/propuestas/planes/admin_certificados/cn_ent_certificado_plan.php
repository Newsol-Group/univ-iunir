<?php 
/**
 * Representa la definición de un Certificado sobre un plan_version
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class cn_ent_certificado_plan extends cn_ent_condiciones
{
	protected $s__certificado;

	function cargar($id) 
	{
		if (! is_array($id)) {
			$this->resetear();
			toba::cn('plan')->cargar($id);
			$this->s__plan_version = $id;
			$this->s__plan_version_editable = (! toba::cn('plan')->edicion_version_bloqueada());
			return;
		} else {
			// Carga de la relación "Planes-Certificados-Resoluciones".
			$this->s__plan_version = $id['plan_version'];
			$this->s__entidad = $id['entidad'];
			$this->s__certificado = $id['certificado'];
			$this->s__plan_version_editable = $id['plan_version_editable'];
			$clave_planes_certificados = array( 'plan_version'  => 	$this->s__plan_version,
												'certificado'	=> 	$this->s__certificado);
			$this->dep('planes_certificados_resoluciones')->resetear();
			$this->dep('planes_certificados_resoluciones')->cargar($clave_planes_certificados);

			// Carga de la relación "Condiciones-Grupos-Requisitos".
			$clave = array( 'plan_version'  => 	$this->s__plan_version,
							'entidad'	=> 	$this->s__entidad);
        	toba::logger()->info("################ Cargar condiciones ENTIDAD: {$id['entidad']} ##################");
			parent::cargar($clave);
		}
	}

	function get_id_certificado()
	{
		return $this->s__certificado;
	}
	
	function resetear()
	{
		unset($this->s__certificado);
		$this->dep('planes_certificados_resoluciones')->resetear();
		parent::resetear();
	}

	function modo_correlativas()
	{
		return false;
	}

	/**
	 * Validaciones correspondientes a la eliminación de un certificado asociado a un plan personalizado activo.
	 * La eliminación del certificado en el plan se da al sincronizar en su edición,
	 * como consecuencia de la eliminación de todos sus grupos de condiciones. 
	 */
	function validar_certificados_plan_pers_activo()
	{
		// Si no quedan grupos para el Certificado (opciones), se eliminaría la asociación del mismo al Plan
		if ($this->tabla('condiciones')->get_cantidad_filas() == 0) {
			/** Si el Plan Activo tiene un único Certificado asociado (éste único), no puede eliminarse.
			 *  Se decidió exceptuar este control para el caso de los de propuestas vocacionales, 
			 *  donde el plan puede quedar sin ningún Certificado asociado.
			 */
			if (!$this->es_propuesta_vocacional()) {
				// Control sólo para personalizados en estado activo/vigente.
				$certificados = $this->controlador()->get_certificados_plan_version();
				if (guarani_arreglos::count($certificados) == 1) {
					$msj = guarani_mensajes::get('800PRO_elim_plan_activo_ultimo_certificado');
					throw new guarani_error($msj); 
				}
			}
		}
	}
	
	function validar_parametros_requisitos()
	{
		$errores = false;
		$elementos = current($this->get_arbol_condiciones());
		if (!empty($elementos)) {
			$opcion_nro = 0;
			foreach ($elementos['grupos'] as $key_grupos => $opcion){
				$opcion_nro++;
				foreach ($opcion['requisitos'] as $key_requisitos => $requisito) {
					if ($requisito['tipo'] == requisito::comp_requisito && isset($requisito['regla'])) {
						$regla_parma_def = toba::consulta_php('co_reglas')->get_regla_params_def($requisito['regla']);
						$id = array();
						if (isset ($requisito['parametros']))
							$id = regla::separar_parametros($requisito['parametros']);
						if (guarani_arreglos::count($id) <> guarani_arreglos::count($regla_parma_def) ) {
							$nombre_requisito = toba::consulta_php('co_requisitos')->get_requisito($requisito['requisito']);
							toba::notificacion()->error($nombre_requisito.' de la Opción '.$opcion_nro);
							$errores = true;
						}
					}
				}
			}
		}
		if ($errores) {
			throw new guarani_error(null,null,'Debe Definir los parámetros de los siguientes requisitos:');
		}
	}
	
	
	function sincronizar()
	{
		$this->validar_parametros_requisitos();
		// Para la edición de un Plan Personalizado Activo.
		if ($this->es_plan_personalizado() && $this->es_version_activa()) {
			$this->validar_certificados_plan_pers_activo();
		}
		parent::sincronizar();
		//Si no quedan grupos entonces no tiene sentido el certificado en el plan -> se elimina su asociación al plan
		if ($this->tabla('condiciones')->get_cantidad_filas() == 0) {
			$this->disociar_certificado_plan();
		} else {
			if ($this->dep('planes_certificados_resoluciones')->tabla('planes_certificados')->get_cantidad_filas() == 0) {
				$this->asociar_certificado($this->s__certificado,$this->s__entidad);
			}
		}
		$this->dep('planes_certificados_resoluciones')->sincronizar();
	}

	function asociar_certificado($certificado, $entidad)
	{
		if (! $this->s__plan_version_editable) {
			throw new guarani_error("ASOCIAR CERTIFICADO: El plan no puede editarse");
		}
		$this->s__entidad = $entidad;
		toba::logger()->info("==== INICIO Asociar CERTIFICADO [$certificado]");
		//-- Controlo que el certificado no exista
		$filtro['certificado'] = $certificado;
		$id_filas = $this->dep('planes_certificados_resoluciones')->tabla('planes_certificados')->get_id_fila_condicion($filtro);
		if(guarani_arreglos::count($id_filas) > 0) {
			throw new guarani_error("ASOCIAR CERTIFICADO: El plan tiene asignado el certificado {$certificado} seleccionado");
		}
		//-- Agrego el certificado
		$datos['plan_version'] = $this->s__plan_version;
		$datos['certificado'] = $certificado;
		$certif = $this->dep('planes_certificados_resoluciones')->tabla('planes_certificados')->nueva_fila($datos);
		$this->dep('planes_certificados_resoluciones')->tabla('planes_certificados')->set_cursor($certif);
		$this->s__guardado = true;//Para que no tome esto como un cambio
		toba::logger()->info("====  FIN Asociar CERTIFICADO [cursor: $certif] *********************");
	}

	function disociar_certificado_plan()
	{
		$this->dep('planes_certificados_resoluciones')->tabla('planes_certificados')->eliminar_filas();
	}

	function desactivar_transacciones_anidadas()
	{
		$this->dep('planes_certificados_resoluciones')->tabla('planes_certificados')->get_persistidor()->desactivar_transaccion(true);
		parent::desactivar_transacciones_anidadas();
	}

	function get_datos_resoluciones()
	{
		return $this->dep('planes_certificados_resoluciones')->tabla('certificados_resoluciones')->get_filas();
	}

	function set_datos_resoluciones($datos)
	{
		if ($this->dep('planes_certificados_resoluciones')->tabla('planes_certificados')->get_cantidad_filas() == 0) {
			$this->asociar_certificado($this->s__certificado, $this->s__entidad);
		}
		$this->validar_resoluciones($datos);
		$this->dep('planes_certificados_resoluciones')->tabla('certificados_resoluciones')->procesar_filas($datos);
	}

	function validar_resoluciones($datos)
	{
		foreach ($datos as $resolucion) {
			if ($resolucion['apex_ei_analisis_fila'] != comunes::estado_baja && !$this->validar_resoluciones_solapadas($resolucion, $datos)) {
				$this->set_error('800PRO_certificado_resolucion_solapado', array($resolucion['tipo']));
				break;
			}
		}
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_resoluciones_solapadas($resolucion, $datos)
	{
		foreach ($datos as $clave => $valor) {
			if ($valor['apex_ei_analisis_fila'] != comunes::estado_baja &&
				$resolucion['tipo'] == $valor['tipo'] &&
				$resolucion['ubicacion'] == $valor['ubicacion'] &&
				$resolucion[apex_datos_clave_fila] != $valor[apex_datos_clave_fila] &&
				guarani_fecha::rango_solapado_en_rango($resolucion['fecha_desde'], $resolucion['fecha_hasta'], $valor['fecha_desde'], $valor['fecha_hasta'])) {
				return false;
			}
		}
		return true;
	}
}
?>