<?php
class ci_rep_insc_propuesta_y_cursada_nsg extends ci_rep_sin_listado_inicial
{
	/*protected $conf_servicios_excluidos = array('cascadas_efs', 'filtrado_ef_ce', 'cascadas_columnas');
	protected $s__seleccion;
	protected $s__imprimir = false;
				function conf()
	{
		$datos = $this->cuadro()->get_datos();
		if (empty($datos)) {
			if ($this->pantalla()->existe_evento('pdf')) {
				$this->pantalla()->eliminar_evento('pdf');
			}
			if ($this->pantalla()->existe_evento('pdf2')) {
				$this->pantalla()->eliminar_evento('pdf2');
			}
		}
	}*/

	function get_tabla_principal()
	{
		return 'sga_propuestas_aspira';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_inscripciones')->get_inscriptos_propuestas_y_cursadas($filtro);
	}

	/*function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$servicio = toba::memoria()->get_servicio_solicitado();
		if (!in_array($servicio, $this->conf_servicios_excluidos)) {
			parent::conf__cuadro($cuadro);
		}
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_clave_seleccionada($this->dep('cuadro')->get_clave_fila($fila, true)));
	}

	function es_clave_seleccionada($clave_fila)
	{
		$claves = array();
		if (isset($this->s__seleccion)) {
			foreach ($this->s__seleccion as $valor) {
				$claves[] = implode(apex_qs_separador, $valor);
			}
		}
		return in_array($clave_fila, $claves);
	}

	function vista_jasperreports(toba_vista_jasperreports $report)
	{
		parent::vista_jasperreports($report);
		
	}

	function evt__pdf()
	{
		$this->s__imprimir = true;
		$this->s__preinscripcion = 0;
	}

	function evt__pdf2()
	{
		$this->s__imprimir = true;
		$this->s__preinscripcion = 1;
	}


	function get_descripcion($aguja, $pajar)
	{
		$descripcion = '-';
		foreach ($pajar as $p) {
			if ($p['valor'] == $aguja) {
				$descripcion = $p['descr'];
			}
		}
		return $descripcion;
	}

	function get_si_no($valor)
	{
		if ($valor == NULL) {
			return '-';
		}
		switch ($valor) {
			case comunes::si: return comunes::si_desc;
				break;
			case comunes::no: return comunes::no_desc;
				break;
			default: return '-';
		}
	}

	function adecuar_null($array)
	{
		foreach ($array as $clave => $elemento) {
			if ($elemento == NULL) {
				$array[$clave] = '-';
			}
		}
		return $array;
	}*/

}
?>