<?php
class cuadro_seleccion_diferida extends guarani_pers_ei_cuadro
{
	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		// Si existe el evento 'seleccion' y es de disparo diferido, se alinea a izquierda de las columnas y se genera la utilidad "Todos/Ninguno".
		if ($this->existe_evento('seleccion')) {
			$this->evento('seleccion')->set_disparo_diferido(true);
			$this->evento('seleccion')->set_alineacion_pre_columnas(true);
			$this->set_manejador_salida('html', 'cuadro_seleccion_diferida_salida_html');
		}
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

class cuadro_seleccion_diferida_salida_html extends toba_ei_cuadro_salida_html
{
	function get_estilo_seleccion($clave_fila)
	{
		return 'ei-cuadro-fila';
	}
}
?>