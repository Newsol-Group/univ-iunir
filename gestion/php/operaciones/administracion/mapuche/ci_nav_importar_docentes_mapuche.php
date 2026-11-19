<?php
class ci_nav_importar_docentes_mapuche extends ci_nav_procesos_bk
{
	protected $s__path_archivo;

	function set_nombre_proceso()
	{
		$this->s__nombre_proceso = pro_importar_docentes_mapuche::nombre;
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		$upload_max_filesize = ini_get('upload_max_filesize');
		$form->ef('archivo')->set_descripcion("El formato de archivo permitido es txt. Tamaño máximo de archivo: {$upload_max_filesize}B.");
	}

	function evt__formulario__modificacion($datos)
	{
		toba_manejador_archivos::crear_arbol_directorios(toba::proyecto()->get_path_temp().'/archivos');
		$this->s__path_archivo = toba::proyecto()->get_path_temp().'/archivos/'.uniqid();
		rename($datos['archivo']['tmp_name'], $this->s__path_archivo);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		if (filesize($this->s__path_archivo) == 0) {
			throw new toba_error(guarani_mensajes::get('800ADM_archivo_vacio'));
		}
		$this->cn()->ejecutar_proceso(array('archivo' => $this->s__path_archivo));
		$this->set_pantalla('procesamiento');
	}

	function evt__volver()
	{
		$this->set_pantalla('seleccion');
	}

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_resultado_proceso(pro_importar_docentes_mapuche::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		$reporte = pro_importar_docentes_mapuche::reporte_docentes_con_errores;
		$datos = $this->modo_visualizacion() ? $this->get_resultado_reporte($reporte) : $this->cn()->get_resultado_proceso($reporte);
		guarani_datos_impresion::instancia()->set_datos(array('docentes' => $datos['mensajes'], 'titulo' => 'Importar Docentes desde SIU-Mapuche - Docentes Procesados con Errores'));
		guarani_datos_impresion::instancia()->guardar_datos_imp();
        $vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '32000052', null, array('celda_memoria'=>'popup'));
		$respuesta->set($vinculo);
	}
 }
?>