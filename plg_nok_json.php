<?php
/**
* @Copyright Copyright (C) 2017 Norbert Kuemin <momo_102@bluemail.ch>
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgContentplg_nok_json extends JPlugin {
	private $debug = array();

	public function onContentPrepare($context, &$article, &$params, $limitstart) {
		$app = JFactory::getApplication();
	  	$globalParams = $this->params;
		$found = false;
		$document = JFactory::getDocument();
		foreach (array('json','jsonfield') as $field) {
			$hits = preg_match_all('#{'.$field.'[\s]+([^}]*)}#s', $article->text, $matches);
			if (!empty($hits)) {
				for ($i=0; $i<$hits; $i++) {
					$entryParamsText = $matches[1][$i];
					$plgParams = $this->json_getParams($globalParams, $entryParamsText);
					if (!$found && ($this->executeOnClient($plgParams) === true)) {
						$doc = JFactory::getDocument();
						$doc->addScript('plugins/content/plg_nok_json/js/json.js');
						$found = true;
					}
					switch ($field) {
						case 'json':
							$html = $this->json_createHtml($i, $plgParams);
							$article->text = str_replace($matches[0][$i], $html, $article->text);
							break;
						case 'jsonfield':
							$html = $this->json_createFieldHtml($i, $plgParams);
							$article->text = str_replace($matches[0][$i], $html, $article->text);
							break;
						default:
							break;
					}
				}
			}
		}
		if (strpos($article->text,'{jsondebug}') !== false) {
			$found = true;
			$article->text = str_replace('{jsondebug}', '<h1>DEBUG-INFORMATION</h1>', $article->text);
		}
		return $found;
	}

	protected function json_getParams($globalParams, $entryParamsText) {

		// Initialize with the global paramteres
		//$entryParamsList['width'] = $globalParams->get('width');

		// Overwrite with the local paramteres
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
//		if ($this->executeOnClient($params) === true) {
			return $this->json_createHtmlClient($id, $params);
//		} else {
//			return $this->json_createHtmlServer($id, $params);
//		}
	}

	protected function json_createHtmlClient($id, $params) {
//		if (isset($params['execute']) { unset($params['execute']); }
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
				$html .= $this->json_createJS("displayJsonAsRecords('".$this->getUrl($params,'url')."','".$elementId."',".$fieldArray.",".$labelArray.",'".$this->hashget($params,'scope')."','".$this->hashget($params,'sortField')."','".$this->hashget($params,'sortDirection')."','".$this->hashget($params,'recordsVar')."');");
				break;
			case "fields":
				$html .= $this->json_createJS("displayJsonAsFields('".$this->getUrl($params,'url')."','".$this->hashget($params,'scope')."');");
				break;
			default:
				$html .= $this->json_generateError('Option view ['.$this->hashget($params,'view').'] unknown.');
				break;
		}
		return $html;
	}

/*
	protected function json_createHtmlServer($id, $params) {
		$elementId = "json_".microtime();
		$html = "\n";
		$fields = explode(',',$this->hashget($params,'fields'));
		$labels = explode(',',$this->hashget($params,'labels'));
		$records = json_decode(file_get_contents($params['url']), true);
		switch ($this->hashget($params,'view')) {
			case "table":
				$html .= $this->json_createHtmlServerTable($elementId, $labels, $fields, $records);
				break;
			case "records":
				$html .= $this->json_createHtmlServerRecords($elementId, $labels, $fields, $records);
				break;
			case "fields":
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
				$html .= '<td>'.$record[$field].'</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>'."\n";
	}

	protected function json_createHtmlServerRecords($elementId, $labels, $fields, $records) {
		$html = '<table id="'.$elementId.'" class="table json">'."\n";
		if(count($labels) > 0) {
			$html .= '<tr><th>'.implode('</th><th>',$labels).'</th></tr>';
		}
		foreach($fields as $key => $field) {
			$html .= '<tr>';
			$html .= '<th>'.$labels[$key].'</th>';
			foreach($records as $record) {
				$html .= '<td>'.$record[$key].'</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>'."\n";
	}
*/

	protected function json_createFieldHtml($id, $params) {
		return "<span id=\"json_field_".$this->hashget($params,'name')."\"></span>";
	}

	protected function getUrl($hashmap) {
		$url = $this->hashget($hashmap, 'url');
		$url = str_replace('&amp;', '&', $url);
		return $url;
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
