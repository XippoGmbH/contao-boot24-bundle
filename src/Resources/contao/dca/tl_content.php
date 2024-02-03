<?php
// contao/dca/tl_content.php
/*
 * This file is part of xippogmbh/boot24-bundle.
 *
 * (c) Aurelio Gisler (Xippo GmbH)
 *
 * @author     Aurelio Gisler
 * @package    XippoGmbHMaps
 * @license    MIT
 * @see        https://github.com/xippoGmbH/contao-boot24-bundle
 *
 */

$GLOBALS['TL_DCA']['tl_content']['fields']['boot24_token'] = [
			'label' => &$GLOBALS['TL_LANG']['tl_content']['boot24_token'],
			'inputType' => 'text',
			'eval' => ['tl_class' => 'w50', 'maxlength' => 35],
    		'sql' => ['type' => 'string', 'length' => 35, 'default' => '']
		];
$GLOBALS['TL_DCA']['tl_content']['fields']['boot24_categorie'] = [
			'label' => &$GLOBALS['TL_LANG']['tl_content']['boot24_categorie'],
			'inputType' => 'text',
			'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    		'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
		];
$GLOBALS['TL_DCA']['tl_content']['fields']['boot24_manufacturer'] = [
			'label' => &$GLOBALS['TL_LANG']['tl_content']['boot24_manufacturer'],
			'inputType' => 'text',
			'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    		'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
		];
$GLOBALS['TL_DCA']['tl_content']['fields']['boot24_newOrUsed'] = [
			'label' => &$GLOBALS['TL_LANG']['tl_content']['boot24_newOrUsed'],
			'inputType' => 'text',
			'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    		'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
		];
$GLOBALS['TL_DCA']['tl_content']['palettes']['xippo_boot24'] = '{type_legend},type,headline;{boot24_legend},boot24_token,boot24_categorie,boot24_manufacturer,boot24_newOrUsed;{protected_legend:hide},protected;{expert_legend:hide},guests,invisible,cssID,space;';
