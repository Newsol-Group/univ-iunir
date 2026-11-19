<?php
/**
	*
	*
	* @package        Matriculas
	* @subpackage    Personas
	* 
	*/
class form_allegados extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('apellido');
		$this->generar_html_ef('nombres');
		$this->generar_html_ef('tipo_allegado');
		echo " <table width='100%'>
				<tbody>
					<tr> 
						<td colspan='1'>
							";  $this->generar_html_ef('nacionalidad', '90px'); echo "
						</td>
							<td colspan='1'>
							";  $this->generar_html_ef('email', '90px'); echo "
						</td>
					</tr>
				</tbody>
			</table>
								
								<table width='100%'>
				<tbody>
					<tr> 
						<td colspan='1'>
							";  $this->generar_html_ef('pais_documento', '90px'); echo "
						</td>
							<td colspan='1'>
							";  $this->generar_html_ef('tipo_documento', '90px'); echo "
						</td>
						<td colspan='1'>
							";  $this->generar_html_ef('nro_documento', '100px'); echo "
						</td>
					</tr>
				</tbody>
			</table>";
		$this->generar_html_ef('observaciones');

		echo "
		<fieldset>
			<legend><strong>Dirección</strong></legend>
			<table width='100%'>
				<tbody>
					<tr>
						<td colspan='4'>
							"; $this->generar_html_ef('calle', '90px'); echo "
						</td>
					</tr>
					<tr>
						<td>
							";  $this->generar_html_ef('numero', '90px'); echo "
						</td>
						<td>
							";  $this->generar_html_ef('piso', '50px'); echo "
						</td>
						<td>
							";  $this->generar_html_ef('departamento', '50px'); echo "
						</td>
						<td>
							";  $this->generar_html_ef('unidad', '50px'); echo "
						</td>
					</tr>
				</tbody>
			</table>
			<table width='100%'>
				<tbody>
					<tr>
						<td colspan='2'>
							";  $this->generar_html_ef('barrio', '90px'); echo "
						</td>
					</tr>
					<tr>
						<td>
							";  $this->generar_html_ef('localidad', '90px'); echo "
						</td>
						<td>
							";  $this->generar_html_ef('codigo_postal'); echo "
						</td>
					</tr>
					<tr>
						<td colspan='2'>
							";  $this->generar_html_ef('telefono', '90px'); echo "
						</td>
					</tr>
				</tbody>
			</table>
		</fieldset>";
		
		$this->generar_html_ef('estudio_alcanzado');
		$this->generar_html_ef('trabajo_existe');
		$this->generar_html_ef('trabajo_hace');
		$this->generar_html_ef('trabajo_ocupacion');
		$this->generar_html_ef('trabajo_tarea');
		$this->generar_html_ef('trabajo_otro');
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$trabajo = persona::trabajo;
		$no_trabajo = persona::no_trabajo;
		$notrabajo_nibusco = persona::notrabajo_nibusco;

		echo "
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__trabajo_existe__procesar = function(es_inicial)
		{
			var mostrar_campos_laborales = false;
			var mostrar_no_laboral = false;
			var actividad_laboral = this.ef('trabajo_existe').get_estado();
				
			if (actividad_laboral == $trabajo) {
				mostrar_campos_laborales = true;
			} else if (actividad_laboral == $notrabajo_nibusco) {
				mostrar_no_laboral = true;
			}

			// Los campos que no se muestran, es decir se ocultan, tienen el parámetro para resetear en true.
				
			this.ef('trabajo_hace').mostrar(mostrar_campos_laborales,!mostrar_campos_laborales);
			this.ef('trabajo_ocupacion').mostrar(mostrar_campos_laborales,!mostrar_campos_laborales);
			this.ef('trabajo_tarea').mostrar(mostrar_campos_laborales,!mostrar_campos_laborales);
			this.ef('trabajo_otro').mostrar(mostrar_no_laboral,!mostrar_no_laboral);
		} 
		
		{$this->objeto_js}.evt__localidad__procesar = function(es_inicial)
		{
			//    Separa el id de localidad que viene como localidad:codigo_postal del buscador, 
			//    para mostrar el valor correspondiente en el ef de codigo_postal.
			
			if (! es_inicial) {
				if (this.ef('localidad').tiene_estado()) {
					var localidad = this.ef('localidad').get_estado().toString();
					var index = localidad.indexOf(':');
					if (index != -1 && index < localidad.length) {    //Si esta el separador : en el string de localidad y el string continua despues del mismo
						var cp = localidad.substring(localidad.indexOf(':') + 1, localidad.length);
						this.ef('codigo_postal').set_estado(cp);
					} else {                                        //La localidad no tiene cp, entonces dejo el estado de cp en vacio
						this.ef('codigo_postal').resetear_estado('');    
					}
				} else {
					//cubre el caso en que se limpia el valor del popup de localidad.
					this.ef('codigo_postal').resetear_estado();
				}
			}
		}
		";
	}

}
?>