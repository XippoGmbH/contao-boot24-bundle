<?php
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
// Backend modules
// $GLOBALS['BE_MOD']['content']['xippo_boot24'] = ['tables' => ['tl_content']];

// Models
$GLOBALS['TL_MODELS']['tl_xippo_boot24'] = \XippoGmbH\Boot24Bundle\Model\Boot24Model::class;
