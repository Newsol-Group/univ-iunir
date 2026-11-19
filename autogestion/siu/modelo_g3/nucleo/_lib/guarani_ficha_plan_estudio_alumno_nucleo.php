<?php
class guarani_ficha_plan_estudio_alumno_nucleo
{
	protected $pdf_hoja = "A4";
	protected $pdf_encabezado = array("", "");
	protected $pdf_encabezado_img = array("img" => "", "width" => "");
	protected $pdf_fuente_texto = 10;
	protected $pdf_fuente_titulo = 10;
	protected $pdf_fuente_subtitulo = 12;
	protected $tiene_encabezado = true;
	protected $tiene_encabezado_long = true;
	protected $encabezado_reporte = "";
	protected $nombre_archivo = "plan_estudios";
	protected $columnas_reporte;
	protected $alumno;
	protected $alumno_nombre;
	protected $universidad;
	protected $plan;
	protected $plan_version;
	protected $legajo;
	protected $pdf;
	protected $label;
	protected $logo;
	protected $ver_optativas_en_estructura_plan;
	protected $tratar_generica_como_materia = false;
	protected $contenido_plan_version;
	protected $historia_academica;

	function __construct($alumno, $plan_version, $legajo)
	{
		$this->alumno = $alumno;
                $this->alumno_nombre = $this->utf8_encode(toba::consulta_php("co_alumnos")->get_alumno_nombre($this->alumno));
		$this->legajo = $legajo;
		$this->plan_version = $plan_version;
		$this->pdf = new plan_estudio_alumno_PDF();
		$this->pdf->set_font();
		$this->datos_interfaz();
		$this->encabezado();
		$this->agregar_genericas_materias();
		$this->agregar_certificados();
	}

	function generar_pdf()
	{
		ob_clean(); //Limpia el buffer en la salida del php
		return $this->pdf->generar(false);
	}

	function encabezado()
	{
		$this->pdf->set_options(['showHeadings' => 1, 'protectRows' => 1, 'maxWidth' => 800, 'width' => 800, 'fontSize' => $this->pdf_fuente_texto, 'shadeCol' => [0.8, 0.8, 0.8], 'cols' => []]);
		$this->pdf->set_text_options(['left' => 5, 'top' => 20, 'maxWidth' => 800, 'width' => 800]);
		$this->pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);
		$this->pdf->set_pdf_fuente_titulo($this->pdf_fuente_titulo);
		$this->pdf->set_pdf_fuente_subtitulo($this->pdf_fuente_subtitulo);
		$this->pdf->set_tiene_encabezado($this->tiene_encabezado);
		$this->pdf->set_tiene_encabezado_long($this->tiene_encabezado_long);
		$this->pdf->set_nombre_archivo('plan_estudios');

		$datos_encabezado = [
			'cant_columnas' => 2,
			'datos' => [
				'<b>' . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_alumno')) . ':</b> ' . $this->alumno_nombre,
				'<b>' . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_legajo')) . ':</b> ' . $this->legajo,
				'<b>' . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_propuesta')) . ':</b> (' . $this->utf8_encode($this->plan['propuesta_codigo']) . ') ' . $this->utf8_encode($this->plan['propuesta_nombre']),
				'<b>' . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_plan_nombre')) . ':</b> (' . $this->utf8_encode($this->plan['plan_nombre']) . ') ',
				'<b>' . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_plan_version')) . ':</b> ' . $this->utf8_encode($this->plan['version_nombre'])
			]
		];

		$this->pdf->set_encabezado_tabla($datos_encabezado);

		$this->pdf_encabezado = [$this->universidad, "<b><u>" . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_titulo')) . "</b></u>", 180];
		$this->pdf->set_encabezado($this->pdf_encabezado[0], $this->pdf_encabezado[1], $this->pdf_encabezado[2]);
	}

	function agregar_genericas_materias()
	{
		if ($this->contenido_plan_version['modulos_genericas'] || $this->contenido_plan_version['materias']) {
			foreach ($this->contenido_plan_version['modulos_genericas'] as $modulo_generica) {

				$this->agregar_modulo_generica_pdf($modulo_generica);
			}

			if (isset($this->contenido_plan_version['materias'])) {

				$this->pdf->dejar_espacio(-20);
                $materias_normalizadas = $this->normalizar_nombre_materias($this->contenido_plan_version['materias']);
				$this->pdf->agregar_tabla($materias_normalizadas, $this->get_columnas_reporte());
			}
		}

		$this->pdf->dejar_espacio(-20);
	}

	function agregar_certificados()
	{
		if (comunes::es_interfaz_gestion()) {
			$certificados_obtenidos = toba::consulta_php("co_alumnos")->get_certificados_obtenidos($this->alumno);
		} else {
			$historia_academica = siu\guarani::historia_academica();
			$certificados_obtenidos = $historia_academica->get_certificados_obtenidos();
		}


		foreach ($certificados_obtenidos as $certificado) {

			$promedio_con_aplazos = number_format($certificado['promedio_con_aplazos'], 2);
			$promedio_sin_aplazos = number_format($certificado['promedio_sin_aplazos'], 2);

			$this->pdf->agregar_texto("<b>" . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_titulo_certificado')) . ":</b> " . $certificado['nombre']);
			$this->pdf->agregar_texto("<b>" . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_promedio_con_aplazos')) . ":</b> " . $promedio_con_aplazos);
			$this->pdf->agregar_texto("<b>" . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_promedio_sin_aplazos')) . ":</b> " . $promedio_sin_aplazos);
			$this->pdf->agregar_texto("<b>" . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_estado_en_tramite')) . ":</b> " . $certificado['estado_actual_tramite']);
			$this->pdf->dejar_espacio(-20);
		}

		// Si el parámetro 'pro_ver_optativas_en_estructura_plan' es 'Si' muestro la siguiente leyenda
		if ($this->ver_optativas_en_estructura_plan == \comunes::si) {
			$this->pdf->agregar_texto("<b>(*):</b> " . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_optativa_compartida_y_asignada_esa_generica_leyenda')));
			$this->pdf->agregar_texto("<b>(-):</b> " . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_optativa_compartida_y_asignada_otra_generica_leyenda')));
			$this->pdf->agregar_texto("<b>(!):</b> " . $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_optativa_compartida_y_sin_asignar_generica_leyenda')));
		}
	}

	function datos_interfaz()
	{
		//Verificar de imprimir logo            
		if (comunes::es_interfaz_gestion()) {

			$this->plan = toba::consulta_php("co_planes")->get_planes_versiones_datos($this->plan_version);
			$this->universidad = toba::consulta_php("co_instituciones")->info_institucion()['nombre'] . "\n";


			$archivo_logo = 'img/logo2.png';
			$path_img = toba::proyecto()->get_www()['path'];
			$desc_archivo = $path_img . $archivo_logo;
			//$this->path = $desc_archivo;
			$this->logo = $path_img . $archivo_logo;

			$this->pdf->set_encabezado_img($this->logo, '50');
			// Obtengo el parámetro 'pro_ver_optativas_en_estructura_plan'
			$this->ver_optativas_en_estructura_plan = toba::consulta_php('co_parametros')->get_estructura_parametro('pro_ver_optativas_en_estructura_plan')['tipo_de_dato'];

			// Si el parámetro 'pro_ver_optativas_en_estructura_plan' es 'No' trato las genéricas como materias comunes
			if ($this->ver_optativas_en_estructura_plan == \comunes::no) {
				$this->tratar_generica_como_materia = true;
			}
			$params = [
				'ver_optativas_en_estructura_plan' => $this->ver_optativas_en_estructura_plan,
				'tratar_generica_como_materia' => $this->tratar_generica_como_materia,
			];
			$this->contenido_plan_version = alumno::get_contenido_plan_version_con_historia_academica($this->alumno, $this->plan['plan_version'], $params);
		} else {

			$this->plan = toba::consulta_php("co_planes")->get_planes_versiones_datos($this->plan_version);
			$this->universidad = toba::consulta_php("co_instituciones")->info_institucion()['nombre'] . "\n";

			$this->logo = \SIU\Chulupi\kernel::localizador()->encontrar_img('www/img', 'logo_comprobante_con_bajada_nuevo.jpg');
			$this->pdf->set_encabezado_img($this->logo['path'], '103');

			// Obtengo el parámetro 'pro_ver_optativas_en_estructura_plan'
			$this->logo = \SIU\Chulupi\kernel::localizador()->encontrar_img('www/img', 'logo_comprobante_con_bajada_nuevo.jpg');
			$this->pdf->set_encabezado_img($this->logo['path'], '103');

			// Obtengo el parámetro 'pro_ver_optativas_en_estructura_plan'
			$this->ver_optativas_en_estructura_plan = toba::consulta_php('co_parametros')->get_estructura_parametro('pro_ver_optativas_en_estructura_plan')['tipo_de_dato'];
			// Si el parámetro 'pro_ver_optativas_en_estructura_plan' es 'No' trato las genéricas como materias comunes
			if ($this->ver_optativas_en_estructura_plan == \comunes::no) {
				$this->tratar_generica_como_materia = true;
			}

			$params = [
				'ver_optativas_en_estructura_plan' => $this->ver_optativas_en_estructura_plan,
				'tratar_generica_como_materia' => $this->tratar_generica_como_materia,
			];

			$this->contenido_plan_version = alumno_nucleo::get_contenido_plan_version_con_historia_academica($this->alumno, $this->plan['plan_version'], $params);
		}
	}

	protected function get_columnas_reporte()
	{

		if (!$this->columnas_reporte) {
			$this->columnas_reporte = [
				'actividad' => $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_actividad')),
				'tipo' => $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_tipo_materia')),
				'anio' => $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_ano_cursada')),
				'periodo' => $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_periodo')),
				'nota' => $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_nota')),
				'origen' => $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_origen')),
				'credito' => $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_credito')),
				'puntaje' => $this->utf8_encode(guarani_mensajes::get('800_Plan_estudio_puntaje')),
			];
		}
		return $this->columnas_reporte;
	}

	function agregar_modulo_generica_pdf($modulo_generica, &$label = "")
	{
		//Si el modulo/generica no esta vacio (tiene submodulos o materias)
		if ((isset($modulo_generica['modulos_genericas']) && $modulo_generica['modulos_genericas']) || (isset($modulo_generica['materias']) && $modulo_generica['materias'])) {

			$label_bis = $label . "/ {$modulo_generica['nombre']} ";

			if ($modulo_generica['entidad_subtipo'] == \elemento::subtipo_agrupador_modulos) {
				$label_bis = strtoupper(guarani_mensajes::get('800_Plan_estudio_modulo')) . ": " . $label_bis;
			} elseif ($modulo_generica['entidad_subtipo'] == \elemento::subtipo_materia_generica) {
				$label_bis = strtoupper(guarani_mensajes::get('800_Plan_estudio_materia_generica_bis')) . ": " . $label_bis;
			} elseif ($modulo_generica['entidad_subtipo'] == \elemento::subtipo_orientacion) {
				$label_bis = strtoupper(guarani_mensajes::get('800_Plan_estudio_orientacion')) . ": " . $label_bis;
			}

			if (isset($modulo_generica['materias']) && $modulo_generica['materias']) {

				$materias = $modulo_generica['materias'];

				// Si es una optativa contenida en una genérica le agrego info de optativa compartida si corresponde
				if ($modulo_generica['entidad_subtipo'] == \elemento::subtipo_materia_generica) {
					$materias = array_map([$this, 'agregar_info_optativa_compartida'], $materias);
				}

				$this->pdf->dejar_espacio(-40);
				$this->pdf->agregar_texto("<b>" . $this->utf8_encode($label_bis) . "</b>");
				$this->pdf->dejar_espacio(-20);
				//$materias = array_map([$this, 'utf8_en_texto'], $materias);
                $materias_normalizadas = $this->normalizar_nombre_materias($materias);
                
				$this->pdf->agregar_tabla($materias_normalizadas, $this->get_columnas_reporte());
			}

			if (isset($modulo_generica['modulos_genericas']) && $modulo_generica['modulos_genericas']) {

				$label_bis = $this->label . "/ {$modulo_generica['nombre']} ";
				foreach ($modulo_generica['modulos_genericas'] as $modulo_generica_bis) {
					$this->agregar_modulo_generica_pdf($modulo_generica_bis, $label_bis);
				}
			}
		}
	}

	function agregar_info_optativa_compartida($materia, $reporte = 'pdf')
	{
		$icono = '';
		switch ($materia['optativa_compartida']) {
			case \elemento::no_es_optativa_compartida:
				$icono = '';
				break;
			case \elemento::es_optativa_compartida_asignada_a_esa_generica:
				$icono = ' (*)';
				break;
			case \elemento::es_optativa_compartida_asignada_a_otra_generica:
				$icono = ' (-)';
				break;
			case \elemento::es_optativa_compartida_sin_generica_asignada:
				$icono = ' (!)';
				break;
		}

		$materia['actividad'] = $materia['actividad'] . $icono;

		return $materia;
	}

	function utf8_en_texto($materia, $reporte = 'pdf')
	{

		$materia['actividad'] = $this->utf8_encode($materia['actividad']);
		$materia['nota'] = $this->utf8_encode($materia['nota']);
		$materia['origen'] = $this->utf8_encode($materia['origen']);

		return $materia;
	}

	function utf8_encode($string)
	{
		if (comunes::es_interfaz_gestion()) {
			$string = utf8_encode($string);
		}
		return $string;
	}
    
    function normalizar_nombre_materias($materias = []){
        $datos_normalizados = [];
//        ei_arbol($materias);die;
        foreach ($materias as $key => $value) {            
            $materias[$key]['actividad'] = $this->utf8_encode($value['actividad']);
            $materias[$key]['origen'] = $this->utf8_encode($value['origen']);
            $materias[$key]['notas'] = $this->utf8_encode($value['notas']);
            $materias[$key]['creditos'] = $this->utf8_encode($value['creditos']);
            $materias[$key]['periodo'] = $this->utf8_encode($value['periodo']);
            $materias[$key]['tipo'] = $this->utf8_encode($value['tipo']);
            $materias[$key]['nombre'] = $this->utf8_encode($value['nombre']);
            $datos_normalizados[] = $materias[$key];
        }
        return $datos_normalizados;
    }

}