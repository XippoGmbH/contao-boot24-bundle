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
	/*
	* caching for the api calls
	*
	* @var int caching time in seconds, if set to 0 -> caching is disabled
	*/
	public $cache_time = 0;

	/*
	* cache directory, has to be writeable
	*
	* @var string absolute path to cache directory
	*/
	public $cache_dir = null;

	/*
	* set this to true, if you want to decode the utf8-data from the xml
	*
	* @var boolean enable utf8-decoding
	*/
	public $utf8_decode = true;

	/*
	* your api token
	*
	* @var string api token
	*/
	private $token;
	
	/*
	* define the language in the constructor if you to have
	* the translation for some content, e.g country names or equipment
	*
	* @var string language code (e.g. en,de,fr)
	*/
	protected $language;
	
	/*
	* api endpoint url 
	*
	* @var string api url
	*/
	private $rest_url = 'https://api.boatvertizer.com/rest';
	
	/*
	* deliver SSL URLs
	*
	* @var boolean sll
	*/
	
	public $ssl = true;
	
	/*
	* namespace for the rest calls
	* 
	* @var string boatvertizer, doesn't change
	*/
	private  $rest_ns = 'boatvertizer';
	
	/*
	* stop the script if an error occurs
	*
	* @var boolean enable or disable this feature
	* @see error()
	*/
	private $die_on_error = true;
	
	/*
	* constructor
	* 
	* @param string your boatvertizer token
	* @param string language code (e.g. en,de,fr), define it if you want the results in your language
	*
	*/
	public function __construct (ContaoFramework $framework, $token=null, $language='de')
	{
		$this->framework = $framework;
		$this->token = $token;
		$this->language = $language;
	}
		
	/*
	* get all ads
	* 
	* @return array returns an array with a similar structure like the XML feed
	*
	*/
	public function getAds ()
	{
		$p = array('lan' => $this->language);
	
		$payload = $this->restCall('ads', $p);
		
		return $this->parseAdverts ($payload);
	}
	
	/*
	* get a single ad
	*
	* @param int the boatvertizer advert id
	* @return array returns an array with a similar structure like the XML feed
	*
	*/
	public function getAd ($id)
	{
		$p = array('ad' => $id, 'lan' => $this->language);
	
		$payload = $this->restCall('ads', $p);
		
		return $this->parseAdverts ($payload);
	}
	
	/*
	* get all pending updates
	*
	* @return array returns an array with all advert ids with pending updates
	*
	*/
	public function getUpdates ($limit=0)
	{
		$payload = $this->restCall('updates');
		
		$updates = array();
		
		$i=0;
		foreach($payload->getElementsByTagName('advert') as $ad)
		{
			$updates[] = $ad->getAttribute('id');
			
			$i++;
			if($limit>0 && $i>=$limit) break;
		}
		
		return $updates;
	}
	
	/*
	* report advert status
	*
	* @param int the boatvertizer ad id
	* @param string the advert status on your portal
	* @param string the advert id on your portal
	* @param string the public URL to the advert on your portal
	* @return void
	*
	*/
	public function reportAdvertStatus ($advert_id, $status, $portal_id=null, $portal_url=null, $error_message=null)
	{
		if(!in_array($status, array('active','inactive','deleted')))
		{
			if(strstr($status, 'error')) $this->error('please use reportAdvertError or reportLoginError to report errors');
			else $this->error('invalid status');
			return;
		}
		
		$p = array('status' => $status, 'ad' => $advert_id);
		
		if(!is_null($portal_id)) $p['portal_id'] = $portal_id;
		if(!is_null($portal_url)) $p['portal_url'] = $portal_url;
		if(!is_null($error_message)) $p['message'] = $error_message;
		
		$payload = $this->restCall('report', $p);
		
		return;
	}
	
	/*
	* report advert error
	*
	* @param int the boatvertizer ad id
	* @param string error message
	* @return void
	*
	*/
	public function reportAdvertError ($advert_id, $error_message)
	{
		$p = array('status' => 'error', 'ad' => $advert_id, 'message' => $error_message);
		
		$payload = $this->restCall('report', $p);
		
		return;
	}
	
	/*
	* report login error
	*
	* @param int the boatvertizer ad id
	* @return void
	*
	*/
	public function reportLoginError ($advert_id)
	{
		$p = array('status' => 'login_error', 'ad' => $advert_id);
		
		$payload = $this->restCall('report', $p);
		
		return;
	}
	
	/*
	* get all countries
	*
	* @param string filter countries by currency
	* @return array all countries
	*
	*/
	public function getCountries ($currency=null)
	{
		$p = array('lan' => $this->language);
	
		if(!is_null($currency)) $p['currency'] = $currency;
	
		$payload = $this->restCall('countries', $p, false);
		
		return $this->parseCountries ($payload);
	}
	
	/*
	* get single country by code
	*
	* @param string ISO 3166 country code
	* @return array country and currency
	*
	*/
	public function getCountry ($code)
	{
		$p = array('code' => $code, 'lan' => $this->language);
		
		$payload = $this->restCall('countries', $p);
		
		$countries = $this->parseCountries ($payload, false);
		
		if(!isset($countries[$code])) $this->error('unknown country '.$code);
		
		return $countries[$code];
	}
	
	/*
	* get all possible equipment values
	*
	* @return array equipment groups with possible items
	*
	*/
	public function getEquipment ()
	{
		$p = array('lan' => $this->language);
	
		$payload = $this->restCall('equipment', $p);
		
		$equipment = array();
		
		foreach($payload->getElementsByTagName('group') as $group)
		{
			$g = array();
			
			foreach($group->getElementsByTagName('item') as $item) $g[$item->getAttribute('name')] = $this->decode($item->nodeValue);
			
			$equipment[$group->getAttribute('name')] = $g;
		}
		
		return $equipment;
	}
	
	/*
	* get all manufacturers from the boatvertizer database
	* 
	* @return array all manufacturers
	*
	*/
	public function getManufacturers ()
	{
		$payload = $this->restCall('manufacturers');
		
		$manufacturer = array();
		
		foreach($payload->getElementsByTagName('manufacturer') as $m)
		{
			$manufacturer[$m->getAttribute('code')] = $this->decode($m->nodeValue);
		}
		
		return $manufacturer;
	}
	
	/*
	* get a single manufacturer from the boatvertizer database
	* 
	* @param string manufacturer code
	* @return string manufacturer name
	*
	*/
	public function getManufacturer ($code)
	{
		$payload = $this->restCall('manufacturers', array('code' => $code));
		
		$manufacturer = array();
		
		foreach($payload->getElementsByTagName('manufacturer') as $m)
		{
			return $this->decode($m->nodeValue);
		}
	
		$this->error('unknown manufacturer code '.$code);
	}
	
	/*
	* get all possible values of the boatvertizer ads XML field types
	*
	* @param string type class
	* @return array all values
	*
	*/
	public function getTypes ($type)
	{
		$p = array('lan' => $this->language, 'type' => $type);
		
		$payload = $this->restCall('types', $p, false);
		
		$types = array();
		
		foreach($payload->getElementsByTagName('type') as $type)
		{
			$types[$type->getAttribute('name')] = $this->decode($type->nodeValue);
		}
		
		return $types;
	}
	
	/*
	* sort the ads by the specified field value
	*
	* @param array the array from getAds
	* @param string the field name
	* @param boolean sort the array ascending or descending
	* @return array sorted ads
	*
	*/
	function sortAds (&$ads, $field, $descending=false)
	{
		$sort = array();
		
		foreach($ads as $id => $data)
		{
			if($field=='asking_price')
			{
				if($data['type']=='sale') $sortval = $data['advert_features'][$field]['price'];
				else $sortval = $data['advert_features']['charter_group']['prices'][0]['price'];
			}
			else
			{
				if(!isset($data[$field])) $field = 'id';
				$sortval = $data[$field];
			}
			
			$sort[$id] = $sortval;
		}
		
		if($descending) arsort($sort); 
		else asort($sort);
				
		$ok = array();
		foreach($sort as $id => $s) $ok[$id] = $ads[$id];
		
		return $ok;
	}
	
	
	/*
	* get a formatted output of all adverts
	*
	* @return array formatted advert data
	*
	*/
	function getFormattedAds ()
	{
		$p = array('token' => $this->token, 'lan' => $this->language);
		
		$payload = $this->restCall('formatted.ads', $p, false);
		
		$data = $this->parseFormattedAdverts ($payload);
		
		return $data;
	}
	
	/*
	* get formatted output of a single advert
	*
	* @param int advert id
	*
	* @return array formatted advert data
	*
	*/
	function getFormattedAd ($ad)
	{
		$p = array('token' => $this->token, 'lan' => $this->language, 'ad' => $ad);
		
		$payload = $this->restCall('formatted.ads', $p, false);
		
		$data = $this->parseFormattedAdverts ($payload);
		
		if(isset($data[$ad])) return $data[$ad];
		
		return null;
	}	
	
	private function parseAdverts ($payload)
	{
		$d = array();
		
		foreach($payload->getElementsByTagName('broker') as $broker)
		{
			$b = $this->attributesToArray($broker);
			
			// Broker Details
			$this->childrenToArray($broker->getElementsByTagName('broker_details')->item(0), $b);
			
			// Offices
			$b['offices'] = array();
			foreach($broker->getElementsByTagName('office') as $office)
			{
				$b['offices'][$office->getAttribute('id')] = $this->childrenToArray($office);
			}
			
			// Sale Persons
			$b['sale_persons'] = array();
			foreach($broker->getElementsByTagName('sale_person') as $sale_person)
			{
				$b['sale_person'][$office->getAttribute('id')] = $this->childrenToArray($sale_person);
			}
			
			// Ads
			$b['ads'] = array();
			foreach($broker->getElementsByTagName('advert') as $ad)
			{
				$a = $this->attributesToArray($ad);
				
				if($a['sale_status']!='delete')
				{
					
					$a['title'] = '';
					
					// Images
					$a['images'] = array();
					foreach($ad->getElementsByTagName('media') as $media)
					{
						$image = $this->attributesToArray($media);
						$image['url'] = $media->nodeValue;
						$a['images'][] = $image;
					}
					
					// Advert Featues
					$a['advert_features'] = array();
					foreach($ad->getElementsByTagName('advert_features')->item(0)->childNodes as $child)
					{
						if($child->nodeType!=XML_ELEMENT_NODE) continue;
						
						// Manufacturer
						if($child->tagName=='manufacturer')
						{
							$a['advert_features']['manufacturer'] = array('id' => $child->getAttribute('id'), 'name' => $this->decode($child->nodeValue));
							continue;
						}
						
						// Lying
						if($child->tagName=='vessel_lying')
						{
							$a['advert_features']['vessel_lying'] = array('country' => $child->getAttribute('country'), 'location' => $this->decode($child->nodeValue), 'mooring_available' => $child->hasAttribute('mooring_available'), 'lat' => $child->getAttribute('lat'), 'lon' => $child->getAttribute('lon'));
							if($child->hasAttribute('region')) $a['advert_features']['vessel_lying']['region'] = $child->getAttribute('region');
							continue;
						}
						
						// Asking Price
						if($child->tagName=='asking_price')
						{
							$a['advert_features']['asking_price'] = array('currency' => '', 'price' => 0, 'poa' => false, 'vat_included' => null);
							if($child->hasAttribute('poa') && $child->getAttribute('poa')=='true') $a['advert_features']['asking_price']['poa'] = true;
							else
							{
								$a['advert_features']['asking_price']['currency'] = $child->getAttribute('currency');
								$a['advert_features']['asking_price']['price'] = $child->nodeValue;
								if($child->hasAttribute('vat_included')) $a['advert_features']['asking_price']['vat_included'] = $child->getAttribute('vat_included')=='true'?true:false;
								$a['advert_features']['asking_price']['vat_stated_separately'] = $child->getAttribute('vat_stated_separately')=='true';
								$a['advert_features']['asking_price']['tax_paid'] = $child->getAttribute('tax_paid');
							}
							continue;
						}
						
						// Charter Group
						if($child->tagName=='charter_group')
						{
							$a['advert_features']['charter_group'] = array();
							
							foreach($child->childNodes as $charter)
							{
								if($charter->nodeType!=XML_ELEMENT_NODE) continue;
								
								if($charter->tagName=='crew')
								{
									$a['advert_features']['charter_group']['charter_type'] = $charter->getAttribute('type');
									$a['advert_features']['charter_group']['crew_number'] = $charter->nodeValue;
									continue;
								}
								
								if($charter->tagName=='charter_regions')
								{
									$a['advert_features']['charter_group']['regions'] = array();
									foreach($charter->getElementsByTagName('region') as $region) $a['advert_features']['charter_group']['regions'][] = array('country' => $region->getAttribute('country'), 'name' => $this->decode($region->nodeValue));
									continue;
								}
								
								if($charter->tagName=='charter_prices')
								{
									$a['advert_features']['charter_group']['prices'] = array();
									foreach($charter->getElementsByTagName('price') as $price) $a['advert_features']['charter_group']['prices'][] = array('duration' => $price->getAttribute('duration'), 'from' => $price->getAttribute('from'), 'to' => $price->getAttribute('to'), 'currency' => $price->getAttribute('currency'), 'price' => $price->nodeValue);
									continue;
								}
							}
							continue;
						}
						
						// Marketing Desc
						if($child->tagName=='marketing_descs')
						{
							$a['advert_features']['marketing_descs'] = array();
							foreach($child->getElementsByTagName('marketing_desc') as $desc) $a['advert_features']['marketing_descs'][] = array('language' => $desc->getAttribute('language'), 'text' => $this->decode($desc->nodeValue));
							continue;
						}
						
						$a['advert_features'][$child->tagName] = $this->decode($child->nodeValue);
					}
					
					// Boat Featues
					$a['boat_features'] = array();
					foreach($ad->getElementsByTagName('boat_features')->item(0)->childNodes as $child)
					{
						if($child->nodeType!=XML_ELEMENT_NODE) continue;
						
						// Range
						if($child->tagName=='range')
						{	
							$a['boat_features'][$child->tagName] = $this->unitValue($child);
							continue;
						}
						
						// Dimensions && Sails
						if(in_array($child->tagName, array('dimensions_group','rig_sails_group')))
						{
							foreach($child->childNodes as $dimension)
							{
								if($dimension->nodeType!=XML_ELEMENT_NODE) continue;
							
								if($dimension->hasAttribute('boatdraftcode') && $dimension->getAttribute('boatdraftcode')=='keelup')  $a['boat_features'][$child->tagName][$dimension->tagName.'_'.$dimension->getAttribute('boatdraftcode')] = $this->unitValue($dimension);
								else $a['boat_features'][$child->tagName][$dimension->tagName] = $this->unitValue($dimension);
							}
							continue;
						}
						
						// Other groups
						if(in_array($child->tagName, array('engine_group','build_group','accommodation_group')))
						{
							foreach($child->childNodes as $engine)
							{
								if($engine->nodeType!=XML_ELEMENT_NODE) continue;
								$a['boat_features'][$child->tagName][$engine->tagName] = (in_array($engine->tagName, array('cruising_speed','max_speed','engine_power','tankage','keel_ballast','displacement','fresh_water_tank','holding_tank')) ? $this->unitValue($engine) : $this->decode($engine->nodeValue));
							}
							continue;
						}
						
						// Equipment
						if($child->tagName=='equipment_group')
						{
							$a['boat_features'][$child->tagName] = array();
							foreach($child->getElementsByTagName('item') as $item) $a['boat_features'][$child->tagName][$item->getAttribute('name')] = $this->decode(trim($item->nodeValue));
							continue;
						}
						
						// Additional Fields
						if($child->tagName=='additional_field_group')
						{
							$a['boat_features'][$child->tagName] = array();
							foreach($child->getElementsByTagName('field') as $item) $a['boat_features'][$child->tagName][] = array('name' => $item->getAttribute('name'), 'content' => $this->decode(trim($item->nodeValue)), 'language' => $item->getAttribute('language'));
							continue;
						}
					
						$a['boat_features'][$child->tagName] = $this->decode($child->nodeValue);
					}
					
					// Additional
					$a['title'] = trim($a['advert_features']['manufacturer']['name'].' '.$a['advert_features']['model']);
				
				}
				
				$b['ads'][$a['id']] = $a;
			}
			
			$d['broker'][$b['code']] = $b;
		}
		
		return $d;
	}
	
	private function parseFormattedAdverts ($payload)
	{
		$data = array();
		
		foreach($payload->getElementsByTagName('ad') as $ad)
		{
			$d = array('id' => $ad->getAttribute('id'), 'advert_type' => $ad->getAttribute('type'));
		
			foreach($ad->getElementsByTagName('field') as $field) $d[$field->getAttribute('id')] = array('label' => $this->decode($field->getAttribute('label')), 'content' => $this->decode($field->nodeValue));

			foreach($ad->getElementsByTagName('list') as $list) 
			{
				$items = array();
				foreach($list->getElementsByTagName('item') as $item) $items[$item->getAttribute('id')] = $this->decode($item->nodeValue);
				$d[$list->getAttribute('id')] = array('label' => $this->decode($list->getAttribute('label')), 'content' => $items);
			}

			$d['media'] = array();
			foreach($ad->getElementsByTagName('media') as $media) $d['media'][] = array('type' => $media->getAttribute('type'), 'title' => $this->decode($media->getAttribute('title')), 'description' => $this->decode($media->getAttribute('description')), 'url' => $this->decode($media->nodeValue));

			
			$data[$ad->getAttribute('id')] = $d;
		}
		
		return $data;
	}
	
	private function parseCountries ($payload)
	{
		$countries = array();
		
		foreach($payload->getElementsByTagName('country') as $c)
		{
			$countries[$c->getAttribute('code')] = array('name' => $this->decode($c->nodeValue), 'currency' => $c->getAttribute('currency'));
		}
		
		return $countries;
	}
	
	protected function restCall ($method, $params=array(), $useToken=true)
	{
		if($this->ssl) $params['ssl'] = 1;
	
		$url = $this->restBuildUrl($method, $params, $useToken);
			
		$xml = $this->loadFile($url);

		$doc = new DomDocument();
		if(@$doc->loadXml($xml)==false) $this->error('unable to parse XML '.$url);
		else
		{
			$response = $doc->firstChild;
			
			if($response == null || $response->nodeName != 'response') $this->error('invalid REST response');
		
			if(!$response->hasAttribute('status')) $this->error('invalid REST response');
		
			if($response->getAttribute('status')!='ok') $this->restError($response);
			
			return $response;
		}
	}
	
	private function restError ($node)
	{		
		foreach($node->getElementsByTagName('error') as $error)
		{
			$msg = '';

			if($error->hasAttribute('method')) $msg .= $error->getAttribute('method').' ';
			
			if($error->hasAttribute('code')) $msg .= 'REST error '.$error->getAttribute('code').' ';
			
			$msg .= $error->nodeValue;
		
			if($error->hasAttribute('remote_address')) $msg .= ' - your IP '.$error->getAttribute('remote_address').' ';
		
			$this->error($msg);
		}
		
		$this->error('unknown REST error');
	}
	
	private function restBuildUrl ($method, $params=array(), $useToken=true)
	{	
		$params['method'] = $this->rest_ns.'.'.$method;
	
		if($useToken && $this->token!=null) $params['token'] = $this->token;
	
		$url = $this->rest_url.'?'.http_build_query($params);
		
		return $url;
	}
	
	private function loadFile ($url)
	{
		if($this->cache_time > 0)
		{
			$filename = md5($url).'.xml';
			$file = $this->cache_dir.$filename;
			
			if(file_exists($file) && filemtime($file)>time()-$this->cache_time) return file_get_contents($file); 
		}
	
		$xml = file_get_contents($url);
		
		if(empty($xml)) $this->error('error loading XML');
		
		if($this->cache_time > 0 && file_exists($this->cache_dir) && is_writeable($this->cache_dir))
		{
			file_put_contents($file, $xml);
		}
		
		return $xml;
	}
	
	private function error ($msg)
	{
		if($this->die_on_error) die('phpBoatvertizer: '.$msg);
	}
	
	protected function childrenToArray ($node, &$data=array())
	{
		foreach($node->childNodes as $child)
		{
			if($child->nodeType!=XML_ELEMENT_NODE) continue;
			$data[$child->tagName] = $this->decode($child->nodeValue);
		}
		
		return $data;
	}
	
	protected function attributesToArray ($node, &$data=array())
	{
		for($i=0;$i<$node->attributes->length;$i++) $data[$node->attributes->item($i)->name] = $this->decode($node->attributes->item($i)->value);
		
		return $data;
	}
	
	protected function unitValue ($node)
	{
		$value = array('value' => $node->nodeValue, 'unit' => '');
		
		if($node->hasAttribute('unit')) $value['unit'] = $node->getAttribute('unit');
		
		return $value;
	}
	
	protected function decode ($str)
	{
		if($this->utf8_decode) return utf8_decode($str);
		
		return $str;
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
