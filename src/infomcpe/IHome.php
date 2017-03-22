<?php
namespace infomcpe;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\utils\Utils; 
use pocketmine\math\Vector3;


class IHome extends PluginBase implements Listener {
     const Prfix = '§f[§aIHome§f]§e ';
   public function onEnable(){
            @mkdir($this->getDataFolder());
            @mkdir($this->getDataFolder().'home');
            $this->session = $this->getServer()->getPluginManager()->getPlugin("SessionAPI");
            $this->pureperms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
            if ($this->getServer()->getPluginManager()->getPlugin("PluginDownloader")) {
            $this->getServer()->getScheduler()->scheduleAsyncTask(new CheckVersionTask($this, 0));
            if(!file_exists($this->getDataFolder()."groups.json")){
                $this->saveResource("groups.json");
            }
            $this->groups = json_decode(file_get_contents($this->getDataFolder()."groups.json"), true);
            if($this->session == NULL){
               if($this->getServer()->getPluginManager()->getPlugin("PluginDownloader")->getDescription()->getVersion() >= '1.4'){
                   $this->getServer()->getPluginManager()->getPlugin("PluginDownloader")->installByID('SessionAPI');
               }
            }
        }
         $this->getServer()->getPluginManager()->registerEvents($this, $this);
   }
     public function onCommand(CommandSender $player, Command $command, $label, array $args){
         if(!is_null($args[1])){
        $args[1] == strtolower($args[1]);
         }
		switch($command->getName()){
                    case 'ihome':
                        switch (strtolower($args[0])) {
                            case 'sethome':
                                if(!is_null($args[1])){
                                    if(is_null($this->getHomeXYZ($args[1]))){
                                    if( $this->getData($player->getName()) <= $this->groups[$this->pureperms->getUserDataMgr()->getGroup($player)->getName()]){
                                         $this->session->createSession($player->getName(), $this->getName().'_scope', 1);
                                         $this->session->createSession($player->getName(), $this->getName().'_homename', strtolower($args[1]));
                                         $player->sendMessage(IHome::Prfix.'Успешно. Теперь тапните там где хотите разместить точку телепортацыии');
                                    }else{
                                        $player->sendMessage(IHome::Prfix."Вы достигли лимита");
                                    }
                                    }else{
                                        $player->sendMessage(IHome::Prfix.'Имя точки уже занято');
                                    }
                                } else {
                                    $player->sendMessage(IHome::Prfix.'Укажите название');
                                }
                                break;
                            case 'setpublic':
                                if (!is_null($args[1])) {
                                   if(strtolower($player->getName()) == $this->getOwner($args[1])){
                                       $this->setPrivate($args[1], FALSE);
                                       $player->sendMessage(IHome::Prfix."Успешно. Дом {$args[1]} стал публичным");
                                   }else{
                                        $player->sendMessage(IHome::Prfix.'Дом который вы пытаитесь сделать публичным не ваш');
                                   }
                                }else{
                                    $player->sendMessage(IHome::Prfix.'Укажите название');
                                }
                                break;
                                 case 'setprivate':
                                if (!is_null($args[1])) {
                                   if(strtolower($player->getName()) == $this->getOwner($args[1])){
                                       $this->setPrivate($args[1], true);
                                       $player->sendMessage(IHome::Prfix."Успешно. Дом {$args[1]} стал приватным");
                                   } 
                                }else{
                                    $player->sendMessage(IHome::Prfix.'Укажите название');
                                }
                                break;
                            case 'tp':
                                if(!is_null($args[1])){
                                        if(strtolower($player->getName()) == $this->getOwner($args[1])){
                                            $xyz = explode(':', $this->getHomeXYZ($args[1]));
                                            $player->Teleport( new Vector3($xyz[0], $xyz[1] + 1, $xyz[2]));
                                            $player->sendMessage(IHome::Prfix.'Вы успешно были телепортирываны в свой дом');
                                        }else if($this->getPrivate($args[1]) == false){
                                            $xyz = explode(':', $this->getHomeXYZ($args[1]));
                                            $player->Teleport( new Vector3($xyz[0], $xyz[1] + 1, $xyz[2]));
                                            $player->sendMessage(IHome::Prfix."Вы успешно были телепортирываны в дом ".$args[1]." который пренадлижит ".$this->getOwner($args[1]));
                                        } else if($this->getPrivate($args[1]) == true){
                                            $player->sendMessage(IHome::Prfix."Этот дом не публичный, попасть в него не возможно");
                                        }
                                    }
                                break;
                            case 'delhome':
                                if(!$player->hasPermission('ihome.delhome')){
                                    if(!is_null($args[0])){
                                        if(!is_null($this->getOwner($args[1]))){
                                        if(strtolower($player->getName()) == strtolower($this->getOwner($args[1]))){
                                            $this->delHome($args[1]);
                                            $player->sendMessage(IHome::Prfix.'Дом успешно удален');
                                        }else{
                                            $player->sendMessage(IHome::Prfix.'Дом который вы пытаитесь удалить не ваш');
                                        }
                                        }else{
                                            $player->sendMessage('Ошибка. Дом не найден');
                                        }
                                    }else{
                                        $player->sendMessage(IHome::Prfix."Укажите название дома");
                                    }
                                }else{
                                     $this->delHome($args[1]);
                                }
                                break;
                          
                           
                        }
                        break;
                         case 'sethome':
                                $this->getServer()->dispatchCommand($player, 'ih sethome '.$args[0]);
                                break;
                        case 'home':
                                $this->getServer()->dispatchCommand($player, 'ih tp '.$args[0]);
                                break;
                        case 'setprivate':
                                $this->getServer()->dispatchCommand($player, 'ih setprivate '.$args[0]);
                                break;
                        case 'setpublic':
                                $this->getServer()->dispatchCommand($player, 'ih setpublic '.$args[0]);
                                break;
                        case 'delhome':
                                $this->getServer()->dispatchCommand($player, 'ih delhome '.$args[0]);
                                break;
                }
     }
     public function delHome($home) {
        $this->dataSave('data',$this->getOwner($home), $this->dataGet('data', $this->getOwner($home)) - 1);
        unlink($this->getDataFolder().'home/'.$home.'.json');
     }
     public function addData($player, $data) {
         if(is_null($this->dataGet('data', $player))){
             $this->dataSave('data', $player, 1);
         } else {
             return $this->dataSave('data', $player, $this->dataGet('data', $player) + $data);
         }
     }
     public function getData($player) {
         return $this->dataGet('data', $player);
     }
     public function getOwner($home) {
          return strtolower($this->dataGet($home, 'owner'));  
     }
     public function getHomeXYZ($home) {
         return $this->dataGet($home, 'xyz');
     }
     public function setPrivate($home, $type) {
         $this->dataSave($home, 'private', $type);
     }
     public function getPrivate($home) {
         return $this->dataGet($home, 'private');
     }
     public function createHome($home, $player, $xyz) {
         $this->dataSave($home, 'owner', $player);
         $this->dataSave($home, 'xyz', $xyz);
     }
      public function onPlayerTouch(PlayerInteractEvent $event){
         $player = $event->getPlayer();
         $block = $event->getBlock();
         if($this->session->getSessionData($player->getName(), $this->getName().'_scope') == 1){
             if($block->getY() != 0){
             $this->createHome($this->session->getSessionData($player->getName(), $this->getName().'_homename'), $player->getName(), $block->getX().':'.$block->getY().":".$block->getZ());
             $this->setPrivate($this->session->getSessionData($player->getName(), $this->getName().'_homename'), 'true');
             $this->session->createSession($player->getName(), $this->getName().'_scope', null);
             $this->addData($player->getName());
             $player->sendMessage(IHome::Prfix.'Дом успешно установлен.');
             }else{
                 $player->sendMessage(IHome::Prfix.'Попробуйте тапнуть ещё раз');
             }
         }
         }
      public function dataSave($id, $tip, $data){
  $Sfile = (new Config($this->getDataFolder() . "home/".strtolower($id).".json", Config::JSON))->getAll();
  $Sfile[$tip] = $data;
  $Ffile = new Config($this->getDataFolder() . "home/".strtolower($id).".json", Config::JSON);
  $Ffile->setAll($Sfile);
  $Ffile->save();
}
	 public function dataGet($id, $tip){
   $Sfile = (new Config($this->getDataFolder() . "home/".strtolower($id).".json", Config::JSON))->getAll();
   return $Sfile[$tip];
         }
   
}