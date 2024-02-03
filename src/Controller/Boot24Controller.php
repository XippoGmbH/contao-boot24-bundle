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
	
	private function getBooteXml()
	{
		$uri = "https://api.boatvertizer.com/rest?method=boatvertizer.formatted.ads&lan=de&token=" . $this->keyString;
		
		$tmp_doc = new \DOMDocument();
		$tmp_doc->loadHTMLFile($uri);
		
		$var = '<div class="boot_list">';
		
		foreach($tmp_doc->getElementsByTagName('ad') as $ad)
		{
			if($ad->getAttribute('type') == 'sale') {
				$id = $ad->getAttribute('id');
				$title = "";
				$subtitle = "";
				$asking_price = 0;
				$new_or_used = "";
				$boat_category = "";$
				$boat_type = "";
				$manufacturer = "";
				$model = "";
				$built_year = "";
				$length = "";
				$width = "";
				$foto = "";
				
				foreach($ad->getElementsByTagName('field') as $field) {
					($field->getAttribute('id') == 'title' ? $title = $field->nodeValue : "");
					($field->getAttribute('id') == 'subtitle' ? $subtitle = $field->nodeValue : "");
					($field->getAttribute('id') == 'asking_price' ? $asking_price = $field->nodeValue : "");
					($field->getAttribute('id') == 'new_or_used' ? $new_or_used = $field->nodeValue : "");
					($field->getAttribute('id') == 'boat_category' ? $boat_category = $field->nodeValue : "");
					($field->getAttribute('id') == 'boat_type' ? $boat_type = $field->nodeValue : "");
					($field->getAttribute('id') == 'manufacturer' ? $manufacturer = $field->nodeValue : "");
					($field->getAttribute('id') == 'model' ? $model = $field->nodeValue : "");
					($field->getAttribute('id') == 'built_year' ? $built_year = $field->nodeValue : "");
					($field->getAttribute('id') == 'length' ? $length = $field->nodeValue : "");
					($field->getAttribute('id') == 'width' ? $width = $field->nodeValue : "");
					($field->getAttribute('id') == 'foto' ? $foto = $field->nodeValue : "");
				}
				
				$show = false;
				
				$foto = str_replace('-thumbnail.', '-medium.', $foto);
				
				$boot = '<div class="row">';
				$boot .= '<div class="col-lg-2"><img src="'. $foto .'" alt="'.$title.'"></div>';
				$boot .= '<div class="col-lg-7 text-start">';
				$boot .= '<h4>'.$title.'</h4>';
				$boot .= '<p>Preis: '.$asking_price.' <br>';
				$boot .= 'Länge: '.$length.'<br>';
				$boot .= 'Breite: '.$width.'</p>';
				$boot .= '</div>';
				$boot .= '<div class="col-lg-3 text-end detail_button"><div class="button_blue" style="height: 54.4px; clip-path: polygon(8.06422% 0%, 100% 0%, 91.9358% 100%, 0% 100%); position: relative;"><a href="boot-details.html?detail='.$id.'">mehr erfahren</a></div>';
				$boot .= '</div>';
				$boot .= '</div>';
				
				if(str_contains(strtolower($new_or_used), strtolower($this->newOrUsed))) {
					$show = true;
					if(str_contains(strtolower($boat_category), strtolower($this->boatCat))) {
						$show = true;
						if(str_contains(strtolower($this->manufacturer), strtolower($manufacturer)) || strlen($this->manufacturer) == 0) {
							$show = true;
						} else {
							$show = false;
						}
					} else {
						$show = false;
					}
				}
				
				if($show) {
					$var .= $boot;
				}
			}
		}
		
		$var .= '</div>';
		
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
		$this->keyString = trim($model->boot24_token);
		$this->boatCat = trim($model->boot24_categorie);
		$this->manufacturer = trim($model->boot24_manufacturer);
		$this->newOrUsed = trim($model->boot24_newOrUsed);
		
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
			if(strlen($this->keyString) > 9) {
				$template->boots = $this->getBooteXml();
			} else {
				$template->boots = $this->getBoote();
			}
		}
		
		$template->isDetail = $this->isDetail;

        return $template->getResponse();
    }
}
