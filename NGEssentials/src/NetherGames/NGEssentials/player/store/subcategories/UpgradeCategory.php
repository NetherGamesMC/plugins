<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\store\subcategories;


use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\FormManager;
use NetherGames\NGEssentials\player\store\categories\Category;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function number_format;

class UpgradeCategory extends SubCategory
{

    public function __construct(
        Category      $category,
        string        $name,
        int           $id,
        array         $elements,
        /** @var int[] */
        private array $prices
    )
    {
        parent::__construct($category, $name, $id, $elements);
    }

    public function sendForm(Player $player, ?callable $onBack = null): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle($this->getName());
            $form->setBackClosure($onBack);

            $goBack = function (Player $player) use ($onBack): void {
                $this->sendForm($player, $onBack);
            };
            $selectedId = $this->getCategory()->getSelected($player, $this->getId());

            foreach ($this->getElements() as $articleId => $values) {
                if (($level = $this->getLevel($player, $articleId)) === 0) {
                    if ($this->getCategory()->getCurrency($player) >= $this->getPrice(1)) {
                        $text = TextFormat::GREEN;
                    } else {
                        $text = TextFormat::RED;
                    }
                } elseif ($selectedId === $articleId) {
                    $text = TextFormat::AQUA . '[' . $level . '] ';
                } else {
                    $text = TextFormat::GRAY . '[' . $level . '] ';
                }

                $form->addButton(new ImageButton($text . $values[self::NAME], ImageButton::IMAGE_TYPE_PATH, $this->getCategory()->getIcon(), function (Player $player) use ($articleId, $goBack) {
                    $this->sendArticleForm($player, $articleId, $goBack);
                }));
            }

            $form->addButton(new ImageButton(($selectedId === -1 ? TextFormat::AQUA : TextFormat::GREEN) . 'None', ImageButton::IMAGE_TYPE_PATH, $this->getCategory()->getIcon(), function (Player $player) use ($goBack) {
                $this->getCategory()->setSelected($player, $this->getId(), -1);

                $goBack($player);
            }));

            $form->sendForm();
        }
    }

    public function getLevel(Player $player, int $articleId): int
    {
        return $this->getClaimed($player)[$articleId] ?? 0;
    }

    /**
     * @param Player $player
     * @return int[]
     */
    public function getClaimed(Player $player): array
    {
        return $this->getCategory()->getValue($player, $this->getId())[Category::CLAIMED] ?? [];
    }

    public function getPrice(int $level): int
    {
        return $this->prices[$level] ?? -1;
    }

    public function sendArticleForm(Player $player, int $articleId, ?callable $onBack): void
    {
        $form = FormManager::createModalForm($player);

        if ($form !== null) {
            $values = $this->getElements()[$articleId];

            $form->setTitle($values[self::NAME]);
            $goBack = function (Player $player) use ($articleId, $onBack): void {
                $this->sendArticleForm($player, $articleId, $onBack);
            };

            if (($level = $this->getLevel($player, $articleId)) === 0) {
                $price = $this->getPrice(1);

                $form->setContent($values[self::DESCRIPTION] . TextFormat::EOL . '$' . number_format($price));

                if ($this->getCategory()->getCurrency($player) >= $price) {
                    $form->setButton1(new Button('Purchase', function (Player $player) use ($price, $articleId, $goBack) {
                        $this->getCategory()->reduceCurrency($player, $price);

                        $this->setClaimed($player, $articleId, 1);

                        $goBack($player);
                    }));
                } else {
                    $form->setButton1(new Button('Not enough coins', $goBack));
                }

                $form->setButton2(new Button('Back', $onBack));
            } elseif ($this->getCategory()->getSelected($player, $this->getId()) === $articleId) {
                if (($price = $this->getPrice($level + 1)) === -1) {
                    $form->setContent($values[self::DESCRIPTION]);

                    $form->setButton1(new Button('Selected', function (Player $player) {
                        $this->getCategory()->setSelected($player, $this->getId(), -1);
                    }));
                } else {
                    $form->setContent($values[self::DESCRIPTION] . TextFormat::EOL . '$' . number_format($price));

                    if ($this->getCategory()->getCurrency($player) >= $price) {
                        $form->setButton1(new Button(TextFormat::RED . 'Purchase Upgrade', function (Player $player) use ($price, $level, $articleId, $goBack) {
                            $this->getCategory()->reduceCurrency($player, $price);

                            $this->setClaimed($player, $articleId, $level + 1);

                            $goBack($player);
                        }));
                    } else {
                        $form->setButton1(new Button('Not enough coins', $goBack));
                    }
                }

                $form->setButton2(new Button('Back', $onBack));
            } else {
                $form->setButton1(new Button('Select', function (Player $player) use ($articleId) {
                    $this->getCategory()->setSelected($player, $this->getId(), $articleId);
                }));

                if (($price = $this->getPrice($level + 1)) === -1) {
                    $form->setButton2(new Button('Back', $onBack));
                } elseif ($this->getCategory()->getCurrency($player) >= $price) {
                    $form->setButton2(new Button(TextFormat::RED . 'Purchase Upgrade', function (Player $player) use ($price, $level, $articleId, $goBack) {
                        $this->getCategory()->reduceCurrency($player, $price);

                        $this->setClaimed($player, $articleId, $level + 1);

                        $goBack($player);
                    }));
                } else {
                    $form->setButton2(new Button('Not enough coins', $goBack));
                }
            }

            $form->sendForm();
        }
    }

    /**
     * @param Player $player
     * @param int $articleId
     * @param int $level
     * @return void
     */
    public function setClaimed(Player $player, int $articleId, int $level): void
    {
        $value = $this->getCategory()->getValue($player, $this->getId());

        $value[Category::CLAIMED][$articleId] = $level;

        krsort($value[Category::CLAIMED]);

        $this->getCategory()->setValue($player, $this->getId(), $value);
    }
}