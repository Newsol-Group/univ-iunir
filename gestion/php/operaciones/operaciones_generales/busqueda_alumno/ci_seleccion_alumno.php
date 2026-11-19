<?php
class ci_seleccion_alumno extends guarani_pers_ci
{

	/*
	 * Devuelve los alumnos activos que son potenciales
	 */
	function get_alumnos_activos($filtro=null)
	{
		$where = " 	sga_alumnos.calidad = '".alumno::calidad_activo."'
				 	AND		sga_planes_versiones.estado IN ('".planes::estado_vigente."','".planes::estado_activo."')
					";
		if ($filtro) {
			$where .= "	AND $filtro";
		}
		return guarani::co('co_alumnos')->get_listado($where);
	}
	
	
	//---- filtro_alumnos -------------------------------------------------------------------

	function conf__filtro_alumnos(guarani_ei_filtro $filtro)
	{
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$filtro->set_ubicacion_obligatoria();
		}
		$filtro->set_sin_filtrado_inicial();
		$this->filtro_configurar('filtro_alumnos', false);
	}

	function evt__filtro_alumnos__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_alumnos', $datos);
	}

	function evt__filtro_alumnos__cancelar()
	{
		$this->filtro_limpiar('filtro_alumnos');
	}

	//---- cuadro_alumnos -------------------------------------------------------------------

	function conf__cuadro_alumnos(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		$filtro = $this->get_filtro('filtro_alumnos');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro_sinfiltro'));

		if ( (isset($filtro) && !empty($filtro) && $filtro != '1=1') || $this->dep('filtro_alumnos')->con_filtrado_inicial() ) {
			$datos = $this->get_alumnos_activos($filtro);
			$cuadro->set_datos($datos);
			if (empty($datos)) {
				$etiquetas = guarani_etiquetas::get_tabla('sga_alumnos');
				$etiqueta = $etiquetas->get_enf('plural');
				$mje = guarani_mensajes::get('800_eof_cuadro_filtrado', array($etiqueta));
				$cuadro->set_eof_mensaje($mje);
			}
		}
	}

}
?>