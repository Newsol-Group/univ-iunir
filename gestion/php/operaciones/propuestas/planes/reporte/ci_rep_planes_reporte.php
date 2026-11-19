<?php 
class ci_rep_planes_reporte extends ci_navegacion_planes
{
	public $s__clave = 'plan_version';

	function ini()
	{
		toba::memoria()->desactivar_reciclado();
		parent::ini();
	}

	function conf__edicion(toba_ei_pantalla $pantalla){}
	
	function conf__seleccion()
	{
		$this->cn()->set_nuevo();
	}
	
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		// El evento Versionar que se muestra en todas las operaciones del módulo de Planes en el cuadro, se oculta sólo para el reporte.
		$cuadro->evento('versionar')->ocultar();
		parent::conf__cuadro($cuadro);
	}
	
	function get_datos($filtro) {
		return toba::consulta_php("co_planes_reportes")->get_listado($filtro);
	}
		
	function vista_xml($inicial = false, $xmlns = NULL) {
		$datos = $this->cn()->get_plan_version();
		$datos = $datos[0];
		$this->xml_set_titulo("Reporte de Plan de Estudios - ".$datos['nombre']);
		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);
		$edi = $this->dep('ci_plan_reporte');
		//$anchos = array('codigo'=>'1.5cm','tipo_de_actividad'=>'1.5cm','modalidad'=>'2cm','anio_de_cursada'=>'1cm', 'periodo_de_cursada'=>'2.5cm','creditos'=>'1.5cm', 'horas_semanales'=>'1.2cm', 'promediable'=>'1.7cm', 'permite_promocion'=>'1.8cm', 'requiere_cursada'=>'1.5cm', 'requiere_examen'=>'1.5cm');
		switch($edi->get_id_pantalla()) {
			case "plan":
				$this->xml_set_subtitulo("Detalles de plan de estudios");
				$xml = parent::vista_xml();
				break;
			case "actividades":
				$this->xml_set_orientacion('landscape');
				$this->xml_set_subtitulo("Detalles de actividades");
				//$edi->dep('cuadro_actividades')->xml_set_columna_ancho($anchos);
				$xml = parent::vista_xml();
				break;
			case "modulos":
				$this->xml_set_orientacion('landscape');
				$this->xml_set_subtitulo("Detalles de módulos");
				$modulos = $this->get_id_arbol($this->cn()->get_arbol_modulos(null));
				$xml = $this->xml_get_tag_inicio();
				if(empty($modulos)) {
					$xml .= $this->xml_texto('No se encontraron módulos asociados');
				} else {
				
					$edi->dep('cuadro_modulos')->set_titulo('');
					//$edi->dep('cuadro_modulos')->xml_set_columna_ancho($anchos);
					/*

					Ejemplo para extender xsl a travï¿½s de namespace. 
						Se agrega xmlns:pro='http:www.siu.edu.ar/proyecto' al tag del elemento del cuadro.
						En el xsl del proyecto se define el template del objeto pro:tabla (tag del elemento cuadro)

					*/
					//$edi->dep('cuadro_modulos')->xml_set_ns('pro','http://www.siu.edu.ar/proyecto');
					$edi->dep('formulario_modulos')->xml_set_ef_no_procesar(array('nombre', 'forma_aprobacion'));
					$count = 1;
					foreach($modulos as $modulo) {
					//	$salida->texto($modulo->get_nombre());
						$frm = $this->cn()->get_modulo_detalle($modulo);
						$edi->dep('formulario_modulos')->set_datos($frm[0]);
						$edi->dep('cuadro_modulos')->set_datos($this->cn()->get_actividades($modulo));
						$edi->dep('formulario_modulos')->xml_set_titulo($count.'. '.$edi->dep('formulario_modulos')->ef('nombre')->get_estado());
						$xml .= $edi->dep('formulario_modulos')->vista_xml();
						$xml .= $edi->dep('cuadro_modulos')->vista_xml();
						$count++;
					}
				}
				$xml .= $this->xml_get_tag_fin();
				break;
			case "mat_genericas":
				$this->xml_set_orientacion('landscape');
				$this->xml_set_subtitulo("Detalles de materias genéricas");
				$mgs = $this->cn()->get_sel_mg();
				$xml = $this->xml_get_tag_inicio();

				if(empty($mgs)) {
					$xml .= $this->xml_texto('No se encontraron materias asociadas');
				} else {				
					$edi->dep('cuadro_activ_mg')->set_titulo('');
					//$edi->dep('cuadro_activ_mg')->xml_set_columna_ancho($anchos);
					$edi->dep('formulario_mg')->xml_set_ef_no_procesar(array('nombre', 'forma_aprobacion'));
					$count = 1;
					foreach($mgs as $mg) {
						$frm = $this->cn()->get_modulo_detalle($mg['elemento']);
						$edi->dep('formulario_mg')->set_datos($frm[0]);
						$edi->dep('cuadro_activ_mg')->set_datos($this->cn()->get_actividades($mg['elemento'], 'false', 'true'));
						$edi->dep('formulario_mg')->xml_set_titulo($count.'. '.$edi->dep('formulario_mg')->ef('nombre')->get_estado());
						$xml .= $edi->dep('formulario_mg')->vista_xml();
						$xml .= $edi->dep('cuadro_activ_mg')->vista_xml();
						$count++;
					}
				}
				
				$xml .= $this->xml_get_tag_fin();
				break;
			case "certificados":
				$this->xml_set_subtitulo("Detalles de certificados");
				$certificados = $this->cn()->get_certificados();
				$xml = $this->xml_get_tag_inicio();
				$cc = 1;
				$tmp = $this->cn()->get_certificados();
				foreach($tmp as $cert) {
					$xml .= $this->xml_texto_fop($cc.'. '.$cert['nombre'], array('margin-top'=>'1cm', 'font-size'=>'9pt', 'text-decoration'=>'underline'));
					$arb = $this->cn()->get_arbol_certificado($cert['certificado']);
					$oc = 1;
					foreach($arb[0]->get_hijos() as $grupo) {
						$xml .= $this->xml_texto_fop($cc.'.'.$oc.'. Opción '.$oc, array('margin-top'=>'0.3cm', 'margin-left'=>'0.2cm'));
						$hc=1;
						foreach($grupo->get_hijos() as $hijo) {
							$nom = $hijo->get_nombre_largo();
							$cond = ($nom != $hijo->get_condicion())?' - '.$hijo->get_condicion():'';
							$xml .= $this->xml_texto_fop($cc.'.'.$oc.'.'.$hc.'. '.$nom.$cond, array('margin-top'=>'0.3cm', 'margin-bottom'=>'0.3cm', 'margin-left'=>'0.4cm'));
							//$xml .= $this->xml_texto(print_r($hijo,true));
							$hc++;	
						}
						$oc++;
					}
					//
					$cc++;	
				}
				$xml .= $this->xml_get_tag_fin();
				break;
			case "correlativas":
				$this->xml_set_subtitulo("Detalles de correlativas");
				$tmp = $this->cn()->get_actividades_restringidas();
				$xml = $this->xml_get_tag_inicio();
				$acts = array();
				// Ordeno los datos
				foreach ($tmp as $acts_tmp)
				{
					$clave = $acts_tmp['elemento'] . '_';
					if (!is_null($acts_tmp['elemento_padre'])) {
						$clave .= $acts_tmp['elemento_padre'];
					}
					if (!isset($acts[$clave])) {
						$acts[$clave]['nombre'] = $acts_tmp['nombre'];
						$acts[$clave]['codigo'] = $acts_tmp['codigo'];
					}
					if (!isset($acts[$clave]['tipos'][$acts_tmp['tipo']])) {
						$acts[$clave]['tipos'][$acts_tmp['tipo']]['descripcion'] = $acts_tmp['desc_tipo'];
					}
					if (!isset($acts[$clave]['tipos'][$acts_tmp['tipo']]['grupos'][$acts_tmp['grupo_restriccion']])) {
						$acts[$clave]['tipos'][$acts_tmp['tipo']]['grupos'][$acts_tmp['grupo_restriccion']]['nombre'] = $acts_tmp['opciones']; 
					}
					if (!isset($acts[$clave]['tipos'][$acts_tmp['tipo']]['grupos'][$acts_tmp['grupo_restriccion']]['requisitos'][$acts_tmp['requisito']])) {
						$acts[$clave]['tipos'][$acts_tmp['tipo']]['grupos'][$acts_tmp['grupo_restriccion']]['requisitos'][$acts_tmp['requisito']]['nombre'] = $acts_tmp['requisito_nombre']; 
					}
					$acts[$clave]['tipos'][$acts_tmp['tipo']]['grupos'][$acts_tmp['grupo_restriccion']]['requisitos'][$acts_tmp['requisito']]['condiciones'][$acts_tmp['condicion_cumplimiento']] = $acts_tmp['condicion'];
					$acts[$clave]['subtipo'] = $acts_tmp['entidad_tipo'];
					$acts[$clave]['padre'] = $acts_tmp['elemento_padre'];
				}
				// Reacomodo
				$act_temp = $acts;
				foreach ($act_temp as $kk=>$data) {
					if ((!is_null($data['padre']))) {
						$this->ubicar_hijo_arbol($acts, $data['padre'], array($kk => $data));
						unset($acts[$kk]);
					}					
				}
				// Armo el XML a imprimir
				$xml .= $this->get_xml_correlativas($acts);
				//var_dump($xml);die;				
				$xml .= $this->xml_get_tag_fin();
				break;
			case "grafico":
				$gd = $edi->dep('formulario_esq')->get_datos();
				$this->xml_set_subtitulo('Gráfico de Correlativas para '.(($gd['tipo_correlativa']=='A')?'Aprobar':'Cursar'));
				/*if($gd['certificado'])
				{
					$crt = $this->cn()->get_certificado_detalle($gd['certificado']);
					$crt = ($crt[0])?$crt[0]['tipo_certificado'].": ".$crt[0]['nombre']:'';
				}
				else
				{
					$crt = "";
				}
				 
				$salida->texto($crt,9,array('justification' => 'center'));
				$salida->salto_linea();
				$esq = $edi->dep('esquema');
				$g = $edi->conf__esquema($esq);
				$tmpfile = $esq->generar_archivo($g, 'jpg');
				//$salida->texto(toba::instalacion()->get_path_temp()."/".$tmpfile);
				$salida->insertar_imagen(toba::instalacion()->get_path_temp()."/".$tmpfile,null,$salida->get_ancho(100));*/
				$xml = parent::vista_xml();
				break;
		}
		return $xml;
	}
	
	function ubicar_hijo_arbol(&$arbol, $id_padre, $hijo) {
		$clave_hijo = current(array_keys($hijo));
		foreach ($arbol as $kk=>$data) {
			$clave_array = explode('_', $kk);
			if ($id_padre == $clave_array[0]) {
				$arbol[$kk]['hijos'][$clave_hijo] = $hijo[$clave_hijo];
				return;
			} else {				
				if (isset($data['hijos'])) {
					$this->ubicar_hijo_arbol($arbol[$kk]['hijos'], $id_padre, $hijo);		
				}
			}
		}
	}
	
	function get_xml_correlativas(&$datos, $ident=0)
	{
		$xml = '';
		$ident = $ident +2;
		$ac = 1;
		foreach ($datos as $k=>$act) {
			if (isset($act['hijos'])) {
				$xml .= $this->xml_texto_fop(strtoupper($act['nombre']), array('margin-top'=>'0.6cm', 'font-size'=>'12pt', 'text-decoration'=>'underline', 'margin-left'=> '0.' . $ident . 'cm'));
				$xml .= $this->get_xml_correlativas($act['hijos'], $ident);
			} else {
				$xml .= $this->xml_texto_fop($ac.'. ('.$act['codigo'].') - '.$act['nombre'], array('margin-top'=>'0.8cm', 'font-size'=>'9pt', 'text-decoration'=>'underline', 'margin-left'=> '0.' . $ident . 'cm'));
				$tc = 1;
				foreach ($act['tipos'] as $tipo) 
				{
					if (is_null($tipo['descripcion'])) {
						$xml .= $this->xml_texto_fop('Sin Correlativas', array('margin-top'=>'0.3cm', 'margin-left'=> '0.' . ($ident+2) . 'cm'));							
					} else {							
						$xml .= $this->xml_texto_fop($ac.'.'.$tc.'. '.$tipo['descripcion'], array('margin-top'=>'0.3cm', 'margin-left'=> '0.' . ($ident+2) . 'cm'));
						$gc = 1;
						foreach ($tipo['grupos'] as $grupo)
						{
							$xml .= $this->xml_texto_fop($ac.'.'.$tc.'.'.$gc.'. '.$grupo['nombre'],array('margin-top'=>'0.3cm', 'margin-bottom'=>'0.3cm', 'margin-left'=> '0.' . ($ident+4) . 'cm'));
							$rc = 1;
							foreach ($grupo['requisitos'] as $requisito)
							{
								foreach($requisito['condiciones'] as $condicion)
								{
									$xml .= $this->xml_texto_fop($ac.'.'.$tc.'.'.$gc.'.'.$rc.'. '.$requisito['nombre']." - ".$condicion,array('margin-left'=> '0.' . ($ident+6) . 'cm'));
									$rc++;	
								}
							}
							$gc++;
						}
					}
					$tc++;
				}
				$ac++;
				
			}
		}
		return $xml;
	}
	
	function get_id_arbol(toba_nodo_arbol $nodo) {
		$id[] = $nodo->get_id();
		if(!$nodo->es_hoja()) {
			foreach($nodo->get_hijos() as $hijos) {
				$hid = $this->get_id_arbol($hijos);
				$id = array_merge($id,$hid);
			}
		}
		return $id;
	}
	
	function extender_objeto_js() {
		echo "
		{$this->objeto_js}.evt__pdf = function()
		{
			window.open(vinculador.get_url(null,null,'vista_xslfo', null, [{$this->objeto_js}._id])); 
			//{$this->objeto_js}.exportar_pdf();
			return false;
		}
		";
	}

	function evt__cancelar()
	{
		$this->dep('ci_plan_reporte')->disparar_limpieza_memoria();
		parent::evt__cancelar();
	}
}