<?php

// Игровые очки
const MIN_SCORE = 0;
const MAX_SCORE = 100;

// Типы комнат
const ROOM_TYPE_TREASURE = 1;
const ROOM_TYPE_MONSTER = 2;
const ROOM_TYPE_EMPTY = 3;
const ROOM_TYPE_VISITED = 4;

// Типы монстров
const MONSTER_TYPE_WEAK = 1;
const MONSTER_TYPE_MEDIUM = 2;
const MONSTER_TYPE_STRONG = 3;

// Редкость сокровищ
const TREASURE_RARITY_COMMON = 1;
const TREASURE_RARITY_UNCOMMON = 2;
const TREASURE_RARITY_RARE = 3;

// Класс комнаты
class Room {
    private $type;
    private $treasure;
    private $monster;
    private $visited;

    public function __construct($type, $treasure = null, $monster = null) {
        $this->type = $type;
        $this->treasure = $treasure;
        $this->monster = $monster;
        $this->visited = false;
    }

    public function interact(Player $player) {
        switch ($this->type) {
            case ROOM_TYPE_TREASURE:
                $player->addScore($this->treasure->getReward());
                break;
            case ROOM_TYPE_MONSTER:
                $player->fight($this->monster);
                break;
            case ROOM_TYPE_EMPTY:

                break;
            case ROOM_TYPE_VISITED:

                break;
        }
    }

    public function isVisited() {
        return $this->visited;
    }

    public function setVisited() {
        $this->visited = true;
    }
}

class Treasure {
    private $rarity;
    private $reward;

    public function __construct($rarity) {
        $this->rarity = $rarity;
        $this->reward = $this->getRewardFromRarity();
    }

    private function getRewardFromRarity() {
        switch ($this->rarity) {
            case TREASURE_RARITY_COMMON:
                return rand(1, 10);
            case TREASURE_RARITY_UNCOMMON:
                return rand(11, 20);
            case TREASURE_RARITY_RARE:
                return rand(21, 30);
        }
    }

    public function getReward() {
        return $this->reward;
    }
}

class Monster {
    private $type;
    private $strength;

    public function __construct($type) {
        $this->type = $type;
        $this->strength = $this->getStrengthFromType();
    }

    private function getStrengthFromType() {
        switch ($this->type) {
            case MONSTER_TYPE_WEAK:
                return rand(1, 5);
            case MONSTER_TYPE_MEDIUM:
                return rand(6, 10);
            case MONSTER_TYPE_STRONG:
                return rand(11, 15);
        }
    }

    public function getStrength() {
        return $this->strength;
    }

    public function reduceStrength() {
        $this->strength -= rand(1, 3);
    }
}

class Player {
    private $score;
    private $currentRoom;

    public function __construct() {
        $this->score = 0;
    }

    public function addScore($amount) {
        $this->score += $amount;
        if ($this->score < MIN_SCORE) {
            $this->score = MIN_SCORE;
        }
    }

    public function getScore() {
        return $this->score;
    }

    public function setCurrentRoom(Room $room) {
        $this->currentRoom = $room;
    }

    public function getCurrentRoom() {
        return $this->currentRoom;
    }

    public function fight(Monster $monster) {
        while (true) {
            $roll = rand(1, 20);
            if ($roll > $monster->getStrength()) {
                $this->addScore($monster->getStrength());
                break;
            } else {
                $monster->reduceStrength();
            }
        }
    }
}

class Game {
    private $rooms;
    private $player;

    public function __construct($rooms) {
        $this->rooms = $rooms;
        $this->player = new Player();
    }

    public function startGame() {
        $this->player->setCurrentRoom($this->rooms[0]);
    }

    public function movePlayer($direction) {
        $adjacentRooms = $this->getAdjacentRooms($this->player->getCurrentRoom());
        $newRoom = $adjacentRooms[rand(0, count($adjacentRooms) - 1)];
        $this->player->setCurrentRoom($newRoom);
    }

    private function getAdjacentRooms(Room $room) {
        $adjacentRooms = [];
        foreach ($this->rooms as $r) {
            if ($r!== $room && $this->areRoomsAdjacent($room, $r)) {
                $adjacentRooms[] = $r;
            }
        }
        return $adjacentRooms;
    }

    private function areRoomsAdjacent(Room $room1, Room $room2) {
        $room1Pos = $this->getRoomPosition($room1);
        $room2Pos = $this->getRoomPosition($room2);
        return abs($room1Pos[0] - $room2Pos[0]) + abs($room1Pos[1] - $room2Pos[1]) === 1;
    }

    private function getRoomPosition(Room $room) {
        foreach ($this->rooms as $i => $r) {
            if ($r === $room) {
                return [$i % 5, floor($i / 5)];
            }
        }
    }

    public function endGame() {
        echo "Игра окончена! Ваш окончательный счет ". $this->player->getScore(). ".\n";
        $this->printShortestPath();
    }

    private function printShortestPath() {
        $path = [];
        $currentRoom = $this->player->getCurrentRoom();
        while ($currentRoom!== $this->rooms[0]) {
            $path[] = $currentRoom;
            $currentRoom = $this->getPreviousRoom($currentRoom);
        }
        $path[] = $this->rooms[0];
        echo "Shortest path: ". implode(" -> ", array_reverse($path)). ".\n";
    }

    private function getPreviousRoom(Room $room) {
        foreach ($this->rooms as $r) {
            if ($r!== $room && $r->isVisited()) {
                return $r;
            }
        }
    }
}

// Возможность загрущки данных о подземелье из .json файла
$dungeonData = json_decode(file_get_contents('dungeon.json'), true);

// Создание комнат и монстров
$rooms = [];
foreach ($dungeonData['rooms'] as $roomData) {
    switch ($roomData['type']) {
        case ROOM_TYPE_TREASURE:
            $treasure = new Treasure($roomData['treasureRarity']);
            $rooms[] = new Room(ROOM_TYPE_TREASURE, $treasure);
            break;
        case ROOM_TYPE_MONSTER:
            $monster = new Monster($roomData['monsterType']);
            $rooms[] = new Room(ROOM_TYPE_MONSTER, null, $monster);
            break;
        case ROOM_TYPE_EMPTY:
            $rooms[] = new Room(ROOM_TYPE_EMPTY);
            break;
    }
}

// Создание игры
$game = new Game($rooms);

// Начало игры
$game->startGame();


while (true) {

    $currentRoom = $game->player->getCurrentRoom();


    $currentRoom->interact($game->player);

    if ($currentRoom === $rooms[count($rooms) - 1]) {
        break;
    }


    $game->movePlayer(rand(0, 3));
}

// Конец игры
$game->endGame();

?>
<!-- Пример .json файла -->
<!-- 
{
    "rooms": [
        {
            "type": 1,
            "treasureRarity": 1
        },
        {
            "type": 2,
            "monsterType": 1
        },
        {
            "type": 3
        },
       ...
    ]
} -->