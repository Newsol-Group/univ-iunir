<?php

namespace siu\operaciones\_comun\operaciones\reporte;

use SIU\Chulupi\kernel;

class generador_excel {

    //Maximo de caracteres que puede tener una hoja como nombre
    const MAX_CARACT_HOJA = 31;
	protected $planilla;
	protected $linea = 1;
	protected $nombre_archivo = "archivo";
    protected $nombre_hoja = "Reporte";

	function __construct(){
		$this->planilla = new \PHPExcel();
	}

	public function agregar_tabla($datos = array(), $columnas = array(), $titulo = ""){
		$hoja = $this->planilla->getActiveSheet();
        $hoja->setTitle(utf8_encode($this->nombre_hoja));

        if (!empty($titulo)) {
            $this->agregar_texto_sin_enter("", 1);
            $this->agregar_texto_estilos($titulo, 1, array('font' => array('bold' => true)));
        }

		$borde = array('style' => \PHPExcel_Style_Border::BORDER_THIN  );

		$estilo_titulos = array(
			'font' => array('bold' => true),
			'borders' => array('bottom' => $borde, 'top' => $borde, 'left' => $borde, 'right' => $borde),
			'fill' => array(
					'type' => \PHPExcel_Style_Fill::FILL_SOLID,
					'rotation'   => 0,
					'startcolor' => array('rgb' => 'E6E6E6')
				),
			'alignment' => array(
            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
			)
			
		);

		$estilo_datos = array(
 			'borders' => array('bottom' => $borde, 'top' => $borde, 'left' => $borde, 'right' => $borde),
 			'fill' => array(
					'type' => \PHPExcel_Style_Fill::FILL_SOLID ,
					'rotation'   => 0,
					'startcolor' => array('rgb' => 'FFFFFF')
				),
			'alignment' => array(
            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
			)
			
 		);

		$col_actual = 0;
		$cantidad_de_enter = 0;
		$maximo = 0;
		
		foreach($columnas as $columna){
			// Me fijo la cantidad de saltos de linea que tiene un texto, para ajustar el alto de la fila de acuerdo con el texto que tenga mayor cantidad de saltos de linea
			$cantidad_de_enter = explode("\n", utf8_encode($columna));
			$cantidad_de_enter = \guarani_arreglos::count($cantidad_de_enter);
			if ($maximo < $cantidad_de_enter){
						$maximo = $cantidad_de_enter;
			}		
			//Lleno la celda con los valores, ajusto el estilo del excel y actualizo los contadores
            $hoja->getCellByColumnAndRow($col_actual, $this->linea)->setValueExplicit(utf8_encode($columna), \PHPExcel_Cell_DataType::TYPE_STRING);
			//Ajusto ancho de cada columna, le pongo ancho automatico
			$hoja->getStyleByColumnAndRow($col_actual, $this->linea)->applyFromArray($estilo_titulos);
			$hoja->getColumnDimension($col_actual)->setAutoSize(true); 
			$col_actual++;
		}
		
		if ($maximo>1){
			$hoja->getRowDimension($this->linea)->setRowHeight(30 * $cantidad_de_enter); 
		}
		$this->linea++;

		if (!empty($datos)){
			foreach($datos as $linea){
				$col_actual = 0;
				$col_actual_letra = 'A';
				$keys_columnas = array_keys($columnas);
				$maximo = 0;
				foreach($keys_columnas as $col_key){
					// Me fijo la cantidad de saltos de linea que tiene un texto, para ajustar el alto de la fila de acuerdo con el texto que tenga mayor cantidad de saltos de linea
					$cantidad_de_enter = explode("\n", $linea[$col_key]);
					$cantidad_de_enter = \guarani_arreglos::count($cantidad_de_enter);
		
					if ($maximo < $cantidad_de_enter){
						$maximo = $cantidad_de_enter;
					}
					//Lleno la celda con los valores, ajusto el estilo del excel y actualizo los contadores
                    $hoja->getCellByColumnAndRow($col_actual, $this->linea)->setValueExplicit(utf8_encode($linea[$col_key]), \PHPExcel_Cell_DataType::TYPE_STRING);
					$hoja->getStyleByColumnAndRow($col_actual, $this->linea)->applyFromArray($estilo_datos);
					$col_actual++;
					$col_actual_letra++;
				}
				
				if ($maximo > 1){
					$hoja->getRowDimension($this->linea)->setRowHeight(20 * $maximo); 
				}
				
				//Ajusto ancho de cada columna, le pongo ancho automatico
				 for ($col = 'A'; $col != $col_actual_letra; $col++) { 
					$hoja->getColumnDimension($col)->setAutoSize(true); 
				}
				
				$this->linea++;
			}
		} else {
            $hoja->getCellByColumnAndRow(0, $this->linea)->setValueExplicit(ucfirst(kernel::traductor()->trans('no_hay_renglones')), \PHPExcel_Cell_DataType::TYPE_STRING);
		}
	}

	public function agregar_texto($title = "", $cant_titulos = 0){
        $this->planilla->getActiveSheet()->getCellByColumnAndRow(0, $this->linea)->setValueExplicit(utf8_encode($title), \PHPExcel_Cell_DataType::TYPE_STRING);
		$this->planilla->getActiveSheet()->mergeCellsByColumnAndRow(0, $this->linea, $cant_titulos-1, $this->linea);
		$this->linea++;
		$this->linea++;
	}

    public function agregar_texto_estilos($title = "", $cant_titulos = 0, $estilo){
        //$this->planilla->getActiveSheet()->setCellValueByColumnAndRow(0, $this->linea, $title);
        $this->planilla->getActiveSheet()->getCellByColumnAndRow(0, $this->linea)->setValueExplicit(utf8_encode($title), \PHPExcel_Cell_DataType::TYPE_STRING);
        $this->planilla->getActiveSheet()->mergeCellsByColumnAndRow(0, $this->linea, $cant_titulos-1, $this->linea);
        $this->planilla->getActiveSheet()->getStyleByColumnAndRow(0, $this->linea)->applyFromArray($estilo);
        $this->linea++;
        $this->linea++;
    }

    public function agregar_texto_sin_enter($title = "", $cant_titulos = 0){
        $this->agregar_texto($title, $cant_titulos);
        $this->linea--;
    }

	public function set_nombre_archivo($nombre) {
		$this->nombre_archivo = $nombre;
	}

    public function set_nombre_hoja($nombre) {
        // Trunco el nombre de la hoja a 31 caracteres
        $nombre = (strlen($nombre) > static::MAX_CARACT_HOJA) ? substr($nombre, 0, static::MAX_CARACT_HOJA-3).'...' : $nombre;
        $this->nombre_hoja = $nombre;
    }

	function generar(){
		$objWriter = new \PHPExcel_Writer_Excel5($this->planilla);
		$nombre = $this->nombre_archivo.".xls";
		
		$path_nombre = kernel::proyecto()->get_dir_temp() ."/". $nombre;
		$objWriter->save($path_nombre);
		$contenido = file_get_contents($path_nombre);
		$longitud = filesize($path_nombre);
		unlink($path_nombre);

		// clean the output buffer
		ob_clean();
		
		header("Cache-Control: private",false);
		header("Content-type: application/vnd.ms-excel");
		header("Content-Length: ".$longitud);
		header("Content-Disposition: attachment; filename=".$nombre);
		header("Content-Transfer-Encoding: binary");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo $contenido;
	}
}