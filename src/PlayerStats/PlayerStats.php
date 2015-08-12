<?php

namespace PlayerStats;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use pocketmine\plugin;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\utils\TextFormat;

/*
████──██─██
█──██──███
████────█
█──██───█
████────█

██─██─████─██─██─████─███─█─█─█───█
─███──█──█──███──█──█──█──█─█─██─██
──█───████───█───█─────█──█─█─█─█─█
─███──█──────█───█──█──█──█─█─█───█
██─██─█──────█───████──█──███─█───█

Developer from Russia.

Supported by KEK!

- Removed Herobrine!
*/

class PlayerStats extends PluginBase implements Listener{

    /** @var Config */
    protected $config;
    /** @var \mysqli */
    protected $db;

    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->saveResource("config.yml", false);
        $this->config = new Config($this->getDataFolder() . "config.yml");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $config = $this->config->get("mysql_settings");
        if(!isset($config["host"]) or !isset($config["user"]) or !isset($config["password"]) or !isset($config["database"])){
            $this->getLogger()->critical("MISSED MYSQL SETTINGS!");
            $this->getLogger()->critical("PLEASE, CHANGE IT IN config.yml");
            $this->getLogger()->critical("PLUGIN: PlayerStats");
            return;
        }
        $this->db = new \mysqli($config["host"], $config["user"], $config["password"], $config["database"], isset($config["port"]) ? $config["port"] : 3306);
        if($this->db->connect_error){
            $this->getLogger()->critical("Couldn't connect to MySQL: ". $this->db->connect_error);
            $this->getLogger()->critical("Disabled PlayerStats plugin !");
            $this->getServer()->getPluginManager()->disablePlugin($this->getServer()->getPluginManager()->getPlugin("PlayerStats"));
            return;
        }
        $this->getLogger()->info("Creating query to database...");
        $resource = $this->getResource("mysql.sql");
        $this->db->query(stream_get_contents($resource));
        @fclose($resource);
        $this->getLogger()->info("Done!");
        $this->getLogger()->info("Successfully connected to MySQL server!");
    }

    public function onDisable(){
        $this->getLogger()->info(TextFormat::RED."- PlayerStats disabled !");
    }

    public function onCommand(CommandSender $sender,Command $command,$label,array $args){
        if($sender instanceof Player){
            if($command == "stats"){
                if(isset($args[0])){
                    if($args[0] instanceof Player){
                        $stats = $this->getAll($this->getServer()->getPlayer($args[0]));
                        $kills = $stats["kills"]; $deaths = $stats["deaths"]; $chats = $stats["chats"]; $breaks = $stats["breaks"];
                        $places = $stats["places"]; $kicks = $stats["kicked"]; $joins = $stats["joins"]; $quits = $stats["quits"];
                        $sender->sendMessage(TextFormat::GREEN."---- ".$args[0]." stats");
                        $sender->sendMessage(TextFormat::GREEN."Kills: ".$kills);
                        $sender->sendMessage(TextFormat::GREEN."Deaths: ".$deaths);
                        $sender->sendMessage(TextFormat::GREEN."Chats: ".$chats);
                        $sender->sendMessage(TextFormat::GREEN."Breaks: ".$breaks);
                        $sender->sendMessage(TextFormat::GREEN."Places: ".$places);
                        $sender->sendMessage(TextFormat::GREEN."Kicks: ".$kicks);
                        $sender->sendMessage(TextFormat::GREEN."Joins: ".$joins);
                        $sender->sendMessage(TextFormat::GREEN."Quits: ".$quits);
                    }else{
                        $sender->sendMessage(TextFormat::RED."- Player offline !");
                    }
                }else{
                    $stats = $this->getAll($sender);
                    $kills = $stats["kills"]; $deaths = $stats["deaths"]; $chats = $stats["chats"]; $breaks = $stats["breaks"];
                    $places = $stats["places"]; $kicks = $stats["kicked"]; $joins = $stats["joins"]; $quits = $stats["quits"];
                    $sender->sendMessage(TextFormat::GREEN."---- ".$sender->getName()." stats");
                    $sender->sendMessage(TextFormat::GREEN."Kills: ".$kills);
                    $sender->sendMessage(TextFormat::GREEN."Deaths: ".$deaths);
                    $sender->sendMessage(TextFormat::GREEN."Chats: ".$chats);
                    $sender->sendMessage(TextFormat::GREEN."Breaks: ".$breaks);
                    $sender->sendMessage(TextFormat::GREEN."Places: ".$places);
                    $sender->sendMessage(TextFormat::GREEN."Kicks: ".$kicks);
                    $sender->sendMessage(TextFormat::GREEN."Joins: ".$joins);
                    $sender->sendMessage(TextFormat::GREEN."Quits: ".$quits);
                }
            }
        }else{
            $sender->sendMessage(TextFormat::RED."- Console didn't support this command !");
        }
    }

    /* ---------------- API PART -------------------*/

    /**
     * @param Player $player
     * @return null|int
     */
    public function getDeaths(Player $player){
        $name = trim(strtolower($player->getName()));
        $result = $this->db->query("SELECT * FROM player_stats WHERE name = '".$this->db->escape_string($name)."'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and strtolower($data["name"]) === $name){
                unset($data["name"]);
                return $data["deaths"];
            }
        }
        return null;
    }

    /**
     * @param Player $player
     * @return null|int
     */
    public function getJoins(Player $player){
        $name = trim(strtolower($player->getName()));
        $result = $this->db->query("SELECT * FROM player_stats WHERE name = '".$this->db->escape_string($name)."'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and strtolower($data["name"]) === $name){
                unset($data["name"]);
                return $data["joins"];
            }
        }
        return null;
    }

    /**
     * @param Player $player
     * @return null|int
     */
    public function getDrops(Player $player){
        $name = trim(strtolower($player->getName()));
        $result = $this->db->query("SELECT * FROM player_stats WHERE name = '".$this->db->escape_string($name)."'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and strtolower($data["name"]) === $name){
                unset($data["name"]);
                return $data["drops"];
            }
        }
        return null;
    }

    /**
     * @param Player $player
     * @return null|int
     */
    public function getKills(Player $player){
        $name = trim(strtolower($player->getName()));
        $result = $this->db->query("SELECT * FROM player_stats WHERE name = '".$this->db->escape_string($name)."'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and strtolower($data["name"]) === $name){
                unset($data["name"]);
                return $data["kills"];
            }
        }
        return null;
    }

    /**
     * @param Player $player
     * @return null|int
     */
    public function getQuits(Player $player){
        $name = trim(strtolower($player->getName()));
        $result = $this->db->query("SELECT * FROM player_stats WHERE name = '".$this->db->escape_string($name)."'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and strtolower($data["name"]) === $name){
                unset($data["name"]);
                return $data["quits"];
            }
        }
        return null;
    }

    /**
     * @param Player $player
     * @return null|int
     */
    public function getBreaks(Player $player){
        $name = trim(strtolower($player->getName()));
        $result = $this->db->query("SELECT * FROM player_stats WHERE name = '".$this->db->escape_string($name)."'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and strtolower($data["name"]) === $name){
                unset($data["name"]);
                return $data["breaks"];
            }
        }
        return null;
    }

    /**
     * @param Player $player
     * @return null|int
     */
    public function getPlaces(Player $player){
        $name = trim(strtolower($player->getName()));
        $result = $this->db->query("SELECT * FROM player_stats WHERE name = '".$this->db->escape_string($name)."'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and strtolower($data["name"]) === $name){
                unset($data["name"]);
                return $data["places"];
            }
        }
        return null;
    }

    /**
     * @param Player $player
     * @return null|int
     */
    public function getChats(Player $player){
        $name = trim(strtolower($player->getName()));
        $result = $this->db->query("SELECT * FROM player_stats WHERE name = '".$this->db->escape_string($name)."'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and strtolower($data["name"]) === $name){
                unset($data["name"]);
                return $data["chats"];
            }
        }
        return null;
    }

    /**
     * @param Player $player
     * @return null|array
     */
    public function getAll(Player $player){
        $name = trim(strtolower($player->getName()));
        $result = $this->db->query("SELECT * FROM player_stats WHERE name = '".$this->db->escape_string($name)."'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and strtolower($data["name"]) === $name){
                return $data;
            }
        }
        return null;
    }

    /* -----------------NON API PART---------------*/

    /**
     * @param Player $player
     */
    public function AddPlayer(Player $player){
	if($this->getPlayer($player->getPlayer()) == null){
        $this->db->query("INSERT INTO player_stats
			(name, breaks, places, deaths, kicked, drops, joins, quits, kills,chats)
			VALUES
			('".$this->db->escape_string(strtolower($player->getPlayer()->getDisplayName()))."', '0','0','0','0','0','0','0','0','0')
		    ");
            }
    }



    /**
     * @param Player $player
     * @return array|null
     */
    public function getPlayer(Player $player){
        $name = trim(strtolower($player->getName()));
        $result = $this->db->query("SELECT * FROM player_stats WHERE name = '".$this->db->escape_string($name)."'");
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

    /* ------------------ EVENTS ------------------*/

    /**
     * @param BlockBreakEvent $e
     */
    public function BlockBreakEvent(BlockBreakEvent $e){
        if(!$e->isCancelled()){
            if($this->getPlayer($e->getPlayer()) == null){
                $this->AddPlayer($e->getPlayer());
            }else{
                $this->db->query("UPDATE player_stats SET breaks = breaks +1 WHERE name = '".strtolower($this->db->escape_string($e->getPlayer()->getDisplayName()))."'");
            }
        }
    }

    /**
     * @param PlayerChatEvent $e
     */
	public function onPlayerChat(PlayerChatEvent $e){
        if(!$e->isCancelled()){
            if($this->getPlayer($e->getPlayer()) == null){
                $this->AddPlayer($e->getPlayer());
            }else{
                $this->db->query("UPDATE player_stats SET chats = chats +1 WHERE name = '".strtolower($this->db->escape_string($e->getPlayer()->getDisplayName()))."'");
            }
        }
    }

    /**
     * @param PlayerDeathEvent $event
     */
    public function DeathEvent(PlayerDeathEvent $event){
        $victim = $event->getEntity();
        if($victim instanceof Player){
            $this->db->query("UPDATE player_stats SET deaths = deaths +1 WHERE name = '".strtolower($this->db->escape_string($event->getEntity()->getPlayer()->getDisplayName()))."'");
            $cause = $event->getEntity()->getLastDamageCause();
                if($cause instanceof EntityDamageByEntityEvent){
                    $killer = $cause->getDamager();
                    $this->db->query("UPDATE player_stats SET kills = kills +1 WHERE name = '".strtolower($this->db->escape_string($killer))."'");
                }
        }
    }

    /**
     * @param PlayerDropItemEvent $e
     */
    public function DropEvent(PlayerDropItemEvent $e){
        if($this->getPlayer($e->getPlayer()) == null){
             $this->AddPlayer($e->getPlayer());
        }else{
            $this->db->query("UPDATE player_stats SET drops = drops +1 WHERE name = '".strtolower($this->db->escape_string($e->getPlayer()->getDisplayName()))."'");
        }
    }

    /**
     * @param BlockPlaceEvent $e
     */
    public function BlockPlaceEvent(BlockPlaceEvent $e){
        if(!$e->isCancelled()){
            if($this->getPlayer($e->getPlayer()) == null){
                 $this->AddPlayer($e->getPlayer());
            }else{
                $this->db->query("UPDATE player_stats SET places = places +1 WHERE name = '".strtolower($this->db->escape_string($e->getPlayer()->getDisplayName()))."'");
            }
        }
    }

    /**
     * @param PlayerKickEvent $e
     */
    public function KickEvent(PlayerKickEvent $e){
        if($this->getPlayer($e->getPlayer()) == null){
             $this->AddPlayer($e->getPlayer());
        }else{
            $this->db->query("UPDATE player_stats SET kicked = kicked +1 WHERE name = '".strtolower($this->db->escape_string($e->getPlayer()->getDisplayName()))."'");
        }
    }

    /**
     * @param PlayerJoinEvent $e
     */
    public function JoinEvent(PlayerJoinEvent $e){
        if ($this->getPlayer($e->getPlayer()) == null) {
            $this->AddPlayer($e->getPlayer());
        } else {
            $this->db->query("UPDATE player_stats SET joins = joins +1 WHERE name = '" . $this->db->escape_string($e->getPlayer()->getDisplayName()) . "'");
        }
    }

    /**
     * @param PlayerQuitEvent $e
     */
    public function onPlayerQuit(PlayerQuitEvent $e){
        if ($this->getPlayer($e->getPlayer()) == null) {
            $this->AddPlayer($e->getPlayer());
        } else {
            $this->db->query("UPDATE player_stats SET quits = quits +1 WHERE name = '" . $this->db->escape_string($e->getPlayer()->getName()) . "'") or die($this->bd->mysqli_error());
        }
        //$this->db->query("UPDATE player_stats SET quits = quits +1 WHERE name = '".$this->db->escape_string($e->getPlayer()->getName())."'") or die($this->bd->mysqli_error());
    }
}
