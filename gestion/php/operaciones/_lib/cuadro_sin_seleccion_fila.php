<?php
class cuadro_sin_seleccion_fila extends cuadro_seleccion_multiple
{
	protected $con_evt_todos_ninguno = true;
	
	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
        $this->set_manejador_salida('html', 'cuadro_sin_seleccion_fila_salida_html');
	}
}

class cuadro_sin_seleccion_fila_salida_html extends cuadro_seleccion_multiple_salida_html
{
    /*
     * Se sobreescribe este metodo para que no se envie el evento a nivel fila.
     */
	protected function get_invocacion_js_eventos_multiples($evt_multiples, $id_fila, $objeto_js)
    {
        return '';
    }
}
?>