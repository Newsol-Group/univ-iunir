<?php
namespace siu\operaciones\_comun\comprobantes;
use siu\conf\generador_comprobantes_conf;
use SIU\Chulupi\kernel;
use \siu\errores\error_guarani;

class generador_comprobantes
{	
    protected $conf;
    
	function __construct($ancho, $alto = "", $opciones = array())
	{
        $this->conf = new generador_comprobantes_conf();
        foreach ($opciones as $clave => $valor){
            $this->set_conf($clave, $valor);
        }
        
        $this->set_conf('ancho', $ancho);
        $this->set_conf('alto', $alto);
	}
	
    function set_conf($clave, $valor)
	{
        $this->conf->set($clave, $valor);
    }

	function agregar_imagen($path, $x, $y, $titulo = null, $subtitulo = null)
	{
		$encabezado = $this->conf->get('encabezado');
		$i = \guarani_arreglos::count($encabezado);
		$encabezado[$i]['path'] = $path;
		$encabezado[$i]['x'] = $x;
		$encabezado[$i]['y'] = $y;
		$encabezado[$i]['es_resource'] = !is_string($path);
		$encabezado[$i]['titulo'] = $titulo;
		$encabezado[$i]['subtitulo'] = $subtitulo;
		$this->set_conf('encabezado', $encabezado);
	}

    function agregar_QR($text, $x, $y)
    {
		//Genero el QR en una carpeta temporal
		$path_carpeta_qr = kernel::proyecto()->get_dir_temp()."/constancias_qr";
		if (!file_exists($path_carpeta_qr)){
			mkdir($path_carpeta_qr);
		}
		$path_qr = $path_carpeta_qr.'/'.uniqid('qr_').".png";
		\QRcode::png($text, $path_qr);
        $this->agregar_imagen($path_qr, $x, $y);
    }
    
	
	function agregar_titulo($texto = "", $alineacion = "c", $padding_izquierda = 0, $padding_derecha = 0)
	{        
		$titulo['texto'] = $texto;        
        $titulo['x'] = $this->alineacion($alineacion, $texto, $this->conf->get('fontBold'), $this->conf->get('tamano_fuente_titulo'), $padding_izquierda, $padding_derecha);
        $this->set_conf('titulo', $titulo);
	}
    
    function agregar_subtitulo($texto = "", $alineacion = "c", $padding_izquierda = 0, $padding_derecha = 0)
	{
		$subtitulo['texto'] = $texto;        
        $subtitulo['x'] = $this->alineacion($alineacion, $texto, $this->conf->get('fontBold'), $this->conf->get('tamano_fuente_subtitulo'), $padding_izquierda, $padding_derecha);
        $this->set_conf('subtitulo', $subtitulo);
	}
	
    protected function alineacion($alineacion, $texto, $fuente, $tfuente, $padding_izquierda = 0, $padding_derecha = 0){
        $padding_izquierda = (int)($padding_izquierda);
        $padding_derecha = (int) ($padding_derecha);
        
        $bbox = imagettfbbox($tfuente, 0, $fuente, $texto);
        switch ($alineacion){            
            case "c":            
                return  (intval(($this->conf->get('ancho') - $padding_izquierda - $padding_derecha) /2) - intval($bbox[2] /2) + $padding_izquierda);                
                break;            
            case "d":
                return  $this->conf->get('ancho') - $this->conf->get('margen') - $bbox[2] - $padding_derecha;
                break;
            case "i":
                return  $this->conf->get('margen') + $padding_derecha;
                break;
            default :
                return $this->conf->get('margen')+ $padding_derecha;
        }
    }
	
	function agregar_tabla($tablas)
	{
        $alto_fila = 20; //cada fila de la tabla tiene una altura de 20px.
		$cant_filas = 0;
		$tabla = $this->conf->get('tabla');
		$i = \guarani_arreglos::count($tabla);
		
		foreach ($tablas as $key_etiqueta => $texto) {
			
			$etiqueta = ucfirst(kernel::traductor()->trans(strtolower($key_etiqueta)));
			$etiqueta .= ": ";
			$box_etiqueta = imagettfbbox($this->conf->get('tamano_fuente'), 0, $this->conf->get('font'), $etiqueta);

			if (is_array($texto)) {
				$texto = implode(', ', $texto); 
			}
			
			$box_texto = imagettfbbox($this->conf->get('tamano_fuente'), 0, $this->conf->get('fontBold'), $texto);
			
			$ancho_etiqueta = $box_etiqueta[2];
			$ancho_texto	= $box_texto[2];
			// Sumo ancho de etiqueta + ancho de texto
			$ancho_fila = $ancho_etiqueta + $ancho_texto;
			
			if ($ancho_fila > $this->ancho_viewport()) {
				// ancho disponible para el texto
				$ancho_disponible = $this->ancho_viewport() - $ancho_etiqueta;
				$filas_de_texto = $this->separar_en_filas($texto, $ancho_disponible);
				$tabla[$i]['texto'] = $filas_de_texto;
				$tabla[$i]['x'] = $this->conf->get('margen') + $ancho_etiqueta;
				$cant_filas += \guarani_arreglos::count($filas_de_texto);
			} else {
				$tabla[$i]['texto'] = $texto;
				$tabla[$i]['x'] = $this->conf->get('margen') + $ancho_etiqueta;
				$cant_filas++;
			}
			
			$tabla[$i]['etiqueta'] = $etiqueta;
			$i++;
		}
		
		$this->set_conf('tabla', $tabla);

		$alto_tabla = $cant_filas * $alto_fila;
		return $alto_tabla;
	}
	
    protected function ancho_viewport(){
        return $this->conf->get('ancho') - ($this->conf->get('margen') * 2);
    }

	protected function separar_en_filas($texto, $ancho_disponible)
	{		
		// $ancho_disponible = ancho disponible para el texto. solo para la primer linea
		// array imagettfbbox ( float $size , float $angle , string $fontfile , string $text )
		// devuelve un array con las filas
		$filas = [];
		$palabras = explode(' ', $texto);
		$total_palabras = \guarani_arreglos::count($palabras);
		
		if ($total_palabras > 0) {
			
			$linea = array_shift($palabras);
			
			foreach ($palabras as $palabra) {
				
				$box_linea = imagettfbbox($this->conf->get('tamano_fuente'), 0, $this->conf->get('fontBold'), "{$linea} {$palabra}");
				$ancho_linea = $box_linea[2] - $box_linea[0];
				
				if ($ancho_linea > $ancho_disponible) {
					$filas[] = $linea;
					$linea = $palabra;
					$ancho_disponible = $this->ancho_viewport();
				} else {
					$linea .= " {$palabra}";
				}
			}
			
			$filas[] = $linea;		
		}
		
		return $filas;
	}

    function calcular_alto(){
        $alto_total = $this->conf->get('set_offset_cuerpo');
        
        // TITULO
        $titulo = $this->conf->get('titulo');
        if (!empty($titulo)){
            $alto_total += $this->conf->get('tamano_fuente_titulo') + $this->conf->get('interlineado') + 20;
        }
        
        // SUBTITULO
        $subtitulo = $this->conf->get('subtitulo');
        if (!empty($subtitulo)){
            $alto_total += $this->conf->get('tamano_fuente_subtitulo') + $this->conf->get('interlineado') + 10;
        }
        
        // TEXTO
        if (!empty($this->tabla)){
            foreach ($this->tabla as $key => $texto){
                if (is_array($texto['texto'])){
                    foreach($texto['texto'] as $key => $value){                        
                        $alto_total += $this->conf->get('tamano_fuente') + $this->conf->get('interlineado');
                    }
                }
                else
                {                
                    $alto_total += $this->conf->get('tamano_fuente') + $this->conf->get('interlineado');
                }                
            }
        }
        
        return $alto_total;
    }
    
    function dibujarBorde(&$img, &$color, $thickness = 1)
    {
        $x1 = 0;
        $y1 = 0;
        $x2 = ImageSX($img) - 1;
        $y2 = ImageSY($img) - 1;

        for($i = 0; $i < $thickness; $i++)
        {
            ImageRectangle($img, $x1++, $y1++, $x2--, $y2--, $color);
        }
    }
	
	function agregar_linea($x1, $y1, $x2, $y2, $color)
	{
		$lineas = $this->conf->get('lineas');
		$i = \guarani_arreglos::count($lineas);
		$lineas[$i]['x1'] = $x1;
		$lineas[$i]['y1'] = $y1;
		$lineas[$i]['x2'] = $x2;
		$lineas[$i]['y2'] = $y2;
		$lineas[$i]['color'] = $color;		
		$this->conf->set('lineas', $lineas);
		
	}

	function generar()
	{
		$alto = $this->conf->get('alto');
		if (empty($alto))
			$this->set_conf('alto', $this->calcular_alto());

		$im = imagecreatetruecolor($this->conf->get('ancho'), $this->conf->get('alto'));
		imagealphablending($im, true);
		$background_color = $this->conf->get('background_color');

		$background = imagecolorallocate($im, $background_color[0], $background_color[1], $background_color[2]);
		$fuente_color_defecto = $this->conf->get('fuente_color_defecto');
		$fuente_color = imagecolorallocate($im, $fuente_color_defecto[0], $fuente_color_defecto[1], $fuente_color_defecto[2]);
		$etiqueta_color_defecto = $this->conf->get('etiqueta_color_defecto');
		imagefill($im, 0, 0, $background);

		// ENCABEZADO
		$encabezados = $this->conf->get('encabezado');
		if (!empty($encabezados))
		{
                    foreach($encabezados as $encabezado)
			{
				if ($encabezado['es_resource'])
					$img_header = $encabezado['path'];
				else
				{
					switch (exif_imagetype($encabezado['path']))
					{
						case IMAGETYPE_GIF:
							$img_header = imagecreatefromgif($encabezado['path']);
							break;
						case IMAGETYPE_JPEG:
							$img_header = imagecreatefromjpeg($encabezado['path']);
							break;
						case IMAGETYPE_PNG:
							$img_header = imagecreatefrompng($encabezado['path']);
							break;
						default:
							throw new error_guarani('Tipo de imagen desconocido!');
					}
				}
				imagealphablending($img_header, true);
				imagefill($img_header, 0, 0, $background);
				if (!empty($encabezado['titulo']))
				{
					$texto= $encabezado['titulo'];
					$encabezado['titulo']='';
					$ancho_disponible = $this->ancho_viewport()  -20- imagesx($img_header);
					$titulo_filas = $this->separar_en_filas($texto, $ancho_disponible);
					foreach ($titulo_filas as $titulo_fila) {
						if (array_pop($titulo_filas) == $titulo_fila) {
							$encabezado['titulo'] .= $titulo_fila ;
						} else {
							$encabezado['titulo'] .="\n".$titulo_fila ;
						}
						
						}
					imagecopy(
						$im, //dst_im
						$img_header, //src_im
						$encabezado['x'], //dst_x
						$encabezado['y'], //dst_y
						0, //src_x
						0, //src_y
						imagesx($img_header), //src_w
						imagesy($img_header) //src_h
					);
					$box_titulo = imagettfbbox($this->conf->get('tamano_fuente_subtitulo'), 0, $this->conf->get('fontBold'), $encabezado['titulo']);
					imagettftext(
						$im, //image
						$this->conf->get('tamano_fuente_subtitulo'), //size
						0, //angle
						$this->conf->get('margen') + imagesx($img_header), //x
						$this->conf->get('margen'), //y
						$fuente_color, //color
						$this->conf->get('fontBold'), //fontfile
						$encabezado['titulo'] //text
					);
					if (!empty($encabezado['subtitulo']))
						imagettftext(
							$im, //image
							$this->conf->get('tamano_fuente_subtitulo'), //size
							0, //angle
							$this->conf->get('margen') + imagesx($img_header), //x
							$this->conf->get('margen') + abs($box_titulo[7] - $box_titulo[1])*2 + $this->conf->get('interlineado'), //y
							$fuente_color, //color
							$this->conf->get('fontBold'), //fontfile
							$encabezado['subtitulo'] //text
						);
				}
				else
                                    	imagecopy($im, $img_header, $encabezado['x'], $encabezado['y'], 0, 0, imagesx($img_header), imagesy($img_header));
			}
		}

		$borde = $this->conf->get('borde');
		if (!empty($borde))
		{
			$borde_color = imagecolorallocate($im, $borde['color'][0], $borde['color'][1], $borde['color'][2]);
			$this->dibujarBorde($im, $borde_color, $borde['ancho']);
		}

		// LINEAS
		$lineas = $this->conf->get('lineas');
		if (!empty($lineas)) {
			foreach($lineas as $linea) {
				imageline($im, $linea['x1'] , $linea['y1'] , $linea['x2'] , $linea['y2'], $borde_color );
			}
		}

		// TITULO
		$titulo = $this->conf->get('titulo');
		if (!empty($titulo)) {
			imagettftext($im, $this->conf->get('tamano_fuente_titulo'), 0, $titulo['x'], $this->conf->get('set_offset_cuerpo'), $fuente_color, $this->conf->get('fontBold'), $titulo['texto']);
			$valor_offset = $this->conf->get('set_offset_cuerpo') + $this->conf->get('tamano_fuente_titulo') + $this->conf->get('interlineado') + 5;
			$this->set_conf('set_offset_cuerpo', $valor_offset);
		}

		// SUBTITULO
		$subtitulo = $this->conf->get('subtitulo');
		if (!empty($subtitulo)) {
			imagettftext($im, $this->conf->get('tamano_fuente_subtitulo'), 0, $subtitulo['x'], $this->conf->get('set_offset_cuerpo'), $fuente_color, $this->conf->get('fontBold'), $subtitulo['texto']);
			$valor_offset = $this->conf->get('set_offset_cuerpo') + $this->conf->get('tamano_fuente_subtitulo') + $this->conf->get('interlineado') + 10;
			$this->set_conf('set_offset_cuerpo', $valor_offset);
		}

		// TEXTO
		$tabla = $this->conf->get('tabla');
		$estilos = $this->conf->get('estilos');
		$tamanio_fuente = $this->conf->get('tamano_fuente');

		if (!empty($tabla))
		{
			foreach ($tabla as $key => $texto)
			{
				$etiqueta_color = imagecolorallocate($im, $etiqueta_color_defecto[0], $etiqueta_color_defecto[1], $etiqueta_color_defecto[2]);
				//$etiqueta = ucfirst(kernel::traductor()->trans(strtolower($texto['etiqueta'])));
				if (!is_numeric($texto['etiqueta']))
				{
					imagettftext($im, $tamanio_fuente, 0, $this->conf->get('margen'), $this->conf->get('set_offset_cuerpo'), $etiqueta_color, $this->conf->get('font'), $texto['etiqueta']);
				}
				if (array_key_exists($texto['etiqueta'], $estilos))
				{
					$tamanio_fuente_activa = $estilos[$texto['etiqueta']]['tamanio'];
					$fuente_color_activo = imagecolorallocate($im, $estilos[$texto['etiqueta']]['color'][0], $estilos[$texto['etiqueta']]['color'][1], $estilos[$texto['etiqueta']]['color'][2]);                    
				}
				else
				{
					$tamanio_fuente_activa = $tamanio_fuente;
					$fuente_color_activo = $fuente_color;
				}

				if (is_array($texto['texto']))
				{
					$x = $texto['x'];
					foreach ($texto['texto'] as $key => $value)
					{
						imagettftext($im, $tamanio_fuente_activa, 0, $x, $this->conf->get('set_offset_cuerpo'), $fuente_color_activo, $this->conf->get('fontBold'), $value);
						if ($key == 0)
							$x = $this->conf->get('margen');
						$valor_offset = $this->conf->get('set_offset_cuerpo') + $tamanio_fuente_activa + $this->conf->get('interlineado');
						$this->set_conf('set_offset_cuerpo', $valor_offset);
					}
				}
				else
				{
						$textos_array = explode("\n", $texto['texto']);
						$x = $texto['x'];
						
						foreach ($textos_array as $texto)
						{
							klog2('Textos_array_2', $texto);
							imagettftext($im, $tamanio_fuente_activa, 0, $x, $this->conf->get('set_offset_cuerpo'), $fuente_color_activo, $this->conf->get('fontBold'), $texto);
							imagettftext($im, $tamanio_fuente_activa, 0, $x, $this->conf->get('set_offset_cuerpo'), $fuente_color_activo, $this->conf->get('fontBold'), "\n");
							
							if ($key == 0)
								$x = $this->conf->get('margen');	
							$valor_offset = $this->conf->get('set_offset_cuerpo') + $tamanio_fuente_activa + $this->conf->get('interlineado');
							$this->set_conf('set_offset_cuerpo', $valor_offset);
						}
					}
				}
			}
		return $im;
	}
}