<?php

class guarani_jasper_nucleo
{

	// Titulo del reporte
	protected $titulo;
	protected $orientacion_vert = true; 
	protected $ancho_cuerpo = 555;
	// Consulta del reporte en formato sql
	protected $query;
	// Consulta del reporte con los tags xml incluidos
	protected $query_st;
	// Campos del reporte (dependiendo de la consulta)
	protected $fields;
	protected $fields_st;
	// Parametros del reporte
	protected $parametros;
	// Grupos del reporte (dependiendo de la consulta)
	protected $grupos;
	// Arreglo conteniendo los Datos del encabezado 
	protected $datos_encabezado;
	protected $ancho_etiquetas;
	// Contenido XML (jrxml) del reporte
	protected $xml;
	// String conteniendo el Archivo XML de template para el reporte
	protected $template;
	// Nombre del archivo del template
	protected $template_nombre = 'template_guarani.jrxml';
	// Codigo xml conteniendo encabezado y pie de las columnas del reporte
	protected $col_header;
	protected $col_footer;
	// Codigo xml conteniendo encabezado de las paginas del reporte
	protected $page_header;
	// Codigo xml conteniendo el detalle del reporte
	protected $detail;	

	function __construct()
	{
		$this->titulo = '';
		$this->query = '';
		$this->query_st = '<queryString language="xPath">
								<![CDATA[/tabla/datos/fila]]>
						   </queryString>';
		$this->fields = '';
		$this->fields_st = '';
		$this->parametros = '';
		$this->grupos = '';
		$this->datos_encabezado = array();
		$this->xml = '';
		$this->template = '';
		$this->template_nombre = toba::proyecto()->get_path().'/exportaciones/jasper/' . $this->template_nombre;
		$this->col_header = '';
		$this->col_footer = '';
		$this->page_header = '';
		$this->detail = '';
		$this->ancho_etiquetas = 100;		
	}
	
	function set_template_nombre($template)
	{
		$this->template_nombre = $template;
	}

    function set_orientacion_vertical($or = true)
	{
            if ($or) {
				$this->orientacion_vert = true;
                $this->ancho_cuerpo = 555;
            } else {
				$this->orientacion_vert = false;                
                $this->ancho_cuerpo = 802;
            }
	}

    function set_titulo($titulo)
	{
		$this->titulo = $titulo;
	}

	function set_datos_encabezado($enc)
	{
		$this->datos_encabezado = $enc;
	}

	function set_querystring($query)
	{
		$this->query = $query;
		$this->query_st = '<queryString language="SQL">
								<![CDATA[' . $query . ']]>
							</queryString>';
	}

	function get_fields_query($query)
	{
	
	}

	function set_fields($fields)
	{
		$this->fields = $fields;
		foreach ($fields as $field) {
			$this->add_fields_st($field);
		}
	}

	function set_parametros($pars)
	{
		foreach ($pars as $par) {
			$this->parametros .= '<parameter name="' . $par['nombre'] . '" class="';
			$this->parametros .= $this->get_tipo($par['tipo']) .'"/>';
		}
	}

	function set_grupos($grupos)
	{
		$ancho_etiq = ($this->orientacion_vert) ? 139 : 200;
		$ancho_detail = $this->ancho_cuerpo - $ancho_etiq;
		$font = 16;
		// Se define este formato para los grupos...reveerlo y cambiarlo de acuerdo al deseado
		foreach ($grupos as $i => $g) {
			$this->grupos .= '<group name="' . $g['nombre'] . '">
								<groupExpression><![CDATA[
								';
			
			$sep = '';
			$campos_id = $g['campo']; 
			foreach ($campos_id as $campo) {
				$campo = trim($campo);
				$this->grupos .= $sep . '$F{' . $campo . '}';
				$sep = '+", "+';
			}
			
			$this->grupos .= ' ]]></groupExpression>
								<groupHeader>
								<band height="24">';
			$x = 0;
			$ancho = $this->ancho_cuerpo;
			if (isset($g['titulo'])) {
				$this->grupos .= '			<staticText>
												<reportElement mode="Opaque" x="0" y="0" width="' . $ancho_etiq . '" height="24" backcolor="#A9D0F5"/>
												<textElement>
													<font size="'.$font.'" isBold="true"/>
												</textElement>
												<text><![CDATA[' . $g['titulo'] . ']]></text>
											</staticText>';
				$x = $ancho_etiq;
				$ancho = $ancho_detail;
			}
			$this->grupos .= '			<textField hyperlinkType="None" isStretchWithOverflow="true">
											<reportElement mode="Opaque" x="'.$x.'" y="0" width="' . $ancho . '" height="24" backcolor="#A9D0F5"/>
											<textElement>
												<font size="'.$font.'" isBold="true"/>
											</textElement>
											<textFieldExpression class="java.lang.String">
												<![CDATA[';
			$sep = '';
			$campos = $g['campo_desc']; 
			foreach ($campos as $campo) {
				$campo = trim($campo);
				$this->grupos .= $sep . '$F{' . $campo . '}';
				$sep = '+", "+';
			}
			$this->grupos .= '					]]>
											</textFieldExpression>
										</textField>
									</band>
								</groupHeader>
								<groupFooter>';
			if ($g['pie']) {
				$this->grupos .= '<band height="20">
										<staticText>
											<reportElement mode="Opaque" x="0" y="0" width="' . $ancho_etiq . '" height="20" forecolor="#000000"/>
											<textElement> <font size="'.$font.'"/> </textElement>
											<text><![CDATA[Cantidad de Filas:]]></text>
										</staticText>
										<textField>
											<reportElement mode="Opaque" x="139" y="0" width="' . $ancho_detail . '" height="20" forecolor="#000000"/>
											<textElement> <font size="'.$font.'" isBold="true"/> </textElement>
											<textFieldExpression class="java.lang.Integer">
												<![CDATA[$V{' . $g['nombre'] . '_COUNT}]]>
											</textFieldExpression>
										</textField>
								</band>';
						} else {
							$this->grupos .= '<band height="6"/>';	                            
						}

			$this->grupos .= '	</groupFooter>
							</group>';	
			$font = $font-2;
		}

	}

	function set_ancho_etiquetas_encabezado($ancho)
	{
		$this->ancho_etiquetas = $ancho;
	}

	function set_column_header($c='')
	{
		$this->col_header = $c;
	}

	function set_detail($d='')
	{
		$this->detail = $d;
	}	

	function set_column_footer($cf='')
	{
		$this->col_footer = $cf;
	}

	function set_page_header($p='')
	{
		$this->page_header = $p;
	}

	function get_colHeader_predef()
	{
		$ancho = $this->ancho_cuerpo;
		$tamanio = floor($ancho / guarani_arreglos::count($this->fields));
		$ch = '<columnHeader>
				<band height="31" splitType="Stretch">
					<line>
						<reportElement positionType="FixRelativeToBottom" x="0" y="30" width="'. $ancho .'" height="1"/>
					</line>';
		
		$x = 0;
		foreach ($this->fields as $field) {		
			$ch .= ' <staticText>
							<reportElement style="Column header" x="'.$x.'" y="0" width="'.$tamanio.'" height="30"/>
							<textElement/>
							<text><![CDATA[' . $field['titulo'] . ']]></text>
					</staticText>';
			$x = $x + $tamanio;
		}
					
		$ch .= '</band>
			  </columnHeader>';
		
		return $ch;
	}

	function get_detail_predef()
	{

		$ancho = $this->ancho_cuerpo;
		$tamanio = floor($ancho / guarani_arreglos::count($this->fields));
		$d = '	<detail>
		<band height="17" splitType="Stretch">
			<frame>
				<reportElement x="0" y="0" width="' . $ancho . '" height="15"/>';
		$x = 0;
		foreach ($this->fields as $field) {		
			
			$pattern = '';
			if (isset($field['tipo'])) {
				$tipo = $this->get_tipo($field['tipo']);
				if ($field['tipo'] == 'D') {
					$pattern = 'pattern="dd/MM/yyyy"';
				}
			} else {
				$tipo = $this->get_tipo('S');
			}
			
			$d .= '
				<textField '.$pattern.' isStretchWithOverflow="true">
					<reportElement style="Detail" x="'.$x.'" y="0" width="'.$tamanio.'" height="15"/>
					<textElement/>
					<textFieldExpression class="'.$tipo.'"><![CDATA[$F{'.$field['nombre'].'}]]></textFieldExpression>
				</textField>';
			$x = $x + $tamanio;
		}
				
		$d .='
			</frame>
			<line>
				<reportElement positionType="FixRelativeToBottom" x="0" y="14" width="' . $ancho . '" height="1"/>
				<graphicElement>
					<pen lineWidth="0.5" lineColor="#999999"/>
				</graphicElement>
			</line>			
		</band>
	</detail>
		';
		
		return $d;	
	}

	function get_colFooter_predef()
	{
		$cf = '	<columnFooter>
					<band splitType="Stretch"/>
				</columnFooter>
			';
		
		return $cf;	
	}

	/**
	 * Retorna el encabezado default para los reportes del sistema, basado en arreglo que contiene los campos del encabezado: $this->datos_encabezado.
	 * Se espera que este arreglo tenga el siguiente formato:
	 *
	 * array (
	 * 			fila_1 => 	(
	 *							campo_1 =>	(
	 * 											'etiq' =>	('xxxxxxxxxxxxx')
	 * 											'valor' =>	(xxxxxxxxxxxx)
	 *										)
	 *							campo_2 =>	(
	 * 											'etiq' =>	('xxxxxxxxxxxxx')
	 * 											'valor' =>	(xxxxxxxxxxxx)
	 *										)
	 *						.....		 
	 *						)
	 *
	 * 			fila_2 => 	(
	 *							campo_1 =>	(
	 * 											'etiq' =>	('xxxxxxxxxxxxx')
	 * 											'valor' =>	(xxxxxxxxxxxx)
	 *										)
	 *							campo_2 =>	(
	 * 											'etiq' =>	('xxxxxxxxxxxxx')
	 * 											'valor' =>	(xxxxxxxxxxxx)
	 *										)
	 *						.....		 
	 *						)
	 *		)
	 *
	 */
	function get_pageHeader_predef()
	{
		$ancho = $this->ancho_cuerpo;
		$ph = '<pageHeader>
				<band height="1" splitType="Stretch">
					<line>
						<reportElement positionType="FixRelativeToBottom" x="0" y="0" width="'. $ancho .'" height="1"/>
					</line>';

		if (!empty($this->datos_encabezado)) {		
		
			$alto_fila = 15;
			$filas = guarani_arreglos::count($this->datos_encabezado);
			$alto = $alto_fila * $filas;
				
			$ph = '<pageHeader>
					<band height="'. ($alto+1) .'" splitType="Stretch">
						<line>
							<reportElement positionType="FixRelativeToBottom" x="0" y="' . $alto .'" width="'. $ancho .'" height="1"/>
						</line>
						<line>
							<reportElement positionType="FixRelativeToBottom" x="0" y="0" width="'. $ancho .'" height="1"/>
						</line>';

			$y = -14;
			foreach ($this->datos_encabezado as $filas) {	
				$x = 0;
				$y = $y + 15;
				$cant_cols = guarani_arreglos::count($filas);
				$ancho_etiqueta = $this->ancho_etiquetas;
				$tamanio = floor(($ancho - ($ancho_etiqueta * $cant_cols)) / $cant_cols);
				foreach ($filas as $col_campo => $col_valores) {		
					$ph .= ' <staticText>
									<reportElement style="Resalt" x="'.$x.'" y="'.$y.'" width="'.$ancho_etiqueta.'" height="15"/>
									<textElement>
										<font size="10"/>
									</textElement>
									<text><![CDATA[' . guarani_texto::sanear_string($col_valores['etiqueta']) . ': ]]></text>
							</staticText>';
					$x = $x + $ancho_etiqueta;
					$ph .= ' <staticText>
									<reportElement style="Comun" x="'.$x.'" y="'.$y.'" width="'.$tamanio.'" height="15"/>
									<textElement/>
									<text><![CDATA[' . ' ' . guarani_texto::sanear_string($col_valores['valor']) . ']]></text>
							</staticText>';
					$x = $x + $tamanio;
				}
			}
		}

		$ph .= '</band>
			  </pageHeader>';
		
		return $ph;
	}

	function get_jrxml()
	{
		if (empty($this->xml)) {

			$template = $this->get_template();
			$this->xml = '';
                        
                        // Si la orientacion es apaisada cambio las dimensiones de la pagina
                        // toDo: Resta cambiar los anchos fijados en grupos, datos, etc...
                        if (!$this->orientacion_vert) {
                            $template = explode('pageWidth="595" pageHeight="842"',$template);
                            $this->xml .= $template[0];
                            $this->xml .= ' pageWidth="842" pageHeight="595" ';
                            $template = $template[1];
                        }
			$template = explode('<group name="Group1">',$template);
			// Seteo el encabezado + estilos al XML
			$this->xml .= $template[0];
			// Agrego los parametros
			$this->xml .= $this->parametros;
			// Agrego la query
			$this->xml .= $this->query_st;
			// Agrego los campos
			if (empty($this->fields)) {
				// Si no se setearon explicitamente, se los extrae de la consulta
				$this->get_fields_query();
			} 
			$this->xml .= $this->fields_st;
			// Agrego los grupos si es que se setearon
			$this->xml .= $this->grupos;
			
			// Obtengo la 2da parte del archivo template
			$template = explode('<background>',$template[1]);
			// Descarto los grupos del template porque ya los agregue
			$template = explode('<pageHeader>',$template[1]);
			// Agrego el titulo correspondiente del reporte
			$xml_temp = str_replace("%TITULO%", $this->titulo, $template[0]);
			$this->xml .= '<background>' . $xml_temp;
			
			//Agrego el pageHeader
			if (empty($this->page_header)) {
				$xml_temp = $this->get_pageHeader_predef();
				$this->set_page_header($xml_temp);
			}
			$this->xml .= $this->page_header;
			
			// Descarto el pageHeader del template porque ya los agregue
			$template = explode('<columnHeader>',$template[1]);
			
			//Agrego el columnHeader
			if (empty($this->col_header)) {
				$xml_temp = $this->get_colHeader_predef();
				$this->set_column_header($xml_temp);
			}
			$this->xml .= $this->col_header;
		
			//Agrego el deTail
			if (empty($this->detail)) {
				$xml_temp = $this->get_detail_predef();
				$this->set_detail($xml_temp);
			}
			$this->xml .= $this->detail;

			//Agrego el columnFooter
			if (empty($this->col_footer)) {
				$xml_temp = $this->get_colFooter_predef();
				$this->set_column_footer($xml_temp);
			}
			$this->xml .= $this->col_footer;

			// Obtengo y agrego la 3ra parte del archivo template
			// Actualmente el pageFooter se obtiene del archivo de template directamente
			// quizas se podria implementar de armarlo como el pageHeader
			$template = explode('<pageFooter>',$template[1]);
			$this->xml .= '<pageFooter>' . $template[1];
		}

		return $this->xml;
		
	}

	function set_jrxml_archivo($archivo)
	{
		if (empty($this->xml)) {
			$this->xml = $this->get_jrxml();
		}
		//Escribo el xml
		toba_manejador_archivos::crear_archivo_con_datos($archivo, utf8_e_seguro($this->xml));		
	}
	
	function get_template()
	{
		if (empty($this->template)) {
			if (!toba_manejador_archivos::existe_archivo_en_path($this->template_nombre)) {
				throw new toba_error("No existe el archivo de template", "Archivo no encontrado: ".$this->template_nombre);
			}
	
			$this->template = file_get_contents($this->template_nombre);		
		}
		
		return $this->template;
	}
	
	function get_tipo($tipo='S')
	{
		switch ($tipo) {
			case 'D':
				$tipo = "java.sql.Date";				
				break;	
			case 'S':
				$tipo = "java.lang.String";				
				break;
			case 'E':
				$tipo = "java.lang.Integer";				
				break;
			case 'F':
				$tipo = "java.math.BigDecimal";				
				break;
			default:
				$tipo = "java.lang.String";
				break;
		}

		return $tipo;
	}
	
	function add_fields_st($field) 
	{
		$tipo = (isset($field['tipo'])) ? $field['tipo'] : 'S';
		$this->fields_st .= '<field name="' . $field['nombre'] . '" class="';
		$this->fields_st .= $this->get_tipo($tipo);
		$this->fields_st .= '"><fieldDescription><![CDATA[dato[@clave = "' . $field['nombre'] . '"]/@valor]]></fieldDescription></field>';
	}

}

?>
