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
	private bool $isDetail;
	private string $keyString;
	private string $boatCat;
	private string $manufacturer;
	private string $newOrUsed;
	
	private function getBoote()
	{
		$uri = "http://www.boatvertizer.com/dealer/" . $this->keyString . ".html";
		$parameters = array();
		
		if(strlen(strval($this->boatCat)) > 0) 
		{
			array_push($parameters, 'boat_category=' . $this->boatCat);
		}
		
		if(strlen(strval($this->manufacturer)) > 0) 
		{
			array_push($parameters, 'manufacturer=' . $this->manufacturer);
		}
		
		if(strlen(strval($this->newOrUsed)) > 0) 
		{
			array_push($parameters, 'new_or_used=' . $this->newOrUsed);
		}
		
		$uri = $uri . '?' . implode("&", $parameters);
		
		$var = file_get_contents ($uri);
		$var = str_replace('inkl. MwSt.', '', $var);
		$var = str_replace('-thumbnail.', '-medium.', $var);
		$var = preg_replace('/<span[^>]+\>/i', '', $var); 
		$var = str_replace(str_replace('http://www.boatvertizer.com', '', $uri) . '&amp;detail=', 'boot-details.html?detail=', $var);

		$var = utf8_encode($var);
		
		return $var;
	}
	
	private function getBootDetailTitle(int $id)
	{
		$uri = "http://www.boatvertizer.com/dealer/" . $this->keyString . ".html?detail=" . $id;
		$var = file_get_contents ($uri);
		
		$var = preg_replace('/<span[^>]+\>/i', '', $var); 
		
		
		$doc = new \DOMDocument();
		$doc->loadHTML($var);
		$finder = new \DomXPath($doc);
		$var = '';
		
		$titleNode = $finder->query("//*[contains(@class, 'boat_title')]");
		foreach ($titleNode as $node) 
		{
			$tmp_doc = new \DOMDocument();
			$tmp_doc->appendChild($tmp_doc->importNode($node,true));
			$var .= $tmp_doc->saveHTML();
		}
		
		$var = str_replace('bodenseezulassung', 'Bodenseezulassung', $var);
		
		$var = utf8_encode($var);
		
		return $var;
	}
	
	private function getBootDetailTop(int $id)
	{
		$uri = "http://www.boatvertizer.com/dealer/" . $this->keyString . ".html?detail=" . $id;
		$var = file_get_contents ($uri);
		
		$var = preg_replace('/<span[^>]+\>/i', '', $var); 
		
		
		$doc = new \DOMDocument();
		$doc->loadHTML($var);
		$finder = new \DomXPath($doc);
		$var = '';
		
		$topNode = $finder->query("//*[contains(@class, 'boat_top')]");
		foreach ($topNode as $node) 
		{
			$tmp_doc = new \DOMDocument();
			$tmp_doc->appendChild($tmp_doc->importNode($node,true));
			$var .= $tmp_doc->saveHTML();
		}
		
		$var = str_replace('bodenseezulassung', 'Bodenseezulassung', $var);
		
		$var = utf8_encode($var);
		
		return $var;
	}
	
	private function getBootDetailImages(int $id)
	{
		$uri = "http://www.boatvertizer.com/dealer/" . $this->keyString . ".html?detail=" . $id;
		$var = file_get_contents ($uri);
		
		$var = preg_replace('/<span[^>]+\>/i', '', $var); 
		
		
		$doc = new \DOMDocument();
		$doc->loadHTML($var);
		$finder = new \DomXPath($doc);
		$var = '';
		
		$imagePart = '<div id="carouselBoatIndicators" class="carousel slide boat_carousel" data-bs-ride="carousel"><ol class="carousel-indicators">';
		$imageNode = $finder->query("//div[contains(@class, 'boat_images')]//img");
		$x = 0;
		foreach ($imageNode as $node) 
		{
			$tmp_doc = new \DOMDocument();
			$tmp_doc->appendChild($tmp_doc->importNode($node,true));
			if($x == 0) {
				$imageIndicator .= '<li data-bs-target="#carouselBoatIndicators" data-bs-slide-to="'.$x.'" class="active" aria-current="true"></li>';
				$imageContent .= '<div class="carousel-item active">' . str_replace('img src=', 'img class="d-block w-100" src=', $tmp_doc->saveHTML()) . '</div>';
			} else {
				$imageIndicator .= '<li data-bs-target="#carouselBoatIndicators" data-bs-slide-to="'.$x.'"></li>';
				$imageContent .= '<div class="carousel-item">' . str_replace('img src=', 'img class="d-block w-100" src=', $tmp_doc->saveHTML()) . '</div>';
			}
			$x++;
		}
		
		$imagePart .= $imageIndicator;
		$imagePart .= '</ol><div class="carousel-inner">';

		$imagePart .= str_replace('-thumblarge.jpg', '-large.jpg', $imageContent);
		$imagePart .= '</div><button class="carousel-control-prev" type="button" data-bs-target="#carouselBoatIndicators" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button><button class="carousel-control-next" type="button" data-bs-target="#carouselBoatIndicators" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button></div>';
		
		$var .= $imagePart;
		
		$var = str_replace('bodenseezulassung', 'Bodenseezulassung', $var);
		
		$var = utf8_encode($var);
		
		return $var;
	}
	
	private function getBootDetailFeatures(int $id, int $featuresId)
	{
		$uri = "http://www.boatvertizer.com/dealer/" . $this->keyString . ".html?detail=" . $id;
		$var = file_get_contents ($uri);
		
		$var = preg_replace('/<span[^>]+\>/i', '', $var); 
		
		
		$doc = new \DOMDocument();
		$doc->loadHTML($var);
		$finder = new \DomXPath($doc);
		$var = '';
		
		$details1Node = $finder->query("//*[contains(@class, 'boat_details". $featuresId ."')]");
		foreach ($details1Node as $node) 
		{
			$tmp_doc = new \DOMDocument();
			$tmp_doc->appendChild($tmp_doc->importNode($node,true));
			$var .= $tmp_doc->saveHTML();
		}
		
		$var = str_replace('bodenseezulassung', 'Bodenseezulassung', $var);
		
		$var = utf8_encode($var);
		
		return $var;
	}

    protected function getResponse(Template $template, ContentModel $model, Request $request): ?Response
    {
		$this->keyString = $model->boot24_token;
		$this->boatCat = $model->boot24_categorie;
		$this->manufacturer = $model->boot24_manufacturer;
		$this->newOrUsed = $model->boot24_newOrUsed;
		
		if($request->query->get('detail') > 0)
		{
			$this->isDetail = true;
			$template->boot_detail_title = $this->getBootDetailTitle(intval($request->query->get('detail')));
			$template->boot_detail_top = $this->getBootDetailTop(intval($request->query->get('detail')));
			$template->boot_detail_images = $this->getBootDetailImages(intval($request->query->get('detail')));
			$template->boot_detail_features1 = $this->getBootDetailFeatures(intval($request->query->get('detail')), 1);
			$template->boot_detail_features2 = $this->getBootDetailFeatures(intval($request->query->get('detail')), 2);
			$template->boot_detail_features3 = $this->getBootDetailFeatures(intval($request->query->get('detail')), 3);
			$template->boot_detail_features4 = $this->getBootDetailFeatures(intval($request->query->get('detail')), 4);
			$template->boot_detail_features5 = $this->getBootDetailFeatures(intval($request->query->get('detail')), 5);
			$template->boot_detail_features6 = $this->getBootDetailFeatures(intval($request->query->get('detail')), 6);
		} else {
			$this->isDetail = false;
			$template->boots = $this->getBoote();
		}
		
		$template->isDetail = $this->isDetail;

        return $template->getResponse();
    }
}
