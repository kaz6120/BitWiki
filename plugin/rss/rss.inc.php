<?php
/*
 * $Id: rss.inc.php,v 1.4 2005/12/24 00:15:30 youka Exp $
 *
 * このプラグインはhaltさんのrssプラグインを元に作られています。
 */


class Plugin_rss extends Plugin
{
	function init()
	{
		$db = DataBase::getinstance();
		$db->begin();
		
		if(!$db->istable('plugin_rss')){
			$db->exec(file_get_contents(PLUGIN_DIR . 'rss/rss.sql'));
		}
		
		$db->commit();
	}
	
	
	function do_block($pagename, $param1, $param2)
	{
		$param = array_map('trim', explode(',', $param1));
		if(!isset($param[0])){
			throw new PluginException('引数がありません', $this);
		}
		
		$url = $param[0];
		$expire = isset($param[1]) ? (int)$param[1] : 1;
		
		if($expire == 0){
			return $this->getrss($url);
		}
		else{
			$db = DataBase::getinstance();
			$db->begin();
			
			$_url = $db->escape($url);
			$row = $db->fetch($db->query("SELECT data,time FROM plugin_rss WHERE url = '$_url'"));
			if($row == false || $row['time'] + $expire * 60 < time()){
				$data = $this->getrss($url);
				$_data = $db->escape($data);
				$query  = "INSERT OR REPLACE INTO plugin_rss (url,data,time)";
				$query .= " VALUES('$_url', '$_data', " . time() . ")";
				$db->query($query);
			}
			else{
				$data = $row['data'];
			}
			
			$db->commit();
			return $data;
		}
	}
	
	/**
	 * getRSS
	 * 
	 * @access protected
	 */
	protected function getrss($url)
	{
		require_once('HTTP/Request.php');
		
		$req = new HTTP_Request($url, array('timeout' => 4, 'readTimeout' => array(4,0)));
		$result = $req->sendRequest();
		if(PEAR::isError($result)){
			$mes = htmlspecialchars($result->getMessage());
			return "<p class=\"warning\">RSSを読み込めません($mes)。</p>";
		}
		
		$xml = @simplexml_load_string($req->getResponseBody());
		if($xml === false){
			return '<p class="warning">RSSを解釈できません。</p>';
		}
		
		$ret[] = '<ul class="plugin_rss">';
		foreach($xml->item as $item){
			/**
			 * Namespace付きの子要素を取得
			 * この場合、<dc:date>要素が対象
			 */
			$dc = $item->children('http://purl.org/dc/elements/1.1/');
			
			$date = isset($dc->date) ? '&nbsp;(' . date('Y-m-d H:i', strtotime($dc->date)) . ')' : '';
			$_link = htmlspecialchars($item->link);
			$_title = htmlspecialchars(mb_convert_encoding($item->title, 'UTF-8', 'auto'));
			$line = '<li>';
			$line.= "<a href=\"{$_link}\">{$_title}</a>" . $date;
			$line.= '</li>';

			$ret[] = $line;
		}

		$ret[] = '</ul>';

		return join("\n", $ret);
	}
}

?>