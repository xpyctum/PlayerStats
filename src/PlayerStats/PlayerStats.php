<?php

namespace PlayerStats\PlayerStats;


use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
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

    protected $config;
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
        $resource = $this->getServer()->getResource("mysql.sql");
        $this->db->query(stream_get_contents($resource));

        $this->plugin->getLogger()->info("Successfully connected to MySQL server");
    }
    public function onDisable(){

    }
    public function BlockBreakEvent(BlockBreakEvent $e){
        if(!$e->isCancelled()){
            
        }
    }
    public function DeathEvent(PlayerDeathEvent $e){
        if(!$e->isCancelled()){

        }
    }
    public function BlockPlaceEvent(BlockPlaceEvent $e){
        if(!$e->isCancelled()){

        }
    }
}