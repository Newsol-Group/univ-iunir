<?php
class ci_seleccion_convenio extends guarani_ci
{
	protected $s__filtro;
	protected $s__datos;
	protected $s__propuesta;

	
	function ini()
	{
		// El ef popup Convenio es esclavo del ef Propuesta y la recibe como parámetro.
		$propuesta = toba::memoria()->get_parametro('propuesta');
		if (! empty($propuesta)) {
			$this->s__propuesta = $propuesta;
		}
	}
	
	function get_datos($filtro='')
	{
		$filtro = empty($filtro) ? 'true' : $filtro;
		if (isset($this->s__propuesta)) {
			$filtro .= " AND sga_convenios.propuesta_base = {$this->s__propuesta}";
		}
		return toba::consulta_php('co_convenios')->get_listado($filtro);
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_convenios(toba_ei_filtro $filtro)
	{
		$filtro->columna('institucion')->set_condicion_fija('es_igual_a');
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
	}

	function evt__filtro_convenios__filtrar($datos)
	{
		if (isset($datos)&& !empty($datos)) {
			$this->s__datos = $datos;
			$filtro_institucion = '';
			if (isset($datos['institucion'])) {
				$filtro_institucion .= " AND EXISTS (SELECT * FROM sga_convenios_instituciones WHERE sga_convenios_instituciones.convenio = sga_convenios.convenio AND sga_convenios_instituciones.institucion = '{$datos['institucion']['valor']}')";
				$this->dep('filtro')->columna('institucion')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro_convenios')->get_sql_where()} $filtro_institucion";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro_convenios__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_convenios(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		/**
 		 * Si no está seteado el filtro, entonces es la carga inicial del cuadro, no se ejecuta la consulta y se 
		 * indica al usuario que debe filtrar para obtener datos.
		*/
		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
			$this->indx_msj_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->get_datos($this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$etiqueta = guarani_etiquetas::get_tabla('sga_convenios');
		$etiq = $etiqueta->get_enf('plural') . " asociados a la Propuesta seleccionada"; 
		$msj_eof = guarani_mensajes::get($this->indx_msj_eof, array($etiq));
		$cuadro->set_eof_mensaje($msj_eof);
	}
}
?>