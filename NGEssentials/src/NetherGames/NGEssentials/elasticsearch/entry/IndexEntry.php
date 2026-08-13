<?php

namespace NetherGames\NGEssentials\elasticsearch\entry;

class IndexEntry extends Entry
{
    public function __construct(
        private string  $index,
        private ?string $id = null,
        private array   $data = []
    )
    {
    }

    public function asArray(): array
    {
        $data = [
            '_index' => $this->index,
        ];

        if ($this->id !== null) {
            $data['_id'] = $this->id;
        }

        return [
            [
                'index' => $data
            ],
            $this->data
        ];
    }
}