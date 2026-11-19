<?php
namespace siu;

use Composer\Script\Event;
use SIU\Chulupi\util\ManejadorArchivos;
use SIU\Chulupi\Scripts\Composer;

class Install
{
	public static function postUpdate(Event $event)
	{
		$vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
		$chulupiJs = $vendorDir . '/siu/chulupi-framework/js';
		if (!is_dir($chulupiJs)) {
			return;
		}
		$dst = __DIR__ . '/www/js/lib';
		ManejadorArchivos::borrar_directorio($dst);
		ManejadorArchivos::copiar_directorio($chulupiJs, $dst);

		//Agrega fuentes en la carpeta 'fonts' de la librería ezpdf
		$fontsToAdd = $vendorDir . '/siu/chulupi-framework/fonts';
		$fontsEzpdf = $vendorDir . '/rebuy/ezpdf/src/ezpdf/fonts';
		ManejadorArchivos::copiar_directorio($fontsToAdd, $fontsEzpdf);
		
		Composer::fixBugContinue($vendorDir);
		Composer::fixBugConstructors($vendorDir);
		Composer::fixCountTwig($vendorDir);
		Composer::fixArrayKeyExistsObjetos($vendorDir);
		Composer::fixDeprecationExcel($vendorDir);
		Composer::fixWarningRDI($vendorDir);
		
	}

}