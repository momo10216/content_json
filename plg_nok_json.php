<?php
/**
* @Copyright Copyright (C) 2015 Norbert Kuemin <momo_102@bluemail.ch>
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgContentplg_nok_json extends JPlugin {

	public function onContentPrepare($context, &$article, &$params, $limitstart) {
		$app = JFactory::getApplication();
	  	$globalParams = $this->params;
		$found = false;
		$document = JFactory::getDocument();
		foreach (array('json','jsonfield') as $field) {
			$hits = preg_match_all('#{'.$field.'\s+(.*?)}#s', $article->text, $matches);
			if (!empty($hits)) {
				if (!$found) {
					$doc = JFactory::getDocument();
					$doc->addScript('plugins/content/plg_nok_json/js/json.js');
					$found = true;
				}
				for ($i=0; $i<$hits; $i++) {
					$entryParamsText = $matches[1][$i];
					$plgParams = $this->json_getParams($globalParams, $entryParamsText);
					switch ($field) {
						case 'json':
							$html = $this->json_createHtml($i, $plgParams);
							$article->text = preg_replace('/'.$this->json_calculatePattern($matches[0][$i]).'/', $html, $article->text, 1);
							break;
						case 'jsonfield':
							$html = $this->json_createFieldHtml($i, $plgParams);
							$article->text = preg_replace('/'.$this->json_calculatePattern($matches[0][$i]).'/', $html, $article->text, 1);
							break;
						default:
							break;
					}
				}
			}
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
				$item	= explode('=[', $item);
				$name 	= $item[0];
				$value	= strtr($item[1], array('['=>'', ']'=>''));
				$entryParamsList[$name] = $value;
			}
		}
		return $entryParamsList;
	}

	protected function json_calculatePattern($match) {
		$pattern = str_replace('[', '\[', $match);
		$pattern = str_replace(']', '\]', $pattern);
		$pattern = str_replace('/', '\/', $pattern);
		$pattern = str_replace('|', '\|', $pattern);
		return $pattern;
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
		$elementId = "json_".$id;
		$html = "\n";
		$fieldArray = "['".str_replace(",","','",$params['fields'])."']";
		if (empty($params['labels'])) {
			$labelArray = $fieldArray;
		} else {
			$labelArray = "['".str_replace(",","','",$params['labels'])."']";
		}
		switch ($params['view']) {
			case "table":
				$html .= '<table id="'.$elementId.'" class="table json">'."\n".'</table>'."\n";
				$html .= $this->json_createJS("displayJsonAsTable('".$params['url']."','".$elementId."',".$fieldArray.",".$labelArray.",'".$params['scope']."','".$params['sortField']."','".$params['sortDirection']."','".$params['recordsVar']."');");
				break;
			case "records":
				$html .= '<table id="'.$elementId.'" class="table json">'."\n".'</table>'."\n";
				$html .= $this->json_createJS("displayJsonAsRecords('".$params['url']."','".$elementId."',".$fieldArray.",".$labelArray.",'".$params['scope']."','".$params['sortField']."','".$params['sortDirection']."','".$params['recordsVar']."');");
				break;
			case "fields":
				$html .= $this->json_createJS("displayJsonAsFields('".$params['url']."','".$params['scope']."');");
				break;
			default:
				$html .= $this->json_generateError('Option view ['.$params['view'].'] unknown.');
				break;
		}
		return $html;
	}

	protected function json_createFieldHtml($id, $params) {
		return "<span id=\"json_field_".$params['name']."\"></span>";
	}
}
