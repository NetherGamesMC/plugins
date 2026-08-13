<?php
declare(strict_types=1);

namespace lobby\features\npc;

use lobby\features\secret\SecretData;
use NetherGames\NGEssentials\utils\CustomIcon;

class NPCCollection
{
    public const NPC_MISC_LOCATIONS = [
        [
            "type" => "Conditional",
            "title" => CustomIcon::STAR . "Adventurer",
            "skins" => ["OldMan.png"],
            "position" => [
                "x" => -15.5, "y" => 41, "z" => -21.5,
            ],
            "condition" => [
                "name" => "has_all_secrets",
                "arg" => "",
                "results" => [
                    "false" => [
                        "text" => "Welcome to §6§lNetherGames§r! \nWe're a simple folk around these parts, but we've got a real few secrets if you know where to look. Between you and me, they say with §c6 tokens§r, the gods will bless you with §cDaedalus' wings§r. Myths say that the first token was lost to the §3minotaur's labyrinth§r, and the second token rests through the §3volcano's trial§r. The third token was claimed by a family of prestigious §3archers§r, who won't give it up easily. A fourth is the prime object of worship in an unusual §3hand-themed festival§r. But nobody's seen the other tokens in years. \nSome say they were lost, waiting to be rediscovered. But what do I know?",
                    ],
                    "true" => [
                        "text" => "I- you've done it. I didn't think it were possible, but you actually found Daedalus' wings. You truly are an adventurer! Though your quest here in NetherGames now comes to a close, you should always feel welcome to stay a little while - you've made a great deal of friends, and I'm not sure they're ready to see you go just yet. Besides, who knows what might be waiting around the corner, ready to propel you into a new journey?",
                    ],
                ],
            ],
        ],
        [
            "type" => "Basic",
            "title" => CustomIcon::STAR . "Scientist",
            "position" => [
                "x" => -76.5, "y" => 40, "z" => 55.5,
            ],
            "skins" => ["Scientist.png"],
            "content" => "Oh, hello friend! \nI see you've stumbled on my test site. My colleagues are just adventuring in the dig site, it's just inside that §3big skull§r over there.
We're gonna move on further into the volcano shortly, but we're having trouble making progress - you have to be pretty agile to follow the §3inner path§r and claim the §crumoured token§r at the end.
Maybe we'll follow the path around the volcano instead...It's probably easier to get to the §3upper temple§r that way",
        ],
        [
            "type" => "Basic",
            "title" => CustomIcon::STAR . "Camper",
            "skins" => ["Camper.png"],
            "position" => [
                "x" => -185.5, "y" => 55, "z" => -70.5,
            ],
            "content" => "Ah, welcome to our campsite! \nWe're celebrating the §cstatue festival§r! They say it's on the anniversary of some big statue falling over.\n\n I wouldn't know... All that's left is that §3big hand§r. \nI wonder if we'll ever find more of it?",
        ],
        [
            "type" => "Conditional",
            "title" => CustomIcon::STAR . "Miner",
            "skins" => ["miner.png"],
            "position" => [
                "x" => 70.5, "y" => 6, "z" => 264.5,
            ],
            "condition" => [
                "name" => "has_secret",
                "arg" => SecretData::CAVE,
                "results" => [
                    "true" => [
                        "text" => "Hello, fellow traveller, nice to see you again! Did you bring me some food by any chance?",
                        "buttons" => [],
                    ],
                    "false" => [
                        "text" => "Hey there! Looks like you made it down here as well. I found this weird thing on the way, maybe you have some use for it?",
                        "buttons" => [
                            [
                                "text" => "Thanks!",
                                "action" => "token_activate",
                                "arg" => SecretData::CAVE,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            "type" => "Conditional",
            "title" => CustomIcon::STAR . "Camper",
            "skins" => ["Camper.png"],
            "position" => [
                "x" => -146.5, "y" => 57, "z" => -210.5,
            ],
            "condition" => [
                "name" => "has_secret",
                "arg" => SecretData::FESTIVAL,
                "results" => [
                    "true" => [
                        "text" => "Oh this is such a great festival! Thanks for the help!",
                        "buttons" => [],
                    ],
                    "false" => [
                        "condition" => [
                            "name" => "has_checkpoint",
                            "arg" => "camper2_quest",
                            "results" => [
                                "true" => [
                                    "condition" => [
                                        "name" => "has_checkpoint",
                                        "arg" => "shopkeeper",
                                        "results" => [
                                            "true" => [
                                                "condition" => [
                                                    "name" => "has_checkpoint",
                                                    "arg" => "blacksmith",
                                                    "results" => [
                                                        "true" => [
                                                            "condition" => [
                                                                "name" => "has_checkpoint",
                                                                "arg" => "chef",
                                                                "results" => [
                                                                    "true" => [
                                                                        "text" => "WAIT WHAT? You actually got all the generic bulk-ordered cutlery, adora-bowls, AND a dedicated soup chef? You`re amazing! I don`t know how you do it. Here, take this as a reward!",
                                                                        "buttons" => [
                                                                            [
                                                                                "text" => "Thanks!",
                                                                                "action" => "token_activate",
                                                                                "arg" => SecretData::FESTIVAL,
                                                                            ],
                                                                        ],
                                                                    ],
                                                                    "false" => [
                                                                        "text" => "Oh wow, you're quick! Just the chef left.",
                                                                    ],

                                                                ],
                                                            ],
                                                        ],
                                                        "false" => [
                                                            "text" => "You've met the shopkeeper! The blacksmith should be your next stop then, maybe hurry, he'll close up for today soon!",
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            "false" => [
                                                "text" => "Back so soon? Have you visited the Shopkeeper yet? No? Then maybe go to him next!",
                                            ],

                                        ],
                                    ],
                                ],
                                "false" => [
                                    "text" => "Oh great, you`re here. We need food for 20 people and it`s a travesty. We`ve been looking for a new §3soup chef§r all day, we lost all 20 sets of §3generic bulk-ordered cutlery§r, and our §3bowls§r aren`t adora-bowl enough. Do you think you can do something about it? There`s something in it for you if you can do it.",
                                    "buttons" => [
                                        [
                                            "text" => "Happy to help!",
                                            "action" => "reach_checkpoint",
                                            "arg" => "camper2_quest",
                                        ],
                                        [
                                            "text" => "Maybe later",
                                            "action" => "noop",
                                            "arg" => "",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            "content" => "Ah, welcome to our campsite! \nWe're celebrating the §cstatue festival§r! They say it's on the anniversary of some big statue falling over.\n\n I wouldn't know... All that's left is that §3big hand. \nMaybe there's more of it past the §3flooded tunnels?",
        ],
        [
            "type" => "Basic",
            "title" => CustomIcon::STAR . "Pirate",
            "skins" => ["pirate.png"],
            "position" => [
                "x" => -26.5, "y" => 40, "z" => -98.5,
            ],
            "content" => "Ah, yer'd be new here, ain't ya?\nMatey, ain't this valley something real special. But me? I ain't here to stay. I'm just here to resupply §3my ship§r, then I'll be on the seas once more. Aye, I'll be going soon.",
        ],
        [
            "type" => "Basic",
            "title" => CustomIcon::STAR . "Miner",
            "skins" => ["miner.png"],
            "position" => ["x" => 200.5, "y" => 69, "z" => 260.5],
            "content" => "Eh? You've found my mine? Make yourself useful then. \nThe §3coal mines§r won't mine themselves. Avoid the §cDeeper crystal mines§r if you can though, I've had one too many folks gone missing down there. \nOn second thought... Maybe it's safer up here.",
        ],
        [
            "type" => "Conditional",
            "title" => CustomIcon::STAR . "Minotaur",
            "skins" => ["minotaur.png"],
            "position" => ["x" => 45.5, "y" => 49, "z" => -142.5],
            "condition" => [
                "name" => "has_secret",
                "arg" => SecretData::MAZE,
                "results" => [
                    "true" => [
                        "text" => "So, you conquered the maze. Well done. Perhaps I underestimated you.",
                    ],
                    "false" => [
                        "text" => "Challenger. You wish to conquer §cthe maze§r, and claim its' §cprized token§r. An admirable goal... Many have failed in the past. Do not be ashamed when you too give up.",
                    ],
                ],
            ],
        ],
        [
            "type" => "Conditional",
            "title" => CustomIcon::STAR . "Archer",
            "skins" => ["archer.png"],
            "position" => ["x" => 210.5, "y" => 55, "z" => 19.5],

            "condition" => [
                "name" => "plays_archery",
                "arg" => "",
                "results" => [
                    "true" => [
                        "text" => "Hold your horses, you can't play archery twice, finish your current game first.",
                    ],
                    "false" => [
                        "condition" => [
                            "name" => "has_secret",
                            "arg" => SecretData::ARCHERY,
                            "results" => [
                                "true" => [
                                    "text" => "Hey, in for another round?",
                                    "buttons" => [
                                        [
                                            "text" => "Oh definitely!",
                                            "action" => "archery_start",
                                            "arg" => "1",
                                        ],
                                        [
                                            "text" => "Maybe later",
                                            "action" => "noop",
                                            "arg" => "",
                                        ],
                                    ],
                                ],
                                "false" => [
                                    "text" => "You look like someone who's tried their hand at some archery before, ain't that right? \nCare to try your hand at my range? I've only got this §cweird token§r - it's a family heirloom, but it's yours if you beat my score of 10!",
                                    "buttons" => [
                                        [
                                            "text" => "Oh definitely!",
                                            "action" => "archery_start",
                                            "arg" => "1",
                                        ],
                                        [
                                            "text" => "Maybe later",
                                            "action" => "noop",
                                            "arg" => "",
                                        ],
                                    ],
                                ],
                            ],
                        ]
                    ],
                ],

            ],
        ],
        [
            "type" => "Basic",
            "title" => "Villager", // Lower village
            "skins" => [
                "Villager1.png",
                "Villager2.png",
                "Villager3.png",
            ],
            "position" => ["x" => 110.5, "y" => 55, "z" => -150.5],
            "content" => "Oh, hello! You're new around here, aren't you?\n Welcome to the better half of village! There's a lot of stuff around here - There's the §3library§r, the §3minotaur temple§r, and this cool §6§lNetherGames§r statue. Plus some kind of §3Hall of fame§r. I can't ever get bored!",
        ],
        [
            "type" => "Basic",
            "skins" => [
                "Villager1.png",
                "Villager2.png",
                "Villager3.png",
            ],
            "title" => "Villager",
            "position" => ["x" => -43.5, "y" => 55, "z" => -227.5],
            "content" => "Welcome to the higher, and better, half of the village.\n None of that hustle and bustle of the lower half. Here, we prefer the quiet atmosphere of the §3church§r, visiting the §3lonely grave§r, and long walks in the woods. Much more civilized.",
        ],
        [
            "type" => "Basic",
            "skins" => [
                "priest.png",
            ],
            "title" => CustomIcon::STAR . "Priest",
            "position" => ["x" => 30.5, "y" => 64, "z" => 143.5],
            "content" => "Welcome, weary traveller. I see your journey has taken you a great distance, and it shall take you a great deal further. \nPlease, use our §3temple§r and the surrounding village as your own on your travels.\nI'd invite you to explore in the catacombs, but I fear the entrance was sealed a long time ago. May our blessings follow you now and always.",
        ],
        [
            "type" => "Basic",
            "skins" => ["Explorer.png"],
            "title" => "Explorer",
            "position" => ["x" => -13.5, "y" => 20, "z" => -11.5],
            "content" => "Oh, hello! I'm just charting these caves now, but they're very big! It feels like they §cconnect§r everywhere... it's much too hard for me. I think I'll just take a rest for a while...",
        ],
        [
            "type" => "Conditional",
            "title" => CustomIcon::STAR . "Climber",
            "skins" => ["Explorer.png"],
            "position" => ["x" => 86.5, "y" => 55, "z" => -260.5],
            "condition" => [
                "name" => "has_secret",
                "arg" => SecretData::CLIFF,
                "results" => [
                    "true" => [
                        "text" => "Oh, you found her! And you got that thing from her. Make good use of it!",
                    ],
                    "false" => [
                        "condition" => [
                            "name" => "has_checkpoint",
                            "arg" => "climber1",
                            "results" => [
                                "true" => [
                                    "text" => "Did you find her yet? No? I recall her trying to get up those water streams, but I can't help you much more.",
                                ],
                                "false" => [
                                    "text" => "Oh, hey. I'm just waiting for my friend – she said she'd try climb these cliffs with me today, but they seem way too steep to climb. Thing is, nobody's seen her for hours. I wonder where she is...",
                                    "buttons" => [
                                        [
                                            "text" => 'I\'ll find her!',
                                            "action" => 'reach_checkpoint',
                                            "arg" => 'climber1',
                                        ],
                                        [
                                            "text" => 'Interesting.',
                                            "action" => 'noop',
                                            "arg" => '',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

        ],
        [
            "type" => "Conditional",
            "title" => CustomIcon::STAR . "Shopkeeper",
            "skins" => ["Shopkeeper.png"],
            "position" => ["x" => -63.5, "y" => 40, "z" => -113.5],
            "condition" => [
                "name" => "has_checkpoint",
                "arg" => "camper2_quest",
                "results" => [
                    "true" => [
                        "text" => "Oh, it`s you. Yada yada, you look poor, get outta here – oh whatever, just take the bowls. Tell people sal gave em` free!",
                        "buttons" => [
                            [
                                "text" => "Thanks!",
                                "action" => "reach_checkpoint",
                                "arg" => "shopkeeper",
                            ],
                        ],
                    ],
                    "false" => [
                        "text" => "Welcome to my adora-bowl shop! Would you like to buy some bowls- wait, you look kinda poor. Get outta here! Come back with money next time you want my adora-bowl bowls!",
                        "buttons" => [],
                    ],
                ],
            ],
        ],
        [
            "type" => "Conditional",
            "title" => CustomIcon::STAR . "Blacksmith",
            "skins" => ["Blacksmith.png"],
            "position" => ["x" => 4.5, "y" => 55, "z" => -220.5],
            "condition" => [
                "name" => "has_checkpoint",
                "arg" => "camper2_quest",
                "results" => [
                    "true" => [
                        "text" => "Twenty sets of generic, bulk-ordered cutlery? No problemo. It`s for the festival, is it? Alright, it`s on the house this time. Come back for all of your generic, bulk-ordered cutlery needs!",
                        "buttons" => [
                            [
                                "text" => "Thanks!",
                                "action" => "reach_checkpoint",
                                "arg" => "blacksmith",
                            ],
                        ],
                    ],
                    "false" => [
                        "text" => "Welcome to the generic, bulk-ordered cutlery blacksmithery! Come talk to me any time you need some generic, bulk-ordered cutlery. Not that anybody ever gets generic, bulk-ordered cutlery",
                        "buttons" => [],
                    ],
                ],
            ],
        ],
        [
            "type" => "Conditional",
            "title" => CustomIcon::STAR . "Chef",
            "skins" => ["Chef.png"],
            "position" => ["x" => -221.5, "y" => 55, "z" => -64.5],
            "condition" => [
                "name" => "has_checkpoint",
                "arg" => "camper2_quest",
                "results" => [
                    "true" => [
                        "text" => "Wait, what? They.... Need soup? You came to the right person! Don`t fret, I`ll get cooking immediately. This is the best day of my life!!!",
                        "buttons" => [
                            [
                                "text" => "Thanks!",
                                "action" => "reach_checkpoint",
                                "arg" => "chef",
                            ],
                        ],
                    ],
                    "false" => [
                        "text" => "Hello! I`m the soup chef! I really like soup. Making it, drinking it, dipping bread in it, I love it! What? No, you`re a generic character with only one trait. I mean, you don`t even say things!",
                        "buttons" => [],
                    ],
                ],
            ],
        ],
        [
            "type" => "Conditional",
            "title" => "Climber",
            "position" => ["x" => 294.5, "y" => 133, "z" => -151.5],
            "skins" => ["Climber.png"],
            "condition" => [
                "name" => "has_checkpoint",
                "arg" => "climber1",
                "results" => [
                    "true" => [
                        "condition" => [
                            "name" => "has_secret",
                            "arg" => "Cliff",
                            "results" => [
                                "true" => [
                                    "text" => "Oh hello!",
                                    "buttons" => [],
                                ],
                                "false" => [
                                    "text" => "secret not collected yet",
                                    "buttons" => [
                                        [
                                            "text" => "unlock",
                                            "action" => "token_activate",
                                            "arg" => SecretData::CLIFF,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "false" => [
                        "text" => "Hello, traveller! Far away from home are you, aren't you?",
                        "buttons" => [
                        ],
                    ],
                ],
            ],
            "content" => "Hey, I see a fellow adventurous spirit made it up here. Would you have seen my friend by any chance? Yes? Great! You know, there was this weird token thingy when I got here. I don't have any use for it. I want you to have it. You know, a gift from one adventurer to another.",
            "buttons" => [
                [
                    "text" => "Thanks!",
                    "action" => "token_activate",
                    "arg" => "cliff",
                ],
            ],
        ],
    ];

    public const NPC_SPREAD_DATA = [
        [
            "type" => "MultiPhrase",
            "title" => "Shiphand",
            "skins" => [
                "Pirate0.png",
                "Pirate1.png",
                "Pirate2.png",
                "Pirate3.png",
            ],
            "phrases" => [
                "Just a few more days and we set sail! I can't wait!",
                "§oArrr, mateys!§r\nOh, what's up? Just practicing my pirate catchphrase don't mind me!",
                "I hate land... nobody told me I'd need to grow land legs when we came ashore. Ugh.",
                "You know, I do sometimes wonder how we ended up in a valley surrounded by walls on all sides and with no access to any other bodies of water. Maybe a wizard did it.",
                "You know, if you ever feel like becoming a pirate, come let us know! We're meant to leave in a few days, but I get this weird feeling that due to the builders not wanting to remove their beautiful boat, we might never actually leave",
                "You seem nice. Can we be friends? Yes? Great! Do you mind if we rob you of everything you own? You know because we're friends",
            ],
            "positions" => [
                ["x" => -51.5, "y" => 39, "z" => -51.5],
                ["x" => 4.5, "y" => 39, "z" => -54.5],
                ["x" => -25.5, "y" => 39, "z" => -50.5],
                ["x" => -39.5, "y" => 40, "z" => -130.5],
                ["x" => -50.5, "y" => 40, "z" => -99.5],
                ["x" => -52.5, "y" => 40, "z" => -120.5],
            ],
        ],
        [
            "type" => "MultiPhrase",
            "title" => "Miner",
            "skins" => [
                "Miner0.png",
                "Miner1.png",
                "Miner2.png",
            ],
            "phrases" => [
                "OHMYGOD DID THE FOREMAN SEND YOU TO TELL ME TO USE TNT TO UNBLOCK THE MINES?? Wait, no? Oh... that's okay. Tell him if he ever needs tnt, I'm always here... waiting... to blow stuff up...",
                "You seem like you'd like the crystal mines. Too bad they're blocked off when there was that cave-in.",
                "it's so dark in the mines. At least there's tons of coal.",
                "They never let me take breaks.",
                "I wonder if they have dwarves here. I hope there aren't any dwarves... they seem mean.",
                "They never let me take breaks",
                "it's so dark in the mines. At least there's tons of coal",
            ],
            "positions" => [
                ["x" => 232.5, "y" => 76, "z" => 234.5],
                ["x" => 231.5, "y" => 83, "z" => 271.5],
                ["x" => 220.5, "y" => 77, "z" => 280.5],
                ["x" => 222.5, "y" => 77, "z" => 280.5],
                ["x" => 230.5, "y" => 70, "z" => 277.5],
                ["x" => 199.5, "y" => 56, "z" => 235.5],
                ["x" => 223.5, "y" => 55, "z" => 254.5],
                ["x" => 207.5, "y" => 18, "z" => 189.5],
                ["x" => 186.5, "y" => 23, "z" => 208.5],
                ["x" => 150.5, "y" => 27, "z" => 189.5],
                ["x" => 162.5, "y" => 25, "z" => 211.5],
            ],
        ],
        [
            "type" => "MultiPhrase",
            "title" => "Upper Villager",
            "skins" => [
                "Villager1.png",
                "Villager2.png",
                "Villager3.png",
            ],
            "phrases" => [
                "The upper village truly is a pinnacle of civilization in this world",
                "Come, relax at the baths with me later. It's truly more refined than anything else",
                "My house is always perfectly clean and tidy. It's truly amazing how much nicer it is than the type of... rabble you find at the lower village",
                "The lower village... how do they put it.... Sucks",
                "Are you here to join us in our refined residential facilities? We reserve them only for the best minds among us",
            ],
            "positions" => [
                ["x" => -76.5, "y" => 55, "z" => -229.5],
                ["x" => -65.5, "y" => 55, "z" => -239.5],
                ["x" => -65.5, "y" => 55, "z" => -198.5],
                ["x" => -63.5, "y" => 55, "z" => -185.5],
                ["x" => -32.5, "y" => 55, "z" => -199.5],
                ["x" => 20.5, "y" => 55, "z" => -235.5],
                ["x" => 5.5, "y" => 63, "z" => -265.5],
                ["x" => 7.5, "y" => 63, "z" => -264.5],
                ["x" => -12.5, "y" => 70, "z" => -265.5],
                ["x" => -43.5, "y" => 55, "z" => -187.5],
            ],
        ],
        [
            "type" => "MultiPhrase",
            "title" => "Lower Villager",
            "phrases" => [
                "These upper will people.. urgh.. no respect for the simple life!",
                "The lower village is where it happens! Once you go low, you can't ever say no",
                "So pretentious with all their fancy baths and churches. We have a library, nerds. Get educated! Stay in school!",
                "Maybe if the upper village had, you know, actual stuff there, I'd go every once in a while.",
                "I went to the upper village the other week. My clothes still reek of posh fanciness",
            ],
            "skins" => [
                "Lower0.png",
                "Lower1.png",
                "Lower2.png",
            ],
            "positions" => [
                ["x" => 77.5, "y" => 40, "z" => -70.5],
                ["x" => 99.5, "y" => 47, "z" => -97.5],
                ["x" => 128.5, "y" => 55, "z" => -117.5],
                ["x" => 126.5, "y" => 55, "z" => -129.5],
                ["x" => 127.5, "y" => 55, "z" => -128.5],
                ["x" => 135.5, "y" => 55, "z" => -161.5],
                ["x" => 167.5, "y" => 55, "z" => -142.5],
                ["x" => 187.5, "y" => 58, "z" => -117.5],
                ["x" => 75.5, "y" => 47, "z" => -119.5],
            ],
        ],
        [
            "type" => "MultiPhrase",
            "title" => "Templars",
            "phrases" => [
                "I like our temple. Not sure what it's in honour of but it looks spectecular",
                "This is such a peaceful town, unlike those numpties over at the other village. Always going on about being higher and lower. Don't they know that height is just a fabricated difference designed to create a political divide which makes it more difficult for the populous to unite against corrupt and self-serving leaders?",
                "Not that size matters, but we have the biggest temple!",
                "This really is prime real estate. I mean what don't you have?\n There's the waterfront, the mines, the volcano, the temple ...  what isn't to love? \nAnd for a low cost, you too can join our community!",
                "I love living here. So much more peaceful than that other village. Yikes.",
            ],
            "skins" => [
                "Villager1.png",
                "Villager2.png",
                "Villager3.png",
            ],
            "positions" => [
                ["x" => 19.5, "y" => 64, "z" => 170.5],
                ["x" => 18.5, "y" => 64, "z" => 168.5],
                ["x" => 37.5, "y" => 63, "z" => 201.5],
                ["x" => 121.5, "y" => 55, "z" => 152.5],
                ["x" => 136.5, "y" => 55, "z" => 156.5],
                ["x" => 164.5, "y" => 55, "z" => 139.5],
                ["x" => 122.5, "y" => 55, "z" => 188.5],
                ["x" => 122.5, "y" => 55, "z" => 190.5],
                ["x" => 195.5, "y" => 55, "z" => 187.5],
                ["x" => 196.5, "y" => 55, "z" => 188.5],
            ],
        ],
        [
            "type" => "MultiPhrase",
            "title" => "Explorer",
            "phrases" => [
                "there's always something new around the corner!",
                "I love exploring!",
                "I'm lost... and cold... and hungry... and homesick...",
                "Hello, fellow explorer!",
            ],
            "skins" => [
                "Explorer0.png",
                "Explorer1.png",
                "Explorer2.png",
            ],
            "positions" => [
                ["x" => 266.5, "y" => 55, "z" => 19.5],
                ["x" => 262.5, "y" => 62, "z" => -95.5],
                ["x" => 193.5, "y" => 37, "z" => -218.5],
                ["x" => -162.5, "y" => 144, "z" => 103.5],
                ["x" => -285.5, "y" => 78, "z" => 285.5],
                ["x" => 75.5, "y" => 71, "z" => 47.5],
                ["x" => 17.5, "y" => 13, "z" => 102.5],
            ],
        ],
        [
            "type" => "MultiPhrase",
            "title" => "Camper",
            "phrases" => [
                "Praise the hand!",
                "Sometimes I wonder if there's anything more to the hand. I guess we haven't found anything yet though",
                "Camping, with my friends, by the fire... I could get used to this",
                "This festival rocks! I wish it would never end!",
                "Are you here to join our festivities?",
            ],
            "skins" => [
                "Camper0.png",
                "Camper1.png",
                "Camper2.png",
                "Camper3.png",
            ],
            "positions" => [
                ["x" => -147.5, "y" => 58, "z" => -201.5],
                ["x" => -146.5, "y" => 58, "z" => -192.5],
                ["x" => -146.5, "y" => 58, "z" => -194.5],
                ["x" => -140.5, "y" => 57, "z" => -232.5],
                ["x" => -142.5, "y" => 57, "z" => -230.5],
                ["x" => -164.5, "y" => 58, "z" => -226.5],
                ["x" => -217.5, "y" => 55, "z" => -72.5],
                ["x" => -218.5, "y" => 55, "z" => -43.5],
                ["x" => -248.5, "y" => 58, "z" => -61.5],
                ["x" => -263.5, "y" => 59, "z" => -81.5],
                ["x" => -100.5, "y" => 47, "z" => -125.5],
            ],
        ],
    ];
}