<?php
class cn_ent_docente_nsg extends cn_ent_docente
{
	function set_datos_docente($datos)
	{
		$this->validar_datos_docente($datos);
		$this->tabla('docente')->set($datos);
	}

	function get_datos_docente()
	{
		return $this->tabla('docente')->get();
	}

	function persona_es_docente()
	{
		$docente = $this->tabla('docente')->get();
		return (!empty($docente));
	}

	//---------------------------------------------------------------------

	function get_datos_docente_resp()
	{
		return $this->tabla('docente_resp')->get_filas();
	}

	function set_datos_docente_resp($datos)
	{
		$docente_resp = $this->tabla('docente_resp')->get_filas();
		
		$docente_resp_aplanadas = aplanar_matriz($docente_resp, 'responsabilidad');
		$docente_resp_a_borrar = array_diff($docente_resp_aplanadas, $datos);
		$docente_resp_a_insertar = array_diff($datos, $docente_resp_aplanadas);
	
		$docente_resp_asociativas = rs_convertir_asociativo($docente_resp, array('responsabilidad'), apex_datos_clave_fila);
		foreach ($docente_resp_a_borrar as $docente_resp) {
			$this->tabla('docente_resp')->eliminar_fila($docente_resp_asociativas[$docente_resp]);
		}

		$sql = "select date(now()) as fecha";
		$fecha_desde = guarani_db::consultar_fila($sql, null, false);

		foreach ($docente_resp_a_insertar as $docente_resp) {
			$this->tabla('docente_resp')->nueva_fila(array('responsabilidad' => $docente_resp, 'fecha_desde' => $fecha_desde['fecha']));
		}
	}
}