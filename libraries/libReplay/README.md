# libReplay

A high-level replay framework for NetherGames. API `1.0` - Codename: Underdog.

### PHP Requirements:

- PHP 7.3 ZTS (only version we're supporting)
- php-ext-zstd (0.8.0)

### About:

This framework is meant to be used on NetherGames Production Servers. Hence, the main goal is to provide a
performance-optimized framework. The framework relies heavily on more than just its code-base. It's not meant to be a
stand-alone.

This project was originally inspired by Tobias. His aim was to provide an entry based system that would record data
monitored by the server.

### Features:

- Recording:
    - Movement (only Player)
    - Damage
    - Health
    - Inventory (only visual changes)
    - Animations (Block placing/breaking, attacking, eating)
    - Block placing/breaking
    - Chest opening/closing
    - Effects
- Replaying:
    - Movement (only Player)
    - Damage
    - Health
    - Inventory (only visual changes)
    - Animations (Block placing/breaking, attacking, eating)
    - Block placing/breaking
    - Chest opening/closing
    - Effects

This framework aims to record every single moment with as much accuracy as possible. The current features are as of
API `1.0`.

### Why open-source?

Open-source has always been an adventure. We have faith in the community that credit will be given to us where due.
Knowledge is meant to be shared and we hope to have this project serves as a basis for other cool projects done by the
community.

Furthermore, open-source has its benefits. We want the project to be able to benefit from the entire community's
knowledge in making it better. This project is meant to not only be a NetherGames team effort but a community effort
too.

For this very reason, we accept contributions to the project through PRs or overall management. Help in managing the
ideas, providing solutions to issues, and anything you can. **NOTE: Due to maintaining high code standards, the PRs must
adhere to strict guidelines.**

## Documentation

### Basic Definitions

Before you start writing code depending on the framework, it's expected to know the difference between `external`
and `internal`. `internal` classes/interfaces have been flagged specifically in the class documentation with
the `@internal` tag. Any class not having an `@internal` tag is assumed (and defined) to be `external`.

##### What does `internal` & `external` mean?

`internal` means this class/interface isn't supposed to be constructed outside
`libReplay` and that its static/non-static methods are not meant to be called outside `libReplay`.

`external` means you may construct the class/interface outside of `libReplay` and its static/non-static methods may be
used outside `libReplay`.

Due to the lack of package-specific visibility in `PHP 7.3`, we must resort to this to ensure the high standards of the
framework.

#### Starting

To begin with, you must `setup()` the `\libReplay\ReplayServer`. This can be done at startup very simply by
adding `\libReplay\ReplayServer::setup(<PluginBase>)` to your code.

#### Making a server

Not to confuse with setup, "making a server" is a runtime-based thing. The server is just the level and a bunch of
connected clients. Only the connected clients are recorded. This can be done very simply too:

```php
$playerListToRecord = array<Player>;
$replayServer = new \libReplay\ReplayServer($playerListToRecord, <RecordedWorld>);
```

That's all. Simple.

#### Start/Stop/Monitor recording

Like the previous steps, thanks to the high-level API, you can start recording simply by:

```php
/** @var \libReplay\ReplayServer $replayServer */
$replayServer->toggleRecord();
```

and to stop, you just run it again.

You can also check if it's recording like so:

```php
/** @var \libReplay\ReplayServer $replayServer */
$replayServer->isRecording();
```

If it was recording, you can specify optional arguments for
`ReplayServer::toggleRecord()` to save the recording allowing you to later on save to a file or database. These optional
arguments are:

```php
/** @var \libReplay\ReplayServer $replayServer */
$replayServer->toggleRecord(saveRecording: true, extraSaveData: []);
```

**Note: These optional arguments are only used if the recording is being stopped. Furthermore, `recordingName` is only
used if `saveRecording` is set to `true`. Extra save data is so you can save confirmation/other types of data for
verification and handling.**

#### Saving/Loading a replay

The framework is designed for saving replays exceptionally well without data corruption. The format is based of
Zstandard rules and regulations. Anyhow, the high-level API provides a method-based wrapper to ensure we can get a
save-able replay very easily. The `\libReplay\event\ReplayComposedEvent` is called when a replay has finished being
compressed. You can fetch the data from that event and save a replay.

To load a replay after getting replay data back (This is not multi-threaded):

```php
$replayCompressed = new \libReplay\data\ReplayCompressed(memory: '');
$replay = $replayCompressed->decompress();
```

#### Playing a replay

The framework is designed to play a replay in `object` form. Straight up compressed strings/file cannot be played.
However, you can convert compressed replay-based strings to a `libReplay\data\Replay` object. Furthermore, in the event
of one replay(ing) session encountering a corruption, it ensures the other sessions are handled on their own
independently.

To play a replay, you must create a new `libReplay\ReplayViewer`. This viewer, views (plays) the replay. You can play a
replay easily like so:

```php
/** @var \libReplay\data\Replay $replay */
/** @var \pocketmine\world\World $world */
/** @var \libReplay\ReplayViewer $replayViewer */
$replayViewer = new \libReplay\ReplayViewer($replay, $world);
$replayViewer->play();
```

Loading the level and teleporting the players is not the framework's responsibility.

## Disclaimer:

This documentation in no way plans to serve as fully accurate. It's an overview. We recommend you to check the PHPDoc
for accurate documentation.