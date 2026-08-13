# libminigames

A lib that literally does everything for your game. In this section, you will be introduced to its API and its basic
use. You might want to take a look at our [Skeleton Plugin](https://github.com/NetherGamesMC/ClassicMinigame) for
further understanding of this library API.

## Introduction

Here we will introduce the basics to you with an explanation about classes and their use.

- `Minigame.php` Your PluginBase abstract class.
- `MinigameListener.php` Your global arena listener.
- `ArenaListener.php` Your arena-specific listener.
- `Command.php` Your command class which is self-explanatory.
- `Arena.php` A basic arena without team support.
- `TeamArena.php` An arena but with team support.
- `Team.php` A class containing all data for your team.
- `tasks/CountDownTask.php` A task for countdown.
- `tasks/MatchTimeTask.php` A task for when the match begins.
- `utils/Forms.php` A form utility class, you may want to extend this if you need custom forms.
- `utils/Items.php` An item utility class, you may want to extend this if you need custom items.
- `utils/StatsData.php` Statistics database, you may need to initialize this first in order to work properly.
- `utils/TypeArena.php` Voting utility class, player can determine the type of arena, i.e: "Hardcore", "Normal", "
  Insane"
- `utils/Utils.php` A utility class.

## Basic use

In order to fully use this library's potential, you have to extend `libminigames/Minigame` in your main class instead
of `PluginBase`. libminigames class will do its first startup after:

- NGE plugin has successfully being integrated.
- All previous arenas/lobbies has been deleted.
- Statistics has been created.

### Creating your first gamemode

This snippet will explain the use of this library:

```php

use libminigames\Arena;
use libminigames\Minigame;
use libminigames\TeamArena;

class ExampleGame extends Minigame {

    // Here, this function will be called after onEnable has successfully been
    // executed, such that above condition is met
    public function registerClasses(): void
    {
        // Do whatever you need in order to fully load your game.
    }

    public function getModes(): array
    {
        // This mode will be used to define its player limit.
        // In a production server, only one gamemode will be chosen.
        return [
            TeamArena::MODE_SOLO => 'Solo',
            TeamArena::MODE_DOUBLES => 'Doubles'
        ];
    }

    public function getMinigameTag(): string
    {
        return "ExampleGame"; // Here is your example game name
    }

    public function generateNewArena(int $modeId, bool $privateGame = false): Arena {
        return new ExampleArena($this, $modeId, $this->mapsPlayed++); // This is your example arena.
    }
}
```

Note for developers: Try your best to not to:

- Override functions that are considered as an internal use willy-nilly.
- Do hacks to bypass the library nature execution.

This is because the library itself should be able to handle it most basic need for an arena to work properly. It is that
simple isn't?

Q: Then how would player join the arena?

A: It will be handled with our NGEssentials, for now you will not be able to read NGE's codebase, but understanding this
library is all that matters.

### Joining an arena

In this case, for further explanation about how player would join this arena is:

    Condition 1:
    NGE CODE -> Minigame::joinArena() -> Minigame::generateNewArena() -> ArenaExample::addPlayer()
    
    Condition 2 [Only works on development mode]:
    Execute "/command join [Mode]" -> Condition 1

Our NGEss library will be able to tell you that a player joins to an arena. It will then call a new Arena class to be
created, thus having you to create everything for this arena again.

Imagine an arena, with a framework, that is basically the "basic" fundamental of a library, to achieve reliability and
reduce work time cost. You will only have to control the arena logic, and you can get the game you want to write.

### Running an arena

Running an arena is fairly easy, you will have to override class `MatchTimeTask` located in `tasks` path Once you have
created your own task, you can apply these methods into your class.

```php
use libminigames\tasks\MatchTimeTask;

class ExampleTimeTask extends MatchTimeTask
{

    public function getPlayingTime(): int
    {
        // The unit of this function are in seconds, this is just a conversion
        // of 10 minutes to seconds.
        return 10 * 60;
    }

    public function gameTick(): void
    {
        // Here, you will do the game tick task, only if the time played is not more 
        // than the playing time set.
    }
    
    public function overTimeTick(): void
    {
        // Function that handle an overtime ticks, meaning that the arena has reached its playing 
        // time, you can perform anything in here and do not forget to set arena status after you have done performing
        // your action, to Arena::STATUS_FINISHING
    }

    public function finishArena(): void
    {
        // You can execute this function for "arena finishing state", meaning that the arena is finished.
        // However, this function is *NOT* meant to perform things continuously, it is not the same as finishTick().

        // Function will only be called when the arena has only 1 player alive in the game, or the game DURING STATE
        // STATUS_RUNNING has finished.
    }

    public function finishTick(): void
    {
        // Do anything after the game reached Arena::STATUS_FINISHING. Here, after 5 seconds passed,
        // it will send the game stats, then finishing arena and clearing tasks.
        if ($this->timePassed === 5) {
            $this->getArena()->sendStats(); // Example to send the stats 5 seconds after the game finished.
        } elseif ($this->timePassed === 10) {
            // Both of these methods are REQUIRED in order to stop the arena gracefully
            $this->getArena()->finish();
            $this->getHandler()->cancel();
        }
    }
}
```

## Summary

libMinigames will be a very useful tool for developers when it comes for developing a minigame. It provides basic
functionality such as queuing, spectating, scoreboards, tasking, world management and concurrent-based arenas gameplay.

If you have any doubts or question, feel free to ask the author of the documentation project :) ! MrPotato101#0060

## Streaks

Tracking win streaks is very easy.
Because all the operations are done on MySQL atomically, you never need to worry about data consistency and race
conditions.

### Winning

To track win streaks, simply implement the function

```php
public function getStreaksKey(): ?string {

}
```

in your arena class. If you want the game to support streaks, simply return anything other than `null`.
For private games, this function should always return null.

### Getting Streaks

You can also get a players streaks by using the `Streaks::GetAll` and `Streaks::GetSingle` methods.
The success callable accepts an `Array<Streak>` and a single `Streak` object, respectively.