<?php
class ci_visualizador_inscripciones extends guarani_pers_ci
{
	protected $info_alumno;
	protected $ev_info;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		$this->info_alumno = toba::memoria()->get_dato('info_alumno');
		//toba::memoria()->eliminar_dato('info_alumno');

		if (! isset($this->info_alumno['alumno'])) {
			return;
		}

		$this->ev_info()->set_datos($this->info_alumno);
		if (isset($this->info_alumno['titulo'])) {
			$this->ev_info()->set_titulo($this->get_titulo());
			$this->set_titulo($this->info_alumno['titulo']);
		}
	}

	function ev_info()
	{
		if (! isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre','documento_principal','legajo','ubicacion_nombre'));
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}

	function vista_xml($inicial=false, $xmlns=null)
	{
		if ($xmlns) {
			$this->xml_set_ns($xmlns);
		}
		$xml = $this->xml_get_tag_inicio();

		$xml .= $this->ev_info()->vista_xml();

		foreach ($this->get_dependencias() as $dep)
		{
			if(method_exists($dep, 'vista_xml')) {
				$xml .= $dep->vista_xml(false,$xmlns);
			}
		}

		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_docentes --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_inscripciones(toba_ei_cuadro $cuadro)
	{
		if (isset($this->info_alumno['alumno'])) {
			$inscripciones = toba::consulta_php('co_inscripciones_examenes')->get_insc_examen_vigentes_alumno($this->info_alumno['alumno']);
			$cuadro->set_datos($inscripciones);
		}
		$msg_eof = guarani_mensajes::get('800_eof_cuadro', array('inscripciones vigentes del alumno'));
		$cuadro->set_eof_mensaje($msg_eof);
	}

	//-----------------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__cerrar = function()	{
			window.close();
		}
		";
	}

}

?>