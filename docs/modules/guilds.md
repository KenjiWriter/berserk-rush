# Moduł: Gildie

## Opis

System gildii pozwala graczom łączyć się w grupy, współpracować, zbierać surowce (EXP, złoto, klejnoty) do wspólnego skarbca i budować społeczność. Każda gildia posiada własny skarbiec, poziom, system ról oraz wbudowany dedykowany kanał czatu.

---

## Zakres funkcjonalności

### Zarządzanie Gildią
- **Tworzenie:** Gracz może założyć gildię, jeśli spełnia wymagania (np. koszt w złocie) i nie należy do innej gildii.
- **Role:** W gildii występują role nadające różne uprawnienia:
  - `leader` (Przywódca) - pełna kontrola (edycja nazwy/opisu, awansowanie członków, wyrzucanie, zarządzanie skarbcem, zapraszanie).
  - `commander` (Dowódca) - zapraszanie, awansowanie niższych ról, wyrzucanie nowicjuszy.
  - `elder` (Starszy) - uprawnienia do zapraszania.
  - `member` (Członek) - standardowa rola, możliwość korzystania z chatu gildii i wpłacania dotacji.
  - `novice` (Nowicjusz) - nowo dołączeni członkowie.

### Skarbiec i Dotacje
Gildie posiadają własny skarbiec na:
- **EXP Gildii** (determinuje poziom gildii)
- **Złoto** (limit zależny od poziomu skarbca)
- **Klejnoty** (Gems)

Gracze mogą przekazywać swoje zasoby na rzecz gildii za pomocą specjalnych komend na chacie gildyjnym:
- `/donate exp <ilość>` - przekazuje EXP gracza do gildii
- `/donate gold <ilość>` - wpłaca złoto do skarbca
- `/donate gems <ilość>` - wpłaca klejnoty do skarbca

Wszystkie operacje skarbca są logowane w historii gildii (`GuildLogs`). Złoto i klejnoty mają określony limit (cap), po przekroczeniu którego dotacje są blokowane, dopóki skarbiec nie zostanie rozbudowany.

### Czat Gildii
- Każda gildia posiada **prywatny kanał komunikacji** (Real-time za pomocą Laravel Reverb).
- Komunikaty o dotacjach (np. "Gracz WojWielki wpłacił 100 złota do skarbca gildii.") są wysyłane automatycznie przez system na chacie gildii w czasie rzeczywistym.

### Zaproszenia (Mail System)
- Rekrutacja odbywa się poprzez wbudowany system poczty w grze.
- Liderzy/Dowódcy/Starsi mogą wysłać zaproszenie do innego gracza bezpośrednio z panelu tooltipa gracza na czacie globalnym.
- Zaproszenie wysyłane jest jako **Wiadomość In-Game z załącznikiem** typu `guild_invite`.
- Gracz po wejściu do skrzynki pocztowej może "odebrać" załącznik, co automatycznie dołącza go do gildii (o ile nadal spełnia warunki i ma miejsce).

---

## Architektura techniczna

### Modele i Tabele
- `Guild`: Główna tabela gildii (nazwa, opis, skarbiec, poziom, max_members).
- `GuildMember`: Tabela łącząca `Guild` i `Character`, przechowująca rolę gracza i datę dołączenia.
- `GuildLog`: Historia akcji (dotacje, dołączenie, opuszczenie).

### Serwisy i Akcje
Zarządzanie gildią opiera się o mechanizmy transakcji by zachować spójność danych:
- Przekazywanie zasobów odbywa się wewnątrz `DB::transaction()`.
- Autoryzacja i operacje na czacie (jak wpisywanie `/donate`) wykonują logikę na żywo w kontrolerach Livewire i rozgłaszają eventy.
