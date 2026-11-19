<?php
class ci_nav_diploma_analitico_digital extends ci_nav_sin_listado_inicial
{

	function ini__operacion()
	{
        // Si la conexión con SICer es OK
		if (!sidcer::isConexionOk()) {
			throw guarani::error(guarani_mensajes::get('800_error_conexion_sidcer'));
		}

        // Si la conexión con Araí Documentos es OK
		if (!arai_documentos::isConexionOk()) {
			throw guarani::error(guarani_mensajes::get('800_error_conexion_arai_documentos'));
		}
	}

    function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);

			$this->ev_info->set_columnas_a_mostrar(['apellido_y_nombres', 'documento_principal', 'certificado_nombre', 'nro_expediente_descr']);
			$this->ev_info->set_max_columnas_por_fila(2);
			// Fila 1
			$this->ev_info->set_columnas_fila(['apellido_y_nombres', 'documento_principal'], 1);
			// Fila 2
			$this->ev_info->set_columnas_fila(['certificado_nombre', 'nro_expediente_descr'], 2);			
			$this->ev_info->set_etiqueta('apellido_y_nombres', 'Alumno:');
			$this->ev_info->set_etiqueta('documento_principal', 'Documento:');
			$this->ev_info->set_etiqueta('certificado_nombre', 'Certificación:');
			$this->ev_info->set_etiqueta('nro_expediente_descr', 'Expediente:');
		}
		return $this->ev_info;
	}
    
    function get_msg_eof($dep=null)
	{
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro) && ! empty($this->s__filtro)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		//$etiqueta = $this->cn()->etiquetas($dep)->get_enf('plural');
        $etiqueta = 'Certificados';
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}
    
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
        $this->cn()->setNroSolicitud($seleccion);
        $this->set_pantalla('edicion');
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
        $this->ev_info()->set_datos($this->cn()->getTramite());
	}

}