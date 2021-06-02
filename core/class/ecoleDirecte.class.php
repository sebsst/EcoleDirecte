<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
/*

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ecoleDirecte extends eqLogic {
	public static $_widgetPossibility = array('custom' => true);
	/*     * *************************Attributs****************************** */

	public static $_infosMap = array();
	public static $_actionMap = array();
	/*     * ***********************Methode static*************************** */

	/*
	* Fonction exécutée automatiquement toutes les minutes par Jeedom
	public static function cron() {

}

*/

public static function initInfosMap(){

	self::$_actionMap = array(

					'Refresh' => array(
						'name' => 'Refresh',
						'cmd' => 'Refresh',

					),

				);

				self::$_infosMap = array(
					'devoirsSemaine' => array(
						'name' => __('devoirSemaine',__FILE__),
						'type' => 'info',
						'subtype' => 'string',
						'isvisible' => 1,

					),
					'devoirSemaineProchaine' => array(
						'name' => __('devoirSemaineProchaine',__FILE__),
						'type' => 'info',
						'subtype' => 'string',
						'isvisible' => 1,

					),


					//	),

				);
			}

			public static function cronHourly() {
				$notfound = true;
				foreach (eqLogic::byType('ecoleDirecte') as $ecoleDirecte)
				{
						$ecoleDirecte->getInformations();
						$ecoleDirecte->refreshWidget();
				}

			}

			/*
			* Fonction exécutée automatiquement toutes les heures par Jeedom
			public static function cronHourly() {

		}
		*/


		/*     * *********************Méthodes d'instance************************* */

		public function refresh() {
			try {
				$this->getInformations();
				$ecoleDirecte->refreshWidget();
			} catch (Exception $exc) {
				log::add('ecoleDirecte', 'error', __('Erreur pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $exc->getMessage());
			}
		}

		public function getInformations($jsondata=null)
		{
			if ($this->getIsEnable() == 1)
			{
				$equipement = $this->getName();

				//if(is_null($jsondata))
				//{
				$id = $this->getConfiguration('identifiant');
				$password = $this->getConfiguration('motdepasse');
				$prenom = $this->getConfiguration('prenom');


				$url = "https://api.ecoledirecte.com/v3/elevesDocuments.awp?verbe=get";
				log::add('ecoleDirecte', 'debug', __METHOD__.' '.__LINE__.' requesting '.$url);




				//login;
				$data_string = 'data={	"identifiant": ".$id.","motdepasse": ".password."}';
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$jsondata = curl_exec($ch);
				curl_close($ch);
				//}

				log::add('ecoleDirecte', 'debug', __METHOD__.' '.__LINE__.' $jsondata '.$jsondata);

				$json = json_decode($jsondata,true);

				if (is_null($json))
				{
					log::add('ecoleDirecte', 'info', 'Connexion KO for '.$equipement.' ('.$ip.')');
					$this->checkAndUpdateCmd('communicationStatus',false);

					return false;
				}
				//get token
				$token = $json['token'];
				$this->setConfiguration('token');


				$this->checkAndUpdateCmd('communicationStatus',true);

				self::initInfosMap();
/*

				//update cmdinfo value
				foreach(self::$_infosMap as $cmdLogicalId=>$params)
				{
					if(!isset($params['restkey']))continue;

					if(!is_array($params['restkey'])){
						if(!isset($json[$params['restkey']]))continue;// si string et que pas défini ds le json on continue

						$value = $json[$params['restkey']];
					}else{// c'est un array
						log::add('ecoleDirecte', 'debug', $cmdLogicalId.' is an array '.implode(',',$params['restkey']));
						foreach($params['restkey'] as $restKey){// on cherche parmi toutes les valeurs si une existe
							if(isset($json[$restKey])){// siexiste ds le jsopn
								$value = $json[$restKey];// on valorise la valeur
								continue;//on sort de la boucle
							}
						}
					}

					if(isset($value))// si on a trouvé une valeur au dessus correspondant à un eentrée du json
					{
						//log::add('ecoleDirecte', 'debug',  __METHOD__.' '.__LINE__.' '.$cmdLogicalId.' => '.json_encode($json[$params['restkey']]));
						str_replace("\\n", " ", $value);
						if(isset($params['cbTransform']) && is_callable($params['cbTransform']))
						{
							$value = call_user_func($params['cbTransform'], $value);
							//log::add('ecoleDirecte', 'debug', __METHOD__.' '.__LINE__.' Transform to => '.json_encode($value));
						}
						$this->checkAndUpdateCmd($cmdLogicalId,$value);
					}
				}
				if($this->getLogicalId() == '' or $this->getLogicalId() !=  $json['deviceID'] )
				{
					$this->setLogicalId($json['deviceID']);
					$this->save();
				}
				//update settings value
				$ip = $this->getConfiguration('addressip');
				$password = $this->getConfiguration('password');
				$port = $this->getConfiguration('port', intval('2323'));

				$url = "http://{$ip}:".$port."/?type=json&cmd=listSettings&password=".$password;
				log::add('ecoleDirecte', 'debug', __METHOD__.' '.__LINE__.' requesting '.$url);

				//$jsondata = file_get_contents($url);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 15);
				$jsondata = curl_exec($ch);
				curl_close($ch);
				//}



				$json = json_decode($jsondata,true);
				log::add('ecoleDirecte', 'debug', __METHOD__.' '.__LINE__.' $mqtt new'.$json['mqttEnabled'] . ' old' . $this->getConfiguration('mqttEnabled'));
*/
				return true;
			}
		}

		public function postSave() {
			self::initInfosMap();

			//Cmd Infos
			foreach(self::$_infosMap as $cmdLogicalId=>$params)
			{
				$ecoleDirecteCmd = $this->getCmd('info', $cmdLogicalId);

				if (!is_object($ecoleDirecteCmd))
				{
					log::add('ecoleDirecte', 'debug', __METHOD__.' '.__LINE__.' cmdInfo create '.$cmdLogicalId.'('.__($params['name'], __FILE__).') '.($params['subtype'] ?: 'subtypedefault'));
					$ecoleDirecteCmd = new ecoleDirecteCmd();

					$ecoleDirecteCmd->setLogicalId($cmdLogicalId);
					$ecoleDirecteCmd->setEqLogic_id($this->getId());
					$ecoleDirecteCmd->setName(__($params['name'], __FILE__));
					$ecoleDirecteCmd->setType(isset($params['type']) ?$params['type']: 'info');
					$ecoleDirecteCmd->setSubType(isset($params['subtype']) ?$params['subtype']: 'numeric');
					$ecoleDirecteCmd->setIsVisible(isset($params['isvisible']) ?$params['isvisible']: 0);
					$ecoleDirecteCmd->setDisplay('icon', isset($params['icon']) ?$params['icon']: null);

					$ecoleDirecteCmd->setConfiguration('cmd', isset($params['cmd']) ?$params['cmd']: null);
					$ecoleDirecteCmd->setDisplay('forceReturnLineBefore', isset($params['forceReturnLineBefore']) ?$params['forceReturnLineBefore']: false);

					if(isset($params['unite']))
					$ecoleDirecteCmd->setUnite($params['unite']);
					$ecoleDirecteCmd->setTemplate('dashboard',isset($params['tpldesktop'])?$params['tpldesktop']: 'default');
					$ecoleDirecteCmd->setTemplate('mobile',isset($params['tplmobile'])?$params['tplmobile']: 'default');
					$ecoleDirecteCmd->setOrder($order++);

					$ecoleDirecteCmd->save();
				}elseif($ecoleDirecteCmd->getConfiguration('restKey','') != '') {

					$ecoleDirecteCmd->setConfiguration('restKey', $params['restKey'] ?: null);
					$ecoleDirecteCmd->save();

				}


			}

			//Cmd Actions
			foreach(self::$_actionMap as $cmdLogicalId => $params)
			{
				$ecoleDirecteCmd = $this->getCmd('action', $cmdLogicalId);
				if(is_object($ecoleDirecteCmd) && $ecoleDirecteCmd->getConfiguration('cmd','') != $params['cmd']) {
					$ecoleDirecteCmd->remove();
				}

				if (!is_object($ecoleDirecteCmd))
				{
					log::add('ecoleDirecte', 'debug', __METHOD__.' '.__LINE__.' cmdAction create '.$cmdLogicalId.'('.__($params['name'], __FILE__).') '.($params['subtype'] ?: 'subtypedefault'));
					$ecoleDirecteCmd = new ecoleDirecteCmd();

					$ecoleDirecteCmd->setLogicalId($cmdLogicalId);
					$ecoleDirecteCmd->setEqLogic_id($this->getId());
					$ecoleDirecteCmd->setName(__($params['name'], __FILE__));
					$ecoleDirecteCmd->setType(isset($params['type']) ?$params['type']: 'action');
					$ecoleDirecteCmd->setSubType(isset($params['subtype'] )?$params['subtype']: 'other');
					$ecoleDirecteCmd->setIsVisible(isset($params['isvisible']) ?$params['isvisible']: 1);
					$ecoleDirecteCmd->setConfiguration('cmd', isset($params['cmd']) ?$params['cmd']: null);


					$ecoleDirecteCmd->setConfiguration('listValue', json_encode(isset($params['listValue']) ?$params['listValue']: ''));

					$ecoleDirecteCmd->setDisplay('forceReturnLineBefore', isset($params['forceReturnLineBefore']) ?$params['forceReturnLineBefore']: false);
					$ecoleDirecteCmd->setDisplay('message_disable', isset($params['message_disable']) ?$params['message_disable']: false);
					$ecoleDirecteCmd->setDisplay('title_disable', isset($params['title_disable']) ?$params['title_disable']: false);
					$ecoleDirecteCmd->setDisplay('title_placeholder', isset($params['title_placeholder']) ?$params['title_placeholder']: false);
					$ecoleDirecteCmd->setDisplay('icon', isset($params['icon']) ?$params['icon']: false);
					$ecoleDirecteCmd->setDisplay('message_placeholder', isset($params['message_placeholder']) ?$params['message_placeholder']: false);

					$ecoleDirecteCmd->setDisplay('title_possibility_list', json_encode(isset($params['title_possibility_list']) ?$params['title_possibility_list']: null));//json_encode(array("1","2"));
					$ecoleDirecteCmd->setDisplay('icon', isset($params['icon']) ?$params['icon']: null);

					if(isset($params['tpldesktop']))
					$ecoleDirecteCmd->setTemplate('dashboard',isset($params['tpldesktop']));
					if(isset($params['tplmobile']))
					$ecoleDirecteCmd->setTemplate('mobile',isset($params['tplmobile']));
					$ecoleDirecteCmd->setOrder($order++);

					if(isset($params['linkedInfo']))
					$ecoleDirecteCmd->setValue($this->getCmd('info', $params['linkedInfo']));

					$ecoleDirecteCmd->save();
				} elseif($ecoleDirecteCmd->getConfiguration('cmd','') != '') {
					$ecoleDirecteCmd->setConfiguration('cmd', $params['cmd'] ?: null);
					$ecoleDirecteCmd->setLogicalId($cmdLogicalId);
					$ecoleDirecteCmd->setEqLogic_id($this->getId());
					$ecoleDirecteCmd->setName(__($params['name'], __FILE__));
					$ecoleDirecteCmd->setType(isset($params['type']) ?$params['type']: 'action');
					$ecoleDirecteCmd->setSubType(isset($params['subtype']) ?$params['subtype']: 'other');
					$ecoleDirecteCmd->setConfiguration('cmd', isset($params['cmd']) ?$params['cmd']: null);
					$ecoleDirecteCmd->setConfiguration('listValue', isset($params['listValue'])?json_encode($params['listValue']): '');
					$ecoleDirecteCmd->setDisplay('forceReturnLineBefore', isset($params['forceReturnLineBefore']) ?$params['forceReturnLineBefore']: false);
					$ecoleDirecteCmd->setDisplay('message_disable', isset($params['message_disable'])?$params['message_disable']: false);
					$ecoleDirecteCmd->setDisplay('title_disable', isset($params['title_disable']) ?$params['title_disable']: false);
					$ecoleDirecteCmd->setDisplay('title_placeholder', isset($params['title_placeholder']) ?$params['title_placeholder']: false);
					$ecoleDirecteCmd->setDisplay('icon', isset($params['icon'])?$params['icon']: false);
					$ecoleDirecteCmd->setDisplay('message_placeholder', isset($params['message_placeholder']) ?$params['message_placeholder']: false);
					$ecoleDirecteCmd->setDisplay('title_possibility_list', json_encode(isset($params['title_possibility_list']) ?$params['title_possibility_list']: null));//json_encode(array("1","2"));
					$ecoleDirecteCmd->save();
				}
			}
		}


		public function toHtml($_version = 'dashboard') {
			$replace = $this->preToHtml($_version);
			if (!is_array($replace)) {
				return $replace;
			}
			$version = jeedom::versionAlias($_version);
			$cmd_html = '';
			$br_before = 1;
			foreach ($this->getCmd('info', null, true) as $cmd) {
				if (isset($replace['#refresh_id#']) && $cmd->getId() == $replace['#refresh_id#']) {
					continue;
				}

				if ($br_before == 0 && $cmd->getDisplay('forceReturnLineBefore', 0) == 1) {
					$cmd_html .= '<br/>';
				}
				$cmd_html .= $cmd->toHtml($_version, '', null);
				//log::add('ecoleDirecte', 'debug', ' cmdAction to html '. $cmd->toHtml($_version, '', $replace['#cmd-background-color#']));
				$br_before = 0;
				if ($cmd->getDisplay('forceReturnLineAfter', 0) == 1) {
					$cmd_html .= '<br/>';
					$br_before = 1;
				}

			}
			foreach ($this->getCmd('action', null, true) as $cmd) {
				if($cmd->getSubType() != 'message' ){
					if (isset($replace['#refresh_id#']) && $cmd->getId() == $replace['#refresh_id#'] ) {
						continue;
					}
					if ($br_before == 0 && $cmd->getDisplay('forceReturnLineBefore', 0) == 1) {
						$cmd_html .= '<br/>';
					}
					$cmd_html .= $cmd->toHtml($_version, '', null);

					//log::add('ecoleDirecte', 'debug', ' cmdAction to html '. $cmd->toHtml($_version, '', $replace['#cmd-background-color#']));
					$br_before = 0;
					if ($cmd->getDisplay('forceReturnLineAfter', 0) == 1) {
						$cmd_html .= '<br/>';
						$br_before = 1;
					}
				}
			}

			// uniquement avec titre
			$cmd_html .= '<br/>';
			foreach ($this->getCmd('action', null, true) as $cmd) {
				if($cmd->getSubType() == 'message'  && $cmd->getDisplay('message_disable', 0) == 1){
					if (isset($replace['#refresh_id#']) && $cmd->getId() == $replace['#refresh_id#'] ) {
						continue;
					}
					if ($br_before == 0 && $cmd->getDisplay('forceReturnLineBefore', 0) == 1) {
						$cmd_html .= '<br/>';
					}
					$cmd_html .= $cmd->toHtml($_version, '', null) ;

					//log::add('ecoleDirecte', 'debug', ' cmdAction to html '. $cmd->toHtml($_version, '', $replace['#cmd-background-color#']));
					$br_before = 0;
					if ($cmd->getDisplay('forceReturnLineAfter', 0) == 1) {
						$cmd_html .= '<br/>';
						$br_before = 1;
					}
				}
			}
			// action message complet
			$cmd_html .= '<br/>';
			foreach ($this->getCmd('action', null, true) as $cmd) {
				if($cmd->getSubType() == 'message'  && $cmd->getDisplay('message_disable', 0) != 1){
					if (isset($replace['#refresh_id#']) && $cmd->getId() == $replace['#refresh_id#'] ) {
						continue;
					}
					if ($br_before == 0 && $cmd->getDisplay('forceReturnLineBefore', 0) == 1) {
						$cmd_html .= '<br/>';
					}
					$cmd_html .= $cmd->toHtml($_version, '', null);

					//log::add('ecoleDirecte', 'debug', ' cmdAction to html '. $cmd->toHtml($_version, '', $replace['#cmd-background-color#']));
					$br_before = 0;
					if ($cmd->getDisplay('forceReturnLineAfter', 0) == 1) {
						$cmd_html .= '<br/>';
						$br_before = 1;
					}
				}
			}


			//$eqlogic = $cmd->getEqLogic();
			$ip = $this->getConfiguration('addressip');
			$password = $this->getConfiguration('password');

			$replace['#cmd#'] = $cmd_html;
			$replace['#ipaddress#'] = $ip;
			$replace['#password#'] = $password;

			return template_replace($replace, getTemplate('core', $version, 'ecoleDirecte', 'ecoleDirecte'));
		}

		/*     * **********************Getteur Setteur*************************** */
	}

	class ecoleDirecteCmd extends cmd {
		/*     * *************************Attributs****************************** */

		/*     * ***********************Methode static*************************** */

		/*     * *********************Methode d'instance************************* */

		/*
		* Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
		public function dontRemoveCmd() {
		return true;
	}
	*/
	public function execute($_options = array())
	{
		log::add('ecoleDirecte', 'debug', __METHOD__.'('.json_encode($_options).') Type: '.$this->getType().' logicalId: '.$this->getLogicalId());

		if ($this->getLogicalId() == 'refresh')
		{
			$this->getEqLogic()->refresh();
			return;
		}

		if( $this->getType() == 'action' )
		{

			if( $this->getSubType() == 'slider' && $_options['slider'] == '')
			return;

			ecoleDirecte::initInfosMap();
			$command = $this->getConfiguration('cmd','');
			if (isset(ecoleDirecte::$_actionMap[$this->getLogicalId()]) || $command != '')
			{
				$params = ecoleDirecte::$_actionMap[$this->getLogicalId()];

				if(isset($params['callback']) && is_callable($params['callback']))
				{
					log::add('ecoleDirecte', 'debug', __METHOD__.'calling back');
					call_user_func($params['callback'], $this);
				}elseif(isset($params['cmd']) || $command != '')
				{
					$cmdval = $params['cmd'];
					$cmdval = $this->getConfiguration('cmd');


					$eqLogic = $this->getEqLogic();
					$ip = $eqLogic->getConfiguration('addressip');
					$password = $eqLogic->getConfiguration('password');
					$port = $eqLogic->getConfiguration('port', intval('2323'));
					$url = 'http://'.$ip.':'.$port.'/?cmd='.$cmdval.'&password='.$password;

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS,$cmdval);
					$jsondata = curl_exec($ch);

					curl_close($ch);
					log::add('ecoleDirecte', 'debug', __METHOD__.'('.$url.' with '.$cmdval.') '.$jsondata);

					$eqLogic->getInformations($jsondata);
				}

				return true;
			}
		} else {
			throw new Exception(__('Commande non implémentée actuellement', __FILE__));
		}
		return false;
	}

	/*     * **********************Getteur Setteur*************************** */
}
/*
*/
?>
