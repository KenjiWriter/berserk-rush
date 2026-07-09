# Moduł Postaci (Characters)

Moduł ten zajmuje się zarządzaniem cyklem życia postaci w grze – jej tworzeniem, postępami oraz przyrostem statystyk.

## Implementacja
- Pliki logiki: `app/Application/Characters/*`
- Modele: `app/Infrastructure/Persistence/Character.php`

## Mechaniki

### 1. Tworzenie Postaci (`CreateCharacter`)
Gracz może utworzyć maksymalnie **4 postacie** na jedno konto. Podczas tworzenia decyduje o nazwie postaci (która musi być unikalna w całej grze) i rozdysponowuje **10 punktów początkowych** pomiędzy 4 główne atrybuty.

Główne Atrybuty:
- **STR (Siła):** Wpływa na fizyczne obrażenia ataku oraz dodaje niewielką ilość życia.
- **INT (Inteligencja):** Wpływa na obrażenia magiczne oraz maksymalną pule many.
- **VIT (Witalność):** Zwiększa punkty życia postaci (HP) oraz nieznacznie redukuje otrzymywane obrażenia.
- **AGI (Zręczność):** Wpływa na zadawane obrażenia dystansowe, szansę na unik, szansę na trafienie krytyczne oraz inicjatywę w walce (kto zaczyna pierwszy).

Każdy z atrybutów podczas początkowego losowania nie może spaść poniżej 0 oraz nie może przekroczyć 10. Stan początkowy zostaje zapisany w kolumnie `attributes` (JSONB).

### 2. Poziomy i XP (`LevelUpService`)
Gracz awansuje na wyższe poziomy poprzez zdobywanie doświadczenia (XP). Wymagane doświadczenie na kolejny poziom obliczane jest w sposób wykładniczy za pomocą wzoru:
`Wymagane_XP = 50 * (1.25 ^ (Obecny_Poziom - 1))`

Kiedy postać zbierze wymaganą ilość XP:
- Zwiększa się jej poziom (`level`).
- Postać otrzymuje 3 punkty postaci (`character_points`) za każdy zdobyty poziom.
- Wywoływane jest zdarzenie `CharacterLeveledUp`, które może zostać odebrane przez listenerów do nadania np. pełnego wyleczenia czy odblokowania nowych lokacji.

### 3. Nagrody z Walki (`RewardService`)
Mechanizm dodający wygrane z walk z powrotem do ekwipunku postaci (złoto i XP). Akcja używa `idempotency_key` w powiązaniu z `CurrencyLedger`, by upewnić się, że jedna wygrana nie przydzieli nagród dwukrotnie. Loguje wszelkie zyski w księdze walut dla celów audytowych.
