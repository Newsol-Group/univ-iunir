<?php
/**
 * 
 * @package Administración
 */
class ci_edi_derechos extends guarani_ci
{
	protected $hay_cambios;

	protected $datos_temp_derechos_comision;
	protected $s__responsabilidad_anterior;
	protected $s__responsabilidad;

	protected $datos_temp_derechos_mesa_examen;
	protected $s__rol_anterior;
	protected $s__rol;

	function ini__operacion()
	{
		$this->cn()->dep('derechos_comision')->cargar();
		$this->cn()->dep('derechos_mesa_examen')->cargar();
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_cursadas(toba_ei_pantalla $pantalla)
	{
		$pantalla->eliminar_dep('form_derechos_comision');
		if (! empty($this->s__responsabilidad)) {
			$pantalla->agregar_dep('form_derechos_comision');
		}
		$this->hay_cambios = $this->cn()->hay_cambios();
	}

	function conf__pant_examenes(toba_ei_pantalla $pantalla)
	{
		$pantalla->eliminar_dep('form_derechos_mesa_examen');
		if (! empty($this->s__rol)) {
			$pantalla->agregar_dep('form_derechos_mesa_examen');
		}
		$this->hay_cambios = $this->cn()->hay_cambios();
	}

	//-----------------------------------------------------------------------------------
	//---- form_responsabilidad ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_responsabilidad(guarani_pers_ei_formulario $form)
	{		
		if (isset($this->s__responsabilidad)) {
			$form->set_datos($this->s__responsabilidad);
		}
	}

	function evt__form_responsabilidad__modificacion($datos)
	{
		$this->s__responsabilidad_anterior = $this->s__responsabilidad;
		$this->s__responsabilidad = empty($datos['responsabilidad']) ? null : $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_derechos_comision -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_derechos_comision(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp_derechos_comision)) {
			$this->hay_cambios = true;
			$form->set_datos($this->datos_temp_derechos_comision);
		} else {
			$datos = aplanar_matriz($this->cn()->get_datos_derechos_comision($this->s__responsabilidad), 'derecho');
			$form->set_datos(array('derecho' => $datos));
		}
	}

	function evt__form_derechos_comision__modificacion($datos)
	{
		if (! empty($this->s__responsabilidad_anterior)) {
			try {
				$this->cn()->set_datos_derechos_comision($datos['derecho'], $this->s__responsabilidad_anterior);
			} catch (guarani_error $e) {
				$this->datos_temp_derechos_comision = $datos;
				throw new guarani_error($e->getMessage());
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_rol ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_rol(guarani_pers_ei_formulario $form)
	{		
		if (isset($this->s__rol)) {
			$form->set_datos($this->s__rol);
		}
	}

	function evt__form_rol__modificacion($datos)
	{
		$this->s__rol_anterior = $this->s__rol;
		$this->s__rol = empty($datos['rol']) ? null : $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_derechos_mesa_examen ----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_derechos_mesa_examen(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp_derechos_mesa_examen)) {
			$this->hay_cambios = true;
			$form->set_datos($this->datos_temp_derechos_mesa_examen);
		} else {
			$datos = aplanar_matriz($this->cn()->get_datos_derechos_mesa_examen($this->s__rol), 'derecho');
			$form->set_datos(array('derecho' => $datos));
		}
	}

	function evt__form_derechos_mesa_examen__modificacion($datos)
	{
		if (! empty($this->s__rol_anterior)) {
			try {
				$this->cn()->set_datos_derechos_mesa_examen($datos['derecho'], $this->s__rol_anterior);
			} catch (guarani_error $e) {
				$this->datos_temp_derechos_mesa_examen = $datos;
				throw new guarani_error($e->getMessage());
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar_derechos_comision()
	{
		try {
			$this->cn()->sincronizar_derechos_comision();
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e) {
			throw $e;
		}
	}

	function evt__guardar_derechos_mesa_examen()
	{
		try {
			$this->cn()->sincronizar_derechos_mesa_examen();
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e) {
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Javascript -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmacion = guarani_mensajes::get('800_abandono_edicion');
		$msj_sin_cambios = guarani_mensajes::get('800_no_hubo_cambios');

		$confirmar = $this->hay_cambios ? 'var confirmar = true;' : 'var confirmar = toba.hay_cambios();';

		echo "
			{$this->objeto_js}.evt__guardar_derechos_comision = function() {
				return confirmar_no_cambios();
			}

			{$this->objeto_js}.evt__guardar_derechos_mesa_examen = function() {
				return confirmar_no_cambios();
			}

			function confirmar_cambios(proyecto, operacion, url, es_popup) {
				var resp = true;
				$confirmar
				if (confirmar) {
					resp = confirm('$msj_confirmacion');
				}
				return resp;
			}

			function confirmar_no_cambios(proyecto, operacion, url, es_popup) {
				var resp = true;
				$confirmar
				if (! confirmar) {
					alert('$msj_sin_cambios');
					resp = false;
				}
				return resp;
			}

            toba.set_callback_menu(confirmar_cambios);
		";
	}
}
?>