# Moduł Walki (Combat)

Moduł obsługuje asynchroniczną, turową walkę (typu Idle) odbywającą się pomiędzy postacią gracza a potworami na poszczególnych mapach. 

## Implementacja
- Pliki logiki: `app/Application/Combat/EncounterService.php`
- Modele: `Encounter`, `Map`, `Monster`

## Mechaniki

### 1. Rozpoczynanie Walki (`start`)
Walka inicjowana jest na konkretnej `Mapie`. W momencie rozpoczęcia walki:
- Gra losuje potwora (`Monster`) z puli przeciwników dostępnych na wybranej mapie.
- Obliczana jest **inicjatywa**. Na podstawie atrybutu **AGI (Zręczność)** postaci gracza i potwora wyłaniana jest strona, która jako pierwsza wykona ruch (`player_first`).
- Utworzona zostaje encja walki (`Encounter`) w stanie `ongoing`, w której przechowywany jest stan pojedynku przed jego symulacją.

### 2. Symulacja Walki (`simulate`)
Serwis w ułamku sekundy symuluje całe starcie, maksymalnie do 50 tur, wymieniając na przemian uderzenia między postacią gracza a potworem, aż HP jednej ze stron spadnie do 0.

Wewnątrz tury występują 3 stany ataku:
- **Trafienie (`hit`):** Standardowy atak zadający obrażenia.
- **Trafienie Krytyczne (`crit`):** Obrażenia x 1.5. Szansa obliczana jest na bazie atrybutu `AGI`. Maksymalnie może wynosić 30% (gracz) i 20% (potwór).
- **Unik / Chybienie (`miss`):** Szansa na całkowity brak obrażeń (sztywno ustawione na 5%).

**Kalkulacja Obrażeń (Damage):**
- Obrażenia zadawane przez gracza to suma: `10 + (STR * 2) + Poziom`. Zmniejszane są one następnie o `Obrona / 2` przeciwnika.
- HP u gracza zależy głównie od `VIT`: `100 + (VIT * 10) + (Poziom * 5)`. HP u potworów skaluje się z ich poziomem.

### 3. Wynik Walki i Nagrody
Na sam koniec symulacji:
- Ustalany jest zwycięzca.
- Jeśli wygrywa gracz, losowane są nagrody – złoto oraz doświadczenie w oparciu o poziom potwora (nagrody skalują się z modyfikatorami bazującymi na różnicy poziomów między graczem a potworem).
- Spotkanie (`Encounter`) oznaczane jest jako wygrane lub przegrane. Uruchamiany jest serwis zrzutów z potwora (`DropService`).
- Pełny log (przebieg wszystkich tur, zadane obrażenia, wyniki losowań RNG) kompresowany jest do formatu JSON i zapisany do bazy danych, by móc zostać odtworzony w UI w formie graficznej walki turowej.
