<?php

namespace siu\operaciones\censo;

use SIU\Chulupi\kernel;
use SIU\Chulupi\nucleo\NuevoRequest;
use siu\extension_kernel\vista_preinscripcion;
use siu\errores\error_preinscripcion_seguridad;
use siu\modelo\util;
use siu\preinscripcion;

class vista extends vista_preinscripcion
{
	// Pagelets del formulario de preinscripción
	static $FORMS = array(
		'propuesta',
		'datos_adicionales',
		'datos_principales',
		'datos_personales',
		'financiamiento',
		'datos_laborales',
		'datos_familiares',
		'datos_estudios',
		'tecnologia',
		'deportes',
		'idiomas',
		'discapacidad'
	);
	
	// Pagelets de formularios adicionales para posgrado
	static $FORMS_POSGRADO = array(
		'actuacion_docente',
		'actuacion_profesional',
		'formacion_acad'
	);
	
	protected $form_activo;
	protected $id_form_activo;
	protected $campos_pagelet;
	protected $campos_faltantes;
	protected $error_mail;

	function ini()
	{
		$finalizado = true;
		$this->controlar_estado();
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('carga_datos_censales'));
		$clase = 'operaciones\censo\pagelet_lista_forms';
		$pl = kernel::localizador()->instanciar($clase, 'lista_forms');
		$this->add_pagelet($pl);

	}
	
	protected function controlar_estado()
	{
		// Si el aspirante fue inscripto en G3, se fuerza el cierre de sesión.
		// En adelante, sus trámites serán realizados en la interfaz de Autogestión.
		if (kernel::persona()->datos()->get_estado_cuenta_preinscripcion() == preinscripcion::estado_inscripto) {
			kernel::sesion()->cerrar();
			throw new NuevoRequest('acceso', 'index');
		}
	}

	function get_action_imprimir()
	{
		return kernel::vinculador()->crear('censo', 'imprimir', array($this->get_id_form_activo()));
	}

	function get_action_finalizar()
	{
		return kernel::vinculador()->crear('censo', 'finalizar');
	}

	function hay_inscripciones()
	{
		$cantidad_inscripciones = kernel::persona()->datos()->get_cantidad_inscripciones();
		return $cantidad_inscripciones > 0;
	}

	function get_url_ocultar_msg_impr()
	{
		return kernel::vinculador()->crear('censo', 'ocultar_msg_impr', array($this->get_id_form_activo()));
	}

	function get_mensaje_impresion_campos_faltantes()
	{
                $mje = 'impresion_campos_faltantes';

                // Turno de presentación (parámetro 'carga_turno_presentacion')
		if (kernel::proyecto()->carga_turno_presentacion()) {

                    $turnos = kernel::persona()->datos()->get_listado_turnos();
                    // Si hay turnos de presentación de documentación muestro un mensaje diferente
                    if (!empty($turnos)) {
                        $mje = 'impresion_campos_faltantes_y_turno';
                    }

		}
                
		return kernel::traductor()->trans($mje);
	}

	//--------------------------------------------------------------------------
	//---- Control de campos faltantes -----------------------------------------
	//--------------------------------------------------------------------------

	function activar_control_faltantes()
	{
		kernel::sesion()->set('modo_control_faltantes', true);
	}

	function desactivar_control_faltantes()
	{
		kernel::sesion()->borrar('modo_control_faltantes');
	}

	function control_faltantes_activado()
	{
		return kernel::sesion()->esta_seteada('modo_control_faltantes');
	}

	function hay_cambios_desde_impresion()
	{
		return $this->controlador->hay_cambios_desde_impresion();
	}

	function get_mensaje_error_email(){
		$this->error_mail = '';
		if (kernel::sesion()->esta_seteada('error_mail')) {
			$this->error_mail = kernel::sesion()->get('error_mail');
		}
		return $this->error_mail;
	}

	function get_campos_faltantes()
	{
		if (!isset($this->campos_faltantes)) {
			$this->campos_faltantes = kernel::persona()->datos()->get_datos_faltantes($this);
		}
		if (empty($this->campos_faltantes)) {
			$this->desactivar_control_faltantes();
		}
		return $this->campos_faltantes;
	}

	//--------------------------------------------------------------------------

	function get_forms_visibles()
	{
		$salida = array('propuesta');
		foreach (static::$FORMS as $seccion) {
			if ($seccion != 'propuesta') {
				if (util::form()->hay_visibles_seccion($seccion)) {
					$salida[] = $seccion;
				}
			}
		}

		// Datos de salud / Discapacidad
		$salida[] = 'datos_salud';

		// Documentos digitales
		if (kernel::proyecto()->usa_rdi()) {
			$salida[] = 'documentacion';
		}
		
		// Turno de presentación (parámetro 'carga_turno_presentacion')
		if (kernel::proyecto()->carga_turno_presentacion()) {

                    $turnos = kernel::persona()->datos()->get_listado_turnos();
                    // Si hay turnos de presentación de documentación muestro la solapa
                    if (!empty($turnos)) {
                        $salida[] = 'turno_presentacion';
                    }
                        
		}

		if (kernel::persona()->datos()->hay_insc_posgrado()) {
			// Se agregan pagelets para datos de posgrado
			$salida = array_merge($salida, static::$FORMS_POSGRADO);
		}
		return $salida;
	}

	function get_forms()
	{
		if (kernel::persona()->datos()->hay_inscripcion_propuesta()) {
			return $this->get_forms_visibles();
		}
	}

	function existe_form($id)
	{
		return in_array($id, $this->get_forms_visibles());
	}

	function get_id_form_activo()
	{
		return $this->id_form_activo;
	}

	function set_form_activo($pagelet)
	{
		$this->form_activo = $pagelet;
		$this->id_form_activo = $pagelet->get_id();
		$this->add_pagelet($pagelet, 0, 'formulario');
	}

	function pagelet($id)
	{
		if (isset($this->pagelets[$id])) {
			return parent::pagelet($id);
		}

		if ($this->existe_form($id)) {
			$clase = "operaciones\censo\pagelet_$id";
			return kernel::localizador()->instanciar($clase, $id);
		}

		throw new error_preinscripcion_seguridad("el pagelet '$id' no existe");
	}

}