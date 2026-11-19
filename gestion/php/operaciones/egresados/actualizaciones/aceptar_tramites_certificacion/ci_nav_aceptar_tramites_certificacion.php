<?php
/**
 * 
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 */
class ci_nav_aceptar_tramites_certificacion extends ci_navegacion
{
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		$filtro->columna('estado_requisitos')->set_condicion_fija('es_igual_a');
		if (empty($this->s__datos['estado_requisitos'])) {
			$filtro->columna('estado_requisitos')->set_estado(array('valor' => tramite_certificacion::estado_requisitos_pendiente));
		}
		parent::conf__filtro($filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof = '800_eof_cuadro_sinfiltro';
		} else {
			$where = empty($this->get_filtro_condicion()) ? 'true' : $this->get_filtro_condicion();
			$where .= ' AND sga_certificados_otorg.anulado = 0';
			$tramites = toba::consulta_php('co_tramites_certificacion')->get_listado($where);
			foreach ($tramites as $clave => $valor) {
				// Obtiene la descripción del Trámite de Certificación desde SUDOCU.
				$tramites[$clave]['nro_expediente'] = sudocu::getDescripcionTramiteCertificacion($valor);
			}
			$cuadro->set_datos($tramites);
		}
		$etiqueta = guarani_etiquetas::get_tabla('sga_certificados_otorg');
		$indx_msj = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));
		$cuadro->set_eof_mensaje($indx_msj);
	}

	function conf_evt__cuadro__administrar_requisitos(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro')->get_datos();
		// Sólo pueden administrarse los requisitos para trámites de solicitud de certificación en estado "Pendiente".
		if ($datos[$fila]['estado_requisitos'] == tramite_certificacion::estado_requisitos_pendiente) {
			$evento->mostrar();
			$evento->vinculo()->set_parametros(array('persona' => $datos[$fila]['persona'], 'operacion_a_volver' => guarani::get_id_operacion_toba(), 'celda_a_restaurar' => toba::memoria()->get_celda_memoria_actual_id()));
			$evento->vinculo()->set_celda_memoria('celda_administrar_requisitos');
		} else {
			$evento->ocultar();
		}
	}

	function conf_evt__cuadro__aceptar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro')->get_datos();
		// Sólo pueden aceptarse los trámites de solicitud de certificación en estado "Pendiente".
		if ($datos[$fila]['estado_requisitos'] == tramite_certificacion::estado_requisitos_pendiente) {
			$evento->mostrar();
		} else {
			$evento->ocultar();
		}
	}

	function evt__cuadro__aceptar($seleccion)
	{
		if (toba::consulta_php('co_tramites_certificacion')->cumple_requisitos_egreso($seleccion['nro_solicitud'])) {
			guarani::act('act_tramites_certificacion')->actualizar_estado_requisitos($seleccion['nro_solicitud'], tramite_certificacion::estado_requisitos_aceptado);
		} else {
			throw guarani::error(guarani_mensajes::get('800EGR_aceptar_requisitos_pendientes'));
		}
	}

	function conf_evt__cuadro__devolver(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro')->get_datos();
		// Sólo pueden devolverse los trámites de solicitud de certificación en estado "Pendiente".
		if ($datos[$fila]['estado_requisitos'] == tramite_certificacion::estado_requisitos_pendiente && guarani::parametros()->get_valor('usa_repositorio_digital') == comunes::si) {
			$evento->mostrar();
		} else {
			$evento->ocultar();
		}
	}

	function evt__cuadro__devolver($seleccion)
	{
		guarani::act('act_tramites_certificacion')->actualizar_estado_requisitos($seleccion['nro_solicitud'], tramite_certificacion::estado_requisitos_abierto);
		$datos_persona = current(toba::consulta_php('co_personas')->get_datos_persona_mail($seleccion['persona']));
		if (!empty(trim($datos_persona['email']))) {
			$parametros['persona'] = $seleccion['persona'];
			$parametros['email'] = array($seleccion['persona'] => array('email' => $datos_persona['email']));
			$parametros['nombre_alumno'] = $datos_persona['nombre_completo'];
			$parametros['certificado_nombre'] = $seleccion['certificado_nombre'];
			guarani::notificador()->enviar(notificador::notificar_reapertura_tramite_egreso, $parametros);
		}
	}
}