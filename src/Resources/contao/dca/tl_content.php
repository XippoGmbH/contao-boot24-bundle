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

if($this->Input->get('do') == 'xippo_boot24')
$GLOBALS['TL_DCA']['tl_content']['fields']['content_boot24'] = [
			'label' => &$GLOBALS['TL_LANG']['tl_content']['content_boot24'],
			'inputType' => 'select',
			'foreignKey' => 'tl_xippo_boot24.title',
			'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => true, 'default' => 0],
			'eval' => [
				'mandatory' => true,
				'includeBlankOption' => true
			]
		];
$GLOBALS['TL_DCA']['tl_content']['palettes']['xippo_boot24'] = '{type_legend},type,headline;{maps_legend},content_boot24;{protected_legend:hide},protected;{expert_legend:hide},guests,invisible,cssID,space;';
