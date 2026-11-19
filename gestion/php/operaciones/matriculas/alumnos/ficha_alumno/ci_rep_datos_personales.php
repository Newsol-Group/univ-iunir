<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_rep_datos_personales extends ci_impresion 
{
	protected $ev_info_datos_personales;
	protected $ev_info_procedencia;
	protected $ev_info_datos_censales;
	protected $ev_info_datos_redes_sociales;
	
	protected $s__titulo;
	protected $s__foto_path;
	
	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
		$this->s__titulo = 'DATOS PERSONALES Y CENSALES';		
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_foto --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_foto(toba_ei_formulario $form)
	{
		$persona = $this->controlador()->controlador()->get_persona_seleccionada();
		$datos_persona = toba::consulta_php('co_personas')->get_datos_persona($persona);
		
		$datos = toba::consulta_php('co_personas')->get_persona_foto($persona);
		
		if (!empty($datos)) {
			$fp_imagen = $datos['imagen'];
			if (isset($fp_imagen)) {
				//-- Se necesita el path fisico y la url de una archivo temporal que va a contener la imagen
				$temp_nombre = md5(uniqid(time()));
				$temp_archivo = toba::proyecto()->get_www_temp($temp_nombre);

				//-- Se pasa el contenido al archivo temporal
				$this->s__foto_path = $temp_archivo['path'];
				$temp_fp = fopen($temp_archivo['path'], 'w');
				stream_copy_to_stream($fp_imagen, $temp_fp);
				fclose($temp_fp);
 
				//-- Se muestra la imagen temporal
				$datos['imagen_vista_previa'] = "<img src='{$temp_archivo['url']}' title='{$datos_persona['nombre_completo']}' style='border:2px solid black'>";
			}
			$form->set_datos($datos);
		}
	}
	
	function hay_imagen()
	{
		$datos = $this->dep('form_foto')->get_datos();
		return isset($datos['imagen_vista_previa']);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		// --- Datos personales ---------------------------------------------------------
		$persona = $this->controlador()->controlador()->get_persona_seleccionada();
		$datos = toba::consulta_php('co_personas')->get_datos_persona($persona);
		$estudios = current(toba::consulta_php('co_personas')->get_estudios_persona($persona));
		if (empty($estudios)) {
			$estudios = array();
		}
		$datos = array_merge($estudios, $datos);
		$this->ev_info_datos_personales()->set_datos($datos);
		
		// --- Datos censales (última residencia declarada) -----------------------------
		$datos_censales = array();
		$redes = array();
		$dato_censal = toba::consulta_php('co_datos_censales')->get_id_dato_censal($persona);
		
		if (!empty($dato_censal['dato_censal'])) {
			$cabecera_dato_censal = toba::consulta_php('co_datos_censales')->get_cabecera_dato_censal($dato_censal['dato_censal']);
			$datos_censales['fecha_actualizacion'] = $cabecera_dato_censal['fecha_actualizacion_formato_visual'];
			
			$datos_personales = current(toba::consulta_php('co_datos_censales')->get_datos_personales($dato_censal['dato_censal']));
			// -- Domicilio actual ------------------------------------------------------
			$datos_censales['localidad'] = (!empty($datos_personales['localidad_periodo_nombre'])) ? $datos_personales['localidad_periodo_nombre'] : ' ';
			$datos_censales['provincia'] = (!empty($datos_personales['provincia_periodo_nombre'])) ? $datos_personales['provincia_periodo_nombre'] : ' ';
			$datos_censales['calle'] = (!empty($datos_personales['periodo_lectivo_calle'])) ? $datos_personales['periodo_lectivo_calle'] : ' ';
			$datos_censales['numero'] = (!empty($datos_personales['periodo_lectivo_numero'])) ? $datos_personales['periodo_lectivo_numero'] : ' ';
			$datos_censales['codigo_postal'] = (!empty($datos_personales['periodo_lectivo_codigo_postal'])) ? $datos_personales['periodo_lectivo_codigo_postal'] : ' ';
			$datos_censales['piso'] = (!empty($datos_personales['periodo_lectivo_piso'])) ? $datos_personales['periodo_lectivo_piso'] : ' ';
			$datos_censales['departamento'] = (!empty($datos_personales['periodo_lectivo_departamento'])) ? $datos_personales['periodo_lectivo_departamento'] : ' ';
			$datos_censales['unidad'] = (!empty($datos_personales['periodo_lectivo_unidad'])) ? $datos_personales['periodo_lectivo_unidad'] : ' ';
			
			// --- Procedencia ----------------------------------------------------------
			$datos_procedencia = array();
			
			$datos_procedencia['localidad'] = (!empty($datos_personales['localidad_procedencia_nombre'])) ? $datos_personales['localidad_procedencia_nombre'] : ' ';
			$datos_procedencia['provincia'] = (!empty($datos_personales['provincia_procedencia_nombre'])) ? $datos_personales['provincia_procedencia_nombre'] : ' ';
			$datos_procedencia['codigo_postal'] = (!empty($datos_personales['procedencia_codigo_postal'])) ? $datos_personales['procedencia_codigo_postal'] : ' ';
			$datos_procedencia['calle'] = (!empty($datos_personales['procedencia_calle'])) ? $datos_personales['procedencia_calle'] : ' ';
			$datos_procedencia['numero'] = (!empty($datos_personales['procedencia_numero'])) ? $datos_personales['procedencia_numero'] : ' ';
			$datos_procedencia['piso'] = (!empty($datos_personales['procedencia_piso'])) ? $datos_personales['procedencia_piso'] : ' ';
			$datos_procedencia['departamento'] = (!empty($datos_personales['procedencia_departamento'])) ? $datos_personales['procedencia_departamento'] : ' ';
			$datos_procedencia['unidad'] = (!empty($datos_personales['procedencia_unidad'])) ? $datos_personales['procedencia_unidad'] : ' ';
			
			$this->ev_info_procedencia()->set_datos($datos_procedencia);
		}
		
		$contactos = toba::consulta_php('co_personas')->get_contactos_persona($persona);
		foreach ($contactos as $contacto) {
			// -- Contactos -------------------------------------------------------------
			switch ($contacto['contacto_tipo']) {
				case persona::tel_fijo:
					$datos_censales['telefono_particular'] = (!empty($contacto['descripcion'])) ? $contacto['descripcion'] : ' ';
					break;
				case persona::tel_celular:
					$datos_censales['telefono_celular'] = (!empty($contacto['descripcion'])) ? $contacto['descripcion'] : ' ';
					break;
				case persona::mail_primario:
					$datos_censales['email_principal'] = (!empty($contacto['descripcion'])) ? $contacto['descripcion'] : ' ';
					break;
				case persona::mail_institucional:
					$datos_censales['email_institucional'] = (!empty($contacto['descripcion'])) ? $contacto['descripcion'] : ' ';
					break;
				case persona::facebook:
					$redes['facebook'] = (!empty($contacto['descripcion'])) ? $contacto['descripcion'] : ' ';
					break;
				case persona::instagram:
					$redes['instagram'] = (!empty($contacto['descripcion'])) ? $contacto['descripcion'] : ' ';
					break;
				case persona::twitter:
					$redes['twitter'] = (!empty($contacto['descripcion'])) ? $contacto['descripcion'] : ' ';
					break;//seguir con la etiqueta para que lo muestre
			}
		}
		
		$this->ev_info_datos_censales()->set_datos($datos_censales);
		$this->ev_info_datos_redes_sociales()->set_datos($redes);
	}
	
	function ev_info_datos_personales()
	{
		if (!isset($this->ev_info_datos_personales)) {
			$this->ev_info_datos_personales = guarani::ev_info_datos();
			$this->ev_info_datos_personales->set_controlador($this);
			
			$this->ev_info_datos_personales->set_columnas_a_mostrar(array(	'apellido_nombres_oficial', 'nombre_completo_elegido', 'documento', 'sexo_valor', 'identidad_genero_nombre', 'identidad_genero_otro', 'fecha_nacimiento_formato_visual', 
																			'nacionalidad_valor', 'lugar_desc', 'titulo_desc', 'tipo_visa_descripcion', 'tipo_residencia_descripcion', 'localidad_valor'));
			
			$this->ev_info_datos_personales->set_max_columnas_por_fila(1);
			
			$this->ev_info_datos_personales->set_etiqueta('apellido_nombres_oficial', 'Apellido y Nombre:');
			$this->ev_info_datos_personales->set_etiqueta('nombre_completo_elegido', 'Apellido y Nombre Elegidos:');
			$this->ev_info_datos_personales->set_etiqueta('documento', 'Tipo y Nro. de Identificación:');	
			$this->ev_info_datos_personales->set_etiqueta('sexo_valor', 'Género:');
			$this->ev_info_datos_personales->set_etiqueta('identidad_genero_nombre', 'Identidad de Género y Diversidad:');
			$this->ev_info_datos_personales->set_etiqueta('identidad_genero_otro', 'Especificar:');
			$this->ev_info_datos_personales->set_etiqueta('fecha_nacimiento_formato_visual', 'Fecha de Nacimiento:');
			$this->ev_info_datos_personales->set_etiqueta('nacionalidad_valor', 'Nacionalidad:');
			$this->ev_info_datos_personales->set_etiqueta('lugar_desc', 'Colegio:');
			$this->ev_info_datos_personales->set_etiqueta('titulo_desc', 'Título secundario:');
			$this->ev_info_datos_personales->set_etiqueta('tipo_visa_descripcion', 'Visa:');
			$this->ev_info_datos_personales->set_etiqueta('tipo_residencia_descripcion', 'Residencia:');
			$this->ev_info_datos_personales->set_etiqueta('localidad_valor', 'Localidad de Nacimiento:');
		
			$this->ev_info_datos_personales->set_td_etiqueta_width('170px');
			$this->ev_info_datos_personales->set_table_width('');
		}
		return $this->ev_info_datos_personales;
	}
	
	function ev_info_procedencia()
	{
		if (!isset($this->ev_info_procedencia)) {
			$this->ev_info_procedencia = guarani::ev_info_datos();
			$this->ev_info_procedencia->set_controlador($this);
			
			$this->ev_info_procedencia->set_columnas_a_mostrar(array('localidad', 'codigo_postal', 'provincia', 'calle', 'numero', 'piso', 'departamento', 'unidad'));
			
			$this->ev_info_procedencia->set_columnas_fila(array('localidad'), 1);			
			$this->ev_info_procedencia->set_columnas_fila(array('codigo_postal'), 2);
			$this->ev_info_procedencia->set_columnas_fila(array('provincia'), 3);
			$this->ev_info_procedencia->set_columnas_fila(array('calle'), 4);
			$this->ev_info_procedencia->set_columnas_fila(array('numero'), 5);
			$this->ev_info_procedencia->set_columnas_fila(array('piso'), 6);
			$this->ev_info_procedencia->set_columnas_fila(array('departamento'), 7);
			$this->ev_info_procedencia->set_columnas_fila(array('unidad'), 8);
			
			$this->ev_info_procedencia->set_max_columnas_por_fila(1);
			
			$this->ev_info_procedencia->set_etiqueta('localidad', 'Localidad:');
			$this->ev_info_procedencia->set_etiqueta('codigo_postal', 'C.P.:');
			$this->ev_info_procedencia->set_etiqueta('provincia', 'Provincia:');
			$this->ev_info_procedencia->set_etiqueta('calle', 'Calle:');
			$this->ev_info_procedencia->set_etiqueta('numero', 'Nro.:');
			$this->ev_info_procedencia->set_etiqueta('piso', 'Piso:');
			$this->ev_info_procedencia->set_etiqueta('departamento', 'Departamento:');
			$this->ev_info_procedencia->set_etiqueta('unidad', 'Unidad:');
		
			$this->ev_info_procedencia->set_td_etiqueta_width('170px');
			$this->ev_info_procedencia->set_table_width('');
		}
		return $this->ev_info_procedencia;
	}
	
	function ev_info_datos_redes_sociales()
	{
		if (!isset($this->ev_info_datos_redes_sociales)) {
			$this->ev_info_datos_redes_sociales = guarani::ev_info_datos();
			$this->ev_info_datos_redes_sociales->set_controlador($this);
			
			$this->ev_info_datos_redes_sociales->set_columnas_a_mostrar(['facebook','twitter','instagram']);
			
			$this->ev_info_datos_redes_sociales->set_columnas_fila(['facebook'], 1);
			$this->ev_info_datos_redes_sociales->set_columnas_fila(['twitter'], 2);
			$this->ev_info_datos_redes_sociales->set_columnas_fila(['instagram'], 3);
			
			$this->ev_info_datos_redes_sociales->set_max_columnas_por_fila(1);
			
			$this->ev_info_datos_redes_sociales->set_etiqueta('facebook', 'Facebook:');
			$this->ev_info_datos_redes_sociales->set_etiqueta('twitter', 'Twitter:');
			$this->ev_info_datos_redes_sociales->set_etiqueta('instagram', 'Instagram:');
			
			$this->ev_info_datos_redes_sociales->set_td_etiqueta_width('170px');
			$this->ev_info_datos_redes_sociales->set_table_width('');
		}
		return $this->ev_info_datos_redes_sociales;
	}
	
	function ev_info_datos_censales()
	{
		if (!isset($this->ev_info_datos_censales)) {
			$this->ev_info_datos_censales = guarani::ev_info_datos();
			$this->ev_info_datos_censales->set_controlador($this);
			
			$this->ev_info_datos_censales->set_columnas_a_mostrar(array('fecha_actualizacion', 'localidad', 'provincia', 'calle', 
																		'numero', 'codigo_postal', 'piso', 'departamento', 
																		'unidad', 'telefono_particular', 'telefono_celular', 'email_principal', 'email_institucional'));
			
			$this->ev_info_datos_censales->set_columnas_fila(array('localidad'), 1);
			$this->ev_info_datos_censales->set_columnas_fila(array('codigo_postal'), 2);
			$this->ev_info_datos_censales->set_columnas_fila(array('provincia'), 3);
			$this->ev_info_datos_censales->set_columnas_fila(array('calle'), 4);
			$this->ev_info_datos_censales->set_columnas_fila(array('numero'), 5);
			$this->ev_info_datos_censales->set_columnas_fila(array('piso'), 6);
			$this->ev_info_datos_censales->set_columnas_fila(array('departamento'), 7);
			$this->ev_info_datos_censales->set_columnas_fila(array('unidad'), 8);
			$this->ev_info_datos_censales->set_columnas_fila(array('telefono_particular'), 9);
			$this->ev_info_datos_censales->set_columnas_fila(array('telefono_celular'), 10);
			$this->ev_info_datos_censales->set_columnas_fila(array('email_principal'), 11);
			$this->ev_info_datos_censales->set_columnas_fila(array('email_institucional'), 12);
			$this->ev_info_datos_censales->set_columnas_fila(array('fecha_actualizacion'), 13);
			
			$this->ev_info_datos_censales->set_max_columnas_por_fila(1);
			
			$this->ev_info_datos_censales->set_etiqueta('localidad', 'Localidad:');
			$this->ev_info_datos_censales->set_etiqueta('codigo_postal', 'C.P.:');
			$this->ev_info_datos_censales->set_etiqueta('provincia', 'Provincia:');
			$this->ev_info_datos_censales->set_etiqueta('calle', 'Calle:');
			$this->ev_info_datos_censales->set_etiqueta('numero', 'Nro.:');
			$this->ev_info_datos_censales->set_etiqueta('piso', 'Piso:');
			$this->ev_info_datos_censales->set_etiqueta('departamento', 'Departamento:');
			$this->ev_info_datos_censales->set_etiqueta('unidad', 'Unidad:');
			$this->ev_info_datos_censales->set_etiqueta('telefono_particular', 'Teléfono Particular:');
			$this->ev_info_datos_censales->set_etiqueta('telefono_celular', 'Teléfono Celular:');
			$this->ev_info_datos_censales->set_etiqueta('email_principal', 'E-Mail (particular):');
			$this->ev_info_datos_censales->set_etiqueta('email_institucional', 'E-Mail (institucional):');
			$this->ev_info_datos_censales->set_etiqueta('fecha_actualizacion', 'Fecha actualización datos:');
			
			$this->ev_info_datos_censales->set_td_etiqueta_width('170px');
			$this->ev_info_datos_censales->set_table_width('');
		}
		return $this->ev_info_datos_censales;
	}
	
	//-----------------------------------------------------------------------------------
	//---- vista_xml --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function vista_xml($inicial = false, $xmlns = NULL)
	{
		$this->xml_set_alto_cabecera('5cm');

		// ----------------------------------------------------------
		// 	ENCABEZADO
		// ----------------------------------------------------------

		$path_logo = guarani::get_path_logo_impresion_chico();
		$this->xml_set_logo($path_logo);

		//Tabla para el título y logo
		$this->xml_set_tabla_cols();

		$this->xml_set_orientacion('landscape');
		
		//Seteo los datos de la tabla
		$filas[] = array(array('valor'=>$this->xml_imagen($path_logo), 'border'=>'none','number-columns-spanned'=>2),array('valor'=>'', 'border'=>'none','number-columns-spanned'=>2));
		$filas[] = array(array('valor'=>$this->xml_texto($this->s__titulo, array('font-weight'=>'bold', 'font-size'=>12, 'text-align'=>'center')),'border-bottom'=>'solid black', 'text-align'=>'center', 'number-columns-spanned'=>4));

		//Genero/Obtengo la tabla.
		$cabecera = $this->xml_tabla($filas, false);

		//Tabla que reemplaza a la cabecera
		$this->xml_set_tabla_cols();
		
		$datos = toba::consulta_php('co_personas')->get_datos_persona($this->controlador()->controlador()->get_persona_seleccionada());
		
		$filas = array();
		$filas[] = array(
						array('valor'=>'APELLIDO Y NOMBRE: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left', 'number-columns-spanned'=>1),
						array('valor'=>$datos['nombre_completo'], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left', 'number-columns-spanned'=>3)
						);
		$filas[] = array(						
						array('valor'=>'TIPO Y NRO. DE IDENTIFICACIÓN: ', 'border'=>'none', 'font-weight'=>'bold', 'text-align'=>'left', 'number-columns-spanned'=>1),
						array('valor'=>$datos['documento'], 'border'=>'none', 'font-weight'=>'normal', 'text-align'=>'left', 'number-columns-spanned'=>3),
						);

		//Se genera la segunda tabla.
		$cabecera .= $this->xml_tabla($filas, false);
		$this->xml_set_cabecera($cabecera);

		// ----------------------------------------------------------
		//	CUERPO
		// ----------------------------------------------------------

		$xml = $this->xml_get_tag_inicio();
		
		if ($this->hay_imagen()) {
			$xml .= $this->xml_imagen($this->s__foto_path);
			$xml .= $this->xml_texto(' ', array('font-weight'=>'bold', 'font-size'=>12, 'margin-bottom'=>'0.5cm', 'text-align'=>'left'));		
		}
				
		if ($this->ev_info_datos_personales()->get_datos()) {
			$xml .= $this->xml_texto('DATOS PERSONALES', array('font-weight'=>'bold', 'text-decoration'=>'underline', 'font-size'=>12, 'margin-bottom'=>'0.5cm', 'text-align'=>'left'));
			$xml .= $this->ev_info_datos_personales()->vista_xml();
		}
		
		if ($this->ev_info_procedencia()->get_datos()) {
			$xml .= $this->xml_texto('PROCEDENCIA', array('font-weight'=>'bold', 'text-decoration'=>'underline', 'font-size'=>12, 'margin-bottom'=>'0.5cm', 'text-align'=>'left'));
			$xml .= $this->ev_info_procedencia()->vista_xml();
		}
				
		if ($this->ev_info_datos_censales()->get_datos()) {
			$xml .= $this->xml_texto('ÚLTIMA RESIDENCIA DECLARADA', array('font-weight'=>'bold', 'text-decoration'=>'underline', 'font-size'=>12, 'margin-bottom'=>'0.5cm', 'text-align'=>'left'));
			$xml .= $this->ev_info_datos_censales()->vista_xml();
		}
		
		if ($this->ev_info_datos_redes_sociales()->get_datos()) {
			$xml .= $this->xml_texto('REDES SOCIALES', array('font-weight'=>'bold', 'text-decoration'=>'underline', 'font-size'=>12, 'margin-bottom'=>'0.5cm', 'text-align'=>'left'));
			$xml .= $this->ev_info_datos_redes_sociales()->vista_xml();
		}

		$xml .= $this->xml_get_tag_fin();
		
		return $xml;
	}

}
?>