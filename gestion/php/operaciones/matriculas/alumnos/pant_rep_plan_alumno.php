<?php
class pant_rep_plan_alumno extends toba_ei_pantalla
{
	public function generar_layout()
	{
		//$this->dep('cuadro_seleccionado')->generar_html();
		// Estilos CSS
		$this->estilos(); 
		
        //Imprimo en html los datos del plan
        $this->imprimir_datos_plan();        
	}
    
	public function estilos()
	{
		$css = '<style>';
		$css .= 'div.accordian.materia_generica > h3 {cursor: pointer; color: #fff;}';
		$css .= 'div.accordian.materia_generica .js-contenido {display: none;}';
		$css .= '</style>';
		
		echo $css;
	}
	
    public function imprimir_datos_plan()
    {
        //Traigo los datos del plan del alumno.        
        $datos = $this->controlador()->get_datos_plan();
        //echo "<div id='accordeon'>";
        if (isset($datos['modulos_genericas']) || isset($datos['materias'])) {
            if (isset($datos['modulos_genericas'])) {
                foreach ($datos['modulos_genericas'] as $genericas) {
                    $this->imprimir_genericas($genericas);
                }
            }
            
            if (isset($datos['materias'])) {
                $this->imprimir_materia($datos['materias'], '', '');
            }
        }
        //echo "</div>";
    }

    public function imprimir_genericas($generica)
    {
        // Si es una genérica, o es un módulo que no esta vacio (tiene submodulos o materias)
        if (($generica['entidad_subtipo'] == elemento::subtipo_materia_generica) || (isset($generica['modulos_genericas']) || isset($generica['materias']))) {
            $nombreSubtipo = $generica['codigo']. ' '.$generica['nombre'];

            $label = '';
            $class = '';
            $cumplimiento = '';
			$entidad_subtipo = '';
			
            #4180BE
            //Verifica que subtipo de elemento es.
            if(isset($generica['entidad_subtipo'])) {
                if ($generica['entidad_subtipo'] == elemento::subtipo_materia_generica) {
					
					$parametros = $generica['parametros'] ?? '""';
					$entidad_subtipo = 'materia_generica';
                    $label = "<h3 class='ui-accordion-header ui-corner-top ui-state-default ui-accordion-icons ui-accordion-header-collapsed ui-corner-all' onclick='ver_optativas({$generica['elemento']}, {$generica['elemento_comp']});' style='background-color: #1a4d80; color: #ffffff;'><span class='ui-accordion-header-icon ui-icon ui-icon-triangle-1-e'></span>Materia Génerica: $nombreSubtipo</h3>";  
                    $boton_cumplimiento = "<button type='button' id='boton_cumplimiento_{$generica['elemento_comp']}' title='' class='ei-boton-fila' "
                    . "onclick='verificar_cumplimiento({$generica['elemento']}, {$generica['regla']}, {$parametros}, {$generica['elemento_comp']});'>
                        Verificar</button>";
                    
                    $cumplimiento =  "<div style='padding: 10px;'>
                                        <div style='float:left;'><b>Cumplimiento:</b> {$generica['forma_cumplimiento']}</div>
                                        <div style='float:right;'>$boton_cumplimiento</div>
                                        <div style='clear:both;'></div>
                                    </div>
                                    <div id='cumplimiento_{$generica['elemento_comp']}' style='display:none;margin-bottom:10px;'></div>
									<div class='js-optativas'>
										<!-- aca escribe el ajax -->
									</div>";

                } elseif ($generica['entidad_subtipo'] == elemento::subtipo_agrupador_modulos) {
					$entidad_subtipo = 'modulos';
                    $label = "<h3 style='background-color: #2673bf; color: #ffffff;'><a href='#' >Módulo: ". $nombreSubtipo.'</a></h3>';

                } elseif ($generica['entidad_subtipo'] == elemento::subtipo_agrupador_orientaciones) {
					$entidad_subtipo = 'agrupador_orientaciones';
                    $label = "<h3 style='background-color: #3399ff; color: #ffffff;'><a href='#'>".$nombreSubtipo.'</a></h3>';

                } elseif ($generica['entidad_subtipo'] == elemento::subtipo_orientacion) {
					$entidad_subtipo = 'orientacion';
                    $label = "<h3 style='background-color: #3399ff; color: #ffffff;'><a href='#'>Orientación: ".$nombreSubtipo.'</a></h3>';
                }
            }

			echo "<div class='accordian $entidad_subtipo elemento-comp-{$generica['elemento_comp']}' data-elemento-comp='{$generica['elemento_comp']}' data-cargado='false'>
                    $label
                    <div class='js-contenido'>
                    $cumplimiento
                ";

            if (isset($generica['modulos_genericas'])) {
                foreach ($generica['modulos_genericas'] as $generica2) {
                    $this->imprimir_genericas($generica2);
                }
            }
            if(isset($generica['materias'])) {
                $this->imprimir_materia($generica['materias'], $generica['elemento'], $generica['entidad_subtipo']);
            }
            
            echo "</div></div>";
        }
        
    }
    
    public function imprimir_materia($materias, $padre_id, $padre_tipo, $retornar_html = false)
    {
		$html = '';
		
		$html .= '
            <table  class= "ei-cuadro_seleccionado-cuerpo tabla-0" summary="Materias" width="100%">
            <thead>
                <tr>
                    <th class="ei-cuadro_seleccionado-col-tit" title="Actividad">Actividad</th>
                    <th class="ei-cuadro_seleccionado-col-tit" title="Tipo">Tipo</th>
                    <th class="ei-cuadro_seleccionado-col-tit" title="Año">Año</th>
                    <th class="ei-cuadro_seleccionado-col-tit" title="Período">Período</th>
                    <th class="ei-cuadro_seleccionado-col-tit" title="Nota">Nota</th>
                    <th class="ei-cuadro_seleccionado-col-tit" title="Origen">Origen</th>
                    <th class="ei-cuadro_seleccionado-col-tit" title="Crédito">Crédito</th>
                    <th class="ei-cuadro_seleccionado-col-tit" title="Puntaje">Puntaje</th>
                    <th class="ei-cuadro_seleccionado-col-tit" title="Correlativas">Correlativas</th>
                </tr>
            </thead>
            <tbody>';
        $i = 0;
        foreach ($materias as $materia) {
            $optativa_compartida = '';
            //Falta agregar la optativa compartida a esa generica
            if (is_null($padre_id) || $padre_tipo != elemento::subtipo_materia_generica) {
                $optativa_compartida = '';
            } elseif ($materia['optativa_compartida'] == elemento::es_optativa_compartida_asignada_a_esa_generica) {
                $optativa_compartida = '
                    <i class="fa fa-check-circle fa-2x asignacion_optativa"
                       aria-label="Optativa compartida y asignada a esta genérica."
                       data-toggle="tooltip" 
                       data-placement="top" 
                       data-html="true"
                       data-container="body"
                       title="Optativa compartida y asignada a esta genérica." 
                       data-original-title="Optativa compartida y asignada a esta genérica.">
                    </i>
                    ';
            } elseif ($materia['optativa_compartida'] == elemento::es_optativa_compartida_asignada_a_otra_generica) {
                $optativa_compartida = '
                    <i class="fa fa-minus-circle fa-2x asignacion_optativa"
                       aria-label="Optativa compartida y asignada a otra genérica."
                       data-toggle="tooltip" 
                       data-placement="top" 
                       data-html="true"
                       data-container="body"
                       title="Optativa compartida y asignada a otra genérica." 
                       data-original-title="Optativa compartida y asignada a otra genérica.">
                    </i>
                    ';
            } elseif ($materia['optativa_compartida'] == elemento::es_optativa_compartida_sin_generica_asignada) {
                $optativa_compartida = '
                    <i class="fa fa-exclamation-circle fa-2x asignacion_optativa"
                       aria-label="Optativa compartida y sin asignar a una genérica."
                       data-toggle="tooltip" 
                       data-placement="top" 
                       data-html="true"
                       data-container="body"
                       title="Optativa compartida y sin asignar a una genérica." 
                       data-original-title="Optativa compartida y sin asignar a una genérica.">
                    </i>
                    ';
            }
            
            $class = 'ei-cuadro_seleccionado-celda-impar';
            if ($i % 2 == 0) {
                $class = 'ei-cuadro_seleccionado-celda-par';
            }
            
            $puntaje = '&nbsp;';
            if (isset($materia['puntaje'])) {
                $puntaje = $materia['puntaje'];
            }
            
            $html .= "
                <tr class='$class materia'>
                    <td class='ei-cuadro_seleccionado-fila col-tex-p1'>$optativa_compartida {$materia['actividad']}</td>
                    <td class='ei-cuadro_seleccionado-fila col-tex-p1'>{$materia['tipo']}&nbsp;</td>
                    <td class='ei-cuadro_seleccionado-fila col-tex-p1'>{$materia['anio']}&nbsp;</td>
                    <td class='ei-cuadro_seleccionado-fila col-tex-p1'>{$materia['periodo']}&nbsp;</td>
                    <td class='ei-cuadro_seleccionado-fila col-tex-p1'>{$materia['nota']}&nbsp;</td>
                    <td class='ei-cuadro_seleccionado-fila col-tex-p1'>{$materia['origen']}&nbsp;</td>
                    <td class='ei-cuadro_seleccionado-fila col-tex-p1'>{$materia['credito']}&nbsp;</td>
                    <td class='ei-cuadro_seleccionado-fila col-tex-p1'>$puntaje</td>
                    <td class='centrado' style='text-align: center;'>
                    ";
            //Tiene coorelativas?
            if ($materia['tiene_correlativas'] == 1)
            {
				$html .= "<button type='button' class='ei-boton-fila ver_correlatividades' data-elemento='{$materia['elemento']}' data-completo='false'>Ver</button>";
            }                        
            $html .= "</td>
                </tr>";
            
            $html .= "
                <tr class='correlatividades'>
                    <td colspan='9'>
                        <table width='100%' style='display: none; background-color: #E5E5E5;'>
                            <!-- aca escribe el ajax -->
                        </table>
                    </td>
                </tr>
                ";
            
            $i ++;
        }
        $html .= "</tbody></table>";
		
		if ($retornar_html) {
			return $html;
		} else {
			echo $html;
		}
    }
    
    /*
     * Funcion que es llamada por el ci para traer y mostrar las correlativas
     */
    function imprimir_correlativas($correlativas)
    {
        //Tengo las correlativas para mostrar.
        //Me fijo el tipo correlativa
        
        $html = '<tr>';
        
        $aprobar = $cursar = [];        
        foreach($correlativas as $correlativa) {
            if ($correlativa['tipo_correlativa'] == 1) {
				$cursadas[] = $correlativa;
			} elseif ($correlativa['tipo_correlativa'] == 2) {
				$aprobadas[] = $correlativa;
			}
        }
        
        
        if (!empty($cursadas)) {
            $html .= $this->imprimir_tipo_correlativa($cursadas, 1);
        }
        
        if (!empty($aprobadas)) {
            $html .= $this->imprimir_tipo_correlativa($aprobadas, 2);
        }
        $html .= '</tr>';
        return $html;
    }
    
    function imprimir_tipo_correlativa($correlativas, $tipo)
    {
        $tipost = 'Aprobar';
        if ($tipo == 1) {
            $tipost = 'Cursar';
        }
        $count = 1;
        $opcion = $correlativas[0]['grupo_condicion'];
		$html = "";
        $html .= "<td><h2>Para $tipost</h2></td>";
        $html .= "<table border=1 summary='Opci&oacute;n para $tipost'>";
        $html .= "<tr><td><h2>Opción $count</h2></td></tr>";
        $html .= "<tr>
                    <th scope='col' class='td-mat-req'>Requisito</th>
                    <th scope='col' class='td-mat-cond'>Condición</th>
                </tr>";
        foreach($correlativas as $correlativa) {                
            if ($correlativa['grupo_condicion'] == $opcion) {
                $html .= $this->agregar_fila_correlativa($correlativa);
            } else {
                $html .= '</table>'; 
                $count ++;
                $html .= "<tr><td><h3>Opción $count</h3></td></tr>";
                $html .= "<table border=1 summary='Opci&oacute;n para $tipost'>";
                $html .= "<tr>
                            <th scope='col' class='td-mat-req'>Requisito</th>
                            <th scope='col' class='td-mat-cond'>Condición</th>
                          </tr>";
                $html .= $this->agregar_fila_correlativa($correlativa);

            }
        }
        $html .= '</table>';
        return $html;
    }
    
    function agregar_fila_correlativa($correlativa)
    {
        $html = '<tr scope="row">';        
        if ($correlativa['tipo'] == "E") {
            $html .= '<td>';
            if ($correlativa['entidad_subtipo'] == elemento::subtipo_agrupador_modulos) {
                $html .= '<b>Módulo:</b>';
            }
            $html .= $correlativa['requisito_nombre'];
            if (isset($correlativa['requisito_codigo'])) {
                $html .= '('.$correlativa['requisito_codigo'].')';
            }
            $html .= '</td>';
            
            $html .= '<td class="centrado">';
            
            if ($correlativa['operador_not']) {
                $html .= '(Negar)';
            }
            $html .= "<b>{$correlativa['condicion']}</b>";
            $html .= '</td>';
        }
        
        elseif ($correlativa['tipo'] == 'R') {
            $html .= '<td colspan="2" class="centrado">';
            
            if ($correlativa['operador_not']) {
                $html .= '(Negar)';
            }
            $html .= "<b>{$correlativa['condicion']}</b>";
            $html .= '</td>';
        }
        $html .= '</tr>';
        return $html;
    }
}