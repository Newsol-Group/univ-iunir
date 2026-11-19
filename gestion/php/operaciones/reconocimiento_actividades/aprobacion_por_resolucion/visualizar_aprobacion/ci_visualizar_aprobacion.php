<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Aprobación por Resolución
 */
class ci_visualizar_aprobacion extends guarani_pers_ci
{
	protected $info_tramite;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		toba::memoria()->desactivar_reciclado();
		$this->info_tramite = toba::memoria()->get_parametros();
		if (!isset($this->info_tramite['nro_tramite'])) {
			return;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_documentos ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_documentos(toba_ei_cuadro $cuadro)
	{
		if (isset($this->info_tramite['nro_tramite'])) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_reconocimiento_doc');
			$msg_eof = guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural')));
			$cuadro->set_eof_mensaje($msg_eof);

			$actividades = toba::consulta_php('co_aprobacion_por_resolucion')->get_actividades_tramite_aprobacion($this->info_tramite['nro_tramite']);
			$cuadro->set_datos($actividades);
		}
	}

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