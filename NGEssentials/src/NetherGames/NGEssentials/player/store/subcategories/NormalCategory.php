<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\store\subcategories;


use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\FormManager;
use NetherGames\NGEssentials\player\store\categories\Category;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function in_array;
use function number_format;

class NormalCategory extends SubCategory
{
    public function sendForm(Player $player, ?callable $onBack = null): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle($this->getName());
            $form->setContent(TextFormat::GREEN . 'Your balance: ' . TextFormat::WHITE . '$' . number_format($this->getCategory()->getCurrency($player)));
            $form->setBackClosure($onBack);

            $goBack = function (Player $player) use ($onBack): void {
                $this->sendForm($player, $onBack);
            };

            foreach ($this->getElements() as $articleId => $values) {
                if ($this->hasClaimed($player, $articleId)) {
                    if ($this->getCategory()->getSelected($player, $this->getId()) === $articleId) {
                        $text = TextFormat::AQUA;
                    } else {
                        $text = TextFormat::GRAY;
                    }
                } elseif ($this->getCategory()->getCurrency($player) >= $values[self::PRICE]) {
                    $text = TextFormat::GREEN;
                } else {
                    $text = TextFormat::RED;
                }

                $form->addButton(new ImageButton($text . $values[self::NAME], ImageButton::IMAGE_TYPE_PATH, $this->getCategory()->getIcon(), function (Player $player) use ($articleId, $goBack) {
                    $this->sendArticleForm($player, $articleId, $goBack);
                }));
            }

            $form->sendForm();
        }
    }

    public function hasClaimed(Player $player, int $articleId): bool
    {
        return in_array($articleId, $this->getClaimed($player), true);
    }

    /**
     * @param Player $player
     * @return int[]
     */
    public function getClaimed(Player $player): array
    {
        return $this->getCategory()->getValue($player, $this->getId())[Category::CLAIMED] ?? [];
    }

    public function sendArticleForm(Player $player, int $articleId, ?callable $onBack): void
    {
        $form = FormManager::createModalForm($player);

        if ($form !== null) {
            $values = $this->getElements()[$articleId];

            $form->setTitle($values[self::NAME]);
            $form->setContent($values[self::DESCRIPTION] . TextFormat::EOL . '$' . number_format($values[self::PRICE]));
            $goBack = function (Player $player) use ($articleId, $onBack): void {
                $this->sendArticleForm($player, $articleId, $onBack);
            };

            if ($this->hasClaimed($player, $articleId)) {
                if ($this->getCategory()->getSelected($player, $this->getId()) === $articleId) {
                    $form->setButton1(new Button('Selected', function (Player $player) {
                        $this->getCategory()->setSelected($player, $this->getId(), -1);
                    }));
                } else {
                    $form->setButton1(new Button('Select', function (Player $player) use ($articleId) {
                        $this->getCategory()->setSelected($player, $this->getId(), $articleId);
                    }));
                }
            } elseif ($this->getCategory()->getCurrency($player) >= $values[self::PRICE]) {
                $form->setButton1(new Button('Purchase', function (Player $player) use ($values, $articleId, $goBack) {
                    $this->getCategory()->reduceCurrency($player, (int)$values[self::PRICE]);

                    $this->addClaimed($player, $articleId);

                    $goBack($player);
                }));
            } else {
                $form->setButton1(new Button('Not enough coins', $goBack));
            }

            $form->setButton2(new Button('Back', $onBack));

            $form->sendForm();
        }
    }

    /**
     * @param Player $player
     * @param int $articleId
     * @return void
     */
    public function addClaimed(Player $player, int $articleId): void
    {
        $value = $this->getCategory()->getValue($player, $this->getId());

        if (isset($value[Category::CLAIMED])) {
            $value[Category::CLAIMED][] = $articleId;
        } else {
            $value[Category::CLAIMED] = [
                $articleId
            ];
        }

        krsort($value[Category::CLAIMED]);

        $this->getCategory()->setValue($player, $this->getId(), $value);
    }
}