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
			'label' => &$GLOBALS['TL_LANG']['tl_content']['boot24_url'],
			'inputType' => 'text',
			'eval' => ['tl_class' => 'w50', 'maxlength' => 33],
    		'sql' => ['type' => 'string', 'length' => 33, 'default' => '']
		];
$GLOBALS['TL_DCA']['tl_content']['fields']['boot24_lang'] = [
			'label' => &$GLOBALS['TL_LANG']['tl_content']['boot24_url'],
			'inputType' => 'text',
			'eval' => ['tl_class' => 'w50', 'maxlength' => 2],
    		'sql' => ['type' => 'string', 'length' => 2, 'default' => '']
		];
$GLOBALS['TL_DCA']['tl_content']['palettes']['xippo_boot24'] = '{type_legend},type,headline;{boot24_legend},boot24_url,boot24_lang;{protected_legend:hide},protected;{expert_legend:hide},guests,invisible,cssID,space;';
