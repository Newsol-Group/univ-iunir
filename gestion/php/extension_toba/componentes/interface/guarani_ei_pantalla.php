<?php
class guarani_ei_pantalla extends toba_ei_pantalla
{
	protected $mostrar_extras_cabecera = true;
	
	/**
	 * Establece un nuevo ancho para la pantalla
	 * 
	 * @param int $ancho
	 */
	function set_ancho($ancho)
	{
		$this->_info_ci['ancho'] = $ancho . 'px';
	}

	/**
	 * Retorna el ancho de la pantalla
	 * 
	 * @return int ancho de la pantalla (si no hay un ancho definido, retorna 950
	 */
	function get_ancho()
	{
		$ancho = !empty($this->_info_ci['ancho']) ? $this->_info_ci['ancho'] : '950';
		return str_replace('px', '', $ancho);
	}
	
	function set_no_mostrar_extras_cabecera()
	{
		$this->mostrar_extras_cabecera = false;
	}

	/**
	 * Genera el html de la barra tabs, el toc (si tiene) y el contenido de las dependencias actuales
	 * @ignore
	 */
	protected function generar_html_cuerpo()
	{
		if ($this->_info_ci['tipo_navegacion'] == self::NAVEGACION_WIZARD && $this->controlador->get_wizard_toc_sentido() == 'horizontal') {
			echo "<table border='0' class='tabla-0' width=100%>\n";
			echo "<tr><td class='ei-barra-wiz-horiz-tit'>";
			if ($this->_info_ci['con_toc']) {
				$this->generar_toc_wizard();
			}
			echo "</td></tr><tr>";
			echo toba::output()->get('Pantalla')->getPreContenido($this->_info_ci['tipo_navegacion']);
			$this->generar_html_contenido();
			echo toba::output()->get('Pantalla')->getPreContenido($this->_info_ci['tipo_navegacion']);
			echo "</tr>\n";
			echo "</table>\n";
		} else {
			parent::generar_html_cuerpo();
		}
	}

	/**
	 * Genera la tabla de contenidos del modo navegacion wizard
	 * @ignore
	 */
	protected function generar_toc_wizard()
	{
		if ($this->controlador->get_wizard_toc_sentido() == 'horizontal') {
			$separador = toba_recurso::imagen_proyecto("wizard/separador.gif", true);
			$pasada = true;
			$paso = 1;
			echo "<table border='0'><tr>";
			foreach ($this->_lista_tabs as $id => $pantalla) {
				$actual = false;
				if ($pasada)
					$clase = 'ci-wiz-toc-horiz-pant-pasada';
				else
					$clase = 'ci-wiz-toc-horiz-pant-futuro';
				if ($id == $this->_id_en_controlador) {
					$actual = true;
					$clase = 'ci-wiz-toc-horiz-pant-actual';
					$pasada = false;
				}
				echo "<td>";
				echo "<span class='$clase' style='background: none;'>";
				echo $pantalla->get_etiqueta();
				echo "</span>";
				echo "<br>";
				echo "<span class='$clase' style='font-size: 9px;padding-left: 7px;background: none;'>";
				echo "PASO ";
				if ($actual) {
					echo "ACTUAL";
				} else {
					echo $paso;
				}
				echo "</span>";
				echo "</td>";
				$paso ++;
				echo "<td style='border: none;padding-left:2px;margin:0;'>";
				echo $separador;
				echo "</td>";
			}
			echo "</tr></table>";
		} else {
			parent::generar_toc_wizard();
		}
	}

	/**
	 * Genera la tabla de contenidos del modo navegacion wizard
	 * @ignore
	 */
	protected function _generar_toc_wizard()
	{
		if ($this->controlador->get_wizard_toc_sentido() == 'horizontal') {
			$separador = toba_recurso::imagen_proyecto("wizard/separador-2.gif", true);
			$separador_actual = toba_recurso::imagen_proyecto("wizard/separador-actual.png", true);
			$pasada = true;
			$paso = 1;
			foreach ($this->_lista_tabs as $id => $pantalla) {
				$actual = false;
				if ($pasada)
					$clase = 'ci-wiz-toc-horiz-pant-pasada';
				else
					$clase = 'ci-wiz-toc-horiz-pant-futuro';
				if ($id == $this->_id_en_controlador) {
					$actual = true;
					$clase = 'ci-wiz-toc-horiz-pant-actual';
					$pasada = false;
				}
				echo "<div class='$clase'>";
				echo "$paso - ";
				echo $pantalla->get_etiqueta();
				echo "&nbsp;&nbsp;</div>";
				echo "<div style='border: none;'>";
				if ($actual) {
					echo $separador_actual;
				} else {
					echo $separador;
				}
				echo "</div>";
				$paso ++;
			}
		} else {
			parent::generar_toc_wizard();
		}
	}

	/**
	 * Genera la barra con el título y los íconos
	 *
	 * @param string $titulo Título de la barra
	 * @param boolean $control_titulo_vacio Si el comp. no tiene titulo definido, ni se lo pasa por parametro, no grafica la barra
	 * @param string $estilo Clase css a utilizar
	 */
	function generar_html_barra_sup($titulo = null, $control_titulo_vacio = false, $estilo = "")
	{
		if ($this->_mostrar_barra_superior) {
			$botonera_en_item = false;
			if (isset($this->_info_ci['botonera_barra_item']) && $this->_info_ci['botonera_barra_item']) {
				$botonera_en_item = true;
			}
			$botonera_sup = $this->hay_botones() && isset($this->_posicion_botonera) && ($this->_posicion_botonera == "arriba" ||
					$this->_posicion_botonera == "ambos") && !$botonera_en_item;
			$tiene_titulo = trim($this->_info["titulo"]) != "" || trim($titulo) != '';
			$fuerza_titulo = (isset($this->_info_cuadro) && $this->_info_cuadro['siempre_con_titulo'] == '1');
			if ($botonera_sup || !$control_titulo_vacio || $tiene_titulo || $fuerza_titulo) {
				if (!isset($titulo)) {
					$titulo = $this->_info["titulo"];
				}
				//---Barra de colapsado
				$colapsado = "";
				// Se colapsa cuando no hay botones o cuando hay pero no esta la botonera arriba
				$colapsado_coherente = (!$this->hay_botones() || ($this->hay_botones() && !$this->botonera_arriba()));
				if ($this->_info['colapsable'] && isset($this->objeto_js) && $colapsado_coherente) {
					$colapsado = "style='cursor: pointer; cursor: hand;' onclick=\"{$this->objeto_js}.cambiar_colapsado();\" title='Mostrar / Ocultar'";
				}
				echo toba::output()->get('ElementoInterfaz')->getInicioBarraSuperior($tiene_titulo, $botonera_sup, $estilo, $colapsado);
				//--> Botonera
				if ($botonera_sup) {
					$this->generar_botones();
				}
				echo toba::output()->get('ElementoInterfaz')->getContenidoBarraSuperior($titulo, $this->_info["descripcion"], $this->_modo_descripcion_tooltip, $this->_info['colapsable'], $colapsado_coherente, $this->objeto_js,$colapsado);
				$this->extras_cabecera();
				echo toba::output()->get('ElementoInterfaz')->getFinBarraSuperior();
			}

			//--- Descripcion con barra. Muestra una barra en lugar de un tooltip
			if (trim($this->_info["descripcion"]) != "" && !$this->_modo_descripcion_tooltip) {
				$tipo = isset($this->_info['descripcion_tipo']) ? $this->_info['descripcion_tipo'] : null;
				$this->generar_html_descripcion($this->_info['descripcion'], $tipo);
			}
			echo "<div id='{$this->_submit}_notificacion'>";
			foreach ($this->_notificaciones as $notificacion) {
				$this->generar_html_descripcion($notificacion['mensaje'], $notificacion['tipo']);
			}
			echo "</div>";
			$this->_notificaciones = array();
		}
	}

	function extras_cabecera()
	{
		if ($this->mostrar_extras_cabecera) {
			$codigo_operacion = guarani::get_id_operacion();
			if (!empty($codigo_operacion)) {
				$parametros_operacion = toba::consulta_php('co_parametros')->get_parametros_operacion($codigo_operacion);
				// En caso que haya parámetros intervinientes en la operación, se muestra un link para visualizar sus valores
				if (!empty($parametros_operacion)) {
					$datos_item = toba::solicitud()->get_datos_item();
					if (!empty($datos_item['item_nombre'])) {
						$nombre_operacion = $datos_item['item_nombre'];
					} else {
						$nombre_operacion = '';
					}

					$url_vinculo = toba::vinculador()->crear_vinculo(
						toba_proyecto::get_id(), 
						'41000154', 
						array('codigo_operacion' => $codigo_operacion, 'nombre_operacion' => utf8_encode($nombre_operacion), 'tm' => '1'), 
						array('celda_memoria' => 'popup_ver_params')
					);

					$url_vinculo = toba::escaper()->escapeHtmlAttr($url_vinculo);

					$javascript = "abrir_popup('info', '$url_vinculo', {'width': '1100', 'height': '600', 'scrollbars': '1', 'resizable': '1'})";

					$hay_conflicto = guarani::parametros()->hay_conflictos_en_parametros_operacion($codigo_operacion);
					if (!$hay_conflicto) {
						$color_texto = 'white';
						$icono = '';
						$tooltip = 'Visualizar parámetros de la operación';
					} else {
						$color_texto = 'yellow';
						$icono = toba_recurso::imagen_proyecto('bullet_error.png', true, null, null);
						$tooltip = 'Visualizar parámetros de la operación. ATENCIÓN: algunos parámetros presentan conflictos de definición, y se resolvió asumiendo el valor default.';
					}

					$texto = 'Ver parámetros';
					$vinculo = "<a style='color: $color_texto;' href='#' title='$tooltip' onclick=\"$javascript\">";
					$vinculo .= $texto;
					$vinculo .= '</a>';

					echo "<span class='ei-barra-sup-tit ei-barra-sup-desc' style='margin-left: 0px;'>$vinculo</span>\n";
					echo "<span class='ei-barra-sup-desc'>$icono</span>\n";
				}
			}
		}
	}

        /**
         * Genera (echo) JS para procesos en background.
         *
         * @param array $btns_proceso_finalizo          Botones de proceso finalizado.
         * @param array $elementos                      Array de identificadores de elementos (efs) del ei_formulario, por defecto array vacío.
         * @param array $eventos                        Array de identificadores de eventos del ei_formulario, por defecto array vacío.
         * @param array $relacion_elementos_eventos     Array con la relación entre elementos y eventos.
         * @param string $ei_formulario_nombre          Nombre del ei_formulario, por defecto 'resultados'.
         * @param string $mensaje                       Mensaje a mostrar.
         * @param bool $consultar_resultados_proceso    Si consulta el resultado del proceso, por defecto true
         *
         * @return void Genera (echo) JS para procesos en background.
         */
        function generar_js_procesos_bk($btns_proceso_finalizo = [], $elementos = [], $eventos = [], $relacion_elementos_eventos = [], $ei_formulario_nombre = 'resultados', $mensaje = '', $consultar_resultados_proceso = true)
        {

            $this->generar_js_barra_progreso();
            
            $this->generar_js_estado_proceso($btns_proceso_finalizo, $elementos, $consultar_resultados_proceso);

            // Si consulta el resultado del proceso
            if ($consultar_resultados_proceso) {
                $this->generar_js_resultados_proceso($elementos, $relacion_elementos_eventos, $ei_formulario_nombre, $mensaje);
            }
            
            $this->generar_js_carga_inicial($btns_proceso_finalizo, $eventos, $ei_formulario_nombre);
        }

        /**
         * Genera (echo) JS para la barra de progreso de procesos en background.
         *
         * @return void Genera (echo) JS para la barra de progreso de procesos en background.
         */
        function generar_js_barra_progreso()
        {
            echo "
                    {$this->objeto_js}.set_progreso = function(count)
                    {
                            var image = 360;
                            var width = -1 * ((image/200)*(100-count));
                            var img = document.getElementById('barra_progreso');
                            img.style.backgroundPosition = + width + 'px 0pt';
                            img.alt   = count + '%';
                            img.title = count + '%';

                            var txt = document.getElementById('texto_progreso');
                            txt.innerHTML = count + '%';
                    }
            ";
        }

        /**
         * Genera (echo) JS para el estado de procesos en background.
         *
         * @param array $btns_proceso_finalizo          Botones de proceso finalizado.
         * @param array $elementos                      Array de identificadores de elementos (efs) del ei_formulario, por defecto array vacío.
         * @param bool $consultar_resultados_proceso    Si consulta el resultado del proceso, por defecto true
         *
         * @return void Genera (echo) JS para el estado de procesos en background.
         */
        function generar_js_estado_proceso($btns_proceso_finalizo = [], $elementos = [], $consultar_resultados_proceso = true)
        {
            $timeout_estados = comunes::get_timeout_procesos_bk();

            echo "
                    var finalizo_proceso = false;

                    {$this->objeto_js}.consultar_estado_proceso = function()
                    {
                            // Hace un AJAX a la función 'ajax__estado_proceso' del CI.
                            this.ajax('estado_proceso', [], this, this.set_estado_proceso);
                    }

                    {$this->objeto_js}.set_estado_proceso = function(datos)
                    {
                            console.log('Respuesta del estado del proceso:');
                            console.log(datos);

                            // Si datos viene con valores
                            if (datos !== null) {

                                    if (datos['progreso'] == 100) {
                                            finalizo_proceso = true;
                                            this.set_progreso(100);
                                    }

                                    if (datos['mensaje'] != false) {
                                            //-- Actualizo el porcentaje completado
                                            this.set_progreso(parseInt(datos['progreso'], 10));
                                    }
                                    if (finalizo_proceso && datos['progreso'] == 100) {
                                    ";

            
            // Recorro los botones de proceso finalizado.
            foreach ($btns_proceso_finalizo as $btn_proceso_finalizo) {
                echo "
                                            {$this->objeto_js}.activar_boton('{$btn_proceso_finalizo}');
                ";
            }

            echo "
                                            {$this->objeto_js}.desactivar_boton('abortar');
                                    }
                            }

                            // Si el proceso finalizo
                            if (finalizo_proceso) {

                                    // Elimino el Timeout
                                    clearTimeout(timeout_estado_proceso);
            ";

            // Si consulta el resultado del proceso
            if ($consultar_resultados_proceso) {
            echo "
                                    
                                    // Se invoca por primera vez para comenzar a consultar el resultado del proceso.
                                    {$this->objeto_js}.consultar_resultados_proceso();

            ";
            }

            echo "
                            } else {// Si el proceso no finalizo

                                    // Vuelvo a consultar el estado del proceso
                                    var timeout_estado_proceso = setTimeout(\"{$this->objeto_js}.consultar_estado_proceso()\", $timeout_estados);

                            }
                    }
            ";
        }

        /**
         * Genera (echo) JS para el resultado de procesos en background.
         *
         * @param array $elementos                      Array de identificadores de elementos (efs) del ei_formulario, por defecto array vacío.
         * @param array $relacion_elementos_eventos     Array con la relación entre elementos y eventos.
         * @param string $ei_formulario_nombre          Nombre del ei_formulario, por defecto 'resultados'.
         * @param string $mensaje                       Mensaje a mostrar.
         * @param bool $consultar_resultados_proceso    Si consulta el resultado del proceso, por defecto true
         *
         * @return void Genera (echo) JS para el resultado de procesos en background.
         */
        function generar_js_resultados_proceso($elementos = [], $relacion_elementos_eventos = [], $ei_formulario_nombre = 'resultados', $mensaje = '')
        {

            $timeout_resultados = comunes::get_timeout_resultados_procesos_bk();

            echo "
                    {$this->objeto_js}.consultar_resultados_proceso = function()
                    {
                            // Hace un AJAX a la función 'ajax__resultados_proceso' del CI.
                            this.ajax('resultados_proceso', [], this, this.set_resultados_proceso);
                    }

                    {$this->objeto_js}.set_resultados_proceso = function(datos)
                    {
                            console.log('Respuesta del resultado del proceso:');
                            console.log(datos);

                            // Si datos es null vuelvo a intentar
                            if (datos === null) {

                                // Vuelvo a consultar el resultado del proceso
                                var timeout_resultados_proceso = setTimeout(\"{$this->objeto_js}.consultar_resultados_proceso()\", $timeout_resultados);

                            } else { // Sino los muestro en pantalla

                                // Elimino el Timeout
                                clearTimeout(timeout_resultados_proceso);
            ";

            // Reseteo el puntero interno del array
            reset($elementos);
            // Recorro los elementos (efs) del ei_formulario y les pongo la cantidad
            foreach ($elementos as $elemento) {
                echo "
                                // Si el valor del elemento es vacío le pongo un 0
                                this.dep('{$ei_formulario_nombre}').ef('{$elemento}').set_estado(datos['{$elemento}'] ? datos['{$elemento}'] : 0);
                ";
            }

            // Reseteo el puntero interno del array
            reset($relacion_elementos_eventos);
            // Recorro la relación de elementos con eventos
            foreach ($relacion_elementos_eventos as $elemento => $elemento_eventos) {
                // Recorro los eventos relacionados al elemento
                foreach ($elemento_eventos as $evento) {
                    echo "
                                if (datos['{$elemento}'] > 0) {
                                        {$this->objeto_js}.dep('{$ei_formulario_nombre}').activar_boton('{$evento}');
                                }
                    ";
                }
            }

            // Si se debe mostrar mensaje al finalizar
            if (!empty($mensaje)) {
                echo "
                                alert('{$mensaje}');
                ";
            }

            echo "
                            }
                    }
            ";
        }

        /**
         * Genera (echo) JS para la carga inicial de procesos en background.
         *
         * @param array $btns_proceso_finalizo          Botones de proceso finalizado.
         * @param array $eventos                        Array de identificadores de eventos del ei_formulario, por defecto array vacío.
         * @param string $ei_formulario_nombre          Nombre del ei_formulario, por defecto 'resultados'.
         *
         * @return void Genera (echo) JS para la carga inicial de procesos en background.
         */
        function generar_js_carga_inicial($btns_proceso_finalizo = [], $eventos = [], $ei_formulario_nombre = 'resultados')
        {
            echo "
                    {$this->objeto_js}.set_progreso(0);
                    // Se invoca por primera vez para comenzar a consultar el estado del proceso.
                    {$this->objeto_js}.consultar_estado_proceso();
            ";

            // Recorro los botones de proceso finalizado.
            foreach ($btns_proceso_finalizo as $btn_proceso_finalizo) {
                echo "
                    {$this->objeto_js}.desactivar_boton('{$btn_proceso_finalizo}');
                ";
            }

            // Recorro los eventos del ei_formulario y los desactivo
            foreach ($eventos as $evento) {
                echo "
                    {$this->objeto_js}.dep('{$ei_formulario_nombre}').desactivar_boton('{$evento}');
                ";
            }
        }
        
}