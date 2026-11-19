<?php
/**
 * 
 * @package Encuestas
 */
class cn_ent_relevamiento extends cn_entidad
{
	function verificar_editabilidad()
	{
		$habilitacion = $this->get_columna_habilitacion('habilitacion');
		$fecha_desde = $this->get_columna_habilitacion('fecha_desde');
		if (toba::consulta_php('co_encuestas')->existe_respuesta($habilitacion) ||
				guarani_fecha::es_menor_igual_a_fecha_actual($fecha_desde)) {
			$this->bloquear_edicion();
		} else {
			$this->habilitar_edicion();
		}
	}

	function validar_editabilidad()
	{
	}

	function resetear_relacion()
	{
		// Eliminar las actividades, propuestas, responsables académicas y tipos de propuestas asociados.
		$this->eliminar_actividades();
		$this->eliminar_propuestas();
		$this->eliminar_responsables_academicas();
		$this->eliminar_tipos_propuestas();
	}

	//--------------------------------------------------------------------------
	//---- Habilitación
	//--------------------------------------------------------------------------

	function get_datos_habilitacion()
	{
		return $this->tabla('habilitacion')->get();
	}

	function get_columna_habilitacion($columna)
	{
		return $this->tabla('habilitacion')->get_columna($columna);
	}

	function set_datos_habilitacion($datos)
	{
		if (isset($datos['anios_academicos'])) {
			$this->set_datos_anios_academicos($datos['anios_academicos']);
			unset($datos['anios_academicos']);
		}
		if (isset($datos['tesis_estados'])) {
			$this->set_datos_tesis_estados($datos['tesis_estados']);
			unset($datos['tesis_estados']);
		}
		if (isset($datos['responsabilidades'])) {
			$this->set_datos_docentes_resp($datos['responsabilidades']);
			unset($datos['responsabilidades']);
		}
		if (isset($datos['certificados'])) {
			$this->set_datos_certificados($datos['certificados']);
			unset($datos['certificados']);
		}
		$this->validar_datos_habilitacion($datos);
		$this->tabla('habilitacion')->set($datos);
	}

	function modificar_columna_habilitacion($columna, $valor)
	{
		$this->tabla('habilitacion')->set_columna_valor($columna, $valor);
	}

	//--------------------------------------------------------------------------
	//---- Grupos
	//--------------------------------------------------------------------------

	function get_datos_grupo()
	{
		return $this->tabla('grupos')->get_filas();
	}

	function agregar_encuesta_grupo($datos_encuesta)
	{
		$this->tabla('grupos')->nueva_fila($datos_encuesta);
	}

	function eliminar_encuesta_grupo($id_fila_encuesta)
	{
		$this->tabla('grupos')->eliminar_fila($id_fila_encuesta);
	}

	function eliminar_encuestas_grupo()
	{
		$this->tabla('grupos')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Actividades
	//--------------------------------------------------------------------------

	function get_datos_actividades()
	{
		return $this->tabla('actividades')->get_filas();
	}

	function agregar_actividad($datos_actividad)
	{
		$this->tabla('actividades')->nueva_fila($datos_actividad);
	}

	function eliminar_actividad($id_fila_actividad)
	{
		$this->tabla('actividades')->eliminar_fila($id_fila_actividad);
	}

	function eliminar_actividades()
	{
		$this->tabla('actividades')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Propuestas
	//--------------------------------------------------------------------------

	function get_datos_propuestas()
	{
		return $this->tabla('propuestas')->get_filas();
	}

	function agregar_propuesta($datos_propuesta)
	{
		$this->tabla('propuestas')->nueva_fila($datos_propuesta);
	}

	function eliminar_propuesta($id_fila_propuesta)
	{
		$this->tabla('propuestas')->eliminar_fila($id_fila_propuesta);
	}

	function eliminar_propuestas()
	{
		$this->tabla('propuestas')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Responsables Académicas
	//--------------------------------------------------------------------------

	function get_datos_responsables_academicas()
	{
		return $this->tabla('responsables_academicas')->get_filas();
	}

	function set_datos_responsables_academicas($datos)
	{
		$ras = $this->tabla('responsables_academicas')->get_filas();
		$ras_aplanadas = aplanar_matriz($ras, 'responsable_academica');
		$ras_a_borrar = array_diff($ras_aplanadas, $datos);
		$ras_a_insertar = array_diff($datos, $ras_aplanadas);

		$ras_asociativas = rs_convertir_asociativo($ras, array('responsable_academica'), apex_datos_clave_fila);
		foreach ($ras_a_borrar as $ra) {
			$this->tabla('responsables_academicas')->eliminar_fila($ras_asociativas[$ra]);
		}

		foreach ($ras_a_insertar as $ra) {
			$this->tabla('responsables_academicas')->nueva_fila(array('responsable_academica' => $ra));
		}
	}

	function eliminar_responsables_academicas()
	{
		$this->tabla('responsables_academicas')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Tipos de Propuesta
	//--------------------------------------------------------------------------

	function get_datos_tipos_propuesta()
	{
		return $this->tabla('tipos_propuesta')->get_filas();
	}

	function set_datos_tipos_propuesta($datos)
	{
		$tipos_propuesta = $this->tabla('tipos_propuesta')->get_filas();
		$tp_aplanados = aplanar_matriz($tipos_propuesta, 'propuesta_tipo');
		$tp_a_borrar = array_diff($tp_aplanados, $datos);
		$tp_a_insertar = array_diff($datos, $tp_aplanados);

		$tp_asociativos = rs_convertir_asociativo($tipos_propuesta, array('propuesta_tipo'), apex_datos_clave_fila);
		foreach ($tp_a_borrar as $tp) {
			$this->tabla('tipos_propuesta')->eliminar_fila($tp_asociativos[$tp]);
		}

		foreach ($tp_a_insertar as $tp) {
			$this->tabla('tipos_propuesta')->nueva_fila(array('propuesta_tipo' => $tp));
		}
	}

	function eliminar_tipos_propuestas()
	{
		$this->tabla('tipos_propuesta')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Años Académicos
	//--------------------------------------------------------------------------

	function get_datos_anios_academicos()
	{
		return $this->tabla('anios_academicos')->get_filas();
	}

	function set_datos_anios_academicos($datos)
	{
		$anios_academicos = $this->tabla('anios_academicos')->get_filas();
		$aa_aplanados = aplanar_matriz($anios_academicos, 'anio_academico');
		$aa_a_borrar = array_diff($aa_aplanados, $datos);
		$aa_a_insertar = array_diff($datos, $aa_aplanados);

		$aa_asociativos = rs_convertir_asociativo($anios_academicos, array('anio_academico'), apex_datos_clave_fila);
		foreach ($aa_a_borrar as $aa) {
			$this->tabla('anios_academicos')->eliminar_fila($aa_asociativos[$aa]);
		}

		foreach ($aa_a_insertar as $aa) {
			$this->tabla('anios_academicos')->nueva_fila(array('anio_academico' => $aa));
		}
	}

	function eliminar_anios_academicos()
	{
		$this->tabla('anios_academicos')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Estados de Tesis
	//--------------------------------------------------------------------------

	function get_datos_tesis_estados()
	{
		return $this->tabla('tesis_estados')->get_filas();
	}

	function set_datos_tesis_estados($datos)
	{
		$tesis_estados = $this->tabla('tesis_estados')->get_filas();
		$estados_aplanados = aplanar_matriz($tesis_estados, 'estado');
		$estados_a_borrar = array_diff($estados_aplanados, $datos);
		$estados_a_insertar = array_diff($datos, $estados_aplanados);

		$estados_asociativos = rs_convertir_asociativo($tesis_estados, array('estado'), apex_datos_clave_fila);
		foreach ($estados_a_borrar as $estado) {
			$this->tabla('tesis_estados')->eliminar_fila($estados_asociativos[$estado]);
		}

		foreach ($estados_a_insertar as $estado) {
			$this->tabla('tesis_estados')->nueva_fila(array('estado' => $estado));
		}
	}

	function eliminar_tesis_estados()
	{
		$this->tabla('tesis_estados')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Modalidades
	//--------------------------------------------------------------------------

	function get_datos_modalidades()
	{
		return $this->tabla('modalidades')->get_filas();
	}

	function set_datos_modalidades($datos)
	{
		$this->tabla('modalidades')->procesar_filas($datos);
	}

	function eliminar_modalidades()
	{
		$this->tabla('modalidades')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Ubicaciones
	//--------------------------------------------------------------------------

	function get_datos_ubicaciones()
	{
		return $this->tabla('ubicaciones')->get_filas();
	}

	function set_datos_ubicaciones($datos)
	{
		$this->tabla('ubicaciones')->procesar_filas($datos);
	}

	function eliminar_ubicaciones()
	{
		$this->tabla('ubicaciones')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Roles Docentes
	//--------------------------------------------------------------------------

	function get_datos_docentes_resp()
	{
		return $this->tabla('docentes_resp')->get_filas();
	}

	function set_datos_docentes_resp($datos)
	{
		$responsabilidades = $this->tabla('docentes_resp')->get_filas();
		$resp_aplanadas = aplanar_matriz($responsabilidades, 'responsabilidad');
		$resp_a_borrar = array_diff($resp_aplanadas, $datos);
		$resp_a_insertar = array_diff($datos, $resp_aplanadas);

		$resp_asociativas = rs_convertir_asociativo($responsabilidades, array('responsabilidad'), apex_datos_clave_fila);
		foreach ($resp_a_borrar as $resp) {
			$this->tabla('docentes_resp')->eliminar_fila($resp_asociativas[$resp]);
		}

		foreach ($resp_a_insertar as $resp) {
			$this->tabla('docentes_resp')->nueva_fila(array('responsabilidad' => $resp));
		}
	}

	function eliminar_docentes_resp()
	{
		$this->tabla('docentes_resp')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Certificados
	//--------------------------------------------------------------------------

	function get_datos_certificados()
	{
		return $this->tabla('certificados')->get_filas();
	}

	function set_datos_certificados($datos)
	{
		$certificados = $this->tabla('certificados')->get_filas();
		$cert_aplanados = aplanar_matriz($certificados, 'certificado');
		$cert_a_borrar = array_diff($cert_aplanados, $datos);
		$cert_a_insertar = array_diff($datos, $cert_aplanados);

		$cert_asociativos = rs_convertir_asociativo($certificados, array('certificado'), apex_datos_clave_fila);
		foreach ($cert_a_borrar as $cert) {
			$this->tabla('certificados')->eliminar_fila($cert_asociativos[$cert]);
		}

		foreach ($cert_a_insertar as $cert) {
			$this->tabla('certificados')->nueva_fila(array('certificado' => $cert));
		}
	}

	function eliminar_certificados()
	{
		$this->tabla('certificados')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Requisitos
	//--------------------------------------------------------------------------

	function get_datos_requisitos()
	{
		return $this->tabla('requisitos')->get_filas();
	}

	function agregar_requisito($datos_requisito)
	{
		$this->tabla('requisitos')->nueva_fila($datos_requisito);
	}

	function eliminar_requisito($id_fila_requisito)
	{
		$this->tabla('requisitos')->eliminar_fila($id_fila_requisito);
	}

	function eliminar_requisitos()
	{
		$this->tabla('requisitos')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Validaciones
	//--------------------------------------------------------------------------

	function validar_datos_habilitacion($datos)
	{
		if (isset($datos['fecha_desde']) && isset($datos['fecha_hasta'])) {
			$this->validar_fechas_validez($datos['fecha_desde'], $datos['fecha_hasta']);
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_fechas_validez($fecha_desde, $fecha_hasta)
	{
		if (guarani_fecha::es_mayor_igual($fecha_desde, $fecha_hasta)) {
			$this->set_error('800_control_fechas', array('desde (de validez)', 'mayor o igual', 'hasta.'));
		}
	}

	function pre_sincronizar()
	{
		// En función del tipo elegido se valida la selección de encuestas.
		$tipo = $this->get_columna_habilitacion('tipo');
		switch ($tipo) {
			case encuesta::tipo_docente:
			case encuesta::tipo_pares:
				$datos = $this->get_datos_grupo();
				$encuesta = current($datos);
				if (guarani_arreglos::count($datos) != 1 || $encuesta['subtipo'] != encuesta::subtipo_docente) {
					$this->set_error('800ENC_tipo_docente');
				}
				break;
			case encuesta::tipo_academica:
				$datos = $this->get_datos_grupo();
				$encuesta = current($datos);
				if (guarani_arreglos::count($datos) != 1 || $encuesta['subtipo'] != encuesta::subtipo_academica) {
					$this->set_error('800ENC_tipo_academica');
				}
				break;
			case encuesta::tipo_docente_academica:
				$datos = $this->get_datos_grupo();
				if (guarani_arreglos::count($datos) != 2) {
					$this->set_error('800ENC_tipo_docente_academica');
				} else {
					$hay_encuesta_docente = false;
					$hay_encuesta_academica = false;
					foreach ($datos as $d) {
						if ($d['subtipo'] == encuesta::subtipo_docente) {
							$hay_encuesta_docente = true;
						}
						if ($d['subtipo'] == encuesta::subtipo_academica) {
							$hay_encuesta_academica = true;
						}
					}
					if (!($hay_encuesta_docente && $hay_encuesta_academica)) {
						$this->set_error('800ENC_tipo_docente_academica');
					}
				}
				break;
			case encuesta::tipo_generica:
				$datos = $this->get_datos_grupo();
				$encuesta = current($datos);
				if (guarani_arreglos::count($datos) != 1 || $encuesta['subtipo'] != encuesta::subtipo_generica) {
					$this->set_error('800ENC_tipo_generica');
				}
				break;
			case encuesta::tipo_egresados:
				$datos = $this->get_datos_grupo();
				$encuesta = current($datos);
				if (guarani_arreglos::count($datos) != 1 || $encuesta['subtipo'] != encuesta::subtipo_egresados) {
					$this->set_error('800ENC_tipo_egresados');
				}
				break;
		}

		// En función del alcance elegido se valida la selección de actividades, propuestas, responsables académicas o tipos de propuesta.
		$alcance = $this->get_columna_habilitacion('alcance');
		if (empty($alcance)) {
			$this->set_error('800ENC_falta_alcance');
		} else {
			switch ($alcance) {
				case encuesta::alcance_actividades:
					$datos = $this->get_datos_actividades();
					if (empty($datos)) {
						$etiqueta = guarani_etiquetas::get_tabla('sga_elementos');
						$this->set_error('800_no_seleccion', array($etiqueta->get('una')));
					}
					break;
				case encuesta::alcance_propuestas:
					$datos = $this->get_datos_propuestas();
					if (empty($datos)) {
						$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
						$this->set_error('800_no_seleccion', array($etiqueta->get('una')));
					}
					break;
				case encuesta::alcance_responsables_academicas:
					$datos = $this->get_datos_responsables_academicas();
					if (empty($datos)) {
						$etiqueta = guarani_etiquetas::get_tabla('sga_responsables_academicas');
						$this->set_error('800_no_seleccion', array($etiqueta->get('una')));
					}
					break;
				case encuesta::alcance_tipos_propuesta:
					$datos = $this->get_datos_tipos_propuesta();
					if (empty($datos)) {
						$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas_tipos');
						$this->set_error('800_no_seleccion', array($etiqueta->get('un')));
					}
					break;
			}
		}

		if ($tipo != encuesta::tipo_egresados) {
			$ubicaciones = $this->get_datos_ubicaciones();
			if (empty($ubicaciones)) {
				$etiqueta = guarani_etiquetas::get_tabla('sga_ubicaciones');
				$this->set_error('800_no_seleccion', array($etiqueta->get('una')));
			}
			$modalidades = $this->get_datos_modalidades();
			if (empty($modalidades)) {
				$etiqueta = guarani_etiquetas::get_tabla('sga_modalidad_cursada');
				$this->set_error('800_no_seleccion', array($etiqueta->get('una')));
			}
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
}
?>