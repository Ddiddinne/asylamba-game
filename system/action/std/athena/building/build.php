<?php
include_once ATHENA;
include_once PROMETHEE;
# building a building action

# int baseid 		id de la base orbitale
# int building 	 	id du bâtiment

for ($i=0; $i < CTR::$data->get('playerBase')->get('ob')->size(); $i++) { 
	$verif[] = CTR::$data->get('playerBase')->get('ob')->get($i)->get('id');
}

if (CTR::$get->exist('baseid')) {
	$baseId = CTR::$get->get('baseid');
} elseif (CTR::$post->exist('baseid')) {
	$baseId = CTR::$post->get('baseid');
} else {
	$baseId = FALSE;
}
if (CTR::$get->exist('building')) {
	$building = CTR::$get->get('building');
} elseif (CTR::$post->exist('building')) {
	$building = CTR::$post->get('building');
} else {
	$building = FALSE;
}

if ($baseId !== FALSE AND $building !== FALSE AND in_array($baseId, $verif)) {
	if (OrbitalBaseResource::isABuilding($building)) {
		$S_OBM1 = ASM::$obm->getCurrentSession();
		ASM::$obm->newSession(ASM_UMODE);
		ASM::$obm->load(array('rPlace' => $baseId, 'rPlayer' => CTR::$data->get('playerId')));
		$ob  = ASM::$obm->get();

		$S_BQM1 = ASM::$bqm->getCurrentSession();
		ASM::$bqm->newSession(ASM_UMODE);
		ASM::$bqm->load(array('rOrbitalBase' => $baseId), array('dEnd'));

		$currentLevel = call_user_func(array($ob, 'getReal' . ucfirst(OrbitalBaseResource::getBuildingInfo($building, 'name')) . 'Level'));
		$technos = new Technology(CTR::$data->get('playerId'));
		if (OrbitalBaseResource::haveRights($building, $currentLevel + 1, 'resource', $ob->getResourcesStorage()) 
			AND OrbitalBaseResource::haveRights($building, $currentLevel + 1, 'queue', ASM::$bqm->size()) 
			AND (OrbitalBaseResource::haveRights($building, $currentLevel + 1, 'buildingTree', $ob) === TRUE)
			AND OrbitalBaseResource::haveRights($building, $currentLevel + 1, 'techno', $technos)) {

			// construit le nouveau batiment
			$bq = new BuildingQueue();
			$bq->rOrbitalBase = $baseId;
			$bq->buildingNumber = $building;
			$bq->targetLevel = $currentLevel+1;
			$time = OrbitalBaseResource::getBuildingInfo($building, 'level', $currentLevel + 1, 'time');
			$bonus = $time * CTR::$data->get('playerBonus')->get(PlayerBonus::GENERATOR_SPEED) / 100;
			if (ASM::$bqm->size() == 0) {
				$bq->dStart = Utils::now();
			} else {
				$bq->dStart = ASM::$bqm->get(ASM::$bqm->size() - 1)->dEnd;
			}
			$bq->dEnd = Utils::addSecondsToDate($bq->dStart, round($time - $bonus));
			ASM::$bqm->add($bq);

			// debit resources
			$ob->decreaseResources(OrbitalBaseResource::getBuildingInfo($building, 'level', $currentLevel + 1, 'resourcePrice'));

			// ajout de l'event dans le contrôleur
			CTR::$data->get('playerEvent')->add($bq->dEnd, EVENT_BASE, $baseId);

			CTR::$alert->add('Construction programmée', ALERT_STD_SUCCESS);
		} else {
			CTR::$alert->add('les conditions ne sont pas remplies pour construire ce bâtiment', ALERT_STD_ERROR);
		}
		ASM::$bqm->changeSession($S_BQM1);
		ASM::$obm->changeSession($S_OBM1);
	} else {
		CTR::$alert->add('le bâtiment indiqué n\'est pas valide', ALERT_STD_ERROR);
	}
} else {
	CTR::$alert->add('pas assez d\'informations pour construire un bâtiment', ALERT_STD_FILLFORM);
}
?>
