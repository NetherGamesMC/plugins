<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\store\categories;


use libforms\elements\ImageButton;
use libforms\FormManager;
use NetherGames\NGEssentials\player\store\Store;
use NetherGames\NGEssentials\player\store\subcategories\NormalCategory;
use NetherGames\NGEssentials\player\store\subcategories\SubCategory;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function number_format;

class SWStore extends Category
{
    public const ID = 0;

    public const NORMAL_CATEGORY = 0;
    public const INSANE_CATEGORY = 1;

    /** @var SubCategory[] */
    private array $subcategories;

    public function __construct(Store $store)
    {
        parent::__construct($store);

        $this->subcategories = [
            self::NORMAL_CATEGORY => new NormalCategory($this, 'Normal Kits', self::NORMAL_CATEGORY, [
                1 => [
                    SubCategory::NAME => 'Archer',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Bow, 16x Arrow, Chainmail Chestplate & Leggings',
                ],
                2 => [
                    SubCategory::NAME => 'Ecologist',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Iron Axe, 16x Oak Wood',
                ],
                3 => [
                    SubCategory::NAME => 'Enderman',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => '2x Ender Pearl, Stone Sword, Chainmail Chestplate, Leather Pants',
                ],
                4 => [
                    SubCategory::NAME => 'Generic',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => "Iron Sword, 16x Egg, Chainmail Chestplate & Leggings",
                ],
                5 => [
                    SubCategory::NAME => 'Healer',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Splash Potion (Healing), Splash Potion (Regeneration), Chainmail Chestplate, Leather Pants',
                ],
                6 => [
                    SubCategory::NAME => 'Pyro',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Flint And Steel, Lave Bucket, Chainmail Chestplate, 2x Splash Potion (Fire Resistance)',
                ],
                7 => [
                    SubCategory::NAME => 'Sumo',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Stone Sword, Iron Chestplate, Chainmail Leggings',
                ],
                8 => [
                    SubCategory::NAME => 'Swordsman',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Iron Sword, Splash Potion (Resistance)',
                ],
                9 => [
                    SubCategory::NAME => 'Tracker',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Crossbow, 16x Arrow, Compass, Leather Tunic',
                ]
            ]),
            self::INSANE_CATEGORY => new NormalCategory($this, 'Insane Kits', self::INSANE_CATEGORY, [
                0 => [
                    SubCategory::NAME => 'Aquaman',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Trident, Water Bucket, Iron Chestplate, Chainmail Leggings',
                ],
                1 => [
                    SubCategory::NAME => 'Archer',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Bow (Power II), 16x Arrow, Chainmail Chestplate & Leggings',
                ],
                2 => [
                    SubCategory::NAME => 'Ecologist',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Diamond Axe, 16x Oak Wood',
                ],
                3 => [
                    SubCategory::NAME => 'Enderman',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => '2x Ender Pearl, Diamond Sword',
                ],
                4 => [
                    SubCategory::NAME => 'Generic',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => "Iron Sword, 16x Egg, Iron Chestplate & Leggings",
                ],
                5 => [
                    SubCategory::NAME => 'Healer',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => '3x Splash Potion (Healing), Splash Potion (Regeneration), Iron Chestplate, Chainmail Leggings',
                ],
                6 => [
                    SubCategory::NAME => 'Pyro',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Iron Sword, Flint And Steel, Lave Bucket, Iron Chestplate, 2x Splash Potion (Fire Resistance)',
                ],
                7 => [
                    SubCategory::NAME => 'Sumo',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Knockback Stick, Iron Chestplate, Chainmail Leggings',
                ],
                8 => [
                    SubCategory::NAME => 'Swordsman',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Iron Sword (Sharpness I), Splash Potion (Resistance)',
                ],
                9 => [
                    SubCategory::NAME => 'Tracker',
                    SubCategory::PRICE => 100,
                    SubCategory::DESCRIPTION => 'Crossbow, 16x Arrow, Compass, Chainmail Chestplate & Leggings',
                ]
            ]),
        ];
    }

    public function sendForm(Player $player, ?callable $onBack = null): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle($this->getName());
            $form->setContent(TextFormat::GREEN . 'Your balance: ' . TextFormat::WHITE . '$' . number_format($this->getCurrency($player)));
            $form->setBackClosure($onBack);

            $goBack = function (Player $player) use ($onBack): void {
                $this->sendForm($player, $onBack);
            };

            foreach ($this->subcategories as $subcategory) {
                $form->addButton(new ImageButton($subcategory->getName(), ImageButton::IMAGE_TYPE_PATH, $this->getIcon(), function (Player $player) use ($subcategory, $goBack) {
                    $subcategory->sendForm($player, $goBack);
                }));
            }

            $form->sendForm();
        }
    }

    public function getName(): string
    {
        return 'Skywars';
    }

    public function getIcon(): string
    {
        return ServerManager::getIcon(ServerManager::SW);
    }

    public function getSelected(Player $player, int $subCategoryId): int
    {
        return $this->getValue($player, $subCategoryId)[self::SELECTED] ?? -1;
    }

    public function getValue(Player $player, int $subCategoryId = -1): array
    {
        $data = $this->getStore()->getValue($player, self::ID);

        if ($subCategoryId === -1) {
            return $data;
        }

        return $data[$subCategoryId] ?? [];
    }

    public function setSelected(Player $player, int $subCategoryId, int $selected): void
    {
        $value = $this->getValue($player, $subCategoryId);

        if ($selected === -1) {
            unset($value[self::SELECTED]);
        } else {
            $value[self::SELECTED] = $selected;
        }

        $this->setValue($player, $subCategoryId, $value);
    }

    public function setValue(Player $player, int $subCategoryId, array $data): void
    {
        $value = $this->getValue($player);

        $value[$subCategoryId] = $data;

        $this->getStore()->setValue($player, self::ID, $value);
    }
}
