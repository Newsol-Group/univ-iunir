<?php
class guarani_tp_basico extends toba_tp_normal
{
	protected $version;
	protected $url;
	protected $menu_ini;
	protected $nro_menu = 0;
	protected $guarani_consumos_globales_js = array('basicos/validacion', 'basicos/varios');

	function __construct()
	{
		$this->menu_ini = toba_proyecto::get_path() . '/menu.ini';
		$this->set_menu();
		$this->version = toba::proyecto()->get_version();
	}

	protected function set_menu()
	{
		if (file_exists($this->menu_ini)) {
			$ini = new toba_ini($this->menu_ini);
			$this->nro_menu = $ini->get_datos_entrada('menu');
		}

		if ($this->nro_menu == 0) {
			$this->menu = toba::menu();
		} else {
			$clase = 'guarani_menu_' . $this->nro_menu;
			$this->menu = new $clase();
		}
	}

	protected function comienzo_cuerpo()
	{
		$this->comienzo_cuerpo_basico();
		echo "<div class='{$this->clase_encabezado}'>";
		$this->cabecera_aplicacion();
		if ($this->nro_menu == 0) {
			echo "<div class='wrapper-menu'>";
			$this->menu();
			echo "</div>";
		}
	}

	protected function cabecera_aplicacion()
	{

                //--- Cabecera
                echo "<div id='header' class='cabecera clearfix'>";
                
		//--- Logo
                echo "<div id='enc-logo' style='height: {$this->alto_cabecera};'>";
		$this->mostrar_logo();
		echo "<span style='color:#2B78BC; vertical-align: 100%'> v. " . toba::proyecto()->get_version() . "</span>";
		echo "</div>\n";
                //--- Fin Logo

		//--- Nombre Institución
                echo "<div id='nombre-institucion' style='height: {$this->alto_cabecera};'>";
		$inst = institucion::get_datos_gestionante();
		echo "<div class='guarani_cab guarani_cab_titulo'>Institución</div>";
		echo "<div class='guarani_cab' title='{$inst['nombre']}'>";
		echo $inst['nombre'];
		echo "</div>\n";
                echo "</div>\n";
                //--- Fin Nombre Institución

		//--- Logo Institución
		$logo = toba::proyecto()->get_www_pers('img/logo_institucion.jpg');
		if (file_exists($logo['path'])) {
			echo "<div id='enc-logo-institucion' style='height: {$this->alto_cabecera};'>" . toba_recurso::imagen($logo['url'], null, $this->alto_cabecera) . "</div>";
		}
                //--- Fin Logo institucion

                //--- Cuenta y APP Launcher
                $this->info_usuario_aplicaciones();
                //--- Fin Cuenta y APP Launcher

		echo "</div>\n";
                //--- Fin Cabecera
	}

	function cambio_perfil()
	{
		$filtro = "mdp_personas_grupo_acc.tipo_usuario = '".usuario::tipo_gestion."'";
		$perfiles = toba::consulta_php('co_usuarios')->get_grupos_acceso_usuario(toba::usuario()->get_id(), $filtro);
		if (guarani_arreglos::count($perfiles) > 1) {
			//-- Si hay al menos dos perfiles funcionales
			echo '<div class="enc-cambio-proy">';
			$datos = rs_convertir_asociativo($perfiles, array('grupo_acceso'), 'nombre');
			$actual = toba::memoria()->get_dato('usuario_perfil_funcional_seleccionado');
			echo toba_form::abrir('chng_profile', toba::vinculador()->get_url(toba_proyecto::get_id(), '2'));
			toba_manejador_sesiones::enviar_csrf_hidden();
			$etiqueta_dropdown = guarani_mensajes::get('800_perfil_dropdown', array($datos[$actual]));
			$on_change = "$('#chng_profile').submit();";
			echo guarani_form::jq_dropdown('js_dropdown_perfil', apex_sesion_qs_cambio_pf, $etiqueta_dropdown, $datos, $actual, $on_change, true, 'etiqueta_dropdown_perfil');
			echo toba_form::cerrar();
			echo '</div>';
		}
	}

	function barra_superior()
	{
		echo "<div id='barra_superior' class='barra-superior barra-superior-tit'>\n";
		$this->info_version();
		if ($this->nro_menu != 0) {
			echo "<div class='item-barra'><img style='vertical-align: bottom; cursor:pointer; float: left' id='menu_img' src='" . toba_recurso::imagen_proyecto('player_playlist.png') . "'   onClick='showMenu(event)'/><div class='item-barra-tit' id='barra_path_operacion'> &raquo;</div>";
			$this->menu();
			echo "</div>";
		}
		echo "<div class='item-barra'>";
		echo "<div class='item-barra-tit'>" . $this->titulo_item() . "</div>";
		$this->generar_ayuda();
		echo "</div>\n\n";
		
		$this->cambio_perfil();
	}

	function cabecera_html()
	{
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo "<HTML>\n";
		echo "<HEAD>\n";
		echo "<title>" . $this->titulo_pagina() . "</title>\n";
		$this->encoding();
		$this->plantillas_css();
		$this->estilos_css();
		toba_js::cargar_consumos_basicos();
		foreach ($this->guarani_consumos_globales_js as $consumo_js) {
			echo toba_js::incluir(toba_recurso::url_proyecto() . "/js/$consumo_js.js");
		}
		
		// Se agrega jQuery Bootstrap-style Dropdowns JS
		echo toba_js::incluir(toba_recurso::url_proyecto() . "/jquery-dropdown-2.0.3/jquery.dropdown.min.js");
		
		// Se agrega timetable.js
		echo toba_js::incluir(toba_recurso::url_proyecto() . "/js/timetable.js");
		
		echo "</HEAD>\n";
	}

	function plantillas_css()
	{
		
		// Se agrega jQuery Bootstrap-style Dropdowns CSS
		$url_jquery_ui_css = toba_recurso::url_proyecto() . "/jquery-dropdown-2.0.3/jquery.dropdown.min.css";
		echo "<link href='$url_jquery_ui_css' rel='stylesheet' type='text/css' media='screen'/>\n";
		
		if (isset($this->menu)) {
			$estilo = $this->menu->plantilla_css();
			if ($estilo != '') {
				$proyecto = toba_proyecto::get_id();
				$path = toba::instancia()->get_path_proyecto($proyecto) . "/www/css/$estilo.css";
				if (file_exists($path)) {
					$url = toba_recurso::url_proyecto($proyecto) . "/css/$estilo.css";
					echo "<link href='$url' rel='stylesheet' type='text/css' media='screen'/>\n";
					if (toba::proyecto()->personalizacion_activa()) {
						$www = toba::proyecto()->get_www_pers("css/$estilo.css");
						if (file_exists($www['path'])) {
							echo "<link href='{$www['url']}' rel='stylesheet' type='text/css' media='screen'/>\n";
						}
					}
					$path = toba::instancia()->get_path_proyecto($proyecto) . "/www/css/" . $estilo . "_hack_ie.css";
					if (file_exists($path)) {
						$url = toba_recurso::url_proyecto($proyecto) . "/css/" . $estilo . "_hack_ie.css";
						echo "<!--[if lt IE 8]>\n";
						echo "<link href='$url' rel='stylesheet' type='text/css' media='screen'/>\n\n";
						echo "<![endif]-->\n";
					}
				} else {
					echo toba_recurso::link_css($estilo, 'screen', false);
					if ($estilo == 'listmenu_horizontal') {
						$url = toba_recurso::url_proyecto($proyecto) . "/css/fix_listmenu_horizontal.css";
						echo "<link href='$url' rel='stylesheet' type='text/css' media='screen'/>\n";
					}
				}
			}
		}
		echo toba_recurso::link_css('toba', 'screen');
		echo toba_recurso::link_css('toba_impr', 'print');
		$ico = toba_recurso::imagen_proyecto('favicon.ico');
		echo '<link rel="icon" href="' . $ico . '" type="image/x-icon" /><link rel="shortcut icon" href="' . $ico . '" type="image/x-icon" />';
		
		// Se agrega timetable.css
		$url_timetable_css = toba_recurso::url_proyecto() . "/css/timetable.css";
		echo "<link href='$url_timetable_css' rel='stylesheet' type='text/css'/>\n";
		// Se agrega fontawesome.css
		$url_fontawesome_css = toba_recurso::url_proyecto() . "/css/fontawesome.min.css";
		echo "<link href='$url_fontawesome_css' rel='stylesheet' type='text/css' />\n";
	}

	function ocultar()
	{
		toba::memoria()->set_dato('guarani_tp_basico_visible', false);
	}

	function mostrar()
	{
		toba::memoria()->set_dato('guarani_tp_basico_visible', true);
	}

	function visible()
	{
		if (!toba::memoria()->verificar_acceso_menu() &&
				toba::memoria()->existe_dato('guarani_tp_basico_visible')) {
			return toba::memoria()->get_dato('guarani_tp_basico_visible');
		}
		return true;
	}

	//--------------------------------------------------------------------------

	function encabezado()
	{
		if ($this->visible()) {
			parent::encabezado();
		} else {
			$this->cabecera_html();
			$this->comienzo_cuerpo_basico();
		}
	}

	function pre_contenido()
	{
		if ($this->visible()) {
			parent::pre_contenido();
		}
	}

	function post_contenido()
	{
		if ($this->visible()) {
			parent::post_contenido();
		}
	}

	function mostrar_logo()
	{
		// Link a la operación "Inicio".
		$proyecto = toba::proyecto()->get_id();
		$item = '2';
		$js = "return toba.ir_a_operacion(\"$proyecto\", \"$item\", false)";
		$img = toba_recurso::imagen_proyecto('logo.png', true);
		echo "<a href='#' onclick='$js'>$img</a>";
	}

	protected function comienzo_cuerpo_basico()
	{
		echo "<body>\n";
		$cerrar = toba_recurso::imagen_toba('nucleo/cerrar_ventana.gif', false);
		toba_js::cargar_consumos_globales(array('basicos/tipclick'));
		echo "\n<div id='overlay'>";
		echo "<div id='overlay_contenido'></div></div>";
		$wait = toba_recurso::imagen_toba('wait.gif');
		echo "<div id='div_toba_esperar' class='div-esperar' style='display:none'>";
		echo "<img src='$wait' style='vertical-align: middle;' alt='' /> Procesando...";
		echo "</div>\n";

		$logo = toba_recurso::imagen_proyecto('logo.png', false);
		echo "<div id='capa_espera'>
				<div>
					<img class='overlay-cerrar' title='Cerrar ventana' src='$cerrar' onclick='mostrar_esperar()' />
					<img src='$logo' /><p>Procesando. Por favor aguarde...</p><img src='$wait'>
				</div>
			</div>
		";
	}

	//--------------------------------------------------------------------------
	//---- Ayuda ---------------------------------------------------------------
	//--------------------------------------------------------------------------

	protected function generar_ayuda()
	{
		$mensaje = toba::mensajes()->get_operacion_actual();
		if (isset($mensaje)) {
			if (strpos($mensaje, ' ') !== false) { //Detecta si es una url o un mensaje completo
				$desc = toba_parser_ayuda::parsear($mensaje);
				$ayuda = toba_recurso::ayuda(null, $desc, 'item-barra-ayuda', 0);
				echo "<div $ayuda>";
				echo toba_recurso::imagen_toba("ayuda_grande.gif", true);
				echo "</div>";
			} else {
				if ($this->es_documentacion_guarani($mensaje)) {
					$mensaje = $this->get_link_documentacion($mensaje);
				} elseif (!toba_parser_ayuda::es_texto_plano($mensaje)) {
					// Versión resumida
					$mensaje = toba_parser_ayuda::parsear($mensaje, true);
				}
				$js = "abrir_popup('ayuda', '$mensaje', {width: 1250, height: 600, scrollbars: 1})";
				echo "<a class='barra-superior-ayuda' href='#' onclick=\"$js\" title='Abrir ayuda'>" . toba_recurso::imagen_toba("ayuda_grande.gif", true) . "</a>";
			}
		}
	}

	function get_link_documentacion($mensaje)
	{
		// Se obiene la versión del proyecto
		$version = toba::proyecto()->get_version();
		// Se concatena el tercer dígito ('.0') a los dos iniciales de la versión para construir la url
		$version = $version->get_release() . '.0';
		return 'http://documentacion.siu.edu.ar/wiki/SIU-Guarani/Version' . $version . '/' . $this->get_parte_documentacion_guarani($mensaje);
	}

	protected function es_documentacion_guarani($mensaje)
	{
		return guarani_texto::contiene_subcadena($mensaje, 'doc::');
	}

	protected function get_parte_documentacion_guarani($mensaje)
	{
		return str_replace('doc::', '', $mensaje);
	}

	protected function info_usuario_aplicaciones()
	{		
		$usuario = toba::usuario()->get_id();
		if ($usuario != toba_usuario_no_autenticado::NO_AUTENTICADO) {
			$persona = toba::consulta_php('co_personas')->get_persona_x_usuario($usuario);
			$aplicaciones = array();
			$usuario_foto = toba_recurso::imagen_toba('usuarios/foto_perfil_defecto.png');
			if (toba::instalacion()->vincula_arai_usuarios()) {
				$atributos_usuario = toba::manejador_sesiones()->get_autenticacion()->get_atributos_usuario();
				$atributos_usuario['appLauncherData'] = ($atributos_usuario['appLauncherData'] ?? []);
				$appLauncherData = (empty($atributos_usuario['appLauncherData'])) ? [] : array_a_latin1(json_decode(current($atributos_usuario['appLauncherData']), true));
				if (isset($appLauncherData['aplicaciones'])) {
					$aplicaciones = $appLauncherData['aplicaciones'];
				}
				if (isset($appLauncherData['usuario_foto'])) {
					$usuario_foto = $appLauncherData['usuario_foto'];
				}
			} else {
				$datos_foto = toba::consulta_php('co_personas')->get_persona_foto($persona['persona']);
				
				if(isset($datos_foto['imagen'])) {
					$fp_imagen = $datos_foto['imagen'];
					$temp_nombre = md5(uniqid(time()));
					$temp_archivo = toba::proyecto()->get_www_temp($temp_nombre);
					//-- Se pasa el contenido al archivo temporal
					$temp_fp = fopen($temp_archivo['path'], 'w');
					stream_copy_to_stream($fp_imagen, $temp_fp);
					fclose($temp_fp);
					//--
					$usuario_foto = $temp_archivo['url'];
				}
			}
			$data = array(	
				'usuario_id' => texto_plano($usuario),
				'usuario_nombre' => texto_plano(ucwords($persona['nombres'].' '.$persona['apellido'])),
				'usuario_foto' => $usuario_foto,
				'perfil_url' => toba::vinculador()->get_url(toba_proyecto::get_id(), '32000047'),
				'aplicaciones' => $aplicaciones
			);
			
			// HTML app_launcher
			$url_base = toba_recurso::url_toba().'/js/packages/siu-js-app-launcher/';
			$html =  '<link rel="stylesheet" href="' . $url_base . 'css/font-awesome-4.4.0/css/font-awesome.min.css" type="text/css" />';
			$html .= '<link rel="stylesheet" href="' . $url_base .'css/app_launcher.css" type="text/css" />';
			$html .= toba_js::incluir($url_base . 'app_launcher.js');
			$html .= "<div id='enc-usuario' class='enc-usuario' style='height: {$this->alto_cabecera};'></div>";
			
			// Código inicialización app_launcher
			$js = toba_editor::modo_prueba() ? 'window.close()' : 'salir()';
			$codigo_inicializacion = 'appLauncher.init({
										container: "#enc-usuario",
										data: '.json_encode(array_a_utf8($data)).',
										js_salir: function() {'.$js.'}});
										$("#boton_cuenta").removeAttr("target");';

			$html .= toba_js::ejecutar($codigo_inicializacion);
			echo $html;
		}
	}
}