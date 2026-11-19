<?php
class cuadro_seleccion_multiple extends guarani_pers_ei_cuadro
{
	protected $con_evt_todos_ninguno = true;
	
	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		// Si existe el evento 'seleccion' y es múltiple, se alinea a izquierda de las columnas y se genera la utilidad "Todos/Ninguno".
		if ($this->existe_evento('seleccion') && $this->evento('seleccion')->es_seleccion_multiple()) {
			$this->evento('seleccion')->set_alineacion_pre_columnas(true);
			if ($this->con_evt_todos_ninguno) {
				$this->set_manejador_salida('html', 'cuadro_seleccion_multiple_salida_html');
			}
		}
	}

	function set_sin_evt_todos_ninguno()
	{
		$this->con_evt_todos_ninguno = false;
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Se redefine el método set_evento para que no marque con un estilo diferente las filas chequeadas.
	 */
	function extender_objeto_js()
	{
		echo "
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.set_evento = function(evento, hacer_submit, input)
		{
			if (typeof hacer_submit == 'undefined') {
				hacer_submit = true;
			}
			ei.prototype.set_evento.call(this, evento, hacer_submit);
		}
		";
	}
}

class cuadro_seleccion_multiple_salida_html extends toba_ei_cuadro_salida_html
{
	function html_cabecera()
	{
		parent::html_cabecera();
		
		$info_cuadro = $this->_cuadro->get_informacion_basica_cuadro();
		if ((isset($info_cuadro) && $info_cuadro['exportar_pdf'] == 1) ||
			(isset($info_cuadro) && $info_cuadro['exportar_xls'] == 1) ||
			($info_cuadro["ordenar"])) {
			echo " <br> <br> ";
		}
				
		$objeto_js = $this->_cuadro->get_id_objeto_js();
		
		echo "
			<div class= 'ef-multi-sel-todos'>
				<a href=\"javascript:$objeto_js.seleccionar_todos('seleccion')\">Todos</a> / 
				<a href=\"javascript:$objeto_js.deseleccionar_todos('seleccion')\">Ninguno</a>
			</div>
		";
	}
}
?>