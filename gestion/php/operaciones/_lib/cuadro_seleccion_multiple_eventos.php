<?php
class cuadro_seleccion_multiple_eventos extends cuadro_seleccion_multiple
{
    function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		// Si existe el evento 'seleccionar' y es múltiple, se alinea a izquierda de las columnas y se genera la utilidad "Todos/Ninguno".
		if ($this->existe_evento('seleccionar') && $this->evento('seleccionar')->es_seleccion_multiple()) {
			$this->evento('seleccionar')->set_alineacion_pre_columnas(true);
			if ($this->con_evt_todos_ninguno) {
				$this->set_manejador_salida('html', 'cuadro_seleccion_multiple_eventos_salida_html');
			}
		}
	}
}

class cuadro_seleccion_multiple_eventos_salida_html extends toba_ei_cuadro_salida_html
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
				<a href=\"javascript:$objeto_js.seleccionar_todos('seleccionar')\">Todos</a> / 
				<a href=\"javascript:$objeto_js.deseleccionar_todos('seleccionar')\">Ninguno</a>
			</div>
		";
	}
}

?>