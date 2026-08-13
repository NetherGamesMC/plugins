<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\utils\skins;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\utils\SkinUtils;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\entity\InvalidSkinException;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\convert\LegacySkinAdapter;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use Symfony\Component\Filesystem\Path;

class SkinValidatorAdapter extends LegacySkinAdapter
{
    public const INVALID_SKIN = 'Steve_Invalid';

    /** @var SkinValidator */
    private SkinValidator $validator;
    /** @var Skin */
    private Skin $defaultSkin;
    private string $defaultGeometry;
    private string $defaultGeometrySlim;

    public function __construct()
    {
        $this->validator = new SkinValidator();
        $this->defaultSkin = new Skin(
            self::INVALID_SKIN,
            SkinUtils::getTextureFromResources(Path::join('skins', 'default', 'steve.png'))
        );

        $this->defaultGeometry = Utils::getResourceContent(Path::join('skins', 'default', 'geometry_humanoid_custom.json'));
        $this->defaultGeometrySlim = Utils::getResourceContent(Path::join('skins', 'default', 'geometry_humanoid_customSlim.json'));
    }

    public function toSkinData(Skin $skin): SkinData
    {
        $skinData = parent::toSkinData($skin);
        $skinData->setVerified(true);

        return $skinData;
    }

    public function fromSkinData(SkinData $data): Skin
    {
        if ($data->isPersona()) {
            return $this->defaultSkin;
        }

        try {
            $skin = parent::fromSkinData($data);
        } catch (InvalidSkinException $ex) {
            NGEssentials::getInstance()->getLogger()->error($ex->getMessage());

            return $this->defaultSkin;
        }

        $slim = stripos(strtolower($skin->getGeometryName()), 'slim') !== false;
        $playerSkin = new Skin(
            $skin->getSkinId(),
            $skin->getSkinData(),
            geometryName: $slim ? 'geometry.humanoid.customSlim' : 'geometry.humanoid.custom',
            geometryData: $slim ? $this->defaultGeometrySlim : $this->defaultGeometry
        );

        return $this->getValidator()->validateSkin($skin) ? $playerSkin : $this->defaultSkin;
    }

    public function getValidator(): SkinValidator
    {
        return $this->validator;
    }
}