# Style Guide: Ikony Przedmiotów (Broń Handlarza)

## 1. Kontekst i cel

Handlarz (`docs/modules/merchant.md`) sprzedaje 6 typów broni: **miecz, topór, łuk, różdżka, sztylet, dzwon**. W kodzie (`database/seeders/ShopEquipmentSeeder.php`, tablica `$themes`) każdy z 10 tierów poziomowych (1, 10, 20, 30, 40, 50, 60, 70, 80, 90) definiuje nazwy przedmiotów dla wszystkich slotów — ale tylko **Tier 1** ma komplet 6 broni. Tiery 10-90 mają zdefiniowany wyłącznie `sword`. Brakuje wpisów `axe`/`bow`/`wand`/`dagger`/`bell` dla tierów 10-90, więc gracz od poziomu 10 w górę widzi u Handlarza tylko miecze.

Zanim dopiszemy brakujące wpisy w seederze, potrzebujemy grafik ikon w stylu spójnym z ~430 istniejącymi plikami w `public/assets/items/`. Ten dokument to przewodnik stylu do generowania nowych ikon (np. w https://toolkit.artlist.io) + konkretny plan brakujących assetów.

## 2. Analiza istniejącego stylu (`public/assets/items/`)

Na podstawie obejrzenia próbek z różnych tierów (miecze, topory, łuki, różdżki, dzwony):

- **Format**: kwadrat (obserwowane pliki ~230-280px, prawdopodobnie generowane w wyższej rozdzielczości np. 1024x1024 i skalowane w dół). PNG, RGBA, ale tło NIE jest przezroczystym wycinkiem — to pełne, ciemne, malowane tło (nie transparent cutout).
- **Tło**: niemal czarne / bardzo ciemny grafit z subtelną teksturą pękniętego kamienia lub tynku, delikatna winieta (ciemniej w rogach). Za przedmiotem widoczna miękka, rozproszona poświata w kolorze motywu danego itemu (np. błękit dla lodu, pomarańcz dla ognia, fiolet dla mroku) — nie ostre tło, tylko delikatny "glow" za obiektem.
- **Kompozycja**: pojedyncza broń, wycentrowana, ułożona po przekątnej (rękojeść/uchwyt w dolnym-lewym rogu, grot/ostrze w górnym-prawym), wypełnia ok. 70-85% kadru. Delikatny cień pod przedmiotem. Brak tekstu, brak ramek UI, brak dłoni/postaci trzymającej przedmiot.
- **Styl renderu**: malarski, cyfrowy fantasy-art, półrealistyczny (nie płaski wektor, nie fotorealizm) — zbliżony do ikon ekwipunku w stylu Diablo/Path of Exile. Wyraźne rozświetlenie krawędzi (rim light), widoczna faktura pędzla, dramatyczne oświetlenie z jednego kierunku (zwykle góra-lewo lub od źródła poświaty).
- **Progresja tierów** (na podstawie nazw broni w seederze, patrz `ShopEquipmentSeeder.php`):

| Tier (poz.) | Motyw nazwy | Materiał / wykończenie | Poświata / efekt |
|---|---|---|---|
| 1 (Nowicjusza) | surowy, prosty | zwykła stal, drewniana/skórzana rękojeść, ślady zużycia | brak |
| 10 (Rycerski) | szlachetny wojownik | polerowana stal, grawerowane zdobienia | brak / bardzo subtelna |
| 20 (Wzmocniony Stalowy) | wzmocnienie | pogrubiona, "wzmocniona" stal, nity | brak |
| 30 (Szlachecki) | arystokracja | zdobienia, akcenty w kolorze złota/mosiądzu | delikatna, ciepła |
| 40 (Weterana) | bojowe zużycie | metal poobijany, znaki walki, ciemniejsza skóra | brak / bardzo subtelna |
| 50 (Mistrzowski) | rzemiosło mistrzowskie | precyzyjne wykończenie, wysoka jakość stali | subtelna, neutralna |
| 60 (Runiczny) | run, magia | ciemny metal (czerń/grafit) z wygrawerowanymi runami | świecące runy w kolorze fioletu/błękitu |
| 70 (Bojowy) | pole bitwy | ciemny, surowy, "wyszczerbiony" metal i drewno | brak silnej magii — ciepła, przygaszona (brąz/pomarańcz) winieta w tle, bez aury na samym przedmiocie |
| 80 (Obsydianowy) | wulkaniczny, mroczny | czarny obsydian z pękniętymi, żarzącymi się szczelinami | intensywna pomarańczowo-czerwona poświata ognia |
| 90 (Tytanowy) | szczyt potęgi | jasny, srebrzysto-biały metal, kryształowe kolce | intensywna błękitno-cyjanowa lodowa poświata, iskry/kryształki |

Ta tabela to punkt odniesienia — nowe ikony (topór/łuk/różdżka/sztylet/dzwon) dla danego tieru powinny używać tego samego materiału i koloru poświaty co odpowiadający miecz z tego tieru, żeby zestaw wyglądał jak jedna, spójna kolekja.

## 3. Uniwersalny prompt do generowania (grid 4x2 = 8 ikon)

Do wklejenia w https://toolkit.artlist.io (lub inne narzędzie AI). Generujemy w języku angielskim (lepsze wyniki), siatką 4 kolumny x 2 rzędy = 8 oddzielnych ikon na jednym obrazie, które potem trzeba wyciąć na osobne pliki PNG.

```
Dark fantasy RPG item icon sheet, [WEAPON_TYPE_EN] weapons, painted digital
game-art style similar to Diablo / Path of Exile equipment icons.

Layout: clean 4x2 grid, 8 separate icons, generous even padding between
cells, no grid lines, no borders, no labels, no text, no numbers.

Each icon individually:
- square composition, single [WEAPON_TYPE_EN] centered on the diagonal
  (handle/grip in the lower-left, tip/head in the upper-right), filling
  about 75-85% of its cell
- dark near-black background with a faint cracked stone/plaster texture
  and soft vignette (darker corners)
- a soft, diffused glow behind the weapon in the tier's accent color
  (see list below), not a hard-edged aura
- painterly, semi-realistic rendering, strong rim lighting on the edges,
  dramatic single-direction key light
- subtle soft shadow under the weapon
- no hands, no character, no UI frame, no watermark, no text

Generate the 8 icons as this tier progression, left-to-right then
top-to-bottom, each noticeably more elaborate/powerful than the last:

1. [ITEM_1_OPIS]
2. [ITEM_2_OPIS]
3. [ITEM_3_OPIS]
4. [ITEM_4_OPIS]
5. [ITEM_5_OPIS]
6. [ITEM_6_OPIS]
7. [ITEM_7_OPIS]
8. [ITEM_8_OPIS]

Keep consistent lighting, color grading, brush texture and canvas
proportions across all 8 icons so they read as one matched set.
```

Tłumaczenia typów broni do `[WEAPON_TYPE_EN]`: topór = "battle axe", łuk = "hunting/war bow", różdżka = "magic wand", sztylet = "dagger", dzwon = "ritual war bell".

## 4. Pełna lista brakujących ikon (44 pozycji)

Nazwy proponowane analogicznie do istniejących nazw mieczy/pancerza w tym samym tierze (`ShopEquipmentSeeder.php`). Plik ikony = `Str::slug(nazwa)` + `.png`, tak jak działa to dziś dla mieczy. **Topór na Tier 70 („Bojowy Topór") już ma gotową grafikę** (`bojowy-topor.png`) — nie generujemy go ponownie.

### Topór (8 brakujących — dokładnie 1 grid)
| Tier | Nazwa | Plik PNG |
|---|---|---|
| 10 | Rycerski Topór | `rycerski-topor.png` |
| 20 | Wzmocniony Stalowy Topór | `wzmocniony-stalowy-topor.png` |
| 30 | Topór Szlachecki | `topor-szlachecki.png` |
| 40 | Topór Weterana | `topor-weterana.png` |
| 50 | Mistrzowski Topór | `mistrzowski-topor.png` |
| 60 | Runiczny Topór | `runiczny-topor.png` |
| 80 | Obsydianowy Topór | `obsydianowy-topor.png` |
| 90 | Tytanowy Topór | `tytanowy-topor.png` |

### Łuk (9 brakujących)
| Tier | Nazwa | Plik PNG |
|---|---|---|
| 10 | Rycerski Łuk | `rycerski-luk.png` |
| 20 | Wzmocniony Stalowy Łuk | `wzmocniony-stalowy-luk.png` |
| 30 | Łuk Szlachecki | `luk-szlachecki.png` |
| 40 | Łuk Weterana | `luk-weterana.png` |
| 50 | Mistrzowski Łuk | `mistrzowski-luk.png` |
| 60 | Runiczny Łuk | `runiczny-luk.png` |
| 70 | Bojowy Łuk | `bojowy-luk.png` |
| 80 | Obsydianowy Łuk | `obsydianowy-luk.png` |
| 90 | Tytanowy Łuk | `tytanowy-luk.png` |

### Różdżka (9 brakujących)
| Tier | Nazwa | Plik PNG |
|---|---|---|
| 10 | Rycerska Różdżka | `rycerska-rozdzka.png` |
| 20 | Wzmocniona Stalowa Różdżka | `wzmocniona-stalowa-rozdzka.png` |
| 30 | Różdżka Szlachecka | `rozdzka-szlachecka.png` |
| 40 | Różdżka Weterana | `rozdzka-weterana.png` |
| 50 | Mistrzowska Różdżka | `mistrzowska-rozdzka.png` |
| 60 | Runiczna Różdżka | `runiczna-rozdzka.png` |
| 70 | Bojowa Różdżka | `bojowa-rozdzka.png` |
| 80 | Obsydianowa Różdżka | `obsydianowa-rozdzka.png` |
| 90 | Tytanowa Różdżka | `tytanowa-rozdzka.png` |

### Sztylet (9 brakujących)
| Tier | Nazwa | Plik PNG |
|---|---|---|
| 10 | Rycerski Sztylet | `rycerski-sztylet.png` |
| 20 | Wzmocniony Stalowy Sztylet | `wzmocniony-stalowy-sztylet.png` |
| 30 | Sztylet Szlachecki | `sztylet-szlachecki.png` |
| 40 | Sztylet Weterana | `sztylet-weterana.png` |
| 50 | Mistrzowski Sztylet | `mistrzowski-sztylet.png` |
| 60 | Runiczny Sztylet | `runiczny-sztylet.png` |
| 70 | Bojowy Sztylet | `bojowy-sztylet.png` |
| 80 | Obsydianowy Sztylet | `obsydianowy-sztylet.png` |
| 90 | Tytanowy Sztylet | `tytanowy-sztylet.png` |

### Dzwon (9 brakujących)
| Tier | Nazwa | Plik PNG |
|---|---|---|
| 10 | Rycerski Dzwon | `rycerski-dzwon.png` |
| 20 | Wzmocniony Stalowy Dzwon | `wzmocniony-stalowy-dzwon.png` |
| 30 | Dzwon Szlachecki | `dzwon-szlachecki.png` |
| 40 | Dzwon Weterana | `dzwon-weterana.png` |
| 50 | Mistrzowski Dzwon | `mistrzowski-dzwon.png` |
| 60 | Runiczny Dzwon | `runiczny-dzwon.png` |
| 70 | Bojowy Dzwon | `bojowy-dzwon.png` |
| 80 | Obsydianowy Dzwon | `obsydianowy-dzwon.png` |
| 90 | Tytanowy Dzwon | `tytanowy-dzwon.png` |

## 5. Kolejność generowania (paczki po 8 = 1 grid)

1. **Topór** — 8 sztuk, dokładnie 1 grid (patrz sekcja 6 — gotowy przykład).
2. **Łuk** — 9 sztuk, 2 gridy (8 + 1, dziewiąty dogenerować pojedynczo lub połączyć z początkiem kolejnej paczki).
3. **Różdżka** — 9 sztuk, 2 gridy.
4. **Sztylet** — 9 sztuk, 2 gridy.
5. **Dzwon** — 9 sztuk, 2 gridy.

Po wygenerowaniu i wycięciu ikon: zapisać w `public/assets/items/` pod nazwami z tabel powyżej, a następnie dopisać brakujące klucze `axe`/`bow`/`wand`/`dagger`/`bell` do `$theme['names']` w `database/seeders/ShopEquipmentSeeder.php` dla tierów 10-90 (to osobny krok, po stronie kodu — nie robimy go teraz).

## 6. Przykład gotowego promptu — paczka "Topór" (8/8)

```
Dark fantasy RPG item icon sheet, battle axe weapons, painted digital
game-art style similar to Diablo / Path of Exile equipment icons.

Layout: clean 4x2 grid, 8 separate icons, generous even padding between
cells, no grid lines, no borders, no labels, no text, no numbers.

Each icon individually:
- square composition, single battle axe centered on the diagonal
  (handle/grip in the lower-left, axe head in the upper-right), filling
  about 75-85% of its cell
- dark near-black background with a faint cracked stone/plaster texture
  and soft vignette (darker corners)
- a soft, diffused glow behind the weapon in the tier's accent color
  (see list below), not a hard-edged aura
- painterly, semi-realistic rendering, strong rim lighting on the edges,
  dramatic single-direction key light
- subtle soft shadow under the weapon
- no hands, no character, no UI frame, no watermark, no text

Generate the 8 icons as this tier progression, left-to-right then
top-to-bottom, each noticeably more elaborate/powerful than the last:

1. Polished knightly steel axe, ornate engraved head, leather-wrapped
   handle, no glow, warm neutral studio light only.
2. Reinforced heavy steel axe, thicker riveted axe head, no glow.
3. Noble ceremonial axe, gold/brass filigree accents on the blade, warm
   faint golden glow.
4. Battle-worn veteran's axe, chipped and scarred steel, dark weathered
   leather grip, no glow.
5. Masterwork axe, flawless mirror-polished steel, refined silhouette,
   very subtle neutral glow.
6. Runic axe, dark blackened steel head engraved with glowing violet-blue
   runes, faint arcane glow along the runes only.
7. Obsidian axe, black volcanic glass head with glowing cracks, intense
   orange-red fire glow and faint embers.
8. Titan axe, bright silver-white metal with crystalline spikes on the
   head, intense cyan-blue icy glow with tiny frost particles.

Keep consistent lighting, color grading, brush texture and canvas
proportions across all 8 icons so they read as one matched set.
```

Nazwy plików do zapisania po wycięciu siatki (w tej samej kolejności 1-8):

```
rycerski-topor.png
wzmocniony-stalowy-topor.png
topor-szlachecki.png
topor-weterana.png
mistrzowski-topor.png
runiczny-topor.png
obsydianowy-topor.png
tytanowy-topor.png
```

Poniższe paczki (Łuk, Różdżka, Sztylet, Dzwon) mają po 9 pozycji, więc każda
dzieli się na **Grid A** (8 ikon, tiery 10-80, pełny 4x2) oraz osobny
**pojedynczy prompt** na Tier 90 (Tytanowy), żeby nie marnować całego gridu
na jedną ikonę.

## 7. Łuk (Bow) — 9/9

### Grid A (tiery 10-80)

```
Dark fantasy RPG item icon sheet, war bow weapons, painted digital
game-art style similar to Diablo / Path of Exile equipment icons.

Layout: clean 4x2 grid, 8 separate icons, generous even padding between
cells, no grid lines, no borders, no labels, no text, no numbers.

Each icon individually:
- square composition, single recurve war bow centered on the diagonal
  (lower limb/grip in the lower-left, upper limb tip in the upper-right),
  filling about 75-85% of its cell
- dark near-black background with a faint cracked stone/plaster texture
  and soft vignette (darker corners)
- a soft, diffused glow behind the bow in the tier's accent color (see
  list below), not a hard-edged aura
- painterly, semi-realistic rendering, strong rim lighting on the edges,
  dramatic single-direction key light
- subtle soft shadow under the bow
- no hands, no character, no arrow, no UI frame, no watermark, no text

Generate the 8 icons as this tier progression, left-to-right then
top-to-bottom, each noticeably more elaborate/powerful than the last:

1. Polished knightly recurve bow, carved wood limbs with steel tips,
   leather-wrapped grip, taut hemp string, no glow, warm neutral studio
   light only.
2. Reinforced steel recurve bow, thicker riveted limb joints, sturdier
   construction, no glow.
3. Noble ceremonial bow, gold/brass filigree inlaid along the limbs,
   polished dark wood, warm faint golden glow.
4. Battle-worn veteran's bow, scarred and weathered wood limbs, frayed
   grip wrapping, no glow.
5. Masterwork bow, flawless mirror-polished dark wood and steel limbs,
   refined elegant silhouette, very subtle neutral glow.
6. Runic bow, blackened steel limbs engraved with glowing violet-blue
   runes along their length, faint arcane glow along the runes only.
7. Battlefield war bow, dark rugged wood and iron limbs, chipped and worn
   from combat, no strong magic - warm dim brownish-orange background
   vignette only.
8. Obsidian bow, black volcanic glass limbs with glowing cracks running
   through them, intense orange-red fire glow and faint embers.

Keep consistent lighting, color grading, brush texture and canvas
proportions across all 8 icons so they read as one matched set.
```

Nazwy plików (kolejność 1-8):

```
rycerski-luk.png
wzmocniony-stalowy-luk.png
luk-szlachecki.png
luk-weterana.png
mistrzowski-luk.png
runiczny-luk.png
bojowy-luk.png
obsydianowy-luk.png
```

### Pojedyncza ikona — Tier 90 (Tytanowy)

```
Dark fantasy RPG item icon, painted digital game-art style similar to
Diablo / Path of Exile equipment icons.

Titan war bow, bright silver-white metal limbs with crystalline spikes
at the tips, intense cyan-blue icy glow with tiny frost particles
drifting off the string. Square composition, bow centered on the
diagonal (grip lower-left, upper limb tip upper-right), filling about
75-85% of the frame. Dark near-black background with a faint cracked
stone/plaster texture and soft vignette. Painterly, semi-realistic
rendering, strong rim lighting, dramatic single-direction key light,
subtle soft shadow underneath. No hands, no character, no arrow, no UI
frame, no watermark, no text.
```

Nazwa pliku: `tytanowy-luk.png`

## 8. Różdżka (Wand) — 9/9

### Grid A (tiery 10-80)

```
Dark fantasy RPG item icon sheet, magic wand weapons, painted digital
game-art style similar to Diablo / Path of Exile equipment icons.

Layout: clean 4x2 grid, 8 separate icons, generous even padding between
cells, no grid lines, no borders, no labels, no text, no numbers.

Each icon individually:
- square composition, single wand centered on the diagonal (grip in the
  lower-left, tip in the upper-right), filling about 75-85% of its cell
- dark near-black background with a faint cracked stone/plaster texture
  and soft vignette (darker corners)
- a soft, diffused glow behind the wand in the tier's accent color (see
  list below), not a hard-edged aura
- painterly, semi-realistic rendering, strong rim lighting on the edges,
  dramatic single-direction key light
- subtle soft shadow under the wand
- no hands, no character, no UI frame, no watermark, no text

Generate the 8 icons as this tier progression, left-to-right then
top-to-bottom, each noticeably more elaborate/powerful than the last:

1. Polished knightly wand, carved dark wood shaft with a plain steel
   cap, no glow, warm neutral studio light only.
2. Reinforced steel-banded wand, thicker metal-wrapped shaft, no glow.
3. Noble ceremonial wand, gold/brass filigree wrapped around dark
   polished wood, small amber gem set at the tip, warm faint golden
   glow.
4. Battle-worn veteran's wand, scarred wood shaft with a dented iron
   cap, no glow.
5. Masterwork wand, flawless mirror-polished ebony shaft with a refined
   silver tip, very subtle neutral glow.
6. Runic wand, blackened metal shaft engraved with glowing violet-blue
   runes spiraling upward, faint arcane glow along the runes only.
7. Battlefield wand, dark rugged iron-bound shaft, chipped and worn from
   combat, no strong magic - warm dim brownish-orange background
   vignette only.
8. Obsidian wand, black volcanic glass shaft with glowing cracks and a
   molten ember-like tip, intense orange-red fire glow and faint embers.

Keep consistent lighting, color grading, brush texture and canvas
proportions across all 8 icons so they read as one matched set.
```

Nazwy plików (kolejność 1-8):

```
rycerska-rozdzka.png
wzmocniona-stalowa-rozdzka.png
rozdzka-szlachecka.png
rozdzka-weterana.png
mistrzowska-rozdzka.png
runiczna-rozdzka.png
bojowa-rozdzka.png
obsydianowa-rozdzka.png
```

### Pojedyncza ikona — Tier 90 (Tytanowa)

```
Dark fantasy RPG item icon, painted digital game-art style similar to
Diablo / Path of Exile equipment icons.

Titan wand, bright silver-white metal shaft with a crystalline spiked
tip, intense cyan-blue icy glow with tiny frost particles. Square
composition, wand centered on the diagonal (grip lower-left, tip
upper-right), filling about 75-85% of the frame. Dark near-black
background with a faint cracked stone/plaster texture and soft vignette.
Painterly, semi-realistic rendering, strong rim lighting, dramatic
single-direction key light, subtle soft shadow underneath. No hands, no
character, no UI frame, no watermark, no text.
```

Nazwa pliku: `tytanowa-rozdzka.png`

## 9. Sztylet (Dagger) — 9/9

### Grid A (tiery 10-80)

```
Dark fantasy RPG item icon sheet, dagger weapons, painted digital
game-art style similar to Diablo / Path of Exile equipment icons.

Layout: clean 4x2 grid, 8 separate icons, generous even padding between
cells, no grid lines, no borders, no labels, no text, no numbers.

Each icon individually:
- square composition, single dagger centered on the diagonal (grip in
  the lower-left, blade tip in the upper-right), filling about 75-85%
  of its cell
- dark near-black background with a faint cracked stone/plaster texture
  and soft vignette (darker corners)
- a soft, diffused glow behind the dagger in the tier's accent color
  (see list below), not a hard-edged aura
- painterly, semi-realistic rendering, strong rim lighting on the edges,
  dramatic single-direction key light
- subtle soft shadow under the dagger
- no hands, no character, no UI frame, no watermark, no text

Generate the 8 icons as this tier progression, left-to-right then
top-to-bottom, each noticeably more elaborate/powerful than the last:

1. Polished knightly dagger, short double-edged steel blade, engraved
   crossguard, leather-wrapped grip, no glow, warm neutral studio light
   only.
2. Reinforced heavy steel dagger, thicker riveted blade base, no glow.
3. Noble ceremonial dagger, gold/brass filigree along the blade spine
   and guard, warm faint golden glow.
4. Battle-worn veteran's dagger, nicked and scarred steel blade, dark
   weathered grip, no glow.
5. Masterwork dagger, flawless mirror-polished slender blade, refined
   silhouette, very subtle neutral glow.
6. Runic dagger, dark blackened steel blade engraved with glowing
   violet-blue runes, faint arcane glow along the runes only.
7. Battlefield dagger, dark rugged serrated blade, chipped and worn from
   combat, no strong magic - warm dim brownish-orange background
   vignette only.
8. Obsidian dagger, black volcanic glass blade with glowing cracks,
   intense orange-red fire glow and faint embers.

Keep consistent lighting, color grading, brush texture and canvas
proportions across all 8 icons so they read as one matched set.
```

Nazwy plików (kolejność 1-8):

```
rycerski-sztylet.png
wzmocniony-stalowy-sztylet.png
sztylet-szlachecki.png
sztylet-weterana.png
mistrzowski-sztylet.png
runiczny-sztylet.png
bojowy-sztylet.png
obsydianowy-sztylet.png
```

### Pojedyncza ikona — Tier 90 (Tytanowy)

```
Dark fantasy RPG item icon, painted digital game-art style similar to
Diablo / Path of Exile equipment icons.

Titan dagger, bright silver-white metal blade with a crystalline
serrated edge, intense cyan-blue icy glow with tiny frost particles.
Square composition, dagger centered on the diagonal (grip lower-left,
blade tip upper-right), filling about 75-85% of the frame. Dark
near-black background with a faint cracked stone/plaster texture and
soft vignette. Painterly, semi-realistic rendering, strong rim lighting,
dramatic single-direction key light, subtle soft shadow underneath. No
hands, no character, no UI frame, no watermark, no text.
```

Nazwa pliku: `tytanowy-sztylet.png`

## 10. Dzwon (Bell) — 9/9

### Grid A (tiery 10-80)

```
Dark fantasy RPG item icon sheet, ritual war bell weapons, painted
digital game-art style similar to Diablo / Path of Exile equipment
icons.

Layout: clean 4x2 grid, 8 separate icons, generous even padding between
cells, no grid lines, no borders, no labels, no text, no numbers.

Each icon individually:
- square composition, single hand bell centered on the diagonal (handle
  loop in the upper-left, bell body flaring toward the lower-right),
  filling about 75-85% of its cell
- dark near-black background with a faint cracked stone/plaster texture
  and soft vignette (darker corners)
- a soft, diffused glow behind the bell in the tier's accent color (see
  list below), not a hard-edged aura
- painterly, semi-realistic rendering, strong rim lighting on the edges,
  dramatic single-direction key light
- subtle soft shadow under the bell
- no hands, no character, no UI frame, no watermark, no text

Generate the 8 icons as this tier progression, left-to-right then
top-to-bottom, each noticeably more elaborate/powerful than the last:

1. Polished knightly hand bell, plain bronze bell body, simple iron
   handle loop, no glow, warm neutral studio light only.
2. Reinforced steel-banded bell, thicker riveted bell body, no glow.
3. Noble ceremonial bell, gold/brass filigree engraved around the rim,
   polished bronze body, warm faint golden glow.
4. Battle-worn veteran's bell, dented and scarred iron body, worn rope
   handle, no glow.
5. Masterwork bell, flawless mirror-polished bronze body with refined
   engraved patterns, very subtle neutral glow.
6. Runic bell, dark blackened iron body engraved with glowing
   violet-blue runes around the rim, faint arcane glow along the runes
   only.
7. Battlefield war bell, dark rugged iron body, chipped and worn from
   combat, no strong magic - warm dim brownish-orange background
   vignette only.
8. Obsidian bell, black volcanic glass body with glowing cracks, intense
   orange-red fire glow and faint embers swirling around the rim.

Keep consistent lighting, color grading, brush texture and canvas
proportions across all 8 icons so they read as one matched set.
```

Nazwy plików (kolejność 1-8):

```
rycerski-dzwon.png
wzmocniony-stalowy-dzwon.png
dzwon-szlachecki.png
dzwon-weterana.png
mistrzowski-dzwon.png
runiczny-dzwon.png
bojowy-dzwon.png
obsydianowy-dzwon.png
```

### Pojedyncza ikona — Tier 90 (Tytanowy)

```
Dark fantasy RPG item icon, painted digital game-art style similar to
Diablo / Path of Exile equipment icons.

Titan bell, bright silver-white metal body with crystalline spikes
around the rim, intense cyan-blue icy glow with tiny frost particles.
Square composition, bell centered on the diagonal (handle loop
upper-left, body flaring lower-right), filling about 75-85% of the
frame. Dark near-black background with a faint cracked stone/plaster
texture and soft vignette. Painterly, semi-realistic rendering, strong
rim lighting, dramatic single-direction key light, subtle soft shadow
underneath. No hands, no character, no UI frame, no watermark, no text.
```

Nazwa pliku: `tytanowy-dzwon.png`
