<?php
class ci_edi_catedras extends ci_edicion_simple
{
	protected $estado_catedra;

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		if (!$this->cn()->entidad_es_nueva()) {
			$datos_catedra = $this->cn()->entidad()->get_datos();

			// Si el estado era baja y tenía cátedra equivalente...
			if ($this->estado_catedra == comunes::estado_baja && $this->cn()->entidad()->tiene_catedra_equivalente($datos_catedra['catedra'])) {
				$mensaje = guarani_mensajes::get('800CAT_cat_equiv_definida');
				$id_js_formulario = $this->dependencia('formulario')->get_id_objeto_js();

				echo "
				//---- Procesamiento de EFs --------------------------------
				{$id_js_formulario}.evt__estado__procesar = function(es_inicial)
				{
					if (this.ef('estado').get_estado() == 'A') {
						notificacion.agregar('$mensaje', 'info');
						notificacion.mostrar();
						notificacion.limpiar();
					}
				}
				";
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos();
			if (!isset($this->s__estado_catedra)) {
				$this->estado_catedra = $datos['estado'] ?? null;
			}
			$form->set_datos($datos);
		}
	}
}