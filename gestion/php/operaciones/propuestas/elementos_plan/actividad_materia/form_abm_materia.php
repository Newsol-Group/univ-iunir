<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class form_abm_materia extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
    	echo "<table border=0>";
    	echo "<tr>";
	    echo "<td>";    
	       	$this->generar_html_ef('cobrable');//		    
	       	$this->generar_html_ef('aprobacion_x_resolucion');//		    
	       	$this->generar_html_ef('permite_promocion');//
	       	$this->generar_html_ef('permite_rendir_libre');//
	       	$this->generar_html_ef('promediable');//
			$this->generar_html_ef('cantidad_de_aplazos');//
	    echo "</td>";
	    echo '<td style="background-image: url('.toba_recurso::imagen_proyecto( '/barra_vert.png').'); background-repeat: repeat-y">';
	    echo "&nbsp;";
		echo "</td>";
	    echo "<td>";
	        $this->generar_html_ef('requiere_cursada');//
	        $this->generar_html_ef('requiere_examen');//
	        $this->generar_html_ef('sale_listado');//
	        $this->generar_html_ef('horas_totales');//
	        $this->generar_html_ef('horas_semanales');//
		echo "</td>";
		echo "</tr>";
        echo "<tr>";
		echo "<td colspan=3>";                
        echo '<hr class="ci-pant-sep-ei"/>';
	    echo "</td>";            
        echo "</tr>";        		
		echo "<tr>";
	    echo "<td>";
			$this->generar_html_ef('escala_de_notas');//
			$this->generar_html_ef('escala_nota_cursada_regular');//
			$this->generar_html_ef('escala_nota_cursada_promocion');//
			$this->generar_html_ef('escala_nota_equivalencia');//
			$this->generar_html_ef('escala_nota_examen');//
	    echo "</td>";
	    echo '<td style="background-image: url('.toba_recurso::imagen_proyecto( '/barra_vert.png').'); background-repeat: repeat-y">';
	    echo "&nbsp;";
		echo "</td>";
        echo "<td>";
	        $this->generar_html_ef('linea_creditos');//
	        $this->generar_html_ef('creditos');//
	        $this->generar_html_ef('creditos_min');//
	        $this->generar_html_ef('creditos_max');//	        
	    echo "</td>";
        echo "</tr>";	
        echo "<tr>";
		echo "<td colspan=3>";                
        echo '<hr class="ci-pant-sep-ei"/>';
	    echo "</td>";            
        echo "</tr>";        	        
      	echo "<tr>";
		echo "<td colspan=3>";
	    $this->generar_html_ef('vigencia');//		
        $this->generar_html_ef('nivel_formacion');
        $this->generar_html_ef('disciplina');		
        $this->generar_html_ef('contenido');
	    echo "</td>";            
        echo "</tr>";       
		echo "</table>";
	}
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_horas = guarani_mensajes::get('800_dato_invalido', array('campo Horas semanales', 'las Horas Totales deben ser mayores que las Horas Semanales'));
		$msj_cantidad_aplazos = guarani_mensajes::get('800_dato_invalido', array('campo Cantidad de aplazos','debe ser un número entero mayor a cero'));
		$msj_vigencia = guarani_mensajes::get('800_dato_invalido', array('campo Vigencia','debe ser un número entero mayor a cero'));
		$msj_creditos_totales = guarani_mensajes::get('800_dato_invalido', array('campo Créditos totales','debe ser un número mayor o igual a cero'));
		$msj_creditos_minimos = guarani_mensajes::get('800_dato_invalido', array('campo Créditos mínimos','debe ser un número mayor o igual a cero'));
		$msj_creditos_maximos = guarani_mensajes::get('800_dato_invalido', array('campo Créditos máximos','debe ser un número mayor o igual a cero'));
		$msj_horas_totales = guarani_mensajes::get('800_dato_invalido', array('campo Horas totales','debe ser un número mayor a cero'));
		$msj_horas_semanales = guarani_mensajes::get('800_dato_invalido', array('campo Horas semanales','debe ser un número mayor a cero'));
		
		$no = comunes::no;
		
		echo "
		
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			var creditos = this.ef('creditos').get_estado();
			var min	= this.ef('creditos_min').get_estado();
			var max	= this.ef('creditos_max').get_estado();;

			if (this.ef('creditos_min').tiene_estado() != this.ef('creditos_max').tiene_estado()) {
				if (this.ef('creditos_min').tiene_estado()) {
					notificacion.agregar('Créditos - Debe ingresar también el Máximo');
					return false;
				} else {
					notificacion.agregar('Créditos - Debe ingresar también el Mínimo');
					return false;
				}
			} else {
				if (this.ef('creditos_min').tiene_estado()) {
					if (min >= max) {
						notificacion.agregar('Créditos - El Máximo debe ser mayor que el Mínimo');
						return false;
					} else {
						if (this.ef('creditos').tiene_estado()) {
							if (! validacion.esta_entre(creditos,min,max)) {
								notificacion.agregar('Créditos - Los Totales deben estar entre el Mínimo y el Máximo');
								return false;
							}
						}
					}
				}
			}

			horas_semanales = this.ef('horas_semanales').get_estado();
			horas_totales = this.ef('horas_totales').get_estado();
			horas_seteadas = this.ef('horas_totales').tiene_estado() && this.ef('horas_semanales').tiene_estado();			
			if (horas_seteadas && (horas_semanales >= horas_totales)) {
				notificacion.agregar('$msj_horas');
				return false;
			}

			return true;
		}
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__cantidad_de_aplazos__validar = function()
		{
			var es_numero_entero = this.ef('cantidad_de_aplazos').get_estado() == Math.round(this.ef('cantidad_de_aplazos').get_estado());
			var esta_en_rango = (this.ef('cantidad_de_aplazos').get_estado() >= 0);
			if (!es_numero_entero || !esta_en_rango) {
				this.ef('cantidad_de_aplazos').set_error('$msj_cantidad_aplazos');
				return false;
		            }
		            return true;
		}
		
		{$this->objeto_js}.evt__creditos__validar = function()
		{
			if (this.ef('creditos').get_estado() < 0) {
				this.ef('creditos').set_error('$msj_creditos_totales');
				return false;
			}
			return true;
		}

		{$this->objeto_js}.evt__creditos_min__validar = function()
		{
			if (this.ef('creditos_min').get_estado() < 0) {
				this.ef('creditos_min').set_error('$msj_creditos_minimos');
				return false;
			}
			return true;
		}

		{$this->objeto_js}.evt__creditos_max__validar = function()
		{
			if (this.ef('creditos_max').get_estado() < 0) {
				this.ef('creditos_max').set_error('$msj_creditos_maximos');
				return false;
			}
			return true;
		}
		
		{$this->objeto_js}.evt__horas_totales__validar = function()
		{
			var esta_en_rango = (this.ef('horas_totales').get_estado() >= 0);
			if (!esta_en_rango) {
				this.ef('horas_totales').set_error('$msj_horas_totales');
				return false;
		            }
		            return true;
		}
		
		{$this->objeto_js}.evt__horas_semanales__validar = function()
		{
			var esta_en_rango = (this.ef('horas_semanales').get_estado() >= 0);
			if (!esta_en_rango) {
				this.ef('horas_semanales').set_error('$msj_horas_semanales');
				return false;
		            }
		            return true;
		}
		
		{$this->objeto_js}.evt__vigencia__validar = function()
		{
			var es_numero_entero = this.ef('vigencia').get_estado() == Math.round(this.ef('vigencia').get_estado());
			var esta_en_rango = (this.ef('vigencia').get_estado() >= 0);
			if (!es_numero_entero || !esta_en_rango) {
				this.ef('vigencia').set_error('$msj_vigencia');
				return false;
		            }
		            return true;
		}
		
		{$this->objeto_js}.evt__permite_promocion__procesar = function(es_inicial)
		{
			var valor = this.ef('permite_promocion').get_estado();
			if (valor == '$no') {
				this.ef('escala_nota_cursada_promocion').resetear_estado();
				this.ef('escala_nota_cursada_promocion').ocultar();
			} else {
				this.ef('escala_nota_cursada_promocion').mostrar();
			}
		}
		
		{$this->objeto_js}.evt__requiere_cursada__procesar = function(es_inicial)
		{
			var valor = this.ef('requiere_cursada').get_estado();
			if (valor == '$no') {
				this.ef('escala_nota_cursada_regular').resetear_estado();
				this.ef('escala_nota_cursada_regular').ocultar();
			} else {
				this.ef('escala_nota_cursada_regular').mostrar();
			}
		}
		";
	}



}
?>