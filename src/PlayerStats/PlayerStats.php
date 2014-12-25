<?php

namespace PlayerStats\PlayerStats;


use pocketmine\entity\Entity;
use pocketmine\IPlayer;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

/*
╔══╗─╔╗╔╗
║╔╗║─║║║║
║╚╝╚╗║╚╝║
║╔═╗║╚═╗║
║╚═╝║─╔╝║
╚═══╝─╚═╝
╔══╗╔══╗╔═══╗╔╗╔╗╔══╗╔════╗╔╗╔╗╔╗──╔╗
╚═╗║║╔═╝║╔═╗║║║║║║╔═╝╚═╗╔═╝║║║║║║──║║
──║╚╝║──║╚═╝║║╚╝║║║────║║──║║║║║╚╗╔╝║
──║╔╗║──║╔══╝╚═╗║║║────║║──║║║║║╔╗╔╗║
╔═╝║║╚═╗║║────╔╝║║╚═╗──║║──║╚╝║║║╚╝║║
╚══╝╚══╝╚╝────╚═╝╚══╝──╚╝──╚══╝╚╝──╚╝
*/

class PlayerStats extends PluginBase implements Listener{

    /** @var PlayerStats */
    protected $config;
    /** @var \mysqli */
    protected $db;

    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $config = $this->config->get("mysql_settings");
        if(!isset($config["host"]) or !isset($config["user"]) or !isset($config["password"]) or !isset($config["database"])){
            $this->getServer()->getLogger()->critical("MISSED MYSQL SETTINGS!");
            $this->getServer()->getLogger()->critical("PLEASE, CHANGE IT IN CONFIG.YML");
            $this->getServer()->getLogger()->critical("PLUGIN: PlayerStats");
            return;
        }
        $this->db = new \mysqli($config["host"], $config["user"], $config["password"], $config["database"], isset($config["port"]) ? $config["port"] : 3306);
        if($this->db->connect_error){
            $this->getServer()->getLogger()->critical("Couldn't connect to MySQL: ". $this->db->connect_error);
            return;
        }
        $resource = $this->getResource("mysql.sql");
        $this->db->query(stream_get_contents($resource));
        $this->getServer()->getLogger()->info("Successfully connected to MySQL server");
    }
    public function onDisable(){
        $this->db->close();
    }
    public function getPlayer(IPlayer $player){
        $name = trim(strtolower($player->getName()));
        $result = $this->db->query("SELECT * FROM player_stats WHERE name = '" . $this->db->escape_string($name)."'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and strtolower($data["name"]) === $name){
                unset($data["name"]);
                return $data;
            }
        }
        return null;
    }
    public function BlockBreakEvent(BlockBreakEvent $e){
        if(!$e->isCancelled()){
            if(!$this->getPlayer($e->getPlayer()->getDisplayName()) !== null){
                $this->db->query("INSERT INTO player_stats
			(name, breaks, places, deaths, kicked, drops, joins, quits)
			VALUES
			('".$this->db->escape_string(strtolower($e->getPlayer()->getDisplayName()))."', '0','0','0','0','0','0','0')
		    ");
            }else{
                $this->db->query("UPDATE player_stats SET breaks = breaks +1 WHERE name = '".strtolower($this->db->escape_string($e->getPlayer()->getDisplayName()))."'");
            }
        }
    }
    public function DeathEvent(PlayerDeathEvent $e){
        if(!$e->isCancelled()){
            if(!$this->getPlayer($e->getEntity()->getPlayer()->getDisplayName()) !== null){
                $this->db->query("INSERT INTO player_stats
			(name, breaks, places, deaths, kicked, drops, joins, quits)
			VALUES
			('".$this->db->escape_string(strtolower($e->getEntity()->getPlayer()->getDisplayName()))."', '0','0','0','0','0','0','0')
		    ");
            }else{
                $this->db->query("UPDATE player_stats SET deaths = deaths +1 WHERE name = '".strtolower($this->db->escape_string($e->getEntity()->getPlayer()->getDisplayName()))."'");
            }
        }
    }
    public function DropEvent(PlayerDropItemEvent $e){
        if(!$this->getPlayer($e->getPlayer()->getDisplayName()) !== null){
            $this->db->query("INSERT INTO player_stats
			(name, breaks, places, deaths, kicked, drops, joins, quits)
			VALUES
			('".$this->db->escape_string(strtolower($e->getPlayer()->getDisplayName()))."', '0','0','0','0','0','0','0')
		    ");
        }else{
            $this->db->query("UPDATE player_stats SET drops = drops +1 WHERE name = '".strtolower($this->db->escape_string($e->getPlayer()->getDisplayName()))."'");
        }
    }
    public function BlockPlaceEvent(BlockPlaceEvent $e){
        if(!$e->isCancelled()){
            if(!$this->getPlayer($e->getPlayer()->getDisplayName()) !== null){
                $this->db->query("INSERT INTO player_stats
			(name, breaks, places, deaths, kicked, drops, joins, quits)
			VALUES
			('".$this->db->escape_string(strtolower($e->getPlayer()->getDisplayName()))."', '0','0','0','0','0','0','0')
		    ");
            }else{
                $this->db->query("UPDATE player_stats SET places = places +1 WHERE name = '".strtolower($this->db->escape_string($e->getPlayer()->getDisplayName()))."'");
            }
        }
    }
    public function KickEvent(PlayerKickEvent $e){
        if(!$this->getPlayer($e->getPlayer()->getDisplayName()) !== null){
            $this->db->query("INSERT INTO player_stats
			(name, breaks, places, deaths, kicked, drops, joins, quits)
			VALUES
			('".$this->db->escape_string(strtolower($e->getPlayer()->getDisplayName()))."', '0','0','0','0','0','0','0')
		    ");
        }else{
            $this->db->query("UPDATE player_stats SET kicked = kicked +1 WHERE name = '".strtolower($this->db->escape_string($e->getPlayer()->getDisplayName()))."'");
        }
    }
    public function JoinEvent(PlayerJoinEvent $e){
        if(!$this->getPlayer($e->getPlayer()->getDisplayName()) !== null){
            $this->db->query("INSERT INTO player_stats
			(name, breaks, places, deaths, kicked, drops, joins, quits)
			VALUES
			('".$this->db->escape_string(strtolower($e->getPlayer()->getDisplayName()))."', '0','0','0','0','0','0','0')
		    ");
        }else{
            $this->db->query("UPDATE player_stats SET joins = joins +1 WHERE name = '".strtolower($this->db->escape_string($e->getPlayer()->getDisplayName()))."'");
        }
    }
    public function QuitEvent(PlayerQuitEvent $e){
        if(!$this->getPlayer($e->getPlayer()->getDisplayName()) !== null){
            $this->db->query("INSERT INTO player_stats
			(name, breaks, places, deaths, kicked, drops, joins, quits)
			VALUES
			('".$this->db->escape_string(strtolower($e->getPlayer()->getDisplayName()))."', '0','0','0','0','0','0','0')
		    ");
        }else{
            $this->db->query("UPDATE player_stats SET quits = quits +1 WHERE name = '".strtolower($this->db->escape_string($e->getPlayer()->getDisplayName()))."'");
        }
    }
}