<?php

use Asylamba\Classes\Library\Utils;
use Asylamba\Classes\Library\Game;
use Asylamba\Classes\Container\ArrayList;
use Asylamba\Modules\Ares\Model\Commander;

$session = $this->getContainer()->get('app.session');
$orbitalBaseManager = $this->getContainer()->get('athena.orbital_base_manager');
$placeManager = $this->getContainer()->get('gaia.place_manager');
$commanderManager = $this->getContainer()->get('ares.commander_manager');
$buildingQueueManager = $this->getContainer()->get('athena.building_queue_manager');
$shipQueueManager = $this->getContainer()->get('athena.ship_queue_manager');
$technologyQueueManager = $this->getContainer()->get('promethee.technology_queue_manager');

# création des tableaux de données dans le contrôler
$session->initPlayerInfo();
$session->initPlayerBase();
$session->initPlayerBonus();

# remplissage des données du joueur
$session->add('playerId', $player->getId());

$session->get('playerInfo')->add('color', $player->getRColor());
$session->get('playerInfo')->add('name', $player->getName());
$session->get('playerInfo')->add('avatar', $player->getAvatar());
$session->get('playerInfo')->add('credit', $player->getCredit());
$session->get('playerInfo')->add('experience', $player->getExperience());
$session->get('playerInfo')->add('level', $player->getLevel());
$session->get('playerInfo')->add('stepTutorial', $player->stepTutorial);
$session->get('playerInfo')->add('stepDone', $player->stepDone);
$session->get('playerInfo')->add('status', $player->status);
$session->get('playerInfo')->add('premium', $player->premium);

if (Utils::isAdmin($player->getBind())) {
	$session->get('playerInfo')->add('admin', TRUE);
} else {
	$session->get('playerInfo')->add('admin', FALSE);
}

$playerBases = $orbitalBaseManager->getPlayerBases($player->getId());
foreach ($playerBases as $base) {
	$session->addBase(
		'ob', $base->getId(), 
		$base->getName(), 
		$base->getSector(), 
		$base->getSystem(), 
		'1-' . Game::getSizeOfPlanet($base->getPlanetPopulation()),
		$base->typeOfBase
	);
}
# remplissage des bonus
$playerBonusManager = $this->getContainer()->get('zeus.player_bonus_manager');
$bonus = $playerBonusManager->getBonusByPlayer($player);
$playerBonusManager->initialize($bonus);

# création des paramètres utilisateur
$session->add('playerParams', new ArrayList());

# remplissage des paramètres utilisateur
$session->get('playerParams')->add('base', $session->get('playerBase')->get('ob')->get(0)->get('id'));

# création des tableaux de données dans le contrôleur
$session->initPlayerEvent();

# remplissage des events
$now = Utils::now();