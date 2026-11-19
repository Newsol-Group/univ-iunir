<?php

class ci_edicion_entidad extends guarani_pers_ci
{
	protected $componentes_eventos_a_inhabilitar = array();

	function inicializar ($parametro=array())
	{
		parent::inicializar();
		//Componentes a inhabilitar si la entidad en edición está bloqueada para editar
		$this->componentes_eventos_a_inhabilitar['formulario_ml'] = array('modificacion');
		$this->componentes_eventos_a_inhabilitar['formulario'] = array('modificacion');
		$this->componentes_eventos_a_inhabilitar['cuadro'] = array('seleccion');
		$this->componentes_eventos_a_inhabilitar['filtro'] = array('filtrar', 'limpiar');
		$this->eventos_controlador_a_inhabilitar = array('guardar', 'limpiar' , 'eliminar');
	}

	function post_configurar()
	{
		if (! $this->cn()->entidad()->es_editable()) {
			$this->inhabilitar_componentes_eventos();
			$this->inhabilitar_eventos_controlador();
			$this->pantalla()->set_descripcion(toba::mensajes()->get('800_entidad_no_editable'));
		} else {
			if (! $this->cn()->entidad()->se_puede_eliminar()) {
				$this->inhabilitar_eventos_controlador(array('eliminar'));
				$this->pantalla()->set_descripcion(toba::mensajes()->get('800_entidad_no_eliminable'));
			}
		}
	}

	/*
	 * Se inhabilitan los eventos de los componentes definidos.
	 */
	function inhabilitar_componentes_eventos($componentes_eventos = array())
	{
		if ($componentes_eventos == array()) {
			$componentes_eventos = $this->componentes_eventos_a_inhabilitar;
		}
		
		foreach ($componentes_eventos as $componente => $eventos) {
			foreach ($this->get_dependencias_clase(".+_ei_$componente$") as  $id => $dep) {
				if ($componente == 'formulario') {
					$this->dep($dep)->set_solo_lectura();
				}
				if ($componente == 'formulario_ml') {
					$this->dep($dep)->set_solo_lectura();
					$this->dep($dep)->desactivar_agregado_filas();
				}
				foreach ($eventos as $evento) {
					if ($this->dep($dep)->existe_evento($evento)) {
						if ($this->dep($dep)->evento($evento)->esta_en_botonera() || $this->dep($dep)->evento($evento)->esta_sobre_fila()) {
							if ($componente == 'cuadro' && $evento == 'seleccion' && ! $this->dep($dep)->evento($evento)->es_seleccion_multiple()) {
								continue;
							}
							$this->dep($dep)->evento($evento)->desactivar();
						} else {
							$this->dep($dep)->eliminar_evento($evento);
						}
					}
				}
			}
		}
	}

	/**
	 * Se inhabilitan los eventos del controlador, ci principal de la operación. En este caso corresponde a dos ci's
	 * superiores ya que la jerarquía de árboles involucra: el ci principal, el ci que maneja el árbol y los ci's 
	 * correspondientes a las diferentes entidades a editar.
	 */
	function inhabilitar_eventos_controlador($eventos=array())
	{
		if ($eventos == array()) {
			$eventos = $this->eventos_controlador_a_inhabilitar;
		}
		foreach ($eventos as $evento) {
			if ($this->controlador()->controlador()->pantalla()->existe_evento($evento)) {
				if ($this->controlador()->controlador()->evento($evento)->esta_en_botonera()) {
					$this->controlador()->controlador()->evento($evento)->desactivar();
				} else {
					$this->controlador()->controlador()->eliminar_evento($evento);
				}
			}
		}
	}

}
?>