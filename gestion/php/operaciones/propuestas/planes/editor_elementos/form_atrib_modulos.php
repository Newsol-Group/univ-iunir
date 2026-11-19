<?php
class form_atrib_modulos extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$encabezado = array(
			'nombre',
			'nombre_abreviado'
		);
		$cuerpo1 = array(
			'origen_creditos',
			'creditos'
		);

		echo $this->get_html_alineado($encabezado, 1, '150px');
		echo "<hr/>";
		echo $this->get_html_alineado($cuerpo1, 1, '150px');
	}

	function extender_objeto_js()
	{
		// Cuando se activa y desactiva el nodo que contiene el input de creditos, 
		// se usa el nombre _t ya que ahora se imprimien en div separados. Este 
		// nombre se pone cuando se arma el formulario en guarani_ei_formulario
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
		
		{$this->objeto_js}.mostrar_columna_puntaje = function(mostrar)
		{
			var form = this.controlador.dep('form_orden');
			if(form) {
				form.mostrar_puntaje(mostrar);
			}
		}
		

		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			// Control del origen de creditos.
			var oc = this.ef('origen_creditos').get_estado();
		    if (oc == 'M' ) {
				var otorgados = this.ef('creditos').get_estado();
				if (otorgados == null || otorgados <= 0 ) {
					notificacion.agregar('Debe ingresar la cantidad de créditos Mayor Cero');
					return false;
				}			
			}			
			return true;
		}
		";
	}

}
?>