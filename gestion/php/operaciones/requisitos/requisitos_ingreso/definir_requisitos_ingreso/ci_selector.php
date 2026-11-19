<?php
class ci_selector extends guarani_pers_ci
{
	protected $s__datos_entidad;
	protected $s__pantalla;

	function conf()
	{
		if (isset($this->s__pantalla)) {
			//$this->set_pantalla($this->s__pantalla);
		}
		$this->s__pantalla = $this->get_id_pantalla();
	}
	
	function evt__seleccion($seleccion)
	{	
		$this->controlador()->evt__cuadro__seleccion(array('requisito_propuesta' => $seleccion),false);
	}
	
	function evt__requisitos__seleccion($seleccion)
	{
		$this->evt__seleccion($seleccion['requisito_propuesta']);
	}
	
	function conf__entidad(toba_ei_formulario $form)
	{
		if (isset($this->s__datos_entidad['entidad'])) {
			$form->set_datos($this->s__datos_entidad);
		}
	}
	
	function evt__entidad__modificacion($datos)
	{
		if ($datos) {
			$this->s__datos_entidad = $datos;
		}
	}

	function conf__requisitos(toba_ei_cuadro $cuadro)
	{
		$etiquetas = guarani_etiquetas::get_tabla('sga_requisitos_propuestas_aplanado'); 
		$msg = guarani_mensajes::get('800_eof_cuadro', array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($msg);

		if (isset($this->s__datos_entidad['entidad'])) {
			$datos = $this->cn()->get_listado_requisitos_propuesta_entidad($this->s__datos_entidad['entidad']);
			$cuadro->set_datos($datos);
		}
	}

	function conf_evt__requisitos__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('requisitos')->get_datos();
		if ($datos[$fila]['tipo'] == requisito_nucleo::comp_entidad) {
			$tipos_perfil_datos = g3entidad::get_tipos_niveles_perfil_datos();
			if (!in_array($datos[$fila]['entidad_tipo'], $tipos_perfil_datos)) {
				$evento->anular();
			}
		} else {
			$subtipos_perfil_datos = g3entidad::get_subtipos_niveles_perfil_datos();
			if (!in_array($datos[$fila]['entidad_subtipo'], $subtipos_perfil_datos)) {
				$evento->anular();
			}
		}
	}

	function conf__vista_requisitos(arbol_requisitos $arbol)
	{
		$requisitos = $this->cn()->get_listado_requisitos_ingreso($this->get_id_pantalla());
		$raices = array();
		foreach ($requisitos as $req) {
			//Si cambió de requisito lo agrego al árbol como raíz
			$cambio_requisito = false;
			if (! isset($requisito) || $requisito != $req['requisito']) {
				$requisito = $req['requisito'];	
				$req_actual = new nodo_arbol_requisito("{$req['requisito_nombre']}", null, $req['requisito_propuesta']);
				$req_actual->ocultar_eliminar(); 
				$req_actual->set_arbol($arbol);
				$raices[] = $req_actual;
				$cambio_requisito = true;
				$para_toda_la_institucion = false;
			}
			if (! $para_toda_la_institucion) {
				//Si cambió de Nivel se lo agrego al Requisito
				$cambio_nivel = false;	
				if (! isset($nivel) || $nivel != $req['entidad_tipo'] || $cambio_requisito) {
					if ($req['entidad_tipo'] == g3entidad::tipo_institucion) {
						$req['entidad_tipo_nombre'] = 'Para Toda la Institución';
						$para_toda_la_institucion = true;
					} 
					$nivel = $req['entidad_tipo'];
					$nivel_actual = new nodo_arbol_requisito($req['entidad_tipo_nombre'], $req_actual, $req['entidad_tipo']);
					$nivel_actual->ocultar_utilerias(); 
					$req_actual->agregar_hijo($nivel_actual);
					$nivel_actual->set_arbol($arbol);
					$cambio_nivel = true;
					$tipos_perfil_datos = g3entidad::get_tipos_niveles_perfil_datos();
					if (!in_array($req['entidad_tipo'], $tipos_perfil_datos)) {
						$req_actual->ocultar_utilerias();
					}
				}	
			}
			if (! $para_toda_la_institucion) {
				//Agrego los subtipos del nivel
				if (! isset($subtipo) || $subtipo != $req['entidad_subtipo'] || $cambio_requisito || $cambio_nivel) {
					// Cuando el perfil de datos es por tipos de propuesta el subtipo de entidad puede no ser visible.
					if (!empty($req['entidad_subtipo_nombre'])) {
						$subtipo = $req['entidad_subtipo'];
						if ($req['tipo'] == g3entidad::nivel_aplicacion_tipo_entidad) {
							$req['entidad_subtipo_nombre'] .= ' (Algunas)';
						} else {
							$subtipos_perfil_datos = g3entidad::get_subtipos_niveles_perfil_datos();
							if (!in_array($req['entidad_subtipo'], $subtipos_perfil_datos)) {
								$req_actual->ocultar_utilerias();
							}
						}
						$subtipo_actual = new nodo_arbol_requisito($req['entidad_subtipo_nombre'], $req_actual, $req['entidad_subtipo']);
						$subtipo_actual->ocultar_utilerias(); 
						$nivel_actual->agregar_hijo($subtipo_actual);
						$subtipo_actual->set_arbol($arbol);
					}
				}
				if ($req['tipo'] == g3entidad::nivel_aplicacion_tipo_entidad) {
					$descripcion_entidad = $this->cn()->co()->get_descripcion_entidad($req['entidad'],$req['entidad_tipo']);
					if (!empty($descripcion_entidad)) {
						$definicion = new nodo_arbol_requisito($descripcion_entidad['entidad_descripcion'], $req_actual, $req['entidad']);
						$definicion->ocultar_utilerias(); 
						$subtipo_actual->agregar_hijo($definicion);
						$definicion->set_arbol($arbol);	
					}
				}
			}	
		}
		$arbol->set_datos($raices);
		$arbol->set_todos_abiertos();
		$arbol->set_ancho_nombres(120);
	}

	function conf__vista_subtipos(arbol_requisitos $arbol)
	{
		$requisitos = $this->cn()->get_listado_requisitos_ingreso($this->get_id_pantalla());
		$raices = array();
		foreach ($requisitos as $req) {
			//Si cambió de Nivel se lo agrego al Requisito
			$cambio_nivel = false;
			if (! isset($nivel) || $nivel != $req['entidad_tipo']) {
				$para_toda_la_institucion = false;
				if ($req['entidad_tipo'] == g3entidad::tipo_institucion) {
					$req['entidad_tipo_nombre'] = 'Para Toda la Institución';
					$para_toda_la_institucion = true;
				}
				$nivel = $req['entidad_tipo'];
				$nivel_actual = new nodo_arbol_requisito("{$req['entidad_tipo_nombre']}", null, $req['entidad_tipo']);
				$nivel_actual->ocultar_utilerias(); 
				$nivel_actual->set_arbol($arbol);
				$raices[] = $nivel_actual;
				$cambio_nivel = true;
			}
			if (! $para_toda_la_institucion) {
				//Agrego los subtipos del nivel
				$cambio_subtipo = false;
				if (! isset($subtipo) || $subtipo != $req['entidad_subtipo'] || $cambio_nivel) {
					$subtipo = $req['entidad_subtipo'];
					$subtipo_actual = new nodo_arbol_requisito($req['entidad_subtipo_nombre'], $nivel_actual, $req['entidad_subtipo']);
					$subtipo_actual->ocultar_utilerias(); 
					$nivel_actual->agregar_hijo($subtipo_actual);
					$subtipo_actual->set_arbol($arbol);	
					$cambio_subtipo = true;
				}
				if ($req['tipo'] == g3entidad::nivel_aplicacion_tipo_entidad) {
					if (!isset($entidad) || $entidad != $req['entidad'] || $cambio_subtipo) {
						$entidad = $req['entidad'];
						$descripcion_entidad = $this->cn()->co()->get_descripcion_entidad($entidad,$req['entidad_tipo']);
						if (!empty($descripcion_entidad)) {
							$entidad_actual = new nodo_arbol_requisito($descripcion_entidad['entidad_descripcion'], $subtipo_actual, $req['entidad']);
							$entidad_actual->ocultar_utilerias(); 
							$subtipo_actual->agregar_hijo($entidad_actual);
							$entidad_actual->set_arbol($arbol);
						}
					}
				} else {	
					unset($entidad);
				}
			}
			$requisito = $req['requisito_propuesta'];	
			if ($para_toda_la_institucion) {
				$req_actual = new nodo_arbol_requisito($req['requisito_nombre'], $nivel_actual, $requisito);
				$nivel_actual->agregar_hijo($req_actual);
			} else {	
				$req_actual = new nodo_arbol_requisito($req['requisito_nombre'], $subtipo_actual, $requisito);
				if (isset($entidad)) {
					$entidad_actual->agregar_hijo($req_actual);
				} else {
					$subtipo_actual->agregar_hijo($req_actual);
				}
			}
			if ($para_toda_la_institucion || isset($entidad)) {
				$tipos_perfil_datos = g3entidad::get_tipos_niveles_perfil_datos();
				if (!in_array($req['entidad_tipo'], $tipos_perfil_datos)) {
					$req_actual->ocultar_utilerias();
				}
			} else {
				$subtipos_perfil_datos = g3entidad::get_subtipos_niveles_perfil_datos();
				if (!in_array($req['entidad_subtipo'], $subtipos_perfil_datos)) {
					$req_actual->ocultar_utilerias();
				}
			}
			$req_actual->ocultar_eliminar();
			$req_actual->set_arbol($arbol);
		}
		$arbol->set_datos($raices);
		$arbol->set_todos_abiertos();
		$arbol->set_ancho_nombres(120);
	}

	function get_nombre_entidad($entidad)
	{
		return toba::consulta_php('co_g3entidades')->get_nombre_entidad($entidad);
	}
}