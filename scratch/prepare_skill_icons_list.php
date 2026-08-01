<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$skills = DB::table('combat_skills')->get();

$iconMap = [
    'Rozproszenie Strzał' => ['file' => 'arrow-spread.png', 'prompt' => 'A square dark fantasy RPG skill icon, fan of multiple sharp glowing arrows spreading out in an arc, bow skill, vibrant gold and emerald aura, dark stone frame'],
    'Stały Refleks' => ['file' => 'steady-reflex.png', 'prompt' => 'A square dark fantasy RPG skill icon, glowing eye and swift arrow trail emblem, passive archery reflex skill, golden light glow, dark background'],
    'Ognisty Grad' => ['file' => 'fire-hail.png', 'prompt' => 'A square dark fantasy RPG skill icon, rain of fiery meteors and fireballs falling from burning sky, magic spell, intense orange and crimson flames, dark background'],
    'Lodowe Okowy' => ['file' => 'icy-shackles.png', 'prompt' => 'A square dark fantasy RPG skill icon, frozen ice chains and jagged frost crystals locking target, frost magic spell, cyan blue glowing ice, dark background'],
    'Rezerwy Astralne' => ['file' => 'astral-reserves.png', 'prompt' => 'A square dark fantasy RPG skill icon, glowing purple cosmic star orb surrounded by mystical runes, passive magic power, deep violet and blue aura, dark background'],
    'Ogłuszający Cios' => ['file' => 'stunning-blow.png', 'prompt' => 'A square dark fantasy RPG skill icon, sword pommel striking with shockwave spark and dizzy stars, stun attack, golden impact flash, dark background'],
    'Aura Miecza' => ['file' => 'sword-aura.png', 'prompt' => 'A square dark fantasy RPG skill icon, glowing knight sword enveloped in fierce golden flame aura, passive sword mastery, vibrant yellow-gold light, dark background'],
    'Roztrzaskujące Uderzenie' => ['file' => 'smashing-blow.png', 'prompt' => 'A square dark fantasy RPG skill icon, heavy battle axe smashing down into shattered ground with red shockwave, stun strike, crimson impact, dark background'],
    'Furia Berserkera' => ['file' => 'berserker-fury.png', 'prompt' => 'A square dark fantasy RPG skill icon, roaring berserker warrior head silhouette in blood red flames, passive battle rage skill, intense red glow, dark background'],
    'Pieśń Odnowy' => ['file' => 'song-of-renewal.png', 'prompt' => 'A square dark fantasy RPG skill icon, ornate golden priest bell emitting gentle green healing waves and musical notes, restoration spell, emerald glow, dark background'],
    'Boskie Uzdrowienie' => ['file' => 'divine-healing.png', 'prompt' => 'A square dark fantasy RPG skill icon, brilliant holy light ray descending with golden angel wings halo, ultimate heal spell, bright warm light, dark background'],
    'Rezonans Wiary' => ['file' => 'faith-resonance.png', 'prompt' => 'A square dark fantasy RPG skill icon, glowing golden holy cross emblem pulsing with divine shockwaves, passive priest faith skill, radiant yellow aura, dark background'],
    'Wir Ostrzy' => ['file' => 'blade-flurry.png', 'prompt' => 'A square dark fantasy RPG skill icon, twin glowing daggers spinning in a lethal whirlwind slash circle, assassin AoE skill, sharp purple and silver trails, dark background'],
    'Cichy Krok' => ['file' => 'silent-step.png', 'prompt' => 'A square dark fantasy RPG skill icon, shadowy assassin silhouette stepping silently through dark smoke with glowing purple dagger, stealth agility skill, violet mist, dark background'],
    'Kojący Dźwięk' => ['file' => 'soothing-tone.png', 'prompt' => 'A square dark fantasy RPG skill icon, silver chime bell with soft sparkling turquoise healing droplets, minor heal spell, gentle cyan light, dark background'],
    'Chór Uzdrowienia' => ['file' => 'healing-chorus.png', 'prompt' => 'A square dark fantasy RPG skill icon, multiple glowing golden holy bells ringing in harmony with aura rings, group heal spell, radiant gold, dark background'],
    'Hymn Świętej Odnowy' => ['file' => 'sacred-renewal-hymn.png', 'prompt' => 'A square dark fantasy RPG skill icon, sacred glowing holy book and priest bell emitting intense golden restoration light, high heal spell, luminous warm gold, dark background'],
    'Requiem Wskrzeszenia' => ['file' => 'resurrection-requiem.png', 'prompt' => 'A square dark fantasy RPG skill icon, majestic holy angel silhouette rising from golden light burst with priest bell, ultimate resurrection heal, radiant golden aura, dark background'],
    'Fala Uderzeniowa' => ['file' => 'shockwave.png', 'prompt' => 'A square dark fantasy RPG skill icon, wizard staff blasting outward ring of blue arc lightning shockwave, magic AoE spell, electric cyan blue, dark background'],
    'Podwójne Cięcie' => ['file' => 'double-slash.png', 'prompt' => 'A square dark fantasy RPG skill icon, two crossed razor-sharp daggers leaving glowing criss-cross slash marks, assassin AoE, vibrant green venom energy, dark background'],
    'Trzęsienie Ziemi' => ['file' => 'earthquake.png', 'prompt' => 'A square dark fantasy RPG skill icon, massive battle axe slammed into earth causing cracking glowing ground fissures, ground slam AoE, fiery orange cracks, dark background'],
    'Świetlisty Krąg' => ['file' => 'luminous-circle.png', 'prompt' => 'A square dark fantasy RPG skill icon, priest bell unleashing expanding ring of blinding holy light beams, holy AoE spell, radiant white gold light, dark background'],
    'Zagłada Ostrzy' => ['file' => 'blade-doomsday.png', 'prompt' => 'A square dark fantasy RPG skill icon, warrior unleashing hurricane of glowing steel sword slashes, ultimate sword AoE, fierce fiery red and orange energy, dark background'],
    'Deszcz Zniszczenia' => ['file' => 'destruction-rain.png', 'prompt' => 'A square dark fantasy RPG skill icon, sky raining hundreds of glowing golden energy arrows onto battlefield, ultimate bow AoE, luminous gold impact, dark background'],
    'Mroźny Podmuch' => ['file' => 'frost-gust.png', 'prompt' => 'A square dark fantasy RPG skill icon, swirling cone of blizzard frost wind and ice shards from magical staff, chill magic, icy turquoise blue, dark background'],
    'Paraliżujący Strzał' => ['file' => 'paralyzing-shot.png', 'prompt' => 'A square dark fantasy RPG skill icon, glowing green glowing arrow hitting nerve target point with electric lightning sparks, paralyze bow skill, bright lime green, dark background'],
    'Okowy Cienia' => ['file' => 'shadow-shackles.png', 'prompt' => 'A square dark fantasy RPG skill icon, dark purple shadow tentacles and chains binding target silhouette, shadow dagger immobilize skill, deep violet purple, dark background'],
    'Wieczny Chłód' => ['file' => 'absolute-zero.png', 'prompt' => 'A square dark fantasy RPG skill icon, giant crystalline ice orb freezing surrounding air into frost spikes, freeze magic, radiant deep blue ice, dark background'],
    'Żrący Jad' => ['file' => 'caustic-venom.png', 'prompt' => 'A square dark fantasy RPG skill icon, dripping green skull poison flask spilling bubbling acid onto blade, toxic poison skill, glowing emerald venom, dark background'],
    'Płonący Impet' => ['file' => 'burning-impetus.png', 'prompt' => 'A square dark fantasy RPG skill icon, flaming broadsword swinging forward with scorching fire trail, fire sword skill, intense fiery orange red flames, dark background'],
    'Postawa Tarczy' => ['file' => 'shield-stance.png', 'prompt' => 'A square dark fantasy RPG skill icon, sturdy steel knight shield glowing with golden protective rune barrier, defensive stance, warm amber gold glow, dark background'],
    'Żelazna Skóra' => ['file' => 'iron-skin.png', 'prompt' => 'A square dark fantasy RPG skill icon, muscular warrior torso turning to polished dark iron steel with metallic sheen, defense buff, metallic silver and grey, dark background'],
    'Zwinny Unik' => ['file' => 'swift-evade.png', 'prompt' => 'A square dark fantasy RPG skill icon, ranger silhouette dodging afterimage with wind swirl trails, evasion skill, bright green wind energy, dark background'],
    'Astralna Osłona' => ['file' => 'astral-shield.png', 'prompt' => 'A square dark fantasy RPG skill icon, spherical purple translucent energy bubble shield surrounding caster, magical defense, glowing violet star aura, dark background'],
    'Boska Bariera' => ['file' => 'divine-barrier.png', 'prompt' => 'A square dark fantasy RPG skill icon, glowing golden holy aegis barrier with divine crest protecting target, priest defense skill, luminous gold light, dark background'],
    'Cień Ukrycia' => ['file' => 'shadow-stealth.png', 'prompt' => 'A square dark fantasy RPG skill icon, rogue vanishing into swirls of dark purple shadow and smoke, stealth defense, dark violet shadow, dark background'],
    'Kamienna Postawa' => ['file' => 'stone-stance.png', 'prompt' => 'A square dark fantasy RPG skill icon, sturdy stone granite armor plating glowing with earthy defense runes, general defense skill, warm brown and orange glow, dark background'],
    'Nieugięta Wola' => ['file' => 'unyielding-will.png', 'prompt' => 'A square dark fantasy RPG skill icon, glowing lion head or unbreakable golden crest radiating powerful defensive shield aura, ultimate defense, brilliant gold, dark background'],
];

$toProcess = [];
foreach ($skills as $s) {
    $iconName = $s->icon;
    $need = false;
    if (empty($iconName)) {
        $need = true;
        if (isset($iconMap[$s->name])) {
            $iconName = $iconMap[$s->name]['file'];
            DB::table('combat_skills')->where('id', $s->id)->update(['icon' => $iconName]);
        }
    } else {
        $path = public_path('assets/skills/icons/' . $iconName);
        if (!file_exists($path)) {
            $need = true;
        }
    }

    if ($need && isset($iconMap[$s->name])) {
        $toProcess[] = [
            'id' => $s->id,
            'name' => $s->name,
            'file' => $iconName,
            'prompt' => $iconMap[$s->name]['prompt']
        ];
    }
}

echo "Skills updated in DB with filenames.\n";
echo "Total to generate: " . count($toProcess) . "\n";
file_put_contents(base_path('scratch/skills_to_generate.json'), json_encode($toProcess, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
