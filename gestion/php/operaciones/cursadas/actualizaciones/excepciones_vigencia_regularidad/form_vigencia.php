<?php
class form_vigencia extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		$parametros = $this->controlador()->cn()->get_valor_parametros();
		$cur_dsd = $parametros['cur_cant_meses_vigencia_regularidad']['valor_desde'];
		$cur_hst = $parametros['cur_cant_meses_vigencia_regularidad']['valor_hasta'];
		$exa_dsd = $parametros['exa_cant_meses_vigencia_regularidad_revalida']['valor_desde'];
		$exa_hst = $parametros['exa_cant_meses_vigencia_regularidad_revalida']['valor_hasta'];
		$msj = "El valor asignado al campo excede el permitido [$cur_dsd...$cur_hst]";
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__vigencia_cursada__validar = function()
		{			
			var valor = this.ef('vigencia_cursada').get_estado();
			if (this.ef('vigencia_cursada').tiene_estado() && (valor > $cur_hst || valor < $cur_dsd || valor > $exa_hst || valor < $exa_dsd)) {
				this.ef('vigencia_cursada').set_error('$msj');
				return false;
			}
			return true;
		}
		
		{$this->objeto_js}.evt__vigencia_revalida__validar = function()
		{			
			var valor = this.ef('vigencia_revalida').get_estado();
			if (this.ef('vigencia_revalida').tiene_estado() && (valor > $cur_hst || valor < $cur_dsd || valor > $exa_hst || valor < $exa_dsd)) {
				this.ef('vigencia_revalida').set_error('$msj');
				return false;
			}
			return true;
		}
		";
	}

}

?>