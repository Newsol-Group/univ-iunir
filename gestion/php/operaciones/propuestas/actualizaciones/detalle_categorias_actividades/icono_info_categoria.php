<?php
class icono_info_categoria implements toba_ef_icono_utileria
{
	protected $ef_referencia;

	/**
	 * Setea el ef del cual saca el estado para generar el vinculo
	 * 
	 * @param <type> $ef
	 */
	function set_ef_referencia($ef)
	{
		$this->ef_referencia = $ef;
	}

	function get_html(toba_ef $ef)
	{
		$ef_referencia = $ef;
		if ($this->ef_referencia) {
			$ef_referencia = $ef->controlador()->ef($this->ef_referencia);
		}
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '41000064', array(), array('celda_memoria'=>'categoria'));
		$javascript = "if ({$ef_referencia->objeto_js()}.get_estado() != 'nopar') {
							abrir_popup('info','$vinculo'+'&categoria='+{$ef_referencia->objeto_js()}.get_estado(),{'width':'450','height':'420'})
					   } else {
					   		alert('No ha seleccionado ninguna Categoría.');
					   }"; 

		$salida = "<a class='icono-utileria' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver detalle de Categoría');
		$salida .= '</a>';

		return $salida;
	}
}
?>