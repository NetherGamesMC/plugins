<?php

namespace NetherGames\NGEssentials\player\cosmetics\types;

use function array_keys;
use function array_rand;

class CosmeticEntry
{
    /** @var list<CosmeticDataEntry> */
    private array $data;

    public static function fromArray(array $data): self
    {
        return new self(
            $data['type'],
            $data['id'],
            $data['name'],
            CosmeticRarity::from($data['rarity']),
            $data['image_type'],
            $data['image_source'],
            $data['crate_available'],
            $data['data']
        );
    }

    public function __construct(
        public readonly int            $type,
        public readonly int            $id,
        public readonly string         $name,
        public readonly CosmeticRarity $rarity,
        public readonly ?int           $imageType,
        public readonly ?string        $imageSource,
        public readonly bool           $crateAvailable,
        string                         $data
    )
    {
        $dataEntries = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        if (array_keys($dataEntries) !== range(0, count($dataEntries) - 1)) {
            $this->data = [
                new CosmeticDataEntry($this->id, $dataEntries)
            ];
        } else {
            $this->data = array_map(fn(array $data): CosmeticDataEntry => new CosmeticDataEntry($this->id, $data), $dataEntries);
        }
    }

    public function getDataEntry(?int $data = null): CosmeticDataEntry
    {
        return $this->data[$data ?? array_rand($this->data)] ?? $this->data[array_rand($this->data)];
    }

    /**
     * @return CosmeticDataEntry[]
     */
    public function getDataEntries(): array
    {
        return $this->data;
    }
}