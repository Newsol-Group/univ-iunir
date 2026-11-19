<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Detalle Escala Notas
 */
class ci_info_escala_notas extends guarani_pers_ci
{
	//Escala de notas a mostrar
	protected $s__escala;

	//Elemento Visual de Información
	protected $ev_info_escala_notas;
	
	function ini()
	{
		$parametro_escala = toba::memoria()->get_parametro('escala');
		if (!empty($parametro_escala)) {
			$this->s__escala = $parametro_escala;
		}
	}

	//---- Configuraciones --------------------------------------------------------------
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if (!empty($this->s__escala) && $this->s__escala != apex_ef_no_seteado) {
			$datos_escala = toba::consulta_php('co_escalas_notas')->get_info_escalas_notas($this->s__escala);
			$this->controlador()->ev_info_escala_notas()->set_datos(current($datos_escala));
		}
	}
	
	//---- Cuadro datos escala ----------------------------------------------------------
	
	function conf__cuadro_datos_escala(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_usar_ordenamiento_con_cortes();
		if (!empty($this->s__escala) && $this->s__escala != apex_ef_no_seteado) {
			$detalle_escala = toba::consulta_php('co_escalas_notas')->get_detalle_escala_notas($this->s__escala);
			$cuadro->set_datos(rs_ordenar_por_columna($detalle_escala, 'nota'));
		}
	}
	
	//---- Auxiliares -------------------------------------------------------------------
	
	function ev_info_escala_notas()
	{
		if (!isset($this->ev_info_escala_notas)) {
			$this->ev_info_escala_notas = guarani::ev_info_datos();
			$this->ev_info_escala_notas->set_controlador($this);
			$this->ev_info_escala_notas->set_style('width: 80%;');
			$this->ev_info_escala_notas->set_columnas_a_mostrar(array('nombre', 'descripcion'));
			$this->ev_info_escala_notas->set_etiqueta('nombre', 'Escala');
			$this->ev_info_escala_notas->set_etiqueta('descripcion', 'Descripción');
		}
		return $this->ev_info_escala_notas;
	}
	
	//---- Extensión Javascript ---------------------------------------------------------

	function extender_objeto_js()
	{
		//Se recupera el id en javascript del cuadro para atender su evento Cerrar, y provocar que se cierre la ventana popup.
		$id_js_cuadro = $this->dependencia('cuadro_datos_escala')->get_id_objeto_js();
		
		echo "
			{$id_js_cuadro}.evt__cerrar = function() {
				window.close();
			}
		";
	}
	
}
?>