<?php
class ci_seleccion_actividad extends guarani_pers_ci
{
	protected $s__lista_actividades;
	protected $s__plan_version;
	protected $s__alumno;
	
	function ini()
	{
		$this->s__lista_actividades = toba::memoria()->get_dato('lista_actividades');
		$this->s__plan_version = toba::memoria()->get_dato('plan_version');
		$this->s__alumno = toba::memoria()->get_dato('alumno');
	}

	function get_datos($filtro)
	{
		if (isset($this->s__plan_version)) {
			if (isset($this->s__alumno)) {
				// Se excluyen las actividades ya aprobadas por el alumno.
				$filtro_alumno = "sga_elementos.elemento	NOT IN (	SELECT	vw_hist_academica.elemento
																		FROM	vw_hist_academica
																		WHERE	vw_hist_academica.alumno = $this->s__alumno
																		AND		vw_hist_academica.resultado = 'A'
																	)";
				$filtro = (isset($filtro) && trim($filtro) != '') ? $filtro . ' AND ' . $filtro_alumno : $filtro_alumno;
			}
			$datos = toba::consulta_php('co_elementos')->get_actividades_plan_version($this->s__plan_version, $filtro);
		} else {
			if (isset($this->s__lista_actividades)) {
				$filtro_actividades = guarani_sql::armar_condicion_in($this->s__lista_actividades, 'sga_elementos', 'elemento');
				$filtro = ($filtro == '') ? $filtro_actividades : $filtro . 'AND ' . $filtro_actividades;
			}
			$datos = toba::consulta_php('co_elementos')->get_listado_actividades($filtro);
		}
		return $datos;
	} 	
	
	//---- filtro_alumnos -------------------------------------------------------------------

	function conf__filtro(guarani_ei_filtro $filtro)
	{
		$etiquetas = guarani_etiquetas::get_tabla('sga_elementos');
		$filtro->set_etiquetas($etiquetas);
		$filtro->set_sin_filtrado_inicial();
		$this->filtro_configurar('filtro', false);
	}

	function evt__filtro__filtrar($datos)
	{
		$this->filtro_filtrar('filtro', $datos);
	}

	function evt__filtro__cancelar()
	{
		$this->filtro_limpiar('filtro');
	}

	//---- cuadro_alumnos -------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();

		$filtro = $this->get_filtro('filtro');
		if (!is_null($filtro)) {
			$datos = $this->get_datos($filtro);
			$cuadro->set_datos($datos);
			
			$etiquetas = guarani_etiquetas::get_tabla('sga_elementos');
			$etiqueta = $etiquetas->get_enf('plural');
			$mje = guarani_mensajes::get('800_eof_cuadro_filtrado', array($etiqueta));
		} else {
			$mje = guarani_mensajes::get('800_eof_cuadro_sinfiltro');
		}

		$cuadro->set_eof_mensaje($mje);
	}

}
?>