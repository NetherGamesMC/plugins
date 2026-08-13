<?php

namespace NetherGames\NGEssentials\player\cosmetics\utils\pet;

enum PetMovementType: string
{
    case BOUNCING = 'bouncing';
    case HOVERING = 'hovering';
    case SWIMMING = 'swimming';
    case WALKING = 'walking';
}