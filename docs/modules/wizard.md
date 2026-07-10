# Moduł Czarodzieja (Wizard / Enchanting)

Moduł Czarodzieja pozwala graczom na dodawanie magicznych właściwości (bonusów) do ich przedmiotów. Stanowi on obok systemu ulepszeń drugą główną oś progresji siły ekwipunku, bazującą na mechanikach losowych.

## Implementacja
- Pliki logiki i akcji: 
  - `app/Application/Wizard/EnchantItem.php`
  - `app/Application/Wizard/RerollEnchantments.php`
  - `app/Domain/Items/EnchantmentStrategy.php`
- Komponenty Livewire: `app/Livewire/City/Wizard.php`
- Widoki: `resources/views/livewire/city/wizard.blade.php`

## Mechaniki

### 1. Interfejs Czarodzieja
- **Dostępne Przedmioty:** Czarodziej pozwala zaklinać jedynie przedmioty nadające się do noszenia, tzn. bronie, pancerze i biżuterię. 
- Przedmioty wyposażone na postaci (`equipped`) oraz te w plecaku (`inventory`) są dostępne od razu w oknie Czarodzieja. Wokół przedmiotów, które posiadają już jakieś bonusy, wyświetlana jest pulsująca, magiczna ramka.

### 2. Zaklinanie (Enchanting)
- Do każdego przedmiotu można dodać maksymalnie **5 magicznych bonusów**.
- Szansa na pomyślne zaklinanie maleje z każdym kolejnym dodanym bonusem:
  - 1 bonus: 75% szansy
  - 2 bonus: 50% szansy
  - 3 bonus: 40% szansy
  - 4 bonus: 30% szansy
  - 5 bonus: 20% szansy
- W przypadku **porażki** przy zaklinaniu, przepada jedynie poniesiony koszt w złocie lub gemach. Sam przedmiot nie niszczy się, a jego obecne bonusy pozostają nietknięte.
- Wylosowane statystyki są dopisywane do właściwości JSON `roll_stats['enchants']` w instancji przedmiotu (`ItemInstance`).

### 3. Pule Bonusów i Typy
W zależności od typu przedmiotu losowane są odpowiednie statystyki. Przykładowo:
- **Bronie (Main Hand):** Zwiększone obrażenia fizyczne, magiczne, obrażenia krytyczne, lub silny przeciwko konkretnemu typowi potworów (np. nieumarli, orkowie, demony).
- **Zbroje (Klatka, Głowa, Stopy):** Zwiększone punkty życia, obrona, witalność, czy zmniejszenie otrzymywanych obrażeń.
- **Biżuteria (Szyja, Pierścienie):** Bonusy do statystyk bazowych (np. INT, STR, AGI).

### 4. Reroll (Przelosowanie)
- Przedmioty, które posiadają już przypisane magiczne bonusy (minimum jeden), mogą zostać przelosowane.
- Reroll powoduje wyczyszczenie **wszystkich** dotychczasowych bonusów na danym przedmiocie i wylosowanie ich na nowo w tej samej ilości.
- Koszt rerolla skaluje się w zależności od liczby posiadanych bonusów na przedmiocie (np. 5 bonusów = 5x większy koszt bazowy).

### 5. Ekonomia i Waluty
- System Czarodzieja obsługuje mikropłatności dwuwalutowe: gracz zawsze ma wybór, by zapłacić zwykłym wewnątrzgrowym **Złotem (Gold)** lub walutą premium **Klejnotami (Gems)**.
- Operacje pobrania waluty są rozliczane natychmiast na głównym balansie (złoto na postaci `$character->gold`, a klejnoty na koncie gracza `$user->gems`), a następnie generują log historyczny dla księgowości (`CurrencyLedger`).
