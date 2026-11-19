<?php 
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class form_materia_generica_revision extends guarani_pers_ei_formulario
{
	protected $reglas;
	
	function generar_layout()
	{	
		echo '<div class="ei-barra-subtit">';
		echo "<span>Creditos a Informar</span>";
		echo "</div>";
		$this->generar_html_ef('origen_creditos');
		$this->generar_html_ef('creditos_otorgados');
	}

	function get_parametros_reglas() 
	{
		$this->reglas = toba::consulta_php('co_reglas')->get_lista_reglas_pars();
		
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Validacion general ----------------------------------

		var reset_al_ocultar = true;
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			return true;	
		}
		
		//---- Procesamiento de EFs --------------------------------

				
		{$this->objeto_js}.evt__regla__procesar = function(es_inicial)
		{
			";
		
			$this->get_parametros_reglas();

			// Se hace un switch conpara mostrar el campo parametros solo cuando la regla seleccionada tiene parametros asociados
			
		echo "
			this.ef('parametros').ocultar();
				
			switch(this.ef('regla').get_estado())
			{
			";
			
			foreach ($this->reglas as $regla)
			{
				echo "	case '{$regla['regla']}':
				
					this.ef('parametros').mostrar();
					
					break;
				
				";
			}
			
		echo "
			default:
			
				this.ef('parametros').ocultar(reset_al_ocultar);
				break;
				
			}
		}
		";			
			
		echo "
		{$this->objeto_js}.evt__parametros__procesar = function(es_inicial)
		{

		}
				
		{$this->objeto_js}.evt__origen_creditos__procesar = function(es_inicial)
		{
			//M/Definidos en el módulo,A/Sumatoria de créditos
			if (this.ef('origen_creditos').get_estado() == 'M') {
				this.ef('creditos_otorgados').mostrar();
			} else {
				this.ef('creditos_otorgados').ocultar(reset_al_ocultar);
			}
		}
		
		{$this->objeto_js}.evt__creditos_otorgados__procesar = function(es_inicial)
		{
		}
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__puntaje__validar = function()
		{
			var campo = 'puntaje';
			if (! this.ef(campo).es_oculto() && this.ef(campo).get_estado() == '') {
				alert ('Ingrese el '+campo+'.');
				this.ef(campo).seleccionar();
				return false;
			} else {
				return true;
			}		
		}
		
		{$this->objeto_js}.evt__parametros__validar = function()
		{
			var parametros = this.ef('parametros').get_estado();
			parametros = parametros.split('|');			
			";
			foreach ($this->reglas as $id => $regla)
			{
				echo "
					if (this.ef('regla').get_estado() == {$regla['regla']}) {
						
						if (! ( (parametros[{$regla['orden']}-1] != null) && ( validar_tipo_par( parametros[{$regla['orden']}-1], '{$regla['tipo_de_dato']}')))) {
							alert('Tipo de Parámetro Incorrecto para la regla');
							this.ef('parametros').seleccionar();
							return false;
						}
					
					}		
				";		
			}
			echo "
			return true;
		}
		
		function validar_tipo_par(par, tipo) 
		{	
			// Validar tambien decimal, varchar y date
			// Quiza convenga hacerlo con Exp regulares
			
			var ret = true;
			
			if (tipo == 'integer') {
				 if (isNaN(par)) {
	        		ret = false;
				 }
			}
			else {
				par = new String(par);
				if (tipo == 'decimal') {
					 if (!(par.match(/^\d*\.\d\d$/))) {
		        		ret = false;
					 }
				} else {
					if (tipo == 'date') {
						if (!(par.match(/^\d{4}\/\d\d\/\d\d$/))) {
			        		ret = false;
						}
					} else {
						if (tipo == 'varchar') {
							 if (!(par.match(/^([A-Z]+[a-z])+$/))) {
				        		ret = false;
							 }
						}					
					}
				}
			}
			
			return ret;
			
		}
	";
	}
}

?>