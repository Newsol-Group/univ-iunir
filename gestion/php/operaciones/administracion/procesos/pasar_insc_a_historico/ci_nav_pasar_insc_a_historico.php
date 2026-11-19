<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Procesos
 * 
 */
class ci_nav_pasar_insc_a_historico extends guarani_pers_ci
{
	protected $s__datos;
	
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->s__datos)) {
			$form->set_datos($this->s__datos);
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$this->s__datos = $datos;
	}
	
	function get_opciones_multi_check()
	{
		return array(array('valor' => 'C','descr' => 'Inscripciones a Actividades'),
		             array('valor' => 'E','descr' => 'Inscripciones a Examen')
		            );
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		if (isset($this->s__datos)) {
			// Procesamiento
			$anio_academico = $this->s__datos['anio_academico'];
			
			$pasar = $this->s__datos['pasar'];
			$cursadas = (in_array('C', $pasar)) ? 1 : 0;
			$examenes = (in_array('E', $pasar)) ? 1 : 0;
			
			$this->cn()->procesar_inscripciones($anio_academico, $cursadas, $examenes);
			unset($this->s__datos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$this->evento('procesar')->set_msg_confirmacion(guarani_mensajes::get('800ADM_confirmar_insc_a_historico'));
	}
}
?>