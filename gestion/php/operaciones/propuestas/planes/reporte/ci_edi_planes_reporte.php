<?php
class ci_edi_planes_reporte extends guarani_pers_ci
{
	
	protected $s__materia_generica;
	protected $s__modulo_revision;
	protected $s__certificado;
	protected $s__act_modulo_certif;
	protected $s__act_correlativa;
	protected $s__grafico_correlativa = array('tipo'=>'A', 'posicion'=>'T');
	protected $s__gra_detalles;
	
	//-----------------------------------------------------------------------------------
	//---- DEPENDENCIAS -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- Pant. Plan de Estudios -------------------------------------------------------

	//El formato del carga debe ser array('id_ef' => $valor, ...)
	function conf__formulario(toba_ei_formulario $form)
	{
		$datos = $this->cn()->get_plan_version();
		$form->set_datos($datos[0]);
	}
	
	//---- Pant. actividades ------------------------------------------------------------
	
	
	function conf__cuadro_actividades(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_manejador_salida('html', 'cuadro_actividades_salida_html');
		$cuadro->set_datos($this->cn()->get_actividades());
	}
	
	//---- Pant. modulos ----------------------------------------------------------------
	
	function conf__cuadro_modulos(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_manejador_salida('html', 'cuadro_actividades_salida_html');
		if ($this->s__modulo_revision) {
			$cuadro->set_datos($this->cn()->get_actividades($this->s__modulo_revision));
		}
	}
	
	function conf__formulario_modulos(toba_ei_formulario $form)
	{
		if($this->s__modulo_revision) {
			$datos = $this->cn()->get_modulo_detalle($this->s__modulo_revision);
			$form->set_datos($datos[0]);
		}
	}
	
	function conf__arbol_modulos(toba_ei_arbol $arbol)
	{
		$elems[] = $this->cn()->get_arbol_modulos('modulos');
		$arbol->set_datos($elems);
		$arbol->set_todos_abiertos();
	}
	
	//---- Pant. materia genérica ---------------------------------------------------------
	
	function conf__cuadro_sel_mg(toba_ei_cuadro $cuadro) 
	{
		$cuadro->set_datos($this->cn()->get_sel_mg());
	}
	
	function evt__cuadro_sel_mg__seleccion($datos) 
	{
		$this->s__materia_generica = $datos['elemento'];
	}
	
	function conf__formulario_mg(toba_ei_formulario $form)
	{
		if($this->s__materia_generica) {
			$datos = $this->cn()->get_modulo_detalle($this->s__materia_generica);
			$form->set_datos($datos[0]);
		}
	}
	
	function conf__cuadro_activ_mg(toba_ei_cuadro $cuadro) 
	{
		$cuadro->set_manejador_salida('html', 'cuadro_actividades_salida_html');
		if($this->s__materia_generica) {
			$cuadro->set_datos($this->cn()->get_actividades($this->s__materia_generica, 'false', 'true'));
		}
	}
	
	//---- Pant. certificados ----------------------------------------------------------------
	
	function conf__arbol_certif(toba_ei_arbol $arbol) 
	{
		if($this->s__certificado) {
			$datos = $this->cn()->get_arbol_certificado($this->s__certificado);
			if($datos) 
				$arbol->set_datos($datos);
		}
	}
	
	function conf__cuadro_sel_certif(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->cn()->get_certificados());
	}
	
	function evt__cuadro_sel_certif__seleccion($datos)
	{
		$this->s__certificado = $datos['certificado'];
	}
	
	function conf__formulario_certif(toba_ei_formulario $form)
	{
		if($this->s__certificado) {
			$datos = $this->cn()->get_certificados($this->s__certificado);
			$form->set_datos($datos[0]);
		}
	}
	//---- Pant. correlativas ---------------------------------------------------------------
	function conf__arbol_plan(toba_ei_arbol $arbol)
	{
		$elementos = $this->cn()->get_requisitos_plan_version();
       	$arbol->set_datos(array($elementos));
       	$arbol->set_todos_abiertos();
		$arbol->set_ancho_nombres(60);
	}
	
	function conf__arbol_requisitos(toba_ei_arbol $arbol)
	{
		if($this->s__act_correlativa)
		{
			$datos = $this->cn()->get_restricciones($this->s__act_correlativa, $this->s__grafico_correlativa['tipo']);
			$arbol->set_datos($datos);
			$arbol->set_todos_abiertos();
			$arbol->set_ancho_nombres(50);
		}
	}
	
	function conf__formulario_correl(toba_ei_formulario $form)
	{
		 if($this->s__act_correlativa)
		 {
		 	$datos = $this->cn()->get_actividad_detalle($this->s__act_correlativa);
		 	if(isset($datos[0])) {
		 		$this->s__grafico_correlativa['nombre'] = $datos[0]['nombre'];
		 		//print_r($this->s__grafico_correlativa);
		 		$form->set_datos($this->s__grafico_correlativa);
		 	}
		 }
	}
	
	function evt__formulario_correl__modificacion($datos)
	{
		$this->s__grafico_correlativa = $datos;
	}
	
	function conf__esquema_correl(toba_ei_esquema $esquema)
	{
		if ($this->s__act_correlativa) {
			$restricciones = $this->cn()->get_restricciones_correlativas($this->s__act_correlativa, $this->s__grafico_correlativa['tipo']);
			$datos_plan = $this->cn()->get_datos_diagrama_plan($this->s__grafico_correlativa['tipo'], $this->s__act_correlativa);
			$elemento = $datos_plan['mapeo_elementos'][$this->s__act_correlativa];
			if (guarani_arreglos::count($datos_plan['transiciones'][$elemento]) > 0) {
				($this->s__grafico_correlativa['posicion'] == 'L') ? $posicion = '"LR"' : $posicion = '"TB"' ;
				$g =	'digraph "corr"
						{	graph [ fontname="Times New Roman" 
									fontsize = "10" 
									compound = true
									rankdir = ' . $posicion . '];

							node [  fontname="Times New Roman" 
									fontsize = "10"
									style="filled" ];

							edge [  fontname="Times New Roman" 
									fontsize = "10" ];

							subgraph cluster_cuadro 
							{ 
						 ';
				$g .= $elemento;
				$g .= ' [ label= "';
				$g .= $datos_plan['actividades'][$elemento]['nombre'];
				$g .= '" fillcolor = "#ffffff"]; ';
				$cont = 1;
				$partes; $e;
				foreach($restricciones as $restriccion) {
					$g .= 'subgraph cluster_cuadro_' . $cont . ' { bgcolor = "#d4f0db" label = "' . $restriccion[0] . '"; ';
					$rest_op_not = array();
					$mapeo_restriccion = array();
					for($i = 0 ; $i < guarani_arreglos::count($restriccion[1]) ; $i++) {
						$partes = explode("_", $restriccion[1][$i]);
						$subpartes = explode("*", $partes[0]);
						if($subpartes[2] != 'r') {
							$e = $datos_plan['mapeo_elementos'][$partes[1]];
							if($subpartes[2] == 'a') {
								$label = $datos_plan['actividades'][$e]['nombre'];
								$color = "#ffffff";
								$shape = "";
							} else {
								$label = $datos_plan['modulos'][$e]['nombre'];
								$color = "#dddddd";
								$shape = "shape=box";
							}
						} else {
							$e = $partes[1];
							$label = $datos_plan['requisitos'][$e];
							$color = "#c5d8eb";
							$shape = "shape=box";
						}
						
						$origen = $datos_plan['mapeo_elementos'][intval($partes[1])];
						$mapeo_restriccion[$origen] = $subpartes[1];						
						if(isset($partes[2])) { $rest_op_not[$e] = 1; } else { $rest_op_not[$e] = 0; }
						
						$g .= $e . $cont . ' [ label="' . $label . '" fillcolor = "'. $color . '" ' . $shape . '];';
					}
					$g .= "}"; 
					foreach ($datos_plan['transiciones'][$elemento] as $incidente) {
						if( ( in_array($incidente['origen'], array_keys($rest_op_not)) && 
							  ($incidente['operador_not'] == $rest_op_not[$incidente['origen']]) ) &&
							( isset($mapeo_restriccion[$incidente['origen']]) &&
							  ($mapeo_restriccion[$incidente['origen']] == $incidente['regla']) ) ) {
							$g .= $incidente['origen'] . $cont;
							$g .= " -> ";
							$g .= $incidente['destino'];
							$g .= ($incidente['operador_not']) ? ' [ color=red fontcolor=red label = "(No) ' : ' [ label = "';
							$g .= $incidente['nombre'];
							$g .= '" ];';
						}
					}
					$cont++;
				}
				$g .= "}}";
				return $g;
			}
		}
	}
	
	//------------- Pant. Grafico ----------------
	
	function conf__formulario_esq(toba_ei_formulario $form)
	{
		if($this->s__gra_detalles)
		{
			$form->set_datos($this->s__gra_detalles);
		}
	}
	
	function evt__formulario_esq__modificacion ($datos)
	{
		$this->s__gra_detalles = $datos;
	}
	
	function get_grafico_detalle()
	{
		return $this->s__gra_detalles;
	}
	
	function conf__esquema(toba_ei_esquema $esquema)
	{	
		$esquema->set_incluir_mapa(true);
		$datos_plan = $this->cn()->get_datos_diagrama_plan($this->s__gra_detalles['tipo_correlativa'] ?? null);
		(isset($this->s__gra_detalles['posicion']) && ($this->s__gra_detalles['posicion'] == 'L')) ? $posicion = '"LR"' : $posicion = '"TB"' ;
		
		$g = '
			digraph "corr"
			{	graph [ fontname="Times New Roman" 
						fontsize = "10" 
						rankdir = ' . $posicion . '
						compound = true  ]; 

				node [  fontname="Times New Roman" 
						fontsize = "10"
						style="filled" ];

				edge [  fontname="Times New Roman" 
						fontsize = "10" ];

				subgraph cluster_raiz 
				{ 
					label="Raíz del plan"; ';
		
		foreach ($datos_plan['modulos'] as $modulo) {
			$g .= "subgraph cluster_";
			$g .= $modulo['elemento_revision'];
			$g .= ' { bgcolor = "#dfdfdf" label = "'.$modulo['nombre_abreviado'].'"; ';
			$g .= $modulo['elemento_revision'];
			$g .= " [style=invis, height=0.0, width=0.0, margin=0.0, penwidth=0.0]; ";
			
			$contenido = $datos_plan['modulos_con_actividades'][$modulo['elemento_revision']];
			foreach($contenido as $actividad_contenida) {
				$nodo = $actividad_contenida['elemento_revision'];
				$g .= $nodo;
				$g .= ' [ fillcolor = "#ffffff" label= "';
				$g .= $datos_plan['actividades'][$nodo]['nombre_abreviado'];
				$g .= '" ]; ';
				$datos_plan['actividades'][$nodo]['pertenece_a_modulo'] = true;
			}
			$g .= "}";
		}
		
		foreach ($datos_plan['actividades'] as $actividad) {
			if(! $actividad['pertenece_a_modulo']) {
				$g .= $actividad['elemento_revision'];
				$g .= ' [ fillcolor = "#ffffff" label = "';
				$g .= $actividad['nombre_abreviado'];
				$g .= '" ]; ';
			}
		}

		foreach (array_keys($datos_plan['requisitos']) as $requisito) {
			$g .= $requisito;
			$g .= ' [ fillcolor = "#c5d8eb"  shape=box label = "';
			$g .= $datos_plan['requisitos'][$requisito];
			$g .= '" ]; ';
		}

		foreach ($datos_plan['transiciones'] as $incidentes_al_nodo) {
			foreach($incidentes_al_nodo as $incidente) {
				$g .= $incidente['origen']." -> ".$incidente['destino'];
				$g .= ($incidente['operador_not']) ? ' [ color=red fontcolor=red label = "(No) ' : ' [ label = "';
				$g .= $incidente['nombre'];
				$g .= '" ltail = ';
				if( array_key_exists($incidente['origen'], $datos_plan['modulos']) ) {
					$g .= "cluster_";
				}
				$g .= $incidente['origen'];
				$g .= " lhead = ";
				$g .= $incidente['destino'];
				$g .= ' ]; ';
			}
		}
		
		$g .= "}}";
		
		return $g;
	}
	
	function get_restricciones_act($elem, $tipo)
	{
		$texto = null;
		if ($elem instanceof plan_elemento_gui_actividad)
		{
			$res = $this->cn()->get_restricciones($elem->get_id(), $tipo);
			if(isset($res[0])) 
			{
				$texto = $res[0]->get_restriccion($elem->get_id(), $elem);
			}
		}
		else
		{
			foreach ($elem->get_hijos() as $hijo)
			{
				$texto .= $this->get_restricciones_act($hijo, $tipo);
			}
		}
		return $texto;
	}
	
	//------------ relleno de ef_combo ----------
	function get_certificados()
	{
		return $this->cn()->get_certificados();
	}
	//-----------------AJAX-----------------------
	
	function ajax__get_form_data($parametros, toba_ajax_respuesta $respuesta)
	{
		$this->es_ajax = true;
		$var = "s__".$parametros[3];
		if(!isset($this->$var) || $this->$var != $parametros[0]) {
			//echo "{$this->$var} {$parametros[0]}";
			$this->$var = $parametros[0];
		}
		/*
		$datos = $this->cn()->$parametros[1]($parametros[0]);
		$this->dep($parametros[2])->set_datos($datos[0]);*/
		$cnf = 'conf__'.$parametros[2];
		$this->$cnf($this->dep($parametros[2]));
		if(method_exists($this->dep($parametros[2]), 'ajax_generar_layout')) {
			$this->dep($parametros[2])->ajax_generar_layout();
		} else {
			$this->dep($parametros[2])->generar_layout();
		}
	}
	
	function ajax__get_cuadro_data($parametros, toba_ajax_respuesta $respuesta)
	{
		$this->es_ajax = true;
		$var = "s__".$parametros[3];
		if(!isset($this->$var) || $this->$var != $parametros[0]) {
			$this->$var = $parametros[0];
		}
		/*$datos = $this->cn()->$parametros[1]($parametros[0]);
		$this->dep($parametros[2])->set_datos($datos);*/
		$cnf = 'conf__'.$parametros[2];
		$this->$cnf($this->dep($parametros[2]));
		$this->dep($parametros[2])->generar_html();
	}

	function ajax__get_arbol_data($parametros, toba_ajax_respuesta $respuesta)
	{
		$this->es_ajax = true;
		$var = "s__".$parametros[3];
		if(!isset($this->$var) || $this->$var != $parametros[0]) {
			$this->$var = $parametros[0];
		}
		/*$datos = $this->cn()->$parametros[1]($parametros[0]);
		$this->dep($parametros[2])->set_datos($datos);*/
		$cnf = 'conf__'.$parametros[2];
		$this->$cnf($this->dep($parametros[2]));
		$this->dep($parametros[2])->set_todos_abiertos();
		$this->dep($parametros[2])->generar_html();
	}

	function ajax__get_esquema_data($parametros, toba_ajax_respuesta $respuesta) {
				$this->es_ajax = true;
		$var = "s__".$parametros[3];
		if(!isset($this->$var) || $this->$var != $parametros[0]) {
			$this->$var = $parametros[0];
		}
		/*$datos = $this->cn()->$parametros[1]($parametros[0]);
		$this->dep($parametros[2])->set_datos($datos);*/
		$cnf = 'conf__'.$parametros[2];
		$this->dep($parametros[2])->set_datos($this->$cnf($this->dep($parametros[2])));
		$this->dep($parametros[2])->generar_html();
	}

	function ajax__get_elem_data($parametros, toba_ajax_respuesta $respuesta)
	{
		$this->es_ajax = true;
		$var = "s__".$parametros[2];
		if(!isset($this->$var) || $this->$var != $parametros[0]) {
			$this->$var = $parametros[0];
		}
		/*$datos = $this->cn()->$parametros[1]($parametros[0]);
		$this->dep($parametros[2])->set_datos($datos);*/
		$cnf = 'conf__'.$parametros[1];
		$this->$cnf($this->dep($parametros[1]));
		$this->dep($parametros[1])->generar_html();
	}
	
	function ajax__set_var($parametros, toba_ajax_respuesta $respuesta) 
	{
		$this->es_ajax = true;
		$var = "s__".$parametros[1];
		if(!isset($this->$var) || $this->$var != $parametros[0]) {
			$this->$var = $parametros[0];
		}
	}
}