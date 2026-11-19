<?php
class ci_config_campos extends guarani_pers_ci
{
	protected $indice_inicial;
	protected $indice_final;
	
	function ini()
	{
		echo "	
			<style type='text/css'>
				<!-- 
					.ci-tabs-v-lista {
						width: 230px;
					}
				-->
			</style>";
		
		parent::ini();
	}
	
	//--------------------------------------------------------------------------
	//---- Configuraciones -----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__datos_personales()
	{
		$this->indice_inicial = 1;
		$this->indice_final = 7;
	}

	function conf__domicilio()
	{
		$this->indice_inicial = 9;
		$this->indice_final = 26;
	}

	function conf__financiamiento()
	{
		$this->indice_inicial = 50;
		$this->indice_final = 69;
	}

	function conf__datos_laborales()
	{
		$this->indice_inicial = 70;
		$this->indice_final = 78;
	}

	function conf__tecnologia()
	{
		$this->indice_inicial = 90;
		$this->indice_final = 104;
	}

	function conf__deportes()
	{
		$this->indice_inicial = 105;
		$this->indice_final = 117;
	}

	function conf__idiomas()
	{
		$this->indice_inicial = 118;
		$this->indice_final = 125;
	}

	function conf__datos_salud()
	{
		$this->indice_inicial = 130;
		$this->indice_final = 131;
	}

	function conf__preferencias()
	{
		$this->indice_inicial = 135;
		$this->indice_final = 157;
	}
	
	function conf__discapacidad()
	{
		$this->indice_inicial = 158;
		$this->indice_final = 193;
	}

	//--------------------------------------------------------------------------
	//---- form_datos_personales -----------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_datos(guarani_pers_ei_formulario_ml $form_ml)
	{
		$datos = $this->cn()->get_datos($this->indice_inicial, $this->indice_final);
		$form_ml->set_datos($datos);
	}

	function evt__form_datos__modificacion($datos)
	{
		$this->cn()->set_datos($datos);
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__guardar()
	{
		toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
	}

}
?>