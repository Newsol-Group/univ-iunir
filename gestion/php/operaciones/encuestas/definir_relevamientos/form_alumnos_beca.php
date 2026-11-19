<?php
/**
 * 
 * @package Encuestas
 */
class form_alumnos_beca extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table width='100%'><tr><td>";
		$this->generar_html_ef('alumnos');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('label_responder');
		echo "</td></tr></table><table><tr><td>";
		$this->generar_html_ef('edad');
		echo '</td><td>';
		$this->generar_html_ef('edad_desde', '0px');
		echo '</td><td>';
		$this->generar_html_ef('edad_hasta', '10px');
		echo "</td></tr></table><table width='100%'><tr><td>";
		$this->generar_html_ef('trabaja');
		echo '</td><td>';
		$this->generar_html_ef('tiene_hijos', '155px');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('alquila');
		echo '</td><td>';
		$this->generar_html_ef('tiene_cobertura_de_salud', '155px');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('vive_cursa_misma_localidad', '240px');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('anios_academicos', '180px');
		echo '</td></tr></table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$alumnos_todos = encuesta::alumnos_todos;
		$mayor = comunes::mayor;
		$entre = comunes::entre;

		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__alumnos__procesar = function(es_inicial)
		{
			if (this.ef('alumnos').get_estado() == '$alumnos_todos') {
				this.ef('label_responder').ocultar();
				this.ef('edad').ocultar(true);
				this.ef('edad_desde').ocultar(true);
				this.ef('edad_hasta').ocultar(true);
				this.ef('trabaja').ocultar(true);
				this.ef('tiene_hijos').ocultar(true);
				this.ef('tiene_cobertura_de_salud').ocultar(true);
				this.ef('alquila').ocultar(true);
				this.ef('vive_cursa_misma_localidad').ocultar(true);
				this.ef('anios_academicos').ocultar(true);
			} else {
				this.ef('label_responder').mostrar();
				this.ef('edad').mostrar();
				this.ef('trabaja').mostrar();
				this.ef('tiene_hijos').mostrar();
				this.ef('tiene_cobertura_de_salud').mostrar();
				this.ef('alquila').mostrar();
				this.ef('vive_cursa_misma_localidad').mostrar();
				this.ef('anios_academicos').mostrar();
			}
		}

		{$this->objeto_js}.evt__edad__procesar = function(es_inicial)
		{
			var edad = this.ef('edad').get_estado();
			var mostrar_edad_desde = false;
			var mostrar_edad_hasta = false;
			if (this.ef('edad').tiene_estado()) {
				mostrar_edad_desde = true;
				if (edad == '$entre') {
					mostrar_edad_hasta = true;
				}
			}
			this.ef('edad_desde').mostrar(mostrar_edad_desde, !mostrar_edad_desde);
			this.ef('edad_hasta').mostrar(mostrar_edad_hasta, !mostrar_edad_hasta);
		}
		";
	}
}
?>