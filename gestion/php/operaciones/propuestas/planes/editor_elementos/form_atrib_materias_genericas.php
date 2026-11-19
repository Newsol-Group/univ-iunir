<?php
class form_atrib_materias_genericas extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$encabezado = array(
			'nombre',
			'nombre_abreviado',
			'codigo'
		);
		$cuerpo1 = array(
			'origen_creditos',
			'creditos'
		);
		$cuerpo2 = array(
			'anio_de_cursada',
			'periodo_de_cursada',
			'coeficiente',
			'sale_listado'
		);

		echo $this->get_html_alineado($encabezado, 1, '130px');
		echo "<hr/>";
		echo $this->get_html_alineado($cuerpo1, 1);
		echo "<hr/>";
		echo $this->get_html_alineado($cuerpo2, 2, '110px');
	}

	function extender_objeto_js()
	{
		// Cuando se activa y desactiva el nodo que contiene el input de creditos se usa el nombre _t ya que ahora se imprimien en div separados
		// el este nombre se pone cuando se arma el formulario en guarani_ei_formulario
		echo "

		{$this->objeto_js}.evt__origen_creditos__procesar = function(es_inicial)
		{
			var oc = this.ef('origen_creditos').get_estado();
			if( oc == 'M') {
				//this.ef('creditos').set_estado('0');
				this.ef('creditos').mostrar();
				nodo = this.ef('creditos').nodo();
				nodo_id = nodo.getAttribute('id');
				nodo = document.getElementById(nodo_id+'_t');
				if (nodo != null) {
					nodo.style.display = 'inline';
				}
			} else {
				this.ef('creditos').ocultar();
				this.ef('creditos').set_estado(null);
				nodo = this.ef('creditos').nodo();
				nodo_id = nodo.getAttribute('id');
				nodo = document.getElementById(nodo_id+'_t');
				if (nodo != null) {
					nodo.style.display = 'none';
				}
			}
		}

		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{

			// Coeficiente
		    var coeficiente = this.ef('coeficiente').get_estado();
			if (isset(coeficiente) && coeficiente < 0) {
				notificacion.agregar('Debe ingresar un Coeficiente Mayor o Igual a Cero');
				return false;
			}
		return true
		}
		";
	}

}
?>