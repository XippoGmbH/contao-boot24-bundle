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
declare(strict_types=1);

namespace XippoGmbH\Boot24Bundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\ContentModel;
use Contao\FilesModel;
use Contao\Frontend;
use Contao\Image;
use Contao\Model;
use Contao\Template;
use XippoGmbH\Boot24Bundle\Model\Boot24Model;
use XippoGmbH\Boot24Bundle\Model\MapsMarkerModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Boot24Controller extends AbstractContentElementController
{
	public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    protected function getResponse(Template $template, ContentModel $model, Request $request): ?Response
    {
		$maps = Boot24Model::findBy('id', $model->content_boot24);

		if (!$maps instanceof Boot24Model) {
            return $template->getResponse();
        }

		\System::log('Maps gefunden, die ID ist: ' . $maps->id, __METHOD__, TL_GENERAL);

		$tempMapsMarkers = MapsMarkerModel::findBy('pid', $maps->id);

		$maps->mapsMarkerCount = count($tempMapsMarkers);
		$maps->mapHeight = \StringUtil::deserialize($maps->height);
		$maps->mapWidth = \StringUtil::deserialize($maps->width);

		$template->maps = $maps;
        $mapsMarkers = [];

		if($tempMapsMarkers->count() > 0)
		{
			foreach($tempMapsMarkers as $tempMapsMarker)
			{
				\System::log('Maps Item ID: ' . $tempMapsMarker->id, __METHOD__, TL_GENERAL);

                $elements = [];

				$content = ContentModel::findPublishedByPidAndTable($tempMapsMarker->id, 'tl_xippo_boot24_marker');

				if (null !== $content) {
					$count = 0;
					$last = $content->count() - 1;

					while ($content->next()) {
						$css = [];

						/** @var ContentModel $objRow */
						$row = $content->current();

						if (0 === $count) {
							$css[] = 'first';
						}

						if ($count === $last) {
							$css[] = 'last';
						}

						$row->classes = $css;
						$elements[] = Frontend::getContentElement($row, $model->strColumn);
						++$count;
					}
				}

				$tempMapsMarker->content = $elements;

				$mapsMarkers[] = $tempMapsMarker;
			}
		}

		$template->mapsMarkers = $mapsMarkers;

        return $template->getResponse();
    }
}
