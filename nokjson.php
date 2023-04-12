<?php
/**
* @version	$Id$
* @package	Joomla
* @subpackage	Content JSON plugin
* @copyright	Copyright (c) 2017 Norbert Kuemin. All rights reserved.
* @license	http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE
* @author	Norbert Kuemin
* @authorEmail	momo_102@bluemail.ch
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

class PlgContentNokjson extends CMSPlugin {
	private $debug = array();
	private $fieldExecute = 'client';
	private $fieldvalues = array();

	public function onContentPrepare($context, &$row, $params, $page = 0) {
		$found = false;
		foreach (array('json','jsonfield') as $field) {
			$hits = preg_match_all('#{'.$field.'[\s]+([^}]*)}#s', $row->text, $matches);
			if (!empty($hits)) {
				for ($i=0; $i<$hits; $i++) {
					$entryParamsText = $matches[1][$i];
					$plgParams = $this->json_getParams($entryParamsText);
					if (!$found && ($this->executeOnClient($plgParams) === true)) {
						$doc = JFactory::getDocument();
						$doc->addScript('plugins/content/nokjson/js/json.js');
						$found = true;
					}
					switch ($field) {
						case 'json':
							$html = $this->json_createHtml($i, $plgParams);
							$row->text = str_replace($matches[0][$i], $html, $row->text);
							break;
						case 'jsonfield':
							$html = $this->json_createFieldHtml($i, $plgParams);
							$row->text = str_replace($matches[0][$i], $html, $row->text);
							break;
						default:
							break;
					}
				}
			}
		}
		if (strpos($row->text,'{jsondebug}') !== false) {
			$found = true;
			$row->text = str_replace('{jsondebug}', '<h1>DEBUG-INFORMATION</h1>'.implode('<br/>',$this->debug), $row->text);
		}
		return $found;
	}

	protected function json_getParams($entryParamsText) {
		$items = explode('] ', $entryParamsText);
		foreach ($items as $item) {
			if ($item != '') {
				$item	= explode('[', $item);
				$name 	= trim($item[0], '=[]');
				$value	= trim($item[1], '[]');
				$entryParamsList[$name] = $value;
			}
		}
		return $entryParamsList;
	}

	protected function json_createJS($js) {
		$retval = "<script language=\"javascript\" type=\"text/javascript\">\n";
		$retval .= $js;
		$retval .= "\n</script>\n";
		return $retval;
	}

	protected function json_generateError($errorText) {
		return "<p><strong>Error:</strong> ".$errorText."</p>\n";
	}

	protected function json_createHtml($id, $params) {
		if ($this->executeOnClient($params) === true) {
			return $this->json_createHtmlClient($id, $params);
		} else {
			return $this->json_createHtmlServer($id, $params);
		}
	}

	protected function json_createHtmlClient($id, $params) {
		if (isset($params['execute'])) { unset($params['execute']); }
		$elementId = "json_".$id;
		$html = "\n";
		$fieldArray = "['".str_replace(",","','",$this->hashget($params,'fields'))."']";
		$labels = $this->hashget($params,'labels');
		if (empty($labels)) {
			$labelArray = $fieldArray;
		} else {
			$labelArray = "['".str_replace(",","','",$labels)."']";
		}
		switch ($this->hashget($params,'view')) {
			case "table":
				$html .= '<table id="'.$elementId.'" class="table json">'."\n".'</table>'."\n";
				$html .= $this->json_createJS("displayJsonAsTable('".$this->getUrl($params)."','".$elementId."',".$fieldArray.",".$labelArray.",'".$this->hashget($params,'scope')."','".$this->hashget($params,'sortField')."','".$this->hashget($params,'sortDirection')."','".$this->hashget($params,'recordsVar')."');");
				break;
			case "records":
				$html .= '<table id="'.$elementId.'" class="table json">'."\n".'</table>'."\n";
				$html .= $this->json_createJS("displayJsonAsRecords('".$this->getUrl($params)."','".$elementId."',".$fieldArray.",".$labelArray.",'".$this->hashget($params,'scope')."','".$this->hashget($params,'sortField')."','".$this->hashget($params,'sortDirection')."','".$this->hashget($params,'recordsVar')."');");
				break;
			case "fields":
				$html .= $this->json_createJS("displayJsonAsFields('".$this->getUrl($params)."','".$this->hashget($params,'scope')."');");
				break;
			default:
				$html .= $this->json_generateError('Option view ['.$this->hashget($params,'view').'] unknown.');
				break;
		}
		return $html;
	}


	protected function json_createHtmlServer($id, $params) {
		$elementId = "json_".$id;
		$html = "\n";
		$fields = explode(',',$this->hashget($params,'fields'));
		$labels = explode(',',$this->hashget($params,'labels'));
		$records = json_decode($this->getData($params), true);
		if (!empty($this->hashget($params,'recordsVar'))) {
			if (isset($records[$this->hashget($params,'recordsVar')])) {
				$records = $records[$this->hashget($params,'recordsVar')];
			} else {
				if (isset($records[0]) && isset($records[0][$this->hashget($params,'recordsVar')])) {
					$records = $records[0][$this->hashget($params,'recordsVar')];
				}
			}
		}
		switch ($this->hashget($params,'view')) {
			case "table":
				$html .= $this->json_createHtmlServerTable($elementId, $labels, $fields, $records);
				break;
			case "records":
				$html .= $this->json_createHtmlServerRecords($elementId, $labels, $fields, $records);
				break;
			case "fields":
				$this->fieldvalues = $records;
				$this->fieldExecute = 'server';
				break;
			default:
				$html .= $this->json_generateError('Option view ['.$this->hashget($params,'view').'] unknown.');
				break;
		}
		return $html;
	}

	protected function json_createHtmlServerTable($elementId, $labels, $fields, $records) {
		$html = '<table id="'.$elementId.'" class="table json">'."\n";
		if(count($labels) > 0) {
			$html .= '<tr><th>'.implode('</th><th>',$labels).'</th></tr>';
		}
		foreach($records as $record) {
			$html .= '<tr>';
			foreach($fields as $field) {
				$html .= '<td>'.(isset($record[$field]) ? $record[$field] : '').'</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>'."\n";
		return $html;
	}

	protected function json_createHtmlServerRecords($elementId, $labels, $fields, $records) {
		$html = '<table id="'.$elementId.'" class="table json">'."\n";
		foreach($fields as $key => $field) {
			$html .= '<tr>';
			$html .= '<th>'.(isset($labels[$key]) ? $labels[$key] : '').'</th>';
			foreach($records as $record) {
				$html .= '<td>'.(isset($record[$field]) ? $record[$field] : '').'</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>'."\n";
		return $html;
	}

	protected function json_createFieldHtml($id, $params) {
		if ($this->fieldExecute == 'client') {
			return $this->json_createFieldHtmlClient($id, $params);
		} else {
			return $this->json_createFieldHtmlServer($id, $params);
		}
	}

	protected function json_createFieldHtmlClient($id, $params) {
		return "<span id=\"json_field_".$this->hashget($params,'name')."\"></span>";
	}

	protected function json_createFieldHtmlServer($id, $params) {
		return '<span id="json_field_'.$this->hashget($params,'name').'">'.$this->formatServerValue($params,'name').'</span>';
	}

	protected function formatServerValue($params,$key) {
		$value = '';
		if (isset($this->fieldvalues[$this->hashget($params,$key)])) {
			$value = $this->fieldvalues[$this->hashget($params,$key)];
			if ((substr($value,0,7) == 'http://') || (substr($value,0,8) == 'https://')) {
				$value = '<a href="'.$value.'">Link</a>';
			}
		}
		return $value;
	}

	protected function getUrl($hashmap) {
		$url = $this->hashget($hashmap, 'url');
		$url = str_replace('&amp;', '&', $url);
		return $url;
	}

	protected function getCachingTime($hashmap) {
		if (isset($hashmap['caching'])) {
			if ($hashmap['caching'] == 'no') { return '0'; }
			return $hashmap['caching'];
		}
		return '0';
	}

	protected function getData($hashmap) {
		$url = $this->getUrl($hashmap);
		$cachingTime = intval($this->getCachingTime($hashmap));
		if ($cachingTime > 0) {
			$cache = JFactory::getCache('JsonCache', '');
			$cache->setCaching(true);
			$cache->setLifeTime($cachingTime);
			$cached_data = $cache->get($url);
			if (empty($cached_data)) {
				$cached_data = file_get_contents($url);
				$cache->store($cached_data, $url);
			}
			return $cached_data;
		}
		return file_get_contents($url);
	}

	protected function hashget($hashmap, $key) {
		if (isset($hashmap[$key])) {
			return $hashmap[$key];
		}
		return "";
	}

	protected function executeOnClient($params) {
		if (!isset($params['execute']) || ($params['execute'] != 'server')) { return true; }
		return false;
	}
}
?>